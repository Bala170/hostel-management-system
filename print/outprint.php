<?php
set_time_limit(0);
require '../db.php';   // must define $conn (mysqli)

header('Content-Type: application/json');

$esc = "\x1B";
$gs  = "\x1D";

$action = $_POST['action'] ?? 'get_outpass';
$device_ip = $_POST['device_ip'] ?? '';

try {
    if ($action === 'get_outpass') {
        if (trim($device_ip) === '') {
            echo json_encode([
                'status' => 'error',
                'message' => 'device_ip is required'
            ]);
            exit;
        }

        $stmt = $conn->prepare(
            "SELECT 
                g.log_id,
                g.out AS gate_time,
                s.roll_number,
                s.name,
                r.room_number AS room_no,
                l.From_Date,
                l.To_Date
             FROM gate_log g
             JOIN students s ON s.user_id = g.user_id
             JOIN rooms r ON r.room_id = s.room_id
             JOIN leave_applications l ON l.Leave_ID = g.leave_id
             WHERE g.generated_at IS NULL
               AND g.device_ip = ?
             ORDER BY g.out ASC"
        );

        $stmt->bind_param('s', $device_ip);
        $stmt->execute();
        $result = $stmt->get_result();

        $tokens = [];

        while ($row = $result->fetch_assoc()) {
            $data  = $esc . "@";
            $data .= $esc . "a" . "\x01";
            $data .= $esc . "E" . "\x01";
            $data .= "MKCE HOSTEL\n";
            $data .= $esc . "E" . "\x00";
            $data .= $esc . "a" . "\x01";
            $data .= strtoupper(date('d-m-Y H:i', strtotime($row['gate_time']))) . "\n\n";

            $data .= $esc . "a" . "\x00";
            $data .= $esc . "E" . "\x01";
            $data .= "    ROLL NO : ";
            $data .= $esc . "E" . "\x00";
            $data .= strtoupper($row['roll_number']) . "\n";

            $data .= $esc . "E" . "\x01";
            $data .= "    NAME : ";
            $data .= $esc . "E" . "\x00";
            $data .= strtoupper($row['name']) . "\n";

            $data .= $esc . "E" . "\x01";
            $data .= "    ROOM NO : ";
            $data .= $esc . "E" . "\x00";
            $data .= strtoupper($row['room_no']) . "\n";

            $data .= $esc . "E" . "\x01";
            $data .= "    LEAVE FROM : ";
            $data .= $esc . "E" . "\x00";
            $data .= strtoupper(date('d-m-Y H:i', strtotime($row['From_Date']))) . "\n";

            $data .= $esc . "E" . "\x01";
            $data .= "    LEAVE TO   : ";
            $data .= $esc . "E" . "\x00";
            $data .= strtoupper(date('d-m-Y H:i', strtotime($row['To_Date']))) . "\n";

            $data .= "\n\n\n\n";
            $data .= $gs . "V" . "\x00";

            $tokens[] = [
                'log_id' => $row['log_id'],
                'print_data' => base64_encode($data)
            ];
        }

        echo json_encode([
            'status' => 'success',
            'tokens' => $tokens
        ]);
        exit;

    } elseif ($action === 'mark_generation') {
        $log_ids = $_POST['log_ids'] ?? [];

        if (is_string($log_ids)) {
            $log_ids = array_filter(array_map('trim', explode(',', $log_ids)), function ($id) {
                return $id !== '';
            });
        }

        if (!is_array($log_ids) || count($log_ids) === 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No log_ids provided'
            ]);
            exit;
        }

        $log_ids = array_values(array_unique(array_map('intval', $log_ids)));

        if (count($log_ids) === 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No valid log IDs provided'
            ]);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($log_ids), '?'));
        $sql = "UPDATE gate_log SET generated_at = NOW() WHERE log_id IN ($placeholders)";
        $stmt = $conn->prepare($sql);

        $types = str_repeat('i', count($log_ids));
        $stmt->bind_param($types, ...$log_ids);
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'updated' => $stmt->affected_rows
        ]);
        exit;

    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Unknown action'
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}

