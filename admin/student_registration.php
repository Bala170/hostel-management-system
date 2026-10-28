<?php
session_start();
include './admin_scope.php';
if (!is_any_admin_role()) {
    header('Location: ../login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIC</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="image/icons/mkce_s.png">

    <!-- ✅ Custom CSS (always after favicon) -->
    <link rel="stylesheet" href="style.css">

    <!-- ✅ Bootstrap CSS (load only once) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

    <!-- ✅ DataTables CSS (with Bootstrap 5 theme) -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- ✅ SweetAlert2 Bootstrap 5 theme -->
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-5/bootstrap-5.css" rel="stylesheet">

    <!-- ✅ jQuery (load only once, before DataTables and Bootstrap JS) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- ✅ Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
        </script>

    <!-- ✅ SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- ✅ DataTables JS (core + Bootstrap 5 integration) -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

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
            --tab-active-bg: #4e73df;
            --tab-hover-bg: #2e59d9;
            --tab-text-color: #fff;
            --tab-inactive-bg: #eaecf4;
            --tab-inactive-text: #555;
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

        /* General Styles with Enhanced Typography */

        /* Content Area Styles */
        .content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        /* Content Navigation */
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
            background: white;
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

        /* Responsive Styles */
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

        /* Add Button Styling */
        .btn-add {
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Action Buttons Styling */
        .btn-action {
            transition: all 0.2s ease;
            border-radius: 4px;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-primary.btn-action:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
        }

        .btn-info.btn-action:hover {
            background-color: #36b9cc;
            border-color: #36b9cc;
        }

        .btn-danger.btn-action:hover {
            background-color: #c82333;
            border-color: #c82333;
        }

        /* Custom Tab Styling */
        .nav-tabs .nav-link {
            background-color: var(--tab-inactive-bg);
            color: var(--tab-inactive-text);
            border: 1px solid #dee2e6;
            border-bottom: none;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link.active {
            background-color: var(--tab-active-bg);
            color: var(--tab-text-color);
            border-color: var(--tab-active-bg);
            font-weight: 500;
        }

        .nav-tabs .nav-link:hover:not(.active) {
            background-color: var(--tab-hover-bg);
            color: var(--tab-text-color);
            border-color: var(--tab-hover-bg);
        }

        .nav-tabs {
            border-bottom: 1px solid var(--tab-active-bg);
        }

        /* loader */
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

        .sidebar.collapsed+.content .loader-container {
            left: var(--sidebar-collapsed-width);
        }

        @media (max-width: 768px) {
            .loader-container {
                left: 0;
            }
        }

        /* Hide loader when done */
        .loader-container.hide {
            display: none;
        }

        /* Loader Animation */
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
    </style>
    <!--table-->
    <style>
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        /* Reset Bootstrap-like table styles */
        #studentTable {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            border: 1px solid #dee2e6;
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.05);
        }

        /* Fixed gradient header */
        #studentTable thead tr,
        #facultyTable thead tr {
            background: linear-gradient(135deg, #4CAF50, #2196F3) !important;
            color: white;
        }

        /* Table Styling */
        .table {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            font-weight: 600;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }

        #studentTable thead th {
            padding: 10px;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        /* Bootstrap-like striped rows */
        #studentTable tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        #studentTable tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        /* Hover effect similar to Bootstrap */
        #studentTable tbody tr:hover {
            background-color: #e9ecef;
            transition: background-color 0.2s ease-in-out;
        }

        /* Borders */
        #studentTable td,
        #studentTable th {
            border: 1px solid #dee2e6;
            padding: 8px;
        }

        /* Room Details Style Tables */
        .gradient-header {
            --bs-table-bg: transparent;
            --bs-table-color: white;
            background: linear-gradient(135deg, #4CAF50, #2196F3) !important;
            text-align: center;
            font-size: 0.9em;
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

        body {
            background-color: #eaeff6;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php include '../assets/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content" style="padding: 30px;">

        <div class="loader-container" id="loaderContainer">
            <div class="loader"></div>
        </div>

        <!-- Topbar -->
        <?php include '../assets/topbar.php'; ?>

        <!-- Breadcrumb -->
        <div class="breadcrumb-area custom-gradient" style="margin-top: 50px;">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Research</li>
                </ol>
            </nav>
        </div>
        <div class="container" style="background-color: white; padding:17px; border-radius: 10px;">
            <ul class="nav nav-tabs mb-3" id="registrationTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active tab-purple" id="student-tab" data-bs-toggle="tab"
                        data-bs-target="#student-tab-pane" type="button" role="tab" aria-controls="student-tab-pane"
                        aria-selected="true">
                        <span>Student Registration</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link tab-yellow" id="faculty-tab" data-bs-toggle="tab"
                        data-bs-target="#faculty-tab-pane" type="button" role="tab" aria-controls="faculty-tab-pane"
                        aria-selected="false">
                        <span>Faculty Registration</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="registrationTabsContent">
                <!-- Student Registration Tab -->
                <div class="tab-pane fade show active" id="student-tab-pane" role="tabpanel"
                    aria-labelledby="student-tab" tabindex="0">
                    <div
                        style="display: flex; justify-content: flex-end;width: 100%; margin: y 5px;background-color:rgba(33, 37, 41, 0.03) ;padding-bottom: 10px; padding-top: 10px;border-radius:20px;margin-bottom: 20px;">
                        <button type="button" class="btn btn-primary d-flex align-items-center gap-2 btn-add"
                            data-bs-toggle="modal" data-bs-target="#studentregModal"
                            style="margin-right: 20px; background: linear-gradient(135deg, #4e73df, #2e59d9); border: none;">
                            <i class="bi bi-person-fill-add" style="font-size: 20px;"></i>
                            <span style="font-size: 15px;">Add Student</span>
                        </button>
                    </div>
                    <div class="table-responsive" id="pendingTable">
                        <div class="container mt-2">
                            <table id="studentTable" class="table table-striped table-bordered" style="width:100%">
                                <thead class="gradient-header">
                                    <tr>
                                        <th>S.No</th>
                                        <th>Roll Number</th>
                                        <th>Name</th>
                                        <th>Hostel</th>
                                        <th>Room</th>
                                        <th>Approval No</th>
                                        <th>Fingerprint ID</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Faculty Registration Tab -->
                <div class="tab-pane fade" id="faculty-tab-pane" role="tabpanel" aria-labelledby="faculty-tab"
                    tabindex="0">
                    <div
                        style="display: flex; justify-content: flex-end;width: 100%; margin: y 5px;background-color:rgba(33, 37, 41, 0.03) ;padding-bottom: 10px; padding-top: 10px;border-radius:20px;margin-bottom: 20px;">
                        <button type="button" class="btn btn-primary d-flex align-items-center gap-2 btn-add"
                            data-bs-toggle="modal" data-bs-target="#facultyregModal"
                            style="margin-right: 20px; background: linear-gradient(135deg, #4e73df, #2e59d9); border: none;">
                            <i class="bi bi-person-fill-add" style="font-size: 20px;"></i>
                            <span style="font-size: 15px;">Add Faculty</span>
                        </button>
                    </div>
                    <div class="table-responsive" id="facultyTableContainer">
                        <div class="container mt-2">
                            <table id="facultyTable" class="table table-striped table-bordered" style="width:100%">
                                <thead class="gradient-header">
                                    <tr>
                                        <th>S.No</th>
                                        <th>Faculty ID</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Designation</th>
                                        <th>Hostel</th>
                                        <th>Room</th>
                                        <th>Fingerprint ID</th>
                                        <th>Role</th>
                                        <th>Additional Role</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--modals-->
        <div class="modal fade" id="studentregModal" tabindex="-1" aria-labelledby="studentModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header"
                        style="background: linear-gradient(135deg, #4e73df, #2e59d9); color: white;">
                        <h5 class="modal-title" id="studentModalLabel">Student Registration</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
<form id="studentForm">

                        <div class="modal-body">
                            <div class="row g-3">

                                <!-- Register No -->
                                <div class="col-md-4">
                                    <label class="form-label">Register No</label>
                                    <input type="text" id="register_no" name="register_no" class="form-control"
                                        required>
                                </div>

                                <!-- Student Name -->
                                <div class="col-md-4">
                                    <label class="form-label">Student Name</label>
                                    <input type="text" id="student_name" name="student_name" class="form-control"
                                        required>
                                </div>

                                <!-- Date of Join -->
                                <div class="col-md-4">
                                    <label class="form-label">Date of Join</label>
                                    <input type="date" id="date_of_join" name="date_of_join" class="form-control"
                                        required>
                                </div>

                                <!-- Date of Birth -->
                                <div class="col-md-4">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" id="dob" name="dob" class="form-control" required>
                                </div>

                                <!-- Admission Type -->
                                <div class="col-md-4">
                                    <label class="form-label">Admission Type</label>
                                    <select id="admission_type" name="admission_type" class="form-select" required>
                                        <option value="">Select Type</option>
                                        <option value="Management">Management</option>
                                        <option value="Counseling">Counseling</option>
                                        <option value="7.5 Scholarship">7.5 Scholarship</option>
                                    </select>
                                </div>

                                <!-- Department -->
                                <div class="col-md-4">
                                    <label for="department">Choose your department:</label>
                                    <select id="department" name="department" class="form-select" required>
                                        <option value="">-- Select Department --</option>
                                        <option value="Civil">Civil Engineering</option>
                                        <option value="CSE">Computer Science & Engineering (CSE)</option>
                                        <option value="IT">Information Technology (IT)</option>
                                        <option value="AIDS">Artificial Intelligence & Data Science (AI & DS)</option>
                                        <option value="EEE">Electrical & Electronics Engineering (EEE)</option>
                                        <option value="ECE">Electronics & Communication Engineering (ECE)</option>
                                        <option value="MECH">Mechanical Engineering</option>
                                        <option value="CSBS">Computer Science & Business Systems (CSBS)</option>
                                        <option value="VLSI">VLSI Design</option>
                                        <option value="MBA">Management / Business Administration (MBA)</option>
                                        <option value="MCA">Computer Applications (MCA)</option>
                                        </option>
                                    </select>
                                </div>
                                <!-- Gender -->
                                <div class="col-md-4">
                                    <label class="form-label">Gender</label>
                                    <select id="gender" name="gender" class="form-select" required>
                                        <option value="">Select</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>

                                    </select>
                                </div>
                                <!-- Hostel -->
                                <div class="col-md-4">
                                    <label class="form-label">Hostel Name</label>
                                    <select id="hostel" name="hostel" class="form-select" required>
                                        <option value="">Select Hostel</option>
                                    </select>
                                </div>

                                <!-- Block -->
                                <div class="col-md-4">
                                    <label class="form-label">Block</label>
                                    <select id="block" name="block" class="form-select" required>
                                        <option value="">Select Block</option>
                                        <option value="North">North</option>
                                        <option value="South">South</option>
                                        <option value="East">East</option>
                                        <option value="West">West</option>
                                    </select>
                                </div>

                                <!-- Floor -->
                                <div class="col-md-4">
                                    <label class="form-label">Floor</label>
                                    <select id="floor" name="floor" class="form-select" required>
                                        <option value="">Select Floor</option>
                                        <option value="I">I</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                        <option value="V">V</option>
                                    </select>
                                </div>

                                <!-- Room -->
                                <div class="col-md-4">
                                    <label class="form-label">Room</label>
                                    <select id="room" name="room" class="form-select" required>
                                        <option value="">Select Room</option>
                                    </select>
                                </div>

                                <!-- Year of Study -->
                                <div class="col-md-4">
                                    <label class="form-label">Year of Study</label>
                                    <select id="year_of_study" name="year_of_study" class="form-select" required>
                                        <option value="">Select Year</option>
                                        <option value="I">I</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                    </select>
                                </div>

                                <!-- Academic Batch -->
                                <div class="col-md-4">
                                    <label class="form-label">Academic Batch</label>
                                    <select id="academic_batch" name="academic_batch" class="form-select" required>
                                        <option value="">Select academic batch</option>
                                    </select>
                                </div>

                                <!-- Fingerprint ID -->
                                <div class="col-md-4">
                                    <label class="form-label">Fingerprint ID</label>
                                    <input type="text" id="fingerprint_id" name="fingerprint_id" class="form-control"
                                        required>
                                </div>



                                <!-- Type of Stay -->
                                <div class="col-md-4">
                                    <label class="form-label">Type of Stay</label>
                                    <select id="stay_type" name="stay_type" class="form-select" required
                                        onchange="toggleTempFields()">
                                        <option value="">Select Type</option>
                                        <option value="Permanent">Permanent</option>
                                        <option value="Temporary">Temporary</option>
                                    </select>
                                </div>

                                <!-- Temporary Stay Dates -->
                                <div class="col-md-4 temp-field d-none">
                                    <label class="form-label">From Date</label>
                                    <input type="date" id="from_date" name="from_date" class="form-control">
                                </div>

                                <div class="col-md-4 temp-field d-none">
                                    <label class="form-label">To Date</label>
                                    <input type="date" id="to_date" name="to_date" class="form-control">
                                </div>

                                <!-- Student No -->
                                <div class="col-md-4">
                                    <label class="form-label">Student No</label>
                                    <input type="text" id="student_no" name="student_no" class="form-control" required>
                                </div>

                                <!-- Email -->
                                <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" required>
                                </div>

                                <!-- Father, Mother, Guardian Names -->
                                <div class="col-md-4">
                                    <label class="form-label">Father Name</label>
                                    <input type="text" id="father_name" name="father_name" class="form-control"
                                        required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Mother Name</label>
                                    <input type="text" id="mother_name" name="mother_name" class="form-control"
                                        required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Guardian Name</label>
                                    <input type="text" id="guardian_name" name="guardian_name" class="form-control">
                                </div>

                                <!-- Phone Numbers -->
                                <div class="col-md-4">
                                    <label class="form-label">Father No</label>
                                    <input type="text" id="father_no" name="father_no" class="form-control" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Mother No</label>
                                    <input type="text" id="mother_no" name="mother_no" class="form-control" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Guardian No</label>
                                    <input type="text" id="guardian_no" name="guardian_no" class="form-control">
                                </div>

                                <!-- Approval No -->
                                <div class="col-md-4">
                                    <label class="form-label">Approval No</label>
                                    <select id="approval_no" name="approval_no" class="form-select" required>
                                        <option value="">Select</option>
                                        <option value="Father">Father</option>
                                        <option value="Mother">Mother</option>
                                        <option value="Guardian">Guardian</option>
                                    </select>
                                </div>

                                <!-- Alt Approval No -->
                                <div class="col-md-4">
                                    <label class="form-label">Alt Approval No</label>
                                    <select id="alt_approval_no" name="alt_approval_no" class="form-select" required>
                                        <option value="">Select</option>
                                        <option value="Father">Father</option>
                                        <option value="Mother">Mother</option>
                                        <option value="Guardian">Guardian</option>
                                    </select>
                                </div>

                                <!-- Language -->
                                <div class="col-md-4">
                                    <label class="form-label">Language</label>
                                    <select id="language" name="language" class="form-select" required>
                                        <option value="">Select Language</option>
                                        <option value="English">English</option>
                                        <option value="Tamil">Tamil</option>
                                    </select>
                                </div>

                                <!-- Aadhaar -->
                                <div class="col-md-4">
                                    <label class="form-label">Aadhaar</label>
                                    <input type="text" id="aadhaar" name="aadhaar" class="form-control">
                                </div>

                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Submit</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="studenteditModal" tabindex="-1" aria-labelledby="studentModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header"
                        style="background: linear-gradient(135deg, #4e73df, #2e59d9); color: white;">
                        <h5 class="modal-title" id="studentModalLabel">Student Registration</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="studenteditForm">
                        <div class="modal-body">
                            <div class="row g-3">
                                <input type="text" id="edit-student_id" name="student_id" class="form-control" hidden>
                                <!-- Register No -->
                                <div class="col-md-4">
                                    <label class="form-label">Register No</label>
                                    <input type="text" id="edit-register_no" name="register_no" class="form-control"
                                        required>
                                </div>

                                <!-- Student Name -->
                                <div class="col-md-4">
                                    <label class="form-label">Student Name</label>
                                    <input type="text" id="edit-student_name" name="student_name" class="form-control"
                                        required>
                                </div>

                                <!-- Date of Join -->
                                <div class="col-md-4">
                                    <label class="form-label">Date of Join</label>
                                    <input type="date" id="edit-date_of_join" name="date_of_join" class="form-control"
                                        required>
                                </div>

                                <!-- Date of Birth -->
                                <div class="col-md-4">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" id="edit-dob" name="dob" class="form-control" required>
                                </div>

                                <!-- Admission Type -->
                                <div class="col-md-4">
                                    <label class="form-label">Admission Type</label>
                                    <select id="edit-admission_type" name="admission_type" class="form-select" required>
                                        <option value="">Select Type</option>
                                        <option value="Management">Management</option>
                                        <option value="Counseling">Counseling</option>
                                        <option value="7.5 Scholarship">7.5 Scholarship</option>
                                    </select>
                                </div>

                                <!-- Department -->
                                <div class="col-md-4">
                                    <label for="department">Choose your department:</label>
                                    <select id="edit-department" name="department" class="form-select" required>
                                        <option value="">-- Select Department --</option>
                                        <option value="civil">Civil Engineering</option>
                                        <option value="computer_science_engineering">Computer Science & Engineering
                                            (CSE)</option>
                                        <option value="information_technology">Information Technology (IT)</option>
                                        <option value="artificial_intelligence_data_science">Artificial Intelligence &
                                            Data Science (AI & DS)</option>
                                        <option value="electrical_electronics_engineering">Electrical & Electronics
                                            Engineering (EEE)</option>
                                        <option value="electronics_communication_engineering">Electronics &
                                            Communication Engineering (ECE)</option>
                                        <option value="electronics_instrumentation_engineering">Electronics &
                                            Instrumentation Engineering (EIE)</option>
                                        <option value="mechanical_engineering">Mechanical Engineering</option>
                                        <option value="computer_science_business_systems">Computer Science & Business
                                            Systems (CSBS)</option>
                                        <option value="manufacturing_engineering">Manufacturing Engineering</option>
                                        <option value="power_systems_engineering">Power Systems Engineering</option>
                                        <option value="vlsi_design">VLSI Design</option>
                                        <option value="management_business_administration">Management / Business
                                            Administration (MBA)</option>
                                        <option value="computer_applications">Computer Applications (MCA)</option>
                                        <option value="mathematics">Mathematics (Foundational Department)</option>
                                        <option value="physics">Physics (Foundational Department)</option>
                                        <option value="chemistry">Chemistry (Foundational Department)</option>
                                        <option value="english">English / Communication Skills (Foundational Department)
                                        </option>
                                    </select>

                                </div>

                                <!-- Hostel -->
                                <!-- <div class="col-md-4">
                                    <label class="form-label">Hostel Name</label>
                                    <select id="edit-hostel" name="hostel" class="form-select" required>
                                        <option value="">Select Hostel</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Block</label>
                                    <select id="edit-block" name="block" class="form-select" required>
                                        <option value="">Select Block</option>
                                        <option value="North">North</option>
                                        <option value="South">South</option>
                                        <option value="East">East</option>
                                        <option value="West">West</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Floor</label>
                                    <select id="edit-floor" name="floor" class="form-select" required>
                                        <option value="">Select Floor</option>
                                        <option value="I">I</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                        <option value="V">V</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Room</label>
                                    <select id="edit-room" name="room" class="form-select" required>
                                        <option value="">Select Room</option>
                                    </select>
                                </div> -->

                                <!-- Year of Study -->
                                <div class="col-md-4">
                                    <label class="form-label">Year of Study</label>
                                    <select id="edit-year_of_study" name="year_of_study" class="form-select" required>
                                        <option value="">Select Year</option>
                                        <option value="I">I</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                    </select>
                                </div>

                                <!-- Academic Batch -->
                                <div class="col-md-4">
                                    <label class="form-label">Academic Batch</label>
                                    <select id="edit-academic_batch" name="academic_batch" class="form-select" required>
                                        <option value="">Select academic batch</option>

                                    </select>
                                </div>

                                <!-- Fingerprint ID -->
                                <div class="col-md-4">
                                    <label class="form-label">Fingerprint ID</label>
                                    <input type="text" id="edit-fingerprint_id" name="fingerprint_id"
                                        class="form-control" required>
                                </div>

                                <!-- Gender -->
                                <div class="col-md-4">
                                    <label class="form-label">Gender</label>
                                    <select id="edit-gender" name="gender" class="form-select" required>
                                        <option value="">Select</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <!-- Type of Stay -->
                                <div class="col-md-4">
                                    <label class="form-label">Type of Stay</label>
                                    <select id="edit-stay_type" name="stay_type" class="form-select" required
                                        onchange="toggleTempFields()">
                                        <option value="">Select Type</option>
                                        <option value="Permanent">Permanent</option>
                                        <option value="Temporary">Temporary</option>
                                    </select>
                                </div>

                                <!-- Temporary Stay Dates -->
                                <div class="col-md-4 temp-field d-none">
                                    <label class="form-label">From Date</label>
                                    <input type="date" id="edit-from_date" name="from_date" class="form-control">
                                </div>

                                <div class="col-md-4 temp-field d-none">
                                    <label class="form-label">To Date</label>
                                    <input type="date" id="edit-to_date" name="to_date" class="form-control">
                                </div>

                                <!-- Student No -->
                                <div class="col-md-4">
                                    <label class="form-label">Student No</label>
                                    <input type="text" id="edit-student_no" name="student_no" class="form-control"
                                        required>
                                </div>

                                <!-- Email -->
                                <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" id="edit-email" name="email" class="form-control" required>
                                </div>

                                <!-- Father, Mother, Guardian Names -->
                                <div class="col-md-4">
                                    <label class="form-label">Father Name</label>
                                    <input type="text" id="edit-father_name" name="father_name" class="form-control"
                                        required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Mother Name</label>
                                    <input type="text" id="edit-mother_name" name="mother_name" class="form-control"
                                        required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Guardian Name</label>
                                    <input type="text" id="edit-guardian_name" name="guardian_name"
                                        class="form-control">
                                </div>

                                <!-- Phone Numbers -->
                                <div class="col-md-4">
                                    <label class="form-label">Father No</label>
                                    <input type="text" id="edit-father_no" name="father_no" class="form-control"
                                        required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Mother No</label>
                                    <input type="text" id="edit-mother_no" name="mother_no" class="form-control"
                                        required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Guardian No</label>
                                    <input type="text" id="edit-guardian_no" name="guardian_no" class="form-control">
                                </div>

                                <!-- Approval No -->
                                <div class="col-md-4">
                                    <label class="form-label">Approval No</label>
                                    <select id="edit-approval_no" name="approval_no" class="form-select" required>
                                        <option value="">Select</option>
                                        <option value="Father">Father</option>
                                        <option value="Mother">Mother</option>
                                        <option value="Guardian">Guardian</option>
                                    </select>
                                </div>

                                <!-- Alt Approval No -->
                                <div class="col-md-4">
                                    <label class="form-label">Alt Approval No</label>
                                    <select id="edit-alt_approval_no" name="alt_approval_no" class="form-select"
                                        required>
                                        <option value="">Select</option>
                                        <option value="Father">Father</option>
                                        <option value="Mother">Mother</option>
                                        <option value="Guardian">Guardian</option>
                                    </select>
                                </div>

                                <!-- Language -->
                                <div class="col-md-4">
                                    <label class="form-label">Language</label>
                                    <select id="edit-language" name="language" class="form-select" required>
                                        <option value="">Select Language</option>
                                        <option value="English">English</option>
                                        <option value="Tamil">Tamil</option>
                                    </select>
                                </div>

                                <!-- Aadhaar -->
                                <div class="col-md-4">
                                    <label class="form-label">Aadhaar</label>
                                    <input type="text" id="edit-aadhaar" name="aadhaar" class="form-control">
                                </div>

                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Submit</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Student Details Modal -->
        <div class="modal fade" id="studentModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header"
                        style="background: linear-gradient(135deg, #4e73df, #2e59d9); color: white;">
                        <h5 class="modal-title">Student Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="studentDetails">
                        <!-- Details inserted dynamically -->
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Photo Modal -->
        <div class="modal fade" id="photoModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content text-center">
                    <div class="modal-header"
                        style="background: linear-gradient(135deg, #4e73df, #2e59d9); color: white;">
                        <h5 class="modal-title">Photo Viewer</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <img id="photoViewer" src="" alt="Photo" class="img-fluid rounded shadow">
                    </div>
                </div>
            </div>
        </div>

        <!-- Faculty Details Modal -->
        <div class="modal fade" id="facultyModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header"
                        style="background: linear-gradient(135deg, #4e73df, #2e59d9); color: white;">
                        <h5 class="modal-title">Faculty Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="facultyDetails">
                        <!-- Details inserted dynamically -->
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Faculty Registration Modal -->
        <div class="modal fade" id="facultyregModal" tabindex="-1" aria-labelledby="facultyModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header"
                        style="background: linear-gradient(135deg, #4e73df, #2e59d9); color: white;">
                        <h5 class="modal-title" id="facultyModalLabel">Faculty Registration</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="facultyForm">
                        <div class="modal-body">
                            <div class="row g-3">
                                <!-- Faculty ID -->
                                <div class="col-md-4">
                                    <label class="form-label">Faculty ID</label>
                                    <input type="text" id="faculty_id" name="faculty_id" class="form-control" required>
                                </div>

                                <!-- Faculty Name -->
                                <div class="col-md-4">
                                    <label class="form-label">Faculty Name</label>
                                    <input type="text" id="faculty_name" name="faculty_name" class="form-control"
                                        required>
                                </div>

                                <!-- Department -->
                                <div class="col-md-4">
                                    <label class="form-label">Department</label>
                                    <select id="faculty_department" name="faculty_department" class="form-select"
                                        required>
                                        <option value="">-- Select Department --</option>
                                        <option value="civil">Civil Engineering</option>
                                        <option value="computer_science_engineering">Computer Science & Engineering
                                            (CSE)</option>
                                        <option value="information_technology">Information Technology (IT)</option>
                                        <option value="artificial_intelligence_data_science">Artificial Intelligence &
                                            Data Science (AI & DS)</option>
                                        <option value="electrical_electronics_engineering">Electrical & Electronics
                                            Engineering (EEE)</option>
                                        <option value="electronics_communication_engineering">Electronics &
                                            Communication Engineering (ECE)</option>
                                        <option value="electronics_instrumentation_engineering">Electronics &
                                            Instrumentation Engineering (EIE)</option>
                                        <option value="mechanical_engineering">Mechanical Engineering</option>
                                        <option value="computer_science_business_systems">Computer Science & Business
                                            Systems (CSBS)</option>
                                        <option value="manufacturing_engineering">Manufacturing Engineering</option>
                                        <option value="power_systems_engineering">Power Systems Engineering</option>
                                        <option value="vlsi_design">VLSI Design</option>
                                        <option value="management_business_administration">Management / Business
                                            Administration (MBA)</option>
                                        <option value="computer_applications">Computer Applications (MCA)</option>
                                        <option value="mathematics">Mathematics (Foundational Department)</option>
                                        <option value="physics">Physics (Foundational Department)</option>
                                        <option value="chemistry">Chemistry (Foundational Department)</option>
                                        <option value="english">English / Communication Skills (Foundational Department)
                                        </option>
                                    </select>
                                </div>

                                <!-- Designation -->
                                <div class="col-md-4">
                                    <label class="form-label">Designation</label>
                                    <select id="designation" name="designation" class="form-select" required>
                                        <option value="">Select Designation</option>
                                        <option value="Professor">Professor</option>
                                        <option value="Associate Professor">Associate Professor</option>
                                        <option value="Assistant Professor">Assistant Professor</option>
                                        <option value="Lecturer">Lecturer</option>
                                    </select>
                                </div>

                                <!-- Email -->
                                <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" id="faculty_email" name="faculty_email" class="form-control"
                                        required>
                                </div>

                                <!-- Mobile No -->
                                <div class="col-md-4">
                                    <label class="form-label">Mobile No</label>
                                    <input type="text" id="faculty_mobile" name="faculty_mobile" class="form-control"
                                        required>
                                </div>

                                <!-- Gender -->
                                <div class="col-md-4">
                                    <label class="form-label">Gender</label>
                                    <select id="faculty_gender" name="gender" class="form-select" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <!-- Hostel -->
                                <div class="col-md-4">
                                    <label class="form-label">Hostel Name</label>
                                    <select id="faculty_hostel" name="faculty_hostel" class="form-select" required>
                                        <option value="">Select Hostel</option>
                                    </select>
                                </div>

                                <!-- Block -->
                                <div class="col-md-4">
                                    <label class="form-label">Block</label>
                                    <select id="faculty_block" name="faculty_block" class="form-select" required>
                                        <option value="">Select Block</option>
                                        <option value="North">North</option>
                                        <option value="South">South</option>
                                        <option value="East">East</option>
                                        <option value="West">West</option>
                                    </select>
                                </div>

                                <!-- Floor -->
                                <div class="col-md-4">
                                    <label class="form-label">Floor</label>
                                    <select id="faculty_floor" name="faculty_floor" class="form-select" required>
                                        <option value="">Select Floor</option>
                                        <option value="I">I</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                        <option value="V">V</option>
                                    </select>
                                </div>

                                <!-- Room -->
                                <div class="col-md-4">
                                    <label class="form-label">Room</label>
                                    <select id="faculty_room" name="faculty_room" class="form-select" required>
                                        <option value="">Select Room</option>
                                    </select>
                                </div>

                                <!-- Role -->
                                <div class="col-md-4">
                                    <label class="form-label">Role</label>
                                    <input type="text" id="faculty_role" name="role" class="form-control">
                                </div>

                                <!-- Additional Role -->
                                <div class="col-md-4">
                                    <label class="form-label">Additional Role</label>
                                    <input type="text" id="faculty_additional_role" name="additional_role"
                                        class="form-control">
                                </div>

                                <!-- Fingerprint ID -->
                                <div class="col-md-4">
                                    <label class="form-label">Fingerprint ID</label>
                                    <input type="text" id="faculty_fingerprint_id" name="faculty_fingerprint_id"
                                        class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Submit</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Faculty Edit Modal -->
        <div class="modal fade" id="facultyeditModal" tabindex="-1" aria-labelledby="facultyEditModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header"
                        style="background: linear-gradient(135deg, #4e73df, #2e59d9); color: white;">
                        <h5 class="modal-title" id="facultyEditModalLabel">Edit Faculty</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="facultyeditForm">
                        <div class="modal-body">
                            <input type="hidden" id="edit-f_id" name="f_id">
                            <div class="row g-3">
                                <!-- Faculty ID -->
                                <div class="col-md-4">
                                    <label class="form-label">Faculty ID</label>
                                    <input type="text" id="edit-faculty_id" name="faculty_id" class="form-control"
                                        required>
                                </div>

                                <!-- Faculty Name -->
                                <div class="col-md-4">
                                    <label class="form-label">Faculty Name</label>
                                    <input type="text" id="edit-faculty_name" name="faculty_name" class="form-control"
                                        required>
                                </div>

                                <!-- Department -->
                                <div class="col-md-4">
                                    <label class="form-label">Department</label>
                                    <select id="edit-faculty_department" name="faculty_department" class="form-select"
                                        required>
                                        <option value="">-- Select Department --</option>
                                        <option value="civil">Civil Engineering</option>
                                        <option value="computer_science_engineering">Computer Science & Engineering
                                            (CSE)</option>
                                        <option value="information_technology">Information Technology (IT)</option>
                                        <option value="artificial_intelligence_data_science">Artificial Intelligence &
                                            Data Science (AI & DS)</option>
                                        <option value="electrical_electronics_engineering">Electrical & Electronics
                                            Engineering (EEE)</option>
                                        <option value="electronics_communication_engineering">Electronics &
                                            Communication Engineering (ECE)</option>
                                        <option value="electronics_instrumentation_engineering">Electronics &
                                            Instrumentation Engineering (EIE)</option>
                                        <option value="mechanical_engineering">Mechanical Engineering</option>
                                        <option value="computer_science_business_systems">Computer Science & Business
                                            Systems (CSBS)</option>
                                        <option value="manufacturing_engineering">Manufacturing Engineering</option>
                                        <option value="power_systems_engineering">Power Systems Engineering</option>
                                        <option value="vlsi_design">VLSI Design</option>
                                        <option value="management_business_administration">Management / Business
                                            Administration (MBA)</option>
                                        <option value="computer_applications">Computer Applications (MCA)</option>
                                        <option value="mathematics">Mathematics (Foundational Department)</option>
                                        <option value="physics">Physics (Foundational Department)</option>
                                        <option value="chemistry">Chemistry (Foundational Department)</option>
                                        <option value="english">English / Communication Skills (Foundational Department)
                                        </option>
                                    </select>
                                </div>

                                <!-- Designation -->
                                <div class="col-md-4">
                                    <label class="form-label">Designation</label>
                                    <select id="edit-designation" name="designation" class="form-select" required>
                                        <option value="">Select Designation</option>
                                        <option value="Professor">Professor</option>
                                        <option value="Associate Professor">Associate Professor</option>
                                        <option value="Assistant Professor">Assistant Professor</option>
                                        <option value="Lecturer">Lecturer</option>
                                    </select>
                                </div>

                                <!-- Email -->
                                <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" id="edit-faculty_email" name="faculty_email"
                                        class="form-control" required>
                                </div>

                                <!-- Mobile No -->
                                <div class="col-md-4">
                                    <label class="form-label">Mobile No</label>
                                    <input type="text" id="edit-faculty_mobile" name="faculty_mobile"
                                        class="form-control" required>
                                </div>

                                <!-- Gender -->
                                <div class="col-md-4">
                                    <label class="form-label">Gender</label>
                                    <select id="edit-faculty_gender" name="gender" class="form-select" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <!-- Hostel -->
                                <div class="col-md-4">
                                    <label class="form-label">Hostel Name</label>
                                    <select id="edit-faculty_hostel" name="faculty_hostel" class="form-select" required>
                                        <option value="">Select Hostel</option>
                                    </select>
                                </div>

                                <!-- Block -->
                                <div class="col-md-4">
                                    <label class="form-label">Block</label>
                                    <select id="edit-faculty_block" name="faculty_block" class="form-select" required>
                                        <option value="">Select Block</option>
                                        <option value="North">North</option>
                                        <option value="South">South</option>
                                        <option value="East">East</option>
                                        <option value="West">West</option>
                                    </select>
                                </div>

                                <!-- Floor -->
                                <div class="col-md-4">
                                    <label class="form-label">Floor</label>
                                    <select id="edit-faculty_floor" name="faculty_floor" class="form-select" required>
                                        <option value="">Select Floor</option>
                                        <option value="I">I</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                        <option value="V">V</option>
                                    </select>
                                </div>

                                <!-- Room -->
                                <div class="col-md-4">
                                    <label class="form-label">Room</label>
                                    <select id="edit-faculty_room" name="faculty_room" class="form-select" required>
                                        <option value="">Select Room</option>
                                    </select>
                                </div>

                                <!-- Role -->
                                <div class="col-md-4">
                                    <label class="form-label">Role</label>
                                    <input type="text" id="edit-faculty_role" name="role" class="form-control">
                                </div>

                                <!-- Additional Role -->
                                <div class="col-md-4">
                                    <label class="form-label">Additional Role</label>
                                    <input type="text" id="edit-faculty_additional_role" name="additional_role"
                                        class="form-control">
                                </div>

                                <!-- Fingerprint ID -->
                                <div class="col-md-4">
                                    <label class="form-label">Fingerprint ID</label>
                                    <input type="text" id="edit-faculty_fingerprint_id" name="faculty_fingerprint_id"
                                        class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Update</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!--temp-->
        <script>
            function toggleTempFields() {
                const stayType = document.getElementById('stay_type').value;
                const tempFields = document.querySelectorAll('.temp-field');
                tempFields.forEach(f => f.classList.toggle('d-none', stayType !== 'Temporary'));
            }

            // Prevent same approval and alt approval selection
            document.getElementById('alt_approval_no').addEventListener('change', function () {
                const main = document.getElementById('approval_no').value;
                if (this.value === main && main !== '') {
                    alert('Approval and Alt Approval cannot be the same.');
                    this.value = '';
                }
            });

            function showLoader() {
                document.getElementById('loaderContainer').classList.remove('hide');
            }

            function hideLoader() {
                document.getElementById('loaderContainer').classList.add('hide');
            }
        </script>

        <!--table script-->
        <script>
            const api = '../api.php';
            $(document).ready(function () {
                // Initialize Bootstrap tooltips
                $('.action-btns [data-bs-toggle="tooltip"]').tooltip();

                // Initialize DataTable with options
                let studentTable = $('#studentTable').DataTable({
                    "processing": true,
                    "serverSide": false,
                    "pageLength": 10,
                    "autoWidth": false,
                    "ordering": true,
                    "searching": true,
                    "columns": [{
                        "data": "sno"
                    },
                    {
                        "data": "roll_number"
                    },
                    {
                        "data": "name"
                    },
                    {
                        "data": "hostel_name"
                    },
                    {
                        "data": "room_number"
                    },
                    {
                        "data": "approval_no"
                    },
                    {
                        "data": "fingerprint_id"
                    },
                    {
                        "data": "actions"
                    }
                    ]
                });

                // Initialize Faculty DataTable
                let facultyTable = $('#facultyTable').DataTable({
                    "processing": true,
                    "serverSide": false,
                    "pageLength": 10,
                    "autoWidth": false,
                    "ordering": true,
                    "searching": true,
                    "columns": [{
                        "data": "sno"
                    },
                    {
                        "data": "faculty_id"
                    },
                    {
                        "data": "f_name"
                    },
                    {
                        "data": "department"
                    },
                    {
                        "data": "designation"
                    },
                    {
                        "data": "hostel_name"
                    },
                    {
                        "data": "room_number"
                    },
                    {
                        "data": "fingerprint_id"
                    },
                    {
                        "data": "role"
                    },
                    {
                        "data": "additional_role"
                    },
                    {
                        "data": "actions"
                    }
                    ]
                });

                // Function to load data into DataTable
                function loadTable() {
                    showLoader();
                    $.post(api, {
                        action: 'list_students'
                    }, function (res) {
                        console.log("Parsed response:", res);

                        if (!res || !res.success) {
                            Swal.fire('Error', 'Failed to load data: ' + (res.message || 'Unknown error'),
                                'error');
                            hideLoader();
                            return;
                        }

                        // Clear existing data
                        studentTable.clear();

                        let i = 1;
                        if (res.data && Array.isArray(res.data)) {
                            res.data.forEach(r => {
                                studentTable.row.add({
                                    "sno": i++,
                                    "roll_number": r.roll_number || '',
                                    "name": r.name || '',
                                    "hostel_name": r.hostel_name || '',
                                    "room_number": r.room_number || '',
                                    "approval_no": r.approval_no || '',
                                    "fingerprint_id": r.fingerprint_id || '',
                                    "actions": `
                                    <div class=\"action-btns\">
                                    <button class=\"btn btn-sm btn-success btn-action\" data-action=\"edit\" data-id=\"${r.student_id}\" data-bs-toggle=\"tooltip\" data-bs-title=\"Edit Student\"><i class=\"bi bi-pencil-square\"></i></button>
                                    <button class=\"btn btn-sm btn-warning btn-action\" data-action=\"view\" data-id=\"${r.student_id}\" data-bs-toggle=\"tooltip\" data-bs-title=\"View Student\">
                                    <i class=\"bi bi-card-list\"></i>
                                    </button>
                                    <button class=\"btn btn-sm btn-danger btn-action\" data-action=\"delete\" data-id=\"${r.student_id}\" data-bs-toggle=\"tooltip\" data-bs-title=\"Delete Student\">
                                    <i class=\"bi bi-trash-fill\"></i>
                                    </button>
                                    </div>
                                    `
                                });
                            });
                        }
                        // Destroy existing tooltips before redrawing
                        $('.action-btns [data-bs-toggle="tooltip"]').tooltip('dispose');
                        studentTable.draw();
                        // Initialize tooltips for student action buttons
                        $('.action-btns [data-bs-toggle="tooltip"]').tooltip();
                        hideLoader();

                    }, 'json')
                        .fail(function (xhr, status, error) {
                            console.error("AJAX Error:", error);
                            Swal.fire('Error',
                                'Failed to load data. Please check your connection and try again.', 'error');
                            hideLoader();
                        });
                }

                // Function to load faculty data into DataTable
                function loadFacultyTable() {
                    showLoader();
                    $.post(api, {
                        action: 'list_faculty'
                    }, function (res) {
                        console.log("Faculty response:", res);

                        if (!res || !res.success) {
                            Swal.fire('Error', 'Failed to load faculty data: ' + (res.message ||
                                'Unknown error'), 'error');
                            hideLoader();
                            return;
                        }

                        // Clear existing data
                        facultyTable.clear();

                        let i = 1;
                        if (res.data && Array.isArray(res.data)) {
                            res.data.forEach(r => {
                                facultyTable.row.add({
                                    "sno": i++,
                                    "faculty_id": r.faculty_id || '',
                                    "f_name": r.f_name || '',
                                    "department": formatDepartmentName(r.department) || '',
                                    "designation": r.designation || '',
                                    "role": r.role || '',
                                    "additional_role": r.additional_role || '',
                                    "hostel_name": r.hostel_name || '',
                                    "room_number": r.room_number || '',
                                    "fingerprint_id": r.fingerprint_id || '',
                                    "actions": `
                                    <div class=\"action-btns\">
                                    <button class=\"btn btn-sm btn-success btn-action\" data-action=\"edit-faculty\" data-id=\"${r.f_id}\" data-bs-toggle=\"tooltip\" data-bs-title=\"Edit Faculty\"><i class=\"bi bi-pencil-square\"></i></button>
                                    <button class=\"btn btn-sm btn-warning btn-action\" data-action=\"view-faculty\" data-id=\"${r.f_id}\" data-bs-toggle=\"tooltip\" data-bs-title=\"View Faculty\">
                                    <i class=\"bi bi-card-list\"></i>
                                    </button>
                                    <button class=\"btn btn-sm btn-danger btn-action\" data-action=\"delete-faculty\" data-id=\"${r.f_id}\" data-bs-toggle=\"tooltip\" data-bs-title=\"Delete Faculty\">
                                    <i class=\"bi bi-trash-fill\"></i>
                                    </button>
                                    </div>
                                    `
                                });
                            });
                        }
                        // Destroy existing tooltips before redrawing
                        $('.action-btns [data-bs-toggle="tooltip"]').tooltip('dispose');
                        facultyTable.draw();
                        // Initialize tooltips for faculty action buttons
                        $('.action-btns [data-bs-toggle="tooltip"]').tooltip();
                        hideLoader();

                    }, 'json')
                        .fail(function (xhr, status, error) {
                            console.error("AJAX Error:", error);
                            Swal.fire('Error',
                                'Failed to load faculty data. Please check your connection and try again.',
                                'error');
                            hideLoader();
                        });
                }

                $(document).on('click', '[data-action="edit"]', function () {
                    const id = $(this).data('id');
                    console.log('Edit button clicked, student ID:', id);

                    $.post(api, {
                        action: 'get_update_student',
                        student_id: id
                    })
                        .done(function (res) {
                            console.log('API response for student edit:', res);
                            if (!res.success) {
                                Swal.fire('Error', res.message || 'Student not found', 'error');
                                return;
                            }

                            const s = res.student;
                            const g = res.guardians;
                            const stay = res.stay;

                            // 🧠 Fill student info
                            $('#edit-student_id').val(s.student_id);
                            $('#edit-register_no').val(s.roll_number || '');
                            $('#edit-student_name').val(s.name || '');
                            $('#edit-date_of_join').val(s.date_of_join || '');
                            $('#edit-dob').val(s.date_of_birth || '');
                            $('#edit-admission_type').val(s.admission_type || '');
                            $('#edit-department').val(s.department || '');
                            $('#edit-hostel').val(s.hostel_name || '');
                            $('#edit-block').val(s.block || '');
                            $('#edit-floor').val(s.floor || '');
                            $('#edit-room').val(s.room_number || '');
                            if (s.year_of_study === 1) {
                                $('#edit-year_of_study').val('I');
                            } else if (s.year_of_study === 2) {
                                $('#edit-year_of_study').val('II');
                            } else if (s.year_of_study === 3) {
                                $('#edit-year_of_study').val('III');
                            } else if (s.year_of_study === 4) {
                                $('#edit-year_of_study').val('IV');
                            }

                            $('#edit-academic_batch').val(s.academic_batch || '');
                            $('#edit-fingerprint_id').val(s.fingerprint_id || '');
                            $('#edit-gender').val(s.gender || '');
                            $('#edit-language').val(s.language || '');
                            $('#edit-aadhaar').val(s.aadhaar || '');
                            $('#edit-email').val(s.email || '');
                            $('#edit-student_no').val(s.student_mobile_no || '');

                            // 🧩 Stay Type
                            if (!stay) {
                                $('#edit-stay_type').val('Permanent');
                                $('.temp-field').addClass('d-none');
                                $('#edit-from_date').val('');
                                $('#edit-to_date').val('');
                            } else {
                                $('#edit-stay_type').val('Temporary');
                                $('.temp-field').removeClass('d-none');
                                $('#edit-from_date').val(stay.from_date || '');
                                $('#edit-to_date').val(stay.to_date || '');
                            }

                            // 👨‍👩‍👧 Guardian details
                            $('#edit-father_name').val(g.father?.name || '');
                            $('#edit-mother_name').val(g.mother?.name || '');
                            $('#edit-guardian_name').val(g.guardian?.name || '');
                            $('#edit-father_no').val(g.father?.phone || '');
                            $('#edit-mother_no').val(g.mother?.phone || '');
                            $('#edit-guardian_no').val(g.guardian?.phone || '');

                            // 🧾 Approval numbers
                            let primary = '';
                            let alternate = '';
                            if (g) { // Check if guardians object exists
                                for (const rel in g) {
                                    const info = g[rel];
                                    if (info && info.approval_type === 'primary') primary =
                                        capitalizeFirst(rel);
                                    if (info && info.approval_type === 'alternate') alternate =
                                        capitalizeFirst(
                                            rel);
                                }
                            }
                            $('#edit-approval_no').val(primary || '');
                            $('#edit-alt_approval_no').val(alternate || '');

                            // 🪄 Finally show modal
                            console.log('Attempting to show student edit modal');
                            const modal = new bootstrap.Modal(document.getElementById(
                                'studenteditModal'));
                            modal.show();
                            console.log('Student edit modal should be shown now');
                        })
                        .fail(function (xhr, status, error) {
                            console.error('API call failed:', status, error);
                            Swal.fire('Error', 'Failed to load student data. Please try again.',
                                'error');
                        });
                });

                // Helper to capitalize first letter
                function capitalizeFirst(str) {
                    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
                }

                // Helper to format department names (convert underscores to spaces and capitalize words)
                function formatDepartmentName(dept) {
                    if (!dept) return '';
                    // Replace underscores with spaces and capitalize each word
                    return dept.replace(/_/g, ' ') // Replace underscores with spaces
                        .replace(/\b\w/g, function (char) {
                            return char.toUpperCase();
                        }); // Capitalize first letter of each word
                }

                $(document).on('click', '[data-action="view"]', function () {
                    const id = $(this).data('id');

                    $.post(api, {
                        action: 'get_student',
                        student_id: id
                    }, function (res) {
                        if (!res.success) {
                            Swal.fire('Error', res.message || 'Student not found', 'error');
                            return;
                        }

                        const s = res.student || {};
                        const g = res.guardians || {};
                        const stay = res.stay || null;

                        const val = (v) =>
                            v && v !== 'null' && v !== '' ? v :
                                '<span class="text-muted">Not provided</span>';

                        let html = `
      <h5 class="fw-bold mb-2 text-primary">Student Information</h5>
      <table class="table table-bordered table-striped table-sm align-middle">
        <tbody>
    `;

                        Object.entries(s).forEach(([key, value]) => {
                            // format key to look nice (e.g., "student_id" → "Student ID")
                            const label = key
                                .replace(/_/g, ' ')
                                .replace(/\b\w/g, (c) => c.toUpperCase());
                            html += `<tr><th>${label}</th><td>${val(value)}</td></tr>`;
                        });

                        html += `</tbody></table>`;

                        // ---------- GUARDIANS ----------
                        html += `
                                    <h5 class="fw-bold mb-2 text-success">Guardian Details</h5>
                                    <table class="table table-bordered table-sm align-middle">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Relation</th>
                                            <th>Name</th>
                                            <th>Phone</th>
                                            <th>Photo</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                    `;

                        ['father', 'mother', 'guardian'].forEach((rel) => {
                            const d = g[rel];
                            if (!d) return;
                            const photoBtn = d.photo_path ?
                                `<button class="btn btn-outline-primary btn-sm" data-photo="${d.photo_path}" data-action="view-photo">View</button>` :
                                `<span class="text-muted">No photo uploaded yet</span>`;
                            html += `
                                <tr>
                                <td class="fw-semibold">${rel.charAt(0).toUpperCase() + rel.slice(1)}</td>
                                <td>${val(d.name)}</td>
                                <td>${val(d.phone)}</td>
                                <td>${photoBtn}</td>
                                </tr>
                            `;
                        });

                        html += `</tbody></table>`;

                        // ---------- TEMPORARY STAY ----------
                        if (stay) {
                            html += `
                                <h5 class="fw-bold mb-2 text-warning">Temporary Stay</h5>
                                <table class="table table-bordered table-sm align-middle">
                                <tbody>
                                    ${Object.entries(stay)
                                    .map(([k, v]) => {
                                        const label = k
                                            .replace(/_/g, ' ')
                                            .replace(/\b\w/g, (c) => c.toUpperCase());
                                        return `<tr><th>${label}</th><td>${val(v)}</td></tr>`;
                                    })
                                    .join('')}
                                </tbody>
                                </table>
                            `;
                        }

                        $('#studentDetails').html(html);
                        new bootstrap.Modal('#studentModal').show();
                    }, 'json');
                });

                // ✅ Photo view modal
                $(document).on('click', '[data-action="view-photo"]', function () {
                    const src = $(this).data('photo');
                    $('#photoViewer').attr('src', src);
                    new bootstrap.Modal('#photoModal').show();
                });

                // ✅ Delete student (status = 0)
                $(document).on('click', '[data-action="delete"]', function () {
                    const id = $(this).data('id');
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "This student will be deactivated.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, deactivate',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.post(api, {
                                action: 'deactivate_student',
                                student_id: id
                            }, function (res) {
                                if (res.success) {
                                    Swal.fire('Done!', 'Student deactivated successfully',
                                        'success');
                                    loadTable();
                                } else {
                                    Swal.fire('Error', res.message ||
                                        'Could not deactivate', 'error');
                                }
                            }, 'json');
                        }
                    });
                });

                // Faculty Action Handlers
                $(document).on('click', '[data-action="edit-faculty"]', function () {
                    const id = $(this).data('id');

                    // Fetch faculty details from API
                    $.post(api, {
                        action: 'get_faculty',
                        faculty_id: id
                    }, function (res) {
                        if (res.success) {
                            const faculty = res.data;

                            // Fill the edit form with faculty data
                            $('#edit-f_id').val(faculty.f_id);
                            $('#edit-faculty_id').val(faculty.faculty_id);
                            $('#edit-faculty_name').val(faculty.f_name);
                            $('#edit-faculty_department').val(faculty.department);
                            $('#edit-designation').val(faculty.designation);
                            $('#edit-faculty_email').val(faculty.email);
                            $('#edit-faculty_mobile').val(faculty.phone_number);
                            $('#edit-faculty_gender').val(faculty.gender);
                            $('#edit-faculty_hostel').val(faculty.hostel_id);
                            $('#edit-faculty_block').val(faculty.block);
                            $('#edit-faculty_floor').val(faculty.floor);
                            $('#edit-faculty_room').val(faculty.room_id);
                            $('#edit-faculty_room').data('selected-room', faculty.room_id);
                            $('#edit-faculty_fingerprint_id').val(faculty.fingerprint_id);
                            $('#edit-faculty_role').val(faculty.role);
                            $('#edit-faculty_additional_role').val(faculty.additional_role);

                            // Show the edit modal
                            const modal = new bootstrap.Modal(document.getElementById(
                                'facultyeditModal'));
                            modal.show();

                            // Load hostels for the edit form
                            $.ajax({
                                url: '../api.php',
                                type: 'POST',
                                data: {
                                    action: 'get_hostels'
                                },
                                dataType: 'html',
                                success: function (data) {
                                    if (data.trim() !== '') {
                                        $('#edit-faculty_hostel').html(
                                            '<option value="">Select Hostel</option>' +
                                            data);
                                        // Set the selected hostel
                                        if (faculty.hostel_id) {
                                            $('#edit-faculty_hostel').val(faculty
                                                .hostel_id);
                                        }
                                    } else {
                                        $('#edit-faculty_hostel').html(
                                            '<option value="">No hostels available</option>'
                                        );
                                    }
                                },
                                error: function (xhr, status, error) {
                                    console.error('Hostel load error:', status, error);
                                    $('#edit-faculty_hostel').html(
                                        '<option value="">Error loading hostels</option>'
                                    );
                                }
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Could not fetch faculty details',
                                'error');
                        }
                    }, 'json').fail(function () {
                        Swal.fire('Error', 'Failed to fetch faculty details. Please try again.',
                            'error');
                    });
                });

                $(document).on('click', '[data-action="view-faculty"]', function () {
                    const id = $(this).data('id');

                    // Fetch faculty details from API
                    $.post(api, {
                        action: 'get_faculty',
                        faculty_id: id
                    }, function (res) {
                        if (res.success) {
                            const faculty = res.data;

                            const val = (v) =>
                                v && v !== 'null' && v !== '' ? v :
                                    '<span class="text-muted">Not provided</span>';

                            let html = `
      <h5 class="fw-bold mb-2 text-primary">Faculty Information</h5>
      <table class="table table-bordered table-striped table-sm align-middle">
        <tbody>
    `;

                            // Faculty information
                            html +=
                                `<tr><th>Faculty ID</th><td>${val(faculty.faculty_id)}</td></tr>`;
                            html += `<tr><th>Name</th><td>${val(faculty.f_name)}</td></tr>`;
                            html +=
                                `<tr><th>Department</th><td>${val(formatDepartmentName(faculty.department))}</td></tr>`;
                            html +=
                                `<tr><th>Designation</th><td>${val(faculty.designation)}</td></tr>`;
                            html += `<tr><th>Email</th><td>${val(faculty.email)}</td></tr>`;
                            html += `<tr><th>Phone</th><td>${val(faculty.phone_number)}</td></tr>`;
                            html += `<tr><th>Gender</th><td>${val(faculty.gender)}</td></tr>`;
                            html += `<tr><th>Hostel</th><td>${val(faculty.hostel_name)}</td></tr>`;
                            html += `<tr><th>Room</th><td>${val(faculty.room_number)}</td></tr>`;
                            html += `<tr><th>Role</th><td>${val(faculty.role)}</td></tr>`;
                            html +=
                                `<tr><th>Additional Role</th><td>${val(faculty.additional_role)}</td></tr>`;
                            html +=
                                `<tr><th>Fingerprint ID</th><td>${val(faculty.fingerprint_id)}</td></tr>`;

                            html += `</tbody></table>`;

                            $('#facultyDetails').html(html);
                            new bootstrap.Modal('#facultyModal').show();
                        } else {
                            Swal.fire('Error', res.message || 'Could not fetch faculty details',
                                'error');
                        }
                    }, 'json').fail(function () {
                        Swal.fire('Error', 'Failed to fetch faculty details. Please try again.',
                            'error');
                    });
                });

                $(document).on('click', '[data-action="delete-faculty"]', function () {
                    const id = $(this).data('id');
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "This faculty will be deleted.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Implement faculty deletion
                            $.post(api, {
                                action: 'delete_faculty',
                                faculty_id: id
                            }, function (res) {
                                if (res.success) {
                                    Swal.fire('Deleted!', 'Faculty has been deleted.',
                                        'success');
                                    loadFacultyTable();
                                } else {
                                    Swal.fire('Error', res.message ||
                                        'Could not delete faculty', 'error');
                                }
                            }, 'json').fail(function () {
                                Swal.fire('Error',
                                    'Failed to delete faculty. Please try again.',
                                    'error');
                            });
                        }
                    });
                });

                // Handle faculty edit form submission
                document.getElementById("facultyeditForm").addEventListener("submit", function (e) {
                    e.preventDefault(); // Prevent normal form submission

                    const form = e.target;
                    const formData = new FormData(form);

                    // Add action parameter
                    formData.append("action", "update_faculty");

                    fetch("../api.php", {
                        method: "POST",
                        body: formData
                    })
                        .then(response => response.json()) // Expect JSON from server
                        .then(data => {
                            if (data.status === "success") {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: 'Faculty record updated successfully!',
                                    confirmButtonText: 'OK'
                                });

                                form.reset(); // Reset form after successful submission
                                // Close modal if inside one
                                const modal = bootstrap.Modal.getInstance(document.querySelector(
                                    "#facultyeditModal"));
                                if (modal) modal.hide();
                                loadFacultyTable();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: data.message || 'Failed to update faculty',
                                    confirmButtonText: 'OK'
                                });
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Something went wrong while submitting the form!',
                                confirmButtonText: 'OK'
                            });
                        });
                });

                // Handle form submission
                document.getElementById("studentForm").addEventListener("submit", function (e) {
                    e.preventDefault(); // Prevent normal form submission

                    const form = e.target;
                    const formData = new FormData(form);

                    // Check required fields
                    const requiredFields = [
                        'register_no', 'student_name', 'date_of_join', 'dob', 'admission_type',
                        'department', 'year_of_study', 'academic_batch', 'fingerprint_id', 'gender',
                        'stay_type', 'student_no', 'father_name', 'mother_name', 'father_no',
                        'mother_no',
                        'approval_no', 'alt_approval_no', 'language', 'email'
                    ];

                    let missingFields = [];
                    for (let field of requiredFields) {
                        if (!formData.get(field) || formData.get(field).trim() === '') {
                            missingFields.push(field);
                        }
                    }

                    // Check conditional fields - if any hostel field is filled, all must be filled
                    const hostelValue = formData.get('hostel');
                    const blockValue = formData.get('block');
                    const floorValue = formData.get('floor');
                    const roomValue = formData.get('room');

                    // Debug output
                    console.log('Student form data:', {
                        hostelValue,
                        blockValue,
                        floorValue,
                        roomValue,
                        hostelFilled: !!hostelValue && hostelValue.trim() !== '',
                        blockFilled: !!blockValue && blockValue.trim() !== '',
                        floorFilled: !!floorValue && floorValue.trim() !== '',
                        roomFilled: !!roomValue && roomValue.trim() !== ''
                    });

                    // Check if hostel options are available
                    const hostelSelect = document.getElementById('hostel');
                    const hasHostelOptions = hostelSelect && hostelSelect.options.length >
                        1; // More than just the default option

                    console.log('Student hostel options available:', hasHostelOptions, 'Options count:',
                        hostelSelect ? hostelSelect.options.length : 'No select found');

                    // If hostel options are available and any hostel field is filled, all must be filled
                    if (hasHostelOptions && (hostelValue || blockValue || floorValue || roomValue)) {
                        if (!hostelValue || hostelValue.trim() === '') {
                            missingFields.push('hostel');
                        }
                        if (!blockValue || blockValue.trim() === '') {
                            missingFields.push('block');
                        }
                        if (!floorValue || floorValue.trim() === '') {
                            missingFields.push('floor');
                        }
                        if (!roomValue || roomValue.trim() === '') {
                            missingFields.push('room');
                        }
                    }

                    // Check temporary stay dates if stay type is temporary
                    if (formData.get('stay_type') === 'Temporary') {
                        if (!formData.get('from_date') || formData.get('from_date').trim() === '') {
                            missingFields.push('from_date');
                        }
                        if (!formData.get('to_date') || formData.get('to_date').trim() === '') {
                            missingFields.push('to_date');
                        }
                    }

                    // Debug output
                    console.log('Student missing fields:', missingFields);
                    console.log('Student form data entries:', [...formData.entries()]);

                    if (missingFields.length > 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Missing Fields',
                            text: "Please fill all required fields. Missing: " + missingFields.join(
                                ', '),
                            confirmButtonText: 'OK'
                        });
                        return;
                    }

                    formData.append("action", "create_student"); // Add action parameter

                    fetch("../api.php", {
                        method: "POST",
                        body: formData
                    })
                        .then(response => response.json()) // Expect JSON from server
                        .then(data => {
                            if (data.status === "success") {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: 'Student record created successfully!',
                                    confirmButtonText: 'OK'
                                });

                                form.reset(); // Reset form after successful submission
                                // Optional: Close modal if inside one
                                const modal = bootstrap.Modal.getInstance(document.querySelector(
                                    "#studentregModal"));
                                loadTable();
                                if (modal) modal.hide();
                                loadTable();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: data.message || 'Failed to create student',
                                    confirmButtonText: 'OK'
                                });
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Something went wrong while submitting the form!',
                                confirmButtonText: 'OK'
                            });
                        });
                });

                document.getElementById("studenteditForm").addEventListener("submit", function (e) {
                    e.preventDefault(); // Prevent normal form submission

                    const form = e.target;
                    const formData = new FormData(form);

                    // Check required fields for student edit
                    const requiredFields = [
                        'register_no', 'student_name', 'date_of_join', 'dob', 'admission_type',
                        'department', 'year_of_study', 'academic_batch', 'fingerprint_id', 'gender',
                        'student_no', 'father_name', 'mother_name', 'father_no', 'mother_no',
                        'approval_no', 'alt_approval_no', 'language', 'email'
                    ];

                    let missingFields = [];
                    for (let field of requiredFields) {
                        if (!formData.get(field) || formData.get(field).trim() === '') {
                            missingFields.push(field);
                        }
                    }

                    // Debug output
                    console.log('Student edit missing fields:', missingFields);
                    console.log('Student edit form data entries:', [...formData.entries()]);

                    if (missingFields.length > 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Missing Fields',
                            text: "Please fill all required fields. Missing: " + missingFields.join(
                                ', '),
                            confirmButtonText: 'OK'
                        });
                        return;
                    }

                    formData.append("action", "update_student"); // Add action parameter

                    fetch("../api.php", {
                        method: "POST",
                        body: formData
                    })
                        .then(response => response.json()) // Expect JSON from server
                        .then(data => {
                            if (data.status === "success") {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: 'Student record updated successfully!',
                                    confirmButtonText: 'OK'
                                });

                                form.reset(); // Reset form after successful submission
                                // Optional: Close modal if inside one
                                let modale = bootstrap.Modal.getInstance(document.querySelector(
                                    "#studenteditModal"));

                                if (modale) modale.hide();
                                loadTable();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: data.message || 'Failed to update student',
                                    confirmButtonText: 'OK'
                                });
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Something went wrong while submitting the form!',
                                confirmButtonText: 'OK'
                            });
                        });
                });

                // Handle faculty form submission
                document.getElementById("facultyForm").addEventListener("submit", function (e) {
                    e.preventDefault(); // Prevent normal form submission

                    const form = e.target;
                    const formData = new FormData(form);

                    // No validation - just submit the form directly
                    formData.append("action", "create_faculty"); // Add action parameter

                    fetch("../api.php", {
                        method: "POST",
                        body: formData
                    })
                        .then(response => response.json()) // Expect JSON from server
                        .then(data => {
                            if (data.status === "success") {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: 'Faculty record created successfully!',
                                    confirmButtonText: 'OK'
                                });

                                form.reset(); // Reset form after successful submission
                                // Close modal if inside one
                                const modal = bootstrap.Modal.getInstance(document.querySelector(
                                    "#facultyregModal"));
                                if (modal) modal.hide();
                                loadFacultyTable();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: data.message || 'Failed to create faculty',
                                    confirmButtonText: 'OK'
                                });
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Something went wrong while submitting the form!',
                                confirmButtonText: 'OK'
                            });
                        });
                });
                document.getElementById("gender").addEventListener("change", function () {
                    let value = this.value;
                    $.ajax({
                        url: '../api.php',
                        type: 'POST',
                        data: {
                            action: 'get_hostels',
                            gender: value

                        },
                        dataType: 'html',
                        success: function (data) {
                            console.log('Hostel data received:', data);
                            if (data.trim() !== '') {
                                $('#hostel').html('<option value="">Select Hostel</option>' + data);
                                $('#faculty_hostel').html('<option value="">Select Hostel</option>' + data);
                            } else {
                                console.warn('No hostel data received');
                                $('#hostel, #faculty_hostel').html(
                                    '<option value="">No hostels available</option>');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Hostel load error:', status, error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Failed to load hostels. Please refresh the page.',
                                confirmButtonText: 'OK'
                            });
                            $('#hostel, #faculty_hostel').html(
                                '<option value="">Error loading hostels</option>');
                        }
                    });
                    ஃ
                    // use value here
                });
                // Load hostels
                $.ajax({
                    url: '../api.php',
                    type: 'POST',
                    data: {
                        action: 'get_hostels',

                    },
                    dataType: 'html',
                    success: function (data) {
                        console.log('Hostel data received:', data);
                        if (data.trim() !== '') {
                            $('#hostel').html('<option value="">Select Hostel</option>' + data);
                            $('#faculty_hostel').html('<option value="">Select Hostel</option>' + data);
                        } else {
                            console.warn('No hostel data received');
                            $('#hostel, #faculty_hostel').html(
                                '<option value="">No hostels available</option>');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Hostel load error:', status, error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Failed to load hostels. Please refresh the page.',
                            confirmButtonText: 'OK'
                        });
                        $('#hostel, #faculty_hostel').html(
                            '<option value="">Error loading hostels</option>');
                    }
                });

                $.ajax({
                    url: '../api.php',
                    type: 'POST',
                    data: {
                        action: 'get_academic_batch'
                    },
                    dataType: 'html', // ensures jQuery treats response as HTML
                    success: function (data) {
                        $('#academic_batch').append(data);
                        $('#edit-academic_batch').append(data);
                    },
                    error: function (xhr, status, error) {
                        console.error('academic batch load error:', error);
                    }
                });

                // Load rooms dynamically
                $('#hostel, #block, #floor').change(function () {
                    let hostel = $('#hostel').val();
                    let block = $('#block').val();
                    let floor = $('#floor').val();

                    if (hostel && block && floor) {
                        $.ajax({
                            url: '../api.php',
                            type: 'POST',
                            data: {
                                action: 'get_rooms',
                                hostel_id: hostel,
                                block: block,
                                floor: floor
                            },
                            dataType: 'html',
                            success: function (data) {
                                console.log('Room data:', data); // debug
                                $('#room').html('<option value="">Select Room</option>' + data);
                            },
                            error: function (xhr, status, error) {
                                console.log('Room load error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Failed to load rooms. Please try again.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    } else {
                        $('#room').html('<option value="">Select Room</option>');
                    }
                });

                // Load faculty rooms dynamically
                $('#faculty_hostel, #faculty_block, #faculty_floor').change(function () {
                    let hostel = $('#faculty_hostel').val();
                    let block = $('#faculty_block').val();
                    let floor = $('#faculty_floor').val();

                    if (hostel && block && floor) {
                        $.ajax({
                            url: '../api.php',
                            type: 'POST',
                            data: {
                                action: 'get_faculty_rooms',
                                hostel_id: hostel,
                                block: block,
                                floor: floor
                            },
                            dataType: 'html',
                            success: function (data) {
                                console.log('Faculty room data:', data); // debug
                                $('#faculty_room').html(
                                    '<option value="">Select Room</option>' + data);
                            },
                            error: function (xhr, status, error) {
                                console.log('Faculty room load error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Failed to load faculty rooms. Please try again.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    } else {
                        $('#faculty_room').html('<option value="">Select Room</option>');
                    }
                });

                // Load faculty rooms dynamically for edit form
                $('#edit-faculty_hostel, #edit-faculty_block, #edit-faculty_floor').change(function () {
                    let hostel = $('#edit-faculty_hostel').val();
                    let block = $('#edit-faculty_block').val();
                    let floor = $('#edit-faculty_floor').val();

                    if (hostel && block && floor) {
                        $.ajax({
                            url: '../api.php',
                            type: 'POST',
                            data: {
                                action: 'get_faculty_rooms',
                                hostel_id: hostel,
                                block: block,
                                floor: floor
                            },
                            dataType: 'html',
                            success: function (data) {
                                console.log('Faculty room data:', data); // debug
                                $('#edit-faculty_room').html(
                                    '<option value="">Select Room</option>' + data);
                                // Set the selected room if available
                                if ($('#edit-faculty_room').data('selected-room')) {
                                    $('#edit-faculty_room').val($('#edit-faculty_room').data(
                                        'selected-room'));
                                }
                            },
                            error: function (xhr, status, error) {
                                console.log('Faculty room load error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Failed to load faculty rooms. Please try again.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    } else {
                        $('#edit-faculty_room').html('<option value="">Select Room</option>');
                    }
                });
                function loadTable() {
                    showLoader();
                    $.post(api, {
                        action: 'list_students'
                    }, function (res) {
                        console.log("Parsed response:", res);

                        if (!res || !res.success) {
                            Swal.fire('Error', 'Failed to load data: ' + (res.message || 'Unknown error'),
                                'error');
                            hideLoader();
                            return;
                        }

                        // Clear existing data
                        studentTable.clear();

                        let i = 1;
                        if (res.data && Array.isArray(res.data)) {
                            res.data.forEach(r => {
                                studentTable.row.add({
                                    "sno": i++,
                                    "roll_number": r.roll_number || '',
                                    "name": r.name || '',
                                    "hostel_name": r.hostel_name || '',
                                    "room_number": r.room_number || '',
                                    "approval_no": r.approval_no || '',
                                    "fingerprint_id": r.fingerprint_id || '',
                                    "actions": `
                                    <div class=\"action-btns\">
                                    <button class=\"btn btn-sm btn-success btn-action\" data-action=\"edit\" data-id=\"${r.student_id}\" data-bs-toggle=\"tooltip\" data-bs-title=\"Edit Student\"><i class=\"bi bi-pencil-square\"></i></button>
                                    <button class=\"btn btn-sm btn-warning btn-action\" data-action=\"view\" data-id=\"${r.student_id}\" data-bs-toggle=\"tooltip\" data-bs-title=\"View Student\">
                                    <i class=\"bi bi-card-list\"></i>
                                    </button>
                                    <button class=\"btn btn-sm btn-danger btn-action\" data-action=\"delete\" data-id=\"${r.student_id}\" data-bs-toggle=\"tooltip\" data-bs-title=\"Delete Student\">
                                    <i class=\"bi bi-trash-fill\"></i>
                                    </button>
                                    </div>
                                    `
                                });
                            });
                        }
                        // Destroy existing tooltips before redrawing
                        $('.action-btns [data-bs-toggle="tooltip"]').tooltip('dispose');
                        studentTable.draw();
                        // Initialize tooltips for student action buttons
                        $('.action-btns [data-bs-toggle="tooltip"]').tooltip();
                        hideLoader();

                    }, 'json')
                        .fail(function (xhr, status, error) {
                            console.error("AJAX Error:", error);
                            Swal.fire('Error',
                                'Failed to load data. Please check your connection and try again.', 'error');
                            hideLoader();
                        });
                }
                // Load table once the page is ready
                loadTable();

                // Load faculty table when faculty tab is shown
                $('button[data-bs-target="#faculty-tab-pane"]').on('shown.bs.tab', function (e) {
                    loadFacultyTable();
                });
            });
        </script>

        <!-- Footer -->
        <?php include '../assets/footer.php'; ?>
    </div>
</body>

</html>