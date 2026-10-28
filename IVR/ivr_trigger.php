<?php
// INDHA FILE DEBUG PANRATHUKU MATTUM THA..IF to check IVR response for testing

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$in = array_merge($_GET, $_POST);

// Validate leave_id
$leaveId = isset($in['leave_id']) ? (int)$in['leave_id'] : 0;
if ($leaveId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'leave_id is required']);
    exit;
}

// Check if leave exists and get student info
$stmt = $conn->prepare("
    SELECT la.Leave_ID, la.Reg_No, la.Status, s.student_id, s.name as student_name
    FROM leave_applications la
    JOIN students s ON la.Reg_No = s.roll_number
    WHERE la.Leave_ID = ?
");
$stmt->bind_param('i', $leaveId);
$stmt->execute();
$leaveResult = $stmt->get_result();

if ($leaveResult->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Leave application not found']);
    exit;
}

$leave = $leaveResult->fetch_assoc();
$stmt->close();

// Check if already has a pending/processed IVR call
$checkStmt = $conn->prepare("SELECT id, call_status, dtmf FROM ivr_calls WHERE leave_id = ? ORDER BY id DESC LIMIT 1");
$checkStmt->bind_param('i', $leaveId);
$checkStmt->execute();
$existingCall = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($existingCall && in_array($existingCall['call_status'], ['pending', 'dialed', 'answered'])) {
    echo json_encode([
        'status' => 'warning',
        'message' => 'IVR call already in progress for this leave',
        'ivr_call_id' => $existingCall['id'],
        'call_status' => $existingCall['call_status']
    ]);
    exit;
}

// Get contact number - either from request or from guardians table (primary guardian)
$contactNumber = isset($in['contact_numbers']) ? preg_replace('/[^0-9]/', '', $in['contact_numbers']) : '';

if (empty($contactNumber)) {
    // Fetch primary guardian's phone
    $guardianStmt = $conn->prepare("
        SELECT phone FROM guardians 
        WHERE student_id = ? AND approval_type = 'primary' AND phone IS NOT NULL AND phone != '' AND phone != 'NIL'
        LIMIT 1
    ");
    $guardianStmt->bind_param('i', $leave['student_id']);
    $guardianStmt->execute();
    $guardian = $guardianStmt->get_result()->fetch_assoc();
    $guardianStmt->close();
    
    if ($guardian && !empty($guardian['phone'])) {
        $contactNumber = preg_replace('/[^0-9]/', '', $guardian['phone']);
    }
}

if (empty($contactNumber) || strlen($contactNumber) < 10) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No valid parent phone number found']);
    exit;
}

// Build IVR API params
$params = [
    'username' => IVR_USERNAME,
    'token' => IVR_TOKEN,
    'plan_id' => IVR_PLAN_ID,
    'announcement_id' => IVR_ANNOUNCEMENT_ID,
    'caller_id' => IVR_CALLER_ID,
    'contact_numbers' => $contactNumber,
    'retry_json' => IVR_RETRY_JSON,
    'dtmf_wait' => IVR_DTMF_WAIT,
    'dtmf_wait_time' => IVR_DTMF_WAIT_TIME,
];

$apiUrl = IVR_TRIGGER_URL . '?' . http_build_query($params);

// Call IVR provider
$ctx = stream_context_create(['http' => ['timeout' => 30]]);
$response = @file_get_contents($apiUrl, false, $ctx);

if ($response === false) {
    // Log the failed attempt
    $insertStmt = $conn->prepare("
        INSERT INTO ivr_calls (leave_id, contact_number, call_status, raw_trigger_response)
        VALUES (?, ?, 'failed', 'API call failed')
    ");
    $insertStmt->bind_param('is', $leaveId, $contactNumber);
    $insertStmt->execute();
    $insertStmt->close();
    
    http_response_code(502);
    echo json_encode(['status' => 'error', 'message' => 'Failed to call IVR provider']);
    exit;
}

$data = json_decode($response, true);

// Check if successful
if (!$data || $data['status'] !== 'success') {
    $insertStmt = $conn->prepare("
        INSERT INTO ivr_calls (leave_id, contact_number, call_status, raw_trigger_response)
        VALUES (?, ?, 'failed', ?)
    ");
    $insertStmt->bind_param('iss', $leaveId, $contactNumber, $response);
    $insertStmt->execute();
    $insertStmt->close();
    
    echo json_encode([
        'status' => 'error',
        'message' => 'IVR provider returned error',
        'provider_response' => $data
    ]);
    exit;
}

// Extract unique_id from response
$uniqueId = null;
$systemApiUniqueId = null;

if (isset($data['data'][0])) {
    $uniqueId = $data['data'][0]['unique_id'] ?? null;
    $systemApiUniqueId = $data['data'][0]['system_api_uniqueid'] ?? null;
}

// Store in database
$insertStmt = $conn->prepare("
    INSERT INTO ivr_calls (leave_id, contact_number, unique_id, system_api_uniqueid, call_status, raw_trigger_response)
    VALUES (?, ?, ?, ?, 'pending', ?)
");
$insertStmt->bind_param('issss', $leaveId, $contactNumber, $uniqueId, $systemApiUniqueId, $response);
$insertStmt->execute();
$ivrCallId = $conn->insert_id;
$insertStmt->close();

// Update leave status to 'IVR Pending' - moves to processed tab
$updateLeaveStmt = $conn->prepare("UPDATE leave_applications SET Status = 'IVR Pending', Remarks = 'Awaiting parent response via IVR call' WHERE Leave_ID = ?");
$updateLeaveStmt->bind_param('i', $leaveId);
$updateLeaveStmt->execute();
$updateLeaveStmt->close();

echo json_encode([
    'status' => 'success',
    'message' => 'IVR call triggered successfully',
    'data' => [
        'ivr_call_id' => $ivrCallId,
        'leave_id' => $leaveId,
        'contact_number' => $contactNumber,
        'unique_id' => $uniqueId,
        'student_name' => $leave['student_name']
    ]
]);
