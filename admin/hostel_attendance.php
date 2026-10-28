<?php session_start();
include './admin_scope.php';
if (!is_any_admin_role()) {
    header('Location: ../login');
    exit;
}
$scopeGender = get_hostel_gender_scope_for_role();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Management </title>
    <link rel="icon" type="image/png" sizes="32x32" href="image/icons/mkce_s.png">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-5/bootstrap-5.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="block-student.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <style>
        
        .welcome-text {
            font-weight: 800;
            background: linear-gradient(to right, #3f4c6b, #70a2c5, #48729c);
            background-size: 200% auto;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shimmer 3s linear infinite;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
            position: relative;
            display: inline-block;
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

        body {
            background-color: #f8f9fc;

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


        
        .loader-container {
            position: fixed;
            left: var(--sidebar-width);
            right: 0;
            top: var(--topbar-height);
            bottom: calc(var(--footer-height) + 20px);
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 500;
            transition: left 0.3s ease;
            pointer-events: none;
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

        
        .action-btns button {
            margin: 2px;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .action-btns .btn-mark-present {
            background-color: #28a745;
            color: white;
            border: none;
        }

        .action-btns .btn-mark-present:hover {
            background-color: #1e7e34;
        }

        .action-btns .btn-present {
            background-color: #28a745;
            color: white;
            border: none;
        }

        .action-btns .btn-present:hover {
            background-color: #1e7e34;
        }

        .action-btns .btn-leave {
            background-color: #1A2980;
            color: white;
            border: none;
        }

        .action-btns .btn-leave:hover {
            background-color: #141f60;
        }

        .action-btns .btn-block-student {
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .action-btns .btn-block-student:hover {
            background-color: #c82333;
        }

        
        .filter-section {
            display: flex;
            gap: 15px;
            align-items: center;
        }

       
        .dropdown-item .badge {
            min-width: 2px;
            
        }



        
        .filter-row {
            padding: 20px 20px 0 20px;
        }
        
        
        .filter-and-report {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .data-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin: 20px;
            padding: 20px;
        }

        .data-card-header {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #4e73df;
            border-bottom: 2px solid #f8f9fc;
            padding-bottom: 10px;
        }
        
        
        .filter-and-report .form-select,
        .filter-and-report .form-control {
            max-width: 160px; 
            border-radius: 0.35rem;
            height: calc(1.5em + 0.75rem + 2px);
            font-size: 1rem;
        }

        .dataTables_wrapper .row:first-child {
            margin-bottom: 10px; 
        }
        
        .attendance-filters-title {
            font-size: 1rem;
            font-weight: 600;
            color: #858796;
            margin-bottom: 5px;
        }

        .filter-row .dropdown {
            min-width: 15px; 
        }
        
        .grad-table td,
        .grad-table th {
            border: 0.1px solid #dee2e6;
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

        td,
        th {
            padding: 5px !important;
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




        .status-btn {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 8px;
            color: green;
            font-weight: 600;
            font-size: 14px;
            text-align: center;
            pointer-events: none;
        }

        .status-btn.present {
            background-color: #28a745;
            
        }

        .status-btn.absent {
            background-color: #dc3545;
            
        }

        .unblock-btn {
            padding: 5px 10px;
            background-color: #FFD101;
            color: black;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .block-form {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #fff;
            padding: 20px 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            flex-wrap: wrap;
            
        }

        
        .block-form .form-group {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 220px;
        }

        /* Label styling */
        .block-form label {
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }

        /* Inputs and textareas */
        .block-form input[type="text"],
        .block-form textarea {
            padding: 10px 14px;
            border-radius: 10px;
            border: 1.5px solid #ccc;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            resize: none;
        }

        .block-form input[type="text"]:focus,
        .block-form textarea:focus {
            border-color: #ff6b6b;
            box-shadow: 0 0 6px rgba(255, 107, 107, 0.4);
            outline: none;
        }

        /* Button styling */
        .block-form .btn-submit {
            background: #F1373A;
            border: none;
            color: white;
            padding: 12px 20px;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(255, 107, 107, 0.3);
            white-space: nowrap;
        }

        .block-form .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(255, 107, 107, 0.4);
        }

        /* Responsive layout */
        @media (max-width: 768px) {
            .block-form {
                flex-direction: column;
                align-items: stretch;
            }

            .block-form .btn-submit {
                width: 100%;
            }
        }
 
        .nav-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            /* spacing between text and count */
        }

        .nav-link p {
            margin: 0;
            display: inline;
            font-weight: 600;
            font-size: 0.9em;
        }


        
        /* Optional: smooth transitions */
        .nav-link {
            border-radius: 25px;
            padding: 10px 20px;
            transition: all 0.3s ease;
            font-weight: 600;
        }
 /* Custom CSS for Professional Dropdown Style */
#attendance-tabs-dropdown .dropdown-toggle {
    /* Base professional look for the main button */
    background-color: #f8f9fa !important;
    color: #343a40 !important;
    border: 1px solid #ced4da !important;
    border-radius: 0.35rem;
    padding: 8px 15px; /* Consistent padding */
    font-weight: 600;
    
    /* Ensure text alignment and icon position */
    text-align: left;
    justify-content: space-between;
    
    /* Hover state for feedback */
    transition: all 0.2s ease-in-out;
}
#attendance-tabs-dropdown .dropdown-toggle:hover {
    background-color: #e9ecef !important;
}

/* Ensure dropdown menu uses full width and remove default padding */
#attendance-tabs-dropdown .dropdown-menu {
    padding: 0 !important; 
    max-height: 400px; 
    overflow-y: auto;
}

/* Ensure dropdown items are full width, use consistent padding, and look clean */
#attendance-tabs-dropdown .dropdown-item {
    padding: 12px 16px !important; /* Consistent item padding */
    border-radius: 0 !important; 
    color: #343a40;
    font-weight: 500;
    transition: background-color 0.15s ease; /* Enable smooth transition for hover */
}

/* 🎯 The Requested Hover Effect */
#attendance-tabs-dropdown .dropdown-item:hover {
    background-color: #e6f7ff !important; /* Very light blue background on hover */
    color: #0056b3; /* Darker text on hover for contrast */
}

/* Active State (when selected) */
#attendance-tabs-dropdown .dropdown-item.active {
    background-color: #007bff !important; /* Clean blue highlight */
    color: white !important;
    /* Remove hover effect from the currently active item */
    pointer-events: none; 
}
#attendance-tabs-dropdown .dropdown-item.active:hover {
    background-color: #007bff !important;
    color: white !important;
}

/* Fix for Bootstrap modals - ensure they appear above backdrop */
.modal {
    z-index: 1055 !important;
}

.modal-backdrop {
    z-index: 1050 !important;
}

.modal-dialog {
    z-index: 1056 !important;
    position: relative;
}

.modal-content {
    position: relative;
    background-color: #fff;
    border: 1px solid rgba(0,0,0,.2);
    border-radius: 0.3rem;
    outline: 0;
}

.modal.fade .modal-dialog {
    transition: transform .3s ease-out;
}

.modal.show .modal-dialog {
    transform: none;
}

/* Action buttons styling for icon-only buttons */
.action-btns {
    display: flex;
    gap: 0.25rem;
    justify-content: center;
    align-items: center;
}

