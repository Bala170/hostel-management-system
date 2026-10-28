<?php 
session_start();
include '../db.php';
include './admin_scope.php';

if (!is_any_admin_role()) {
    header('Location: ../login');
    exit;
}

$scopeGender = get_hostel_gender_scope_for_role();
date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Hostel Management</title>
    <link rel="icon" type="image/png" sizes="32x32" href="image/icons/mkce_s.png">
    <!-- CSS -->
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

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

        .student-list {
            max-height: 100px;
            overflow-y: auto;
            font-size: 0.9em;
            line-height: 1.4;
        }
        
        .text-muted {
            color: #6c757d !important;
        }

        .gradient-header {
            --bs-table-bg: transparent;
            --bs-table-color: white;
            background: linear-gradient(135deg, #4CAF50, #2196F3) !important;
            text-align: center;
            font-size: 0.9em;
        }

        td {
            text-align: left;
            font-size: 0.9em;
            vertical-align: middle;
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

            .footer {
                left: 0 !important;
            }
        }

        .container-fluid {
            padding: 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .dt-buttons {
            margin-bottom: 10px;
        }

        /* TAB COLORS WITH HOVER EFFECTS */
        .nav-tabs .nav-link {
            color: #333;
            border: 2px solid transparent;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-tabs .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
            z-index: 0;
        }

        .nav-tabs .nav-link:hover::before {
            left: 0;
        }

        .nav-tabs .nav-link span {
            position: relative;
            z-index: 1;
        }

        /* Purple Tab - Dr.Muthulakshmi */
        .nav-tabs .nav-link.tab-purple {
            color: #6a0dad !important;
        }
        
        .nav-tabs .nav-link.tab-purple:hover { 
            background: linear-gradient(135deg, #6a0dad, #8a2be2) !important;
            border-color: #6a0dad !important;
            color: #fff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(106, 13, 173, 0.3);
        }

        .nav-tabs .nav-link.tab-purple.active { 
            background: linear-gradient(135deg, #6a0dad, #8a2be2) !important;
            color: white !important;
            border-color: #6a0dad !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(106, 13, 173, 0.4);
        }
        
        /* Yellow Tab - Octa */
        .nav-tabs .nav-link.tab-yellow {
            color: #f6c34c !important;
        }
        
        .nav-tabs .nav-link.tab-yellow:hover { 
            background: linear-gradient(135deg, #f6c34c, #f1a200) !important;
            border-color: #f6c34c !important;
            color: #fff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(246, 195, 76, 0.3);
        }

        .nav-tabs .nav-link.tab-yellow.active { 
            background: linear-gradient(135deg, #f6c34c, #f1a200) !important;
            color: #fff !important;
            border-color: #f6c34c !important;
            font-weight: 600;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(246, 195, 76, 0.4);
        }
        
        /* Red Tab - Veda */
        .nav-tabs .nav-link.tab-red {
            color: #f44336 !important;
        }
        
        .nav-tabs .nav-link.tab-red:hover { 
            background: linear-gradient(135deg, #f44336, #d32f2f) !important;
            border-color: #f44336 !important;
            color: #fff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(244, 67, 54, 0.3);
        }

        .nav-tabs .nav-link.tab-red.active { 
            background: linear-gradient(135deg, #f44336, #d32f2f) !important;
            color: #fff !important;
            border-color: #f44336 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
        }

        /* Orange Tab - Vacated Students */
        .nav-tabs .nav-link.tab-orange {
            color: #4caf50 !important;
        }
        
        .nav-tabs .nav-link.tab-orange:hover { 
            background: linear-gradient(135deg, #4caf50, #388e3c) !important;
            border-color: #4caf50 !important;
            color: #fff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
        }

        .nav-tabs .nav-link.tab-orange.active { 
            background: linear-gradient(135deg, #4caf50, #388e3c) !important;
            color: #fff !important;
            border-color: #4caf50 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
        }
        
        .card .card-header { 
            display:flex; 
            align-items:center; 
            justify-content:space-between; 
            gap:10px;
            flex-wrap: wrap;
        }
        
        /* ACTION BUTTONS - FIXED ALIGNMENT */
        .action-btns {
            display: flex;
            gap: 4px;
            flex-wrap: nowrap;
            justify-content: center;
            min-width: 260px;
        }
        
        .action-btns .btn { 
            padding: 4px 8px;
            font-size: 0.85rem;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* Fixed width for actions column */
        table th:last-child,
        table td:last-child {
            min-width: 270px;
            text-align: center;
        }
        
        #loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #loader.hidden { 
            display: none; 
        }
        
        #loader .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-radius: 50%;
            border-top: 5px solid var(--primary-color);
            animation: spin 1s linear infinite;
        }

        /* Select2 Custom Styling */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
        }

        .select2-container--bootstrap-5 .select2-dropdown {
            z-index: 9999;
        }

        /* Block and Floor Filter */
        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        
        .filter-section h6 {
            margin-bottom: 15px;
            color: #495057;
            font-weight: 600;
        }
        
        .filter-section .form-group {
            margin-bottom: 10px;
        }
        
        .filter-section label {
            font-weight: 500;
            margin-bottom: 5px;
        }
    </style>

    <!-- JS libs -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
</head>
<body>

<div id="loader" class="hidden">
        <div class="spinner"></div>
    </div>

<?php include '../assets/sidebar.php'; ?>

<div class="content">
    <?php include '../assets/topbar.php'; ?>

    <div class="breadcrumb-area custom-gradient">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Room Details</li>
            </ol>
        </nav>
    </div>

    <div class="container-fluid">
        <div class="tab-content">
            <?php
            // Fetch hostels to make sure names exist
            $hostels_map = [];
            $hostelSql = "SELECT hostel_id, hostel_name, gender FROM hostels";
            if ($scopeGender !== null) {
                $hostelSql .= " WHERE gender = '" . $conn->real_escape_string($scopeGender) . "'";
            }
            $hres = $conn->query($hostelSql);
            if ($hres) {
                while ($h = $hres->fetch_assoc()) $hostels_map[$h['hostel_name']] = $h['hostel_id'];
            }
            // Debug: Log the hostels map
            error_log("Hostels map: " . print_r($hostels_map, true));
            
            $tabs = [
                ['key'=>'Muthulakshmi','id'=>'muthu','class'=>'tab-purple','filter'=>'gender=Female'],
                ['key'=>'Octa','id'=>'octa','class'=>'tab-yellow','filter'=>'gender=Male'],
                ['key'=>'Veda','id'=>'veda','class'=>'tab-red','filter'=>'gender=Male']
            ];
            $tabs = array_values(array_filter($tabs, function($t) use ($scopeGender) {
                if ($scopeGender === null) return true;
                if ($scopeGender === 'Female') return $t['key'] === 'Muthulakshmi';
                if ($scopeGender === 'Male') return in_array($t['key'], ['Octa', 'Veda'], true);
                return true;
            }));
            ?>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3" id="hostelTabs" role="tablist">
                <?php foreach ($tabs as $idx => $tb): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $idx === 0 ? 'active' : '' ?> <?= htmlspecialchars($tb['class']) ?>" id="tab-<?= htmlspecialchars($tb['id']) ?>" data-bs-toggle="tab" data-hostel="<?= htmlspecialchars($tb['key']) ?>" data-bs-target="#<?= htmlspecialchars($tb['id']) ?>" type="button" role="tab"><span><?= htmlspecialchars($tb['key']) ?> Hostel</span></button>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link tab-orange" id="tab-vacated" data-bs-toggle="tab" data-bs-target="#vacated" type="button" role="tab"><span>Vacated Students</span></button>
                </li>
            </ul>

            <?php
            foreach ($tabs as $t):
                $hostelName = $t['key'];
                $tabId = $t['id'];
                $tabClass = $t['class'];
                $hostelId = $hostels_map[$hostelName] ?? 0;
                // Debug: Log each hostel
                error_log("Hostel: $hostelName, ID: $hostelId");
            ?>
            <div class="tab-pane fade <?= $tabId === ($tabs[0]['id'] ?? '') ? 'show active' : '' ?>" id="<?= $tabId ?>" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header">
                        <div>
                            <h5 class="card-title mb-0"><?= htmlspecialchars($hostelName) ?> - Rooms</h5>
                            <small class="text-muted">Manage rooms for <?= htmlspecialchars($hostelName) ?></small>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
    <input type="text" class="form-control form-control-sm table-search" placeholder="Search in table..." data-target="<?= $tabId ?>" style="width:220px;">
    <?php if ($hostelId > 0): ?>
        <button class="btn btn-success btn-sm add-room-btn" data-hostel="<?= htmlspecialchars($hostelName) ?>" data-hostel-id="<?= $hostelId ?>"><i class="fa fa-plus"></i> Add Room</button>
        <a href="room_backend.php?download_pdf=1&hostel=<?= urlencode($hostelName) ?>" class="btn btn-danger btn-sm download-pdf-btn" target="_blank" data-hostel="<?= htmlspecialchars($hostelName) ?>">
            <i class="fa fa-file-pdf"></i> Download PDF
        </a>
        <a href="room_backend.php?download_xls=1&hostel=<?= urlencode($hostelName) ?>" class="btn btn-success btn-sm download-xls-btn" target="_blank" data-hostel="<?= htmlspecialchars($hostelName) ?>">
            <i class="fa fa-file-excel"></i> Download Excel
        </a>
    <?php else: ?>
        <span class="text-danger small fw-bold">⚠️ Cannot add room: Hostel ID is missing from database.</span>
    <?php endif; ?>
</div>
                    </div>
                    <div class="card-body">
                        <!-- Filter Section -->
                        <div class="filter-section">
                            <h6><i class="fas fa-filter"></i> Filter Rooms</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_department_<?= $tabId ?>">Department</label>
                                        <select id="filter_department_<?= $tabId ?>" class="form-select filter-department" data-tab="<?= $tabId ?>">
                                            <option value="">All Departments</option>
                                            <option value="Information Technology">Information Technology</option>
                                            <option value="Computer Science Engineering">Computer Science Engineering</option>
                                            <option value="Electronics & Communication Engineering">Electronics & Communication Engineering</option>
                                            <option value="Electrical Engineering">Electrical Engineering</option>
                                            <option value="Mechanical Engineering">Mechanical Engineering</option>
                                            <option value="Civil Engineering">Civil Engineering</option>
                                            <option value="CSBS">Computer Science And Business Systems</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_year_<?= $tabId ?>">Year</label>
                                        <select id="filter_year_<?= $tabId ?>" class="form-select filter-year" data-tab="<?= $tabId ?>">
                                            <option value="">All Years</option>
                                            <?php
                                            // Fetch academic batches from database
                                            $batchResult = $conn->query("SELECT DISTINCT academic_batch FROM students WHERE academic_batch IS NOT NULL AND academic_batch != '' ORDER BY academic_batch");
                                            if ($batchResult && $batchResult->num_rows > 0) {
                                                while ($batchRow = $batchResult->fetch_assoc()) {
                                                    echo "<option value=\"" . htmlspecialchars($batchRow['academic_batch']) . "\">" . htmlspecialchars($batchRow['academic_batch']) . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_block_<?= $tabId ?>">Block</label>
                                        <select id="filter_block_<?= $tabId ?>" class="form-select filter-block" data-tab="<?= $tabId ?>">
                                            <option value="">All Blocks</option>
                                            <option value="North">North</option>
                                            <option value="South">South</option>
                                            <option value="East">East</option>
                                            <option value="West">West</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_floor_<?= $tabId ?>">Floor</label>
                                        <select id="filter_floor_<?= $tabId ?>" class="form-select filter-floor" data-tab="<?= $tabId ?>">
                                            <option value="">All Floors</option>
                                            <option value="I">I</option>
                                            <option value="II">II</option>
                                            <option value="III">III</option>
                                            <option value="IV">IV</option>
                                            <option value="V">V</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_room_type_<?= $tabId ?>">Room Type</label>
                                        <select id="filter_room_type_<?= $tabId ?>" class="form-select filter-room-type" data-tab="<?= $tabId ?>">
                                            <option value="">All Types</option>
                                            <option value="AC">AC</option>
                                            <option value="Non-AC">Non-AC</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_user_<?= $tabId ?>">User Type</label>
                                        <select id="filter_user_<?= $tabId ?>" class="form-select filter-user" data-tab="<?= $tabId ?>">
                                            <option value="">All Users</option>
                                            <option value="student">Students Only</option>
                                            <option value="faculty">Faculty Only</option>
                                            <option value="both">Both Students & Faculty</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <button class="btn btn-primary btn-sm apply-filter" data-tab="<?= $tabId ?>"><i class="fas fa-filter"></i> Apply Filter</button>
                                        <button class="btn btn-secondary btn-sm clear-filter" data-tab="<?= $tabId ?>"><i class="fas fa-times"></i> Clear</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered hostel-table" id="table_<?= $tabId ?>">
                                <thead class="gradient-header">
                                    <tr>
                                        <th>S.No.</th>
                                        <th>Hostel Name</th>
                                        <th>Block</th>
                                        <th>Floor</th>
                                        <th>Room No</th>
                                        <th>Type</th>
                                        <th>Capacity</th>
                                        <th>Occupied Students</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                // load rows for this hostel using prepared statement
                                $escapedHostel = $hostelName;
                                // Modified query to use PIPE delimiter instead of comma
                                $q = "SELECT r.room_id, r.hostel_id, r.room_number, r.block, r.floor, r.room_type, r.capacity, r.occupied, r.status, h.hostel_name, h.gender as hostel_gender,
                                        GROUP_CONCAT(DISTINCT 
                                            CONCAT(s.name,' (',s.roll_number,')') 
                                            SEPARATOR '<br>'
                                        ) AS student_info,
                                        GROUP_CONCAT(DISTINCT 
                                            CONCAT(hf.f_name,' (Faculty - ', hf.faculty_id,')') 
                                            SEPARATOR '<br>'
                                        ) AS faculty_info,
                                        GROUP_CONCAT(DISTINCT s.department SEPARATOR '|') AS departments,
                                        GROUP_CONCAT(DISTINCT s.academic_batch SEPARATOR '|') AS academic_years,
                                        GROUP_CONCAT(DISTINCT s.name SEPARATOR '|') AS student_names,
                                        GROUP_CONCAT(DISTINCT hf.f_name SEPARATOR '|') AS faculty_names
                                      FROM rooms r
                                      LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                                      LEFT JOIN room_students rs ON r.room_id = rs.room_id AND rs.is_active = 1 AND (rs.vacated_at IS NULL OR rs.vacated_at='0000-00-00 00:00:00')
                                      LEFT JOIN students s ON rs.student_id = s.student_id
                                      LEFT JOIN hostel_faculty hf ON r.room_id = hf.room_id AND hf.status = 1
                                      WHERE h.hostel_name = ?
                                      GROUP BY r.room_id
                                      ORDER BY r.room_number ASC";
                                $stmt = $conn->prepare($q);
                                if ($stmt) {
                                    $stmt->bind_param('s', $escapedHostel);
                                    $stmt->execute();
                                    $res = $stmt->get_result();
                                    $idx = 1;
                                    while ($row = $res->fetch_assoc()) {
                                        // Show rooms that have occupants (students or faculty) or are not fully occupied
                                        if ($row['occupied'] >= $row['capacity'] && empty($row['student_info']) && empty($row['faculty_info'])) {
                                            continue;
                                        }
                                        
                                        // Build display for occupants (show only students or faculty, not both)
                                        $studentDisplay = '';
                                        if (!empty($row['student_info'])) {
                                            $studentDisplay = $row['student_info'];
                                        } elseif (!empty($row['faculty_info'])) {
                                            $studentDisplay = $row['faculty_info'];
                                        } else {
                                            $studentDisplay = '<small class="text-muted">No occupants</small>';
                                        }
                                        $actionBtns = '<div class="action-btns">';
                                        $actionBtns .= "<button class='btn btn-sm btn-success assignStudent' data-room-id='{$row['room_id']}' title='Assign'><i class='fa fa-user-plus'></i></button>";
                                        $actionBtns .= "<button class='btn btn-sm btn-warning transferBtn' data-room-id='{$row['room_id']}' title='Transfer Student'><i class='fa fa-exchange-alt'></i></button>";
                                        $actionBtns .= "<button class='btn btn-sm btn-info transferRoomBtn' data-room-id='{$row['room_id']}' title='Transfer Room'><i class='fa fa-users'></i></button>";
                                        $actionBtns .= "<button class='btn btn-sm btn-primary swapBtn' data-room-id='{$row['room_id']}' title='Swap'><i class='fa fa-sync-alt'></i></button>";
                                        $actionBtns .= "<button class='btn btn-sm btn-danger vacateBtn' data-room-id='{$row['room_id']}' title='Vacate'><i class='fa fa-sign-out-alt'></i></button>";
                                        $actionBtns .= "<button class='btn btn-sm btn-primary editRoom' data-room-id='{$row['room_id']}' title='Edit'><i class='fa fa-edit'></i></button>";
                                        $actionBtns .= "<button class='btn btn-sm btn-danger deleteRoom' data-room-id='{$row['room_id']}' title='Delete'><i class='fa fa-trash'></i></button>";
                                        $actionBtns .= '</div>';

                                        // Prepare department and year data for filtering
                                        $departments = $row['departments'] ? $row['departments'] : '';
                                        $academic_years = $row['academic_years'] ? $row['academic_years'] : '';
                                        
                                        // Fix the data attributes to ensure faculty names are properly set
                                        $studentNames = $row['student_names'] ?? '';
                                        $facultyNames = $row['faculty_names'] ?? '';
                                        
                                        // Ensure proper data attributes for filtering
                                        $block = isset($row['block']) ? htmlspecialchars($row['block']) : '';
                                        $floor = isset($row['floor']) ? htmlspecialchars($row['floor']) : '';
                                        $hostelId = isset($row['hostel_id']) ? htmlspecialchars($row['hostel_id']) : '';
                                        $capacity = isset($row['capacity']) ? htmlspecialchars($row['capacity']) : '';
                                        $occupied = isset($row['occupied']) ? htmlspecialchars($row['occupied']) : '';
                                        
                                        echo "<tr data-room-id='{$row['room_id']}' data-hostel-id='{$hostelId}' data-capacity='{$capacity}' data-occupied='{$occupied}' data-departments='".htmlspecialchars($departments)."' data-years='".htmlspecialchars($academic_years)."' data-block='{$block}' data-floor='{$floor}' data-student-names='".htmlspecialchars($studentNames)."' data-faculty-names='".htmlspecialchars($facultyNames)."'>";
                                        echo "<td class='sno text-center'>{$idx}</td>";
                                        echo "<td>".htmlspecialchars($row['hostel_name'])."</td>";
                                        echo "<td>".htmlspecialchars($row['block'] ?? '')."</td>";
                                        echo "<td>".htmlspecialchars($row['floor'] ?? '')."</td>";
                                        echo "<td>".htmlspecialchars($row['room_number'])."</td>";
                                        echo "<td>".htmlspecialchars($row['room_type'] ?? '')."</td>";
                                        echo "<td class='text-center'>".intval($row['capacity'])."</td>";
                                        echo "<td class='student-list'>{$studentDisplay}</td>";
                                        echo "<td class='text-center'>{$actionBtns}</td>";
                                        echo "</tr>";
                                        $idx++;
                                    }
                                    $stmt->close();
                                } else {
                                    echo "<tr class='error-row'><td colspan='9'>Error loading rooms: " . htmlspecialchars($conn->error) . "</td></tr>";
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Vacated Students Tab -->
            <div class="tab-pane fade" id="vacated" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header">
                        <div>
                            <h5 class="card-title mb-0">Vacated Students</h5>
                            <small class="text-muted">View all students who have been vacated from rooms</small>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <input type="text" class="form-control form-control-sm table-search" placeholder="Search in table..." data-target="vacated" style="width:220px;">
                            <a href="room_backend.php?download_vacated_pdf=1" class="btn btn-danger btn-sm download-pdf-btn" target="_blank">
                                <i class="fa fa-file-pdf"></i> Download PDF
                            </a>
                            <a href="room_backend.php?download_vacated_xls=1" class="btn btn-success btn-sm download-xls-btn" target="_blank">
                                <i class="fa fa-file-excel"></i> Download Excel
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filter Section -->
                        <div class="filter-section">
                            <h6><i class="fas fa-filter"></i> Filter Vacated Students</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_department_vacated">Department</label>
                                        <select id="filter_department_vacated" class="form-select filter-department" data-tab="vacated">
                                            <option value="">All Departments</option>
                                            <option value="Information Technology">Information Technology</option>
                                            <option value="Computer Science Engineering">Computer Science Engineering</option>
                                            <option value="Electronics & Communication Engineering">Electronics & Communication Engineering</option>
                                            <option value="Electrical Engineering">Electrical Engineering</option>
                                            <option value="Mechanical Engineering">Mechanical Engineering</option>
                                            <option value="Civil Engineering">Civil Engineering</option>
                                            <option value="CSBS">Computer Science And Business Systems</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_year_vacated">Year</label>
                                        <select id="filter_year_vacated" class="form-select filter-year" data-tab="vacated">
                                            <option value="">All Years</option>
                                            <?php
                                            // Fetch academic batches from database for vacated students
                                            $batchResult = $conn->query("SELECT DISTINCT academic_batch FROM students WHERE academic_batch IS NOT NULL AND academic_batch != '' ORDER BY academic_batch");
                                            if ($batchResult && $batchResult->num_rows > 0) {
                                                while ($batchRow = $batchResult->fetch_assoc()) {
                                                    echo "<option value=\"" . htmlspecialchars($batchRow['academic_batch']) . "\">" . htmlspecialchars($batchRow['academic_batch']) . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_hostel_vacated">Hostel</label>
                                        <select id="filter_hostel_vacated" class="form-select filter-hostel" data-tab="vacated">
                                            <option value="">All Hostels</option>
                                            <?php if ($scopeGender === null || $scopeGender === 'Female'): ?>
                                                <option value="Dr.Muthulakshmi">Dr.Muthulakshmi</option>
                                            <?php endif; ?>
                                            <?php if ($scopeGender === null || $scopeGender === 'Male'): ?>
                                                <option value="Octa">Octa</option>
                                                <option value="Veda">Veda</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_gender_vacated">Gender</label>
                                        <select id="filter_gender_vacated" class="form-select filter-gender" data-tab="vacated">
                                            <option value="">All Genders</option>
                                            <?php if ($scopeGender === null || $scopeGender === 'Male'): ?>
                                                <option value="Male">Male</option>
                                            <?php endif; ?>
                                            <?php if ($scopeGender === null || $scopeGender === 'Female'): ?>
                                                <option value="Female">Female</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_room_vacated">Room Number</label>
                                        <input type="text" id="filter_room_vacated" class="form-control filter-room" data-tab="vacated" placeholder="Enter room number">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_month_vacated">Month</label>
                                        <select id="filter_month_vacated" class="form-select filter-month" data-tab="vacated">
                                            <option value="">All Months</option>
                                            <option value="01">January</option>
                                            <option value="02">February</option>
                                            <option value="03">March</option>
                                            <option value="04">April</option>
                                            <option value="05">May</option>
                                            <option value="06">June</option>
                                            <option value="07">July</option>
                                            <option value="08">August</option>
                                            <option value="09">September</option>
                                            <option value="10">October</option>
                                            <option value="11">November</option>
                                            <option value="12">December</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <button class="btn btn-primary btn-sm apply-filter" data-tab="vacated"><i class="fas fa-filter"></i> Apply Filter</button>
                                        <button class="btn btn-secondary btn-sm clear-filter" data-tab="vacated"><i class="fas fa-times"></i> Clear</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="table_vacated">
                                <thead class="gradient-header">
                                    <tr>
                                        <th>S.No.</th>
                                        <th>Student Name</th>
                                        <th>Roll Number</th>
                                        <th>Department</th>
                                        <th>Previous Hostel</th>
                                        <th>Previous Room</th>
                                        <th>Vacated At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                // Fetch vacated students - only show students who don't have current active assignments
                                $vacatedQuery = "SELECT rs.*, s.name, s.roll_number, s.department, s.academic_batch, s.gender, h.hostel_name, r.room_number
                                                FROM room_students rs
                                                LEFT JOIN students s ON rs.student_id = s.student_id
                                                LEFT JOIN rooms r ON rs.room_id = r.room_id
                                                LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                                                WHERE rs.is_active = 0 AND rs.vacated_at IS NOT NULL AND rs.vacated_at != '0000-00-00 00:00:00'
                                                AND NOT EXISTS (SELECT 1 FROM room_students rs2 WHERE rs2.student_id = rs.student_id AND rs2.is_active = 1)
                                                AND rs.id = (
                                                    SELECT MAX(rs3.id) 
                                                    FROM room_students rs3 
                                                    WHERE rs3.student_id = rs.student_id 
                                                    AND rs3.is_active = 0 
                                                    AND rs3.vacated_at IS NOT NULL 
                                                    AND rs3.vacated_at != '0000-00-00 00:00:00'
                                                )
                                                ORDER BY rs.vacated_at DESC";
                                $vacatedResult = $conn->query($vacatedQuery);
                                $idx = 1;
                                if ($vacatedResult && $vacatedResult->num_rows > 0) {
                                    while ($vRow = $vacatedResult->fetch_assoc()) {
                                        echo "<tr data-departments='".htmlspecialchars($vRow['department'] ?? '')."' data-years='".htmlspecialchars($vRow['academic_batch'] ?? '')."' data-hostel='".htmlspecialchars($vRow['hostel_name'] ?? '')."' data-room='".htmlspecialchars($vRow['room_number'] ?? '')."' data-gender='".htmlspecialchars($vRow['gender'] ?? '')."'>";
                                        echo "<td class='text-center'>{$idx}</td>";
                                        echo "<td>".htmlspecialchars($vRow['name'] ?? 'N/A')."</td>";
                                        echo "<td>".htmlspecialchars($vRow['roll_number'] ?? 'N/A')."</td>";
                                        echo "<td>".htmlspecialchars($vRow['department'] ?? 'N/A')."</td>";
                                        echo "<td>".htmlspecialchars($vRow['hostel_name'] ?? 'N/A')."</td>";
                                        echo "<td>".htmlspecialchars($vRow['room_number'] ?? 'N/A')."</td>";
                                        echo "<td>".htmlspecialchars($vRow['vacated_at'])."</td>";
                                        echo "</tr>";
                                        $idx++;
                                    }
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Room Modal -->
<div class="modal fade" id="roomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="roomForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add / Edit Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="room_id" name="room_id" value="">
                <!-- Hidden hostel field - automatically set based on current tab -->
                <input type="hidden" id="modal_hostel" name="hostel_id" value="">
                <div class="mb-3">
                    <label class="form-label">Hostel</label>
                    <div id="hostel_name_display" class="form-control-plaintext fw-bold"></div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="block" class="form-label">Block</label>
                        <select id="block" name="block" class="form-select">
                            <option value="">-- Select Block --</option>
                            <option value="North">North</option>
                            <option value="South">South</option>
                            <option value="East">East</option>
                            <option value="West">West</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="floor" class="form-label">Floor</label>
                        <select id="floor" name="floor" class="form-select">
                            <option value="">-- Select Floor --</option>
                            <option value="I">I</option>
                            <option value="II">II</option>
                            <option value="III">III</option>
                            <option value="IV">IV</option>
                            <option value="V">V</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="room_number" class="form-label">Room Number</label>
                    <input id="room_number" name="room_number" type="text" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="room_type" class="form-label">Room Type</label>
                    <select id="room_type" name="room_type" class="form-select">
                        <option>Non-AC</option>
                        <option>AC</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col mb-3">
                        <label for="capacity" class="form-label">Capacity</label>
                        <input id="capacity" name="capacity" type="number" min="1" class="form-control" value="3">
                    </div>
                    <div class="col mb-3">
                        <label for="occupied" class="form-label">Occupied</label>
                        <input id="occupied" name="occupied" type="number" min="0" class="form-control" value="0" readonly>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option>Available</option>
                        <option>Under Construction</option>
                        <option>Not Available</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="saveRoomBtn" class="btn btn-success">Save Room</button>
            </div>
        </form>
    </div>
</div>

<!-- Vacate Modal -->
<div class="modal fade" id="vacateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vacate Students</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Select Option</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="vacateOption" id="vacateAll" value="all" checked>
                        <label class="form-check-label" for="vacateAll">
                            Vacate All Students
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="vacateOption" id="vacateSelect" value="select">
                        <label class="form-check-label" for="vacateSelect">
                            Select Specific Students
                        </label>
                    </div>
                </div>
                <div id="studentSelectContainer" style="display: none;">
                    <label class="form-label fw-bold">Select Students to Vacate</label>
                    <select id="vacateStudentSelect" class="form-select" multiple style="width:100%">
                        <!-- Options will be populated dynamically -->
                    </select>
                    <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple students</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmVacate" class="btn btn-danger">Vacate</button>
            </div>
        </div>
    </div>
</div>

<?php include '../assets/footer.php'; ?>

<script>
$(function(){
    // Hide loader when page is ready
    function hideLoader() {
        $('#loader').addClass('hidden');
    }
    
    function showLoader() {
        $('#loader').removeClass('hidden');
    }
    
    // Initialize DataTables for each hostel table
    var datatables = {};
    setTimeout(function() {
        $('.hostel-table').each(function(){
            var tableId = $(this).attr('id');
            
            try {
                var table = $(this).DataTable({
                    pageLength: 10,
                    lengthChange: false,
                    ordering: true,
                    searching: true,
                    responsive: true,
                    dom: 'Bfrtip',
                    buttons: [],
                    order: [[1, 'asc']],
                    columnDefs: [
                        { orderable: false, targets: [0, 8] }
                    ],
                    drawCallback: function() {
                        recalcSno(tableId);
                    }
                });
                // Exclude error rows from DataTables processing
                table.on('preDraw', function() {
                    $(table.table().body()).find('.error-row').detach();
                });
                datatables[tableId] = table;
            } catch (e) {
                console.error('Error initializing DataTable for ' + tableId + ':', e);
            }
        });
    }, 100);

    // Recalculate S.No for a table
    function recalcSno(tableId) {
        var table = $('#' + tableId).DataTable();
        var info = table.page.info();
        
        table.rows({page:'current'}).every(function(rowIdx, tableLoop, rowLoop){
            var node = this.node();
            $(node).find('td.sno').text(info.start + rowLoop + 1);
        });
    }

    // Search input
    $('.table-search').on('input', function(){
        var target = $(this).data('target');
        var tbl = $('#table_' + target).DataTable();
        tbl.search(this.value).draw();
    });

    // Apply filter - improved: normalize values, trim, and robust matching for dept/year
    $(document).on('click', '.apply-filter', function(){
        var tabId = $(this).data('tab');

        var department = $('#filter_department_' + tabId).val() || '';
        var year = $('#filter_year_' + tabId).val() || '';
        var block = $('#filter_block_' + tabId).val() || '';
        var floor = $('#filter_floor_' + tabId).val() || '';
        var roomType = $('#filter_room_type_' + tabId).val() || '';
        var userType = $('#filter_user_' + tabId).val() || '';

        var table = datatables['table_' + tabId];
        if (!table) return;

        table.search('').draw(); // Clear global search

        // Reset custom filters (we re-add a single function below)
        $.fn.dataTable.ext.search = [];

        // helpers
        function normalize(s){ return (s||'').toString().trim().toLowerCase(); }

        function matchList(attrString, val){
            if (!val) return true;
            if (!attrString) return false;
            var list = attrString.split('|').map(function(x){ return (x||'').toString().trim().toLowerCase(); });
            val = normalize(val);
            if (list.indexOf(val) !== -1) return true;
            // try mapping via departmentShortcuts if available (both directions)
            if (typeof departmentShortcuts !== 'undefined'){
                for (var k in departmentShortcuts){
                    if (!departmentShortcuts.hasOwnProperty(k)) continue;
                    var full = normalize(k);
                    var shortv = normalize(departmentShortcuts[k]);
                    if (full === val && list.indexOf(shortv) !== -1) return true;
                    if (shortv === val && list.indexOf(full) !== -1) return true;
                }
            }
            // fallback: substring match
            for (var i=0;i<list.length;i++){
                if (list[i].indexOf(val) !== -1) return true;
            }
            return false;
        }

        // Add the filtering function
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
            if (settings.nTable.id !== 'table_' + tabId) return true;

            // Use DataTables' API row() to get node
            var rowNode = table.row(dataIndex).node();

            // block (column 2)
            if (block && normalize(data[2]) !== normalize(block)) return false;
            // floor (column 3)
            if (floor && normalize(data[3]) !== normalize(floor)) return false;
            // room type (column 5)
            if (roomType && normalize(data[5]) !== normalize(roomType)) return false;

            // department - uses data-departments (pipe-delimited)
            var rowDepartments = $(rowNode).attr('data-departments') || '';
            if (department && !matchList(rowDepartments, department)) return false;

            // year - uses data-years (pipe-delimited)
            var rowYears = $(rowNode).attr('data-years') || '';
            if (year && !matchList(rowYears, year)) return false;

            // user type filtering
            if (userType) {
                var studentNames = $(rowNode).attr('data-student-names') || '';
                var facultyNames = $(rowNode).attr('data-faculty-names') || '';
                var hasStudents = studentNames.trim() !== '';
                var hasFaculty = facultyNames.trim() !== '';
                
                switch(userType) {
                    case 'student':
                        if (!hasStudents) return false;
                        break;
                    case 'faculty':
                        // Fixed faculty filtering - check if faculty names exist
                        if (!hasFaculty) return false;
                        break;
                    case 'both':
                        // Both students and faculty should be present
                        if (!hasStudents || !hasFaculty) return false;
                        break;
                }
            }

            return true;
        });

        table.draw();
    });

    // Clear filter
    $(document).on('click', '.clear-filter', function(){
        var tabId = $(this).data('tab');
        
        // Reset all filter dropdowns
        $('#filter_department_' + tabId).val('');
        $('#filter_year_' + tabId).val('');
        $('#filter_block_' + tabId).val('');
        $('#filter_floor_' + tabId).val('');
        $('#filter_room_type_' + tabId).val('');
        $('#filter_user_' + tabId).val('');
        
        // For vacated students tab, also reset additional filters
        if (tabId === 'vacated') {
            $('#filter_hostel_vacated').val('');
            $('#filter_room_vacated').val('');
            $('#filter_gender_vacated').val('');
            $('#filter_month_vacated').val('');
        }
        
        // Remove custom search functions
        $.fn.dataTable.ext.search = [];
        
        var table = datatables['table_' + tabId];
        table.search('').draw();
    });

    // Add Room
    $(document).on('click', '.add-room-btn', function(){
        var hostel = $(this).data('hostel');
        var hostelId = $(this).data('hostel-id');
        console.log('Hostel (data):', hostel);
        console.log('Hostel ID (data):', hostelId);
        
        var hostelIdAttr = $(this).attr('data-hostel-id');
        console.log('Hostel ID (attr):', hostelIdAttr);
        console.log('Button element:', this);
        
        if (!hostelId || hostelId == '0' || hostelId == 0) {
            hostelId = hostelIdAttr;
        }
        
        if (!hostelId || hostelId == '0' || hostelId == 0) {
            Swal.fire('Error', 'Hostel ID is missing. Please refresh the page and try again.', 'error');
            return;
        }
        $('#roomForm')[0].reset();
        $('#room_id').val('');
        $('#modal_hostel').val(hostelId);
        $('#hostel_name_display').text(hostel);
        $('#saveRoomBtn').text('Add Room');
        $('.modal-title').text('Add Room');
        $('#roomModal').modal('show');
    });

    // Edit room
    $(document).on('click', '.editRoom', function(){
        var roomId = $(this).data('room-id');
        var tr = $(this).closest('tr');
        var hostelName = tr.find('td').eq(1).text().trim();
        var hostelId = tr.data('hostel-id');
        var block = tr.find('td').eq(2).text().trim();
        var floor = tr.find('td').eq(3).text().trim();
        var roomNumber = tr.find('td').eq(4).text().trim();
        var roomType = tr.find('td').eq(5).text().trim();
        
        // Normalize room type to match dropdown options
        if (roomType.toLowerCase() === 'ac') {
            roomType = 'AC';
        } else if (roomType.toLowerCase() === 'non-ac' || roomType.toLowerCase() === 'nonac') {
            roomType = 'Non-AC';
        } else if (roomType === '' || roomType.toLowerCase() === 'null' || roomType.toLowerCase() === 'undefined') {
            roomType = 'Non-AC'; // Default value
        }
        var capacity = tr.find('td').eq(6).text().trim();

        $('#room_id').val(roomId);
        $('#modal_hostel').val(hostelId);
        $('#hostel_name_display').text(hostelName);

        $('#block').val(block);
        $('#floor').val(floor);
        $('#room_number').val(roomNumber);
        $('#room_type').val(roomType);
        $('#capacity').val(capacity);
        $('#occupied').val(tr.data('occupied') || 0);

        $('#saveRoomBtn').text('Update Room');
        $('.modal-title').text('Edit Room');
        $('#roomModal').modal('show');
    });

    // Save Room - COMPLETELY FIXED
    $(document).on('submit', '#roomForm', function(e){
        e.preventDefault();
        showLoader();
        var form = $(this);
        var data = form.serializeArray();
        console.log('Form data:', data);
        var isUpdate = $('#room_id').val() ? true : false;
        
        var hostelId = $('#modal_hostel').val();
        var roomNumber = $('#room_number').val();
        
        if (!isUpdate && (!hostelId || hostelId == '0')) {
            hideLoader();
            Swal.fire('Error', 'Please select a hostel', 'error');
            return;
        }
        
        if (!roomNumber || roomNumber.trim() === '') {
            hideLoader();
            Swal.fire('Error', 'Please enter a room number', 'error');
            return;
        }
        
        data.push({name:'action', value: (isUpdate ? 'update_room' : 'add_room')});

        $.post('room_backend.php', data, function(res){
            hideLoader();

            if (!res || !res.success) {
                Swal.fire('Error', (res && res.error) ? res.error : 'Server error occurred while saving room', 'error');
                return;
            }
            
            var rowData = res.data;
            var hostelName = rowData['hostel_name'];
            var mapping = {'Dr.Muthulakshmi':'muthu','Muthulakshmi':'muthu','Octa':'octa','Veda':'veda'};
            var tabId = mapping[hostelName] || 'muthu';
            var table = datatables['table_' + tabId];

            var studentDisplay = '<small class="text-muted">No students</small>';

                var actionBtns = '<div class="action-btns">' +
                    "<button class='btn btn-sm btn-success assignStudent' data-room-id='" + rowData.room_id + "' title='Assign'><i class='fa fa-user-plus'></i></button>" +
                    "<button class='btn btn-sm btn-warning transferBtn' data-room-id='" + rowData.room_id + "' title='Transfer Student'><i class='fa fa-exchange-alt'></i></button>" +
                    "<button class='btn btn-sm btn-info transferRoomBtn' data-room-id='" + rowData.room_id + "' title='Transfer Room'><i class='fa fa-users'></i></button>" +
                    "<button class='btn btn-sm btn-primary swapBtn' data-room-id='" + rowData.room_id + "' title='Swap'><i class='fa fa-sync-alt'></i></button>" +
                    "<button class='btn btn-sm btn-danger vacateBtn' data-room-id='" + rowData.room_id + "' title='Vacate'><i class='fa fa-sign-out-alt'></i></button>" +
                    "<button class='btn btn-sm btn-primary editRoom' data-room-id='" + rowData.room_id + "' title='Edit'><i class='fa fa-edit'></i></button>" +
                    "<button class='btn btn-sm btn-danger deleteRoom' data-room-id='" + rowData.room_id + "' title='Delete'><i class='fa fa-trash'></i></button>" +
                    "</div>";

            if (isUpdate) {
                // Use the same updateRoomInTable function for consistency
                var updatedRoomData = {
                    room_id: rowData.room_id,
                    hostel_name: rowData.hostel_name,
                    block: rowData.block || '',
                    floor: rowData.floor || '',
                    room_number: rowData.room_number,
                    room_type: rowData.room_type || '',
                    capacity: rowData.capacity,
                    occupied: rowData.occupied || 0,
                    student_info: '', // Will be updated when needed
                    departments: '',
                    academic_years: '',
                    student_names: '',
                    faculty_names: ''
                };
                
                updateRoomInTable(updatedRoomData);
                Swal.fire('Success', 'Room updated successfully', 'success');
            } else {
                var newRowData = [
                    '',
                    rowData.hostel_name,
                    rowData.block || '',
                    rowData.floor || '',
                    rowData.room_number,
                    rowData.room_type || '',
                    rowData.capacity,
                    studentDisplay,
                    actionBtns
                ];
                
                var newRow = table.row.add(newRowData);
                
                $(newRow.node()).attr('data-room-id', rowData.room_id)
                               .attr('data-capacity', rowData.capacity)
                               .attr('data-occupied', rowData.occupied || 0)
                               .attr('data-block', rowData.block || '')
                               .attr('data-floor', rowData.floor || '')
                               .attr('data-room-type', rowData.room_type || '')
                               .attr('data-departments', '')
                               .attr('data-years', '')
                               .attr('data-student-names', '')
                               .attr('data-faculty-names', '');
                
                $(newRow.node()).find('td').eq(0).addClass('sno text-center');
                
                table.draw(false);
                recalcSno('table_' + tabId);
                
                Swal.fire('Success', 'Room added successfully', 'success');
            }

            $('#roomModal').modal('hide');
            $('#roomForm')[0].reset();
        }, 'json').fail(function(xhr, status, error){ 
            hideLoader();
            console.error('AJAX Error:', status, error);
            console.error('Response:', xhr.responseText);
            Swal.fire('Error','Server error: ' + error + '. Check console for details.','error'); 
        });
    });

    // Delete Room
    $(document).on('click', '.deleteRoom', function(){
        console.log('Delete button clicked');
        var roomId = $(this).data('room-id');
        console.log('Room ID:', roomId);
        var row = $(this).closest('tr');
        var tableId = $(this).closest('table').attr('id');
        console.log('Table ID:', tableId);
        
        Swal.fire({
            title:'Confirm delete?',
            text:'This will permanently delete the room entry',
            icon:'warning',
            showCancelButton:true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then(function(res){
            if (!res.isConfirmed) return;
            showLoader();
            $.post('room_backend.php', {action:'delete_room', room_id: roomId}, function(resp){
                hideLoader();
                console.log('Delete room response:', resp);
                if (resp && resp.success) {
                    var table = datatables[tableId];
                    table.row(row).remove().draw();
                    recalcSno(tableId);
                    Swal.fire('Deleted!','Room removed successfully','success');
                } else {
                    Swal.fire('Error', (resp && resp.error) ? resp.error : 'Failed to delete room. Please try again.', 'error');
                }
            }, 'json').fail(function(xhr, status, error){ 
                hideLoader();
                console.error('Delete room failed:', xhr.responseText);
                Swal.fire('Error','Server error: ' + error + '. Check console for details.','error'); 
            });
        });
    });

    // Assign Student - COMPLETELY FIXED WITH FULL ROOM UPDATE
    $(document).on('click', '.assignStudent', function(){
        console.log('Assign button clicked');
        var roomId = $(this).data('room-id');
        console.log('Room ID:', roomId);
        var clickedRow = $(this).closest('tr');
        var tableElement = clickedRow.closest('table');
        var tableId = tableElement.attr('id');
        console.log('Table ID:', tableId);
        
        showLoader();
        $.post('room_backend.php', { action: 'get_available_students', room_id: roomId }, function(res){
            hideLoader();
            console.log('Get available students response:', res);
            if (!res || !res.success) { 
                Swal.fire('Error','Could not load students: ' + (res && res.error ? res.error : 'Unknown error'),'error'); 
                return; 
            }
            if (!res.data.length) { 
                Swal.fire('No Students','No available students','info'); 
                return; 
            }
            
            var options = '<option value="">Select Student</option>';
            res.data.forEach(function(s){
                options += "<option value='"+s.student_id+"'>"+s.name+" ("+s.roll_number+")</option>";
            });
            
            Swal.fire({
                title:'Assign student to room',
                html: '<select id="assignSelect" class="form-select" style="width:100%">' + options + '</select>',
                showCancelButton:true,
                confirmButtonText: 'Assign',
                didOpen: () => {
                    $('#assignSelect').select2({
                        theme: 'bootstrap-5',
                        dropdownParent: $('.swal2-container'),
                        placeholder: 'Search and select student',
                        allowClear: true
                    });
                },
                preConfirm: () => {
                    var st = $('#assignSelect').val();
                    if (!st) { 
                        Swal.showValidationMessage('Please select a student'); 
                        return false; 
                    }
                    return st;
                }
            }).then(function(result){
                if (result.isConfirmed && result.value) {
                    showLoader();
                    $.post('room_backend.php', { 
                        action: 'assign_student', 
                        room_id: roomId, 
                        student_id: result.value 
                    }, function(assignRes){
                        hideLoader();
                        console.log('Assign student response:', assignRes);
                        if (assignRes && assignRes.success && assignRes.updated_room) {
                            var table = datatables[tableId];
                            var tr = $("tr[data-room-id='" + roomId + "']");
                            
                            if (tr.length) {
                                var row = table.row(tr);
                                var currentData = row.data();
                                var currentSno = currentData[0];
                                
                                var updatedRoom = assignRes.updated_room;
                                var studentInfo = updatedRoom.student_info ? updatedRoom.student_info : '<small class="text-muted">No students</small>';
                                
                    var actionBtns = '<div class="action-btns">' +
                        "<button class='btn btn-sm btn-success assignStudent' data-room-id='" + updatedRoom.room_id + "' title='Assign'><i class='fa fa-user-plus'></i></button>" +
                        "<button class='btn btn-sm btn-warning transferBtn' data-room-id='" + updatedRoom.room_id + "' title='Transfer Student'><i class='fa fa-exchange-alt'></i></button>" +
                        "<button class='btn btn-sm btn-info transferRoomBtn' data-room-id='" + updatedRoom.room_id + "' title='Transfer Room'><i class='fa fa-users'></i></button>" +
                        "<button class='btn btn-sm btn-primary swapBtn' data-room-id='" + updatedRoom.room_id + "' title='Swap'><i class='fa fa-sync-alt'></i></button>" +
                        "<button class='btn btn-sm btn-danger vacateBtn' data-room-id='" + updatedRoom.room_id + "' title='Vacate'><i class='fa fa-sign-out-alt'></i></button>" +
                        "<button class='btn btn-sm btn-primary editRoom' data-room-id='" + updatedRoom.room_id + "' title='Edit'><i class='fa fa-edit'></i></button>" +
                        "<button class='btn btn-sm btn-danger deleteRoom' data-room-id='" + updatedRoom.room_id + "' title='Delete'><i class='fa fa-trash'></i></button>" +
                        "</div>";
                                
                                row.data([
                                    currentSno,
                                    updatedRoom.hostel_name,
                                    updatedRoom.block || '',
                                    updatedRoom.floor || '',
                                    updatedRoom.room_number,
                                    updatedRoom.room_type || '',
                                    updatedRoom.capacity,
                                    studentInfo,
                                    actionBtns
                                ]);
                                
                                $(row.node()).attr('data-room-id', updatedRoom.room_id)
                                             .attr('data-capacity', updatedRoom.capacity)
                                             .attr('data-occupied', updatedRoom.occupied || 0)
                                             .attr('data-block', updatedRoom.block || '')
                                             .attr('data-floor', updatedRoom.floor || '')
                                             .attr('data-room-type', updatedRoom.room_type || '')
                                             .attr('data-departments', updatedRoom.departments || '')
                                             .attr('data-years', updatedRoom.academic_years || '')
                                             .attr('data-student-names', updatedRoom.student_names || '')
                                             .attr('data-faculty-names', updatedRoom.faculty_names || '');
                                
                                table.draw(false);
                                recalcSno(tableId);
                            }
                            
                            Swal.fire('Success','Student assigned successfully','success');
                        } else {
                            Swal.fire('Error', assignRes && assignRes.error ? assignRes.error : 'Assignment failed', 'error');
                        }
                    }, 'json').fail(function(xhr, status, error){ 
                        hideLoader();
                        console.error('Assign failed:', xhr.responseText);
                        Swal.fire('Error','Failed to assign student: ' + error,'error'); 
                    });
                }
            });
        }, 'json').fail(function(xhr, status, error){ 
            hideLoader();
            console.error('Load students failed:', xhr.responseText);
            Swal.fire('Error','Failed to load students: ' + error,'error'); 
        });
    });

    // Transfer with Select2 search - FIXED
    $(document).on('click', '.transferBtn', function(){
        console.log('Transfer button clicked');
        var fromRoomId = $(this).data('room-id');
        console.log('From room ID:', fromRoomId);
        var hostelId = $(this).closest('tr').data('hostel-id');
        console.log('Hostel ID:', hostelId);
        showLoader();
        $.post('room_backend.php', { action: 'get_students', room_id: fromRoomId }, function(res){
            hideLoader();
            console.log('Get students response:', res);
            if (!res || !res.success) { 
                Swal.fire('Error', res && res.error ? res.error : 'Failed to load students', 'error'); 
                return; 
            }
            if (!res.data.length) { 
                Swal.fire('No students','This room has no students','info'); 
                return; 
            }
            
            showLoader();
            $.post('room_backend.php', { action: 'get_rooms_status', hostel_id: hostelId }, function(listRes){
                hideLoader();
                console.log('Get rooms status response:', listRes);
                if (!listRes || !listRes.success) { 
                    Swal.fire('Error', listRes && listRes.error ? listRes.error : 'Failed to load rooms', 'error'); 
                    return; 
                }
                // Filter to show all rooms in the same hostel with same gender that have available capacity
                var dests = listRes.data.filter(function(r){
                    var availableCapacity = parseInt(r.capacity) - parseInt(r.occupied);
                    return r.room_id != fromRoomId && availableCapacity > 0;
                });
                if (!dests.length) { 
                    Swal.fire('No rooms','No available destination rooms','info'); 
                    return; 
                }
                var studentsOptions = '<option value="">Select Student</option>' + res.data.map(s => "<option value='"+s.student_id+"'>"+s.name+" ("+s.roll_number+")</option>").join('');
                var destOptions = '<option value="">Select Destination Room</option>' + dests.map(d => "<option value='"+d.room_id+"'>"+d.hostel_name+" - "+d.room_number+" ("+d.occupied+"/"+d.capacity+")</option>").join('');
                Swal.fire({
                    title: 'Transfer student',
                    html: '<div class="mb-3"><label class="form-label">Select student</label><select id="trStudent" class="form-select" style="width:100%">'+studentsOptions+'</select></div>\
                           <div class="mb-3"><label class="form-label">Select destination room</label><select id="trDest" class="form-select" style="width:100%">'+destOptions+'</select></div>',
                    showCancelButton:true,
                    confirmButtonText: 'Transfer',
                    width: '600px',
                    didOpen: () => {
                        $('#trStudent').select2({
                            theme: 'bootstrap-5',
                            dropdownParent: $('.swal2-container'),
                            placeholder: 'Search student',
                            allowClear: true
                        });
                        $('#trDest').select2({
                            theme: 'bootstrap-5',
                            dropdownParent: $('.swal2-container'),
                            placeholder: 'Search destination room',
                            allowClear: true
                        });
                    },
                    preConfirm: () => {
                        var s = $('#trStudent').val(), d = $('#trDest').val();
                        if (!s || !d) { 
                            Swal.showValidationMessage('Please select both options'); 
                            return false; 
                        }
                        return {student_id: s, to_room_id: d};
                    }
                }).then(function(result){
                    if (result.isConfirmed && result.value) {
                        showLoader();
                        console.log('Transfer request data:', {
                            action: 'transfer_student', 
                            student_id: result.value.student_id, 
                            from_room_id: fromRoomId, 
                            to_room_id: result.value.to_room_id 
                        });
                        $.post('room_backend.php', { 
                            action: 'transfer_student', 
                            student_id: result.value.student_id, 
                            from_room_id: fromRoomId, 
                            to_room_id: result.value.to_room_id 
                        }, function(transferRes){
                            hideLoader();
                            console.log('Transfer response:', transferRes);
                            if (transferRes && transferRes.success) {
                                updateRoomInTable(transferRes.updated_from);
                                updateRoomInTable(transferRes.updated_to);
                                Swal.fire('Success','Transfer completed successfully','success');
                            } else {
                                Swal.fire('Error', transferRes && transferRes.error ? transferRes.error : 'Transfer failed', 'error');
                            }
                        }, 'json').fail(function(xhr, status, error){
                            hideLoader();
                            console.error('Transfer failed:', xhr.responseText);
                            // Check if the response is HTML (error page) instead of JSON
                            if (xhr.responseText && xhr.responseText.trim().startsWith('<')) {
                                Swal.fire('Error','Server error during transfer: The server returned an HTML error page instead of JSON. Check the server logs for details.','error');
                            } else {
                                Swal.fire('Error','Server error during transfer: ' + error + '. Check console for details.','error');
                            }
                        });
                    }
                });
            }, 'json').fail(function(xhr, status, error){ 
                hideLoader();
                console.error('Load rooms failed:', xhr.responseText);
                Swal.fire('Error','Failed to load rooms: ' + error + '. Check console for details.','error'); 
            });
        }, 'json').fail(function(xhr, status, error){ 
            hideLoader();
            console.error('Load students failed:', xhr.responseText);
            Swal.fire('Error','Failed to load students: ' + error + '. Check console for details.','error'); 
        });
    });

    // Transfer Room - NEW FUNCTIONALITY
    $(document).on('click', '.transferRoomBtn', function(){
        var fromRoomId = $(this).data('room-id');
        var hostelId = $(this).closest('tr').data('hostel-id');
        showLoader();
        $.post('room_backend.php', { action: 'get_students', room_id: fromRoomId }, function(res){
            hideLoader();
            if (!res || !res.success) { 
                Swal.fire('Error', res && res.error ? res.error : 'Failed to load room details', 'error'); 
                return; 
            }
            if (!res.data.length) { 
                Swal.fire('No students','This room has no students to transfer','info'); 
                return; 
            }
            
            showLoader();
            $.post('room_backend.php', { action: 'get_rooms_status', hostel_id: hostelId }, function(listRes){
                hideLoader();
                if (!listRes || !listRes.success) { 
                    Swal.fire('Error', listRes && listRes.error ? listRes.error : 'Failed to load rooms', 'error'); 
                    return; 
                }
                
                var dests = listRes.data.filter(function(r){
                    var availableCapacity = parseInt(r.capacity) - parseInt(r.occupied);
                    return r.room_id != fromRoomId && availableCapacity > 0;
                });
                
                if (!dests.length) { 
                    Swal.fire('No rooms','No available destination rooms','info'); 
                    return; 
                }
                
                var studentsOptions = '<option value="">Select Student</option>' + res.data.map(s => "<option value='"+s.student_id+"'>"+s.name+" ("+s.roll_number+")</option>").join('');
                var destOptions = '<option value="">Select Destination Room</option>' + dests.map(d => "<option value='"+d.room_id+"'>"+d.hostel_name+" - "+d.room_number+" (Available: "+(parseInt(d.capacity)-parseInt(d.occupied))+"/"+d.capacity+")</option>").join('');
                Swal.fire({
                    title: 'Transfer Room',
                    html: '<div class="alert alert-info">Transferring <strong>'+res.data.length+'</strong> students from this room to another room</div>\
                           <div class="mb-3"><label class="form-label">Select destination room</label><select id="trRoomDest" class="form-select" style="width:100%">'+destOptions+'</select></div>',
                    showCancelButton:true,
                    confirmButtonText: 'Transfer All Students',
                    width: '600px',
                    didOpen: () => {
                        $('#trRoomDest').select2({
                            theme: 'bootstrap-5',
                            dropdownParent: $('.swal2-container'),
                            placeholder: 'Search destination room',
                            allowClear: true
                        });
                    },
                    preConfirm: () => {
                        var d = $('#trRoomDest').val();
                        if (!d) { 
                            Swal.showValidationMessage('Please select a destination room'); 
                            return false; 
                        }
                        return {to_room_id: d};
                    }
                }).then(function(result){
                    if (result.isConfirmed && result.value) {
                        Swal.fire({
                            title: 'Confirm Transfer?',
                            text: 'This will transfer all '+res.data.length+' students from this room to the selected room. This action cannot be undone!',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#4caf50',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Yes, transfer all!'
                        }).then(function(confirmResult){
                            if (confirmResult.isConfirmed) {
                                showLoader();
                                $.post('room_backend.php', { 
                                    action: 'transfer_room', 
                                    from_room_id: fromRoomId, 
                                    to_room_id: result.value.to_room_id 
                                }, function(transferRes){
                                    hideLoader();
                                    if (transferRes && transferRes.success) {
                                        if (transferRes.updated_rooms) {
                                            transferRes.updated_rooms.forEach(function(room){ 
                                                updateRoomInTable(room);
                                            });
                                        }
                                        Swal.fire('Success','Successfully transferred '+transferRes.transferred_count+' students!','success');
                                    } else {
                                        Swal.fire('Error', transferRes && transferRes.error ? transferRes.error : 'Transfer failed', 'error');
                                    }
                                }, 'json').fail(function(xhr, status, error){
                                    hideLoader();
                                    console.error('Transfer room failed:', xhr.responseText);
                                    Swal.fire('Error','Server error during room transfer: ' + error,'error');
                                });
                            }
                        });
                    }
                });
            }, 'json').fail(function(xhr, status, error){ 
                hideLoader();
                console.error('Load rooms failed:', xhr.responseText);
                Swal.fire('Error','Failed to load rooms: ' + error,'error'); 
            });
        }, 'json').fail(function(xhr, status, error){ 
            hideLoader();
            console.error('Load students failed:', xhr.responseText);
            Swal.fire('Error','Failed to load students: ' + error,'error'); 
        });
    });

    // Swap with BOTH student dropdowns with Select2 search - FIXED
    $(document).on('click', '.swapBtn', function(){
        var roomA = $(this).data('room-id');
        var currentTr = $(this).closest('tr');
        showLoader();
        $.post('room_backend.php', { action: 'get_students', room_id: roomA }, function(resA){
            hideLoader();
            if (!resA || !resA.success) { 
                Swal.fire('Error', resA && resA.error ? resA.error : 'Failed to load room details', 'error'); 
                return; 
            }
            if (!resA.data.length) { 
                Swal.fire('No students','No students in this room','info'); 
                return; 
            }
            
            var hostelId = currentTr.data('hostel-id');
            
            showLoader();
            $.post('room_backend.php', { action: 'get_rooms_status', hostel_id: hostelId }, function(listRes){
                hideLoader();
                if (!listRes || !listRes.success) { 
                    Swal.fire('Error', listRes && listRes.error ? listRes.error : 'Failed to load rooms', 'error'); 
                    return; 
                }
                var candidates = listRes.data.filter(r => r.room_id != roomA && parseInt(r.occupied) > 0);
                if (!candidates.length) { 
                    Swal.fire('No rooms','No rooms with students to swap', 'info'); 
                    return; 
                }
                
                var optsRooms = '<option value="">Select Room</option>' + candidates.map(c => "<option value='"+c.room_id+"'>"+c.hostel_name+" - "+c.room_number+" ("+c.occupied+" students)</option>").join('');
                var studentsA = '<option value="">Select Student</option>' + resA.data.map(s => "<option value='"+s.student_id+"'>"+s.name+" ("+s.roll_number+")</option>").join('');
                
                Swal.fire({
                    title: 'Swap Students Between Rooms',
                    html: '<div class="mb-3"><label class="form-label fw-bold">Step 1: Select student from current room</label><select id="swA" class="form-select" style="width:100%">'+studentsA+'</select></div>\
                           <div class="mb-3"><label class="form-label fw-bold">Step 2: Select the room to swap with</label><select id="swRoom" class="form-select" style="width:100%">'+optsRooms+'</select></div>\
                           <div id="swBcontainer"><div class="alert alert-info"><i class="fa fa-info-circle"></i> Please select a room first to see available students</div></div>',
                    width: '650px',
                    showCancelButton: true,
                    confirmButtonText: 'Swap Students',
                    cancelButtonText: 'Cancel',
                    didOpen: () => {
                        $('#swA').select2({
                            theme: 'bootstrap-5',
                            dropdownParent: $('.swal2-container'),
                            placeholder: 'Search and select student',
                            allowClear: true,
                            width: '100%'
                        });
                        
                        $('#swRoom').select2({
                            theme: 'bootstrap-5',
                            dropdownParent: $('.swal2-container'),
                            placeholder: 'Search and select room',
                            allowClear: true,
                            width: '100%'
                        });
                        
                        function loadStudentsB(roomId){
                            $('#swBcontainer').html('<div class="text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading students...</div>');
                            
                            $.post('room_backend.php', { action: 'get_students', room_id: roomId }, function(resB){
                                if (!resB || !resB.success) { 
                                    $('#swBcontainer').html('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> Failed to load students: ' + (resB && resB.error ? resB.error : 'Unknown error') + '</div>'); 
                                    return; 
                                }
                                if (!resB.data.length) { 
                                    $('#swBcontainer').html('<div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i> No students found in selected room</div>'); 
                                    return; 
                                }
                                
                                var opts = '<option value="">Select Student</option>' + resB.data.map(s => "<option value='"+s.student_id+"'>"+s.name+" ("+s.roll_number+")</option>").join('');
                                $('#swBcontainer').html('<div class="mb-3"><label class="form-label fw-bold">Step 3: Select student from the other room</label><select id="swB" class="form-select" style="width:100%">'+opts+'</select></div>');
                                
                                $('#swB').select2({
                                    theme: 'bootstrap-5',
                                    dropdownParent: $('.swal2-container'),
                                    placeholder: 'Search and select student',
                                    allowClear: true,
                                    width: '100%'
                                });
                            }, 'json').fail(function(xhr, status, error){
                                $('#swBcontainer').html('<div class="alert alert-danger"><i class="fa fa-times-circle"></i> Server error while loading students: ' + error + '</div>');
                            });
                        }
                        
                        $('#swRoom').on('change', function(){ 
                            var rid = $(this).val();
                            if(rid) {
                                loadStudentsB(rid);
                            } else {
                                $('#swBcontainer').html('<div class="alert alert-info"><i class="fa fa-info-circle"></i> Please select a room to see available students</div>');
                            }
                        });
                    },
                    preConfirm: () => {
                        var a = $('#swA').val();
                        var b = $('#swB').val();
                        var roomSelected = $('#swRoom').val();
                        
                        if (!a) { 
                            Swal.showValidationMessage('Please select a student from the current room'); 
                            return false; 
                        }
                        if (!roomSelected) { 
                            Swal.showValidationMessage('Please select a room to swap with'); 
                            return false; 
                        }
                        if (!b) { 
                            Swal.showValidationMessage('Please select a student from the other room'); 
                            return false; 
                        }
                        
                        return {student_a_id: a, student_b_id: b};
                    }
                }).then(function(result){
                    if (result.isConfirmed && result.value) {
                        showLoader();
                        $.post('room_backend.php', { 
                            action: 'swap_students', 
                            student_a_id: result.value.student_a_id, 
                            student_b_id: result.value.student_b_id 
                        }, function(swapRes){
                            hideLoader();
                            if (swapRes && swapRes.success) {
                                if (swapRes.updated_rooms) {
                                    swapRes.updated_rooms.forEach(function(room){ 
                                        updateRoomInTable(room);
                                    });
                                }
                                Swal.fire('Success','Students swapped successfully!','success');
                            } else {
                                Swal.fire('Error', swapRes && swapRes.error ? swapRes.error : 'Swap failed', 'error');
                            }
                        }, 'json').fail(function(xhr, status, error){
                            hideLoader();
                            console.error('Swap failed:', xhr.responseText);
                            Swal.fire('Error','Server error during swap: ' + error,'error');
                        });
                    }
                });
            }, 'json').fail(function(xhr, status, error){ 
                hideLoader();
                console.error('Load rooms failed:', xhr.responseText);
                Swal.fire('Error','Failed to load rooms: ' + error,'error'); 
            });
        }, 'json').fail(function(xhr, status, error){ 
            hideLoader();
            console.error('Load students failed:', xhr.responseText);
            Swal.fire('Error','Failed to load students: ' + error,'error'); 
        });
    });

    // Helper function to update room in table
    function updateRoomInTable(roomData) {
        if (!roomData) return;
        
        var tr = $("tr[data-room-id='" + roomData.room_id + "']");
        if (!tr.length) return;
        
        var tableId = tr.closest('table').attr('id');
        var table = datatables[tableId];
        if (!table) return;
        
        var row = table.row(tr);
        
        // Validate that we have all required data
        if (!roomData.room_id || !roomData.hostel_name) {
            console.error('Missing required room data:', roomData);
            return;
        }
        
        // Hide room only if it's full AND has no students
        if (roomData.occupied >= roomData.capacity && (!roomData.student_info || roomData.student_info.trim() === '')) {
            row.remove().draw(false);
            recalcSno(tableId);
            return;
        }
        
        var studentInfo = roomData.student_info ? roomData.student_info : '<small class="text-muted">No students</small>';
        
        // Fixed the action buttons to ensure proper data attributes
        var actionBtns = '<div class="action-btns">' +
            "<button class='btn btn-sm btn-success assignStudent' data-room-id='" + roomData.room_id + "' title='Assign'><i class='fa fa-user-plus'></i></button>" +
            "<button class='btn btn-sm btn-warning transferBtn' data-room-id='" + roomData.room_id + "' title='Transfer Student'><i class='fa fa-exchange-alt'></i></button>" +
            "<button class='btn btn-sm btn-info transferRoomBtn' data-room-id='" + roomData.room_id + "' title='Transfer Room'><i class='fa fa-users'></i></button>" +
            "<button class='btn btn-sm btn-primary swapBtn' data-room-id='" + roomData.room_id + "' title='Swap'><i class='fa fa-sync-alt'></i></button>" +
            "<button class='btn btn-sm btn-danger vacateBtn' data-room-id='" + roomData.room_id + "' title='Vacate'><i class='fa fa-sign-out-alt'></i></button>" +
            "<button class='btn btn-sm btn-primary editRoom' data-room-id='" + roomData.room_id + "' title='Edit'><i class='fa fa-edit'></i></button>" +
            "<button class='btn btn-sm btn-danger deleteRoom' data-room-id='" + roomData.room_id + "' title='Delete'><i class='fa fa-trash'></i></button>" +
            "</div>";
        
        row.data([
            '', // S.No. will be recalculated by recalcSno
            roomData.hostel_name,
            roomData.block || '',
            roomData.floor || '',
            roomData.room_number,
            roomData.room_type || '',
            roomData.capacity,
            studentInfo,
            actionBtns
        ]);
        
        // Fixed data attributes to ensure proper faculty filtering
        $(row.node()).attr('data-room-id', roomData.room_id)
                     .attr('data-hostel-id', roomData.hostel_id || '')
                     .attr('data-capacity', roomData.capacity)
                     .attr('data-occupied', roomData.occupied || 0)
                     .attr('data-block', roomData.block || '')
                     .attr('data-floor', roomData.floor || '')
                     .attr('data-room-type', roomData.room_type || '')
                     .attr('data-departments', roomData.departments || '')
                     .attr('data-years', roomData.academic_years || '')
                     .attr('data-student-names', roomData.student_names || '')
                     .attr('data-faculty-names', roomData.faculty_names || '');
        
        // Redraw the table and recalculate serial numbers
        table.draw(false);
        // Small delay to ensure the table is fully rendered before recalculating S.No.
        setTimeout(function() {
            recalcSno(tableId);
        }, 10);
    }

    // Download PDF button - allow server-side generation for hostel-specific PDFs, client-side for vacated students
    $('.download-pdf-btn').on('click', function(e){
        // Allow server-side PDF generation for hostel-specific downloads
        var hostel = $(this).data('hostel') || '';
        if (hostel) {
            // Let the default behavior happen (server-side PDF generation)
            return true;
        } else {
            // For vacated students, use client-side generation
            e.preventDefault();
            showLoader();
            var table = document.getElementById('table_vacated');
            if (!table) { hideLoader(); return Swal.fire('Error','Could not locate vacated students table','error'); }
            generateRoomsPDF(table, 'Vacated Students');
            setTimeout(function(){ hideLoader(); }, 800);
        }
    });

    // Download XLS button - allow server-side generation for hostel-specific Excel files, client-side for vacated students
    $('.download-xls-btn').on('click', function(e){
        // Allow server-side Excel generation for hostel-specific downloads
        var hostel = $(this).data('hostel') || '';
        if (hostel) {
            // Let the default behavior happen (server-side Excel generation)
            return true;
        } else {
            // For vacated students, use client-side generation
            e.preventDefault();
            showLoader();
            var table = document.getElementById('table_vacated');
            if (!table) { hideLoader(); return Swal.fire('Error','Vacated students table not found','error'); }
            exportRoomsExcel(table, 'Vacated Students');
            setTimeout(function(){ hideLoader(); }, 800);
        }
    });

    /* ========== Client side export helpers (styled like general_leave.php) ========== */
    function generateRoomsPDF(tableEl, title) {
        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
            const headerColor = [0, 109, 109];
            const borderGray = [180, 180, 180];

            const headers = Array.from(tableEl.querySelectorAll('thead tr th')).map(th => th.textContent.trim());
            const rows = Array.from(tableEl.querySelectorAll('tbody tr')).map(tr => Array.from(tr.querySelectorAll('td')).map(td => td.textContent.trim()));
            if (rows.length === 0) { return Swal.fire('No Data', 'There are no records to export', 'info'); }

            // Load logos
            const leftLogo = new Image(); leftLogo.src = 'image/mkce_logo2.jpg';
            const rightLogo = new Image(); rightLogo.src = 'image/kr.jpg';
            Promise.all([
                new Promise(r => { leftLogo.onload = leftLogo.onerror = r; }),
                new Promise(r => { rightLogo.onload = rightLogo.onerror = r; })
            ]).then(() => {
                const pageWidth = doc.internal.pageSize.getWidth();
                const logoSize = 18;
                const rightX = pageWidth - 15 - logoSize;
                try { if (leftLogo.complete && leftLogo.naturalHeight !== 0) doc.addImage(leftLogo, 'JPG', 15, 10, logoSize, logoSize); } catch (e) {}
                try { if (rightLogo.complete && rightLogo.naturalHeight !== 0) doc.addImage(rightLogo, 'JPG', rightX, 10, logoSize, logoSize); } catch (e) {}

                const leftLogoRightEdge = 15 + logoSize; const rightLogoLeftEdge = rightX; const logosCenterX = (leftLogoRightEdge + rightLogoLeftEdge) / 2;

                doc.setFont('helvetica', 'bold'); doc.setFontSize(14);
                doc.text('M.Kumarasamy College of Engineering, Karur - 639 113', logosCenterX, 25, { align: 'center' });
                doc.setFont('helvetica', 'italic'); doc.setFontSize(10);
                doc.text('(An Autonomous Institution Affiliated to Anna University, Chennai)', logosCenterX, 30, { align: 'center' });

                doc.setFont('helvetica', 'bold'); doc.setFontSize(12);
                doc.text(title.toUpperCase(), logosCenterX, 43, { align: 'center' });

                const generatedDateStr = new Date().toLocaleString();
                doc.setFont('helvetica', 'normal'); doc.setFontSize(9);
                doc.text(`Generated Date: ${generatedDateStr}`, 15, 51, { align: 'left' });
                doc.text('Generated by : Admin', pageWidth - 15, 51, { align: 'right' });

                doc.setDrawColor(...borderGray); doc.line(10, 55, pageWidth - 10, 55);

                let startY = 60;
                doc.autoTable({
                    startY: startY,
                    head: [headers],
                    body: rows,
                    theme: 'grid',
                    styles: { fontSize: 9, halign: 'center', valign: 'middle', lineColor: borderGray, lineWidth: 0.2 },
                    headStyles: { fillColor: headerColor, textColor: 255, fontStyle: 'bold' },
                    alternateRowStyles: { fillColor: [242, 247, 247] }
                });

                // page numbers
                const totalPages = doc.internal.getNumberOfPages();
                for (let i = 1; i <= totalPages; i++) {
                    doc.setPage(i);
                    const pageHeight = doc.internal.pageSize.getHeight();
                    doc.setFont('helvetica', 'normal'); doc.setFontSize(8); doc.setTextColor(50);
                    doc.text(`Page ${i} / ${totalPages}`, pageWidth / 2, pageHeight - 10, { align: 'center' });
                }

                const formatDateForFilename = (d) => {
                    const pad = (n) => String(n).padStart(2, '0');
                    return `${pad(d.getDate())}-${pad(d.getMonth()+1)}-${d.getFullYear()} ${pad(d.getHours())}-${pad(d.getMinutes())}-${pad(d.getSeconds())}`;
                };
                const fname = `${title} (${formatDateForFilename(new Date())}).pdf`;
                doc.save(fname);
            }).catch(err => {
                console.warn('Logo load or PDF generation error', err);
            });
        } catch (err) {
            console.error('PDF generation failed', err);
            Swal.fire('Error','Failed to generate PDF. See console for details','error');
        }
    }

    function exportRoomsExcel(tableEl, title) {
        try {
            let html = '';
            html += '<table border="0" cellspacing="0" cellpadding="0" style="width:100%;">';
            html += '<tr><th colspan="6" style="background:#0aa2a1;color:#fff;font-size:14px;">M.KUMARASAMY COLLEGE OF ENGINEERING, KARUR - 639 113</th></tr>';
            html += '<tr><th colspan="6" style="background:#f2f2f2;color:#000;font-size:11px;">(An Autonomous Institution Affiliated to Anna University, Chennai)</th></tr>';
            html += `<tr><th colspan="6" style="text-align:center;font-weight:bold;">${title} - ${new Date().toLocaleString()}</th></tr>`;
            html += '</table><br/>';

            // Add a horizontal line after Generated Date
            html += '<table border="0" cellspacing="0" cellpadding="0" style="width:100%;"><tr><td style="border-bottom: 1px solid #000;">&nbsp;</td></tr></table><br/>';

            const tableClone = tableEl.cloneNode(true);
            // clean data if necessary (remove action buttons)
            tableClone.querySelectorAll('button, .action-btns, a').forEach(n => n.remove());
            const tableHtml = '<table border="1" cellspacing="0" cellpadding="4">' + tableClone.querySelector('thead').outerHTML + tableClone.querySelector('tbody').outerHTML + '</table>';

            html = '<!doctype html><html><head><meta charset="UTF-8"><style>table, th, td { font-family: "Times New Roman", Times, serif; }</style></head><body>' + html + tableHtml + '</body></html>';

            const blob = new Blob(['\uFEFF' + html], { type: 'application/vnd.ms-excel;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            const formatDateForFilename = (d) => {
                const pad = (n) => String(n).padStart(2, '0');
                return `${pad(d.getDate())}-${pad(d.getMonth()+1)}-${d.getFullYear()} ${pad(d.getHours())}-${pad(d.getMinutes())}-${pad(d.getSeconds())}`;
            };
            a.download = `${title} (${formatDateForFilename(new Date())}).xls`;
            document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
        } catch (e) {
            console.error('Excel generation failed', e);
            Swal.fire('Error','Failed to create Excel file','error');
        }
    }

    // Initialize S.No for all tables on page load
    $('.hostel-table').each(function(){ 
        var tblId = $(this).attr('id');
        setTimeout(function(){
            recalcSno(tblId);
        }, 200);
    });

    function getVacatedTable() {
        if ($.fn.dataTable.isDataTable('#table_vacated')) {
            return $('#table_vacated').DataTable();
        }

        return $('#table_vacated').DataTable({
            pageLength: 10,
            lengthChange: false,
            ordering: true,
            searching: true,
            responsive: true,
            order: [[6, 'desc']], // Sort by Vacated At column
            language: {
                emptyTable: 'No vacated students found'
            }
        });
    }
    
    // Initialize DataTable for vacated students
    setTimeout(function() {
        try {
            getVacatedTable();
        } catch (e) {
            console.error('Error initializing DataTable for vacated students:', e);
        }
    }, 100);
    
    // Search input for vacated students
    $('.table-search[data-target="vacated"]').on('input', function(){
        var tbl = getVacatedTable();
        tbl.search(this.value).draw();
    });
    
    // Apply filter for vacated students
    $(document).on('click', '.apply-filter[data-tab="vacated"]', function(){
        var department = $('#filter_department_vacated').val() || '';
        var year = $('#filter_year_vacated').val() || '';
        var hostel = $('#filter_hostel_vacated').val() || '';
        var room = $('#filter_room_vacated').val() || '';
        var gender = $('#filter_gender_vacated').val() || '';
        var month = $('#filter_month_vacated').val() || '';
        
        var table = getVacatedTable();
        table.search('').draw(); // Clear global search
        
        // Reset custom filters
        $.fn.dataTable.ext.search = [];
        
        // helpers
        function normalize(s){ return (s||'').toString().trim().toLowerCase(); }
        
        function matchList(attrString, val){
            if (!val) return true;
            if (!attrString) return false;
            var list = attrString.split('|').map(function(x){ return (x||'').toString().trim().toLowerCase(); });
            val = normalize(val);
            if (list.indexOf(val) !== -1) return true;
            // try mapping via departmentShortcuts if available (both directions)
            if (typeof departmentShortcuts !== 'undefined'){
                for (var k in departmentShortcuts){
                    if (!departmentShortcuts.hasOwnProperty(k)) continue;
                    var full = normalize(k);
                    var shortv = normalize(departmentShortcuts[k]);
                    if (full === val && list.indexOf(shortv) !== -1) return true;
                    if (shortv === val && list.indexOf(full) !== -1) return true;
                }
            }
            // fallback: substring match
            for (var i=0;i<list.length;i++){
                if (list[i].indexOf(val) !== -1) return true;
            }
            return false;
        }
        
        // Add the filtering function
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
            if (settings.nTable.id !== 'table_vacated') return true;
            
            // Use DataTables' API row() to get node
            var rowNode = table.row(dataIndex).node();
            
            // department (column 3 in HTML, but 2 in data array since S.No is 0)
            var rowDepartments = $(rowNode).attr('data-departments') || '';
            if (department && !matchList(rowDepartments, department)) return false;
            
            // year (column 4 in HTML, but 3 in data array)
            var rowYears = $(rowNode).attr('data-years') || '';
            if (year && !matchList(rowYears, year)) return false;
            
            // hostel (column 5 in HTML, but 4 in data array)
            var rowHostel = $(rowNode).attr('data-hostel') || '';
            if (hostel && normalize(rowHostel) !== normalize(hostel)) return false;
            
            // room (column 6 in HTML, but 5 in data array)
            var rowRoom = $(rowNode).attr('data-room') || '';
            if (room && normalize(rowRoom).indexOf(normalize(room)) === -1) return false;
            
            // gender
            var rowGender = $(rowNode).attr('data-gender') || '';
            if (gender && normalize(rowGender) !== normalize(gender)) return false;
            
            // month - check the vacated_at date (column 6 in HTML, but 6 in data array)
            if (month) {
                // Get the date from the table data (7th column, index 6)
                var vacatedDate = data[6]; // Vacated At column
                if (vacatedDate) {
                    // Extract month from date string (assuming format like "2025-11-18 10:30:45")
                    var dateParts = vacatedDate.split(' ')[0].split('-');
                    if (dateParts.length >= 2) {
                        var vacatedMonth = dateParts[1]; // Month is at index 1 (0-indexed)
                        if (vacatedMonth !== month) return false;
                    }
                } else {
                    return false; // No date available
                }
            }
            
            return true;
        });
        
        table.draw();
    });
    
    // Clear filter for vacated students
    $(document).on('click', '.clear-filter[data-tab="vacated"]', function(e){
        // Prevent the general clear filter handler from executing
        e.stopPropagation();
        
        // Reset all filter dropdowns
        $('#filter_department_vacated').val('');
        $('#filter_year_vacated').val('');
        $('#filter_hostel_vacated').val('');
        $('#filter_room_vacated').val('');
        $('#filter_gender_vacated').val('');
        $('#filter_month_vacated').val('');
        
        // Remove custom search functions
        $.fn.dataTable.ext.search = [];
        
        var table = getVacatedTable();
        table.search('').draw();
    });
    
    // Vacate button click handler
    $(document).on('click', '.vacateBtn', function(){
        var roomId = $(this).data('room-id');
        showLoader();
        
        // Get students in the room
        $.post('room_backend.php', { action: 'get_students', room_id: roomId }, function(res){
            hideLoader();
            if (!res || !res.success) { 
                Swal.fire('Error', res && res.error ? res.error : 'Failed to load room details', 'error'); 
                return; 
            }
            if (!res.data.length) { 
                Swal.fire('No students','This room has no students to vacate','info'); 
                return; 
            }
            
            // Store room ID and students data
            $('#vacateModal').data('room-id', roomId);
            $('#vacateModal').data('students', res.data);
            
            // Populate student dropdown
            var options = '';
            res.data.forEach(function(s){
                options += "<option value='" + s.student_id + "'>" + s.name + " (" + s.roll_number + ")</option>";
            });
            $('#vacateStudentSelect').html(options);
            
            // Reset form
            $('#vacateAll').prop('checked', true);
            $('#studentSelectContainer').hide();
            
            // Show modal
            $('#vacateModal').modal('show');
        }, 'json').fail(function(xhr, status, error){ 
            hideLoader();
            console.error('Load students failed:', xhr.responseText);
            Swal.fire('Error','Failed to load students: ' + error,'error'); 
        });
    });
    
    // Toggle student select dropdown
    $('input[name="vacateOption"]').on('change', function(){
        if ($(this).val() === 'select') {
            $('#studentSelectContainer').show();
            $('#vacateStudentSelect').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#vacateModal'),
                placeholder: 'Select students to vacate',
                allowClear: true
            });
        } else {
            $('#studentSelectContainer').hide();
            if ($('#vacateStudentSelect').hasClass('select2-hidden-accessible')) {
                $('#vacateStudentSelect').select2('destroy');
            }
        }
    });
    
    // Confirm vacate button
    $(document).on('click', '#confirmVacate', function(){
        var roomId = $('#vacateModal').data('room-id');
        var vacateOption = $('input[name="vacateOption"]:checked').val();
        var studentIds = [];
        
        if (vacateOption === 'all') {
            var students = $('#vacateModal').data('students');
            studentIds = students.map(s => s.student_id);
        } else {
            studentIds = $('#vacateStudentSelect').val();
            if (!studentIds || studentIds.length === 0) {
                Swal.fire('Error', 'Please select at least one student', 'error');
                return;
            }
        }
        
        // Confirm action
        var confirmMsg = vacateOption === 'all' 
            ? 'Are you sure you want to vacate all students from this room?' 
            : 'Are you sure you want to vacate the selected students?';
            
        Swal.fire({
            title: 'Confirm Vacate',
            text: confirmMsg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, vacate!'
        }).then(function(result){
            if (result.isConfirmed) {
                showLoader();
                
                $.post('room_backend.php', {
                    action: 'vacate_students',
                    room_id: roomId,
                    student_ids: studentIds
                }, function(vacateRes){
                    hideLoader();
                    if (vacateRes && vacateRes.success) {
                        $('#vacateModal').modal('hide');
                        
                        // Update room in table
                        if (vacateRes.updated_room) {
                            updateRoomInTable(vacateRes.updated_room);
                        }
                        
                        Swal.fire('Success', 'Students vacated successfully!', 'success');
                    } else {
                        Swal.fire('Error', vacateRes && vacateRes.error ? vacateRes.error : 'Vacate failed', 'error');
                    }
                }, 'json').fail(function(xhr, status, error){
                    hideLoader();
                    console.error('Vacate failed:', xhr.responseText);
                    Swal.fire('Error','Server error during vacate: ' + error,'error');
                });
            }
        });
    });
    
    // Hide loader on page load
    hideLoader();
});

// Define the mapping between dropdown full names and database short forms
const departmentShortcuts = {
    "Computer Science Engineering": "CSE",
    "Computer Science And Business Systems": "CSBS",
    "Electronics and Communication Engineering": "ECE",
    "Information Technology": "IT",
    "Mechanical Engineering": "MECH",
    "Electrical and Electronics Engineering": "EEE",
    "Civil Engineering": "CIVIL",
    "Chemical Engineering": "CHEM",
};
</script>


<script>

</script>

<script>
// Update vacated students download links to include current filters
function updateVacatedDownloadLinks() {
    const department = $('#filter_department_vacated').val() || '';
    const year = $('#filter_year_vacated').val() || '';
    const hostel = $('#filter_hostel_vacated').val() || '';
    const room = $('#filter_room_vacated').val() || '';
    const gender = $('#filter_gender_vacated').val() || '';
    const month = $('#filter_month_vacated').val() || '';
    
    let pdfUrl = 'room_backend.php?download_vacated_pdf=1';
    let xlsUrl = 'room_backend.php?download_vacated_xls=1';
    
    const params = [];
    if (department) params.push('department=' + encodeURIComponent(department));
    if (year) params.push('year=' + encodeURIComponent(year));
    if (hostel) params.push('hostel=' + encodeURIComponent(hostel));
    if (room) params.push('room=' + encodeURIComponent(room));
    if (gender) params.push('gender=' + encodeURIComponent(gender));
    if (month) params.push('month=' + encodeURIComponent(month));
    
    if (params.length > 0) {
        const paramString = params.join('&');
        pdfUrl += '&' + paramString;
        xlsUrl += '&' + paramString;
    }
    
    $('.download-pdf-btn[href*="download_vacated_pdf"]').attr('href', pdfUrl);
    $('.download-xls-btn[href*="download_vacated_xls"]').attr('href', xlsUrl);
}

// Update regular room download links to include current filters
function updateRoomDownloadLinks() {
    // Get all hostel tabs and update their download links
    $('.hostel-table').each(function() {
        const tableId = $(this).attr('id');
        const tabId = tableId.replace('table_', '');
        const hostelName = $(this).closest('.tab-pane').find('.card-header .card-title').text().replace(' - Rooms', '').trim();
        
        // Get filter values for this tab
        const department = $('#filter_department_' + tabId).val() || '';
        const year = $('#filter_year_' + tabId).val() || '';
        const block = $('#filter_block_' + tabId).val() || '';
        const floor = $('#filter_floor_' + tabId).val() || '';
        const roomType = $('#filter_room_type_' + tabId).val() || '';
        const userType = $('#filter_user_' + tabId).val() || '';
        
        // Build URLs with filter parameters
        let pdfUrl = 'room_backend.php?download_pdf=1&hostel=' + encodeURIComponent(hostelName);
        let xlsUrl = 'room_backend.php?download_xls=1&hostel=' + encodeURIComponent(hostelName);
        
        const params = [];
        if (department) params.push('department=' + encodeURIComponent(department));
        if (year) params.push('year=' + encodeURIComponent(year));
        if (block) params.push('block=' + encodeURIComponent(block));
        if (floor) params.push('floor=' + encodeURIComponent(floor));
        if (roomType) params.push('room_type=' + encodeURIComponent(roomType));
        if (userType) params.push('user_type=' + encodeURIComponent(userType));
        
        if (params.length > 0) {
            const paramString = params.join('&');
            pdfUrl += '&' + paramString;
            xlsUrl += '&' + paramString;
        }
        
        // Update download links for this hostel tab
        $('.download-pdf-btn[data-hostel="' + hostelName + '"]').attr('href', pdfUrl);
        $('.download-xls-btn[data-hostel="' + hostelName + '"]').attr('href', xlsUrl);
    });
}

// Update download links when filters change
$(document).on('change', '#filter_department_vacated, #filter_year_vacated, #filter_hostel_vacated, #filter_room_vacated, #filter_gender_vacated, #filter_month_vacated', updateVacatedDownloadLinks);

// Update download links when applying filters
$(document).on('click', '.apply-filter[data-tab="vacated"]', updateVacatedDownloadLinks);

// Update download links when clearing filters
$(document).on('click', '.clear-filter[data-tab="vacated"]', function() {
    // Use a small delay to ensure the filters are cleared before updating links
    setTimeout(updateVacatedDownloadLinks, 10);
});

// Update regular room download links when filters change
$(document).on('change', '.filter-department, .filter-year, .filter-block, .filter-floor, .filter-room-type, .filter-user', function() {
    // Use a small delay to ensure the filter value is updated before updating links
    setTimeout(updateRoomDownloadLinks, 10);
});

// Update regular room download links when applying filters
$(document).on('click', '.apply-filter:not([data-tab="vacated"])', updateRoomDownloadLinks);

// Update regular room download links when clearing filters
$(document).on('click', '.clear-filter:not([data-tab="vacated"])', function() {
    // Use a small delay to ensure the filters are cleared before updating links
    setTimeout(updateRoomDownloadLinks, 10);
});

// Initialize download links
updateVacatedDownloadLinks();
updateRoomDownloadLinks();
</script>

    

</script>

<script>
// Add click handlers for the download buttons to open in new tab
document.addEventListener('DOMContentLoaded', function() {
    // The download buttons now use direct links, so we don't need to handle them with JavaScript
    // But we can still add some visual feedback
    document.querySelectorAll('.download-pdf-btn, .download-xls-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Add visual feedback that the download has started
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Preparing...';
            this.classList.add('disabled');
            
            // Restore original text after a short delay
            setTimeout(() => {
                this.innerHTML = originalText;
                this.classList.remove('disabled');
            }, 2000);
        });
    });
});
</script>

