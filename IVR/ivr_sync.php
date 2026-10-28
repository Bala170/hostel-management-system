<?php
/**
 * IVR Sync Script
 * 
 * This script fetches IVR call reports and updates leave status based on DTMF.
 * 
 * Usage:
 *   - Run via cron every 1-2 minutes: php /path/to/ivr_sync.php
 *   - Or call via HTTP: GET ivr_sync.php
 *   - Or sync specific call: GET ivr_sync.php?ivr_call_id=123
 * 
 * DTMF Mapping:
 *   1 = Approve (parent approved the leave)
 *   2 = Reject (parent rejected the leave)
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$in = array_merge($_GET, $_POST);
$specificCallId = isset($in['ivr_call_id']) ? (int)$in['ivr_call_id'] : 0;

// Get pending IVR calls that need to be checked
if ($specificCallId > 0) {
    $query = "SELECT * FROM ivr_calls WHERE id = ? AND call_status IN ('pending', 'dialed', 'answered', 'not_answered', 'busy', 'congestion', 'failed')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $specificCallId);
} else {
    // Get all pending/answered calls that need processing (not older than 24 hours)
    $query = "SELECT * FROM ivr_calls 
              WHERE call_status IN ('pending', 'dialed', 'answered') 
              AND unique_id IS NOT NULL 
              AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
              ORDER BY created_at ASC
              LIMIT 50";
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$calls = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($calls)) {
    echo json_encode(['status' => 'success', 'message' => 'No pending IVR calls to sync', 'processed' => 0]);
    exit;
}

$results = [];
$processedCount = 0;
$approvedCount = 0;
$rejectedCount = 0;
$errorCount = 0;

foreach ($calls as $call) {
    $uniqueId = $call['unique_id'];
    $leaveId = $call['leave_id'];
    $callId = $call['id'];
    
    // Fetch report from IVR provider
    $params = [
        'username' => IVR_USERNAME,
        'token' => IVR_TOKEN,
        'unique_ids' => $uniqueId
    ];
    
    $apiUrl = IVR_REPORT_URL . '?' . http_build_query($params);
    $ctx = stream_context_create(['http' => ['timeout' => 20]]);
    $response = @file_get_contents($apiUrl, false, $ctx);
    
    if ($response === false) {
        $results[] = [
            'ivr_call_id' => $callId,
            'leave_id' => $leaveId,
            'status' => 'error',
            'message' => 'Failed to fetch report from provider'
        ];
        $errorCount++;
        continue;
    }
    
    $data = json_decode($response, true);
    
    // Update raw response in DB
    $updateRaw = $conn->prepare("UPDATE ivr_calls SET raw_report_response = ? WHERE id = ?");
    $updateRaw->bind_param('si', $response, $callId);
    $updateRaw->execute();
    $updateRaw->close();
    
    // Check if we got valid data
    if (!$data || $data['status'] !== 'success' || !isset($data['data'][$uniqueId])) {
        $results[] = [
            'ivr_call_id' => $callId,
            'leave_id' => $leaveId,
            'status' => 'pending',
            'message' => 'Report not yet available'
        ];
        continue;
    }
    
    $reportData = $data['data'][$uniqueId]['data'] ?? null;
    
    if (!$reportData) {
        $results[] = [
            'ivr_call_id' => $callId,
            'leave_id' => $leaveId,
            'status' => 'pending',
            'message' => 'Report data not available'
        ];
        continue;
    }
    
    // Parse report data
    $callStatus = strtolower($reportData['status'] ?? '');
    $report = strtolower($reportData['report'] ?? '');
    $dtmf = $reportData['dtmf'] ?? '';
    $duration = (int)($reportData['duration'] ?? 0);
    
    // Parse timestamps
    $timeStart = null;
    $timeConnect = null;
    $timeEnd = null;
    
    if (!empty($reportData['time_start'])) {
        $ts = DateTime::createFromFormat('d-M-Y h:i:s A', $reportData['time_start']);
        if ($ts) $timeStart = $ts->format('Y-m-d H:i:s');
    }
    if (!empty($reportData['time_connect'])) {
        $ts = DateTime::createFromFormat('d-M-Y h:i:s A', $reportData['time_connect']);
        if ($ts) $timeConnect = $ts->format('Y-m-d H:i:s');
    }
    if (!empty($reportData['time_end'])) {
        $ts = DateTime::createFromFormat('d-M-Y h:i:s A', $reportData['time_end']);
        if ($ts) $timeEnd = $ts->format('Y-m-d H:i:s');
    }
    
    $retryCount = (int)($reportData['currentRetryCount'] ?? 0);
    
    // Determine new call status
    $newCallStatus = 'dialed';
    if ($report === 'answered') {
        $newCallStatus = 'answered';
    } elseif ($report === 'not answered' || $report === 'no answer') {
        $newCallStatus = 'not_answered';
    } elseif ($report === 'busy') {
        $newCallStatus = 'busy';
    } elseif ($report === 'congestion') {
        $newCallStatus = 'congestion';
    } elseif ($report === 'failed' || $report === 'fail') {
        $newCallStatus = 'failed';
    }
    
    // Update ivr_calls record
    $updateCall = $conn->prepare("
        UPDATE ivr_calls SET 
            call_status = ?,
            dtmf = ?,
            duration = ?,
            time_start = ?,
            time_connect = ?,
            time_end = ?,
            retry_count = ?
        WHERE id = ?
    ");
    $updateCall->bind_param('ssisssii', $newCallStatus, $dtmf, $duration, $timeStart, $timeConnect, $timeEnd, $retryCount, $callId);
    $updateCall->execute();
    $updateCall->close();
    
    // Process DTMF if call was answered
    $leaveAction = null;
    
    if ($report === 'answered' && $dtmf !== '' && $dtmf !== null) {
        if ($dtmf === DTMF_APPROVE) {
            // Approve the leave
            $updateLeave = $conn->prepare("UPDATE leave_applications SET Status = 'Approved' WHERE Leave_ID = ?");
            $updateLeave->bind_param('i', $leaveId);
            $updateLeave->execute();
            $updateLeave->close();
            
            // Mark IVR call as processed
            $markProcessed = $conn->prepare("UPDATE ivr_calls SET call_status = 'processed' WHERE id = ?");
            $markProcessed->bind_param('i', $callId);
            $markProcessed->execute();
            $markProcessed->close();
            
            $leaveAction = 'approved';
            $approvedCount++;
            
        } elseif ($dtmf === DTMF_REJECT) {
            // Reject the leave
            $updateLeave = $conn->prepare("UPDATE leave_applications SET Status = 'Rejected by Parents', Remarks = 'Rejected via IVR call' WHERE Leave_ID = ?");
            $updateLeave->bind_param('i', $leaveId);
            $updateLeave->execute();
            $updateLeave->close();
            
            // Mark IVR call as processed
            $markProcessed = $conn->prepare("UPDATE ivr_calls SET call_status = 'processed' WHERE id = ?");
            $markProcessed->bind_param('i', $callId);
            $markProcessed->execute();
            $markProcessed->close();
            
            $leaveAction = 'rejected';
            $rejectedCount++;
        }
    }
    
    $processedCount++;
    $results[] = [
        'ivr_call_id' => $callId,
        'leave_id' => $leaveId,
        'status' => 'success',
        'call_status' => $newCallStatus,
        'dtmf' => $dtmf,
        'report' => $report,
        'duration' => $duration,
        'leave_action' => $leaveAction
    ];
}

echo json_encode([
    'status' => 'success',
    'message' => "Processed $processedCount IVR calls",
    'summary' => [
        'total' => count($calls),
        'processed' => $processedCount,
        'approved' => $approvedCount,
        'rejected' => $rejectedCount,
        'errors' => $errorCount
    ],
    'results' => $results
]);
