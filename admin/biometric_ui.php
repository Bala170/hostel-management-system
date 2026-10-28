<?php
session_start();
include './admin_scope.php';
if (!is_any_admin_role()) {
    header('Location: ../login');
    exit;
}
?>
<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db.php';

// Test database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Debug: Check what database we're connected to
$database_result = $conn->query("SELECT DATABASE() as db_name");
if ($database_result) {
    $database_row = $database_result->fetch_assoc();
    error_log("Connected to database: " . $database_row['db_name']);
} else {
    error_log("Failed to get database name: " . $conn->error);
}

date_default_timezone_set('Asia/Kolkata');

function shorten_department_name(string $department): string
{
    $shortNames = [
        // AIDS variations
        'Artificial Intelligence & Data Science' => 'AIDS',
        'Artificial Intelligence & Data Science (AIDS)' => 'AIDS',
        'artificial_intelligence_data_science' => 'AIDS',
        'Artificial Intelligence & Data Science AIDS' => 'AIDS',

        // CSE variations
        'Computer Science & Engineering' => 'CSE',
        'computer_science_&_engineering' => 'CSE',
        'Computer Science and Business Systems' => 'CSBS',
        'computer_science_and_business_systems' => 'CSBS',
        'computer_science_business_systems' => 'CSBS',
        'computer_science_engineering' => 'CSE',

        // IT variations
        'Information Technology' => 'IT',
        'information_technology' => 'IT',

        // MECH variations
        'Mechanical Engineering' => 'MECH',
        'mechanical_engineering' => 'MECH',
        'Mech' => 'MECH',

        // CIVIL variations
        'Civil Engineering' => 'CIVIL',
        'civil_engineering' => 'CIVIL',

        // ECE variations
        'Electronics & Communication Engineering' => 'ECE',
        'electronics_&_communication_engineering' => 'ECE',
        'electronics_communication_engineering' => 'ECE',

        // EEE variations
        'Electrical & Electronics Engineering' => 'EEE',
        'electrical_&_electronics_engineering' => 'EEE',

        // EIE variations
        'Electronics & Instrumentation Engineering' => 'EIE',
        'electronics_&_instrumentation_engineering' => 'EIE',

        // VLSI variations
        'VLSI Design' => 'VLSI',
        'vlsi_design' => 'VLSI'
    ];

    // Try exact match first
    if (isset($shortNames[$department])) {
        return $shortNames[$department];
    }

    // Try lowercase match
    $lowerDepartment = strtolower($department);
    $lowerShortNames = [];
    foreach ($shortNames as $key => $value) {
        $lowerShortNames[strtolower($key)] = $value;
    }

    return $lowerShortNames[$lowerDepartment] ?? $department;
}

function fetchDistinctValues(mysqli $conn, string $query, string $column): array
{
    // Check database connection
    if (!$conn || $conn->connect_error) {
        return [];
    }

    $values = [];
    if ($result = $conn->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $value = trim((string) ($row[$column] ?? ''));
            if ($value !== '') {
                $values[] = $value;
            }
        }
        $result->free();
    }
    return $values;
}

$rawDepartments = fetchDistinctValues(
    $conn,
    "SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department <> '' ORDER BY department ASC",
    'department'
);

// Deduplicate departments based on their shortened names
$departments = [];
$seenShortNames = [];
foreach ($rawDepartments as $department) {
    $shortName = shorten_department_name($department);
    if (!in_array($shortName, $seenShortNames)) {
        $seenShortNames[] = $shortName;
        $departments[] = $department;
    }
}

// Sort departments by their shortened names for consistent display
usort($departments, function ($a, $b) {
    $shortA = shorten_department_name($a);
    $shortB = shorten_department_name($b);
    return strcmp($shortA, $shortB);
});

// Use hardcoded values from room details (room.php)
$blocks = ['North', 'South', 'East', 'West'];

