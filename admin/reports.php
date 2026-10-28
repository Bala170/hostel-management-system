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

$scopeGender = get_hostel_gender_scope_for_role();

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
    "SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department <> ''" . ($scopeGender !== null ? " AND gender = '" . $conn->real_escape_string($scopeGender) . "'" : "") . " ORDER BY department ASC",
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
                    <li class="breadcrumb-item active" aria-current="page">Report Generation</li>
                </ol>
            </nav>
        </div>

        <div class="container-fluid">
            <div class="card filter-card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0"><i class="fa-solid fa-filter me-2 text-primary"></i>Filter Options</h5>
                        <small class="text-muted">Tune the dataset to generate focused hostel reports</small>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-primary btn-sm" id="applyFilters">
                            <i class="fa-solid fa-arrows-rotate me-1"></i> Apply Filters
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" id="resetFilters">
                            <i class="fa-solid fa-rotate-left me-1"></i> Reset
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form id="reportFilters">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label text-muted fw-semibold">Department</label>
                                <select class="form-select filter-trigger" id="departmentFilter">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $department): ?>
                                        <?php $shortDepartment = shorten_department_name($department); ?>
                                        <option value="<?php echo htmlspecialchars($department); ?>">
                                            <?php echo htmlspecialchars($shortDepartment); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fw-semibold">Hostel</label>
                                <select class="form-select filter-trigger" id="hostelFilter">
                                    <option value="">All Hostels</option>
                                    <?php if ($_SESSION['role'] === 'female_admin') { ?>
                                    <option value="Muthulakshmi">Muthulakshmi</option>
                                    <?php } elseif ($_SESSION['role'] === 'male_admin') { ?>
                                    <option value="Octa">Octa</option>
                                    <option value="Veda">Veda</option>
                                    <?php } else { ?>
                                    <option value="Muthulakshmi">Muthulakshmi</option>
                                    <option value="Octa">Octa</option>
                                    <option value="Veda">Veda</option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fw-semibold">Block</label>
                                <select class="form-select filter-trigger" id="blockFilter">
                                    <option value="">All Blocks</option>
                                    <option value="North">North</option>
                                    <option value="South">South</option>
                                    <option value="East">East</option>
                                    <option value="West">West</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fw-semibold">Floor</label>
                                <select class="form-select filter-trigger" id="floorFilter">
                                    <option value="">All Floors</option>
                                    <option value="I">I</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                    <option value="IV">IV</option>
                                    <option value="V">V</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fw-semibold">Room Type</label>
                                <select class="form-select filter-trigger" id="roomTypeFilter">
                                    <option value="">All Room Types</option>
                                    <option value="Non-AC">Non-AC</option>
                                    <option value="AC">AC</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fw-semibold">Academic Batch</label>
                                <select class="form-select filter-trigger" id="batchFilter">
                                    <option value="">All Batches</option>
                                    <?php foreach ($academicBatches as $batch): ?>
                                        <option value="<?php echo htmlspecialchars($batch); ?>">
                                            <?php echo htmlspecialchars($batch); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fw-semibold">Attendance Date</label>
                                <input type="date" class="form-control filter-trigger" id="reportDate"
                                    value="<?php echo $defaultDate; ?>">
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-12">
                                <label class="form-label text-muted fw-semibold">Attendance Status</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($attendanceFilters as $key => $label): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input filter-trigger" type="checkbox"
                                                id="status-<?php echo $key; ?>" name="attendance_status[]"
                                                value="<?php echo $key; ?>" checked>
                                            <label class="form-check-label" for="status-<?php echo $key; ?>">
                                                <?php echo htmlspecialchars($label); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label text-muted fw-semibold">Residency Filter</label>
                                <select class="form-select filter-trigger" id="vacatedFilter">
                                    <option value="active">Active</option>
                                    <option value="vacated">Vacated Students</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fw-semibold">Type of Stay</label>
                                <select class="form-select filter-trigger" id="typeOfStayFilter">
                                    <option value="all">All</option>
                                    <option value="temporary">Temporary</option>
                                    <option value="permanent">Permanent</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fw-semibold">Month Filter</label>
                                <select class="form-select filter-trigger" id="monthFilter">
                                    <?php foreach ($monthOptions as $value => $label): ?>
                                        <option value="<?php echo $value; ?>">
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fw-semibold">Hostel Newly Joined Students</label>
                                <select class="form-select filter-trigger" id="newlyJoinedMonth">
                                    <?php foreach ($monthOptions as $value => $label): ?>
                                        <option value="<?php echo $value; ?>">
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="alert alert-info w-100 mb-0 py-2">
                                    <small class="d-flex align-items-center mb-0">
                                        <i class="fa-solid fa-circle-info me-2"></i>
                                        Fine-tune filters and click apply to refresh the report instantly.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h5 class="mb-0"><i class="fa-solid fa-table-list me-2 text-success"></i>Student Hostel Activity
                            Report</h5>
                        <small class="text-muted">Search, filter, and export comprehensive hostel insights</small>
                    </div>
                    <div class="d-flex gap-2 flex-wrap" id="appliedFilters"></div>
                </div>
                <div class="card-body">
                    <!-- Export buttons -->
                    <div class="d-flex justify-content-end mb-3">
                        <a href="./report_api.php?action=report_pdf" target="_blank" style="margin-right: 5px;"
                            class="btn btn-danger btn-sm" id="exportPdf">
                            <i class="fa-solid fa-file-pdf"></i> Export PDF
                        </a>
                        <a href="./report_api.php?action=report_excel" class="btn btn-success btn-sm" id="exportExcel">
                            <i class="fa-solid fa-file-excel"></i> Export Excel
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle" id="reportTable" style="width:100%">
                            <thead class="gradient-header">
                                <tr>
                                    <th>Roll Number</th>
                                    <th>Student Name</th>
                                    <th>Department</th>
                                    <th>Batch</th>
                                    <th>Hostel</th>
                                    <th>Block</th>
                                    <th>Floor</th>
                                    <th>Room</th>
                                    <th>Status</th>
                                    <th>Attendance Time</th>
                                    <th>Date of Join</th>
                                    <th>Residency</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
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
        // Use IIFE to avoid conflicts with sidebar.php loaderContainer
        (function () {
            let reportLoaderContainer = null;

            // Override sidebar's hideLoader to prevent errors - must be done before DOMContentLoaded
            window.hideLoader = function () {
                // Safe version that checks for element existence
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

            window.showReportLoader = function () {
                if (!reportLoaderContainer) {
                    reportLoaderContainer = document.getElementById('loaderContainer');
                }
                if (reportLoaderContainer) {
                    try {
                        reportLoaderContainer.classList.remove('hide');
                    } catch (e) {
                        console.warn('Error showing loader:', e);
                    }
                }
            };

            window.hideReportLoader = function () {
                if (!reportLoaderContainer) {
                    reportLoaderContainer = document.getElementById('loaderContainer');
                }
                if (reportLoaderContainer) {
                    try {
                        reportLoaderContainer.classList.add('hide');
                    } catch (e) {
                        console.warn('Error hiding loader:', e);
                    }
                }
            };
        })();

        document.addEventListener('DOMContentLoaded', function () {
            const appliedFiltersContainer = document.getElementById('appliedFilters');
            const defaultReportDate = '<?php echo $defaultDate; ?>';
            const filterForm = document.getElementById('reportFilters');

            const statusStyles = {
                present: 'status-present',
                absent: 'status-absent',
                onduty: 'status-onduty',
                onleave: 'status-onleave',
                lateentry: 'status-lateentry',
                blocked: 'status-blocked'
            };

            const filterChips = () => {
                const chips = [];
                const department = $('#departmentFilter').val();
                const hostel = $('#hostelFilter').val();
                const block = $('#blockFilter').val();
                const floor = $('#floorFilter').val();
                const roomType = $('#roomTypeFilter').val();
                const batch = $('#batchFilter').val();
                const month = $('#newlyJoinedMonth option:selected').text();
                const residency = $('#vacatedFilter option:selected').text();
                const typeOfStay = $('#typeOfStayFilter option:selected').text();
                const selectedMonth = $('#monthFilter option:selected').text();

                if (department) chips.push(`Dept: ${department}`);
                if (hostel) chips.push(`Hostel: ${hostel}`);
                if (block) chips.push(`Block: ${block}`);
                if (floor) chips.push(`Floor: ${floor}`);
                if (roomType) chips.push(`Room: ${roomType}`);
                if (batch) chips.push(`Batch: ${batch}`);
                if ($('#newlyJoinedMonth').val() !== 'all') chips.push(`Joined: ${month}`);
                if ($('#monthFilter').val() !== 'all') chips.push(`Month: ${selectedMonth}`);
                chips.push(residency);
                if ($('#typeOfStayFilter').val() !== 'all') chips.push(typeOfStay);
                return chips;
            };

            const attendanceFilters = () => {
                const filters = [];
                document.querySelectorAll('input[name="attendance_status[]"]:checked').forEach((checkbox) => {
                    filters.push(checkbox.value);
                });
                return filters;
            };

            const collectFilters = () => {
                const filters = {
                    department: $('#departmentFilter').val() || '',
                    hostel: $('#hostelFilter').val() || '',
                    block: $('#blockFilter').val() || '',
                    floor: $('#floorFilter').val() || '',
                    room_type: $('#roomTypeFilter').val() || '',
                    academic_batch: $('#batchFilter').val() || '',
                    report_date: $('#reportDate').val() || defaultReportDate,
                    attendance_filters: attendanceFilters().join(','),
                    vacated_only: $('#vacatedFilter').val() === 'vacated' ? 1 : 0,
                    type_of_stay: $('#typeOfStayFilter').val() || 'all',
                    newly_joined_month: $('#newlyJoinedMonth').val() || 'all',
                    month_filter: $('#monthFilter').val() || 'all'
                };
                console.log('Filters being sent to API:', filters);
                return filters;
            };

            // Update export links with current filters
            const updateExportLinks = () => {
                const filters = collectFilters();
                const params = new URLSearchParams(filters);

                document.getElementById('exportPdf').href = `./report_api.php?action=report_pdf&${params.toString()}`;
                document.getElementById('exportExcel').href = `./report_api.php?action=report_excel&${params.toString()}`;
            };

            const renderFilterChips = () => {
                appliedFiltersContainer.innerHTML = '';
                filterChips().forEach((chip) => {
                    const span = document.createElement('span');
                    span.className = 'filter-chip';
                    span.innerHTML = `<i class="fa-solid fa-circle text-primary" style="font-size:8px;"></i> ${chip}`;
                    appliedFiltersContainer.appendChild(span);
                });
            };

            const reportTable = $('#reportTable').DataTable({
                processing: true,
                responsive: true,
                dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-sm-12 col-md-5"l><"col-sm-12 col-md-7"p>>',
                buttons: [],
                ajax: {
                    url: 'report_api.php?action=report_data',
                    type: 'GET',
                    data: function () {
                        return collectFilters();
                    },
                    dataSrc: function (resp) {
                        console.log('Report API Response:', resp);
                        if (resp && resp.success) {
                            if (resp.data && resp.data.length === 0) {
                                // Show info message if no data found (but not an error)
                                console.log('No data found for current filters', resp.debug || '');
                                console.log('Debug info:', JSON.stringify(resp.debug, null, 2));
                            } else {
                                console.log('Data fetched successfully:', resp.count, 'records');
                            }
                            return resp.data || [];
                        }
                        const errorMsg = resp?.message || 'Unable to fetch report';
                        console.error('Report API Error:', errorMsg, resp);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error Loading Report',
                            text: errorMsg,
                            footer: resp?.debug ? 'Check console for debug info' : ''
                        });
                        return [];
                    },
                    error: function (xhr, error, thrown) {
                        console.error('AJAX Error:', error, thrown);
                        if (typeof window.hideReportLoader === 'function') {
                            window.hideReportLoader();
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Connection Error',
                            text: 'Failed to connect to server. Please check your connection and try again.',
                            footer: 'Error: ' + (thrown || error)
                        });
                    }
                },
                columns: [{
                    data: 'roll_number'
                },
                {
                    data: 'student_name'
                },
                {
                    data: 'department'
                },
                {
                    data: 'academic_batch'
                },
                {
                    data: 'hostel_name'
                },
                {
                    data: 'block'
                },
                {
                    data: 'floor'
                },
                {
                    data: 'room_number'
                },
                {
                    data: 'status_label',
                    render: function (data, type, row) {
                        const badgeClass = statusStyles[row.status_key] || 'status-present';
                        return `<span class="status-chip ${badgeClass}">${data}</span>`;
                    }
                },
                {
                    data: 'attendance_time'
                },
                {
                    data: 'date_of_join'
                },
                {
                    data: 'residency_status'
                }
                ],
                order: [
                    [4, 'asc'],
                    [5, 'asc']
                ]
            });

            $('#reportTable').on('preXhr.dt', window.showReportLoader);
            $('#reportTable').on('xhr.dt', function () {
                window.hideReportLoader();
                renderFilterChips();
                updateExportLinks(); // Update export links when data is loaded
            });

            $('#applyFilters').on('click', function (e) {
                e.preventDefault();
                window.showReportLoader();
                reportTable.ajax.reload(null, false, function (json) {
                    window.hideReportLoader();
                    if (json && json.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Filters Applied',
                            text: `Found ${json.count || 0} records`,
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                });
            });

            $('#resetFilters').on('click', function (e) {
                e.preventDefault();
                window.showReportLoader();
                filterForm.reset();
                $('#departmentFilter, #hostelFilter, #blockFilter, #floorFilter, #roomTypeFilter, #batchFilter').val('');
                document.querySelectorAll('input[name="attendance_status[]"]').forEach(cb => cb.checked = true);
                $('#reportDate').val(defaultReportDate);
                $('#vacatedFilter').val('active');
                $('#typeOfStayFilter').val('all');
                $('#newlyJoinedMonth').val('all');
                $('#monthFilter').val('all');
                reportTable.ajax.reload(null, false, function (json) {
                    window.hideReportLoader();
                    if (json && json.success) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Filters Reset',
                            text: `Showing ${json.count || 0} records`,
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                });
            });

            renderFilterChips();
            updateExportLinks(); // Initialize export links
            // Initial load - hide loader after a short delay to ensure DOM is ready
            setTimeout(function () {
                window.hideReportLoader();
                reportTable.ajax.reload();
            }, 100);
        });
    </script>
</body>

</html>

