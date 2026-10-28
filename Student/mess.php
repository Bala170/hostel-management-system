<?php
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login');
    exit();
}

// Set the timezone to ensure consistent time comparisons
date_default_timezone_set('Asia/Kolkata');

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['role'] ?? $_SESSION['user_type'] ?? 'student';

// For students, fetch data needed for display
if ($user_type === 'student') {
    // Get student roll number
    $stmt = $conn->prepare("SELECT roll_number FROM students WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $roll_number = $student['roll_number'] ?? null;

    // Get today's menu
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT * FROM mess_menu WHERE date = ? ORDER BY meal_type");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $today_menu = $stmt->get_result();

    // Get all special menus within the current date range and determine availability + whether the student already requested each
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
    SELECT 
        s.*,
        (
            SELECT COUNT(*) 
            FROM mess_tokens m 
            WHERE m.menu_id = s.menu_id 
              AND m.token_type = 'Special'
        ) AS used_count
    FROM specialtokenenable s
    WHERE s.from_date <= ? 
      AND s.to_date >= ? 
      AND s.status = 'active'
    ORDER BY s.from_date DESC
");
    $stmt->bind_param("ss", $today, $today);
    $stmt->execute();
    $res = $stmt->get_result();
    $special_menus_all_array = [];

    // Prepare check statement to see if student has already requested the special meal
    // Use mess_tokens.token_date and token_type to directly check requests
    $checkStmt = $conn->prepare("SELECT 1 FROM mess_tokens WHERE roll_number = ? AND token_date = ? AND meal_type = ? AND token_type = 'Special' LIMIT 1");

    $now = new DateTime();

    while ($row = $res->fetch_assoc()) {
        // Determine if the special menu is currently available (using full datetime comparison)
        $is_available = false;
        try {
            $start = new DateTime($row['from_date'] . ' ' . $row['from_time']);
            $end = new DateTime($row['to_date'] . ' ' . $row['to_time']);

            // If end is earlier than start, assume it crosses midnight and add a day to end
            if ($end < $start) {
                $end->modify('+1 day');
            }

            if ($now >= $start && $now <= $end) {
                $is_available = true;
            }
        } catch (Exception $e) {
            // If parsing fails, default to not available
            $is_available = false;
        }

        // Check if the student has already requested a token for this special menu (match by token_date & meal_type)
        $token_date_check = $row['token_date'] ?: null;
        $checkStmt->bind_param("sss", $roll_number, $token_date_check, $row['meal_type']);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        $requested = ($checkRes && $checkRes->num_rows > 0) ? 1 : 0;

        $row['requested_token'] = $requested;
        $row['is_available'] = $is_available;

        $special_menus_all_array[] = $row;
    }

    // Get special token history for the student
    $stmt = $conn->prepare("SELECT * FROM mess_tokens 
                           WHERE roll_number = ? AND token_type = 'Special' 
                           ORDER BY created_at DESC");
    $stmt->bind_param("s", $roll_number);
    $stmt->execute();
    $special_token_history = $stmt->get_result();

    // Convert to array for easier handling
    $special_token_history_array = [];
    while ($row = $special_token_history->fetch_assoc()) {
        $special_token_history_array[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Management</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../images/icons/mkce_s.png">

    <!-- Bootstrap 5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --topbar-height: 60px;
            --footer-height: 40px;
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --dark-bg: #1a1c23;
            --light-bg: #f8f9fc;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --table-header-gradient: linear-gradient(135deg, #4CAF50, #2196F3);
        }

        /* Add white separators for table headers */
        .table thead th {
            background: var(--table-header-gradient);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px 10px;
            border-right: 1px solid rgba(255, 255, 255, 0.3);
        }

        .table thead th:last-child {
            border-right: none;
        }

        /* Ensure all table headers have consistent styling */
        .table th {
            border-right: 1px solid rgba(255, 255, 255, 0.3);
        }

        .table th:last-child {
            border-right: none;
        }

        /* General Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fc;
            color: #333;
            overflow-x: hidden;
        }

        /* Content Area Styles */
        .content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .container-fluid {
            padding: 20px;
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

        /* Custom small status-style tabs (like Pending / Processed) */
        /* Tab styles based on provided reference */
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            color: #333;
        }

        /* ========== CUSTOM TABS STYLING (ACTIVE TAB COLORED) ========== */
        .custom-tabs {
            margin-bottom: 2rem;
        }

        /* Tabs Container */
        .custom-tabs .nav-tabs {
            border-bottom: none;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 10px 10px 0 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            gap: 8px;
            flex-wrap: nowrap;
            overflow-x: auto;
        }

        /* Individual Tab Button - INACTIVE (Grey/White) */
        .custom-tabs .nav-item:nth-child(1) .nav-link {
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #d531a4ff;
            background: white;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .custom-tabs .nav-item:nth-child(2) .nav-link {
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #2196F3;
            background: white;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .custom-tabs .nav-item:nth-child(3) .nav-link {
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #7B1FA2;
            background: white;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }



        /* Icon Styling - Inactive */
        .custom-tabs .nav-item:nth-child(1) .nav-link i {
            font-size: 1.1rem;
            color: #d531a4ff;
        }

        .custom-tabs .nav-item:nth-child(2) .nav-link i {
            font-size: 1.1rem;
            color: #3497efff;
        }

        .custom-tabs .nav-item:nth-child(3) .nav-link i {
            font-size: 1.1rem;
            color: #7B1FA2;
        }



        .custom-tabs .nav-item:nth-child(1) .nav-link i:hover {
            font-size: 1.1rem;
            color: white;
        }

        .custom-tabs .nav-item:nth-child(2) .nav-link i:hover {
            font-size: 1.1rem;
            color: white;
        }

        .custom-tabs .nav-item:nth-child(3) .nav-link i:hover {
            font-size: 1.1rem;
            color: white;
        }






        /* ========== ACTIVE TAB STATES (COLORED) ========== */

        /* Tab 1 Active - GREEN */
        .custom-tabs .nav-item:nth-child(1) .nav-link.active {
            background: linear-gradient(135deg, #d531a4ff 0%, #cc4da2ff 100%);
            color: white;
            border: 2px solid #d531a4ff;
            transform: translateY(-3px);
        }

        .custom-tabs .nav-item:nth-child(1) .nav-link.active i {
            color: white;
        }

        /* Tab 2 Active - BLUE */
        .custom-tabs .nav-item:nth-child(2) .nav-link.active {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
            border: 2px solid #2196F3;
            transform: translateY(-3px);
        }

        .custom-tabs .nav-item:nth-child(2) .nav-link.active i {
            color: white;
        }

        /* Tab 3 Active - PURPLE */
        .custom-tabs .nav-item:nth-child(3) .nav-link.active {
            background: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 100%);
            color: white;
            border: 2px solid #9C27B0;
            transform: translateY(-3px);
        }

        .custom-tabs .nav-item:nth-child(3) .nav-link.active i {
            color: white;
        }



        /* Tab Content Area */
        .custom-tabs .tab-content {
            background: white;
            padding: 25px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            min-height: 400px;
        }


        /* Hover Effect for Inactive Tabs */
        .custom-tabs .nav-item:nth-child(1) .nav-link:hover,
        .custom-tabs .nav-item:nth-child(1) .nav-link:hover i {
            background: linear-gradient(135deg, #d531a4ff 0%, #cc4da2ff 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .custom-tabs .nav-item:nth-child(2) .nav-link:hover,
        .custom-tabs .nav-item:nth-child(2) .nav-link:hover i {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .custom-tabs .nav-item:nth-child(3) .nav-link:hover,
        .custom-tabs .nav-item:nth-child(3) .nav-link:hover i {
            background: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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

        /* Breadcrumb */
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

        /* Tables */
        .table-responsive {
            overflow-x: auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .table {
            margin-bottom: 0;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, .05);
        }

        .table-bordered {
            border: 1px solid #dee2e6;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #dee2e6;
        }

        .table td {
            vertical-align: middle;
            font-size: 0.9em;
            text-align: left;
        }

        .table th {
            vertical-align: middle;
            font-size: 0.95em;
            text-align: center;
            font-weight: bold;
        }



        .table tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            border-bottom: 1px solid #e3e6f0;
        }

        .table tbody tr:hover {
            background-color: #f8f9fc;
        }

        /* Navigation Tabs/Pills */
        .nav-tabs .nav-link,
        .nav-pills .nav-link {
            border-radius: 0.35rem;
            font-size: 0.9em;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        /* Consistent Tab Style */
        .nav-pills .nav-link {
            border-radius: 50px;
            padding: 10px 20px;
            margin-right: 8px;
            transition: 0.3s;
            color: #555;
            background-color: #f8f9fc;
            border: 1px solid #e3e6f0;
        }

        .nav-pills .nav-link.active {
            background: var(--table-header-gradient);
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
            border: 1px solid transparent;
        }

        .nav-pills .nav-link i {
            font-size: 1em;
        }

        .nav-pills .nav-link:hover {
            background: rgba(78, 115, 223, 0.2);
            color: #4e73df;
        }


        /* Buttons */
        button.request-btn,
        button.requestToken {
            font-size: 0.85em;
            border-radius: 0.35rem;
        }

        button.request-btn:hover {
            opacity: 0.9;
        }

        .btn {
            border-radius: 0.35rem;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #218838, #1cb386);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            border: none;
            color: #212529;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #e0a800, #f08a00);
            color: #212529;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff, #1cc88a);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0069d9, #1aae77);
        }

        .btn-outline-primary {
            color: #007bff;
            border-color: #007bff;
        }

        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #007bff, #1cc88a);
            color: white;
            border-color: #007bff;
        }

        /* Page Titles */
        .content h4 {
            font-weight: 600;
            color: #4e73df;
            margin-bottom: 1rem;
        }

        .content h5 {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        /* Monthly bill header controls */
        .card-header.d-flex {
            gap: 0.25rem;
            align-items: center;
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

        /* Container Styles */
        .main-container {
            background: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            padding: 20px;
            margin-bottom: 20px;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 20px;
        }

        .table-container .table-title {
            margin-top: 0;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e3e6f0;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.1em;
        }

        .combined-table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 20px;
        }

        .combined-table-container .table-container {
            box-shadow: none;
            padding: 0;
            margin-bottom: 15px;
        }

        .combined-table-container .table-container:last-child {
            margin-bottom: 0;
        }

        .combined-table-container .table-title {
            margin-top: 0;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e3e6f0;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.1em;
        }

        /* Gradient Header for Tables */
        .gradient-header {
            background: linear-gradient(135deg, #4CAF50, #2196F3) !important;
            color: white;
            font-weight: 600;
            font-size: 0.9em;
        }

        .gradient-header th {
            border-right: 1px solid rgba(255, 255, 255, 0.7);
            padding: 15px 10px;
            background: transparent !important;
        }

        .gradient-header th:last-child {
            border-right: none;
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

            .nav-tabs .nav-link,
            .nav-pills .nav-link {
                font-size: 0.8em;
                padding: 0.35rem 0.5rem;
            }

            .card-body {
                padding: 0.8rem 1rem;
            }

            .table td,
            .table th {
                font-size: 0.85em;
                padding: 0.5rem;
            }
        }
    </style>

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <!-- Version Check Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('jQuery version:', $.fn.jquery);
            console.log('DataTables version:', $.fn.DataTable ? $.fn.DataTable.version : 'Not loaded');

            // Check all tables
            $('table').each(function() {
                console.log('Table found - ID:', this.id || 'no-id', 'Class:', this.className, 'Rows:', $(this).find('tbody tr').length);
            });
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            let todayMenuTable;
            let specialMealsTable;
            let specialTokenHistoryTable;
            let monthlyBillTable;

            // Initialize all DataTables once (avoid destroying and recreating)
            function initTables() {
                // Remove any placeholder rows with colspan to avoid DataTables column count errors
                ['#todayMenuTable', '#specialMealsTable', '#specialTokenHistoryTable', '#monthlyBillTable'].forEach(function(sel) {
                    if ($(sel).length) {
                        $(sel).find('tbody td[colspan]').closest('tr').remove();
                    }
                });

                // Today's Menu Table
                if ($('#todayMenuTable').length && !$.fn.DataTable.isDataTable('#todayMenuTable')) {
                    todayMenuTable = $('#todayMenuTable').DataTable({
                        pageLength: 10,
                        lengthMenu: [
                            [5, 10, 25, 50, -1],
                            [5, 10, 25, 50, 'All']
                        ],
                        language: {
                            search: 'Search menu:',
                            lengthMenu: 'Show _MENU_ meals per page',
                            info: 'Showing _START_ to _END_ of _TOTAL_ meals',
                            infoEmpty: 'No meals available',
                        },
                        responsive: true,
                        autoWidth: false,
                        order: [
                            [0, 'asc']
                        ]
                    });
                }

                // Special Meals Table
                if ($('#specialMealsTable').length && !$.fn.DataTable.isDataTable('#specialMealsTable')) {
                    specialMealsTable = $('#specialMealsTable').DataTable({
                        pageLength: 10,
                        lengthMenu: [
                            [5, 10, 25, 50, -1],
                            [5, 10, 25, 50, 'All']
                        ],
                        language: {
                            search: 'Search special meals:',
                            emptyTable: 'No special meals available.'
                        },
                        responsive: true,
                        autoWidth: false,
                        order: [
                            [0, 'desc']
                        ],
                        columnDefs: [{
                            targets: -1,
                            orderable: false,
                            searchable: false
                        }]
                    });
                }

                // Special Token History Table
                if ($('#specialTokenHistoryTable').length && !$.fn.DataTable.isDataTable('#specialTokenHistoryTable')) {
                    // ensure placeholder rows removed (again)
                    $('#specialTokenHistoryTable').find('tbody td[colspan]').closest('tr').remove();
                    specialTokenHistoryTable = $('#specialTokenHistoryTable').DataTable({
                        pageLength: 10,
                        lengthMenu: [
                            [5, 10, 25, 50, -1],
                            [5, 10, 25, 50, 'All']
                        ],
                        language: {
                            search: 'Search history:',
                            emptyTable: 'No special tokens requested yet.'
                        },
                        responsive: true,
                        autoWidth: false,
                        order: [
                            [1, 'desc']
                        ],
                        columnDefs: [{
                            targets: 0,
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        }]
                    });
                }

                // Monthly Bill Table
                if ($('#monthlyBillTable').length && !$.fn.DataTable.isDataTable('#monthlyBillTable')) {
                    $('#monthlyBillTable').find('tbody td[colspan]').closest('tr').remove();
                    monthlyBillTable = $('#monthlyBillTable').DataTable({
                        pageLength: 10,
                        lengthMenu: [
                            [5, 10, 25, 50, -1],
                            [5, 10, 25, 50, 'All']
                        ],
                        language: {
                            search: 'Search :',
                            emptyTable: 'No special tokens billed yet.'
                        },
                        responsive: true,
                        autoWidth: false,
                        order: [
                            [1, 'desc']
                        ],
                        columnDefs: [{
                            targets: 0,
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        }],
                        footerCallback: function(row, data, start, end, display) {
                            const api = this.api();
                            const total = api.column(4, {
                                search: 'applied'
                            }).data().reduce(function(sum, value) {
                                let numericValue = value;
                                if (typeof numericValue === 'string') numericValue = numericValue.replace(/[₹,\s]/g, '');
                                return sum + (parseFloat(numericValue) || 0);
                            }, 0);
                            $(api.column(4).footer()).html('<strong>₹' + total.toFixed(2) + '</strong>');
                        }
                    });
                }

                // After initializing, adjust columns for visible tables
                setTimeout(function() {
                    if (todayMenuTable) todayMenuTable.columns.adjust();
                    if (specialMealsTable) specialMealsTable.columns.adjust();
                    if (specialTokenHistoryTable) specialTokenHistoryTable.columns.adjust();
                    if (monthlyBillTable) monthlyBillTable.columns.adjust();
                }, 50);
            }

            // Initialize now
            initTables();

            // When a tab becomes visible, adjust columns so DataTables lay out correctly
            $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
                const target = $(e.target).attr('data-bs-target');
                setTimeout(function() {
                    if (target === '#today-menu' && todayMenuTable) todayMenuTable.columns.adjust().draw(false);
                    if (target === '#special-meals' && specialMealsTable) specialMealsTable.columns.adjust().draw(false);
                    if (target === '#monthly-bill' && monthlyBillTable) monthlyBillTable.columns.adjust().draw(false);
                }, 100);
            });

            // Adjust on window resize
            $(window).on('resize', function() {
                if (todayMenuTable) todayMenuTable.columns.adjust();
                if (specialMealsTable) specialMealsTable.columns.adjust();
                if (specialTokenHistoryTable) specialTokenHistoryTable.columns.adjust();
                if (monthlyBillTable) monthlyBillTable.columns.adjust();
            });

            $('#loadBillBtn').on('click', function() {
                const month = $('#billMonth').val();
                const year = $('#billYear').val();
                const $button = $(this);

                if (!monthlyBillTable) {
                    Swal.fire('Error', 'Monthly bill table is not ready yet.', 'error');
                    return;
                }

                $button.prop('disabled', true).text('Loading...');

                $.post('../api.php', {
                    action: 'get_monthly_bill',
                    month: month,
                    year: year
                }, function(response) {
                    monthlyBillTable.clear();

                    if (response.status && response.data.length > 0) {
                        response.data.forEach(function(item) {
                            monthlyBillTable.row.add([
                                '',
                                item.formatted_date,
                                item.meal_type,
                                item.menu.replace(/\n/g, '<br>'),
                                `₹${parseFloat(item.special_fee).toFixed(2)}`
                            ]);
                        });
                    } else if (!response.status) {
                        Swal.fire('Error', response.msg || 'Failed to load bill data.', 'error');
                    }

                    monthlyBillTable.draw();
                }).fail(function() {
                    Swal.fire('Error', 'Failed to connect to server.', 'error');
                }).always(function() {
                    $button.prop('disabled', false).text('Load');
                });
            });

            // Request special token - using event delegation to handle dynamically created buttons
            $(document).on('click', '.request-btn', function() {
                let menu_id = $(this).data('menu-id');
                let btn = $(this);

                // Disable button to prevent double clicks
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Requesting...');

                $.post('../api.php', {
                    action: 'request_special_token',
                    menu_id: menu_id
                }, function(response) {
                    // Handle both string and JSON responses
                    let resp;
                    if (typeof response === 'string') {
                        try {
                            resp = JSON.parse(response);
                        } catch (e) {
                            // If parsing fails, show the raw response for debugging
                            Swal.fire('Error', 'Invalid response from server: ' + response.substring(0, 200) + '...', 'error');
                            btn.prop('disabled', false).html('<i class="fas fa-plus-circle"></i> Request');
                            return;
                        }
                    } else {
                        resp = response;
                    }

                    if (resp.status) {
                        Swal.fire('Success', resp.msg, 'success');
                        // Update button to show "Special Token Taken" in yellow
                        btn.removeClass('btn-success').addClass('btn-warning')
                            .html('<i class="fas fa-check"></i> Special Token Taken')
                            .prop('disabled', true)
                            .css({
                                'padding': '0.5rem 1rem',
                                'font-size': '0.9rem',
                                'min-width': '120px'
                            });

                        // Add the new token to the DataTable views immediately
                        if (resp.token_data) {
                            const createdAt = new Date(resp.token_data.created_at);
                            const formattedDate = createdAt.toLocaleDateString('en-GB', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric'
                            });

                            const historyRowData = [
                                '',
                                resp.token_data.token_date,
                                resp.token_data.meal_type,
                                resp.token_data.menu.replace(/\n/g, '<br>'),
                                `₹${parseFloat(resp.token_data.special_fee).toFixed(2)}`,
                                resp.token_data.created_at
                            ];

                            // Ensure DataTable is initialized before adding row
                            if (!specialTokenHistoryTable && $('#special-meals').hasClass('show')) {
                                // Initialize the table if it's not already initialized
                                initializeVisibleDataTables();
                            }

                            if (specialTokenHistoryTable) {
                                specialTokenHistoryTable.row.add(historyRowData).draw(false);
                            } else {
                                $('#specialTokenHistoryTable tbody').append(`
                                    <tr>
                                        <td></td>
                                        <td>${resp.token_data.token_date}</td>
                                        <td>${resp.token_data.meal_type}</td>
                                        <td>${resp.token_data.menu.replace(/\n/g, '<br>')}</td>
                                        <td>₹${parseFloat(resp.token_data.special_fee).toFixed(2)}</td>
                                        <td>${resp.token_data.created_at}</td>
                                    </tr>
                                `);
                            }

                            const monthlyRowData = [
                                '',
                                formattedDate,
                                resp.token_data.meal_type,
                                resp.token_data.menu.replace(/\n/g, '<br>'),
                                `₹${parseFloat(resp.token_data.special_fee).toFixed(2)}`
                            ];

                            // Ensure DataTable is initialized before adding row
                            if (!monthlyBillTable && $('#monthly-bill').hasClass('show')) {
                                // Initialize the table if it's not already initialized
                                initializeVisibleDataTables();
                            }

                            if (monthlyBillTable) {
                                monthlyBillTable.row.add(monthlyRowData).draw(false);
                            } else {
                                $('#monthlyBillTable tbody').append(`
                                    <tr>
                                        <td></td>
                                        <td>${formattedDate}</td>
                                        <td>${resp.token_data.meal_type}</td>
                                        <td>${resp.token_data.menu.replace(/\n/g, '<br>')}</td>
                                        <td>₹${parseFloat(resp.token_data.special_fee).toFixed(2)}</td>
                                    </tr>
                                `);
                            }
                        }
                    } else {
                        Swal.fire('Error', resp.msg, 'error');
                        btn.prop('disabled', false).html('<i class="fas fa-plus-circle"></i> Request');
                    }
                }).fail(function(xhr, status, error) {
                    // Show detailed error information
                    let errorMessage = 'Failed to connect to server';
                    if (xhr.responseText) {
                        errorMessage += ': ' + xhr.responseText.substring(0, 200) + '...';
                    } else {
                        errorMessage += ': ' + error;
                    }
                    Swal.fire('Error', errorMessage, 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-plus-circle"></i> Request');
                });
            });



            // Function to reinitialize DataTables when needed
            function reinitializeDataTables() {
                initializeVisibleDataTables();
            }

        });
    </script>

</head>

<body>
    <!-- Sidebar -->
    <?php include '../assets/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content">
        <div class="loader-container hide" id="loaderContainer">
            <div class="loader"></div>
        </div>

        <!-- Topbar -->
        <?php include '../assets/topbar.php'; ?>

        <!-- Breadcrumb -->
        <div class="breadcrumb-area custom-gradient">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mess Menu</li>
                </ol>
            </nav>
        </div>

        <!-- Content Area -->
        <div class="container-fluid">
            <?php if ($user_type === 'student'): ?>
                <!-- Student Interface -->
                <!-- Tabs Navigation -->
                <div class="main-container">
                    <div class="custom-tabs mb-3">
                        <ul class="nav nav-tabs" id="menuTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active tab-blue" id="today-menu-tab" data-bs-toggle="tab" href="#today-menu" role="tab" aria-controls="today-menu" aria-selected="true">
                                    <i class="fas fa-utensils tab-icon"></i> Today's Menu
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link tab-teal" id="special-meals-tab" data-bs-toggle="tab" href="#special-meals" role="tab" aria-controls="special-meals" aria-selected="false">
                                    <i class="fas fa-mug-hot tab-icon"></i> Special Meals
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link tab-green" id="monthly-bill-tab" data-bs-toggle="tab" href="#monthly-bill" role="tab" aria-controls="monthly-bill" aria-selected="false">
                                    <i class="fas fa-file-invoice-dollar tab-icon"></i> Monthly Bill
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content" id="menuTabsContent">
                        <div class="tab-pane fade show active" id="today-menu" role="tabpanel" aria-labelledby="today-menu-tab">
                            <div class="table-container">
                                <h5 class="table-title">Today's Menu - <?php echo date('d M Y'); ?></h5>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered" id="todayMenuTable">
                                        <thead class="gradient-header">
                                            <tr>
                                                <th>Meal Type</th>
                                                <th>Items</th>
                                                <th>Category</th>
                                                <th>Fee (₹)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($today_menu->num_rows === 0): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">No menu available for today.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php while ($row = $today_menu->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row['meal_type']) ?></td>
                                                        <td><?= nl2br(htmlspecialchars($row['items'])) ?></td>
                                                        <td><?= htmlspecialchars($row['category']) ?></td>
                                                        <td><?= number_format($row['fee'], 2) ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- ======================= -->
                        <!-- ✅ SPECIAL MEALS TAB -->
                        <!-- ======================= -->
                        <div class="tab-pane fade" id="special-meals" role="tabpanel" aria-labelledby="special-meals-tab">
                            <div class="combined-table-container">
                                <!-- Available Special Meals -->
                                <div class="table-container">
                                    <h5 class="table-title">Available Special Meals</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered" id="specialMealsTable">
                                            <thead class="gradient-header">
                                                <tr>
                                                    <th>Special Meal Date</th>
                                                    <th>From Date</th>
                                                    <th>From Time</th>
                                                    <th>To Date</th>
                                                    <th>To Time</th>
                                                    <th>Meal Type</th>
                                                    <th>Token Type</th>
                                                    <th>Items</th>
                                                    <th>Fee (₹)</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($special_menus_all_array)): ?>
                                                    <?php foreach ($special_menus_all_array as $row): ?>
                                                        <tr>
    <td><?= htmlspecialchars(date("d-m-Y", strtotime($row['token_date']))) ?></td>
    <td><?= htmlspecialchars(date("d-m-Y", strtotime($row['from_date']))) ?></td>
    <td><?= substr($row['from_time'], 0, 5) ?></td>
    <td><?= htmlspecialchars(date("d-m-Y", strtotime($row['to_date']))) ?></td>
    <td><?= substr($row['to_time'], 0, 5) ?></td>

    <td><?= htmlspecialchars($row['meal_type']) ?></td>

    <!-- ✅ TOKEN TYPE -->
    <td>
        <?php if ((int)$row['max_usage'] === -1): ?>
            <span class="badge bg-primary">Unlimited</span>
        <?php else: ?>
            <span class="badge bg-info text-dark">Limited</span>
        <?php endif; ?>
    </td>

    <td><?= nl2br(htmlspecialchars($row['menu_items'])) ?></td>
    <td><?= number_format($row['fee'], 2) ?></td>

    <td>
        <?php if (!empty($row['requested_token'])): ?>
            <span class="badge bg-warning text-dark">
                <i class="fas fa-check"></i> Special Token Taken
            </span>
        <?php elseif (!empty($row['is_available'])): ?>
            <span class="badge bg-success text-white">
                <i class="fas fa-check-circle"></i> Available
            </span>
        <?php else: ?>
            <span class="badge bg-secondary text-white">
                Not Available
            </span>
        <?php endif; ?>
    </td>
</tr>

                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="9" class="text-center text-muted">No special meals available.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Special Token History -->
                                <div class="table-container">
                                    <h5 class="table-title">Special Token History</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered" id="specialTokenHistoryTable">
                                            <thead class="gradient-header">
                                                <tr>
                                                    <th>S.no</th>
                                                    <th>Token Date</th>
                                                    <th>Meal Type</th>
                                                    <th>Menu Items</th>
                                                    <th>Fee (₹)</th>
                                                    <th>Requested At</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($special_token_history_array)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">No special tokens requested yet.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php
                                                    $serial_number = 1;
                                                    foreach ($special_token_history_array as $h): ?>
                                                        <tr>
                                                            <td><?= $serial_number++ ?></td>
                                                            <td><?= htmlspecialchars(date("d-m-Y", strtotime($h['token_date']))) ?></td>
                                                            <td><?= htmlspecialchars($h['meal_type']) ?></td>
                                                            <td>
                                                                <?php
                                                                // Sometimes older records store a numeric menu id in `menu` column.
                                                                // If it's numeric, try to resolve to the actual menu items from `mess_menu`.
                                                                $menu_text = '';
                                                                if (isset($h['menu']) && ctype_digit((string)$h['menu'])) {
                                                                    $mid = intval($h['menu']);
                                                                    $mstmt = $conn->prepare("SELECT items FROM mess_menu WHERE menu_id = ? LIMIT 1");
                                                                    if ($mstmt) {
                                                                        $mstmt->bind_param("i", $mid);
                                                                        $mstmt->execute();
                                                                        $mres = $mstmt->get_result();
                                                                        if ($mres && $mrow = $mres->fetch_assoc()) {
                                                                            $menu_text = $mrow['items'];
                                                                        }
                                                                    }
                                                                }
                                                                if (empty($menu_text) && isset($h['menu'])) {
                                                                    $menu_text = $h['menu'];
                                                                }
                                                                echo nl2br(htmlspecialchars($menu_text));
                                                                ?>
                                                            </td>
                                                            <td>₹<?= number_format($h['special_fee'], 2) ?></td>
                                                            <td><?= htmlspecialchars(date("d-m-Y H:i:s", strtotime($h['created_at']))) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ======================= -->
                        <!-- ✅ MONTHLY BILL TAB -->
                        <!-- ======================= -->
                        <div class="tab-pane fade" id="monthly-bill" role="tabpanel" aria-labelledby="monthly-bill-tab">
                            <div class="combined-table-container">
                                <!-- Bill Details Section -->
                                <div class="table-container">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="table-title mb-0">Monthly Bill Details</h5>
                                        <div class="d-flex gap-2">
                                            <select id="billMonth" class="form-select form-select-sm" style="width: auto;">
                                                <?php
                                                $months = range(1, 12);
                                                $current_month = date('n');
                                                foreach ($months as $month):
                                                    $month_name = date('F', mktime(0, 0, 0, $month, 10));
                                                    $selected = ($month == $current_month) ? 'selected' : '';
                                                ?>
                                                    <option value="<?= $month ?>" <?= $selected ?>><?= $month_name ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select id="billYear" class="form-select form-select-sm" style="width: auto;">
                                                <?php
                                                $years = range(2020, date('Y') + 1);
                                                $current_year = date('Y');
                                                foreach ($years as $year):
                                                    $selected = ($year == $current_year) ? 'selected' : '';
                                                ?>
                                                    <option value="<?= $year ?>" <?= $selected ?>><?= $year ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button id="loadBillBtn" class="btn btn-primary btn-sm">Load</button>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered" id="monthlyBillTable">
                                            <thead class="gradient-header">
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Date</th>
                                                    <th>Meal Type</th>
                                                    <th>Items</th>
                                                    <th>Fee (₹)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $monthly_total = 0;
                                                $serial_number = 1;
                                                foreach ($special_token_history_array as $h):
                                                    $monthly_total += $h['special_fee'];
                                                ?>
                                                    <tr>
                                                        <td><?= $serial_number++ ?></td>
                                                        <td><?= htmlspecialchars(date("d M Y", strtotime($h['created_at']))) ?></td>
                                                        <td><?= htmlspecialchars($h['meal_type']) ?></td>
                                                        <td><?= nl2br(htmlspecialchars($h['menu'])) ?></td>
                                                        <td>₹<?= number_format($h['special_fee'], 2) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="4" class="text-end">Total:</th>
                                                    <th id="monthlyBillTotal">₹<?= number_format($monthly_total, 2) ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../assets/footer.php'; ?>
</body>

</html>