.action-btns .btn {
    padding: 0.375rem 0.5rem;
    min-width: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.action-btns .btn i {
    font-size: 1rem;
}

.action-btns .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.action-btns .btn-mark-present:hover {
    background-color: #218838 !important;
}

.action-btns .btn-mark-leave:hover {
    background-color: #0056b3 !important;
}

.action-btns .btn-block-student:hover {
    background-color: #c82333 !important;
}

/* Reduce Action column width */
#absent-table th:last-child,
#absent-table td:last-child {
    width: 120px;
    max-width: 120px;
    white-space: nowrap;
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
<?php include '../assets/footer.php'; ?>
        <?php
        // Ensure DB connection is available for dynamic hostel loading
        // db.php lives in the same folder as this file (dash_attend/db.php)
        include '../db.php';

        // START: DYNAMIC HOSTEL LOADING LOGIC
        $hostel_result = null;
        if (isset($conn)) {
            // Query the database to get the unique hostel names
            $hostel_query = "SELECT DISTINCT hostel_name FROM hostels WHERE hostel_name IS NOT NULL AND hostel_name != ''";
            if ($scopeGender !== null) {
                $hostel_query .= " AND gender = '" . $conn->real_escape_string($scopeGender) . "'";
            }
            $hostel_query .= " ORDER BY hostel_name ASC";
            $hostel_result = $conn->query($hostel_query);
        }
        // END: DYNAMIC HOSTEL LOADING LOGIC
        ?>
<div class="breadcrumb-area custom-gradient">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Hostel Attendance</li>
                </ol>
            </nav>
        </div>

        <div class="data-card filter-container">
            <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3">
                
                <div class="d-flex flex-column" style="flex-grow: 1;">
                    <div class="attendance-filters-title">Filter</div>
                    <div class="dropdown" id="attendance-tabs-dropdown">
                        <button class="btn btn-success dropdown-toggle d-flex align-items-center" 
                                type="button" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false" 
                                id="active-tab-display"
                                style="background: linear-gradient(135deg, #dbe5deff, #c9dad5ff); border: none; padding: 8px 15px; font-weight: 600; font-size: 1rem;">
Student Status
                            
                        </button>
<ul class="dropdown-menu w-10" id="pills-tab" role="tablist" style="border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 0;">
    <li role="presentation">
        <a class="dropdown-item active" 
            href="#" 
            data-bs-toggle="pill" 
            data-bs-target="#pills-present" 
            id="pills-present-tab" 
            onclick="loadPresentStudents(); updateMainTabDisplay('Present Students');" 
            aria-controls="pills-present" 
            aria-selected="true">
            <span>Present Students</span>
        </a>
    </li>
    <li role="presentation">
        <a class="dropdown-item" 
            href="#" 
            data-bs-toggle="pill" 
            data-bs-target="#pills-absent" 
            id="pills-absent-tab" 
            onclick="loadAbsentStudents(); updateMainTabDisplay('Absent Students');" 
            aria-controls="pills-absent" 
            aria-selected="false">
            <span>Absent Students</span>
        </a>
    </li>
    <li role="presentation">
        <a class="dropdown-item" 
            href="#" 
            data-bs-toggle="pill" 
            data-bs-target="#pills-leave" 
            id="pills-leave-tab" 
            onclick="loadOnLeaveStudents(); updateMainTabDisplay('Students on Leave');" 
            aria-controls="pills-leave" 
            aria-selected="false">
            <span>Students on Leave</span>
        </a>
    </li>
    <li role="presentation">
        <a class="dropdown-item" 
            href="#" 
            data-bs-toggle="pill" 
            data-bs-target="#pills-late-entry" 
            id="pills-late-entry-tab" 
            onclick="loadLateEntryStudents(); updateMainTabDisplay('Late Entry Students');" 
            aria-controls="pills-late-entry" 
            aria-selected="false">
            <span>Late Entry Students</span>
        </a>
    </li>
    <li role="presentation">
        <a class="dropdown-item" 
            href="#" 
            data-bs-toggle="pill" 
            data-bs-target="#pills-blocked" 
            id="pills-blocked-tab" 
            onclick="loadBlockedStudents(); updateMainTabDisplay('Blocked Students');" 
            aria-controls="pills-blocked" 
            aria-selected="false">
            <span>Blocked Students</span>
        </a>
    </li>
</ul>
                    </div>
                </div>

                <div class="filter-and-report">
                    <div class="d-flex flex-column">
                        <div class="attendance-filters-title">Hostel</div>
                        <select id="hostel_filter" class="form-select" onchange="applyFilters()">
                            <option value="">All Hostels</option>
                            <?php
                            // Dynamically populate options from DB
                            if ($hostel_result && $hostel_result->num_rows > 0) {
                                // PHP will reset the pointer after the first loop or error if no data
                                $hostel_result->data_seek(0);
                                while ($row = $hostel_result->fetch_assoc()) {
                                    $hostelName = htmlspecialchars($row['hostel_name']);
                                    echo "<option value=\"{$hostelName}\">{$hostelName}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="d-flex flex-column">
                        <div class="attendance-filters-title">Date</div>
                        <input type="date" id="date_input" class="form-control" onchange="applyFilters()">
                    </div>

                    <div class="d-flex flex-column align-self-end">
                         <div class="attendance-filters-title" style="color:transparent;">Action</div>
                        <button type="button" class="btn btn-success d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#reportModal" style="white-space: nowrap;">
                            <i class="bi bi-download me-2"></i> Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

<div class="data-card" style="margin-top: 20px;">
    <div class="tab-content" id="pills-tabContent" style="padding: 0;">
    
    <div class="tab-pane fade show active" id="pills-present" role="tabpanel" aria-labelledby="pills-present-tab">
        <div class="data-card">
            <div class="data-card-header text-success">
                Present Students <span id="present-count" class="badge bg-success ms-2">0</span>
            </div>
            <table class="grad-table" id="present-table">
                <thead>
                    <tr>
                        <th>Roll Number</th>
                        <th>Name</th>
                        <th>Department</th>
                                <th>Year</th>
                                <th>Floor</th>
                                <th>Room</th>
                        <th>Marked At</th>
                        <th>Reason</th>
                        <th>Status</th>

                    </tr>
                </thead>
                <tbody id="students-present-body">
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="pills-absent" role="tabpanel" aria-labelledby="pills-absent-tab">
        <div class="data-card">
            <div class="data-card-header text-danger">
                Absent Students <span id="absent-count" class="badge bg-danger ms-2">0</span>
            </div>
            <table class="grad-table" id="absent-table">
                <thead>
                    <tr>
                        <th>Roll Number</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Floor</th>
                        <th>Room</th>
                     
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="students-absent-body">
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="pills-leave" role="tabpanel" aria-labelledby="pills-leave-tab">
        <div class="data-card">
            <div class="data-card-header text-info">
                Students on Leave <span id="leave-count" class="badge bg-info ms-2">0</span>
            </div>
            <table class="grad-table" id="leave-table">
                <thead>
                    <tr>
                        <th>Roll Number</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Floor</th>
                        <th>Room</th>
                        <th>Reason</th>
                        <th>Leave Type</th>
                        <th>Marked At</th>
                    </tr>
                </thead>
                <tbody id="students-leave-body">
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="pills-late-entry" role="tabpanel" aria-labelledby="pills-late-entry-tab">
    <div class="data-card">
        <div class="data-card-header text-warning">
            Late Entry Students <span id="late-entry-count" class="badge bg-warning text-dark ms-2">0</span>
        </div>
        <table class="grad-table" id="late-entry-table">
            <thead>
                <tr>
                    <th>Roll Number</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Year</th>
                    <th>Floor</th>
                    <th>Room</th>
                    <th>Marked At</th>
                    
                </tr>
            </thead>
            <tbody id="students-late-entry-body">
            </tbody>
        </table>
    </div>
</div>

    <div class="tab-pane fade" id="pills-blocked" role="tabpanel" aria-labelledby="pills-blocked-tab">
        <div class="data-card">
            <div class="data-card-header text-secondary">
                Blocked Students <span id="blocked-count" class="badge bg-secondary ms-2">0</span>
            </div>
            <table class="grad-table" id="blocked-table">
                <thead>
                    <tr>
                        <th>Roll Number</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Floor</th>
                        <th>Room</th>
                        <th>Status</th>
                        <th>Blocked at</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="students-blocked-body">
                </tbody>
            </table>
        </div>
    </div>
</div>
</div><!-- Close data-card wrapper for tab content -->

    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="reportForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reportModalLabel">Select Report Type(s)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                             <label for="report_hostel_filter" class="form-label">Filter by Hostel </label>
                            <select id="report_hostel_filter" class="form-select">
                            <option value="">All Hostels</option>
                            <?php
                            // Dynamically populate options from DB
                            if ($hostel_result && $hostel_result->num_rows > 0) {
                                // PHP will reset the pointer after the first loop or error if no data
                                $hostel_result->data_seek(0);
                                while ($row = $hostel_result->fetch_assoc()) {
                                    $hostelName = htmlspecialchars($row['hostel_name']);
                                    echo "<option value=\"{$hostelName}\">{$hostelName}</option>";
                                }
                            }
                            ?>
                        </select>
                        </div>
<div class="modal-body">
<div class="form-check">
    <input class="form-check-input" type="checkbox" name="reports[]" value="present" id="presentCheckbox" checked>
    <label class="form-check-label" for="presentCheckbox">Present Students</label>
</div>
<div class="form-check">
    <input class="form-check-input" type="checkbox" name="reports[]" value="absent" id="absentCheckbox" checked>
    <label class="form-check-label" for="absentCheckbox">Absent Students</label>
</div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="reports[]" value="on_leave" id="leaveCheckbox" checked>
        <label class="form-check-label" for="leaveCheckbox">Students on Leave</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="reports[]" value="late_entry" id="lateEntryCheckbox" checked>
        <label class="form-check-label" for="lateEntryCheckbox">Late Entry Students</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="reports[]" value="blocked" id="blockedCheckbox" checked>
        <label class="form-check-label" for="blockedCheckbox">Blocked Students</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="reports[]" value="manual_present" id="manualPresentCheckbox">
        <label class="form-check-label" for="manualPresentCheckbox">Manual Present</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="reports[]" value="manual_leave" id="manualLeaveCheckbox">
        <label class="form-check-label" for="manualLeaveCheckbox">Manual Leave</label>
    </div>
    <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" id="floorWiseCheckbox" name="floor_wise" value="1">
        <label class="form-check-label" for="floorWiseCheckbox">Download Floor-wise Details</label>
    </div>
    <div class="text-danger mt-2" id="checkboxError" style="display:none;">Please select at least one report type.</div>
</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Generate PDF</button>
                    </div>
                </form>
            </div>
        </div>
    </div>




    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

    <!--script files-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

    <!--table data load-->
<script>

    const dateInput = document.getElementById("date_input");
    const today = new Date().toISOString().split("T")[0];

    $(document).ready(function() {
        // Initial load on page load
        // Automatic blocking disabled: admin will block manually when required
        // blockabsentees();
        loadPresentStudents(); // Now loads Present and Late Entry
        loadAbsentStudents();  // Now loads Absent and On Leave
        
        // Reload tables when switching tabs
        $('#pills-present-tab').on('click', function() {
            setTimeout(() => loadPresentStudents(), 100);
        });
        
        $('#pills-absent-tab').on('click', function() {
            setTimeout(() => loadAbsentStudents(), 100);
        });
        
        $('#pills-leave-tab').on('click', function() {
            setTimeout(() => loadOnLeaveStudents(), 100);
        });
        
        $('#pills-late-entry-tab').on('click', function() {
            setTimeout(() => loadLateEntryStudents(), 100);
        });
        
        $('#pills-blocked-tab').on('click', function() {
            setTimeout(() => loadBlockedStudents(), 100);
        });
        
        dateInput.addEventListener("change", function() {
            console.log("Date changed, reloading students...");
            // Ensure DataTables are destroyed before reloading content based on the new date
            if ($.fn.DataTable.isDataTable('#present-table')) {
                $('#present-table').DataTable().destroy();
            }
            if ($.fn.DataTable.isDataTable('#absent-table')) {
                $('#absent-table').DataTable().destroy();
            }
            if ($.fn.DataTable.isDataTable('#blocked-table')) {
                $('#blocked-table').DataTable().destroy();
            }
            loadPresentStudents();
            loadAbsentStudents();
            loadBlockedStudents();
            loadOnLeaveStudents(); 
        loadLateEntryStudents(); // Reload blocked just in case
        });
        dateInput.value = today;

        function blockabsentees() {
            $.ajax({
                url: '../api.php',
                method: 'POST',
                data: {
                    action: 'block_all_absent'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        loadBlockedStudents();
                        loadPresentStudents();
                        loadAbsentStudents();
                        loadOnLeaveStudents(); 
                        loadLateEntryStudents();    
                    } else {
                        // Suppress error if the API is intentionally failing/missing the action
                        // console.warn('Block absentees action failed or is missing.', response.message);
                    }
                },
                error: function() {
                    // console.error('AJAX error during block absentees action!');
                }
            });
        }



// Define these functions in GLOBAL scope (outside document.ready)
// Modal preparation functions have been replaced with direct SweetAlert2 implementation


$(document).ready(function() {
    // Event delegation for dynamically created buttons
    $(document).on('click', '.btn-mark-present', function() {
        const rollNumber = $(this).data('roll');
        console.log("Mark Present clicked via delegation:", rollNumber);
        
        // Show SweetAlert2 dialog with reason input
        Swal.fire({
            title: 'Mark as Present',
            html: `
                <p>Mark student <strong>${rollNumber}</strong> as present?</p>
                <div class="mb-3 text-start">
                    <label for="swal-present-reason" class="form-label">Reason for Manual Present</label>
                    <textarea id="swal-present-reason" class="swal2-input" placeholder="Enter reason for marking present" rows="3" style="height: 80px; width: 100%;"></textarea>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Mark Present',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const reason = document.getElementById('swal-present-reason').value;
                if (!reason || reason.trim() === '') {
                    Swal.showValidationMessage('Please enter a reason for marking present');
                    return false;
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const reason = result.value;
                
                // AJAX call to mark present
                $.ajax({
                    url: '../api.php',
                    method: 'POST',
                    data: {
                        action: 'mark_manual_present',
                        roll_number: rollNumber,
                        reason: reason,
                        selectedDate: document.getElementById("date_input").value
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Student marked as present successfully!',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                            // Delay reload to ensure DOM is ready
                            setTimeout(() => {
                                loadAbsentStudents();
                                loadPresentStudents();
                            }, 100);
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message || 'Failed to mark student as present',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Mark present error:", error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'Error processing request. Please try again.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });
    
    $(document).on('click', '.btn-mark-leave', function() {
        const rollNumber = $(this).data('roll');
        console.log("Mark Leave clicked via delegation:", rollNumber);
        
        // Fetch leave types from database
        $.ajax({
            url: '../api.php',
            method: 'POST',
            data: { action: 'get_leave_types' },
            dataType: 'json',
            success: function(leaveTypesResponse) {
                // Build leave type options
                let leaveTypeOptions = '';
                if (leaveTypesResponse.success && leaveTypesResponse.data.length > 0) {
                    leaveTypesResponse.data.forEach(type => {
                        leaveTypeOptions += `<option value="${type.LeaveType_ID}">${type.Leave_Type_Name}</option>`;
                    });
                } else {
                    leaveTypeOptions = '<option value="0">General Leave</option>';
                }
                
                // Show SweetAlert2 dialog with dynamic leave types
                Swal.fire({
                    title: 'Mark as Leave',
                    html: `
                        <p>Mark student <strong>${rollNumber}</strong> as on leave?</p>
                        <div class="mb-3 text-start">
                            <label for="swal-leave-type" class="form-label">Leave Type</label>
                            <select id="swal-leave-type" class="swal2-input" style="width: 100%; padding: 10px;">
                                ${leaveTypeOptions}
                            </select>
                        </div>
                        <div class="mb-3 text-start">
                            <label for="swal-leave-reason" class="form-label">Reason</label>
                            <textarea id="swal-leave-reason" class="swal2-input" placeholder="Enter reason for leave" rows="3" style="height: 80px; width: 100%;"></textarea>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#1A2980',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Mark as Leave',
                    cancelButtonText: 'Cancel',
                    preConfirm: () => {
                        const reason = document.getElementById('swal-leave-reason').value;
                        const leaveTypeId = document.getElementById('swal-leave-type').value;
                        if (!reason || reason.trim() === '') {
                            Swal.showValidationMessage('Please enter a reason for leave');
                            return false;
                        }
                        return { reason, leaveTypeId };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const { reason, leaveTypeId } = result.value;
                        
                        // Show loading
                        Swal.fire({
                            title: 'Processing...',
                            html: 'Marking student as leave',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // AJAX call to mark leave
                        $.ajax({
                            url: '../api.php',
                            method: 'POST',
                            data: {
                                action: 'mark_manual_leave',
                                roll_number: rollNumber,
                                reason: reason,
                                leave_type_id: leaveTypeId,
                                selectedDate: document.getElementById("date_input").value
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Student marked as leave successfully!',
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    });
                                    // Delay reload to ensure DOM is ready
                                    setTimeout(() => {
                                        loadAbsentStudents();
                                        loadOnLeaveStudents();
                                    }, 100);
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: response.message || 'Failed to mark student as leave',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error("Mark leave error:", error);
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Error processing request. Please try again.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    }
                });
            },
            error: function() {
                // Fallback if leave types can't be loaded
                console.error("Failed to load leave types");
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to load leave types. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
    
    $(document).on('click', '.btn-block-student', function() {
        const rollNumber = $(this).data('roll');
        console.log("Block clicked via delegation:", rollNumber);
        
        // Use SweetAlert2 instead of modal
        Swal.fire({
            title: 'Block Student',
            html: `
                <p>Block student <strong>${rollNumber}</strong>?</p>
                <textarea id="swal-block-reason" class="swal2-input" placeholder="Enter reason for blocking" rows="3" style="height: 80px; width: 100%;"></textarea>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Block Student',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const reason = document.getElementById('swal-block-reason').value;
                if (!reason || reason.trim() === '') {
                    Swal.showValidationMessage('Please enter a reason for blocking');
                    return false;
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const reason = result.value;
                
                // Show loading
                Swal.fire({
                    title: 'Processing...',
                    html: 'Blocking student',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // AJAX call to block student
                $.ajax({
                    url: '../api.php',
                    method: 'POST',
                    data: {
                        action: 'block_student',
                        roll_number: rollNumber,
                        reason: reason,
                        type: 'Both'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Student blocked successfully!',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                            // Delay reload to ensure DOM is ready
                            setTimeout(() => {
                                loadAbsentStudents();
                                loadBlockedStudents();
                            }, 100);
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message || 'Failed to block student',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Block student error:", error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'Error processing request. Please try again.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });
    
    // Modal handlers have been replaced with direct SweetAlert2 implementation in the button click handlers
});

function loadPresentStudents() {
    console.log("Loading Present students...");
    
    // Destroy existing DataTable instance
    if ($.fn.DataTable.isDataTable('#present-table')) {
        $('#present-table').DataTable().destroy();
    }
    
    let selectedDate = document.getElementById("date_input").value;
    let selectedHostel = document.getElementById("hostel_filter").value;
    
    $.ajax({
        url: '../api.php',
        method: 'POST',
        data: { 
            action: 'load_present_or_late', 
            selectedDate: selectedDate,
            hostel_filter: selectedHostel
        },
        dataType: 'json',
        success: function(response) {
            console.log("Present students API response:", response);
            const tbody = $('#students-present-body');
            tbody.empty(); // Only empty the tbody, not the entire table
            document.getElementById("present-count").innerText = response.data.length; 
            
            if (response.success && response.data.length > 0) {
                console.log("Building rows for", response.data.length, "present students");
                response.data.forEach(student => {
                    // Determine status badge and optional row highlight for Late Entry
                    const status = student.status || student.status || (student.status === undefined ? (student.status || student.attendance_status) : student.status);
                    let statusBadge = '<span class="badge bg-success">Present</span>';
                    let rowClass = '';
                    if (status === 'Late Entry' || student.status === 'Late Entry') {
                        statusBadge = '<span class="badge bg-warning text-dark">Late Entry</span>';
                        rowClass = 'table-warning';
                    }

                    const row = `
                        <tr class="${rowClass}">
                            <td>${student.roll_number}</td>
                            <td>${student.name}</td>
                            <td>${student.department}</td>
                            <td>${student.academic_batch}</td>
                            <td>${student.floor ?? '-'}</td>
                            <td>${student.room_number ?? '-'}</td>
                            <td>${student.marked_at}</td>
                            <td>${student.reason ?? '-'}</td>
                            <td>${statusBadge}</td>
                        </tr>`;
                    tbody.append(row);
                });

                console.log("Initializing DataTable for present-table");
                // Initialize DataTable with proper options
                $('#present-table').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [5, 10, 20, 50],
                    "order": [],
                    "destroy": true
                });
            } else {
                console.log("No present students found");
                tbody.append('<tr><td colspan="9" class="text-center text-muted">No Present Students Today</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error (loadPresentStudents):", error);
            console.error("Response Text:", xhr.responseText);
            $('#students-present-body').empty().append('<tr><td colspan="9" class="text-center text-danger">Error loading data.</td></tr>');
        }
    });
}
// MODIFIED: Function to load ONLY Absent students
function loadAbsentStudents() {
    console.log("Loading Absent students...");
    
    // Destroy DataTable if it exists
    if ($.fn.DataTable.isDataTable('#absent-table')) {
        $('#absent-table').DataTable().destroy();
    }
    
    let selectedDate = document.getElementById("date_input").value;
    let selectedHostel = document.getElementById("hostel_filter").value;
    
    $.ajax({
        url: '../api.php',
        method: 'POST',
        data: { 
            action: 'load_absent', 
            selectedDate: selectedDate,
            hostel_filter: selectedHostel
        },
        dataType: 'json',
        success: function(response) {
            console.log("Absent students API response:", response);
            const tbody = $('#students-absent-body');
            tbody.empty(); // Only empty the tbody, not the entire table
            document.getElementById("absent-count").innerText = response.data.length;
            
            if (response.success && response.data.length > 0) {
                console.log("Building rows for", response.data.length, "absent students");
                response.data.forEach(student => {
                    const row = `
    <tr>
        <td>${student.roll_number}</td>
        <td>${student.name}</td>
        <td>${student.department}</td>
        <td>${student.academic_batch}</td>
        <td>${student.floor ?? '-'}</td>
        <td>${student.room_number ?? '-'}</td>
      
        <td>
            <div class="action-btns d-flex gap-1 justify-content-center">
                <button type="button" class="btn btn-success btn-sm btn-mark-present" data-roll="${student.roll_number}" title="Mark Present" data-bs-toggle="tooltip" data-bs-placement="top">
                    <i class="bi bi-check-circle"></i>
                </button>
                <button type="button" class="btn btn-primary btn-sm btn-mark-leave" data-roll="${student.roll_number}" title="Mark Leave" data-bs-toggle="tooltip" data-bs-placement="top" style="background-color: #1A2980">
                    <i class="bi bi-calendar-x"></i>
                </button>
                <button type="button" class="btn btn-danger btn-sm btn-block-student" data-roll="${student.roll_number}" title="Block Student" data-bs-toggle="tooltip" data-bs-placement="top">
                    <i class="bi bi-lock-fill"></i>
                </button>
            </div>
        </td>
    </tr>`;
                    tbody.append(row);
                }); 
                
                console.log("Initializing DataTable for absent-table");
                // Initialize DataTable after adding rows
                $('#absent-table').DataTable({ 
                    "pageLength": 10, 
                    "lengthMenu": [5, 10, 20, 50], 
                    "order": [],
                    "destroy": true
                });
                
                // Initialize Bootstrap tooltips for action buttons
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            } else { 
                console.log("No absent students found");
                tbody.append('<tr><td colspan="7" class="text-center text-muted">No Absent Students Today</td></tr>'); 
            } 
        }, 
        error: function(xhr, status, error) { 
            console.error("AJAX Error (loadAbsentStudents):", error); 
            console.error("Response Text:", xhr.responseText);
            $('#students-absent-body').empty().append('<tr><td colspan="7" class="text-center text-danger">Error loading data.</td></tr>'); 
        } 
    }); 
}

 
    // NEW FUNCTION: Load ONLY On Leave students
function loadOnLeaveStudents() {
    console.log("Loading On Leave students...");
    
    // Destroy DataTable if it exists
    if ($.fn.DataTable.isDataTable('#leave-table')) {
        $('#leave-table').DataTable().destroy();
    }
    
    let selectedDate = document.getElementById("date_input").value;
    let selectedHostel = document.getElementById("hostel_filter").value;
    
    $.ajax({
        url: '../api.php',
        method: 'POST',
        data: { 
            action: 'load_on_leave', 
            selectedDate: selectedDate,
            hostel_filter: selectedHostel
        },
        dataType: 'json',
        success: function(response) {
            console.log("On Leave students API response:", response);
            const tbody = $('#students-leave-body');
            tbody.empty(); // Only empty the tbody, not the entire table
            document.getElementById("leave-count").innerText = response.data.length;

            if (response.success && response.data.length > 0) {
                console.log("Building rows for", response.data.length, "students on leave");
                response.data.forEach(student => {
                    const row = `
                        <tr>
                            <td>${student.roll_number}</td>
                            <td>${student.name}</td>
                            <td>${student.department}</td>
                            <td>${student.academic_batch}</td>
                            <td>${student.floor ?? '-'}</td>
                            <td>${student.room_number ?? '-'}</td>
                            <td>${student.reason ?? '-'}</td>
                            <td><span class="badge bg-info">${student.leave_type ?? 'General Leave'}</span></td>
                            <td>${student.marked_at}</td>
                        </tr>`;
                    tbody.append(row);
                });

                console.log("Initializing DataTable for leave-table");
                $('#leave-table').DataTable({
                    "pageLength": 10,
                    "lengthMenu": [5, 10, 20, 50],
                    "order": [],
                    "destroy": true
                });
            } else {
                console.log("No students on leave found");
                tbody.append('<tr><td colspan="8" class="text-center text-muted">No Students On Leave Today</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error (loadOnLeaveStudents):", error);
            console.error("Response Text:", xhr.responseText);
            $('#students-leave-body').empty().append('<tr><td colspan="8" class="text-center text-danger">Error loading data.</td></tr>');
        }
    });
}
    // NEW FUNCTION: Load ONLY Late Entry students for the new tab
    function loadLateEntryStudents() {
        console.log("Loading Late Entry students...");
        if ($.fn.DataTable.isDataTable('#late-entry-table')) { $('#late-entry-table').DataTable().destroy(); }
        
        let selectedDate = document.getElementById("date_input").value;
        let selectedHostel = document.getElementById("hostel_filter").value;
        
        $.ajax({
            url: '../api.php',
            method: 'POST',
            data: { 
                action: 'load_late_entry', 
                selectedDate: selectedDate,
                hostel_filter: selectedHostel
            }, 
            dataType: 'json',
            success: function(response) {
                console.log('Late entry API response:', response);
                const tbody = $('#students-late-entry-body');
                tbody.empty();

                // Defensive checks
                if (!response || response.success !== true || !Array.isArray(response.data)) {
                    document.getElementById("late-entry-count").innerText = 0;
                    const msg = (response && response.message) ? response.message : 'No Late Entry Students Today';
                    tbody.append('<tr><td colspan="7" class="text-center text-muted">' + msg + '</td></tr>');
                    return;
                }

                document.getElementById("late-entry-count").innerText = response.data.length || 0;

                if (response.data.length > 0) {
                    response.data.forEach(student => {
                        const row = `
                            <tr>
                                <td>${student.roll_number}</td>
                                <td>${student.name}</td>
                                <td>${student.department}</td>
                                <td>${student.academic_batch}</td>
                                <td>${student.floor ?? '-'}</td>
                                <td>${student.room_number ?? '-'}</td>
                                <td>${student.marked_at}</td>
                            </tr>`;
                        tbody.append(row);
                    });

                    // Initialize DataTable
                    $('#late-entry-table').DataTable({
                        "pageLength": 10,
                        "lengthMenu": [5, 10, 20, 50],
                        "order": [],
                        "destroy": true
                    });
                } else {
                    tbody.append('<tr><td colspan="7" class="text-center text-muted">No Late Entry Students Today</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error (loadLateEntryStudents):", error);
                console.error('Response text:', xhr.responseText);
                document.getElementById("late-entry-count").innerText = 0;
                $('#students-late-entry-body').empty().append('<tr><td colspan="7" class="text-center text-danger">Error loading data.</td></tr>');
            }
        });
    }


        window.loadBlockedStudents = function() {
            // Destroy DataTable if it exists
            if ($.fn.DataTable.isDataTable('#blocked-table')) {
                $('#blocked-table').DataTable().destroy();
            }
            
            let selectedDate = document.getElementById("date_input").value;
            let selectedHostel = document.getElementById("hostel_filter").value;
            
            $.ajax({
                url: '../api.php',
                method: 'POST',
                data: {
                    action: 'load_blocked',
                    selectedDate: selectedDate,
                    hostel_filter: selectedHostel
                },
                dataType: 'json',
                success: function(response) {
                    console.log("Blocked students API response:", response);
                    const tbody = $('#students-blocked-body');
                    tbody.empty();

                    // Validate response before using it
                    if (!response || response.success !== true) {
                        const msg = (response && response.message) ? response.message : 'Error loading data.';
                        document.getElementById("blocked-count").innerText = 0;
                        tbody.append('<tr><td colspan="9" class="text-center text-danger">' + msg + '</td></tr>');
                        return;
                    }

                    const rows = Array.isArray(response.data) ? response.data : [];
                    document.getElementById("blocked-count").innerText = rows.length;

                    if (rows.length > 0) {
                        console.log("Building rows for", rows.length, "blocked students");
                        rows.forEach(student => {
                            // Determine status badge color
                            let statusBadge = '';
                            const status = student.attendance_status;
                            
                            if (status === 'Present') {
                                statusBadge = '<span class="badge bg-success">Present</span>';
                            } else if (status === 'Absent') {
                                statusBadge = '<span class="badge bg-danger">Absent</span>';
                            } else if (status === 'On Leave') {
                                statusBadge = '<span class="badge bg-info">On Leave</span>';
                            } else if (status === 'Late Entry') {
                                statusBadge = '<span class="badge bg-warning text-dark">Late Entry</span>';
                            } else {
                                statusBadge = '<span class="badge bg-secondary">Not Marked</span>';
                            }
                            
                            const row = `
                                <tr>
                                    <td>${student.roll_number}</td>
                                    <td>${student.name}</td>
                                    <td>${student.department}</td>
                                    <td>${student.academic_batch}</td>
                                    <td>${student.floor ?? '-'}</td>
                                    <td>${student.room_number ?? '-'}</td>
                                    <td>${statusBadge}</td>
                                    <td>${student.blocked_at}</td>
                                    <td>
                                        <button class="unblock-btn" data-id="${student.blocked_id}"><i class="bi bi-unlock-fill"></i> Unblock</button>
                                    </td>
                                </tr>`;
                            tbody.append(row);
                        });
                        
                        console.log("Initializing DataTable for blocked-table");
                        $('#blocked-table').DataTable({
                            pageLength: 10,
                            lengthMenu: [5, 10, 20, 50],
                            order: [],
                            destroy: true
                        });
                    } else {
                        console.log("No blocked students found");
                        tbody.append('<tr><td colspan="9" class="text-center text-muted">No Blocked Students</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error (loadBlockedStudents):", error);
                    console.error("Response Text:", xhr.responseText);
                    $('#students-blocked-body').empty().append('<tr><td colspan="9" class="text-center text-danger">Error loading data.</td></tr>');
                }
            });
        }
        
        // Function to apply filters (hostel and date)
        function applyFilters() {
            console.log("Filters changed, reloading all tables...");
            loadPresentStudents();
            loadAbsentStudents();
            loadOnLeaveStudents();
            loadLateEntryStudents();
            loadBlockedStudents();
        }

        // Ensure hostel dropdown triggers immediate AJAX load for the active tab
        $(document).ready(function() {
            $('#hostel_filter').on('change', function() {
                console.log('Hostel filter changed (jQuery handler)');
                // Prefer updating only the active tab to reduce load
                const activePane = $('.tab-pane.show.active').attr('id');
                switch (activePane) {
                    case 'pills-present':
                        loadPresentStudents();
                        break;
                    case 'pills-absent':
                        loadAbsentStudents();
                        break;
                    case 'pills-leave':
                        loadOnLeaveStudents();
                        break;
                    case 'pills-late-entry':
                        loadLateEntryStudents();
                        break;
                    case 'pills-blocked':
                        loadBlockedStudents();
                        break;
                    default:
                        // Fallback: reload all
                        applyFilters();
                }
            });
        });
        
        // Block/Unblock Handlers (kept as is)
        $('#block-student-form').on('submit', function(e) {
            e.preventDefault();

            const rollNumber = $('#roll_number').val().trim();
            const reason = $('#reason').val().trim();

            if (!rollNumber || !reason) {
                alert('Please fill all fields');
                return;
            }

            const leaveChecked = $('#leave_checkbox').is(':checked');
            const outingChecked = $('#outing_checkbox').is(':checked');

            if (!leaveChecked && !outingChecked) {
                alert('Please select at least one type: Leave or Outing');
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

                    const msgDiv = $('#block-student-message');
                    if (response.success) {
                        msgDiv.html('<span style="color:green">' + response.message + '</span>');
                        $('#block-student-form')[0].reset();
                        loadBlockedStudents();
                        Swal.fire({
                            title: 'Success!',
                            text: 'Student blocked successfully!',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                        modal.hide();
                    } else {
                        msgDiv.html('<span style="color:red">' + response.message + '</span>');
                    }

                },
                error: function() {
                    alert('AJAX error while blocking student');
                }
            });
        });
        
        $(document).on('click', '.unblock-btn', function() {
            const blockedId = $(this).data('id');
            const button = $(this);

            // Confirm before unblocking
            Swal.fire({
                title: 'Unblock Student?',
                text: 'Are you sure you want to unblock this student?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Unblock',
                cancelButtonText: 'Cancel'
            }).then((res) => {
                if (!res.isConfirmed) return;

                // Disable the button and show a small spinner/state
                button.prop('disabled', true).data('orig-html', button.html());
                button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Unblocking');

                // Send request
                $.ajax({
                    url: '../api.php',
                    method: 'POST',
                    data: {
                        action: 'unblock_student',
                        blocked_id: blockedId
                    },
                    timeout: 10000,
                    dataType: 'json'
                }).done(function(response) {
                    if (response && response.success) {
                        loadAbsentStudents();
                        // remove row visually
                        button.closest('tr').fadeOut(300, function() { $(this).remove(); });
                        Swal.fire({ title: 'Success!', text: response.message || 'Student unblocked successfully!', icon: 'success' });
                    } else {
                        console.error('Unblock failed response:', response);
                        const msg = (response && response.message) ? response.message : 'Something went wrong!';
                        Swal.fire({ title: 'Error!', text: msg, icon: 'error' });
                    }
                }).fail(function(xhr, status, error) {
                    console.error('AJAX error (unblock_student):', status, error, xhr.responseText);
                    let text = 'Something went wrong!';
                    // Try to parse JSON body if provided
                    try {
                        const parsed = JSON.parse(xhr.responseText || '{}');
                        if (parsed && parsed.message) text = parsed.message;
                    } catch (e) {
                        // If not JSON, and statusText is meaningful, use it
                        if (xhr.responseText && xhr.responseText.trim()) text = xhr.responseText.trim();
                        else if (status) text = status;
                    }
                    Swal.fire({ title: 'Error!', text: text, icon: 'error' });
                }).always(function() {
                    // restore button state
                    try { button.prop('disabled', false).html(button.data('orig-html')); } catch (e) {}
                });
            });
        });
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const reportForm = document.getElementById("reportForm");
        const checkboxError = document.getElementById("checkboxError");

        reportForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            const selectedDate = document.getElementById("date_input").value;
            const selectedHostel = document.getElementById("report_hostel_filter").value;
            // Collects all checked report values, including 'late_entry' and 'on_leave'
            const selectedReports = Array.from(document.querySelectorAll('input[name="reports[]"]:checked'))
                .map(cb => cb.value);

            if (selectedReports.length === 0) {
                checkboxError.style.display = "block";
                return;
            } else {
                checkboxError.style.display = "none";
            }

            const floorWise = document.getElementById('floorWiseCheckbox').checked;
            const formData = new FormData();
            formData.append("action", "report_generation");
            formData.append("reports", JSON.stringify(selectedReports));
            formData.append("selectedDate", selectedDate);
            formData.append("hostel_filter", selectedHostel);
            formData.append("floor_wise", floorWise ? '1' : '0');

            try {
                const res = await fetch("../api.php", {
                    method: "POST",
                    body: formData
                });

                // Read raw response text first so we can log any HTML/PHP warnings
                const text = await res.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('report_generation returned non-JSON response:', text);
                    alert('Failed to load data: server returned invalid response. See console for details.');
                    return;
                }

                if (result.success === false) {
                    alert(`Failed to load data for report: ${result.message || 'API error'}`);
                    // If server included debug_output, log it
                    if (result.debug_output) console.error('Server debug output:', result.debug_output);
                    return;
                }

                // If server returned debug_output even on success, log it (useful for stray warnings)
                if (result.debug_output) console.warn('Server debug output:', result.debug_output);

                // Pass server-generated timestamp so PDF shows actual generation datetime
                generatePDF(result.date, result.generated_date || new Date().toISOString().replace('T',' '), result.data, selectedHostel, floorWise);
            } catch (err) {
                console.error('Fetch/parse error (report_generation):', err);
                alert("Failed to load data. Check console for details.");
            }
        });

        async function generatePDF(date, generatedDate, data, hostelFilter, floorWise) {
            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF({
                orientation: "portrait",
                unit: "mm",
                format: "a4"
            });

            const headerColor = [0, 109, 109];
            const borderGray = [180, 180, 180];
            
            // Map keys from the API response to clean titles for the PDF
            const titleMap = {
                'present': 'Present Students',
                'absent': 'Absent Students',
                'blocked': 'Blocked Students',
                'late_entry': 'Late Entry Students',
                'on_leave': 'Students On Leave',
                'manual_present': 'Manual Present',
                'manual_leave': 'Manual Leave'
            };


// --- HEADER SECTION ---
            const logo = new Image();
            logo.src = "image/mkce_logo2.jpg"; 
            // Ensure the image is loaded before attempting to use it
            await new Promise(resolve => {
                logo.onload = resolve;
                logo.onerror = resolve; // Continue even if image fails to load
            });

            // Optional KR logo on the top-right
            const krLogo = new Image();
            krLogo.src = "image/kr.jpg";
            await new Promise(resolve => {
                krLogo.onload = resolve;
                krLogo.onerror = resolve;
            });

            // Row 1: Logo (left) + Date / Generated info (right)
            // Use a consistent logo size for both images and compute the right X dynamically
            const logoSize = 18; // mm square for both logos
            const pageWidth = doc.internal.pageSize.getWidth();
            const rightX = pageWidth - 15 - logoSize; // 15mm right margin

            if (logo.complete && logo.naturalHeight !== 0) {
                try { doc.addImage(logo, "JPG", 15, 10, logoSize, logoSize); } catch (e) { /* ignore image errors */ }
            }

            // Draw kr logo at top-right if available using same size
            try {
                if (krLogo.complete && krLogo.naturalHeight !== 0) {
                    doc.addImage(krLogo, "JPG", rightX, 10, logoSize, logoSize);
                }
            } catch (e) {
                // ignore image errors
            }
            // Prepare header text positions
            doc.setFont("helvetica", "normal");
            doc.setFontSize(9);

            // Left and right logo X positions are:
            const leftLogoRightEdge = 15 + logoSize; // right edge of left logo
            const rightLogoLeftEdge = rightX;       // left edge of right logo



            // Calculate center position between logos
            const logosCenterX = (leftLogoRightEdge + rightLogoLeftEdge) / 2;

            // Centered text between logos
            doc.setFont("helvetica", "bold");
            doc.setFontSize(14);
            doc.text("M.Kumarasamy College of Engineering, Karur - 639 113", logosCenterX, 25, {
                align: "center"
            });

            doc.setFont("helvetica", "italic");
            doc.setFontSize(10);
            doc.text("(An Autonomous Institution Affiliated to Anna University, Chennai)", logosCenterX, 30, {
                align: "center"
            });

            // Bottom Centered Title with selected date
            doc.setFont("helvetica", "bold");
            doc.setFontSize(11);
            // Use the selected date passed to the function (report date)
            const formattedDate = date;
            // Use server-generated timestamp for PDF generated date (fallback to now)
            const generatedDateStr = generatedDate || new Date().toISOString().replace('T',' ');
            const reportTitle = hostelFilter ? `ATTENDANCE REPORT - ${hostelFilter} (${formattedDate})` : `ATTENDANCE REPORT (${formattedDate})`;
            const titleY = 43;
            doc.text(reportTitle, logosCenterX, titleY, { align: 'center' });

            // Generated date and Generated by Admin
            const pageRightMargin = pageWidth - 15;
            doc.setFont("helvetica", "normal");
            doc.setFontSize(9);
            const dateY = titleY + 8; // Position below the title
            doc.text(`Generated Date: ${generatedDateStr}`, 15, dateY, { align: 'left' });
            doc.text('Generated by : Admin', pageRightMargin, dateY, { align: 'right' });

            // Divider below header
            doc.setDrawColor(...borderGray);
            doc.line(10, dateY + 5, 200, dateY + 5);

            let yPos = dateY + 20;
            let firstTable = true;

            // --- TABLES ---
            const defaultReportOrder = ['present', 'late_entry', 'on_leave', 'absent', 'blocked', 'manual_present', 'manual_leave'];
            // Only include report types that the API returned (i.e., the user requested)
            const reportOrder = defaultReportOrder.filter(k => Object.prototype.hasOwnProperty.call(data, k));

            reportOrder.forEach(type => {
                const students = data[type];

                if (!students || students.length === 0) return;

                // Page break logic for subsequent tables
                if (!firstTable && yPos > 260) {
                    doc.addPage();
                    yPos = 20;
                }
                firstTable = false;

                doc.setTextColor(0, 0, 0);
                doc.setFont("helvetica", "bold");
                doc.setFontSize(12);
                
                // Get the clean title using the map
                const tableTitle = titleMap[type.toLowerCase()] || `${type.toUpperCase()} STUDENTS`;
                doc.text(tableTitle, 14, yPos);
                yPos += 5;

                let tableData, headCols, styles = {
                    fontSize: 9,
                    halign: "center",
                    valign: "middle",
                    lineColor: borderGray,
                    lineWidth: 0.2
                };

                // Determine headings and raw tableData (used if not floor-wise)
                if (type === 'blocked') {
                    headCols = ["Roll No", "Name", "Department", "Year", "Floor", "Room", "Status", "Blocked At"];
                    tableData = students.map(s => [
                        s.roll_number,
                        s.name,
                        s.department,
                        s.academic_batch,
                        s.floor || '-',
                        s.room_number || '-',
                        s.attendance_status || 'Not Marked',
                        s.blocked_at || '-'
                    ]);
                } else if (type === 'manual_present' || type === 'manual_leave') {
                    headCols = ["Roll No", "Name", "Department", "Year", "Floor", "Reason", "Room", "Date/Time"];
                    tableData = students.map(s => [
                        s.roll_number,
                        s.name,
                        s.department,
                        s.academic_batch,
                        s.floor || '-',
                        s.reason || "-",
                        s.room_number || "-",
                        s.marked_at || "-"
                    ]);
                } else {
                    headCols = ["Roll No", "Name", "Department", "Year", "Floor", "Room", "Date/Time"];
                    tableData = students.map(s => [
                        s.roll_number,
                        s.name,
                        s.department,
                        s.academic_batch,
                        s.floor || '-',
                        s.room_number || "-",
                        s.marked_at || "-"
                    ]);
                }

                // If the user requested floor-wise grouping, render per-floor sub-tables
                if (floorWise) {
                    // group students by floor
                    const floorGroups = {};
                    students.forEach(s => {
                        const f = s.floor || 'Unknown';
                        if (!floorGroups[f]) floorGroups[f] = [];
                        floorGroups[f].push(s);
                    });

                    const floorOrder = ['I','II','III','IV','V'];
                    const floors = Object.keys(floorGroups).sort((a,b) => {
                        const ia = floorOrder.indexOf(a);
                        const ib = floorOrder.indexOf(b);
                        if (ia === -1 && ib === -1) return a.localeCompare(b);
                        if (ia === -1) return 1;
                        if (ib === -1) return -1;
                        return ia - ib;
                    });

                    floors.forEach(floor => {
                        if (!firstTable && yPos > 260) {
                            doc.addPage();
                            yPos = 20;
                        }
                        // Floor header
                        doc.setFont('helvetica', 'bold');
                        doc.setFontSize(11);
                        doc.text(`Floor: ${floor}`, 14, yPos);
                        yPos += 6;

                        const rowsForFloor = floorGroups[floor].map(s => {
                            if (type === 'blocked') {
                                return [
                                    s.roll_number,
                                    s.name,
                                    s.department,
                                    s.academic_batch,
                                    s.floor || '-',
                                    s.room_number || '-',
                                    s.attendance_status || 'Not Marked',
                                    s.blocked_at || '-'
                                ];
                            } else if (type === 'manual_present' || type === 'manual_leave') {
                                return [
                                    s.roll_number,
                                    s.name,
                                    s.department,
                                    s.academic_batch,
                                    s.floor || '-',
                                    s.reason || "-",
                                    s.room_number || '-',
                                    s.marked_at || '-'
                                ];
                            }
                            return [
                                s.roll_number,
                                s.name,
                                s.department,
                                s.academic_batch,
                                s.floor || '-',
                                s.room_number || '-',
                                s.marked_at || '-'
                            ];
                        });

                        doc.autoTable({
                            startY: yPos,
                            head: [headCols],
                            body: rowsForFloor,
                            theme: 'grid',
                            styles: styles,
                            headStyles: { fillColor: headerColor, textColor: 255, fontStyle: 'bold' },
                            alternateRowStyles: { fillColor: [242,247,247] }
                        });

                        yPos = doc.lastAutoTable.finalY + 8;
                        firstTable = false;
                    });
                } else {
                    doc.autoTable({
                        startY: yPos,
                        head: [headCols],
                        body: tableData,
                        theme: "grid",
                        styles: styles,
                        headStyles: {
                            fillColor: headerColor,
                            textColor: 255,
                            fontStyle: "bold"
                        },
                        alternateRowStyles: {
                            fillColor: [242, 247, 247]
                        }
                    });

                    yPos = doc.lastAutoTable.finalY + 10;
                }
                // Add page break if the next content will be pushed off the page
                if (yPos > 270) {
                    doc.addPage();
                    yPos = 20;
                }
            });

            // --- SUMMARY (moved to end) ---
            // Build overall totals and optional floor-wise summary
            const totals = {};
            const floorsSet = new Set();
            reportOrder.forEach(t => {
                const arr = data[t] || [];
                totals[t] = arr.length;
                arr.forEach(s => { if (s && s.floor) floorsSet.add(s.floor); });
            });

            // Render overall totals table
            doc.setFont("helvetica", "bold");
            doc.setFontSize(12);
            // Page break if summary would overflow
            if (!firstTable && yPos > 240) { doc.addPage(); yPos = 20; }
            doc.text("Report Summary", 14, yPos);
            yPos += 6;

            const summaryHead = [ ["Report", "Count"] ];
            const summaryBody = reportOrder.map(t => [ titleMap[t], totals[t] || 0 ]);
            doc.autoTable({
                startY: yPos,
                head: summaryHead,
                body: summaryBody,
                theme: 'grid',
                styles: { fontSize: 9, halign: 'center', valign: 'middle', lineColor: borderGray, lineWidth: 0.2 },
                headStyles: { fillColor: headerColor, textColor: 255, fontStyle: 'bold' }
            });
            yPos = doc.lastAutoTable.finalY + 8;

            // If floorWise was requested, render a cross-tab of Floor vs Report counts
            if (floorWise) {
                const floors = Array.from(floorsSet);
                // Use custom floor order when present
                const floorOrder = ['I','II','III','IV','V'];
                floors.sort((a,b) => {
                    const ia = floorOrder.indexOf(a);
                    const ib = floorOrder.indexOf(b);
                    if (ia === -1 && ib === -1) return a.localeCompare(b);
                    if (ia === -1) return 1;
                    if (ib === -1) return -1;
                    return ia - ib;
                });

                // header: Floor + each report short title
                const floorHead = ['Floor'].concat(reportOrder.map(t => titleMap[t]));
                const floorBody = floors.map(f => {
                    const row = [f];
                    reportOrder.forEach(t => {
                        const count = (data[t] || []).filter(s => (s.floor || 'Unknown') === f).length;
                        row.push(count);
                    });
                    return row;
                });

                // Page break if needed
                if (yPos > 240) { doc.addPage(); yPos = 20; }
                doc.setFont("helvetica", "bold"); doc.setFontSize(12);
                doc.text("Floor-wise Summary", 14, yPos);
                yPos += 6;
                doc.autoTable({
                    startY: yPos,
                    head: [floorHead],
                    body: floorBody,
                    theme: 'grid',
                    styles: { fontSize: 9, halign: 'center', valign: 'middle', lineColor: borderGray, lineWidth: 0.2 },
                    headStyles: { fillColor: headerColor, textColor: 255, fontStyle: 'bold' }
                });
                yPos = doc.lastAutoTable.finalY + 10;
            }

            // --- FOOTER ---
            // Add page numbers to all pages
const totalPages = doc.internal.getNumberOfPages();

for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);

    // Footer position (15 units above bottom)
    const pageHeight = doc.internal.pageSize.getHeight();

    doc.setFont("helvetica", "normal");
    doc.setFontSize(8);
    doc.setTextColor(50);

    // Centered page numbering
    doc.text(
        `Page ${i} / ${totalPages}`,
        pageWidth / 2,          
        pageHeight - 10,  
        { align: "center" }
    );
}

// ---- SAVE PDF ----
const fileName = hostelFilter 
    ? `attendance_report_${hostelFilter.replace(/\s+/g, '_')}_${formattedDate.replace(/\//g, '-')}.pdf`
    : `attendance_report_${formattedDate.replace(/\s+/g, '-')}.pdf`;

doc.save(fileName);
        }
    });
</script>
<script>
    const loaderContainer = document.getElementById('loaderContainer');

    function showLoader() {
        loaderContainer.classList.add('show');
    }

    function hideLoader() {
        loaderContainer.classList.remove('show');
    }

    //    automatic loader
    document.addEventListener('DOMContentLoaded', function() {
        const loaderContainer = document.getElementById('loaderContainer');
        const contentWrapper = document.getElementById('contentWrapper');
        let loadingTimeout;

        function hideLoader() {
            loaderContainer.classList.add('hide');
            contentWrapper.classList.add('show');
        }

        function showError() {
            console.error('Page load took too long or encountered an error');
            // You can add custom error handling here
        }

        // Set a maximum loading time (10 seconds)
        loadingTimeout = setTimeout(showError, 10000);

        // Hide loader when everything is loaded
        window.onload = function() {
            clearTimeout(loadingTimeout);

            // Add a small delay to ensure smooth transition
            setTimeout(hideLoader, 500);
        };

        // Error handling
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            clearTimeout(loadingTimeout);
            showError();
            return false;
        };
    });

    // Toggle Sidebar
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const body = document.body;
    const mobileOverlay = document.getElementById('mobileOverlay');

    function toggleSidebar() {
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('mobile-show');
            mobileOverlay.classList.toggle('show');
            body.classList.toggle('sidebar-open');
        } else {
            sidebar.classList.toggle('collapsed');
        }
    }
    hamburger.addEventListener('click', toggleSidebar);
    mobileOverlay.addEventListener('click', toggleSidebar);
    // Toggle User Menu
    const userMenu = document.getElementById('userMenu');
    const dropdownMenu = userMenu.querySelector('.dropdown-menu');

    userMenu.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdownMenu.classList.toggle('show');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', () => {
        dropdownMenu.classList.remove('show');
    });

    // Toggle Submenu
    const menuItems = document.querySelectorAll('.has-submenu');
    menuItems.forEach(item => {
        item.addEventListener('click', () => {
            const submenu = item.nextElementSibling;
            item.classList.toggle('active');
            submenu.classList.toggle('active');
        });
    });

    // Handle responsive behavior
    window.addEventListener('resize', () => {
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('mobile-show');
            mobileOverlay.classList.remove('show');
            body.classList.remove('sidebar-open');
        } else {
            sidebar.style.transform = '';
            mobileOverlay.classList.remove('show');
            body.classList.remove('sidebar-open');
        }
    });
</script>
    
    
</body>

</html>

<script>
    // Update the main dropdown display text and active state
    function updateMainTabDisplay(label) {
        var btn = document.getElementById('active-tab-display');
        if (btn) btn.textContent = label;
    }

    // Ensure clicks on dropdown items also update the main display and active class
    (function() {
        // Use event delegation to catch dynamically added items as well
        document.addEventListener('click', function(e) {
            var target = e.target.closest && e.target.closest('#attendance-tabs-dropdown .dropdown-item');
            if (!target) return;
            e.preventDefault();
            var label = target.textContent.trim();
            updateMainTabDisplay(label);

            // toggle active class
            var items = document.querySelectorAll('#attendance-tabs-dropdown .dropdown-item');
            items.forEach(function(it){ it.classList.remove('active'); });
            target.classList.add('active');

            // hide bootstrap dropdown if open
            var toggleEl = document.querySelector('#attendance-tabs-dropdown .dropdown-toggle');
            if (toggleEl) {
                try {
                    var dd = bootstrap.Dropdown.getInstance(toggleEl) || new bootstrap.Dropdown(toggleEl);
                    dd.hide();
                } catch (err) {
                    // ignore
                }
            }
        });
    })();
</script>
