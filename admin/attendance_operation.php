<?php
session_start();
include '../db.php';
include './admin_scope.php';

if (!is_any_admin_role()) {
    header('Location: ../login');
    exit;
}


if (isset($_POST['enableAttendance'])) {
    $from_time = $_POST['from_time'];
    $to_time = $_POST['to_time'];
    $late_entry_time = $_POST['late_entry_time'];
    $enabled_by = 'Admin';
    $hostel_id = isset($_POST['hostel_id']) && ctype_digit($_POST['hostel_id']) ? (int)$_POST['hostel_id'] : null;

    // Ensure the attendance_time_control table has a hostel_id column. If not, try to add it.
    $colRes = $conn->query("SHOW COLUMNS FROM attendance_time_control LIKE 'hostel_id'");
    if ($colRes && $colRes->num_rows == 0) {
        // Best effort: add column (nullable) to support per-hostel timing. Requires DB privileges.
        $conn->query("ALTER TABLE attendance_time_control ADD COLUMN hostel_id INT(11) DEFAULT NULL");
    }

    // Disable only existing enabled rows for this hostel (if specified and column exists) otherwise disable global records
    if ($hostel_id !== null) {
        // check if hostel_id column exists (ALTER may have been attempted above)
        $colResDisable = $conn->query("SHOW COLUMNS FROM attendance_time_control LIKE 'hostel_id'");
        $hasHostelColDisable = ($colResDisable && $colResDisable->num_rows > 0);
        if ($hasHostelColDisable) {
            $stmt_disable = $conn->prepare("UPDATE attendance_time_control SET status='disabled' WHERE hostel_id = ?");
            if ($stmt_disable) {
                $stmt_disable->bind_param('i', $hostel_id);
                $stmt_disable->execute();
                $stmt_disable->close();
            } else {
                // Fallback: disable all if prepare fails
                $conn->query("UPDATE attendance_time_control SET status='disabled'");
            }
        } else {
            // Column not present -> fallback to disabling all
            $conn->query("UPDATE attendance_time_control SET status='disabled'");
        }
    } else {
        $conn->query("UPDATE attendance_time_control SET status='disabled'");
    }

    // Insert a new attendance time control record.
    $colRes = $conn->query("SHOW COLUMNS FROM attendance_time_control LIKE 'hostel_id'");
    $hasHostelCol = ($colRes && $colRes->num_rows > 0);
    if ($hasHostelCol && $hostel_id !== null) {
        // Insert with hostel_id when provided
        $stmt = $conn->prepare("INSERT INTO attendance_time_control (hostel_id, from_time, to_time, late_entry_time, enabled_by, status) VALUES (?, ?, ?, ?, ?, 'enabled')");
        if ($stmt) {
            $stmt->bind_param("issss", $hostel_id, $from_time, $to_time, $late_entry_time, $enabled_by);
        }
    } else {
        // Insert without hostel_id (global record)
        $stmt = $conn->prepare("INSERT INTO attendance_time_control (from_time, to_time, late_entry_time, enabled_by, status) VALUES (?, ?, ?, ?, 'enabled')");
        if ($stmt) {
            $stmt->bind_param("ssss", $from_time, $to_time, $late_entry_time, $enabled_by);
        }
    }

    if ($stmt->execute()) {
        $storedHostel = ($hostel_id !== null) ? $hostel_id : '';
        echo "<script>
            try { localStorage.setItem('hostelFilterTime', '" . $storedHostel . "'); } catch(e) {}
            Swal.fire({
                title: 'Success!',
                text: 'Attendance Time Enabled Successfully!',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'attendance_operation.php';
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                title: 'Error!',
                text: 'Error enabling attendance time.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        </script>";
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'disable_time') {
    header('Content-Type: application/json');
    $time_id = $_POST['time_id'];

    $stmt = $conn->prepare("UPDATE attendance_time_control SET status='disabled' WHERE id=?");
    $stmt->bind_param("i", $time_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Attendance time disabled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to disable attendance time']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../db.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Hostel Management</title>
    <link rel="icon" type="image/png" sizes="32x32" href="image/icons/mkce_s.png">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-5/bootstrap-5.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <style>
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            color: #333;
            line-height: 1.6;
        }

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


        .content-nav {
            background: linear-gradient(45deg, #4e73df, #1cc88a);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .content-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 20px;
            overflow-x: auto;
        }

        .content-nav li a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .content-nav li a:hover {
            background: rgba(255, 255, 255, 0.2);
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
            border: none;
        }

        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb-item a:hover {
            color: #224abe;
        }

        /* Table Styles */
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

            .mobile-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                display: none;
            }

            .mobile-overlay.show {
                display: block;
            }

            .content {
                margin-left: 0 !important;
            }

            .brand-logo {
                display: block;
            }

            .user-profile {
                margin-left: 0;
            }

            .sidebar .logo {
                justify-content: center;
            }

            .sidebar .menu-item span,
            .sidebar .has-submenu::after {
                display: block !important;
            }

            body.sidebar-open {
                overflow: hidden;
            }

            .footer {
                left: 0 !important;
            }

            .content-nav ul {
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 5px;
            }

            .content-nav ul::-webkit-scrollbar {
                height: 4px;
            }

            .content-nav ul::-webkit-scrollbar-thumb {
                background: rgba(255, 255, 255, 0.3);
                border-radius: 2px;
            }
        }

        .container-fluid {
            padding: 20px;
        }
        /* Page panel that wraps all tabs/content (keeps breadcrumb outside)
           Use the same container styling as Leave Approval page (white card look) */
        .page-panel {
            background: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin: 20px;
            padding: 15px 20px;
            border: 1px solid rgba(0,0,0,0.03);
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

        /* Orange Tab - Late Attendance */
        .nav-tabs .nav-link.tab-orange {
            color: #ff9800 !important;
        }
        .nav-tabs .nav-link.tab-orange:hover {
            background: linear-gradient(135deg, #ff9800, #f57c00) !important;
            border-color: #ff9800 !important;
            color: #fff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 152, 0, 0.3);
        }
        .nav-tabs .nav-link.tab-orange.active {
            background: linear-gradient(135deg, #ff9800, #f57c00) !important;
            color: #fff !important;
            border-color: #ff9800 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
        }

        .sidebar.collapsed+.content .loader-container {
            left: var(--sidebar-collapsed-width);
        }

        @media (max-width: 768px) {
            .loader-container {
                left: 0;
            }
        }

    
        .loader-container.hide {
            display: none;
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

        .card {
            border-radius: 15px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .btn-custom {
            border-radius: 30px;
        }

        .nav-tabs .nav-link.active {
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
        }

        /* Tab hover and color styles copied from other admin pages (room.php) */
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

        /* Purple Tab - Enable Attendance */
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

        /* Red Tab - Force Block */
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

        /* Hostel dashboard stat-box styles (inspired by Student dashboard) */
        .hostel-stat {
            position: relative;
            overflow: hidden;
            padding: 18px 16px;
            border-radius: 12px;
            color: white;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 200px; /* fixed stable height for all cards */
            max-height: 200px;
            min-height: 200px;
            box-sizing: border-box;
        }

        .hostel-stat h5 { margin-bottom: 6px; font-size: 1.05rem; font-weight:600; }
        .hostel-stat p { margin: 0; font-size: 0.95rem; }
        .hostel-stat .small-muted { color: rgba(255,255,255,0.9); font-size:0.85rem; }

        /* Icon inside hostel stat (matches Student stat-icon) */
        .hostel-stat .stat-icon {
            font-size: 2.2rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 6px rgba(0,0,0,0.18);
            opacity: 0.95;
        }

        /* Decorative overlay like Student page */
        .hostel-stat::before {
            content: '';
            position: absolute;
            top: -45%;
            right: -45%;
            width: 180%;
            height: 180%;
            background: rgba(255,255,255,0.08);
            transform: rotate(45deg);
            transition: all 0.5s ease;
        }

        .hostel-stat:hover::before { transform: rotate(45deg) translateY(-8%); }

        .hostel-purple { background: linear-gradient(135deg,#9c27b0 0%,#7b1fa2 100%); }
        .hostel-orange { background: linear-gradient(135deg,#ff9800 0%,#f57c00 100%); }
        .hostel-red { background: linear-gradient(135deg,#f44336 0%,#d32f2f 100%); }
        .hostel-gray { background: linear-gradient(135deg,#9e9e9e 0%,#616161 100%); }

        .hostel-stat:hover { transform: translateY(-5px); box-shadow: 0 12px 35px rgba(0,0,0,0.2); }

        /* Responsive: slightly smaller fixed height on small screens */
        @media (max-width: 768px) {
            .hostel-stat { height: 160px; max-height: 160px; min-height: 160px; }
        }

        /* Make regular cards lift on hover to match hostel-stat style */
        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.12);
        }

        /* Buttons: subtle lift and color transition */
        .btn, .btn-custom {
            transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
        }
        .btn:hover, .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(0,0,0,0.12);
            filter: brightness(1.02);
        }

        /* Table row hover: gentle highlight + subtle elevation on cells */
        .grad-table tbody tr:hover {
            background-color: rgba(33, 150, 243, 0.06);
        }
        .grad-table tbody tr:hover td {
            transition: box-shadow 0.18s ease, transform 0.18s ease;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
        }

   
        .grad-table {
            width: 100% !important;
            border-collapse: collapse !important;
            box-shadow: var(--card-shadow) !important;
            overflow: hidden !important;
        }

        .grad-table thead tr {
            background: linear-gradient(135deg, #4CAF50, #2196F3) !important;
            color: white !important;
        }

        .grad-table td,
        .grad-table th {
            border: 0.1px solid #dee2e6;
            padding: 8px !important;
        }

        .grad-table tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.03);
        }

        .grad-table tbody tr:nth-of-type(even) {
            background-color: white;
        }

        .grad-table tbody tr:hover {
            background-color: rgba(33, 150, 243, 0.08);
            transition: background-color 0.2s ease;
        }

        .unblock-btn {
            padding: 5px 10px;
            background-color: #FFD101;
            color: black;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .unblock-btn:hover {
            background-color: #e6b800;
        }

        .block-late-btn {
            padding: 5px 10px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .block-late-btn:hover {
            background-color: #c82333;
        }

        .tab-content {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            margin-top: 20px;
        }

        .late-badge {
            background-color: #ffc107;
            color: #000;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <?php include '../assets/sidebar.php'; ?>
    <div class="content">
        <?php include '../assets/topbar.php'; ?>

        <div class="breadcrumb-area custom-gradient">
            <nav aria-label="breadcrumb" class="d-flex justify-content-between align-items-center">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Attendance Operations</li>
                </ol>
            </nav>
        </div>

        <div class="container-fluid">
            <div class="custom-tabs">
                <ul class="nav nav-tabs" id="attendanceTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link tab-purple active" id="enable-attendance-tab" data-bs-toggle="tab" data-bs-target="#enableAttendance-content" type="button" role="tab" aria-controls="enableAttendance-content" aria-selected="true"><span>Enable Attendance Time</span></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link tab-red" id="force-block-tab" data-bs-toggle="tab" data-bs-target="#forceBlock-content" type="button" role="tab" aria-controls="forceBlock-content" aria-selected="false"><span>Force Block</span></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link tab-orange" id="late-attendance-tab" data-bs-toggle="tab" data-bs-target="#lateAttendance-content" type="button" role="tab" aria-controls="lateAttendance-content" aria-selected="false"><span>Late Attendance</span></button>
                    </li>
                </ul>

                <div class="tab-content mt-3">
                    <!-- Enable Attendance Time tab -->
                    <div class="tab-pane fade show active" id="enableAttendance-content" role="tabpanel" aria-labelledby="enable-attendance-tab">
                        <!-- Hostel quick-dashboard: show active time per important hostels -->
                        <div class="row mb-3" id="hostelQuickDashboard">
                                <?php
                                // Attempt to show three important hostels (fallback to first 3 if not found)
                                if (isset($conn)) { $conn->close(); }
                                include '../db.php';
                                $conn->query("SET SESSION query_cache_type = OFF");

                                $wanted = [ 'muthulakshmi', 'veda', 'octa' ];
                                // Build a safe IN clause by fetching all hostels and filtering in PHP (avoid complex prepared IN)
                                $hostelsRes = $conn->query("SELECT hostel_id, hostel_name FROM hostels ORDER BY hostel_name");
                                $found = [];
                                if ($hostelsRes) {
                                    while ($h = $hostelsRes->fetch_assoc()) {
                                        $lname = strtolower(trim($h['hostel_name']));
                                        if (in_array($lname, $wanted)) {
                                            $found[] = $h;
                                        }
                                    }
                                }
                                // If not enough found, pick first 3 hostels
                                if (count($found) < 3) {
                                    $found = [];
                                    if ($hostelsRes) {
                                        $hostelsRes->data_seek(0);
                                        $i = 0;
                                        while ($h = $hostelsRes->fetch_assoc()) {
                                            $found[] = $h;
                                            $i++; if ($i>=3) break;
                                        }
                                    }
                                }

                                foreach ($found as $h) {
                                    $hid = (int)$h['hostel_id'];
                                    $timeOut = null;
                                    // try hostel specific enabled record, only if the column exists
                                    $colResInner = $conn->query("SHOW COLUMNS FROM attendance_time_control LIKE 'hostel_id'");
                                    $hasHostelColInner = ($colResInner && $colResInner->num_rows > 0);
                                    if ($hasHostelColInner) {
                                        $stmt = $conn->prepare("SELECT * FROM attendance_time_control WHERE status='enabled' AND hostel_id = ? ORDER BY id DESC LIMIT 1");
                                        if ($stmt) {
                                            $stmt->bind_param('i', $hid);
                                            $stmt->execute();
                                            $r = $stmt->get_result();
                                            if ($r && $r->num_rows>0) $timeOut = $r->fetch_assoc();
                                            $stmt->close();
                                        }
                                    }
                                    // fallback to global - choose query depending on whether hostel_id column exists
                                    if ($timeOut === null) {
                                        if ($hasHostelColInner) {
                                            $stmt2 = $conn->prepare("SELECT * FROM attendance_time_control WHERE status='enabled' AND (hostel_id IS NULL OR hostel_id = '') ORDER BY id DESC LIMIT 1");
                                            if ($stmt2) {
                                                $stmt2->execute();
                                                $r2 = $stmt2->get_result();
                                                if ($r2 && $r2->num_rows>0) $timeOut = $r2->fetch_assoc();
                                                $stmt2->close();
                                            }
                                        } else {
                                            // simple global query when hostel_id column is not present
                                            $resG = $conn->query("SELECT * FROM attendance_time_control WHERE status='enabled' ORDER BY id DESC LIMIT 1");
                                            if ($resG && $resG->num_rows>0) {
                                                $timeOut = $resG->fetch_assoc();
                                            }
                                        }
                                    }

                                    $from = $to = $late = null;
                                    if ($timeOut) {
                                        $from = date('h:i A', strtotime($timeOut['from_time']));
                                        $to = date('h:i A', strtotime($timeOut['to_time']));
                                        $late = date('h:i A', strtotime($timeOut['late_entry_time']));
                                    }
                                    ?>
                                    <?php
                                        $lname = strtolower(trim($h['hostel_name']));
                                        $colorClass = 'hostel-gray';
                                        if (strpos($lname, 'muthulakshmi') !== false) { $colorClass = 'hostel-purple'; }
                                        elseif (strpos($lname, 'octa') !== false) { $colorClass = 'hostel-orange'; }
                                        elseif (strpos($lname, 'veda') !== false) { $colorClass = 'hostel-red'; }
                                    ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="hostel-stat <?php echo $colorClass; ?>">
                                            <div class="stat-icon">
                                                <i class="fas fa-building"></i>
                                            </div>
                                            <h5><?php echo htmlspecialchars($h['hostel_name']); ?></h5>
                                            <?php if ($from): ?>
                                                <p><strong>From:</strong> <?php echo $from; ?></p>
                                                <p><strong>To:</strong> <?php echo $to; ?></p>
                                                <p class="small-muted"><strong>Late:</strong> <?php echo $late; ?></p>
                                            <?php else: ?>
                                                <p class="small-muted">No active attendance time</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php } ?>
                        </div>

                        <div class="d-flex justify-content-end mb-3">
                                <label for="hostelFilterTime" class="form-label mb-0 me-2" style="font-weight:600">Hostel:</label>
                                <select id="hostelFilterTime" class="form-select w-auto d-inline-block">
                                </select>
                        </div>
                        <div id="activeTimeContainer" class="text-center mb-0">
                                <?php
                                // Server-side fallback render for initial load (global latest)
                                if (isset($conn)) { /* already connected */ }
                                $conn->query("SET SESSION query_cache_type = OFF");
                                $check = $conn->query("SELECT * FROM attendance_time_control WHERE status='enabled' ORDER BY id DESC LIMIT 1");
                                if ($check && $check->num_rows > 0) {
                                    $data = $check->fetch_assoc();
                                    echo "<div class='alert alert-success mb-3' role='alert'>
                                            <h5 class='alert-heading'><i class='fas fa-clock me-2'></i>Active Attendance Time</h5>
                                            <p class='mb-2'><strong>From Time:</strong> " . date('h:i A', strtotime($data['from_time'])) . "</p>
                                            <p class='mb-2'><strong>To Time:</strong> " . date('h:i A', strtotime($data['to_time'])) . "</p>
                                            <p class='mb-2'><strong>Late Entry Time:</strong> " . date('h:i A', strtotime($data['late_entry_time'])) . "</p>
                                            <hr>
                                            <p class='mb-0 text-muted'>Attendance is currently being tracked during this period.</p>
                                        </div>";
                                    echo "<button type='button' class='btn btn-danger' id='disableTimeBtn' data-time-id='{$data['id']}'>
                                            <i class='fas fa-times-circle me-1'></i>Disable Attendance Time
                                        </button>";
                                } else {
                                    echo "<div class='alert alert-info mb-3' role='alert'>
                                            <i class='fas fa-info-circle me-2'></i>
                                            No active attendance time. Click below to enable new timing.
                                        </div>";
                                    echo "<button class='btn btn-success btn-custom mt-2' data-bs-toggle='modal' data-bs-target='#enableModal'>
                                            <i class='bi bi-calendar-check'></i> Enable Attendance Time
                                        </button>";
                                }
                                ?>
                        </div>
                    </div>

                    <!-- Force Block tab -->
                    <div class="tab-pane fade" id="forceBlock-content" role="tabpanel" aria-labelledby="force-block-tab">
                        <div class="mb-3">
                            <div class="d-flex align-items-center gap-2 justify-content-end">
                                    <button type="button" class="btn btn-danger d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#blockStudentModal">
                                        <i class="fas fa-user-lock me-2"></i> Force Block Student
                                    </button>

                                    <button id="unblockAllBtn" type="button" class="btn btn-success d-flex align-items-center">
                                        <i class="fas fa-unlock me-1"></i> Unblock All
                                    </button>
                                </div>
                                <h4 class="mt-3">Blocked Students Management</h4>
                        </div>

                        <div class="mb-3">
                            <label for="hostelFilterBlocked" class="form-label me-2" style="font-weight:600">Hostel:</label>
                                <select id="hostelFilterBlocked" class="form-select w-auto d-inline-block">
                                </select>
                        </div>

                        <table class="grad-table" id="blocked-table">
                            <thead>
                                <tr>
                                    <th>Roll Number</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Batch</th>
                                    <th>Room</th>
                                    <th>Blocked At</th>
                                    <th>Reason</th>
                                    <th>Restriction</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="students-blocked-body">
                            </tbody>
                        </table>
                    </div>

                    <!-- Late Attendance tab -->
                    <div class="tab-pane fade" id="lateAttendance-content" role="tabpanel" aria-labelledby="late-attendance-tab">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4>Late Attendance Management</h4>
                            <button type="button" class="btn btn-primary d-flex align-items-center" id="refreshLateAttendance">
                                <i class="fas fa-sync-alt me-2"></i> Refresh
                            </button>
                        </div>

                        <div class="mb-3">
                            <label for="hostelFilterLate" class="form-label me-2" style="font-weight:600">Hostel:</label>
                            <select id="hostelFilterLate" class="form-select w-auto d-inline-block">
                            </select>
                        </div>

                        <table class="grad-table" id="late-attendance-table">
                            <thead>
                                <tr>
                                    <th>Roll Number</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Batch</th>
                                    <th>Room</th>
                                    <th>Attendance Date</th>
                                    <th>Entry Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="late-attendance-body">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <!-- Block Student Modal -->
    <div class="modal fade" id="blockStudentModal" tabindex="-1" aria-labelledby="blockStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="block-student-form">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="blockStudentModalLabel">
                            <i class="fas fa-user-lock me-2"></i>Force Block Student
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="block-student-message" class="mb-3"></div>

                        <div class="mb-3">
                            <label for="roll_number" class="form-label">Roll Number</label>
                            <input type="text" class="form-control" id="roll_number" name="roll_number" placeholder="Enter Roll Number" required>
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Enter reason for blocking" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Block Type</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="leave_checkbox" value="Leave">
                                <label class="form-check-label" for="leave_checkbox">Leave</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="outing_checkbox" value="Outing">
                                <label class="form-check-label" for="outing_checkbox">Outing</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-lock me-1"></i>Block Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enable Time Modal -->
   <div class="modal fade" id="enableModal" tabindex="-1" aria-labelledby="enableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="" id="enableAttendanceForm">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="enableModalLabel">Enable Attendance Time</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">From Time</label>
                        <input type="time" name="from_time" id="from_time" class="form-control" required>
                        <div class="invalid-feedback" id="from_time_error"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">To Time</label>
                        <input type="time" name="to_time" id="to_time" class="form-control" required>
                        <div class="invalid-feedback" id="to_time_error"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Late Entry Time</label>
                        <input type="time" name="late_entry_time" id="late_entry_time" class="form-control" required>
                        <div class="invalid-feedback" id="late_entry_time_error"></div>
                    </div>
                    <input type="hidden" name="hostel_id" id="enable_hostel_id" value="">
                </div>

                <div class="modal-footer">
                    <button type="submit" name="enableAttendance" class="btn btn-primary w-100">Save & Enable</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <?php include '../assets/footer.php'; ?>

    <script>
        $(document).ready(function() {
 $('#from_time').val('09:00');
    $('#to_time').val('17:00');
    $('#late_entry_time').val('09:30');

    function checkTimes() {
        let fromTime = $('#from_time').val();
        let toTime = $('#to_time').val();
        let lateTime = $('#late_entry_time').val();
        
        let allGood = true;

        $('#from_time, #to_time, #late_entry_time').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        let fromParts = fromTime.split(':');
        let toParts = toTime.split(':');
        let lateParts = lateTime.split(':');

        let fromTotal = parseInt(fromParts[0]) * 60 + parseInt(fromParts[1]);
        let toTotal = parseInt(toParts[0]) * 60 + parseInt(toParts[1]);
        let lateTotal = parseInt(lateParts[0]) * 60 + parseInt(lateParts[1]);

        if (toTotal <= fromTotal) {
            $('#to_time').addClass('is-invalid');
            $('#to_time_error').text('To Time must be after From Time');
            allGood = false;
        }

        if (lateTotal <= toTotal) {
            $('#late_entry_time').addClass('is-invalid');
            $('#late_entry_time_error').text('Late Time must be Greater than To Time');
            allGood = false;
        }

        return allGood;
    }

    $('#from_time, #to_time, #late_entry_time').on('change', function() {
        checkTimes();
    });

    $('#enableAttendanceForm').on('submit', function(e) {
        if (!checkTimes()) {
            e.preventDefault();
            Swal.fire('Error!', 'Please fix the time errors before saving', 'error');
        }
    });
    
    // Fetch and display active attendance time for selected hostel
    function fetchActiveAttendanceTime(hostelId) {
        $.ajax({
            url: '../api.php',
            method: 'POST',
            data: { action: 'get_attendance_time', hostel_id: hostelId || '' },
            dataType: 'json',
            success: function(res) {
                var container = $('#activeTimeContainer');
                if (res && res.success && res.data) {
                    var d = res.data;
                    var html = "<div class='alert alert-success mb-3' role='alert'>" +
                        "<h5 class='alert-heading'><i class='fas fa-clock me-2'></i>Active Attendance Time</h5>" +
                        "<p class='mb-2'><strong>From Time:</strong> " + d.from_time_display + "</p>" +
                        "<p class='mb-2'><strong>To Time:</strong> " + d.to_time_display + "</p>" +
                        "<p class='mb-2'><strong>Late Entry Time:</strong> " + d.late_entry_time_display + "</p>" +
                        "<hr><p class='mb-0 text-muted'>Attendance is currently being tracked during this period.</p></div>";
                    html += "<button type='button' class='btn btn-danger' id='disableTimeBtn' data-time-id='" + d.id + "'>" +
                        "<i class='fas fa-times-circle me-1'></i>Disable Attendance Time</button>";
                    container.html(html);
                } else {
                    var html = "<div class='alert alert-info mb-3' role='alert'>" +
                        "<i class='fas fa-info-circle me-2'></i>No active attendance time for selected hostel. Click below to enable new timing." +
                        "</div>";
                    html += "<button class='btn btn-success btn-custom mt-2' data-bs-toggle='modal' data-bs-target='#enableModal'>" +
                        "<i class='bi bi-calendar-check'></i> Enable Attendance Time</button>";
                    container.html(html);
                }
            },
            error: function() {
                console.warn('Failed to load active attendance time');
            }
        });
    }

    // When time-hostel filter changes, save selection and fetch active time
    $(document).on('change', '#hostelFilterTime', function() {
        var v = $(this).val();
        try { localStorage.setItem('hostelFilterTime', v); } catch(e) { }
        fetchActiveAttendanceTime(v);
    });

    // Initial fetch (for default/global)
    setTimeout(function() { fetchActiveAttendanceTime($('#hostelFilterTime').val()); }, 500);

    $('#from_time').on('change', function() {
        let fromTime = $(this).val();
        let toTime = $('#to_time').val();
        let lateTime = $('#late_entry_time').val();

        if (fromTime && toTime) {
            let fromParts = fromTime.split(':');
            let toParts = toTime.split(':');
            
            let fromTotal = parseInt(fromParts[0]) * 60 + parseInt(fromParts[1]);
            let toTotal = parseInt(toParts[0]) * 60 + parseInt(toParts[1]);
            
            if (toTotal <= fromTotal) {
                let newHour = (parseInt(fromParts[0]) + 1) % 24;
                let newTime = newHour.toString().padStart(2, '0') + ':' + fromParts[1];
                $('#to_time').val(newTime);
            }
        }

        if (fromTime && lateTime) {
            let fromParts = fromTime.split(':');
            let lateParts = lateTime.split(':');
            
            let fromTotal = parseInt(fromParts[0]) * 60 + parseInt(fromParts[1]);
            let lateTotal = parseInt(lateParts[0]) * 60 + parseInt(lateParts[1]);
            
            if (lateTotal <= fromTotal) {
                let newHour = (parseInt(fromParts[0]) + 1) % 24;
                let newTime = newHour.toString().padStart(2, '0') + ':' + '30';
                $('#late_entry_time').val(newTime);
            }
        }
        
        checkTimes();
    });

    // Populate hostel dropdowns and wire change handlers
    function populateHostelFilters() {
        $.ajax({
            url: '../api.php',
            method: 'POST',
            data: { action: 'attget_hostels' },
            dataType: 'json',
            success: function(res) {
                if (res && res.success && Array.isArray(res.data)) {
                    // Filter hostels based on admin role
                    // female_admin: Muthulakshmi only
                    // male_admin: Octa and Veda only
                    // admin (super): All three
                    <?php
                    $role = get_current_session_role();
                    $isFemaleAdmin = ($role === 'female_admin');
                    $isMaleAdmin = ($role === 'male_admin');
                    ?>
                    
                    var isAdmin = <?php echo $isFemaleAdmin ? 'true' : 'false'; ?>;
                    var isMaleAdmin = <?php echo $isMaleAdmin ? 'true' : 'false'; ?>;
                    
                    var filteredHostels = res.data.filter(function(h) {
                        var lname = (h.hostel_name || '').toLowerCase().trim();
                        
                        if (isAdmin) {
                            // Female admin: only Muthulakshmi
                            return lname.indexOf('muthulakshmi') !== -1;
                        } else if (isMaleAdmin) {
                            // Male admin: Octa and Veda
                            return lname.indexOf('octa') !== -1 || lname.indexOf('veda') !== -1;
                        } else {
                            // Super admin: all three
                            var wanted = ['muthulakshmi', 'veda', 'octa'];
                            return wanted.some(function(w) { return lname.indexOf(w) !== -1; });
                        }
                    });

                    // Build options without "All Hostels"
                    var opts = '';
                    filteredHostels.forEach(function(h) {
                        var id = h.hostel_id ?? '';
                        var name = h.hostel_name || '';
                        opts += '<option value="' + escapeHtml(id) + '">' + escapeHtml(name) + '</option>';
                    });
                    
                    // Only populate if we have hostels
                    if (opts) {
                        $('#hostelFilterTime').html(opts);
                        $('#hostelFilterBlocked').html(opts);
                        $('#hostelFilterLate').html(opts);
                    } else {
                        $('#hostelFilterTime').html('<option value="">No hostels available</option>');
                        $('#hostelFilterBlocked').html('<option value="">No hostels available</option>');
                        $('#hostelFilterLate').html('<option value="">No hostels available</option>');
                    }

                    // Restore previously selected hostel from localStorage (if any)
                    var saved = null;
                    try { saved = localStorage.getItem('hostelFilterTime'); } catch(e) { saved = null; }
                    if (saved !== null && saved !== undefined) {
                        // If saved value exists in options, select it
                        if ($('#hostelFilterTime option[value="' + saved + '"]').length > 0) {
                            $('#hostelFilterTime').val(saved);
                            $('#hostelFilterBlocked').val(saved);
                            $('#hostelFilterLate').val(saved);
                        } else {
                            // saved not available (maybe hostels changed) -> select first available
                            var firstVal = $('#hostelFilterTime option:first').val();
                            $('#hostelFilterTime').val(firstVal);
                            $('#hostelFilterBlocked').val(firstVal);
                            $('#hostelFilterLate').val(firstVal);
                        }
                    } else {
                        // If no saved value, select first hostel by default
                        var firstVal = $('#hostelFilterTime option:first').val();
                        $('#hostelFilterTime').val(firstVal);
                        $('#hostelFilterBlocked').val(firstVal);
                        $('#hostelFilterLate').val(firstVal);
                    }

                    // After populating and restoring selection, load lists
                    loadBlockedStudents();
                    loadLateAttendance();
                    fetchActiveAttendanceTime($('#hostelFilterTime').val());
                }
            },
            error: function() {
                console.warn('Failed to load hostels for filter');
            }
        });
    }

   
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"'`=\/]/g, function(s) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '/': '&#x2F;',
                '`': '&#x60;',
                '=': '&#x3D;'
            })[s];
        });
    }

    $(document).on('change', '#hostelFilterBlocked', function() {
        loadBlockedStudents();
    });
    $(document).on('change', '#hostelFilterLate', function() {
        loadLateAttendance();
    });

    // When enable modal is shown, set the hidden hostel_id to the currently selected hostel
    $(document).on('show.bs.modal', '#enableModal', function() {
        $('#enable_hostel_id').val($('#hostelFilterTime').val());
    });
         
            populateHostelFilters();

            $('#refreshLateAttendance').on('click', function() {
                loadLateAttendance();
            });

       
            $('#block-student-form').on('submit', function(e) {
                e.preventDefault();

                const rollNumber = $('#roll_number').val().trim();
                const reason = $('#reason').val().trim();

                if (!rollNumber || !reason) {
                    Swal.fire('Error!', 'Please fill all fields', 'error');
                    return;
                }

                const leaveChecked = $('#leave_checkbox').is(':checked');
                const outingChecked = $('#outing_checkbox').is(':checked');

                if (!leaveChecked && !outingChecked) {
                    Swal.fire('Error!', 'Please select at least one type: Leave or Outing', 'error');
                    return;
                }

                let type = '';
                if (leaveChecked && outingChecked) {
                    type = 'Both';
                } else if (leaveChecked) {
                    type = 'Leave';
                } else if (outingChecked) {
                    type = 'Outing';
                }

                $.ajax({
                    url: '../api.php',
                    method: 'POST',
                    data: {
                        action: 'block_student',
                        roll_number: rollNumber,
                        reason: reason,
                        type: type
                    },
                    dataType: 'json',
                    success: function(response) {
                        const modalEl = document.getElementById('blockStudentModal');
                        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Student blocked successfully!',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                $('#block-student-form')[0].reset();
                                modal.hide();
                                loadBlockedStudents();
                            });
                        } else {
                            Swal.fire('Error!', response.message || 'Failed to block student', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'AJAX error while blocking student', 'error');
                    }
                });
            });

                // Unblock All button handler (top-level)
                $(document).on('click', '#unblockAllBtn', function() {
                    Swal.fire({
                        title: 'Unblock All Blocked Students?',
                        text: 'This will unblock all currently blocked students. Are you sure?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, Unblock All',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const btn = $('#unblockAllBtn');
                            btn.prop('disabled', true);
                            btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Unblocking...');

                            $.ajax({
                                url: '../api.php',
                                method: 'POST',
                                data: {
                                    action: 'unblock_all',
                                    hostel_filter: $('#hostelFilterBlocked').val() || ''
                                },
                                dataType: 'json',
                                success: function(res) {
                                    if (res && res.success) {
                                        Swal.fire({
                                            title: 'Unblocked',
                                            text: (res.affected ? res.affected + ' students unblocked.' : 'All blocked students have been unblocked.'),
                                            icon: 'success',
                                            confirmButtonText: 'OK'
                                        }).then(() => {
                                            loadBlockedStudents();
                                            btn.prop('disabled', false);
                                            btn.html('<i class="fas fa-unlock-open me-2"></i> Unblock All');
                                        });
                                    } else {
                                        Swal.fire('Error!', res && res.message ? res.message : 'Failed to unblock all', 'error');
                                        btn.prop('disabled', false);
                                        btn.html('<i class="fas fa-unlock-open me-2"></i> Unblock All');
                                    }
                                },
                                error: function() {
                                    Swal.fire('Error!', 'AJAX error while unblocking all', 'error');
                                    btn.prop('disabled', false);
                                    btn.html('<i class="fas fa-unlock-open me-2"></i> Unblock All');
                                }
                            });
                        }
                    });
                });

            $(document).on('click', '.unblock-btn', function() {
                const blockedId = $(this).data('id');
                const button = $(this);

                Swal.fire({
                    title: 'Unblock Student?',
                    text: 'Are you sure you want to unblock this student?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, unblock!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '../api.php',
                            method: 'POST',
                            data: {
                                action: 'unblock_student',
                                blocked_id: blockedId
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Student unblocked successfully!',
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        loadBlockedStudents();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Something went wrong!',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Something went wrong!',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    }
                });
            });


$(document).on('click', '.block-late-btn', function() {
    const rollNumber = $(this).data('roll-number');
    const studentName = $(this).data('student-name');
    const button = $(this);
    const row = button.closest('tr');
    
    Swal.fire({
        title: 'Block Student?',
        text: `Do you want to block ${studentName} (${rollNumber}) for repeated late attendance?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Block Student',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {

            button.html('<i class="fas fa-spinner fa-spin me-1"></i> Blocking...');
            button.prop('disabled', true);
            button.removeClass('block-late-btn').addClass('btn btn-secondary btn-sm');
            
            $.ajax({
                url: '../api.php',
                method: 'POST',
                data: {
                    action: 'block_student',
                    roll_number: rollNumber,
                    reason: 'Repeated late attendance - automatic block from late attendance system',
                    type: 'Both'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
            
                        button.html('<i class="fas fa-ban me-1"></i> Already Blocked');
                        button.prop('disabled', true);
                        button.removeClass('btn btn-secondary btn-sm block-late-btn').addClass('btn btn-danger btn-sm');

                        row.css('background-color', '#f8f9fa');
                        setTimeout(() => {
                            row.css('background-color', '');
                        }, 1000);
 
                        loadBlockedStudents();
                        
                        Swal.fire({
                            title: 'Success!',
                            text: 'Student blocked successfully!',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        Swal.fire('Error!', response.message || 'Failed to block student', 'error');
        
                        button.html('<i class="fas fa-ban me-1"></i> Block');
                        button.prop('disabled', false);
                        button.removeClass('btn-secondary').addClass('block-late-btn');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'AJAX error while blocking student', 'error');
     
                    button.html('<i class="fas fa-ban me-1"></i> Block');
                    button.prop('disabled', false);
                    button.removeClass('btn-secondary').addClass('block-late-btn');
                }
            });

            
        }
    });
});
            function loadBlockedStudents() {
                $.ajax({
                    url: '../api.php',
                    method: 'POST',
                    data: {
                        action: 'load_blocked',
                        hostel_filter: $('#hostelFilterBlocked').val() || ''
                    },
                    dataType: 'json',
                    success: function(response) {
                        const tbody = $('#students-blocked-body');
                        tbody.empty();

                        if ($.fn.DataTable.isDataTable('#blocked-table')) {
                            $('#blocked-table').DataTable().clear().destroy();
                        }

                        if (response.success && response.data.length > 0) {
                            response.data.forEach(student => {
                                const row = `
                                    <tr>
                                        <td>${student.roll_number}</td>
                                        <td>${student.name}</td>
                                        <td>${student.department}</td>
                                        <td>${student.academic_batch}</td>
                                        <td>${student.room_number ?? '-'}</td>
                                        <td>${student.blocked_at}</td>
                                        <td>${student.reason}</td>
                                        <td>${student.type}</td>
                                        <td>
                                            <button class="unblock-btn" data-id="${student.blocked_id}">
                                                <i class="fas fa-unlock me-1"></i> Unblock
                                            </button>
                                        </td>
                                    </tr>`;
                                tbody.append(row);
                            });

                            $('#blocked-table').DataTable({
                                pageLength: 10,
                                lengthMenu: [5, 10, 20, 50],
                                order: []
                            });
                        } else {
                            tbody.append('<tr><td colspan="9" class="text-center text-muted">No Blocked Students</td></tr>');
                        }
                    }
                });
            }

function loadLateAttendance() {
    $.ajax({
        url: '../api.php',
        method: 'POST',
        data: {
            action: 'load_late_attendance',
            hostel_filter: $('#hostelFilterLate').val() || ''
        },
        dataType: 'json',
        success: function(response) {
            const tbody = $('#late-attendance-body');
            tbody.empty();

            if ($.fn.DataTable.isDataTable('#late-attendance-table')) {
                $('#late-attendance-table').DataTable().clear().destroy();
            }

            if (response.success && response.data.length > 0) {

                $.ajax({
                    url: '../api.php',
                    method: 'POST',
                    data: {
                        action: 'load_blocked',
                        hostel_filter: $('#hostelFilterLate').val() || ''
                    },
                    dataType: 'json',
                    success: function(blockedResponse) {
                        const blockedStudents = blockedResponse.success ? blockedResponse.data : [];
                        
                        response.data.forEach(record => {
                            const statusBadge = '<span class="late-badge">Late</span>';
                            
                            const isBlocked = blockedStudents.some(blocked => 
                                blocked.roll_number === record.roll_number
                            );
                            let actions = '';
                            if (isBlocked) {
                                actions = `<button class="btn btn-danger btn-sm" disabled>
                                        <i class="fas fa-ban me-1"></i> Already Blocked
                                    </button>`;
                            } else {
                                actions = `<button class="block-late-btn" data-roll-number="${record.roll_number}" data-student-name="${record.name}">
                                        <i class="fas fa-ban me-1"></i> Block
                                    </button>`;
                            }
                            const row = `
                                <tr>
                                    <td>${record.roll_number}</td>
                                    <td>${record.name}</td>
                                    <td>${record.department}</td>
                                    <td>${record.academic_batch}</td>
                                    <td>${record.room_number ?? '-'}</td>
                                    <td>${record.attendance_date}</td>
                                    <td>${record.entry_time}</td>
                                    <td>${statusBadge}</td>
                                    <td>${actions}</td>
                                </tr>`;
                            tbody.append(row);
                        });

                        $('#late-attendance-table').DataTable({
                            pageLength: 10,
                            lengthMenu: [5, 10, 20, 50],
                            order: [[5, 'desc']]
                        });
                    },
                    error: function() {
        
                        response.data.forEach(record => {
                            const statusBadge = '<span class="late-badge">Late</span>';
                            
                            const actions = `<button class="block-late-btn" data-roll-number="${record.roll_number}" data-student-name="${record.name}">
                                    <i class="fas fa-ban me-1"></i> Block
                                </button>`;

                            const row = `
                                <tr>
                                    <td>${record.roll_number}</td>
                                    <td>${record.name}</td>
                                    <td>${record.department}</td>
                                    <td>${record.academic_batch}</td>
                                    <td>${record.room_number ?? '-'}</td>
                                    <td>${record.attendance_date}</td>
                                    <td>${record.entry_time}</td>
                                    <td>${statusBadge}</td>
                                    <td>${actions}</td>
                                </tr>`;
                            tbody.append(row);
                        });

                        $('#late-attendance-table').DataTable({
                            pageLength: 10,
                            lengthMenu: [5, 10, 20, 50],
                            order: [[5, 'desc']]
                        });
                    }
                });
            } else {
                tbody.append('<tr><td colspan="9" class="text-center text-muted">No Late Attendance Records Found</td></tr>');
            }
        }
    });
}
        
            $(document).on('click', '#disableTimeBtn', function() {
                const timeId = $(this).data('time-id');
                const button = $(this);
                
                // Disable button immediately to prevent double clicks
                button.prop('disabled', true);
                button.html('<i class="fas fa-spinner fa-spin me-1"></i>Disabling...');

                Swal.fire({
                    title: 'Disable Attendance Time?',
                    text: 'This will stop tracking attendance for this time period. Are you sure?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Disable It',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'attendance_operation.php',
                            type: 'POST',
                            data: {
                                time_id: timeId,
                                action: 'disable_time',
                                _t: Date.now() // Prevent caching
                            },
                            dataType: 'json',
                            timeout: 10000, // 10 second timeout
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Attendance Time has been disabled successfully.',
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        // Small delay to ensure DB commit
                                        setTimeout(() => {
                                            window.location.href = window.location.pathname + '?t=' + Date.now();
                                        }, 500);
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: response.message || 'Failed to disable Attendance Time.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                    // Re-enable button on error
                                    button.prop('disabled', false);
                                    button.html('<i class="fas fa-times-circle me-1"></i>Disable Attendance Time');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX Error:', status, error);
                                console.error('Response:', xhr.responseText);
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'An error occurred while processing the request: ' + error,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                                // Re-enable button on error
                                button.prop('disabled', false);
                                button.html('<i class="fas fa-times-circle me-1"></i>Disable Attendance Time');
                            }
                        });
                    } else {
                        // Re-enable button if cancelled
                        button.prop('disabled', false);
                        button.html('<i class="fas fa-times-circle me-1"></i>Disable Attendance Time');
                    }
                });
            });

    
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelector('input[name="from_time"]').value = '19:00';
                document.querySelector('input[name="to_time"]').value = '20:00';
                document.querySelector('input[name="late_entry_time"]').value = '19:45';
            });
        });
    </script>

</body>

</html>