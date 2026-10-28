<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

set_time_limit(0);

require '../db.php';

header('Content-Type: application/json');

$esc = "\x1B";
$gs  = "\x1D";

$action = $_POST['action'] ?? '';

/* =====================================================
   GET TOKENS
===================================================== */
if ($action === "get_messtokens") {

    $device_ip = $_POST['device_ip'];

    $sql = "SELECT 
            token_id,
            roll_number,
            meal_type,
            menu,
            special_fee,
            created_at
        FROM mess_tokens
        WHERE generated_at IS NULL
        AND device_ip = ?
        ORDER BY created_at ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $device_ip);
    $stmt->execute();

    $result = $stmt->get_result();

    $tokens = [];

    while ($token = $result->fetch_assoc()) {

        /* GET STUDENT NAME */
        $sql2 = "SELECT name FROM students WHERE roll_number = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("s", $token['roll_number']);
        $stmt2->execute();
        $res2 = $stmt2->get_result();

        if ($res2->num_rows == 0) {
            continue;
        }

        $row = $res2->fetch_assoc();

        /* =====================================
           BUILD ESC/POS DATA
        ===================================== */

        $data  = $esc . "@";

        // HEADER
        $data .= $esc . "a" . "\x01";
        $data .= $gs  . "!" . "\x11";
        $data .= "MESS TOKEN\n";
        $data .= $gs  . "!" . "\x00";
        $data .= "-----------------------------\n";

        // DATE
        $data .= $esc . "a" . "\x01";
        $data .= date('d-m-Y h:i A', strtotime($token['created_at'])) . "\n\n";

        // BODY
        $data .= $esc . "a" . "\x00";

        $data .= "ROLL NO  : " . strtoupper($token['roll_number']) . "\n";
        $data .= "NAME     : " . strtoupper($row['name']) . "\n";
        $data .= "MEAL     : " . strtoupper($token['meal_type']) . "\n";
        $data .= "MENU     : " . strtoupper($token['menu']) . "\n";

        if (!empty($token['special_fee']) && $token['special_fee'] > 0) {
            $data .= "EXTRA ₹ : " . number_format($token['special_fee'], 2) . "\n";
        }

        // CUT
        $data .= "\n\n\n";
        $data .= $gs . "V" . "\x00";

        /* STORE TOKEN + PRINT DATA */

        $tokens[] = [
            "token_id" => $token['token_id'],
            "print_data" => base64_encode($data)
        ];
    }

    echo json_encode([
        "status" => "success",
        "tokens" => $tokens
    ]);

    exit;
}


/* =====================================================
   MARK GENERATED
===================================================== */
if ($action === "mark_generation") {

    $token_ids = $_POST['token_ids'] ?? [];

    // Accept either array or comma-separated string (e.g. "195,196")
    if (is_string($token_ids)) {
        $token_ids = array_filter(array_map('trim', explode(',', $token_ids)), function ($id) {
            return $id !== '';
        });
    }

    if (!is_array($token_ids) || count($token_ids) == 0) {
        echo json_encode([
            "status" => "error",
            "message" => "No tokens provided"
        ]);
        exit;
    }

    // Normalize to integer IDs
    $token_ids = array_values(array_unique(array_map('intval', $token_ids)));

    if (count($token_ids) == 0) {
        echo json_encode([
            "status" => "error",
            "message" => "No valid token IDs provided"
        ]);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($token_ids), '?'));

    $sql = "UPDATE mess_tokens
            SET generated_at = NOW()
            WHERE token_id IN ($placeholders)";

    $stmt = $conn->prepare($sql);

    $types = str_repeat("i", count($token_ids));
    $stmt->bind_param($types, ...$token_ids);

    $stmt->execute();

    echo json_encode([
        "status" => "success",
        "updated" => $stmt->affected_rows
    ]);

    exit;
}