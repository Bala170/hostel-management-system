<?php
set_time_limit(0);
date_default_timezone_set('Asia/Kolkata');

require '../db.php';
require 'vendor/autoload.php';

use Biosync\Biosync;
class Attendance {

    protected $ip = '10.0.251.243';
    protected $device;

    public function connect()
    {
        $this->device = new Biosync($this->ip, 4370);
        $this->device->connect();
        $this->device->disableDevice();
    }

    public function disconnect()
    {
        $this->device->enableDevice();
        $this->device->disconnect();
    }

    public function getAttendance()
    {
        $this->connect();
        $data = $this->device->getAttendance();
        $this->disconnect();
        return $data;
    }
}
function safeGetLogs($ip)
{
    $attendance = new Attendance();
    return $attendance->getAttendance();
}

/* ===============================
   ATTENDANCE FUNCTION (attr.php)
=============================== */

function processAttendance($conn)
{
    try {
        $today = date('Y-m-d');
        $nowTime = date('H:i:s');

        $allLogs = [];

        $sql = "
            SELECT machine_ip, menu_id
            FROM biometric_machines
            WHERE machine_type = 'attendance'
        ";

        $result = mysqli_query($conn, $sql);

        while ($row = mysqli_fetch_assoc($result)) {
            $deviceIp = $row['machine_ip'];

            $logs = safeGetLogs($deviceIp);

            if (empty($logs)) {
                echo "⚠️ No response from $deviceIp - skipped\n";
                continue;
            }

            foreach ($logs as $log) {
                $allLogs[] = $log;
            }
        }

        /* ===============================
           FETCH TIME CONTROL
        =============================== */
        $timeSql = "
            SELECT from_time, to_time, late_entry_time
            FROM attendance_time_control
            WHERE status = 'enabled'
            ORDER BY id DESC
            LIMIT 1
        ";
        $timeResult = mysqli_query($conn, $timeSql);
        $time = mysqli_fetch_assoc($timeResult);
        if (!$time)
            return ['success' => false, 'error' => 'No time control found'];

        $fromTime = $time['from_time'];
        $toTime = $time['to_time'];
        $lateTime = $time['late_entry_time'];

        foreach ($allLogs as $log) {

            $fingerprintId = $log[1];
            $logDateTime = $log[3];
            $logDate = date('Y-m-d', strtotime($logDateTime));
            $logTime = date('H:i:s', strtotime($logDateTime));

            if (
                $logDate !== $today ||
                $logTime < $fromTime ||
                $logTime > $lateTime
            ) {
                continue;
            }

            /* ===============================
               FIND STUDENT
            =============================== */
            $stmt = mysqli_prepare($conn, "
                SELECT student_id, user_id, roll_number
                FROM students
                WHERE fingerprint_id = ?
                LIMIT 1
            ");
            mysqli_stmt_bind_param($stmt, "s", $fingerprintId);
            mysqli_stmt_execute($stmt);
            $student = mysqli_stmt_get_result($stmt)->fetch_assoc();
            if (!$student)
                continue;

            /* ===============================
               CHECK GATE LOG CONDITION
            =============================== */
            $stmt = mysqli_prepare($conn, "
                SELECT log_id
                FROM gate_log
                WHERE user_id = ?
                  AND `out` IS NOT NULL
                  AND in_time IS NULL
                ORDER BY log_id DESC
                LIMIT 1
            ");
            mysqli_stmt_bind_param($stmt, "i", $student['user_id']);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_get_result($stmt)->fetch_assoc())
                continue;

            /* ===============================
               INSERT ATTENDANCE
            =============================== */
            if ($logTime > $toTime && $logTime <= $lateTime) {
                $stmt = mysqli_prepare($conn, "
                    INSERT IGNORE INTO attendance
                    (student_id, roll_number, date, marked_at, status)
                    VALUES (?, ?, ?, ?, 'Late')
                ");
            }

            if ($logTime <= $toTime && $logTime >= $fromTime) {
                $stmt = mysqli_prepare($conn, "
                    INSERT IGNORE INTO attendance
                    (student_id, roll_number, date, marked_at, status)
                    VALUES (?, ?, ?, ?, 'Present')
                ");
            }

            mysqli_stmt_bind_param(
                $stmt,
                "isss",
                $student['student_id'],
                $student['roll_number'],
                $today,
                $logDateTime
            );
            mysqli_stmt_execute($stmt);
        }

        /* ===============================
           BLOCKING LOGIC (runs once after lateTime)
        =============================== */
        if ($nowTime <= $lateTime) {
            return ['success' => true, 'message' => 'Attendance processed, blocking skipped (before late time)'];
        }

        /* ===============================
           DAILY BLOCK CHECK
        =============================== */
        $check = mysqli_query(
            $conn,
            "SELECT 1 FROM daily_blocks WHERE date = CURDATE() LIMIT 1"
        );
        if (mysqli_num_rows($check) > 0) {
            return ['success' => true, 'message' => 'Attendance processed, already blocked today'];
        }

        /* ===============================
           FETCH LEAVE STUDENTS
        =============================== */
        $leaveResult = mysqli_query($conn, "
            SELECT DISTINCT user_id
            FROM gate_log
            WHERE `out` IS NOT NULL
            AND in_time IS NULL
        ");
        $leaveIds = [];
        while ($row = mysqli_fetch_assoc($leaveResult)) {
            $leaveIds[$row['user_id']] = true;
        }

        /* ===============================
           FETCH PRESENT/LATE STUDENTS
        =============================== */
        $presentResult = mysqli_query($conn, "
            SELECT student_id
            FROM attendance
            WHERE status IN ('Present', 'Late')
            AND date = CURDATE()
        ");
        $presentIds = [];
        while ($row = mysqli_fetch_assoc($presentResult)) {
            $presentIds[$row['student_id']] = true;
        }

        /* ===============================
           FETCH ALL STUDENTS
        =============================== */
        $allResult = mysqli_query($conn, "
            SELECT student_id, user_id, roll_number
            FROM students
        ");

        while ($stu = mysqli_fetch_assoc($allResult)) {

            if (
                !isset($presentIds[$stu['student_id']]) &&
                !isset($leaveIds[$stu['user_id']])
            ) {
                $blockCheck = mysqli_prepare($conn, "
                    SELECT 1 FROM blocked_students
                    WHERE student_id = ? AND unblocked_at IS NULL
                    LIMIT 1
                ");
                mysqli_stmt_bind_param($blockCheck, "i", $stu['student_id']);
                mysqli_stmt_execute($blockCheck);
                if (mysqli_stmt_get_result($blockCheck)->fetch_assoc())
                    continue;

                $reason = "Automatically blocked due to missing biometric attendance within the allowed time.";
                $stmt = mysqli_prepare($conn, "
                    INSERT IGNORE INTO attendance
                    (student_id, roll_number, date, status, marked_at, created_at)
                    VALUES (?, ?, ?, 'Absent', NOW(), NOW())
                ");

                mysqli_stmt_bind_param(
                    $stmt,
                    "iss",
                    $stu['student_id'],
                    $stu['roll_number'],
                    $today
                );

                mysqli_stmt_execute($stmt);

                $stmt = mysqli_prepare($conn, "
                    INSERT INTO blocked_students
                    (student_id, reason, blocked_at)
                    VALUES (?, ?, NOW())
                ");
                mysqli_stmt_bind_param(
                    $stmt,
                    "is",
                    $stu['student_id'],
                    $reason
                );
                mysqli_stmt_execute($stmt);
            }
        }

        /* ===============================
           MARK DAY AS PROCESSED
        =============================== */
        mysqli_query(
            $conn,
            "INSERT IGNORE INTO daily_blocks (date) VALUES (CURDATE())"
        );

        return ['success' => true, 'message' => 'Attendance and blocking processed'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/* ===============================
   MESS TOKEN FUNCTION (mtr.php)
=============================== */
function processMessTokens($conn)
{
    try {
        $allLogs = [];

        $sql = "
            SELECT machine_ip, menu_id
            FROM biometric_machines
            WHERE machine_type = 'mess'
        ";

        $result = mysqli_query($conn, $sql);

        while ($row = mysqli_fetch_assoc($result)) {
            $deviceIp = $row['machine_ip'];

            $logs = safeGetLogs($deviceIp);

            if (empty($logs)) {
                echo "⚠️ No response from $deviceIp - skipped\n";
                continue;
            }

            foreach ($logs as $log) {
                $log['device_ip'] = $deviceIp;
                $log['menu_id'] = $row['menu_id'];
                $allLogs[] = $log;
            }
        }

        $processed = 0;
        $skipped = 0;

        foreach ($allLogs as $log) {
            $sql = "SELECT 
                menu_id,
                from_date,
                from_time,
                to_date,
                to_time,
                token_date,
                meal_type,
                menu_items,
                fee,
                created_at,
                max_usage,
                status
            FROM specialtokenenable
            WHERE menu_id = ?
            LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $log['menu_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $menu = $result->fetch_assoc();

            if (!$menu) {
                $skipped++;
                continue;
            }

            $fromDateTime = new DateTime("{$menu['from_date']} {$menu['from_time']}");
            $toDateTime = new DateTime("{$menu['to_date']} {$menu['to_time']}");
            $currentTime = new DateTime();

            if (!($currentTime >= $fromDateTime && $currentTime <= $toDateTime)) {
                $skipped++;
                continue;
            }

            $sql = "SELECT *
                FROM students
                WHERE fingerprint_id = ?
                LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $log[1]);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();

            if (!$student) {
                $skipped++;
                continue;
            }

            $checkSql = "
                SELECT 1 
                FROM mess_tokens 
                WHERE roll_number = ? 
                AND device_ip = ? 
                AND created_at = ?
                LIMIT 1
            ";

            $checkStmt = $conn->prepare($checkSql);
            if (!$checkStmt) {
                continue;
            }

            $checkStmt->bind_param("sss", $student['roll_number'], $log['device_ip'], $log[3]);

            // execute
            $checkStmt->execute();

            // optional: check if record exists
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                continue;
            }
            if ($menu['max_usage'] > 0) {
                $checkSql = "
                SELECT COUNT(*) AS usage_count
                FROM mess_tokens 
                WHERE roll_number = ? 
                AND menu_id = ?
            ";

                $checkStmt = $conn->prepare($checkSql);
                if (!$checkStmt) {
                    die("Prepare failed: " . $conn->error);
                }

                $checkStmt->bind_param("si", $student['roll_number'], $log['menu_id']);
                $checkStmt->execute();

                $result = $checkStmt->get_result();
                $row = $result->fetch_assoc();

                $currentUsage = (int) $row['usage_count'];

                // compare with max usage
                if ($currentUsage >= $menu['max_usage']) {
                    continue;
                }

                // else → CONTINUE YOUR CODE BELOW
                // insert / generate token / etc.
                $checkStmt->close();
            }

            // Re-prepare the duplicate check query (needed after max_usage check)
            $checkSql = "
                SELECT 1 
                FROM mess_tokens 
                WHERE roll_number = ? 
                AND device_ip = ? 
                AND created_at = ?
                LIMIT 1
            ";
            $checkStmt = $conn->prepare($checkSql);
            if (!$checkStmt) {
                continue;
            }

            $checkStmt->bind_param(
                "sss",
                $student['roll_number'],
                $log['device_ip'],
                $log[3]
            );

            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $checkStmt->close();
                $skipped++;
                continue;
            }

            $checkStmt->close();

            $sql = "INSERT INTO mess_tokens (
                roll_number,
                menu_id,
                meal_type,
                `menu`,
                token_date,
                special_fee,
                device_ip,
                generated_at,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?)";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                continue;
            }
            $stmt->bind_param(
                "sisssdss",
                $student['roll_number'],
                $log['menu_id'],
                $menu['meal_type'],
                $menu['menu_items'],
                $menu['token_date'],
                $menu['fee'],
                $log['device_ip'],
                $log[3]
            );

            $stmt->execute();
            $processed++;
        }

        return [
            'success' => true,
            'processed' => $processed,
            'skipped' => $skipped
        ];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/* ===============================
   GATE OUT FUNCTION (outr.php)
=============================== */
function processGateOut($conn)
{
    try {
        $allLogs = [];

        $sql = "
            SELECT machine_ip, menu_id
            FROM biometric_machines
            WHERE machine_type = 'gate_out'
        ";

        $result = mysqli_query($conn, $sql);

        while ($row = mysqli_fetch_assoc($result)) {
            $deviceIp = $row['machine_ip'];

            $logs = safeGetLogs($deviceIp);

            if (empty($logs)) {
                echo "⚠️ No response from $deviceIp - skipped\n";
                continue;
            }

            foreach ($logs as $log) {
                $log['device_ip'] = $deviceIp;
                $allLogs[] = $log;
            }
        }

        $findStudent = $conn->prepare(
            "SELECT user_id, roll_number
             FROM students
             WHERE fingerprint_id = ?
             LIMIT 1"
        );

        $checkExistingOut = $conn->prepare(
            "SELECT log_id
             FROM gate_log
             WHERE user_id = ?
               AND `out` IS NOT NULL
               AND in_time IS NULL
             LIMIT 1"
        );

        $findLeave = $conn->prepare(
            "SELECT Leave_ID
             FROM leave_applications
             WHERE Reg_No = ?
               AND Status = 'Approved'
               AND From_Date <= ?
               AND To_Date >= ?
             ORDER BY Leave_ID DESC
             LIMIT 1"
        );

        $insertGateLog = $conn->prepare(
            "INSERT IGNORE INTO gate_log
             (device_ip, user_id, leave_id, `out`)
             VALUES (?, ?, ?, ?)"
        );

        $processed = 0;
        $skipped = 0;

        foreach ($allLogs as $log) {

            $fingerprintId = preg_replace('/\D/', '', $log[0]);
            $logTime = $log[3];

            if (!$fingerprintId || !$logTime) {
                $skipped++;
                continue;
            }

            $findStudent->bind_param("s", $fingerprintId);
            $findStudent->execute();
            $studentResult = $findStudent->get_result();
            $student = $studentResult->fetch_assoc();

            if (!$student) {
                $skipped++;
                continue;
            }

            // Check if user already has an open gate_log (out without in_time)
            $checkExistingOut->bind_param("i", $student['user_id']);
            $checkExistingOut->execute();
            $existingOutResult = $checkExistingOut->get_result();
            if ($existingOutResult->fetch_assoc()) {
                $skipped++;
                continue;
            }

            // Check if this exact out log already exists (prevent duplicates)
            $checkDuplicate = $conn->prepare(
                "SELECT log_id FROM gate_log 
                 WHERE user_id = ? AND `out` = ? 
                 LIMIT 1"
            );
            $checkDuplicate->bind_param("is", $student['user_id'], $logTime);
            $checkDuplicate->execute();
            if ($checkDuplicate->get_result()->fetch_assoc()) {
                $skipped++;
                continue;
            }

            $findLeave->bind_param(
                "sss",
                $student['roll_number'],
                $logTime,
                $logTime
            );
            $findLeave->execute();
            $leaveResult = $findLeave->get_result();
            $leave = $leaveResult->fetch_assoc();

            if (!$leave) {
                $skipped++;
                continue;
            }

            $insertGateLog->bind_param(
                "siis",
                $log['device_ip'],
                $student['user_id'],
                $leave['Leave_ID'],
                $logTime
            );
            $insertGateLog->execute();

            if ($insertGateLog->affected_rows > 0) {
                $processed++;
            } else {
                $skipped++;
            }
        }

print_r($allLogs);

        return [
            'success' => true,
            'device_logs_received' => count($allLogs),
            'gate_logs_inserted' => $processed,
            'skipped_logs' => $skipped
        ];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/* ===============================
   GATE IN FUNCTION (inr.php)
=============================== */
function processGateIn($conn)
{
    try {
        $allLogs = [];

        $sql = "
            SELECT machine_ip, menu_id
            FROM biometric_machines
            WHERE machine_type = 'gate_in'
        ";

        $result = mysqli_query($conn, $sql);

        while ($row = mysqli_fetch_assoc($result)) {
            $deviceIp = $row['machine_ip'];

            $logs = safeGetLogs($deviceIp);

            if (empty($logs)) {
                echo "⚠️ No response from $deviceIp - skipped\n";
                continue;
            }

            foreach ($logs as $log) {
                $allLogs[] = $log;
            }
        }

        $findStudent = $conn->prepare(
            "SELECT user_id, roll_number
             FROM students
             WHERE fingerprint_id = ?
             LIMIT 1"
        );

        $findLeave = $conn->prepare(
            "SELECT Leave_ID
             FROM leave_applications
             WHERE Reg_No = ?
               AND Status = 'Approved'
               AND From_Date <= ?
               AND To_Date >= ?
             ORDER BY Leave_ID DESC
             LIMIT 1"
        );

        $updateGateLog = $conn->prepare(
            "UPDATE gate_log
             SET in_time = NOW()
             WHERE user_id = ?
               AND leave_id = ?
               AND in_time IS NULL
             LIMIT 1"
        );

        $updated = 0;
        $skipped = 0;

        foreach ($allLogs as $log) {

            $fingerprintId = preg_replace('/\D/', '', $log[0]);
            $logTime = $log[3];

            if (!$fingerprintId || !$logTime) {
                $skipped++;
                continue;
            }

            $findStudent->bind_param("s", $fingerprintId);
            $findStudent->execute();
            $studentRes = $findStudent->get_result();
            $student = $studentRes->fetch_assoc();

            if (!$student) {
                $skipped++;
                continue;
            }

            $findLeave->bind_param(
                "sss",
                $student['roll_number'],
                $logTime,
                $logTime
            );
            $findLeave->execute();
            $leaveRes = $findLeave->get_result();
            $leave = $leaveRes->fetch_assoc();

            if (!$leave) {
                $skipped++;
                continue;
            }

            $updateGateLog->bind_param(
                "ii",
                $student['user_id'],
                $leave['Leave_ID']
            );
            $updateGateLog->execute();

            if ($updateGateLog->affected_rows > 0) {
                $updated++;
            } else {
                $skipped++;
            }
        }

        return [
            'success' => true,
            'device_logs_received' => count($allLogs),
            'gate_logs_updated' => $updated,
            'skipped_logs' => $skipped
        ];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/* ===============================
   IVR SYNC FUNCTION
=============================== */
function processIvrSync($conn, $specificCallId = 0)
{
    try {
        require_once __DIR__ . '/../IVR/config.php';

        if (!defined('IVR_REPORT_URL') || !defined('IVR_USERNAME') || !defined('IVR_TOKEN')) {
            return ['success' => false, 'error' => 'IVR config missing (IVR_REPORT_URL/IVR_USERNAME/IVR_TOKEN)'];
        }

        $specificCallId = (int) $specificCallId;

        if ($specificCallId > 0) {
            $query = "SELECT * FROM ivr_calls WHERE id = ? AND call_status IN ('pending', 'dialed', 'answered', 'not_answered', 'busy', 'congestion', 'failed')";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                return ['success' => false, 'error' => 'Failed to prepare IVR query'];
            }
            $stmt->bind_param('i', $specificCallId);
        } else {
            $query = "SELECT * FROM ivr_calls 
                      WHERE call_status IN ('pending', 'dialed', 'answered')
                      AND unique_id IS NOT NULL
                      AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                      ORDER BY created_at ASC
                      LIMIT 50";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                return ['success' => false, 'error' => 'Failed to prepare IVR query'];
            }
        }

        $stmt->execute();
        $calls = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($calls)) {
            return ['success' => true, 'message' => 'No pending IVR calls to sync', 'processed' => 0];
        }

        $results = [];
        $processedCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;
        $errorCount = 0;

        foreach ($calls as $call) {
            $uniqueId = $call['unique_id'];
            $leaveId = (int) $call['leave_id'];
            $callId = (int) $call['id'];

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

            $updateRaw = $conn->prepare("UPDATE ivr_calls SET raw_report_response = ? WHERE id = ?");
            if ($updateRaw) {
                $updateRaw->bind_param('si', $response, $callId);
                $updateRaw->execute();
                $updateRaw->close();
            }

            if (!$data || ($data['status'] ?? '') !== 'success' || !isset($data['data'][$uniqueId])) {
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

            $report = strtolower($reportData['report'] ?? '');
            $dtmf = $reportData['dtmf'] ?? '';
            $duration = (int) ($reportData['duration'] ?? 0);

            $timeStart = null;
            $timeConnect = null;
            $timeEnd = null;

            if (!empty($reportData['time_start'])) {
                $ts = DateTime::createFromFormat('d-M-Y h:i:s A', $reportData['time_start']);
                if ($ts) {
                    $timeStart = $ts->format('Y-m-d H:i:s');
                }
            }
            if (!empty($reportData['time_connect'])) {
                $ts = DateTime::createFromFormat('d-M-Y h:i:s A', $reportData['time_connect']);
                if ($ts) {
                    $timeConnect = $ts->format('Y-m-d H:i:s');
                }
            }
            if (!empty($reportData['time_end'])) {
                $ts = DateTime::createFromFormat('d-M-Y h:i:s A', $reportData['time_end']);
                if ($ts) {
                    $timeEnd = $ts->format('Y-m-d H:i:s');
                }
            }

            $retryCount = (int) ($reportData['currentRetryCount'] ?? 0);

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

            $updateCall = $conn->prepare(
                "UPDATE ivr_calls SET 
                    call_status = ?,
                    dtmf = ?,
                    duration = ?,
                    time_start = ?,
                    time_connect = ?,
                    time_end = ?,
                    retry_count = ?
                 WHERE id = ?"
            );
            if ($updateCall) {
                $updateCall->bind_param('ssisssii', $newCallStatus, $dtmf, $duration, $timeStart, $timeConnect, $timeEnd, $retryCount, $callId);
                $updateCall->execute();
                $updateCall->close();
            }

            $leaveAction = null;
            if ($report === 'answered' && $dtmf !== '' && $dtmf !== null) {
                if (defined('DTMF_APPROVE') && $dtmf === DTMF_APPROVE) {
                    $updateLeave = $conn->prepare("UPDATE leave_applications SET Status = 'Approved' WHERE Leave_ID = ?");
                    if ($updateLeave) {
                        $updateLeave->bind_param('i', $leaveId);
                        $updateLeave->execute();
                        $updateLeave->close();
                    }

                    $markProcessed = $conn->prepare("UPDATE ivr_calls SET call_status = 'processed' WHERE id = ?");
                    if ($markProcessed) {
                        $markProcessed->bind_param('i', $callId);
                        $markProcessed->execute();
                        $markProcessed->close();
                    }

                    $leaveAction = 'approved';
                    $approvedCount++;
                } elseif (defined('DTMF_REJECT') && $dtmf === DTMF_REJECT) {
                    $updateLeave = $conn->prepare("UPDATE leave_applications SET Status = 'Rejected by Parents', Remarks = 'Rejected via IVR call' WHERE Leave_ID = ?");
                    if ($updateLeave) {
                        $updateLeave->bind_param('i', $leaveId);
                        $updateLeave->execute();
                        $updateLeave->close();
                    }

                    $markProcessed = $conn->prepare("UPDATE ivr_calls SET call_status = 'processed' WHERE id = ?");
                    if ($markProcessed) {
                        $markProcessed->bind_param('i', $callId);
                        $markProcessed->execute();
                        $markProcessed->close();
                    }

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

        return [
            'success' => true,
            'message' => "Processed $processedCount IVR calls",
            'summary' => [
                'total' => count($calls),
                'processed' => $processedCount,
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
                'errors' => $errorCount
            ],
            'results' => $results
        ];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/* ===============================
   MAIN RUNNER LOOP
=============================== */
header('Content-Type: application/json');

$loopInterval = 10; // seconds between each cycle
$maxIterations = 0; // 0 = infinite loop

$iteration = 0;
$results = [];

while (true) {
    $iteration++;
    $cycleStart = date('Y-m-d H:i:s');

    echo "\n[Cycle $iteration started at $cycleStart]\n";

    // 1. Process Attendance
    echo "Processing Attendance...\n";
    $attendanceResult = processAttendance($conn);
    $results['attendance'] = $attendanceResult;
    echo "Attendance: " . json_encode($attendanceResult) . "\n";

    // 2. Process Mess Tokens
    echo "Processing Mess Tokens...\n";
    $messResult = processMessTokens($conn);
    $results['mess_tokens'] = $messResult;
    echo "Mess Tokens: " . json_encode($messResult) . "\n";

    // 3. Process Gate Out
    echo "Processing Gate Out...\n";
    $gateOutResult = processGateOut($conn);
    $results['gate_out'] = $gateOutResult;
    echo "Gate Out: " . json_encode($gateOutResult) . "\n";

    // 4. Process Gate In
    echo "Processing Gate In...\n";
    $gateInResult = processGateIn($conn);
    $results['gate_in'] = $gateInResult;
    echo "Gate In: " . json_encode($gateInResult) . "\n";

    // 5. IVR Sync
    echo "Processing IVR Sync...\n";
    $ivrSyncResult = processIvrSync($conn);
    $results['ivr_sync'] = $ivrSyncResult;
    echo "IVR Sync: " . json_encode($ivrSyncResult) . "\n";

    $cycleEnd = date('Y-m-d H:i:s');
    echo "[Cycle $iteration completed at $cycleEnd]\n";

    // Check if we should stop
    if ($maxIterations > 0 && $iteration >= $maxIterations) {
        echo "\nMax iterations ($maxIterations) reached. Stopping.\n";
        break;
    }

    // Wait before next cycle
    echo "Waiting $loopInterval seconds before next cycle...\n";
    // sleep($loopInterval);
}

echo json_encode([
    'success' => true,
    'total_iterations' => $iteration,
    'last_results' => $results
]);
