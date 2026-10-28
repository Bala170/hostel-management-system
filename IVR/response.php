<?php
/**
 * IVR Response Checker (Debug/Testing)
 * 
 * Usage:
 *   GET response.php?unique_ids=2391204_4_3050-u6824
 * 
 * For production, use ivr_sync.php instead.
 */

require_once __DIR__ . '/config.php';

$in = array_merge($_GET, $_POST);

$uniqueIds = isset($in['unique_ids']) ? (string)$in['unique_ids'] : '';

if (empty($uniqueIds)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'unique_ids parameter is required']);
    exit;
}

$params = [
    'username' => IVR_USERNAME,
    'token' => IVR_TOKEN,
    'unique_ids' => $uniqueIds
];

$api = IVR_REPORT_URL . '?' . http_build_query($params);

$ctx = stream_context_create(['http' => ['timeout' => 20]]);
$response = @file_get_contents($api, false, $ctx);

if ($response === false) {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Failed to call IVR provider API']);
    exit;
}

// Pretty print for debugging
header('Content-Type: application/json');
$data = json_decode($response, true);
echo json_encode($data, JSON_PRETTY_PRINT);
