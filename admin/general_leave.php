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

    <?php include '../db.php'; ?>
    <?php
    $sessionRole = get_current_session_role();
    $isSuperAdminGeneralLeave = is_super_admin_role($sessionRole);
    if ($isSuperAdminGeneralLeave) {
        $activeGeneralLeaveScopeSql = "1=1";
        $historyGeneralLeaveScopeSql = "1=1";
    } else {
        $roleScopeSql = "Enabled_By = '" . $conn->real_escape_string($sessionRole) . "'";
        $activeGeneralLeaveScopeSql = $roleScopeSql;
        $historyGeneralLeaveScopeSql = $roleScopeSql;
    }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
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
        /* For vertical alignment */
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


    /* loader */
    .loader-container {
        position: fixed;
        left: var(--sidebar-width);
        right: 0;
        top: var(--topbar-height);
        bottom: var(--footer-height);
        background: rgba(255, 255, 255, 0.95);
        display: flex;
        /* Changed from 'none' to show by default */
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

    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    /* Hide default HTML checkbox */
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    /* The slider */
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked+.slider {
        background-color: #2196F3;
    }

    input:focus+.slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked+.slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }

    /* Rounded sliders */
    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }
    </style>
</head>

<body>


    <!-- Sidebar -->
    <?php include '../assets/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content">

        <div class="loader-container" id="loaderContainer">
            <div class="loader"></div>
        </div>

        <!-- Topbar -->
        <?php include '../assets/topbar.php'; ?>

        <!-- Breadcrumb -->
        <div class="breadcrumb-area custom-gradient">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">General Leave Management</li>
                </ol>
            </nav>
        </div>

        <!-- Content Area -->
        <div class="container-fluid">
            <div class="custom-tabs">
                <ul class="nav nav-tabs" role="tablist">
                    <!-- Center the main tabs -->
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="tab" id="family-main-tab" href="#enableLeave-content"
                            role="tab" aria-selected="true">
                            <span class="hidden-xs-down" style="font-size: 0.9em;"><i
                                    class="fas fa-repeat tab-icon"></i>
                                Enable / Disable Leave </span>
                        </a>
                    </li>

                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" id="processed-main-tab" href="#processed-content"
                            role="tab" aria-selected="false">
                            <span class="hidden-xs-down" style="font-size: 0.9em;"><i
                                    class="fas fa-clock-rotate-left tab-icon"></i> History </span>
                        </a>
                    </li>
                </ul>


                <div class="tab-content mt-3">
                    <!-- enableLeave Tab Content -->
                    <div class="tab-pane fade show active" id="enableLeave-content" role="tabpanel"
                        aria-labelledby="family-main-tab">
          

                            <div class="card-body">
                                <div class="table-responsive" id="enableLeaveTable">
                                    <?php


                                    // First, check for any enabled leaves that have expired and disable them
                                    $disableExpiredSql = "UPDATE general_Leave 
                                                         SET Is_Enabled = 0 
                                                         WHERE Is_Enabled = 1 
                                                         AND To_Date < NOW()
                                                         AND " . $activeGeneralLeaveScopeSql;
                                    $disableResult = mysqli_query($conn, $disableExpiredSql);
                                    
                                   
                                    // check enabled leave
                                    $sql = "SELECT * FROM general_Leave WHERE Is_Enabled = 1 AND " . $activeGeneralLeaveScopeSql . " ORDER BY GeneralLeave_ID DESC";
                                    $result = mysqli_query($conn, $sql);
                                    $activeLeaves = [];
                                    if ($result) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $activeLeaves[] = $row;
                                        }
                                    }
                                    ?>

                                    <div class="text-center">
                                        <?php if (!empty($activeLeaves)): ?>

                                        <?php foreach ($activeLeaves as $activeLeave): ?>
                                        <?php
                                            $enabledBy = strtolower(trim($activeLeave['Enabled_By'] ?? ''));
                                            $scopeLabel = 'Common Scope';
                                            $scopeClass = 'bg-primary';
                                            if ($enabledBy === 'male_admin') {
                                                $scopeLabel = 'Boys Scope';
                                                $scopeClass = 'bg-info';
                                            } elseif ($enabledBy === 'female_admin') {
                                                $scopeLabel = 'Girls Scope';
                                                $scopeClass = 'bg-danger';
                                            }
                                        ?>
                                        <div class="alert alert-success mb-3 text-start" role="alert">
                                            <h5 class="alert-heading d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-calendar-check me-2"></i>Active General Leave</span>
                                                <span class="badge <?php echo $scopeClass; ?>"><?php echo $scopeLabel; ?></span>
                                            </h5>
                                            <p class="mb-2"><strong>Leave Name:</strong>
                                                <?php echo htmlspecialchars($activeLeave['Leave_Name']); ?></p>
                                            <p class="mb-2"><strong>From:</strong>
                                                <?php echo date('d-m-Y h:i A', strtotime($activeLeave['From_Date'])); ?>
                                            </p>
                                            <p class="mb-2"><strong>To:</strong>
                                                <?php echo date('d-m-Y h:i A', strtotime($activeLeave['To_Date'])); ?>
                                            </p>
                                            <?php if (!empty($activeLeave['Instructions'])): ?>
                                            <p class="mb-2"><strong>Instructions:</strong>
                                                <?php echo htmlspecialchars($activeLeave['Instructions']); ?></p>
                                            <?php endif; ?>
                                            <hr>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <button type="button" class="btn btn-warning editLeaveBtn"
                                                    data-leave-id="<?php echo $activeLeave['GeneralLeave_ID']; ?>"
                                                    data-leave-name="<?php echo htmlspecialchars($activeLeave['Leave_Name']); ?>"
                                                    data-from-date="<?php echo date('Y-m-d\TH:i', strtotime($activeLeave['From_Date'])); ?>"
                                                    data-to-date="<?php echo date('Y-m-d\TH:i', strtotime($activeLeave['To_Date'])); ?>"
                                                    data-instructions="<?php echo htmlspecialchars($activeLeave['Instructions']); ?>">
                                                    <i class="fas fa-edit me-1"></i> Edit Leave
                                                </button>
                                                <button type="button" class="btn btn-danger disableLeaveBtn"
                                                    data-leave-id="<?php echo $activeLeave['GeneralLeave_ID']; ?>">
                                                    <i class="fas fa-times-circle me-1"></i> Cancel Leave
                                                </button>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php else: ?>

                                        <!--General Leave activated ah illana-->
                                        <div class="alert alert-info mb-3" role="alert">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No active General General Leave. Click the button below to enable general
                                            leave for students.
                                        </div>
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                            id="enableLeaveBtn" data-bs-target="#leaveModal">
                                            <i class="fas fa-calendar-plus me-1"></i>Enable Leave
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                   
                    </div>

                    <!-- History Tab Content -->
                    <div class="tab-pane fade" id="processed-content" role="tabpanel"
                        aria-labelledby="processed-main-tab">
 
                            <div class="card-body">
                                <!-- Report buttons -->
                                <div class="d-flex justify-content-end mb-2">
                                    <button type="button" id="exportPdfBtn" onclick="generateGeneralLeavePDF();" style="margin-right: 5px;" class="btn btn-danger btn-sm" title="Export General Leave to PDF">
                                        <i class="fa-solid fa-file-pdf"></i> Generate PDF
                                    </button>
                                    <button type="button" id="exportExcelBtn" onclick="exportGeneralLeaveExcel();" class="btn btn-success btn-sm" title="Export General Leave to Excel">
                                        <i class="fa-solid fa-file-excel"></i> Export Excel
                                    </button>
                                </div>

                                <table class="table table-bordered" id="generalLeave-table" width="100%" cellspacing="0">
                                    <thead class="gradient-header">
                                        <tr>
                                            <th>S.No</th>
                                            <th>Leave Name</th>
                                            <th>Created Date</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Instructions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql="SELECT * FROM general_Leave WHERE Is_Enabled = 0 AND " . $historyGeneralLeaveScopeSql . " ORDER BY GeneralLeave_ID DESC";
                                        $result = mysqli_query($conn, $sql);
                                        $sno=1;
                                        while($row=mysqli_fetch_assoc($result)){
                                            echo "<tr>";
                                            echo "<td>".$sno++."</td>";
                                            echo "<td>".htmlspecialchars($row['Leave_Name'])."</td>";
                                       
                                            $createdDate = date('d-m-Y h:i A', strtotime($row['Created_Date']));
                                            $fromDate = date('d-m-Y h:i A', strtotime($row['From_Date']));
                                            $toDate = date('d-m-Y h:i A', strtotime($row['To_Date']));

                                            echo "<td>".$createdDate."</td>";
                                            echo "<td>".$fromDate."</td>";
                                            echo "<td>".$toDate."</td>";
                                       
                                            if (!empty($row['Instructions'])) {
                                                echo "<td class='text-center align-middle'>
                                                    ".htmlspecialchars($row['Instructions'])."
                                                </td>";
                                            } else {
                                                echo "<td class='text-center align-middle text-muted'>No Instructions</td>";
                                            }
                                            echo "</tr>";
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

    <!--Leave Modal-->
    <div class="modal fade" id="leaveModal" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white" id="modalHeader">
                    <h5 class="modal-title" id="leaveModalLabel">
                        Enable General Leave
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="leaveForm">
                    <input type="hidden" id="leaveId" name="leave_id" value="">
                    <input type="hidden" id="formMode" value="create">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="leaveName" class="form-label">
                                    Leave Name
                                </label>
                                <input type="text" class="form-control" id="leaveName" name="leave_name"
                                    placeholder="Enter Leave name" required>

                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fromDateTime" class="form-label">
                                    From Date & Time
                                </label>
                                <input type="datetime-local" class="form-control" id="fromDateTime" name="from_date"
                                    required>

                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="toDateTime" class="form-label">
                                    To Date & Time
                                </label>
                                <input type="datetime-local" class="form-control" id="toDateTime" name="to_date"
                                    required>

                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="leaveDescription" class="form-label">
                                    Insturctions
                                </label>
                                <textarea class="form-control" id="leaveDescription" name="description" rows="1"
                                    placeholder="Enter Instructions / Description"></textarea>
                            </div>
                        </div>

                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> Once enabled, students will be able to apply for General leave during
                            the specified period.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success" id="submitBtn">
                            <i class="fas fa-check me-1"></i> <span id="submitBtnText">Enable General Leave</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!-- Footer -->
    <?php include '../assets/footer.php'; ?>
    </div>
    <script>
    const loaderContainer = document.getElementById('loaderContainer');

    function showLoader() {
        loaderContainer.classList.add('show');
    }

    function hideLoader() {
        loaderContainer.classList.remove('show');
    }

    //    automatic loader
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


    <!--My Scripts-->
    <script>
    $(document).ready(function() {

        // Initialize DataTable for general leave
        if ($.fn.DataTable.isDataTable('#generalLeave-table')) {
            $('#generalLeave-table').DataTable().destroy();
        }
        $('#generalLeave-table').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50, 100],
            order: [[0, 'asc']],
            columnDefs: [{
                orderable: false,
                targets: [5]
            }]
        });

    });

    // Client-side export to PDF
    window.generateGeneralLeavePDF = async function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        const headerColor = [0, 109, 109];
        const borderGray = [180, 180, 180];

        // Collect header & data from table
        const table = document.getElementById('generalLeave-table');
        if (!table) return Swal.fire('Error', 'Could not find the General Leave table', 'error');

        const headers = Array.from(table.querySelectorAll('thead tr th')).map(th => th.textContent.trim());
        const rows = Array.from(table.querySelectorAll('tbody tr')).map(tr => Array.from(tr.querySelectorAll('td')).map(td => td.textContent.trim()));
        if (rows.length === 0) return Swal.fire('No Data', 'There are no records to export', 'info');

        // Load images (non-blocking fallback)
        const leftLogo = new Image(); leftLogo.src = 'image/mkce_logo2.jpg';
        const rightLogo = new Image(); rightLogo.src = 'image/kr.jpg';
        await Promise.all([
            new Promise(r => { leftLogo.onload = leftLogo.onerror = r; }),
            new Promise(r => { rightLogo.onload = rightLogo.onerror = r; })
        ]);

        const pageWidth = doc.internal.pageSize.getWidth();
        const logoSize = 18;
        const rightX = pageWidth - 15 - logoSize;

        try { if (leftLogo.complete && leftLogo.naturalHeight !== 0) doc.addImage(leftLogo, 'JPG', 15, 10, logoSize, logoSize); } catch (e) {}
        try { if (rightLogo.complete && rightLogo.naturalHeight !== 0) doc.addImage(rightLogo, 'JPG', rightX, 10, logoSize, logoSize); } catch (e) {}

        const leftLogoRightEdge = 15 + logoSize;
        const rightLogoLeftEdge = rightX;
        const logosCenterX = (leftLogoRightEdge + rightLogoLeftEdge) / 2;

        doc.setFont('helvetica', 'bold'); doc.setFontSize(14);
        doc.text('M.Kumarasamy College of Engineering, Karur - 639 113', logosCenterX, 25, { align: 'center' });
        doc.setFont('helvetica', 'italic'); doc.setFontSize(10);
        doc.text('(An Autonomous Institution Affiliated to Anna University, Chennai)', logosCenterX, 30, { align: 'center' });

        doc.setFont('helvetica', 'bold'); doc.setFontSize(12);
        const formattedDate = new Date().toLocaleDateString();
        doc.text(`GENERAL LEAVE HISTORY`, logosCenterX, 43, { align: 'center' });

        const generatedDateStr = new Date().toLocaleString();
        doc.setFont('helvetica', 'normal'); doc.setFontSize(9);
        doc.text(`Generated Date: ${generatedDateStr}`, 15, 51, { align: 'left' });
        doc.text('Generated by : Admin', pageWidth - 15, 51, { align: 'right' });

        doc.setDrawColor(...borderGray);
        doc.line(10, 55, pageWidth - 10, 55);

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

        // Add page numbers
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

        const fname = `General Leave History (${formatDateForFilename(new Date())}).pdf`;
        doc.save(fname);
    };

    window.exportGeneralLeaveExcel = function() {
        const table = document.getElementById('generalLeave-table');
        if (!table) return Swal.fire('Error', 'General Leave table not found', 'error');

        
        let html = '';
        html += '<table border="0" cellspacing="0" cellpadding="0" style="width:100%;">';
        html += '<tr><th colspan="6" style="background:#0aa2a1;color:#fff;font-size:14px;">M.KUMARASAMY COLLEGE OF ENGINEERING, KARUR - 639 113</th></tr>';
        html += '<tr><th colspan="6" style="background:#f2f2f2;color:#000;font-size:11px;">(An Autonomous Institution Affiliated to Anna University, Chennai)</th></tr>';
        html += `<tr><th colspan="6" style="text-align:center;font-weight:bold;">General Leave History - ${new Date().toLocaleString()}</th></tr>`;
        html += '</table><br/>';

        const tableClone = table.cloneNode(true);
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

        a.download = `General Leave History (${formatDateForFilename(new Date())}).xls`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    };

    // Handle Disable Leave Button (if alrdy enabled)
    $(document).on('click', '.disableLeaveBtn', function() {
        const leaveId = $(this).data('leave-id');

        Swal.fire({
            title: 'Disable General Leave?',
            text: 'This will prevent students from applying for leave. Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Disable It',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../api.php',
                    type: 'POST',
                    data: {
                        leave_id: leaveId,
                        action: 'disableGeneralLeave'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'General Leave has been disabled successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location
                            .reload(); // Refresh the page to update the UI
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message ||
                                    'Failed to disable General Leave.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while processing the request.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });


    // Handle Edit Leave Button
    $(document).on('click', '.editLeaveBtn', function() {
        const leaveId = $(this).data('leave-id');
        const leaveName = $(this).data('leave-name');
        const fromDate = $(this).data('from-date');
        const toDate = $(this).data('to-date');
        const instructions = $(this).data('instructions');

        // Set form mode to edit
        $('#formMode').val('edit');
        $('#leaveId').val(leaveId);
        
        // Populate form fields
        $('#leaveName').val(leaveName);
        $('#fromDateTime').val(fromDate);
        $('#toDateTime').val(toDate);
        $('#leaveDescription').val(instructions);
        
        // Update modal title and button
        $('#leaveModalLabel').text('Edit General Leave');
        $('#modalHeader').removeClass('bg-success').addClass('bg-warning');
        $('#submitBtn').removeClass('btn-success').addClass('btn-warning');
        $('#submitBtnText').text('Update General Leave');
        
        // Show modal
        $('#leaveModal').modal('show');
    });

    // Reset modal when closed
    $('#leaveModal').on('hidden.bs.modal', function() {
        // Reset form mode
        $('#formMode').val('create');
        $('#leaveId').val('');
        
        // Reset form fields
        $('#leaveForm')[0].reset();
        
        // Reset modal title and styling
        $('#leaveModalLabel').text('Enable General Leave');
        $('#modalHeader').removeClass('bg-warning').addClass('bg-success');
        $('#submitBtn').removeClass('btn-warning').addClass('btn-success');
        $('#submitBtnText').text('Enable General Leave');
    });


    $(document).on('click', '#deleteLeaveBtn', function() {
        const leaveId = $(this).data('leave-id');

        Swal.fire({
            title: 'Delete General Leave?',
            text: 'This will permanently remove the leave record. Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete It',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../api.php',
                    type: 'POST',
                    data: {
                        leave_id: leaveId,
                        action: 'deleteGeneralLeave'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'General Leave has been deleted successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location
                            .reload(); // Refresh the page to update the UI
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message ||
                                    'Failed to disable General Leave.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while processing the request.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });

    // Leave Form Validation
    document.getElementById('leaveForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formMode = document.getElementById('formMode').value;
        const leaveId = document.getElementById('leaveId').value;
        const leaveName = document.getElementById('leaveName').value.trim();
        const fromDateTime = document.getElementById('fromDateTime').value;
        const toDateTime = document.getElementById('toDateTime').value;
        const instructions = document.getElementById('leaveDescription').value.trim();

        // Validate required fields
        if (!leaveName || !fromDateTime || !toDateTime) {
            Swal.fire({
                title: 'Validation Error!',
                text: 'Please fill all required fields.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Validate date range
        const fromDate = new Date(fromDateTime);
        const toDate = new Date(toDateTime);
        const currentDate = new Date();

        if (fromDate >= toDate) {
            Swal.fire({
                title: 'Invalid Date Range!',
                text: 'From date must be earlier than To date.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Determine action and confirmation message based on form mode
        const action = formMode === 'edit' ? 'updateGeneralLeave' : 'enableGeneralLeave';
        const confirmTitle = formMode === 'edit' ? 'Confirm Leave Update' : 'Confirm Leave Enable';
        const confirmButton = formMode === 'edit' ? 'Yes, Update Leave' : 'Yes, Enable Leave';
        const successMessage = formMode === 'edit' ? 'General Leave has been updated successfully.' : 'General Leave has been enabled successfully.';

        // Show confirmation dialog
        Swal.fire({
            title: confirmTitle,
            html: `
                <div class="text-start">
                    <strong>Leave Name:</strong> ${leaveName}<br>
                    <strong>From:</strong> ${new Date(fromDateTime).toLocaleString()}<br>
                    <strong>To:</strong> ${new Date(toDateTime).toLocaleString()}<br>
                    ${instructions ? `<strong>Instructions:</strong> ${instructions}` : ''}
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: formMode === 'edit' ? '#ffc107' : '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmButton,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Prepare data
                const postData = {
                    leave_name: leaveName,
                    from_date: fromDateTime,
                    to_date: toDateTime,
                    instructions: instructions,
                    action: action
                };

                // Add leave_id for update
                if (formMode === 'edit') {
                    postData.leave_id = leaveId;
                }

                // Submit via AJAX
                $.ajax({
                    url: '../api.php',
                    type: 'POST',
                    data: postData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: successMessage,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                $('#leaveModal').modal('hide');
                                location.reload(); 
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message ||
                                    'Failed to process General Leave.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while processing the request.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });

    // Set minimum date to current date for from date
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const currentDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;

        document.getElementById('fromDateTime').min = currentDateTime;

        // Update to date minimum when from date changes
        document.getElementById('fromDateTime').addEventListener('change', function() {
            document.getElementById('toDateTime').min = this.value;
        });
    });
    </script>

</body>

</html>