<?php
/**
 * IVR Provider Configuration
 * Update these values with your actual credentials
 */

define('IVR_USERNAME', 'u6824');
define('IVR_TOKEN', 'zBo6eA');
define('IVR_PLAN_ID', '15537');
define('IVR_ANNOUNCEMENT_ID', '694605');
define('IVR_CALLER_ID', '8448305219');

// API endpoints
define('IVR_TRIGGER_URL', 'http://103.255.103.28/api/voice/voice_broadcast.php');
define('IVR_REPORT_URL', 'http://103.255.103.28/api/voice/fetch_report.php');

// DTMF mapping: what each keypress means
define('DTMF_APPROVE', '1');  // Parent presses 1 to approve
define('DTMF_REJECT', '0');   // Parent presses 0 to reject

// Retry settings
define('IVR_RETRY_JSON', json_encode([
    'FNA' => '1',  // Retry on No Answer
    'FBZ' => 0,    // No retry on Busy
    'FCG' => '2',  // Retry on Congestion
    'FFL' => '1'   // Retry on Failed
]));

define('IVR_DTMF_WAIT', 5);
define('IVR_DTMF_WAIT_TIME', 5);
