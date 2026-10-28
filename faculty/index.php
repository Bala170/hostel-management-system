<?php session_start(); ?>
<?php
include '../db.php';
date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default department name if not defined
$department_name = isset($department_name) ? $department_name : 'All Departments';

// Removed session - no session needed for this page
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <?php include '../db.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Management - Faculty Leave Approval | <?php echo htmlspecialchars($department_name); ?></title>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Custom CSS Variables */
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

        /* Gradient Card Styles */
        .gradient-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            min-height: 150px;
            max-height: 180px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .gradient-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        /* Decorative tilted corners */
        .gradient-card::before,
        .gradient-card::after {
            content: "";
            position: absolute;
            pointer-events: none;
            border-radius: 5px;
            transform: rotate(45deg);
            transition: transform 0.35s ease, opacity 0.35s ease;
            z-index: 0;
        }

        .gradient-card::before {
            top: -95px;
            right: -95px;
            width: 140px;
            height: 140px;
            background: rgba(0, 0, 0, 0.06);
        }

        .gradient-card::after {
            bottom: -105px;
            left: -105px;
            width: 200px;
            height: 140px;
            background: rgba(0, 0, 0, 0.06);
        }

        .gradient-card:hover::before {
            transform: translate(-4px, 4px) rotate(45deg) scale(1.03);
            opacity: 0.14;
        }

        .gradient-card:hover::after {
            transform: translate(4px, -4px) rotate(45deg) scale(1.03);
            opacity: 0.22;
        }

        /* Gradient Backgrounds */
        .gradient-primary {
            background: linear-gradient(135deg, #566eee 0%, #4e28b0 100%);
        }

        .gradient-success {
            background: linear-gradient(135deg, #42cbbd 0%, #21d9ab 100%);
        }

        .gradient-info {
            background: linear-gradient(135deg, #ffa41a 0%, #ff8a1a 100%);
        }

        .gradient-warning {
            background: linear-gradient(135deg, #f45a67ff 0%, #e84956 100%);
        }

        .gradient-danger {
            background: linear-gradient(135deg, #96a1b4 0%, #6e788c 100%);
        }

        .gradient-secondary {
            background: linear-gradient(135deg, #5ecdf2 0%, #539bfc 100%);
        }

        /* Card Content */
        .gradient-card .card-body {
            padding: 1.25rem 1rem;
            position: relative;
            z-index: 1;
        }

        /* Icon Container */
        .gradient-card .icon-container {
            font-size: 40px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            opacity: 0.9;
        }

        .gradient-card:hover .icon-container {
            transform: scale(1.2);
        }

        /* Card Title */
        .gradient-card .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        /* Card Value */
        .gradient-card .card-value {
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .pulse-value {
            animation: pulse 2s infinite;
        }

        .card-clickable {
            cursor: pointer;
        }

        /* Other styles... */
        .content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .breadcrumb-area {
            background-image: linear-gradient(to top, #fff1eb 0%, #ace0f9 100%);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin: 20px;
            padding: 15px 20px;
        }

        /* Use the custom gradient background for the breadcrumb as a highlight */
        .breadcrumb-area.custom-gradient {
            background-image: linear-gradient(to top, #fff1eb 0%, #ace0f9 100%);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
        }

        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb-item a:hover {
            color: #224abe;
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

        /* Loader */
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
            display: none;
            /* Hide by default, show on load */
        }

        .loader-container.show {
            display: flex;
        }

        .loader-container.hide {
            display: none;
        }


        /* Custom style for the clickable name button */
        .btn-name-style {
            color: #333;
            /* Darker text color */
            text-decoration: none;
            /* No underline */
            font-weight: 500;
            text-align: left !important;
            background: none;
            border: none;
            padding: 0;
            margin: 0;
        }

        .btn-name-style:hover {
            color: var(--primary-color);
            /* Subtle hover color change */
            text-decoration: underline;
            /* Add underline on hover for clickability */
            background: none;
        }

        /* Navigation Tab Styles */
        .custom-tabs .nav-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 20px;
        }

        .custom-tabs .nav-link {
            font-weight: 500;
            font-size: 1.1rem;
            padding: 12px 24px;
            border: none;
            border-radius: 8px 8px 0 0;
            background: #f8f9fc;
            transition: all 0.3s ease;
            position: relative;
            margin-right: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        /* Pending Tab - Orange */
        .custom-tabs .nav-link#family-main-tab {
            color: #ff8a1a;
        }

        .custom-tabs .nav-link#family-main-tab:hover,
        .custom-tabs .nav-link#family-main-tab.active {
            color: #fff;
            background: linear-gradient(135deg, #ffa41a 0%, #ff8a1a 100%);
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(255, 164, 26, 0.4);
        }

        .custom-tabs .nav-link#family-main-tab.active {
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(255, 164, 26, 0.5);
        }

        /* Processed Tab - Teal/Green */
        .custom-tabs .nav-link#processed-main-tab {
            color: #21d9ab;
        }

        .custom-tabs .nav-link#processed-main-tab:hover,
        .custom-tabs .nav-link#processed-main-tab.active {
            color: #fff;
            background: linear-gradient(135deg, #42cbbd 0%, #21d9ab 100%);
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(66, 203, 189, 0.4);
        }

        .custom-tabs .nav-link#processed-main-tab.active {
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(66, 203, 189, 0.5);
        }

        .custom-tabs .nav-link::before {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 4px;
            background: transparent;
            transition: all 0.3s ease;
        }

        .custom-tabs .nav-link#family-main-tab.active::before {
            background: linear-gradient(90deg, #ffa41a, #ff8a1a);
        }

        .custom-tabs .nav-link#processed-main-tab.active::before {
            background: linear-gradient(90deg, #42cbbd, #21d9ab);
        }
    </style>

    <!-- Student Leave Breakdown Modal Styling (MATCH ADMIN EXACTLY) -->
    <style>
        #studentLeaveBreakdownModal .modal-content {
            border-radius: 12px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            background: linear-gradient(145deg, #f0f0f0, #ffffff);
            border: none;
            overflow: hidden;
        }

        #studentLeaveBreakdownModal .modal-header {
            background: linear-gradient(to right, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 15px 20px;
            border-bottom: none;
        }

        #studentLeaveBreakdownModal .modal-header .modal-title {
            font-weight: 200;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        #studentLeaveBreakdownModal .modal-header .btn-close {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            opacity: 1;
            width: 30px;
            height: 30px;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e");
            background-size: 30%;
            background-position: center;
            background-repeat: no-repeat;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        #studentLeaveBreakdownModal .modal-header .btn-close:hover {
            background-color: rgba(255, 255, 255, 0.4);
            transform: scale(1.1);
        }

        #studentLeaveBreakdownModal .modal-body {
            padding: 20px;
            background: #f8f9fa;
        }

        #studentLeaveBreakdownModal .card {
            border-radius: 5px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            overflow: hidden;
        }

        #studentLeaveBreakdownModal .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(31, 38, 135, 0.2);
        }

        #studentLeaveBreakdownModal .card-header {
            background: linear-gradient(to left, #06b6d4, #0893b3);
            border-bottom: 2px solid rgba(37, 117, 252, 0.2);
            font-weight: 600;
            color: #ffffffff;
        }

        #studentLeaveBreakdownModal .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }

        #studentLeaveBreakdownModal .table-responsive::-webkit-scrollbar {
            width: 8px;
        }

        #studentLeaveBreakdownModal .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        #studentLeaveBreakdownModal .badge {
            font-size: 0.9em;
            padding: 5px 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            font-weight: normal;
        }

        /* Student Modal Animation */
        #studentLeaveBreakdownModal.show .modal-dialog {
            animation: modalEnter 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes modalEnter {
            from {
                opacity: 0;
                transform: scale(0.8) translateY(-50px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Gradient Card Styles for Student Modal */
        #studentLeaveBreakdownModal .gradient-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            min-height: 150px;
            max-height: 180px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        #studentLeaveBreakdownModal .gradient-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        /* Decorative tilted corners */
        #studentLeaveBreakdownModal .gradient-card::before,
        #studentLeaveBreakdownModal .gradient-card::after {
            content: "";
            position: absolute;
            pointer-events: none;
            border-radius: 5px;
            transform: rotate(45deg);
            transition: transform 0.35s ease, opacity 0.35s ease;
            z-index: 0;
        }

        #studentLeaveBreakdownModal .gradient-card::before {
            top: -95px;
            right: -95px;
            width: 140px;
            height: 140px;
            background: rgba(0, 0, 0, 0.06);
        }

        #studentLeaveBreakdownModal .gradient-card::after {
            bottom: -105px;
            left: -105px;
            width: 200px;
            height: 140px;
            background: rgba(0, 0, 0, 0.06);
        }

        #studentLeaveBreakdownModal .gradient-card:hover::before {
            transform: translate(-4px, 4px) rotate(45deg) scale(1.03);
            opacity: 0.14;
        }

        #studentLeaveBreakdownModal .gradient-card:hover::after {
            transform: translate(4px, -4px) rotate(45deg) scale(1.03);
            opacity: 0.22;
        }

        /* Card Content */
        #studentLeaveBreakdownModal .gradient-card .card-body {
            padding: 1.25rem 1rem;
            position: relative;
            z-index: 1;
        }

        /* Icon Container */
        #studentLeaveBreakdownModal .gradient-card .icon-container {
            font-size: 40px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            opacity: 0.9;
        }

        #studentLeaveBreakdownModal .gradient-card:hover .icon-container {
            transform: scale(1.2);
        }

        /* Card Title */
        #studentLeaveBreakdownModal .gradient-card .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        /* Card Value */
        #studentLeaveBreakdownModal .gradient-card .card-value {
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }
    </style>

    <!-- Custom Modal Styles -->
    <style>
        /* Ensure student history modal is properly centered and sized */
        #studentHistoryModal .modal-dialog {
            max-width: 90%;
            margin: 1.75rem auto;
        }

        #studentHistoryModal .modal-content {
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        #studentHistoryModal .modal-header {
            border-top-left-radius: calc(0.5rem - 1px);
            border-top-right-radius: calc(0.5rem - 1px);
        }

        /* Ensure DataTables in modal are properly sized */
        #studentHistoryModal .table-responsive {
            overflow-x: auto;
        }

        #studentHistoryModal .dataTables_wrapper {
            overflow: hidden;
        }

        /* Isolate student history tables from main page tables */
        #studentHistoryModal .student-history-table {
            width: 100% !important;
        }

        #studentHistoryModal .student-history-table.dataTable {
            margin: 0 !important;
        }

        /* Gradient Card Styles for student history modal */
        .gradient-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            min-height: 150px;
            max-height: 180px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            cursor: pointer;
        }

        .gradient-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        /* Decorative tilted corners */
        .gradient-card::before,
        .gradient-card::after {
            content: "";
            position: absolute;
            pointer-events: none;
            border-radius: 5px;
            transform: rotate(45deg);
            transition: transform 0.35s ease, opacity 0.35s ease;
            z-index: 0;
        }

        /* Dark top-right corner */
        .gradient-card::before {
            top: -95px;
            right: -95px;
            width: 140px;
            height: 140px;
            background: rgba(0, 0, 0, 0.06);
        }

        /* Soft bottom-left sweep */
        .gradient-card::after {
            bottom: -105px;
            left: -105px;
            width: 200px;
            height: 140px;
            background: rgba(0, 0, 0, 0.06);
        }

        .gradient-card:hover::before {
            transform: translate(-4px, 4px) rotate(45deg) scale(1.03);
            opacity: 0.14;
        }

        .gradient-card:hover::after {
            transform: translate(4px, -4px) rotate(45deg) scale(1.03);
            opacity: 0.22;
        }

        /* Gradient Backgrounds */
        .gradient-primary {
            background: linear-gradient(135deg, #566eee 0%, #4e28b0 100%);
        }

        .gradient-success {
            background: linear-gradient(135deg, #1cc88a 0%, #0f9d58 100%);
        }

        .gradient-danger {
            background: linear-gradient(135deg, #e74a3b 0%, #c0392b 100%);
        }

        .gradient-warning {
            background: linear-gradient(135deg, #f6c23e 0%, #ff9800 100%);
        }

        .gradient-info {
            background: linear-gradient(135deg, #36b9cc 0%, #258cd1 100%);
        }

        /* Card content styling */
        .card-body.text-center {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            padding: 1.25rem;
            position: relative;
            z-index: 1;
        }

        .icon-container {
            font-size: 40px;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            text-align: center;
        }

        .card-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        /* Pulse Animation */
        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        .pulse-value {
            animation: pulse 2s infinite;
        }

        /* Click behavior hint */
        .card-clickable {
            cursor: pointer;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .gradient-card .card-value {
                font-size: 1.5rem;
            }

            .gradient-card .card-title {
                font-size: 1rem;
            }

            .gradient-card .icon-container {
                font-size: 30px;
            }

            .gradient-card {
                min-height: 130px;
                max-height: 160px;
            }
        }

        /* Ensure student history DataTables don't inherit main page styles */
        #studentHistoryModal .dataTables_wrapper {
            font-family: inherit !important;
        }

        #studentHistoryModal table.dataTable thead th {
            border-bottom: 2px solid #dee2e6 !important;
        }

        /* Force isolation of student history tables */
        #studentHistoryModal #processed-history-dt-<?php echo isset($_GET['reg_no']) ? $_GET['reg_no'] : 'dynamic'; ?>,
        #studentHistoryModal #pending-history-dt-<?php echo isset($_GET['reg_no']) ? $_GET['reg_no'] : 'dynamic'; ?> {
            table-layout: fixed !important;
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
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Leave Approval (Faculty HOD -
                        <?php echo htmlspecialchars($department_name); ?>)</li>
                </ol>
            </nav>
        </div>

        <div class="container-fluid">
            <div class="custom-tabs">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="family-main-tab" data-bs-toggle="tab" href="#pending-content"
                            role="tab">Pending</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="processed-main-tab" data-bs-toggle="tab" href="#processed-content"
                            role="tab">Processed</a>
                    </li>
                </ul>
                <div class="tab-content mt-3">
                    <!-- Pending Tab Content -->
                    <div class="tab-pane fade show active" id="pending-content" role="tabpanel">
                        <div id="leaveStatsCards">
                            <?php
                            // Get counts for each leave type (pending status only)
                            $sql = "SELECT lt.Leave_Type_Name, lt.LeaveType_ID, COUNT(la.Leave_ID) as count 
                                    FROM leave_types lt
                                    LEFT JOIN leave_applications la ON lt.LeaveType_ID = la.LeaveType_ID 
                                        AND la.Status IN ('Pending', 'Forwarded to Admin')
                                    WHERE lt.LeaveType_ID <> 1
                                    GROUP BY lt.LeaveType_ID, lt.Leave_Type_Name
                                    ORDER BY lt.LeaveType_ID";

                            $result = mysqli_query($conn, $sql);

                            // Get total pending count
                            $totalSql = "SELECT COUNT(*) as total FROM leave_applications 
                                         WHERE Status IN ('Pending', 'Forwarded to Admin') 
                                         AND LeaveType_ID <> 1";
                            $totalResult = mysqli_query($conn, $totalSql);
                            $totalRow = mysqli_fetch_assoc($totalResult);
                            $totalCount = $totalRow['total'];

                            // Define colors for each card
                            $colors = [
                                'primary' => ['bg' => '#4e73df', 'icon' => '#2e59d9'],
                                'success' => ['bg' => '#1cc88a', 'icon' => '#17a673'],
                                'info' => ['bg' => '#36b9cc', 'icon' => '#2c9faf'],
                                'warning' => ['bg' => '#faa319', 'icon' => '#ff891a'],
                                'danger' => ['bg' => '#e94b58', 'icon' => '#de1f40'],
                                'secondary' => ['bg' => '#e94b58', 'icon' => '#de1f40']
                            ];

                            $colorKeys = array_keys($colors);
                            $colorIndex = 0;
                            ?>
                            <div class="row mb-4">
                                <!-- Total Pending Card -->
                                <div class="col-xl col-lg-3 col-md-6 mb-4">
                                    <div class="gradient-card gradient-primary">
                                        <div class="card-body text-center">
                                            <div class="icon-container">
                                                <i class="fas fa-clipboard-list text-white"></i>
                                            </div>
                                            <h4 class="card-title text-white">Total Pending</h4>
                                            <h2 class="card-value text-white font-weight-bold pulse-value">
                                                <?php echo $totalCount; ?></h2>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                // Define gradient classes for different leave types
                                $gradients = ['success', 'info', 'warning', 'danger', 'secondary'];
                                $gradientIndex = 0;

                                while ($row = mysqli_fetch_assoc($result)) {
                                    $gradientClass = $gradients[$gradientIndex % count($gradients)];
                                    $gradientIndex++;

                                    // Define icons for different leave types
                                    $icons = [
                                        'Medical Leave' => 'fa-user-doctor',
                                        'Emergency Leave' => 'fa-triangle-exclamation',
                                        'Leave' => 'fa-house',
                                        'On Duty' => 'fa-solid fa-book',
                                        'Outing' => 'fa-solid fa-suitcase'
                                    ];

                                    $icon = isset($icons[$row['Leave_Type_Name']]) ? $icons[$row['Leave_Type_Name']] : 'fa-file-alt';
                                    ?>

                                    <div class="col-xl col-lg-3 col-md-6 mb-4">
                                        <div class="gradient-card gradient-<?php echo $gradientClass; ?>">
                                            <div class="card-body text-center">
                                                <div class="icon-container">
                                                    <i class="fas <?php echo $icon; ?> text-white"></i>
                                                </div>
                                                <h4 class="card-title text-white">
                                                    <?php echo htmlspecialchars($row['Leave_Type_Name']); ?></h4>
                                                <h2 class="card-value text-white font-weight-bold pulse-value">
                                                    <?php echo $row['count']; ?></h2>
                                            </div>
                                        </div>
                                    </div>

                                <?php } ?>
                            </div>
                        </div>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold flex-grow-1 text-center">Pending Leave Applications
                                    (Faculty)</h6>
                                <div class="btn-group" role="group">
                                    <a href="export.php?action=faculty_pending_pdf" target="_blank"
                                        class="btn btn-danger btn-sm" title="Export to PDF">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>
                                    <a href="export.php?action=faculty_pending_excel" class="btn btn-success btn-sm"
                                        title="Export to Excel">
                                        <i class="fas fa-file-excel"></i> Excel
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" id="pendingTable">
                                    <?php
                                    $result = false;
                                    ?>
                                    <table class="table table-bordered" id="ivr-pending-leave-table" width="100%"
                                        cellspacing="0">
                                        <colgroup>
                                            <col style="width:2%;"> <!-- S.No -->
                                            <col style="width:8%;"> <!-- Reg No -->
                                            <col style="width:12%;"> <!-- Name -->
                                            <col style="width:9%;"> <!-- Leave Type -->
                                            <col style="width:13%;"> <!-- Applied Date -->
                                            <col style="width:12%;"> <!-- From -->
                                            <col style="width:12%;"> <!-- To -->
                                            <col style="width:10%;"> <!-- Reason -->
                                            <col style="width:8%;"> <!-- Proof -->
                                            <col style="width:13%;"> <!-- Status -->
                                            <col style="width:3%;"> <!-- Action -->
                                        </colgroup>
                                        <thead class="gradient-header">
                                            <tr>
                                                <th>S.No</th>
                                                <th>Reg No </th>
                                                <th>Name</th>
                                                <th>Leave Type</th>
                                                <th>Applied Date</th>
                                                <th>From</th>
                                                <th>To</th>
                                                <th>Reason</th>
                                                <th>Proof</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT la.*, s.name AS student_name, lt.Leave_Type_Name 
                                            FROM leave_applications la
                                            JOIN students s ON la.Reg_No = s.roll_number
                                            JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
                                            WHERE la.Status IN ('Pending')
                                            AND la.LeaveType_ID <> 1
                                            ORDER BY la.Applied_Date DESC";

                                            $result = mysqli_query($conn, $sql);
                                            $sno = 1;
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                echo "<tr>";
                                                echo "<td>" . $sno++ . "</td>";
                                                echo "<td>" . $row['Reg_No'] . "</td>";
                                                echo "<td>" . $row['student_name'] . "</td>";
                                                echo "<td>" . $row['Leave_Type_Name'] . "</td>";

                                                $appliedDate = date('d-m-Y h:i A', strtotime($row['Applied_Date']));
                                                $fromDate = date('d-m-Y h:i A', strtotime($row['From_Date']));
                                                $toDate = date('d-m-Y h:i A', strtotime($row['To_Date']));

                                                echo "<td>" . $appliedDate . "</td>";
                                                echo "<td>" . $fromDate . "</td>";
                                                echo "<td>" . $toDate . "</td>";
                                                echo "<td>" . $row['Reason'] . "</td>";
                                                if (!empty($row['Proof'])) {
                                                    echo "<td class='text-center align-middle'>
                                                        <button type='button' class='btn btn-info btn-sm view-proof' 
                                                            data-proof='" . $row['Proof'] . "' 
                                                            data-bs-toggle='modal' 
                                                            data-bs-target='#viewProofModal'>
                                                            <i class='fa-solid fa-eye'></i> View Proof
                                                        </button>
                                                    </td>";
                                                } else {
                                                    echo "<td class='text-center align-middle text-muted'>No Proof Uploaded </td>";
                                                }

                                                echo "<td class='text-center align-middle'>
                                                    <button class='btn btn-success btn-sm approve-leave-btn' data-id='" . $row['Leave_ID'] . "' >
                                                        <i class='fa-solid fa-check'></i> 
                                                    </button>
                                                    <button type='button' class='btn btn-danger btn-sm reject-leave-btn' data-bs-toggle='modal' data-bs-target='#leaveRejectModal' data-id='" . $row['Leave_ID'] . "' >
                                                        <i class='fa-solid fa-xmark'></i> 
                                                    </button>
                                                </td>";
                                                echo "</tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Processed Tab Content -->
                    <div class="tab-pane fade" id="processed-content" role="tabpanel">
                        <div id="processedLeaveStatsCards">
                            <?php
                            // Get total processed count
                            $totalSql = "SELECT COUNT(*) as total FROM leave_applications 
                                         WHERE Status IN ('Rejected by HOD','Rejected by Admin','Rejected by Parents','Approved')";
                            $totalResult = mysqli_query($conn, $totalSql);
                            $totalRow = mysqli_fetch_assoc($totalResult);
                            $totalCount = $totalRow['total'];

                            // Get approved count by leave type
                            $approvedSql = "SELECT lt.Leave_Type_Name, lt.LeaveType_ID, COUNT(la.Leave_ID) as count 
                                            FROM leave_types lt
                                            LEFT JOIN leave_applications la ON lt.LeaveType_ID = la.LeaveType_ID 
                                                AND la.Status = 'Approved'
                                            GROUP BY lt.LeaveType_ID, lt.Leave_Type_Name
                                            ORDER BY lt.LeaveType_ID";
                            $approvedResult = mysqli_query($conn, $approvedSql);

                            // Get rejected count by leave type
                            $rejectedSql = "SELECT lt.Leave_Type_Name, lt.LeaveType_ID, COUNT(la.Leave_ID) as count 
                                            FROM leave_types lt
                                            LEFT JOIN leave_applications la ON lt.LeaveType_ID = la.LeaveType_ID 
                                                AND la.Status IN ('Rejected by HOD','Rejected by Admin','Rejected by Parents')
                                            GROUP BY lt.LeaveType_ID, lt.Leave_Type_Name
                                            ORDER BY lt.LeaveType_ID";
                            $rejectedResult = mysqli_query($conn, $rejectedSql);

                            // Get total approved and rejected counts
                            $totalApprovedSql = "SELECT COUNT(*) as total FROM leave_applications WHERE Status = 'Approved'";
                            $totalApprovedResult = mysqli_query($conn, $totalApprovedSql);
                            $totalApprovedRow = mysqli_fetch_assoc($totalApprovedResult);
                            $totalApprovedCount = $totalApprovedRow['total'];

                            $totalRejectedSql = "SELECT COUNT(*) as total FROM leave_applications 
                                                 WHERE Status IN ('Rejected by HOD','Rejected by Admin','Rejected by Parents')";
                            $totalRejectedResult = mysqli_query($conn, $totalRejectedSql);
                            $totalRejectedRow = mysqli_fetch_assoc($totalRejectedResult);
                            $totalRejectedCount = $totalRejectedRow['total'];

                            // Store approved and rejected counts by leave type
                            $approvedCounts = [];
                            $rejectedCounts = [];

                            while ($row = mysqli_fetch_assoc($approvedResult)) {
                                $approvedCounts[$row['Leave_Type_Name']] = $row['count'];
                            }

                            while ($row = mysqli_fetch_assoc($rejectedResult)) {
                                $rejectedCounts[$row['Leave_Type_Name']] = $row['count'];
                            }

                            // Get all leave types
                            $leaveTypesSql = "SELECT Leave_Type_Name, LeaveType_ID FROM leave_types ORDER BY LeaveType_ID";
                            $leaveTypesResult = mysqli_query($conn, $leaveTypesSql);
                            $leaveTypes = [];
                            while ($row = mysqli_fetch_assoc($leaveTypesResult)) {
                                $leaveTypes[] = $row['Leave_Type_Name'];
                            }

                            // Define colors for each card
                            $colors = ['success', 'info', 'warning', 'danger', 'primary'];
                            $colorIndex = 0;

                            // Define icons for different leave types
                            $icons = [
                                'Medical Leave' => 'fa-user-doctor',
                                'Emergency Leave' => 'fa-triangle-exclamation',
                                'Home Leave' => 'fa-house',
                                'General Leave' => 'fa-calendar-days',
                                'IVR Leave' => 'fa-phone',
                            ];
                            ?>
                            <!-- Store data for modal as JSON in hidden div -->
                            <div id="breakdownData" style="display:none;"
                                data-approved='<?php echo json_encode($approvedCounts); ?>'
                                data-rejected='<?php echo json_encode($rejectedCounts); ?>'>
                            </div>

                            <div class="row mb-4">
                                <!-- Total Processed Card -->
                                <div class="col-xl col-lg-3 col-md-6 mb-4">
                                    <div class="gradient-card gradient-primary card-clickable"
                                        data-card-type="processed" data-title="Total Processed Breakdown">
                                        <div class="card-body text-center">
                                            <div class="icon-container">
                                                <i class="fas fa-tasks text-white"></i>
                                            </div>
                                            <h4 class="card-title text-white">Total Processed</h4>
                                            <h2 class="card-value text-white font-weight-bold pulse-value">
                                                <?php echo $totalCount; ?></h2>
                                        </div>
                                    </div>
                                </div>

                                <!-- Total Approved Card -->
                                <div class="col-xl col-lg-3 col-md-6 mb-4">
                                    <div class="gradient-card gradient-success card-clickable" data-card-type="approved"
                                        data-title="Total Approved Breakdown">
                                        <div class="card-body text-center">
                                            <div class="icon-container">
                                                <i class="fas fa-check-circle text-white"></i>
                                            </div>
                                            <h4 class="card-title text-white">Total Approved</h4>
                                            <h2 class="card-value text-white font-weight-bold pulse-value">
                                                <?php echo $totalApprovedCount; ?></h2>
                                        </div>
                                    </div>
                                </div>

                                <!-- Total Rejected Card -->
                                <div class="col-xl col-lg-3 col-md-6 mb-4">
                                    <div class="gradient-card gradient-danger card-clickable" data-card-type="rejected"
                                        data-title="Total Rejected Breakdown">
                                        <div class="card-body text-center">
                                            <div class="icon-container">
                                                <i class="fas fa-times-circle text-white"></i>
                                            </div>
                                            <h4 class="card-title text-white">Total Rejected</h4>
                                            <h2 class="card-value text-white font-weight-bold pulse-value">
                                                <?php echo $totalRejectedCount; ?></h2>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold flex-grow-1 text-center">Processed Leave Applications
                                    (Faculty)</h6>
                                <div class="btn-group" role="group">
                                    <a href="export.php?action=faculty_processed_pdf" target="_blank"
                                        class="btn btn-danger btn-sm" title="Export to PDF">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>
                                    <a href="export.php?action=faculty_processed_excel" class="btn btn-success btn-sm"
                                        title="Export to Excel">
                                        <i class="fas fa-file-excel"></i> Excel
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" id="processedTable">
                                    <table class="table table-bordered" id="processed-leave-table" width="100%"
                                        cellspacing="0">
                                        <colgroup>
                                            <col style="width:2%;"> <!-- S.No -->
                                            <col style="width:8%;"> <!-- Reg No -->
                                            <col style="width:12%;"> <!-- Name -->
                                            <col style="width:9%;"> <!-- Leave Type -->
                                            <col style="width:13%;"> <!-- Applied Date -->
                                            <col style="width:12%;"> <!-- From -->
                                            <col style="width:12%;"> <!-- To -->
                                            <col style="width:10%;"> <!-- Reason -->
                                            <col style="width:8%;"> <!-- Proof -->
                                            <col style="width:13%;"> <!-- Status -->
                                        </colgroup>
                                        <thead class="gradient-header">
                                            <tr>
                                                <th>S.No</th>
                                                <th>Reg No</th>
                                                <th>Name</th>
                                                <th>Leave Type</th>
                                                <th>Applied Date</th>
                                                <th>From</th>
                                                <th>To</th>
                                                <th>Reason</th>
                                                <th>Proof</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Ensure db connection is available before running the query
                                            if (!isset($conn)) {
                                                echo "<tr><td colspan='9' class='text-danger text-center'>Database connection failed. Check db.php.</td></tr>";
                                                exit;
                                            }

                                            // Fetch only rows where final_status is one of the processed states
                                            $sql = "SELECT la.*, s.name AS student_name, s.roll_number AS Reg_No, lt.Leave_Type_Name 
                                                    FROM leave_applications la
                                                    JOIN students s ON la.Reg_No = s.roll_number
                                                    JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
                                                    WHERE la.Status IN ('Rejected by HOD','Rejected by Admin','Rejected by Parents','Approved', 'Forwarded to Admin') 
                                                    ORDER BY la.Leave_ID DESC";
                                            $result = mysqli_query($conn, $sql);

                                            if (!$result) {
                                                echo "<tr><td colspan='9' class='text-danger text-center'>Query failed: " . mysqli_error($conn) . "</td></tr>";
                                            } else {
                                                $sno = 1;
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<tr>";
                                                    echo "<td>" . $sno++ . "</td>";

                                                    // --- Reg No & Clickable Name (MATCH ADMIN EXACTLY) ---
                                                    $reg_no = htmlspecialchars($row['Reg_No']);
                                                    $student_name = htmlspecialchars($row['student_name']);

                                                    echo "<td>" . $reg_no . "</td>";

                                                    // Match admin exactly - use anchor tag with student-name-link class
                                                    echo "<td><a href='#' class='student-name-link' data-reg-no='" . $reg_no . "' style='text-decoration:none; color:#166176; cursor:pointer;'>" . $student_name . "</a></td>";
                                                    // --- END Clickable Name ---
                                            
                                                    echo "<td>" . $row['Leave_Type_Name'] . "</td>";

                                                    $appliedDate = date('d-m-Y h:i A', strtotime($row['Applied_Date']));
                                                    $fromDate = date('d-m-Y h:i A', strtotime($row['From_Date']));
                                                    $toDate = date('d-m-Y h:i A', strtotime($row['To_Date']));

                                                    echo "<td>" . $appliedDate . "</td>";
                                                    echo "<td>" . $fromDate . "</td>";
                                                    echo "<td>" . $toDate . "</td>";
                                                    echo "<td>" . $row['Reason'] . "</td>";

                                                    if (!empty($row['Proof'])) {
                                                        echo "<td class='text-center align-middle'>
                                                            <button type='button' class='btn btn-info btn-sm view-proof' 
                                                                data-proof='" . $row['Proof'] . "' 
                                                                data-bs-toggle='modal' 
                                                                data-bs-target='#viewProofModal'>
                                                                <i class='fa-solid fa-eye'></i> View Proof
                                                            </button>
                                                        </td>";
                                                    } else {
                                                        echo "<td class='text-center align-middle text-muted'>No Proof Uploaded </td>";
                                                    }

                                                    // --- START CORRECTED STATUS DISPLAY LOGIC ---
                                                    $current_status = $row['Status'];
                                                    // CRITICAL FIX: Use ENT_QUOTES to ensure single quotes in remarks don't break the 'data-reason' attribute.
                                                    $remarks = htmlspecialchars($row['Remarks'] ?? 'No reason recorded', ENT_QUOTES, 'UTF-8');
                                                    echo "<td class='text-center align-middle'>";

                                                    // 1. Approved Status (Green Button)
                                                    if ($current_status == 'Approved') {
                                                        // Check if Remarks exists, if not, default to Parents. (Assuming Parent approval is the final step)
                                                        $approved_by = !empty($row['Remarks']) ? $row['Remarks'] : 'Parents';
                                                        echo "<button class='btn btn-success btn-sm' disabled>Leave Approved</button>";
                                                        echo "<br><span class='text-muted'> (Final Approval)</span>";
                                                    } else if ($current_status == 'Forwarded to Admin') {
                                                        echo "<button class='btn btn-primary btn-sm' disabled>Forwarded To Admin</button>";
                                                        echo "<br><span class='text-muted'> (Waiting for Admin)</span>";
                                                    }
                                                    // 2. Rejected Status (Orange/Warning Button)
                                                    else if (strpos($current_status, 'Rejected') !== false) {
                                                        // Determine who rejected it for the label
                                                        $rejected_by = '';
                                                        if ($current_status == 'Rejected by HOD') {
                                                            $rejected_by = ' (Rejected by HOD)';
                                                        } else if ($current_status == 'Rejected by Admin') {
                                                            $rejected_by = ' (Rejected by Admin)';
                                                        } else if ($current_status == 'Rejected by Parents') {
                                                            $rejected_by = ' (Rejected by Parents)';
                                                            $remarks = 'Rejected by Parents'; // Use status as reason if no remarks
                                                        }

                                                        // CRITICAL FIX: Add Bootstrap attributes and change class to be more descriptive
                                                        echo "<button type='button' 
                                                                 style='background-color:#f1a460; color: #fff;' 
                                                                 class='btn btn-sm reject-reason-view' 
                                                                 data-bs-toggle='modal' 
                                                                 data-bs-target='#rejectReasonModal' 
                                                                 data-reason='{$remarks}'>
                                                                 <i class='fa-solid fa-question'></i> Rejected
                                                              </button>";
                                                        echo "<br><span class='text-muted'> {$rejected_by}</span>";
                                                    }

                                                    // 3. Unexpected Processed Status (Shouldn't happen with the WHERE clause, but good for debug)
                                                    else {
                                                        echo "<button class='btn btn-secondary btn-sm' disabled>" . $current_status . "</button>";
                                                    }

                                                    echo "</td>";
                                                    // --- END CORRECTED STATUS DISPLAY LOGIC ---
                                            
                                                    echo "</tr>";
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                    <img id="pdfLogo" src="../image/kr.jpg" style="display:none;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include '../assets/footer.php'; ?>
    </div>
    <script>
        // General JS for loader and sidebar toggle...
        document.addEventListener('DOMContentLoaded', function () {
            const loaderContainer = document.getElementById('loaderContainer');
            let loadingTimeout;

            function hideLoader() {
                loaderContainer.classList.add('hide');
            }

            function showError() {
                console.error('Page load took too long or encountered an error');
            }

            loaderContainer.classList.add('show'); // Show loader immediately
            loadingTimeout = setTimeout(showError, 10000);

            window.onload = function () {
                clearTimeout(loadingTimeout);
                setTimeout(hideLoader, 500);
            };

            window.onerror = function (msg, url, lineNo, columnNo, error) {
                clearTimeout(loadingTimeout);
                showError();
                return false;
            };

            // ... (Sidebar and user menu toggles would go here) ...
        });

        $(document).ready(function () {

            // GLOBAL DATATABLES ERROR HANDLER - PREVENTS COLUMN COUNT WARNINGS
            if (typeof $ !== 'undefined' && $.fn.DataTable) {
                // Override DataTables error function to suppress column count warnings
                $.fn.dataTable.ext.errMode = function (settings, helpPage, message) {
                    // Only log to console, don't show alert
                    console.error('DataTables Error [' + settings.sInstance + ']: ' + message);

                    // Specific handling for column count errors
                    if (message.includes('Incorrect column count') || message.includes('tn/18')) {
                        console.warn('Column count mismatch detected - attempting auto-recovery');
                        // Try to recover by reinitializing the table
                        const tableId = '#' + settings.sTableId;
                        if ($(tableId).length > 0) {
                            try {
                                if ($.fn.DataTable.isDataTable(tableId)) {
                                    $(tableId).DataTable().destroy();
                                }
                                // Reinitialize with basic settings
                                $(tableId).DataTable({
                                    responsive: true,
                                    pageLength: 10,
                                    destroy: true,
                                    retrieve: false
                                });
                                console.log('Auto-recovery successful for:', tableId);
                            } catch (recoveryError) {
                                console.error('Auto-recovery failed for:', tableId, recoveryError);
                            }
                        }
                    }
                };
            }

            // Initialize DataTables - ROBUST SOLUTION FOR COLUMN COUNT ERRORS
            if (typeof $ !== 'undefined' && $.fn.DataTable) {
                // Function to safely initialize a single table with column validation
                function initTableWithValidation(tableSelector, expectedColumns, columnDefsTargets) {
                    const $table = $(tableSelector);

                    // Check if table exists in DOM
                    if ($table.length === 0) {
                        console.log('Table not found in DOM:', tableSelector);
                        return false;
                    }

                    // Check actual column count
                    const actualColumns = $table.find('thead th').length;
                    if (actualColumns !== expectedColumns) {
                        console.error('Column count mismatch for', tableSelector,
                            '- Expected:', expectedColumns, 'Actual:', actualColumns);
                        return false;
                    }

                    // Destroy existing instance if it exists
                    if ($.fn.DataTable.isDataTable(tableSelector)) {
                        console.log('Destroying existing DataTable:', tableSelector);
                        $table.DataTable().destroy();
                    }

                    // Initialize with validated configuration
                    try {
                        $table.DataTable({
                            responsive: true,
                            pageLength: 10,
                            lengthMenu: [5, 10, 25, 50, 100],
                            order: [[0, 'asc']],
                            destroy: true,  // Force destroy existing instances
                            retrieve: false, // Don't retrieve existing instances
                            columnDefs: [{
                                orderable: false,
                                targets: columnDefsTargets
                            }]
                        });
                        console.log('Successfully initialized:', tableSelector, 'with', actualColumns, 'columns');
                        return true;
                    } catch (error) {
                        console.error('DataTables initialization failed for', tableSelector, error);
                        return false;
                    }
                }

                // Initialize tables with explicit column counts and validation
                // Pending table: 11 columns (S.No, Reg No, Name, Leave Type, Applied Date, From, To, Reason, Proof, Status, Action)
                initTableWithValidation('#ivr-pending-leave-table', 11, [8, 9]);

                // Processed table: 10 columns (S.No, Reg No, Name, Leave Type, Applied Date, From, To, Reason, Proof, Status)
                initTableWithValidation('#processed-leave-table', 10, [8, 9]);
            }


            // Proof View Modal Handler
            $(document).on("click", ".view-proof", function () {
                let proofPath = $(this).data("proof");
                let timestamp = new Date().getTime();
                let cacheBustedPath = proofPath + "?t=" + timestamp;
                let ext = proofPath.split('.').pop().toLowerCase();

                let html = "";
                if (["jpg", "jpeg", "png", "gif"].includes(ext)) {
                    html = `<img src="${cacheBustedPath}" class="img-fluid" alt="Proof">`;
                } else if (ext === "pdf") {
                    html =
                        `<iframe src="${cacheBustedPath}" width="100%" height="600px" style="border:none;"></iframe>`;
                } else {
                    html = `<p class="text-danger">Unsupported file format</p>`;
                }

                $("#proofContainer").html(html);
            });


            // Rejection Reason View Handler
            $(document).on("click", ".reasonView", function () {
                Swal.fire({
                    title: "Rejection - Reason",
                    text: $(this).data("reason"),
                    icon: "error"
                });
            })

            // SCRIPT TO HANDLE PROCESSED BREAKDOWN MODAL
            $(document).on('click', '.card-clickable', function () {
                var type = $(this).data('card-type');
                var title = $(this).data('title');

                // Get the breakdown data from the hidden div populated by processedLeaveStats.php
                var breakdownDataElement = $('#processedLeaveStatsCards').find('#breakdownData');
                if (breakdownDataElement.length === 0 || !breakdownDataElement.attr('data-approved')) {
                    // If data isn't loaded yet, show a warning and return
                    Swal.fire('Loading Error', 'Please wait for the data to fully load before clicking.', 'warning');
                    return;
                }

                var approvedData = JSON.parse(breakdownDataElement.attr('data-approved'));
                var rejectedData = JSON.parse(breakdownDataElement.attr('data-rejected'));

                var $modal = $('#processedBreakdownModal');
                $modal.find('.modal-title').text(title || 'Leave Type Breakdown');

                // Clear all breakdown containers first
                $modal.find('.breakdown-container').hide().find('tbody').empty();

                // Function to generate and append rows
                function appendRows(data, containerId, countClass) {
                    var rows = [];
                    Object.keys(data).forEach(function (leaveType) {
                        var count = parseInt(data[leaveType]) || 0;
                        if (count > 0) {
                            rows.push({ type: leaveType, count: count });
                        }
                    });

                    rows.sort(function (a, b) { return a.type.localeCompare(b.type); });

                    rows.forEach(function (row) {
                        $modal.find(`#${containerId} tbody`).append(
                            '<tr>' +
                            '<td>' + row.type + '</td>' +
                            '<td class="text-end fw-bold ' + countClass + '">' + row.count + '</td>' +
                            '</tr>'
                        );
                    });
                    $modal.find(`#${containerId}`).show();
                }

                // Build the appropriate breakdown table
                if (type === 'processed') {
                    var allTypes = new Set([...Object.keys(approvedData), ...Object.keys(rejectedData)]);
                    var processedRows = [];

                    allTypes.forEach(function (leaveType) {
                        var approved = parseInt(approvedData[leaveType]) || 0;
                        var rejected = parseInt(rejectedData[leaveType]) || 0;
                        var total = approved + rejected;
                        if (total > 0) {
                            processedRows.push({ type: leaveType, count: total });
                        }
                    });

                    processedRows.sort(function (a, b) { return a.type.localeCompare(b.type); });

                    processedRows.forEach(function (row) {
                        $modal.find('#processed-breakdown tbody').append(
                            '<tr>' +
                            '<td>' + row.type + '</td>' +
                            '<td class="text-end fw-bold">' + row.count + '</td>' +
                            '</tr>'
                        );
                    });

                    $modal.find('#processed-breakdown').show();
                } else if (type === 'approved') {
                    appendRows(approvedData, 'approved-breakdown', 'text-success');
                } else if (type === 'rejected') {
                    appendRows(rejectedData, 'rejected-breakdown', 'text-danger');
                }

                // Use Bootstrap's data-bs-toggle method instead of creating new modal instance
                $('#processedBreakdownModal').modal('show');
            });

            // Fix modal backdrop issue - ensure backdrop is removed when modal is hidden
            $('#processedBreakdownModal').on('hidden.bs.modal', function () {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('overflow', '');
            });

        })
    </script>





    <div class="modal fade" id="viewProofModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #4e73df, #2e59d9); color: white;">
                    <h5 class="modal-title">Leave Proof</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="proofContainer"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-labelledby="rejectReasonModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectReasonModalLabel">
                        <i class="fas fa-times-circle me-2"></i> Rejection Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Reason for Rejection:</strong></p>
                    <div class="alert alert-light border p-3 mt-2" id="modalRejectionReason"
                        style="white-space: pre-wrap; word-break: break-word;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>



    <div class="modal fade" id="processedBreakdownModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #4e73df, #2e59d9); color: white;">
                    <h5 class="modal-title">Leave Type Breakdown</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="processed-breakdown" class="breakdown-container">
                        <h6 class="text-center mb-3">Processed by Leave Type</h6>
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>

                    <div id="approved-breakdown" class="breakdown-container">
                        <h6 class="text-center mb-3">Approved by Leave Type</h6>
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>

                    <div id="rejected-breakdown" class="breakdown-container">
                        <h6 class="text-center mb-3">Rejected by Leave Type</h6>
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Leave Breakdown Modal (MATCH ADMIN EXACTLY) -->
    <div class="modal fade" id="studentLeaveBreakdownModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Student Leave History ( <span id="studentRegNoDisplay"></span> - <span
                            id="studentNameDisplay"></span> )
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Loading Spinner -->
                    <div id="leaveHistoryLoader" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading leave history...</p>
                    </div>

                    <!-- Content Container -->
                    <div id="leaveHistoryContent" style="display:none;">

                        <!-- Overall Statistics -->
                        <div class="row mb-4">
                            <div class="col-xl-3 col-lg-3 col-md-6 mb-3">
                                <div class="gradient-card gradient-primary">
                                    <div class="card-body text-center">
                                        <div class="icon-container">
                                            <i class="fas fa-clipboard-list text-white"></i>
                                        </div>
                                        <h4 class="card-title text-white">Total Applications</h4>
                                        <h2 class="card-value text-white font-weight-bold pulse-value"
                                            id="totalApplications">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-md-6 mb-3">
                                <div class="gradient-card gradient-success">
                                    <div class="card-body text-center">
                                        <div class="icon-container">
                                            <i class="fas fa-check-circle text-white"></i>
                                        </div>
                                        <h4 class="card-title text-white">Approved</h4>
                                        <h2 class="card-value text-white font-weight-bold pulse-value"
                                            id="totalApproved">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-md-6 mb-3">
                                <div class="gradient-card gradient-warning">
                                    <div class="card-body text-center">
                                        <div class="icon-container">
                                            <i class="fas fa-times-circle text-white"></i>
                                        </div>
                                        <h4 class="card-title text-white">Rejected</h4>
                                        <h2 class="card-value text-white font-weight-bold pulse-value"
                                            id="totalRejected">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-md-6 mb-3">
                                <div class="gradient-card gradient-info">
                                    <div class="card-body text-center">
                                        <div class="icon-container">
                                            <i class="fas fa-clock text-white"></i>
                                        </div>
                                        <h4 class="card-title text-white">Pending</h4>
                                        <h2 class="card-value text-white font-weight-bold pulse-value"
                                            id="totalPending">0</h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Leave Type Statistics -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Leave Type Statistics</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0">
                                        <thead class="gradient-header">
                                            <tr>
                                                <th>Leave Type</th>
                                                <th class="text-center">Total</th>
                                                <th class="text-center">Approved</th>
                                                <th class="text-center">Rejected</th>
                                                <th class="text-center">Pending</th>
                                            </tr>
                                        </thead>
                                        <tbody id="leaveTypeStats">
                                            <!-- Dynamically populated -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Leave History -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-history me-2"></i>Recent Leave History (Last 10)</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0">
                                        <thead class="gradient-header">
                                            <tr>
                                                <th>S.No</th>
                                                <th>Leave Type</th>
                                                <th>Applied Date</th>
                                                <th>From</th>
                                                <th>To</th>
                                                <th>Days</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="leaveHistoryTable">
                                            <!-- Dynamically populated -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background: #f8f9fa; border-top: 1px solid rgba(0,0,0,0.1);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!--leave reject reason model in pending table-->
    <div class="modal fade" id="leaveRejectModal" tabindex="-1" aria-labelledby="leaveRejectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="leaveRejectModalLabel">Reject Leave Application</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Confirm to **REJECT** this leave application?</p>
                    <div class="mb-3">
                        <label for="rejectionReason" class="form-label fw-bold">Rejection Reason <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectionReason" rows="3"
                            placeholder="Enter a mandatory reason for rejection..."></textarea>
                    </div>
                    <input type="hidden" id="leaveIdToReject">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmReject">Reject Leave</button>
                </div>
            </div>
        </div>
    </div>
    <script>

        function reloadAllTables() {
            console.log('Reloading all tables and stats...');
            location.reload(); // Simple reload for now since everything is in one file
        }

        // All stats and tables are now in a single file, so reload is handled by page refresh

        // Student Leave Breakdown Modal Handler (MATCH ADMIN EXACTLY)
        $(document).on("click", ".student-name-link", function (e) {
            e.preventDefault();
            let regNo = $(this).data("reg-no");

            // Show modal and loader
            $('#studentLeaveBreakdownModal').modal('show');
            $('#leaveHistoryLoader').show();
            $('#leaveHistoryContent').hide();

            // Fetch student leave history
            $.ajax({
                url: "../api.php",
                type: "POST",
                data: {
                    action: "getStudentLeaveHistory",
                    reg_no: regNo
                },
                dataType: "json",
                success: function (response) {
                    if (response.success) {
                        // Populate student info
                        $('#studentNameDisplay').text(response.student.name);
                        $('#studentRegNoDisplay').text(response.student.roll_number);

                        // Populate totals
                        $('#totalApplications').text(response.totals.total_applications || 0);
                        $('#totalApproved').text(response.totals.total_approved || 0);
                        $('#totalRejected').text(response.totals.total_rejected || 0);
                        $('#totalPending').text(response.totals.total_pending || 0);

                        // Populate leave type statistics
                        let leaveTypeStatsHtml = '';
                        if (response.leave_stats.length > 0) {
                            response.leave_stats.forEach(function (stat) {
                                leaveTypeStatsHtml += `
                            <tr>
                                <td>${stat.Leave_Type_Name}</td>
                                <td class="text-center"><span>${stat.total_leaves}</span></td>
                                <td class="text-center"><span>${stat.approved_count}</span></td>
                                <td class="text-center"><span>${stat.rejected_count}</span></td>
                                <td class="text-center"><span>${stat.pending_count}</span></td>
                            </tr>
                        `;
                            });
                        } else {
                            leaveTypeStatsHtml = '<tr><td colspan="5" class="text-center text-muted">No leave statistics available</td></tr>';
                        }
                        $('#leaveTypeStats').html(leaveTypeStatsHtml);

                        // Populate leave history
                        let leaveHistoryHtml = '';
                        if (response.leave_history.length > 0) {
                            // Show only last 10 records
                            const recentLeaves = response.leave_history.slice(0, 10);
                            recentLeaves.forEach(function (leave, index) {
                                let statusBadge = '';
                                let status = leave.Status || '';

                                // Status badge mapping based on database enum
                                if (status === 'Approved') {
                                    statusBadge = '<center><span class="badge" style="background: #1cc88a; color: white;">Approved</span></center>';
                                } else if (['Rejected by HOD', 'Rejected by Admin', 'Rejected by Parents', 'Rejected'].includes(status)) {
                                    statusBadge = '<center><span class="badge" style="background: #e74a3b; color: white;">Rejected</span></center>';
                                } else if (status === 'Pending') {
                                    statusBadge = '<center><span class="badge" style="background: #f6c23e; color: #333;">Pending</span></center>';
                                } else if (status === 'Forwarded to Admin') {
                                    statusBadge = '<center><span class="badge" style="background: #3498db; color: white;">Forwarded</span></center>';
                                } else if (status === 'Cancelled') {
                                    statusBadge = '<center><span class="badge" style="background: #95a5a6; color: white;">Cancelled</span></center>';
                                } else if (status === 'Closed') {
                                    statusBadge = '<center><span class="badge" style="background: #7f8c8d; color: white;">Closed</span></center>';
                                } else if (status === 'Out') {
                                    statusBadge = '<center><span class="badge" style="background: #9b59b6; color: white;">Out</span></center>';
                                } else if (status === 'Late Entry') {
                                    statusBadge = '<center><span class="badge" style="background: #e67e22; color: white;">Late Entry</span></center>';
                                } else {
                                    statusBadge = '<center><span class="badge" style="background: #858796; color: white;">' + status + '</span></center>';
                                }

                                let appliedDate = new Date(leave.Applied_Date).toLocaleDateString('en-GB', {
                                    day: '2-digit', month: 'short', year: 'numeric'
                                });
                                let fromDate = new Date(leave.From_Date).toLocaleDateString('en-GB', {
                                    day: '2-digit', month: 'short', year: 'numeric'
                                });
                                let toDate = new Date(leave.To_Date).toLocaleDateString('en-GB', {
                                    day: '2-digit', month: 'short', year: 'numeric'
                                });

                                leaveHistoryHtml += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${leave.Leave_Type_Name}</td>
                                <td>${appliedDate}</td>
                                <td>${fromDate}</td>
                                <td>${toDate}</td>
                                <td><span>${leave.duration_days} day(s)</span></td>
                                <td>${statusBadge}</td>
                            </tr>
                        `;
                            });
                        } else {
                            leaveHistoryHtml = '<tr><td colspan="7" class="text-center text-muted">No leave history available</td></tr>';
                        }
                        $('#leaveHistoryTable').html(leaveHistoryHtml);

                        // Hide loader and show content
                        $('#leaveHistoryLoader').hide();
                        $('#leaveHistoryContent').show();
                    } else {
                        $('#leaveHistoryLoader').hide();
                        Swal.fire({
                            title: "Error",
                            text: response.message || "Failed to load student leave history",
                            icon: "error"
                        });
                        $('#studentLeaveBreakdownModal').modal('hide');
                    }
                },
                error: function (xhr, status, error) {
                    $('#leaveHistoryLoader').hide();
                    Swal.fire({
                        title: "Error",
                        text: "Failed to fetch student leave history. Please try again.",
                        icon: "error"
                    });
                    $('#studentLeaveBreakdownModal').modal('hide');
                    console.error("AJAX Error:", status, error);
                }
            });
        });
    </script>
    <script>
        $(document).ready(function () {
            // Handle faculty approve leave button click
            $(document).on("click", ".approve-leave-btn", function () {
                let leaveId = $(this).data("id");

                Swal.fire({
                    title: 'Confirm Approval',
                    text: "Are you sure you want to approve this leave? It will be forwarded to Admin for final approval.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#1cc88a', // Success green
                    cancelButtonColor: '#6c757d', // Secondary gray
                    confirmButtonText: 'Yes, Forward to Admin'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // User confirmed, proceed with AJAX
                        $.ajax({
                            url: "../api.php",
                            type: "POST",
                            data: {
                                action: "faculty_approve", // Forward to admin with "Approved by Faculty" status
                                id: leaveId
                            },
                            dataType: "json",
                            success: function (response) {
                                if (response.status === "success") {
                                    // Show success message using SweetAlert
                                    Swal.fire(
                                        'Forwarded!',
                                        response.message,
                                        'success'
                                    );

                                    // Reload tables and stats
                                    reloadAllTables();
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        'Error: ' + response.message,
                                        'error'
                                    );
                                }
                            },
                            error: function () {
                                Swal.fire(
                                    'Error!',
                                    'Error: Failed to process approval request.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });

            // Handle faculty reject leave button click
            $(document).on("click", ".reject-leave-btn", function () {
                let leaveId = $(this).data("id");
                $('#leaveIdToReject').val(leaveId);
                $('#rejectionReason').val('');
            });

            // Handle confirm reject button click
            $(document).on("click", "#confirmReject", function () {
                let id = $("#leaveIdToReject").val();
                let rejectionreason = $("#rejectionReason").val().trim();

                // Basic Validation
                if (!id) {
                    Swal.fire('Error', 'Leave ID not found. Please try again.', 'error');
                    $("#leaveRejectModal").modal("hide");
                    return;
                }
                if (!rejectionreason) {
                    Swal.fire('Warning', 'Please enter a mandatory rejection reason!', 'warning');
                    return;
                }

                $.ajax({
                    url: "../api.php",
                    type: "POST",
                    data: {
                        action: "reject",
                        id: id,
                        rejectionreason: rejectionreason
                    },
                    dataType: "json",
                    success: function (response) {
                        $("#leaveRejectModal").modal("hide"); // Hide the modal first
                        $("#rejectionReason").val(''); // Clear input

                        if (response.status === "success") {
                            Swal.fire({
                                title: 'Rejected!',
                                text: response.message,
                                icon: 'warning'
                            });
                            reloadAllTables();
                        } else {
                            Swal.fire('Error!', 'Error: ' + response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error!', 'Error: Failed to process rejection request.', 'error');
                    }
                });
            });

            // Listener for when the #rejectReasonModal is about to be shown
            $('#rejectReasonModal').on('show.bs.modal', function (event) {
                // 1. Get the button that triggered the modal (the Rejected status button)
                var button = $(event.relatedTarget);

                // 2. Extract the rejection reason from the custom data-reason attribute
                // The .data() method automatically handles HTML escaping
                var rejectionReason = button.data('reason');

                // 3. Update the modal's content
                var modal = $(this);

                // Set the reason text
                // Use .text() for security against XSS, and to display raw text/line breaks correctly
                modal.find('#modalRejectionReason').text(rejectionReason);
            });
        });
    </script>

    <?php
    // Handle AJAX request for student history
    if (isset($_GET['action']) && $_GET['action'] == 'fetch_student_history' && isset($_GET['reg_no'])) {
        // Include database connection
        include '../db.php';

        // Sanitize input
        $reg_no = mysqli_real_escape_string($conn, $_GET['reg_no']);

        // SQL Query to fetch history
        $sql = "
        SELECT 
            la.Applied_Date,
            la.From_Date, 
            la.To_Date, 
            la.Reason, 
            la.Status,
            la.Remarks,  
            lt.Leave_Type_Name
        FROM leave_applications la
        JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
        WHERE la.Reg_No = '{$reg_no}'
        ORDER BY la.Applied_Date DESC
    ";

        $result = mysqli_query($conn, $sql);

        if (!$result) {
            echo '<div class="alert alert-danger">Database query failed: ' . mysqli_error($conn) . '</div>';
            exit;
        }

        // Initialize Data Arrays and Counters
        $pending_rows = '';
        $processed_rows = '';
        $count_approved = 0;
        $count_rejected = 0;
        $count_pending = 0;
        $sno_pending = 1;
        $sno_processed = 1;

        // Loop and Separate Data
        while ($row = mysqli_fetch_assoc($result)) {
            $current_status = $row['Status'];

            // Determine status class and target table
            $is_pending = in_array($current_status, ['Pending', 'Forwarded to Admin']);
            $is_processed = (strpos($current_status, 'Rejected') !== false) || ($current_status == 'Approved');

            // Counters
            if ($current_status == 'Approved') {
                $count_approved++;
            } elseif (strpos($current_status, 'Rejected') !== false) {
                $count_rejected++;
            } elseif ($is_pending) {
                $count_pending++;
            }

            // Common row data formatting
            $applied_date = date('d-m-Y', strtotime($row['Applied_Date']));
            $from_date = date('d-m-Y h:i A', strtotime($row['From_Date']));
            $to_date = date('d-m-Y h:i A', strtotime($row['To_Date']));
            $reason_short = htmlspecialchars(substr($row['Reason'], 0, 50)) . '...';
            $leave_type = htmlspecialchars($row['Leave_Type_Name']);

            // Common table cells excluding S.No and last column
            $row_base_cells = '<td>' . $leave_type . '</td>';
            $row_base_cells .= '<td>' . $applied_date . '</td>';
            $row_base_cells .= '<td>' . $from_date . '</td>';
            $row_base_cells .= '<td>' . $to_date . '</td>';
            $row_base_cells .= '<td>' . $reason_short . '</td>';

            // Decide which table the row goes into
            if ($is_pending) {
                // Pending Table
                $row_html_final = '<tr>';
                $row_html_final .= '<td>' . $sno_pending++ . '</td>';
                $row_html_final .= $row_base_cells;
                $row_html_final .= '<td><span class="badge bg-warning text-dark">' . htmlspecialchars($current_status) . '</span></td>';
                $row_html_final .= '</tr>';
                $pending_rows .= $row_html_final;

            } elseif ($is_processed) {
                // Processed Table
                $status_class = ($current_status == 'Approved') ? 'success' : 'danger';
                $remarks_text = htmlspecialchars($row['Remarks'] ?? 'N/A');

                $row_html_final = '<tr>';
                $row_html_final .= '<td>' . $sno_processed++ . '</td>';
                $row_html_final .= $row_base_cells;

                // Combined Remarks & Status Column
                $row_html_final .= '<td>';
                $row_html_final .= '<div class="mb-1 small text-dark">' . htmlspecialchars($remarks_text) . '</div>';
                $row_html_final .= '<span class="badge bg-' . $status_class . '">' . htmlspecialchars($current_status) . '</span>';
                $row_html_final .= '</td>';
                $row_html_final .= '</tr>';
                $processed_rows .= $row_html_final;
            }
        }

        // Output student history content (simplified like admin panel)
        echo '<div class="row">';

        // Pending Leaves Section
        echo '<div class="col-md-6">';
        echo '<h6 class="text-warning fw-bold mb-3 border-bottom pb-2"><i class="fas fa-clock me-2"></i>Pending/Forwarded Leaves</h6>';

        if (empty($pending_rows)) {
            echo '<div class="text-center text-muted py-4"><i class="fas fa-check-circle me-2"></i>No pending leaves found.</div>';
        } else {
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped" id="pending-history-dt-' . $reg_no . '" width="100%" cellspacing="0">';
            echo '    <thead class="table-warning">';
            echo '        <tr>';
            echo '            <th>S.No</th>';
            echo '            <th>Leave Type</th>';
            echo '            <th>Applied Date</th>';
            echo '            <th>From Date</th>';
            echo '            <th>To Date</th>';
            echo '            <th>Reason</th>';
            echo '            <th>Status</th>';
            echo '        </tr>';
            echo '    </thead>';
            echo '    <tbody>';
            echo $pending_rows;
            echo '    </tbody>';
            echo '</table>';
            echo '</div>';
        }
        echo '</div>';

        // Processed Leaves Section
        echo '<div class="col-md-6">';
        echo '<h6 class="text-success fw-bold mb-3 border-bottom pb-2"><i class="fas fa-tasks me-2"></i>Processed Leaves</h6>';

        if (empty($processed_rows)) {
            echo '<div class="text-center text-muted py-4"><i class="fas fa-exclamation-circle me-2"></i>No processed leaves found.</div>';
        } else {
            echo '<div class="table-responsive">';
            echo '<table class="table table-bordered table-striped" id="processed-history-dt-' . $reg_no . '" width="100%" cellspacing="0">';
            echo '    <thead class="table-success">';
            echo '        <tr>';
            echo '            <th>S.No</th>';
            echo '            <th>Leave Type</th>';
            echo '            <th>Applied Date</th>';
            echo '            <th>From Date</th>';
            echo '            <th>To Date</th>';
            echo '            <th>Reason</th>';
            echo '            <th>Remarks & Status</th>';
            echo '        </tr>';
            echo '    </thead>';
            echo '    <tbody>';
            echo $processed_rows;
            echo '    </tbody>';
            echo '</table>';
            echo '</div>';
        }
        echo '</div>';

        echo '</div>';

        // DataTables Initialization Script - Simple and clean like admin panel
        ?>
        <script>
            // Initialize DataTables for student history tables
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof $ !== 'undefined' && $.fn.DataTable) {
                    // Initialize pending history table
                    if (!$.fn.DataTable.isDataTable('#pending-history-dt-<?php echo $reg_no; ?>')) {
                        $('#pending-history-dt-<?php echo $reg_no; ?>').DataTable({
                            responsive: true,
                            pageLength: 5,
                            lengthMenu: [5, 10, 20],
                            order: [[0, "desc"]],
                            columnDefs: [{
                                orderable: false,
                                targets: [6] // Status column
                            }]
                        });
                    }

                    // Initialize processed history table
                    if (!$.fn.DataTable.isDataTable('#processed-history-dt-<?php echo $reg_no; ?>')) {
                        $('#processed-history-dt-<?php echo $reg_no; ?>').DataTable({
                            responsive: true,
                            pageLength: 5,
                            lengthMenu: [5, 10, 20],
                            order: [[0, "desc"]],
                            columnDefs: [{
                                orderable: false,
                                targets: [6] // Remarks & Status column
                            }]
                        });
                    }
                }
            });
        </script>
        <?php
        exit;
    }
    ?>

</body>

</html>