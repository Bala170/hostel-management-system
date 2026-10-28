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
    <title>Hostel Management</title>
    <link rel="icon" type="image/png" sizes="32x32" href="image/icons/mkce_s.png">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        }

        .content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .sidebar.collapsed~.content {
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

        .container-fluid {
            padding: 20px;
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

        .sidebar.collapsed~.content .loader-container {
            left: var(--sidebar-collapsed-width);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width) !important;
            }

            .sidebar.mobile-show {
                transform: translateX(0);
            }

            .content {
                margin-left: 0 !important;
            }

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

        .table-responsive {
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-top: 20px;
        }

        .table {
            margin-bottom: 0;
        }

        /* Table Header Gradient - Green to Blue */
        .table thead {
            background: linear-gradient(135deg, #4CAF50 0%, #2196F3 100%) !important;
            color: white !important;
        }

        .table thead th {
            background: transparent !important;
            color: white !important;
            text-align: center;
            height: 1.5cm;
            font-size: 1em;
            font-weight: 600;
            padding: 12px 8px;
            border: 0.3px solid #feffffff !important;
            /* Change color as needed */
            vertical-align: middle;
        }

        .table tbody {
            text-align: center;
        }

        /* Override any Bootstrap defaults */
        .table>thead {
            --bs-table-bg: transparent;
            --bs-table-color: white;
        }

        /* For striped/hover tables */

        .table-hover thead,
        .table-bordered thead {
            background: linear-gradient(135deg, #4CAF50 0%, #2196F3 100%) !important;
        }


        .btn-group-sm .btn {
            margin: 0 2px;
        }

        .badge {
            font-size: 0.75em;
            padding: 0.5em 0.75em;
        }

        /* ========== STATISTICS DASHBOARD STYLING ========== */

        /* Dashboard Header */
        .statistics-header {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .statistics-header i {
            font-size: 1.8rem;
        }

        /* Statistics Container */
        .statistics-container {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        /* Individual Stat Box */
        .stat-box {
            flex: 1;
            min-width: 280px;
            padding: 30px 20px;
            border-radius: 15px;
            color: white;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        /* Hover Effect */
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
        }

        /* Gradient Backgrounds */
        .stat-box:nth-child(1) {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }

        .stat-box:nth-child(2) {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        }

        .stat-box:nth-child(3) {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
        }

        .stat-box:nth-child(4) {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
        }

        /* Stat Value (Number) */
        .stat-box h3 {
            font-size: 3rem;
            font-weight: 700;
            margin: 0;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Stat Label (Text) */
        .stat-box p {
            font-size: 1.1rem;
            margin: 0;
            font-weight: 500;
            opacity: 0.95;
            letter-spacing: 0.5px;
        }

        /* Decorative Background Effect */
        .stat-box::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
            transition: all 0.5s ease;
        }

        .stat-box:hover::before {
            transform: rotate(45deg) translateY(-10%);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .statistics-container {
                gap: 1rem;
            }

            .stat-box {
                min-width: 230px;
                padding: 25px 15px;
            }

            .stat-box h3 {
                font-size: 2.5rem;
            }

            .stat-box p {
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            .stat-box {
                min-width: 100%;
            }

            .statistics-container {
                flex-direction: column;
            }
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
            border: 2px solid #dee2e6;
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
            border: 2px solid #dee2e6;
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
            border: 2px solid #dee2e6;
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

        .custom-tabs .nav-item:nth-child(4) .nav-link {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #F57C00;
            background: white;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .custom-tabs .nav-item:nth-child(5) .nav-link {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #f50000ff;
            background: white;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }


        .custom-tabs .nav-item:nth-child(6) .nav-link {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #d800f5ff;
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

        .custom-tabs .nav-item:nth-child(4) .nav-link i {
            font-size: 1.1rem;
            color: #F57C00;
        }

        .custom-tabs .nav-item:nth-child(5) .nav-link i {
            font-size: 1.1rem;
            color: #f50000ff;
        }

        .custom-tabs .nav-item:nth-child(6) .nav-link i {
            font-size: 1.1rem;
            color: #d800f5ff;
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

        .custom-tabs .nav-item:nth-child(4) .nav-link:hover i {
            font-size: 1.1rem;
            color: white;
        }

        .custom-tabs .nav-item:nth-child(5) .nav-link:hover i {
            font-size: 1.1rem;
            color: white;
        }

        .custom-tabs .nav-item:nth-child(6) .nav-link:hover i {
            font-size: 1.1rem;
            color: white;
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

        .custom-tabs .nav-item:nth-child(4) .nav-link:hover,
        .custom-tabs .nav-item:nth-child(4) .nav-link:hover i {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .custom-tabs .nav-item:nth-child(5) .nav-link:hover,
        .custom-tabs .nav-item:nth-child(5) .nav-link:hover i {
            background: linear-gradient(135deg, #eb454aff 0%, #f50000ff 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .custom-tabs .nav-item:nth-child(6) .nav-link:hover,
        .custom-tabs .nav-item:nth-child(6) .nav-link:hover i {
            background: linear-gradient(135deg, #da59f0ff 0%, #d800f5ff 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* ========== ACTIVE TAB STATES (COLORED) ========== */

        /* Tab 1 Active - GREEN */
        .custom-tabs .nav-item:nth-child(1) .nav-link.active {
            background: linear-gradient(135deg, #d531a4ff 0%, #cc4da2ff 100%);
            color: white;
            border-color: transparent;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(175, 76, 134, 0.4);
        }

        .custom-tabs .nav-item:nth-child(1) .nav-link.active i {
            color: white;
        }

        /* Tab 2 Active - BLUE */
        .custom-tabs .nav-item:nth-child(2) .nav-link.active {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
            border-color: transparent;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(33, 150, 243, 0.4);
        }

        .custom-tabs .nav-item:nth-child(2) .nav-link.active i {
            color: white;
        }

        /* Tab 3 Active - PURPLE */
        .custom-tabs .nav-item:nth-child(3) .nav-link.active {
            background: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 100%);
            color: white;
            border-color: transparent;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(156, 39, 176, 0.4);
        }

        .custom-tabs .nav-item:nth-child(3) .nav-link.active i {
            color: white;
        }

        /* Tab 4 Active - ORANGE */
        .custom-tabs .nav-item:nth-child(4) .nav-link.active {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
            color: white;
            border-color: transparent;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(255, 152, 0, 0.4);
        }

        .custom-tabs .nav-item:nth-child(4) .nav-link.active i {
            color: white;
        }


        .custom-tabs .nav-item:nth-child(5) .nav-link.active {
            background: linear-gradient(135deg, #eb454aff 0%, #f50000ff 100%);
            color: white;
            border-color: transparent;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(255, 152, 0, 0.4);
        }

        .custom-tabs .nav-item:nth-child(5) .nav-link.active i {
            color: white;
        }

        .custom-tabs .nav-item:nth-child(6) .nav-link.active {
            background: linear-gradient(135deg, #da59f0ff 0%, #d800f5ff 100%);
            color: white;
            border-color: transparent;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(255, 152, 0, 0.4);
        }

        .custom-tabs .nav-item:nth-child(6) .nav-link.active i {
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .custom-tabs .nav-tabs {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .custom-tabs .nav-link {
                padding: 10px 15px;
                font-size: 0.85rem;
            }

            .custom-tabs .nav-link i {
                font-size: 1rem;
            }
        }

        /* Filter Section Styling */
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .filter-section .form-label {
            color: #333;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .filter-section .form-control,
        .filter-section .form-select {
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }

        .filter-section .form-control:focus,
        .filter-section .form-select:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .filter-section .btn {
            margin-right: 5px;
        }

        /* Export button styling */
        .btn-success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            border: none;
            box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3);
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(76, 175, 80, 0.4);
        }

        .btn-success i {
            margin-right: 5px;
        }

        /* ========== MOBILE RESPONSIVE STYLES ========== */
        @media (max-width: 768px) {

            /* Statistics Dashboard */
            .statistics-container {
                flex-direction: column;
                gap: 1rem;
            }

            .stat-box {
                min-width: 100%;
                padding: 20px 15px;
            }

            .stat-box h3 {
                font-size: 2rem;
            }

            .stat-box p {
                font-size: 0.95rem;
            }

            /* Tabs */
            .nav-tabs {
                flex-wrap: wrap;
                gap: 5px;
            }

            .nav-item {
                flex: 1 1 45%;
                min-width: 150px;
            }

            .nav-link {
                padding: 10px 8px;
                font-size: 0.85rem;
            }

            /* Tables */
            .table-responsive {
                font-size: 0.85rem;
                overflow-x: auto;
            }

            .table thead th {
                font-size: 0.8rem;
                padding: 8px 5px;
            }

            .table tbody td {
                padding: 8px 5px;
                word-wrap: break-word;
            }

            /* Buttons */
            .btn-sm {
                padding: 0.25rem 0.4rem;
                font-size: 0.75rem;
            }

            .btn {
                font-size: 0.85rem;
                padding: 0.4rem 0.8rem;
            }

            /* Filter Section */
            .filter-section {
                padding: 15px;
            }

            .filter-section .row {
                gap: 10px;
            }

            .filter-section .col-md-2,
            .filter-section .col-md-3,
            .filter-section .col-md-6 {
                width: 100%;
                margin-bottom: 10px;
            }

            /* Hide certain columns on mobile */
            .table th:nth-child(n+6),
            .table td:nth-child(n+6) {
                display: none;
            }

            /* Breadcrumb */
            .breadcrumb-area {
                padding: 10px 15px;
            }

            .breadcrumb {
                font-size: 0.85rem;
            }

            /* Export buttons */
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 10px;
            }

            .d-flex.justify-content-between .btn {
                width: 100%;
            }

            /* Headings */
            h5 {
                font-size: 1.1rem;
            }

            .statistics-header {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {

            /* Extra small devices */
            .stat-box h3 {
                font-size: 1.8rem;
            }

            .stat-box p {
                font-size: 0.85rem;
            }

            .nav-item {
                flex: 1 1 100%;
            }

            .table {
                font-size: 0.75rem;
            }

            .table thead th {
                font-size: 0.7rem;
            }

            /* Show only essential columns */
            .table th:nth-child(n+4),
            .table td:nth-child(n+4) {
                display: none;
            }

            .btn-sm {
                padding: 0.2rem 0.3rem;
                font-size: 0.7rem;
            }

            /* Stack filter buttons vertically */
            .filter-section .btn {
                width: 100%;
                margin-bottom: 5px;
            }
        }

        /* Tablet landscape */
        @media (min-width: 769px) and (max-width: 1024px) {
            .stat-box {
                min-width: 48%;
            }

            .nav-item {
                flex: 1 1 30%;
            }

            .table {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../assets/sidebar.php'; ?>
    <div class="content">
        <?php include '../assets/topbar.php'; ?>
        <div class="breadcrumb-area">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Mess Menu</li>
                </ol>
            </nav>
        </div>
        <div class="container-fluid">
            <div class="custom-tabs">
                <div class="statistics-header">
                    <i class="fas fa-chart-bar"></i>
                    Statistics Dashboard
                </div>

                <div class="statistics-container">
                    <div class="stat-box">
                        <h3 id="stat_menu">0</h3>
                        <p>Today's Menu Items</p>
                    </div>

                    <div class="stat-box">
                        <h3 id="stat_tokens">0</h3>
                        <p>Active Tokens</p>
                    </div>

                    <div class="stat-box">
                        <h3 id="stat_revenue">₹0</h3>
                        <p>Today's Revenue</p>
                    </div>

                    <div class="stat-box">
                        <h3 id="stat_month">0</h3>
                        <p>Month Tokens</p>
                    </div>
                </div>
            </div>

            <div class="custom-tabs">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#menu" type="button" role="tab">
                            <i class="fas fa-utensils"></i> Menu
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#specialtokens" type="button" role="tab">
                            <i class="fas fa-star"></i> Special Tokens
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tokens" type="button" role="tab">
                            <i class="fas fa-ticket-alt"></i> Token Requests
                        </button>
                    </li>
                    <?php if ($_SESSION['role'] === 'admin') { ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#revenue" type="button" role="tab">
                            <i class="fas fa-dollar-sign"></i> Revenue
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#consumption" type="button" role="tab">
                            <i class="fas fa-chart-pie"></i> Consumption
                        </button>
                    </li>
                    <?php } ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                            <i class="fas fa-history"></i> History
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="menu" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fas fa-utensils"></i> Menu Items</h5>
                        </div>
                        <div class="filter-section">
                            <div class="row align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label"><strong>Select Date:</strong></label>
                                    <input type="date" id="menuDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-primary" onclick="loadMenu()"><i class="fas fa-search"></i> View Menu</button>
                                </div>
                            </div>
                        </div>
                        <div class="loading" id="menuLoading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                        <div class="table-responsive">
                            <table id="messMenuTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>Date</th>
                                        <th>Meal Type</th>
                                        <th>Items</th>
                                        <th>Category</th>
                                        <th>Fee</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="specialtokens" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fas fa-star"></i> Special Tokens Management</h5>
                        </div>
                        <div class="loading" id="specialLoading" style="display:none;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                        <div class="table-responsive">
                            <table id="specialTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>Meal Type</th>
                                        <th>Items</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Token Date</th>
                                        <th>Fee (₹)</th>
                                        <th>Token Type</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tokens" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fas fa-ticket-alt"></i> Token Requests</h5>
                            <button class="btn btn-success" onclick="exportTokenRequests()"><i class="fas fa-file-pdf"></i> Export PDF</button>
                        </div>

                        <div class="row mb-3 g-2">
                            <div class="col-md-2">
                                <label class="form-label"><strong>Filter by Month:</strong></label>
                                <input type="month" id="requestsFilterMonth" class="form-control" value="<?php echo date('Y-m'); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><strong>Filter by Date:</strong></label>
                                <input type="date" id="requestsFilterDate" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><strong>Filter by Meal Type:</strong></label>
                                <select id="requestsFilterMealType" class="form-control">
                                    <option value="">All Meal Types</option>
                                    <option value="Breakfast">Breakfast</option>
                                    <option value="Lunch">Lunch</option>
                                    <option value="Snacks">Snacks</option>
                                    <option value="Dinner">Dinner</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><strong>Search Menu Items:</strong></label>
                                <input type="text" id="requestsFilterItem" class="form-control" placeholder="Type item name (e.g., Rice, Chicken)">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button class="btn btn-primary me-2" onclick="loadRequests()"><i class="fas fa-filter"></i> Apply Filter</button>
                                <button class="btn btn-secondary" onclick="resetRequestsFilter()"><i class="fas fa-undo"></i> Reset</button>
                            </div>
                        </div>

                        <div class="loading" id="requestsLoading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                        <div class="table-responsive">
                            <table id="requestsTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>S.NO</th>
                                        <th>Roll No.</th>
                                        <th>Name</th>
                                        <th>Meal Type</th>
                                        <th>Items</th>
                                        <th>Fee (₹)</th>
                                        <th>Token Date</th>
                                        <th>Status</th>
                                        <th>Requested At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <?php if ($_SESSION['role'] === 'admin') { ?>
                    <!-- Revenue Tab -->
                    <div class="tab-pane fade" id="revenue" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fas fa-dollar-sign"></i> Revenue Analysis</h5>
                            <button class="btn btn-success" onclick="exportRevenue()"><i class="fas fa-file-pdf"></i> Export PDF</button>
                        </div>

                        <!-- Month Filter -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label"><strong>Select Month:</strong></label>
                                <input type="month" id="revenueFilterMonth" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-primary w-100" onclick="applyRevenueFilter()"><i class="fas fa-filter"></i> Apply</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="revenueTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>Date</th>
                                        <th>Tokens Count</th>
                                        <th>Revenue (Rs.)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center">Select a month to view revenue</td>
                                    </tr>
                                </tbody>
                                <tfoot></tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Consumption Tab -->
                    <div class="tab-pane fade" id="consumption" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fas fa-chart-pie"></i> Student Consumption</h5>
                            <button class="btn btn-success" onclick="exportConsumption()"><i class="fas fa-file-pdf"></i> Export PDF</button>
                        </div>

                        <!-- Month Filter -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label"><strong>Select Month:</strong></label>
                                <input type="month" id="consumptionFilterMonth" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-primary w-100" onclick="applyConsumptionFilter()"><i class="fas fa-filter"></i> Apply</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="consumptionTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>Roll Number</th>
                                        <th>Student Name</th>
                                        <th>Tokens Count</th>
                                        <th>Total Spent (Rs.)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="5" class="text-center">Select a month to view consumption</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php } ?>

                    <div class="tab-pane fade" id="history" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fas fa-history"></i> Token History (Expired/Inactive)</h5>
                        </div>
                        <div class="loading" id="historyLoading" style="display:none;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                        <div class="table-responsive">
                            <table id="historyTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>Meal Type</th>
                                        <th>Items</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Token Date</th>
                                        <th>Fee (₹)</th>
                                        <th>Token Type</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Special Token Modal -->
        <div class="modal fade" id="specialtokenModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, #4e73df, #2e59d9); color: white;">
                        <h5 class="modal-title">Enable Special Token</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Token Type</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tokenType" id="limitedToken" value="limited" checked>
                                    <label class="form-check-label" for="limitedToken">Limited</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tokenType" id="unlimitedToken" value="unlimited">
                                    <label class="form-check-label" for="unlimitedToken">Unlimited</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3" id="limitInputContainer">
                            <label class="form-label">Number of Tokens</label>
                            <input type="number" id="tokenLimit" class="form-control" min="1" value="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" id="tokenfromDate" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Time</label>
                            <input type="time" id="tokenfromTime" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" id="tokentoDate" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Time</label>
                            <input type="time" id="tokentoTime" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Token Date</label>
                            <input type="date" id="tokenDate" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meal Type</label>
                            <select id="specialMealType" class="form-control" required>
                                <option value="">Select Meal Type</option>
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch">Lunch</option>
                                <option value="Snacks">Snacks</option>
                                <option value="Dinner">Dinner</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Menu Items</label>
                            <textarea id="specialMenuItems" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fee (₹)</label>
                            <input type="number" id="specialtokenFee" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="saveSpecialToken()">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editSpecialTokenModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, #4e73df, #2e59d9); color: white;">
                        <h5 class="modal-title">Edit Special Token</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editSpecialMenuId">
                        <div class="mb-3">
                            <label class="form-label">Token Type</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="editTokenType" id="editLimitedToken" value="limited" checked>
                                    <label class="form-check-label" for="editLimitedToken">Limited</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="editTokenType" id="editUnlimitedToken" value="unlimited">
                                    <label class="form-check-label" for="editUnlimitedToken">Unlimited</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3" id="editLimitInputContainer">
                            <label class="form-label">Number of Tokens</label>
                            <input type="number" id="editTokenLimit" class="form-control" min="1" value="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" id="editSpecialFromDate" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">From Time</label>
                            <input type="time" id="editSpecialFromTime" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" id="editSpecialToDate" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Time</label>
                            <input type="time" id="editSpecialToTime" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Token Date</label>
                            <input type="date" id="editSpecialTokenDate" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meal Type</label>
                            <select id="editSpecialMealType" class="form-control">
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch">Lunch</option>
                                <option value="Snacks">Snacks</option>
                                <option value="Dinner">Dinner</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Menu Items</label>
                            <textarea id="editSpecialMenuItems" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fee (₹)</label>
                            <input type="number" id="editSpecialFee" class="form-control" step="0.01">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="updateSpecialToken()">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="activateTokenModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, #4e73df, #2e59d9); color: white;">
                        <h5 class="modal-title">Activate Token - Extend Duration</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="activateMenuId">
                        <div class="mb-3">
                            <label class="form-label">New To Date</label>
                            <input type="date" id="activateToDate" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New To Time</label>
                            <input type="time" id="activateToTime" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success" onclick="saveActivateToken()">Activate Token</button>
                    </div>
                </div>
            </div>
        </div>
        <?php include '../assets/footer.php'; ?>
    </div>
    <script>
        let tables = {
            menu: null,
            special: null,
            requests: null,
            revenue: null,
            consumption: null,
            history: null
        };

        let historyTable = null;
        let specialTokensData = [];

        $(document).ready(function() {
            // Initialize Bootstrap tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            loadDashboard();
            loadMenu();
            setInterval(loadMenu, 30000);
            loadSpecialTokens();
            loadRequests();
            setInterval(loadDashboard, 30000);
            setInterval(loadSpecialTokens, 30000);
            setInterval(loadRequests, 30000);

            $('#requestsFilterMonth').val('<?php echo date('Y-m'); ?>');

            $('#requestsFilterItem').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    loadRequests();
                }
            });

            $('input[name="tokenType"]').on('change', function() {
                if ($(this).val() === 'limited') {
                    $('#limitInputContainer').show();
                } else {
                    $('#limitInputContainer').hide();
                }
            });

            $('input[name="editTokenType"]').on('change', function() {
                if ($(this).val() === 'limited') {
                    $('#editLimitInputContainer').show();
                } else {
                    $('#editLimitInputContainer').hide();
                }
            });
        });

        function loadDashboard() {
            $.ajax({
                url: '../api.php',
                type: 'POST',
                data: {
                    action: 'get_dashboard_stats'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#stat_menu').text(response.data.todays_menu_items || 0);
                        $('#stat_tokens').text(response.data.active_special_tokens || 0);
                        $('#stat_revenue').text('₹' + parseFloat(response.data.todays_revenue || 0).toFixed(2));
                        $('#stat_month').text(response.data.month_special_tokens || 0);
                    }
                },
                error: function(err) {
                    console.log('Dashboard error:', err);
                }
            });
        }

        function loadMenu() {
            $('#menuLoading').show();
            const date = $('#menuDate').val();
            $.ajax({
                url: '../api.php',
                type: 'POST',
                data: {
                    action: 'get_mess_menu',
                    date: date
                },
                dataType: 'json',
                success: function(response) {
                    $('#menuLoading').hide();
                    if (response.success) displayMenu(response.data);
                },
                error: function(err) {
                    $('#menuLoading').hide();
                    console.log('Menu error:', err);
                }
            });
        }

        function displayMenu(data) {
            if (tables.menu) {
                tables.menu.destroy();
                tables.menu = null;
            }
            const tbody = $('#messMenuTable tbody').empty();
            if (!data || data.length === 0) {
                tbody.html('<tr><td colspan="7" class="text-center">No menu found for this date</td></tr>');
                return;
            }
            data.forEach((item, index) => {
                const categoryBadge = item.category ? `<span class="badge bg-info">${item.category}</span>` : '<span class="badge bg-secondary">N/A</span>';
                tbody.append(`<tr><td>${index + 1}</td><td>${item.date}</td><td><span class="badge bg-primary">${item.meal_type}</span></td><td>${item.items}</td><td>${categoryBadge}</td><td>₹${parseFloat(item.fee).toFixed(2)}</td><td><span class="badge bg-success">Active</span></td></tr>`);
            });
            tables.menu = $('#messMenuTable').DataTable({
                paging: true,
                pageLength: 10,
                searching: true,
                ordering: true
            });
        }

        function loadSpecialTokens() {
            $('#specialLoading').show();
            $('#historyLoading').show();
            
            $.post('../api.php', {
                action: 'read_special_tokens'
            }, function(response1) {
                $.post('../api.php', {
                    action: 'read_inactive_special_tokens'
                }, function(response2) {
                    $('#specialLoading').hide();
                    $('#historyLoading').hide();
                    
                    let activeTokens = [];
                    let expiredTokens = [];
                    
                    if (response1 && response1.success && Array.isArray(response1.data)) {
                        const now = new Date();
                        response1.data.forEach(token => {
                            const toDateTime = new Date(token.to_date + ' ' + token.to_time);
                            if (now > toDateTime) {
                                expiredTokens.push({...token, status_type: 'expired'});
                            } else {
                                activeTokens.push({...token, status_type: 'active'});
                            }
                        });
                    }
                    
                    if (response2 && response2.success && Array.isArray(response2.data)) {
                        expiredTokens = expiredTokens.concat(response2.data.map(t => ({...t, status_type: 'inactive'})));
                    }
                    
                    specialTokensData = activeTokens;
                    displaySpecialTokens(activeTokens);
                    displayTokenHistory(expiredTokens);
                }, 'json').fail(function(err) {
                    $('#specialLoading').hide();
                    $('#historyLoading').hide();
                    console.error('Error loading inactive tokens:', err);
                });
            }, 'json').fail(function(err) {
                $('#specialLoading').hide();
                $('#historyLoading').hide();
                console.error('Error loading special tokens:', err);
            });
        }

        function displaySpecialTokens(data) {
            if (tables.special) {
                tables.special.destroy();
                tables.special = null;
            }

            const tbody = $('#specialTable tbody').empty();

            if (!data || data.length === 0) {
                tbody.html('<tr><td colspan="10" class="text-center">No active special tokens</td></tr>');
                return;
            }

            data.forEach((item, i) => {
                let tokenTypeDisplay;
                if (parseInt(item.max_usage) === -1) {
                    tokenTypeDisplay = 'Unlimited';
                } else {
                    tokenTypeDisplay = `Limited (${item.max_usage})`;
                }

                const actionButtons = `<button class="btn btn-danger btn-sm me-2" onclick="deactivateSpecialToken(${item.menu_id})" data-bs-toggle="tooltip" data-bs-title="Deactivate"><i class="fas fa-pause"></i></button>
                                   <button class="btn btn-secondary btn-sm" onclick="deleteSpecialToken(${item.menu_id})" data-bs-toggle="tooltip" data-bs-title="Delete"><i class="fas fa-trash"></i></button>`;

                tbody.append(`
                <tr>
                    <td>${i + 1}</td>
                    <td><strong>${item.meal_type}</strong></td>
                    <td>${item.menu_items}</td>
                    <td>${item.from_date}<br><small>${item.from_time.substring(0,5)}</small></td>
                    <td>${item.to_date}<br><small>${item.to_time.substring(0,5)}</small></td>
                    <td><strong>${item.token_date}</strong></td>
                    <td>₹${parseFloat(item.fee).toFixed(2)}</td>
                    <td><span class="badge bg-info">${tokenTypeDisplay}</span></td>
                    <td><span class="badge bg-success">Active</span></td>
                    <td>${actionButtons}</td>
                </tr>`);
            });

            const tooltipTriggerListSpecial = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerListSpecial.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            tables.special = $('#specialTable').DataTable({
                paging: true,
                pageLength: 10,
                order: [[5, 'desc']]
            });
        }

        function displayTokenHistory(data) {
            if (tables.history) {
                tables.history.destroy();
                tables.history = null;
            }

            const tbody = $('#historyTable tbody').empty();

            if (!data || data.length === 0) {
                tbody.html('<tr><td colspan="10" class="text-center">No expired or inactive tokens</td></tr>');
                return;
            }

            data.forEach((item, i) => {
                let tokenTypeDisplay;
                if (parseInt(item.max_usage) === -1) {
                    tokenTypeDisplay = 'Unlimited';
                } else {
                    tokenTypeDisplay = `Limited (${item.max_usage})`;
                }

                let statusBadge, actionButton;
                
                if (item.status_type === 'expired') {
                    statusBadge = '<span class="badge bg-danger">Expired</span>';
                } else {
                    statusBadge = '<span class="badge bg-warning text-dark">Inactive</span>';
                }
                
                actionButton = `<button class="btn btn-success btn-sm me-2" onclick="activateSpecialToken(${item.menu_id})" data-bs-toggle="tooltip" data-bs-title="Activate"><i class="fas fa-play"></i></button>
                               <button class="btn btn-secondary btn-sm" onclick="deleteSpecialToken(${item.menu_id})" data-bs-toggle="tooltip" data-bs-title="Delete"><i class="fas fa-trash"></i></button>`;

                tbody.append(`
                <tr>
                    <td>${i + 1}</td>
                    <td><strong>${item.meal_type}</strong></td>
                    <td>${item.menu_items}</td>
                    <td>${item.from_date}<br><small>${item.from_time.substring(0,5)}</small></td>
                    <td>${item.to_date}<br><small>${item.to_time.substring(0,5)}</small></td>
                    <td><strong>${item.token_date}</strong></td>
                    <td>₹${parseFloat(item.fee).toFixed(2)}</td>
                    <td><span class="badge bg-info">${tokenTypeDisplay}</span></td>
                    <td>${statusBadge}</td>
                    <td>${actionButton}</td>
                </tr>`);
            });

            const tooltipTriggerListHistory = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerListHistory.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            tables.history = $('#historyTable').DataTable({
                paging: true,
                pageLength: 10,
                order: [[5, 'desc']]
            });
        }


        function loadRequests() {
            $('#requestsLoading').show();

            // Get filter values
            const filterMonth = $('#requestsFilterMonth').val();
            const filterDate = $('#requestsFilterDate').val();
            const filterMealType = $('#requestsFilterMealType').val();
            const filterItem = $('#requestsFilterItem').val().trim();

            $.ajax({
                url: '../api.php',
                type: 'POST',
                data: {
                    action: 'get_token_requests',
                    filter_month: filterMonth,
                    filter_date: filterDate,
                    filter_meal_type: filterMealType,
                    filter_item: filterItem
                },
                dataType: 'json',
                success: function(response) {
                    $('#requestsLoading').hide();
                    if (response.success) displayRequests(response.data);
                },
                error: function(err) {
                    $('#requestsLoading').hide();
                    console.log('Requests error:', err);
                }
            });
        }

        function saveSpecialToken() {
            const tokenType = $('input[name="tokenType"]:checked').val();
            const maxUsage = tokenType === 'unlimited' ? -1 : parseInt($('#tokenLimit').val() || '1', 10);
            const payload = {
                action: 'create_special_token',
                from_date: $('#tokenfromDate').val(),
                from_time: $('#tokenfromTime').val(),
                to_date: $('#tokentoDate').val(),
                to_time: $('#tokentoTime').val(),
                token_date: $('#tokenDate').val(),
                meal_type: $('#specialMealType').val(),
                menu_items: $('#specialMenuItems').val(),
                fee: $('#specialtokenFee').val(),
                max_usage: maxUsage
            };

            if (!payload.from_date || !payload.from_time || !payload.to_date || !payload.to_time || !payload.token_date || !payload.meal_type || !payload.menu_items || !payload.fee) {
                Swal.fire('Error', 'Please fill all required fields', 'error');
                return;
            }

            $.post('../api.php', payload, function(response) {
                if (response && response.success) {
                    Swal.fire('Success', response.message || 'Special token created', 'success');
                    $('#specialtokenModal').modal('hide');
                    $('#specialtokenModal input, #specialtokenModal textarea').not('[type=radio]').val('');
                    $('#limitedToken').prop('checked', true);
                    $('#limitInputContainer').show();
                    loadSpecialTokens();
                    loadDashboard();
                } else {
                    Swal.fire('Error', response.message || 'Failed to create special token', 'error');
                }
            }, 'json');
        }

        function editSpecialToken(menuId) {
            const token = specialTokensData.find(t => parseInt(t.menu_id, 10) === parseInt(menuId, 10));
            if (!token) {
                Swal.fire('Error', 'Token not found', 'error');
                return;
            }

            $('#editSpecialMenuId').val(token.menu_id);
            $('#editSpecialFromDate').val(token.from_date || '');
            $('#editSpecialFromTime').val((token.from_time || '').substring(0, 5));
            $('#editSpecialToDate').val(token.to_date || '');
            $('#editSpecialToTime').val((token.to_time || '').substring(0, 5));
            $('#editSpecialTokenDate').val(token.token_date || '');
            $('#editSpecialMealType').val(token.meal_type || '');
            $('#editSpecialMenuItems').val(token.menu_items || '');
            $('#editSpecialFee').val(token.fee || 0);

            if (parseInt(token.max_usage, 10) === -1) {
                $('#editUnlimitedToken').prop('checked', true);
                $('#editLimitInputContainer').hide();
                $('#editTokenLimit').val('');
            } else {
                $('#editLimitedToken').prop('checked', true);
                $('#editLimitInputContainer').show();
                $('#editTokenLimit').val(parseInt(token.max_usage, 10) || 1);
            }

            new bootstrap.Modal(document.getElementById('editSpecialTokenModal')).show();
        }

        function updateSpecialToken() {
            const tokenType = $('input[name="editTokenType"]:checked').val();
            const maxUsage = tokenType === 'unlimited' ? -1 : parseInt($('#editTokenLimit').val() || '1', 10);
            const payload = {
                action: 'update_special_token',
                menu_id: $('#editSpecialMenuId').val(),
                from_date: $('#editSpecialFromDate').val(),
                from_time: $('#editSpecialFromTime').val(),
                to_date: $('#editSpecialToDate').val(),
                to_time: $('#editSpecialToTime').val(),
                token_date: $('#editSpecialTokenDate').val(),
                meal_type: $('#editSpecialMealType').val(),
                menu_items: $('#editSpecialMenuItems').val(),
                fee: $('#editSpecialFee').val(),
                max_usage: maxUsage
            };

            if (!payload.menu_id || !payload.to_date || !payload.to_time) {
                Swal.fire('Error', 'Missing token details', 'error');
                return;
            }

            if (tokenType === 'limited' && (!maxUsage || maxUsage < 1)) {
                Swal.fire('Error', 'Enter a valid token limit', 'error');
                return;
            }

            $.post('../api.php', payload, function(response) {
                if (response && response.success) {
                    Swal.fire('Success', response.message || 'Special token updated', 'success');
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('editSpecialTokenModal')).hide();
                    loadSpecialTokens();
                    loadDashboard();
                } else {
                    Swal.fire('Error', response.message || 'Failed to update special token', 'error');
                }
            }, 'json');
        }

        function deleteSpecialToken(menuId) {
            Swal.fire({
                title: 'Delete Special Token?',
                text: 'This action cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                $.post('../api.php', {
                    action: 'delete_special_token',
                    menu_id: menuId
                }, function(response) {
                    if (response && response.success) {
                        Swal.fire('Deleted!', response.message || 'Special token deleted', 'success');
                        loadSpecialTokens();
                        loadDashboard();
                    } else {
                        Swal.fire('Error', response.message || 'Failed to delete special token', 'error');
                    }
                }, 'json');
            });
        }

        function activateToken(menuId) {
            Swal.fire({
                title: 'Activate Token?',
                text: 'This will activate the token and change its status to Active.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, activate it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../api.php', {
                        action: 'activate_special_token',
                        menu_id: menuId
                    }, function(response) {
                        if (response && response.success) {
                            Swal.fire('Success!', 'Token activated successfully', 'success');
                            loadSpecialTokens();
                            loadDashboard();
                        } else {
                            Swal.fire('Error', response.message || 'Failed to activate token', 'error');
                        }
                    }, 'json').fail(function(err) {
                        Swal.fire('Error', 'Network error: ' + err.statusText, 'error');
                        console.error('Activation error:', err);
                    });
                }
            });
        }

        function saveActivateToken() {
            const payload = {
                action: 'update_special_token',
                menu_id: $('#activateMenuId').val(),
                to_date: $('#activateToDate').val(),
                to_time: $('#activateToTime').val()
            };

            if (!payload.menu_id || !payload.to_date || !payload.to_time) {
                Swal.fire('Error', 'Please provide To Date and To Time', 'error');
                return;
            }

            $.post('../api.php', payload, function(response) {
                if (response && response.success) {
                    Swal.fire('Success', response.message || 'Token activated', 'success');
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('activateTokenModal')).hide();
                    loadSpecialTokens();
                    loadDashboard();
                } else {
                    Swal.fire('Error', response.message || 'Failed to activate token', 'error');
                }
            }, 'json');
        }

        function deactivateSpecialToken(menuId) {
            Swal.fire({
                title: 'Deactivate Token?',
                text: 'This will change the token status to Inactive. It will appear in the history.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, deactivate it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../api.php', {
                        action: 'deactivate_special_token',
                        menu_id: menuId
                    }, function(response) {
                        if (response && response.success) {
                            Swal.fire('Success!', 'Token deactivated successfully', 'success');
                            loadSpecialTokens();
                            loadDashboard();
                        } else {
                            Swal.fire('Error', response.message || 'Failed to deactivate token', 'error');
                        }
                    }, 'json').fail(function(err) {
                        Swal.fire('Error', 'Network error: ' + err.statusText, 'error');
                        console.error('Deactivation error:', err);
                    });
                }
            });
        }

        function activateSpecialToken(menuId) {
            Swal.fire({
                title: 'Activate Token?',
                text: 'This will change the token status to Active. It will appear in the main tokens list.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, activate it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../api.php', {
                        action: 'activate_special_token_status',
                        menu_id: menuId
                    }, function(response) {
                        if (response && response.success) {
                            Swal.fire('Success!', 'Token activated successfully', 'success');
                            loadSpecialTokens();
                            loadDashboard();
                        } else {
                            Swal.fire('Error', response.message || 'Failed to activate token', 'error');
                        }
                    }, 'json').fail(function(err) {
                        Swal.fire('Error', 'Network error: ' + err.statusText, 'error');
                        console.error('Activation error:', err);
                    });
                }
            });
        }

        function resetRequestsFilter() {
            $('#requestsFilterMonth').val('<?php echo date('Y-m'); ?>');
            $('#requestsFilterDate').val('');
            $('#requestsFilterMealType').val('');
            $('#requestsFilterItem').val('');
            loadRequests();
        }

        function displayRequests(data) {
            if (tables.requests) {
                tables.requests.destroy();
                tables.requests = null;
            }
            const tbody = $('#requestsTable tbody').empty();
            if (!data || data.length === 0) {
                tbody.html('<tr><td colspan="10" class="text-center">No token requests</td></tr>');
                return;
            }
            data.forEach((item, i) => {
                let requestedAtFormatted = 'N/A';
                if (item.requested_at && item.requested_at !== 'N/A') {
                    try {
                        const dateObj = new Date(item.requested_at);
                        if (!isNaN(dateObj.getTime())) {
                            requestedAtFormatted = dateObj.toLocaleString('en-IN', {
                                year: 'numeric',
                                month: '2-digit',
                                day: '2-digit',
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                                hour12: true
                            });
                        }
                    } catch (e) {
                        requestedAtFormatted = item.requested_at;
                    }
                }
                const status = item.status || 'Generated';
                let statusBadge = '<span class="badge bg-warning text-dark">Generated</span>';
                let actionButton = `<button class="btn btn-sm btn-success me-2" onclick="activateTokenRequest(${item.token_id})" data-bs-toggle="tooltip" data-bs-title="Approve"><i class="fas fa-check"></i></button>
                <button class="btn btn-sm btn-danger" onclick="deactivateTokenRequest(${item.token_id})" data-bs-toggle="tooltip" data-bs-title="Reject"><i class="fas fa-ban"></i></button>`;
                
                if (status === 'Used') {
                    statusBadge = '<span class="badge bg-success">Used</span>';
                    actionButton = '<span class="badge bg-secondary">No Action</span>';
                } else if (status === 'Cancelled') {
                    statusBadge = '<span class="badge bg-danger">Cancelled</span>';
                    actionButton = '<span class="badge bg-secondary">No Action</span>';
                } else if (status === 'Expired') {
                    statusBadge = '<span class="badge bg-info">Expired</span>';
                    actionButton = '<span class="badge bg-secondary">No Action</span>';
                }
                
                tbody.append(`<tr><td>${i+1}</td>
                <td><strong>${item.roll_number}</strong></td>
                <td>${item.student_name}</td>
                <td>${item.meal_type}</td>
                <td>${item.menu_items}</td>
                <td>₹${parseFloat(item.fee).toFixed(2)}</td>
                <td><strong>${item.token_date}</strong></td>
                <td>${statusBadge}</td>
                <td>${requestedAtFormatted}</td>
                <td>${actionButton}</td></tr>`);
            });
            // Re-initialize tooltips after DataTable renders
            const tooltipTriggerList2 = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList2.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            tables.requests = $('#requestsTable').DataTable({
                paging: true,
                pageLength: 10
            });
        }

        function activateTokenRequest(tokenId) {
            Swal.fire({
                title: 'Approve Token Request?',
                text: 'This will mark the token as approved and ready for use.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, approve it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../api.php', {
                        action: 'activate_token_request',
                        token_id: tokenId
                    }, function(response) {
                        if (response && response.success) {
                            Swal.fire('Success!', response.message || 'Token approved successfully', 'success');
                            loadRequests();
                            loadDashboard();
                        } else {
                            Swal.fire('Error', response.message || 'Failed to approve token', 'error');
                        }
                    }, 'json');
                }
            });
        }

        function deactivateTokenRequest(tokenId) {
            Swal.fire({
                title: 'Reject Token Request?',
                text: 'This will cancel the token request.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, reject it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../api.php', {
                        action: 'deactivate_token_request',
                        token_id: tokenId
                    }, function(response) {
                        if (response && response.success) {
                            Swal.fire('Success!', response.message || 'Token request cancelled', 'success');
                            loadRequests();
                            loadDashboard();
                        } else {
                            Swal.fire('Error', response.message || 'Failed to cancel token request', 'error');
                        }
                    }, 'json');
                }
            });
        }

        function viewHistory(type) {
            const action = type === 'menu' ? 'get_menu_history' : 'get_special_token_history';
            $.post('../api.php', {
                action: action
            }, function(response) {
                if (historyTable) {
                    historyTable.destroy();
                    historyTable = null;
                }

                const tableBody = $('#historyTable tbody');
                tableBody.empty();

                if (!response || !response.success || !Array.isArray(response.data) || response.data.length === 0) {
                    tableBody.html('<tr><td colspan="6" class="text-center">No history found</td></tr>');
                    return;
                }

                response.data.forEach(function(item) {
                    tableBody.append(`<tr><td>${item.type || 'N/A'}</td><td>${item.date || 'N/A'}</td><td>${item.details || 'N/A'}</td><td>${item.description || 'N/A'}</td><td>₹${parseFloat(item.amount || 0).toFixed(2)}</td><td>${item.timestamp || 'N/A'}</td></tr>`);
                });

                historyTable = $('#historyTable').DataTable({
                    paging: true,
                    pageLength: 10,
                    searching: true,
                    ordering: true
                });
            }, 'json');
        }

        function applyRevenueFilter() {
            const month = $('#revenueFilterMonth').val();
            if (!month) {
                Swal.fire('Error', 'Please select a month', 'error');
                return;
            }

            $.ajax({
                url: '../api.php',
                type: 'POST',
                data: {
                    action: 'get_revenue',
                    filter_month: month
                },
                dataType: 'json'
            }).done(function(response) {
                if (response && response.success) {
                    displayRevenue(response.data);
                } else {
                    displayRevenue([]);
                }
            }).fail(function() {
                displayRevenue([]);
            });
        }

        function displayRevenue(data) {
            if (tables.revenue) {
                tables.revenue.destroy();
                tables.revenue = null;
            }

            const $table = $('#revenueTable');
            const $tbody = $table.find('tbody');
            const $tfoot = $table.find('tfoot');

            $tbody.empty();
            $tfoot.empty();

            if (!data || data.length === 0) {
                $tbody.html(
                    '<tr><td colspan="4" class="text-center">No revenue data found for selected month</td></tr>'
                );
                return;
            }

            let totalRevenue = 0;
            let totalTokens = 0;

            data.forEach(function(row, index) {
                const revenue = parseFloat(row.revenue || 0);
                const tokens = parseInt(row.tokens_count || 0, 10);

                totalRevenue += revenue;
                totalTokens += tokens;

                const dateText = row.date ?
                    new Date(row.date).toLocaleDateString('en-GB') :
                    'N/A';

                $tbody.append(`
            <tr>
                <td>${index + 1}</td>
                <td>${dateText}</td>
                <td>${tokens}</td>
                <td>Rs.${revenue.toFixed(2)}</td>
            </tr>
        `);
            });

            // TOTAL row in FOOTER
            $tfoot.append(`
        <tr class="table-info fw-bold" style="align-items: center;">
            <td></td>
            <td>TOTAL</td>
            <td>${totalTokens}</td>
            <td>Rs.${totalRevenue.toFixed(2)}</td>
        </tr>
    `);

            tables.revenue = $table.DataTable({
                paging: true,
                searching: true,
                ordering: true,
                pageLength: 31,
                order: [
                    [1, 'desc']
                ],
                columnDefs: [{
                        className: "text-center",
                        targets: "_all"
                    }
                ]
            });
        }

        // ========== CONSUMPTION FUNCTIONS ==========
        function applyConsumptionFilter() {
            const month = $('#consumptionFilterMonth').val();
            if (!month) {
                Swal.fire('Error', 'Please select a month', 'error');
                return;
            }

            $.ajax({
                url: '../api.php',
                type: 'POST',
                data: {
                    action: 'get_consumption',
                    filter_month: month
                },
                dataType: 'json'
            }).done(function(response) {
                if (response && response.success) {
                    displayConsumption(response.data);
                } else {
                    displayConsumption([]);
                }
            }).fail(function() {
                displayConsumption([]);
            });
        }

        function displayConsumption(data) {
            const $table = $('#consumptionTable');

            // Safely destroy existing DataTable instance if any
            if ($.fn.DataTable.isDataTable('#consumptionTable')) {
                $table.DataTable().clear().destroy();
            }

            const $tbody = $table.find('tbody');
            const $tfoot = $table.find('tfoot');

            $tbody.empty();
            $tfoot.empty();

            if (!data || data.length === 0) {
                $tbody.html(
                    '<tr><td colspan="5" class="text-center">No consumption data found for selected month</td></tr>'
                );
                return;
            }

            let totalSpent = 0;
            let totalTokensCount = 0;

            data.forEach(function(row, index) {
                const spent = parseFloat(row.total_spent || 0);
                const tokens = parseInt(row.tokens_count || 0, 10);

                totalSpent += spent;
                totalTokensCount += tokens;

                $tbody.append(`
            <tr>
                <td>${index + 1}</td>
                <td>${row.roll_number || 'N/A'}</td>
                <td>${row.student_name || 'Unknown'}</td>
                <td><span class="badge bg-primary">${tokens}</span></td>
                <td>Rs.${spent.toFixed(2)}</td>
            </tr>
        `);
            });

            // TOTAL row in footer (stays at bottom, not sorted)
            $tfoot.append(`
        <tr class="table-info fw-bold text-center">
            <td></td>
            <td colspan="2">TOTAL</td>
            <td>${totalTokensCount}</td>
            <td>Rs.${totalSpent.toFixed(2)}</td>
        </tr>
    `);

            // Initialize DataTable
            tables.consumption = $table.DataTable({
                paging: true,
                searching: true,
                ordering: true,
                pageLength: 60,
                order: [
                    [4, 'desc']
                ], // sort by Spent
                columnDefs: [{
                        className: "text-center",
                        targets: "_all"
                    },
                    {
                        orderable: false,
                        targets: 0
                    }
                ]
            });
        }



        // Export functions
        function exportTokenRequests() {
            const filterMonth = $('#requestsFilterMonth').val();
            const filterDate = $('#requestsFilterDate').val();
            const filterMealType = $('#requestsFilterMealType').val();
            const filterItem = $('#requestsFilterItem').val().trim();

            let url = '../mess/messexport.php?type=token_requests';
            if (filterMonth) url += '&filter_month=' + encodeURIComponent(filterMonth);
            if (filterDate) url += '&filter_date=' + encodeURIComponent(filterDate);
            if (filterMealType) url += '&filter_meal_type=' + encodeURIComponent(filterMealType);
            if (filterItem) url += '&filter_item=' + encodeURIComponent(filterItem);

            window.open(url, '_blank');
        }

        function exportRevenue() {
            const month = $('#revenueFilterMonth').val();
            let url = '../mess/messexport.php?type=revenue';
            if (month) url += '&filter_month=' + encodeURIComponent(month);

            window.open(url, '_blank');
        }

        function exportConsumption() {
            const month = $('#consumptionFilterMonth').val();
            let url = '../mess/messexport.php?type=consumption';
            if (month) url += '&filter_month=' + encodeURIComponent(month);

            window.open(url, '_blank');
        }
    </script>
</body>

</html>