$floors = ['I', 'II', 'III', 'IV', 'V'];

$roomTypes = ['Non-AC', 'AC'];

$academicBatches = fetchDistinctValues(
    $conn,
    "SELECT DISTINCT academic_batch FROM academic_batch ORDER BY academic_batch DESC",
    'academic_batch'
);

// Debug: Check if we have a valid connection and database
if ($conn) {
    echo "<!-- Database info: " . $conn->host_info . " -->\n";
    echo "<!-- Selected database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . " -->\n";
}

$defaultDate = date('Y-m-d');

$monthOptions = [
    'all' => 'All Months',
    '01' => 'January',
    '02' => 'February',
    '03' => 'March',
    '04' => 'April',
    '05' => 'May',
    '06' => 'June',
    '07' => 'July',
    '08' => 'August',
    '09' => 'September',
    '10' => 'October',
    '11' => 'November',
    '12' => 'December',
];

$attendanceFilters = [
    'present' => 'Present',
    'absent' => 'Absent',
    'onduty' => 'On Duty (OD)',
    'onleave' => 'Students on Leave',
    'lateentry' => 'Late Entry Students',
    'blocked' => 'Blocked',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Generation | Hostel Management</title>
    <link rel="icon" type="image/png" sizes="32x32" href="image/icons/mkce_s.png">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-5/bootstrap-5.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --topbar-height: 60px;
            --footer-height: 60px;
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --dark-bg: #1a1c23;
            --light-bg: #f8f9fc;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            color: #333;
            line-height: 1.6;
        }

        .content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .sidebar.collapsed+.content {
            margin-left: var(--sidebar-collapsed-width);
        }

        .breadcrumb-area {
            background-image: linear-gradient(to top, #fff1eb 0%, #ace0f9 100%);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin: 20px;
            padding: 15px 20px;
        }

        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb-item a:hover {
            color: #224abe;
        }

        .filter-card {
            border: none;
            box-shadow: var(--card-shadow);
            border-radius: 15px;
        }

        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            border: 1px solid rgba(78, 115, 223, 0.35);
            background: rgba(78, 115, 223, 0.08);
            font-size: 0.85rem;
            margin: 4px;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #fff;
        }

        .status-present {
            background: linear-gradient(135deg, #1cc88a, #17a673);
        }

        .status-absent {
            background: linear-gradient(135deg, #f6c23e, #f4b619);
            color: #212529;
        }

        .status-onduty {
            background: linear-gradient(135deg, #36b9cc, #2c9faf);
        }

        .status-onleave {
            background: linear-gradient(135deg, #ff7eb9, #ff758c);
        }

        .status-lateentry {
            background: linear-gradient(135deg, #ff9f43, #ff6f00);
        }

        .status-blocked {
            background: linear-gradient(135deg, #e74a3b, #c82333);
        }

        .gradient-header {
            --bs-table-bg: transparent;
            --bs-table-color: #fff;
            background: linear-gradient(135deg, #4CAF50, #2196F3) !important;
            text-align: center;
            font-size: 0.9em;
        }

        .loader-container {
            position: fixed;
            left: var(--sidebar-width);
            right: 0;
            top: var(--topbar-height);
            bottom: var(--footer-height);
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            transition: left 0.3s ease;
        }

        .loader-container.hide {
            display: none;
        }

        .sidebar.collapsed+.content .loader-container {
            left: var(--sidebar-collapsed-width);
        }

        .loader {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-radius: 50%;
            border-top: 5px solid var(--primary-color);
            border-right: 5px solid var(--success-color);
            border-bottom: 5px solid var(--primary-color);
            border-left: 5px solid var(--success-color);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .dt-buttons .btn {
            margin-right: 8px;
            border-radius: 999px;
        }

        .badge-divider {
            display: inline-block;
            height: 24px;
            width: 1px;
            background: rgba(255, 255, 255, 0.4);
            margin: 0 8px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width) !important;
            }

            .sidebar.mobile-show {
                transform: translateX(0);
            }

            .topbar {
                left: 0 !important;
            }

            .content {
                margin-left: 0 !important;
            }

            .filter-chip {
                width: 100%;
                justify-content: space-between;
            }

            .dt-buttons {
                margin-bottom: 10px;
            }
        }

        /* Leave type badge styles (aligned with Task page chips) */
        .lv-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .2rem .6rem;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 700;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .lv-success {
            background: #ecfdf5;
            color: #065f46;
            border-color: #a7f3d0;
        }

        .lv-warning {
            background: #fffbeb;
            color: #92400e;
            border-color: #fde68a;
        }

        .lv-danger {
            background: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
        }

        .lv-info {
            background: #eff6ff;
            color: #1e40af;
            border-color: #bfdbfe;
        }

        .lv-primary {
            background: #eef2ff;
            color: #3730a3;
            border-color: #c7d2fe;
        }

        .lv-muted {
            background: #f3f4f6;
            color: #374151;
            border-color: #e5e7eb;
        }

        /* Center-align biometric config tables */
        .machines-table th,
        .machines-table td {
            text-align: center;
            vertical-align: middle;
        }
    </style>
</head>

<body>

    <?php include '../assets/sidebar.php'; ?>

    <div class="content">
        <div class="loader-container" id="loaderContainer">
            <div class="loader"></div>
        </div>

        <?php include '../assets/topbar.php'; ?>

        <div class="breadcrumb-area custom-gradient">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Biometric Device Configuration</li>
                </ol>
            </nav>
        </div>

        <div class="container-fluid">
            <!-- Mess Token Fingerprint Machine Section -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card filter-card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="mb-0"><i class="fa-solid fa-utensils me-2 text-warning"></i>Mess Token Fingerprint Machine</h5>
                            </div>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#addMachineModal" onclick="setMachineType('mess')">
                                <i class="fa-solid fa-plus me-1"></i> Add Machine
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle machines-table" id="messTable" style="width:100%">
                                    <thead class="gradient-header">
                                        <tr>
                                            <th>S.No</th>
                                            <th>Machine Name</th>
                                            <th>Hostel</th>
                                            <th>Token</th>
                                            <th>IP Address</th>
                                            <th>Device ID</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="fa-solid fa-inbox me-2"></i> No machines configured yet. Click 'Add Machine' to get started.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gate IN Section -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card filter-card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="mb-0"><i class="fa-solid fa-arrow-right-to-bracket me-2 text-success"></i>Gate IN Fingerprint Machine</h5>
                            </div>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addMachineModal" onclick="setMachineType('gate_in')">
                                <i class="fa-solid fa-plus me-1"></i> Add Machine
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle machines-table" id="gateInTable" style="width:100%">
                                    <thead class="gradient-header">
                                        <tr>
                                            <th>S.No</th>
                                            <th>Machine Name</th>
                                            <th>Hostel</th>
                                            <th>IP Address</th>
                                            <th>Device ID</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fa-solid fa-inbox me-2"></i> No machines configured yet. Click 'Add Machine' to get started.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gate OUT Section -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card filter-card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="mb-0"><i class="fa-solid fa-arrow-right-from-bracket me-2 text-danger"></i>Gate OUT Fingerprint Machine</h5>
                            </div>
                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addMachineModal" onclick="setMachineType('gate_out')">
                                <i class="fa-solid fa-plus me-1"></i> Add Machine
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle machines-table" id="gateOutTable" style="width:100%">
                                    <thead class="gradient-header">
                                        <tr>
                                            <th>S.No</th>
                                            <th>Machine Name</th>
                                            <th>Hostel</th>
                                            <th>IP Address</th>
                                            <th>Device ID</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fa-solid fa-inbox me-2"></i> No machines configured yet. Click 'Add Machine' to get started.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hostel Attendance Section -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card filter-card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="mb-0"><i class="fa-solid fa-fingerprint me-2 text-primary"></i>Hostel Attendance Fingerprint Machine</h5>
                            </div>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMachineModal" onclick="setMachineType('attendance')">
                                <i class="fa-solid fa-plus me-1"></i> Add Machine
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle machines-table" id="attendanceTable" style="width:100%">
                                    <thead class="gradient-header">
                                        <tr>
                                            <th>S.No</th>
                                            <th>Machine Name</th>
                                            <th>Hostel</th>
                                            <th>IP Address</th>
                                            <th>Device ID</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fa-solid fa-inbox me-2"></i> No machines configured yet. Click 'Add Machine' to get started.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Machine Modal -->
        <div class="modal fade" id="addMachineModal" tabindex="-1" aria-labelledby="addMachineModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title" id="addMachineModalLabel">
                            <i class="fa-solid fa-microchip me-2"></i> Add Biometric Machine
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="machineForm">
                        <div class="modal-body">
                            <input type="hidden" id="machineType" name="machine_type" value="">
                            <input type="hidden" id="machineId" name="machine_id" value="">

                            <div class="mb-3">
                                <label for="machineName" class="form-label text-muted fw-semibold">Machine Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="machineName" name="machine_name" placeholder="Machine Name" required>
                                <small class="text-muted">Provide a descriptive name for identification</small>
                            </div>

                            <div class="mb-3" id="locationField" style="display: none;">
                                <label for="machineLocation" class="form-label text-muted fw-semibold">Hostel <span class="text-danger">*</span></label>
                                <select class="form-select" id="machineLocation" name="machine_location">
                                    <option value="">Select Hostel</option>
                                </select>
                                <small class="text-muted">Select the hostel for this machine</small>
                            </div>

                            <div class="mb-3" id="tokenField" style="display: none;">
                                <label for="machineToken" class="form-label text-muted fw-semibold">Token <span class="text-danger">*</span></label>
                                <select class="form-select" id="machineToken" name="menu_id">
                                    <option value="">Select Token</option>
                                </select>
                                <small class="text-muted">Select the special token for this machine</small>
                            </div>

                            <div class="mb-3">
                                <label for="machineIp" class="form-label text-muted fw-semibold">IP Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="machineIp" name="machine_ip" placeholder="192.168.1.100" required>
                                <small class="text-muted">Enter the machine's network IP address</small>
                            </div>

                            <div class="mb-3">
                                <label for="deviceId" class="form-label text-muted fw-semibold">Device ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="deviceId" name="device_id" placeholder="DID001" required>
                                <small class="text-muted">Unique device identifier</small>
                            </div>

                        </div>
                        <div class="modal-footer border-top">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save Machine
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php include '../assets/footer.php'; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <script>
        (function () {
            let currentMachineType = null;
            const API_URL = '../api.php';

            window.hideLoader = function () {
                const container = document.getElementById('loaderContainer');
                const contentWrapper = document.getElementById('contentWrapper');

                if (container) {
                    try {
                        container.classList.add('hide');
                    } catch (e) {
                        console.warn('Error hiding loader:', e);
                    }
                }

                if (contentWrapper) {
                    try {
                        contentWrapper.classList.add('show');
                    } catch (e) {
                        // contentWrapper might not exist on this page, that's okay
                    }
                }
            };

            // Fetch hostels and populate dropdown
            const fetchHostels = async (selectedValue = '') => {
                const select = document.getElementById('machineLocation');
                if (!select) return;
                
                try {
                    const resp = await fetch(`${API_URL}?action=list_hostels`, { method: 'GET' });
                    const json = await resp.json();
                    
                    if (json.success && Array.isArray(json.data)) {
                        select.innerHTML = '<option value="">Select Hostel</option>';
                        json.data.forEach(hostel => {
                            const option = document.createElement('option');
                            option.value = hostel.hostel_name;
                            option.textContent = hostel.hostel_name;
                            if (hostel.hostel_name === selectedValue) {
                                option.selected = true;
                            }
                            select.appendChild(option);
                        });
                    }
                } catch (e) {
                    console.error('Failed to fetch hostels:', e);
                }
            };

            // Fetch special tokens and populate dropdown
            const fetchSpecialTokens = async (selectedValue = '') => {
                const select = document.getElementById('machineToken');
                if (!select) return;
                
                try {
                    const resp = await fetch(`${API_URL}?action=list_special_tokens`, { method: 'GET' });
                    const json = await resp.json();
                    
                    if (json.success && Array.isArray(json.data)) {
                        select.innerHTML = '<option value="">Select Token</option>';
                        json.data.forEach(token => {
                            const option = document.createElement('option');
                            option.value = token.menu_id;
                            option.textContent = `${token.token_date} - ${token.meal_type} (${token.menu_items})`;
                            if (String(token.menu_id) === String(selectedValue)) {
                                option.selected = true;
                            }
                            select.appendChild(option);
                        });
                    }
                } catch (e) {
                    console.error('Failed to fetch special tokens:', e);
                }
            };

            window.setMachineType = function (type) {
                currentMachineType = type;
                document.getElementById('machineType').value = type;
                document.getElementById('machineId').value = '';
                
                // Update modal title based on machine type
                const titleElement = document.getElementById('addMachineModalLabel');
                const typeLabels = {
                    'mess': 'Mess Token Fingerprint Machine',
                    'gate_in': 'Gate IN Fingerprint Machine',
                    'gate_out': 'Gate OUT Fingerprint Machine',
                    'attendance': 'Hostel Attendance Fingerprint Machine'
                };
                
                titleElement.innerHTML = `<i class="fa-solid fa-microchip me-2"></i> Add ${typeLabels[type] || 'Biometric Machine'}`;
                
                // Show location field for all machines (required)
                const locationField = document.getElementById('locationField');
                locationField.style.display = 'block';
                document.getElementById('machineLocation').required = true;
                
                // Show/hide token field based on machine type (only for mess)
                const tokenField = document.getElementById('tokenField');
                const tokenSelect = document.getElementById('machineToken');
                if (type === 'mess') {
                    tokenField.style.display = 'block';
                    tokenSelect.required = true;
                    fetchSpecialTokens();
                } else {
                    tokenField.style.display = 'none';
                    tokenSelect.required = false;
                    tokenSelect.value = '';
                }
                
                // Reset form
                document.getElementById('machineForm').reset();
                
                // Load hostels dropdown
                fetchHostels();
            };

            window.editMachine = function (machineId, machineName, machineIp, deviceId, machineLocation = '', menuId = '', machineType = '') {
                // Determine machine type from the clicked button's context
                currentMachineType = machineType || null;
                document.getElementById('machineType').value = machineType || '';
                document.getElementById('machineId').value = machineId;
                
                // Populate form fields
                document.getElementById('machineName').value = machineName;
                document.getElementById('machineIp').value = machineIp;
                document.getElementById('deviceId').value = deviceId;
                
                // Show location field and load hostels with selected value
                const locationField = document.getElementById('locationField');
                locationField.style.display = 'block';
                document.getElementById('machineLocation').required = true;
                fetchHostels(machineLocation);
                
                // Show/hide token field based on machine type
                const tokenField = document.getElementById('tokenField');
                const tokenSelect = document.getElementById('machineToken');
                if (machineType === 'mess') {
                    tokenField.style.display = 'block';
                    tokenSelect.required = true;
                    fetchSpecialTokens(menuId);
                } else {
                    tokenField.style.display = 'none';
                    tokenSelect.required = false;
                    tokenSelect.value = '';
                }
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('addMachineModal'));
                modal.show();
            };

            window.deleteMachine = function (machineId, machineName, machineType) {
                Swal.fire({
                    title: 'Delete Machine?',
                    text: `Are you sure you want to delete "${machineName}"? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Send delete request to API
                        fetch(API_URL, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'delete_machine',
                                machine_id: machineId,
                                machine_type: machineType
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire(
                                    'Deleted!',
                                    'Machine has been deleted successfully.',
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.message || 'Failed to delete machine', 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error', 'Failed to delete machine: ' + error.message, 'error');
                        });
                    }
                });
            };

            document.addEventListener('DOMContentLoaded', function () {
                const machineForm = document.getElementById('machineForm');

                const escapeHtml = (str) => {
                    return String(str ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                };

                const renderStatusBadge = (online) => {
                    const cls = online ? 'status-present' : 'status-blocked';
                    const label = online ? 'Online' : 'Offline';
                    return `<span class="status-chip ${cls}">${label}</span>`;
                };

                const checkStatus = async (ip) => {
                    try {
                        const url = `${API_URL}?action=check_status&machine_ip=${encodeURIComponent(ip)}`;
                        const resp = await fetch(url, { method: 'GET' });
                        const json = await resp.json();
                        return !!json.online;
                    } catch (e) {
                        return false;
                    }
                };

                const populateTable = async (machineType, tableId, hasLocation, locationHeaderLabel) => {
                    const table = document.getElementById(tableId);
                    if (!table) return;
                    const tbody = table.querySelector('tbody');
                    if (!tbody) return;
                    
                    // Mess table has an extra Token column (8 cols), others have 7
                    const isMessTable = machineType === 'mess';
                    const colSpan = isMessTable ? 8 : 7;

                    try {
                        const url = `${API_URL}?action=list_machines&machine_type=${encodeURIComponent(machineType)}`;
                        const resp = await fetch(url, { method: 'GET' });
                        const json = await resp.json();

                        if (!json || !json.success) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="${colSpan}" class="text-center text-danger py-4">
                                        <i class="fa-solid fa-triangle-exclamation me-2"></i> ${escapeHtml(json?.message || 'Unable to load machines')}
                                    </td>
                                </tr>`;
                            return;
                        }

                        const rows = Array.isArray(json.data) ? json.data : [];
                        if (rows.length === 0) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="${colSpan}" class="text-center text-muted py-4">
                                        <i class="fa-solid fa-inbox me-2"></i> No machines configured yet. Click 'Add Machine' to get started.
                                    </td>
                                </tr>`;
                            return;
                        }

                        // Fetch token info for mess machines
                        let tokenMap = {};
                        if (isMessTable) {
                            try {
                                const tokenResp = await fetch(`${API_URL}?action=list_special_tokens`, { method: 'GET' });
                                const tokenJson = await tokenResp.json();
                                if (tokenJson.success && Array.isArray(tokenJson.data)) {
                                    tokenJson.data.forEach(token => {
                                        tokenMap[token.menu_id] = `${token.token_date} - ${token.meal_type} (${token.menu_items})`;
                                    });
                                }
                            } catch (e) {
                                console.error('Failed to fetch tokens for mapping:', e);
                            }
                        }

                        // Render immediately with loading status, then async status update.
                        tbody.innerHTML = rows.map((m, idx) => {
                            const id = Number(m.id);
                            const name = escapeHtml(m.machine_name);
                            const ip = escapeHtml(m.machine_ip);
                            const did = escapeHtml(m.device_id);
                            const loc = escapeHtml(m.machine_location || '');
                            const menuId = m.menu_id || '';
                            const tokenLabel = menuId && tokenMap[menuId] ? escapeHtml(tokenMap[menuId]) : '-';

                            const editArgs = isMessTable
                                ? `${id}, '${name.replace(/'/g, "\\'")}', '${ip.replace(/'/g, "\\'")}', '${did.replace(/'/g, "\\'")}', '${loc.replace(/'/g, "\\'")}', '${menuId}', '${machineType}'`
                                : `${id}, '${name.replace(/'/g, "\\'")}', '${ip.replace(/'/g, "\\'")}', '${did.replace(/'/g, "\\'")}', '${loc.replace(/'/g, "\\'")}', '', '${machineType}'`;

                            const locationTd = hasLocation ? `<td>${loc || '-'}</td>` : '';
                            const tokenTd = isMessTable ? `<td>${tokenLabel}</td>` : '';

                            return `
                                <tr data-id="${id}" data-ip="${ip}">
                                    <td>${idx + 1}</td>
                                    <td>${name}</td>
                                    ${locationTd}
                                    ${tokenTd}
                                    <td>${ip}</td>
                                    <td>${did}</td>
                                    <td class="status-cell">${renderStatusBadge(false)}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editMachine(${editArgs});">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteMachine(${id}, '${name.replace(/'/g, "\\'")}', '${machineType}')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>`;
                        }).join('');

                        // Update status badges asynchronously.
                        const trList = Array.from(tbody.querySelectorAll('tr[data-ip]'));
                        trList.forEach(async (tr) => {
                            const ip = tr.getAttribute('data-ip');
                            const cell = tr.querySelector('.status-cell');
                            if (!ip || !cell) return;
                            const online = await checkStatus(ip);
                            cell.innerHTML = renderStatusBadge(online);
                        });
                    } catch (e) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="${colSpan}" class="text-center text-danger py-4">
                                    <i class="fa-solid fa-triangle-exclamation me-2"></i> Failed to load machines
                                </td>
                            </tr>`;
                    }
                };
                
                machineForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    
                    const machineType = document.getElementById('machineType').value;
                    const machineId = document.getElementById('machineId').value;
                    const machineName = document.getElementById('machineName').value;
                    const machineIp = document.getElementById('machineIp').value;
                    const deviceId = document.getElementById('deviceId').value;
                    const machineLocation = document.getElementById('machineLocation').value;
                    const menuId = document.getElementById('machineToken').value;

                    if (!machineType) {
                        Swal.fire('Error', 'Machine type not selected', 'error');
                        return;
                    }

                    // Validate IP format
                    const ipRegex = /^(\d{1,3}\.){3}\d{1,3}$/;
                    if (!ipRegex.test(machineIp)) {
                        Swal.fire('Error', 'Please enter a valid IP address', 'error');
                        return;
                    }

                    // Show loader
                    document.getElementById('loaderContainer')?.classList.remove('hide');

                    const formData = {
                        action: machineId ? 'update_machine' : 'add_machine',
                        machine_type: machineType,
                        machine_id: machineId || null,
                        machine_name: machineName,
                        machine_ip: machineIp,
                        device_id: deviceId,
                        machine_location: machineLocation,
                        menu_id: machineType === 'mess' ? menuId : null
                    };

                    fetch(API_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(formData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('loaderContainer')?.classList.add('hide');
                        
                        if (data.success) {
                            Swal.fire(
                                'Success',
                                machineId ? 'Machine updated successfully' : 'Machine added successfully',
                                'success'
                            ).then(() => {
                                // Close modal and reload
                                bootstrap.Modal.getInstance(document.getElementById('addMachineModal'))?.hide();
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message || 'Operation failed', 'error');
                        }
                    })
                    .catch(error => {
                        document.getElementById('loaderContainer')?.classList.add('hide');
                        Swal.fire('Error', 'Failed to save machine: ' + error.message, 'error');
                    });
                });

                // Hide loader on page load
                setTimeout(function () {
                    window.hideLoader();
                    // Load tables
                    populateTable('mess', 'messTable', true);
                    populateTable('gate_in', 'gateInTable', true);
                    populateTable('gate_out', 'gateOutTable', true);
                    populateTable('attendance', 'attendanceTable', true);
                }, 100);
            });
        })();
    </script>
</body>

</html>