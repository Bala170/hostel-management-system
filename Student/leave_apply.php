<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id']) || (!isset($_SESSION['role']) && !isset($_SESSION['user_type'])) || ($_SESSION['role'] ?? $_SESSION['user_type'] ?? 'student') !== 'student') {
    // Redirect to login if not logged in as student
    header("Location: ../login");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Management</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../images/icons/mkce_s.png">
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
        body {
            background: linear-gradient(135deg, #e7e9f3ff 0%, #f9f9faff 100%);
           
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
        
        /* NEW: Custom modal styles for consistent size and reduced width */
        #leaveModal .modal-title {
            font-size: 1.15rem; /* Ensure title is clear */
        }

        #leaveModal .modal-body label,
        #leaveModal .modal-body .form-label,
        #leaveModal .modal-body .form-control,
        #leaveModal .modal-body .form-select,
        #leaveModal .modal-body small {
            font-size: 0.875rem; /* Standard smaller font size (Bootstrap small) for uniformity */
        }

        #leaveModal .modal-body h6 {
            font-size: 1rem; /* Reduce h6 size slightly */
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
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

        .gradient-header {
            /* Overrides Bootstrap's table background/color */
            --bs-table-bg: transparent;
            --bs-table-color: white;

            /* The actual gradient background */
            background: linear-gradient(135deg, #4CAF50, #2196F3) !important;

            text-align: center;
            font-size: 0.9em;
        }

        td,
        th {
            padding-top: 20px;
            padding-bottom: 10px;
        }

        btn-group-sm>.btn,
        .btn-sm {
            padding: .2rem .4rem;
            font-size: .8rem;
            line-height: 1.4;
            border-radius: .15rem;
        }
        
        /* Action column styling */
        .table td:nth-child(9) {
            text-align: center;
            white-space: nowrap;
            width: 20px; /* Much smaller width */
            padding: 0.001rem !important;
        }
        
        .table .btn-group {
                    
            justify-content: center;
            gap: 0.25rem;
          
        }
        
        /* Square buttons in action column */
        .table td:nth-child(9) .btn-sm {
            padding: 0.01rem;
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            min-width: 18px;
            min-height: 18px;
            border-radius: 2px;
            line-height: 1;
        }
        
        /* Smaller icons in action column */
        .table td:nth-child(9) .btn-sm .fas {
            font-size: 0.6rem;
        }
        .modal-header {
    background: linear-gradient(to right, #6a11cb 0%, #2575fc 100%);
    color: white;
    padding: 15px 20px;
    border-bottom: none;
}
        .modal-header .modal-title {
    font-weight: 200;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.modal-header .btn-close {
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

.modal-header .btn-close:hover {
    background-color: rgba(255, 255, 255, 0.4);
    transform: scale(1.1);
}

.modal-header .btn-close:focus {
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
    outline: none;
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
                    <li class="breadcrumb-item active" aria-current="page">Leave</li>
                </ol>
            </nav>
        </div>

        <div class="container-fluid">
            <div class="card shadow mb-4">
                <div class="card-header py-3" style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0;">
                    <h6 class="m-0 font-weight-bold text-dark">
                        <i class="fas fa-calendar-alt me-2"></i> My Leave Applications
                    </h6>
                </div>
                <div class="card-body">

                    <div class="d-flex justify-content-end mb-3">
                        <button id="openApplyLeave" class="btn btn-primary shadow-sm">
                            <i class="fas fa-plus me-1"></i> Apply Leave
                        </button>
                    </div>

                    <div id="errorContainer"></div>
                    <div id="successMessage"></div>
                    <div id="updatedMessage"></div>

                    <div class="p-3 border rounded shadow-sm">
                        <br>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">

                                <thead class="gradient-header">
                                    <tr>
                                        <th>S.No</th>
                                        <th>Type</th>
                                        <th>From Date/Time</th>
                                        <th>To Date/Time</th>
                                        <th>Reason</th>
                                        <th>Proof</th>
                                        <th>Applied On</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody id="leaveTableBody">
                                    <!-- Data will be populated by JavaScript -->
                                </tbody>

                            </table>
                        </div>

                        <div class="modal fade" id="leaveModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-md modal-dialog-centered">
                                <div class="modal-content form-model-style">
                                    <form id="leaveForm" class="p-0 needs-validation" novalidate
                                        enctype="multipart/form-data" method="POST">

                                        <div class="modal-header  text-white p-3 rounded-top">
                                            <h5 class="modal-title" id="leaveModalTitle">Apply for Leave</h5>
                                            <button type="button" class="btn-close btn-close-white"
                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body p-4">
                                            <input type="hidden" id="leave_id" name="leave_id" value="">
                                            <input type="hidden" name="apply_leave" value="1">

                                            <div class="mb-3">
                                                <label for="leave_type_id" class="form-label required-label">Leave
                                                    Type</label>
                                                <select name="leave_type_id" id="leave_type_id"
                                                    class="form-select" required>
                                                    <option value="">Select Leave Type</option>
                                                    <!-- Options will be populated by JavaScript -->
                                                </select>
                                                <div class="invalid-feedback">Please select a leave type.</div>
                                                <div id="general_leave_info"></div> 
                                            </div>

                                            <h6>Leave Duration</h6>

                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="p-3 border rounded h-100">
                                                        <label for="from_date" class="form-label required-label">From
                                                            Date</label>
                                                        <input type="date" name="from_date" id="from_date"
                                                            class="form-control" required>
                                                        <div class="invalid-feedback">Please select a start date.</div>

                                                        <label class="form-label mt-3 required-label">From Time</label>
                                                        <div class="input-group">
                                                            <select name="from_time" id="from_time" class="form-select"
                                                                required>
                                                                <?php for ($h = 1; $h <= 12; $h++): ?>
                                                                    <option
                                                                        value="<?php echo str_pad($h, 2, '0', STR_PAD_LEFT); ?>">
                                                                        <?php echo str_pad($h, 2, '0', STR_PAD_LEFT); ?>
                                                                    </option>
                                                                <?php endfor; ?>
                                                            </select>
                                                            <select name="from_minute" id="from_minute"
                                                                class="form-select">
                                                                <?php for ($m = 0; $m < 60; $m += 5): ?>
                                                                    <option
                                                                        value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>">
                                                                        <?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>
                                                                    </option>
                                                                <?php endfor; ?>
                                                            </select>
                                                            <select name="from_ampm" id="from_ampm" class="form-select"
                                                                required>
                                                                <option>AM</option>
                                                                <option selected>PM</option>
                                                            </select>
                                                            <div class="invalid-feedback">Please select a valid start
                                                                time.</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="p-3 border rounded h-100">
                                                        <label for="to_date" class="form-label required-label">To
                                                            Date</label>
                                                        <input type="date" name="to_date" id="to_date"
                                                            class="form-control" required>
                                                        <div class="invalid-feedback">Please select an end date.</div>

                                                        <label class="form-label mt-3 required-label">To Time</label>
                                                        <div class="input-group">
                                                            <select name="to_time" id="to_time" class="form-select"
                                                                required>
                                                                <?php for ($h = 1; $h <= 12; $h++): ?>
                                                                    <option
                                                                        value="<?php echo str_pad($h, 2, '0', STR_PAD_LEFT); ?>">
                                                                        <?php echo str_pad($h, 2, '0', STR_PAD_LEFT); ?>
                                                                    </option>
                                                                <?php endfor; ?>
                                                            </select>
                                                            <select name="to_minute" id="to_minute" class="form-select">
                                                                <?php for ($m = 0; $m < 60; $m += 5): ?>
                                                                    <option
                                                                        value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>">
                                                                        <?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>
                                                                    </option>
                                                                <?php endfor; ?>
                                                            </select>
                                                            <select name="to_ampm" id="to_ampm" class="form-select"
                                                                required>
                                                                <option>AM</option>
                                                                <option selected>PM</option>
                                                            </select>
                                                            <div class="invalid-feedback">Please select a valid end
                                                                time.</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <label for="reason" class="form-label required-label">Reason</label>
                                                <textarea name="reason" id="reason" class="form-control" rows="3"
                                                    required></textarea>
                                                <div class="invalid-feedback">Please enter the reason for your leave.
                                                </div>
                                            </div>

                                            <div class="mt-4 p-3 border rounded">
                                                <label for="proof" class="form-label">Upload Proof (Optional)</label>
                                                <div id="current_proof_preview" class="mb-2"></div>
                                                <input type="file" name="proof" id="proof" class="form-control">
                                                <small id="proof-help" class="form-text text-muted">Upload proof if
                                                    required (PDF, JPG, PNG).</small>
                                            </div>
                                        </div>
                                        <div
                                            class="modal-footer d-flex justify-content-end bg-light p-3 rounded-bottom">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-success" id="submitLeaveBtn">Submit
                                                Application</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="proofModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Leave Proof</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body" id="proofModalBody">
                                        <p class="text-center">Loading proof</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php include '../assets/footer.php'; ?>

                        <script>
                            // Global variables
                            let GENERAL_LEAVE_SETTING = null;
                            let leaveData = [];
                            let leaveTypes = [];

                            // CRITICAL FIX: Robust, universal date/time parsing function (copied from previous step)
                            function parseDateTimeString(dateTimeStr) {
                                // Split YYYY-MM-DD and HH:MM:SS
                                const [datePart, timePart] = dateTimeStr.split(' ');
                                // Ensure datePart is not empty before splitting
                                if (!datePart || !timePart) {
                                    console.error("Invalid datetime string received:", dateTimeStr);
                                    return { date: '', hour12: '12', minute: '00', ampm: 'AM' };
                                }
                                const [year, month, day] = datePart.split('-').map(Number);
                                const [hour24, minute] = timePart.split(':').map(Number); 

                                // Convert 24-hour to 12-hour format
                                const ampm = hour24 >= 12 ? 'PM' : 'AM';
                                const hour12 = hour24 % 12 || 12; 

                                return {
                                    date: `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`,
                                    hour12: String(hour12).padStart(2, '0'),
                                    minute: String(minute).padStart(2, '0'),
                                    ampm: ampm
                                };
                            }
                            
                            // Function to format Date objects for display
                            const formatDateDisplay = (date) => date.toLocaleDateString(undefined, {
                                year: 'numeric', 
                                month: 'short', 
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: true
                            });

                            function getTodayYmd() {
                                return new Date().toISOString().split('T')[0];
                            }

                            function getLocalTodayYmd() {
                                const today = new Date();
                                const year = today.getFullYear();
                                const month = String(today.getMonth() + 1).padStart(2, '0');
                                const day = String(today.getDate()).padStart(2, '0');
                                return `${year}-${month}-${day}`;
                            }

                            function getSelectedLeaveTypeName() {
                                const selected = $('#leave_type_id').find('option:selected');
                                const fromDataAttr = selected.data('type-name');
                                if (typeof fromDataAttr === 'string' && fromDataAttr.trim() !== '') {
                                    return fromDataAttr.toLowerCase();
                                }
                                const fromText = selected.text();
                                return (fromText || '').toLowerCase().trim();
                            }

                            function normalizeProofUrl(proofUrl) {
                                if (!proofUrl) {
                                    return '';
                                }

                                const trimmedUrl = String(proofUrl).trim();
                                if (!trimmedUrl) {
                                    return '';
                                }

                                if (/^(?:https?:)?\/\//i.test(trimmedUrl)) {
                                    return trimmedUrl;
                                }

                                const pathMatch = window.location.pathname.match(/^(.*?\/)(?:Student|admin|mess|print|faculty)\//i);
                                const appRootPath = pathMatch ? pathMatch[1] : '/';

                                if (trimmedUrl.toLowerCase().startsWith(appRootPath.toLowerCase())) {
                                    return trimmedUrl;
                                }

                                const cleanedPath = trimmedUrl
                                    .replace(/^(?:\.\.\/)+/, '')
                                    .replace(/^\/+/, '');

                                return appRootPath + cleanedPath;
                            }

                            function isOutingSelected() {
                                const leaveType = getSelectedLeaveTypeName();
                                return leaveType.includes('outing');
                            }

                            function to24Hour(hour12, minute, ampm) {
                                let hour = parseInt(hour12, 10) % 12;
                                if ((ampm || '').toUpperCase() === 'PM') {
                                    hour += 12;
                                }
                                return {
                                    hour,
                                    minute: parseInt(minute, 10)
                                };
                            }

                            function lockSelectToValue(selector, fixedValue) {
                                const select = $(selector);
                                select.val(fixedValue);
                                select.find('option').each(function () {
                                    $(this).prop('disabled', $(this).val() !== fixedValue);
                                });
                                select.trigger('change');
                            }

                            function unlockSelectOptions(selector) {
                                $(selector).find('option').prop('disabled', false);
                            }

                            function applyOutingRestrictions() {
                                const fromDateInput = $('#from_date');
                                const toDateInput = $('#to_date');
                                const generalInfo = $('#general_leave_info');

                                if (isOutingSelected()) {
                                    const todayStr = getLocalTodayYmd();

                                    fromDateInput.attr('min', todayStr).attr('max', todayStr).val(todayStr).prop('readonly', true);
                                    toDateInput.attr('min', todayStr).attr('max', todayStr).val(todayStr).prop('readonly', true);

                                    // Keep return time constant at 11:30 PM for outing
                                    lockSelectToValue('#to_time', '11');
                                    lockSelectToValue('#to_minute', '30');
                                    lockSelectToValue('#to_ampm', 'PM');

                                    generalInfo.html(`
                                        <i class="fas fa-info-circle text-info me-1"></i>
                                        <strong>Outing Rule:</strong> Outing is allowed only for today and return time is fixed at <strong>11:30 PM</strong>.
                                    `).show();
                                } else {
                                    fromDateInput.prop('readonly', false);
                                    toDateInput.prop('readonly', false);
                                    unlockSelectOptions('#to_time');
                                    unlockSelectOptions('#to_minute');
                                    unlockSelectOptions('#to_ampm');
                                }
                            }

                            function applyEmergencyLeaveRestrictions() {
                                const leaveType = getSelectedLeaveTypeName();
                                const fromDateInput = $('#from_date');
                                const toDateInput = $('#to_date');
                                const generalInfo = $('#general_leave_info');

                                if (leaveType.includes('emergency')) {
                                    const todayStr = getTodayYmd();

                                    // Set from_date to today and make it readonly
                                    fromDateInput.attr('min', todayStr).val(todayStr).prop('readonly', true);
                                    
                                    // Allow to_date to be flexible but minimum should be today
                                    toDateInput.attr('min', todayStr).prop('readonly', false);

                                    // Allow time selection for both from and to
                                    unlockSelectOptions('#from_time');
                                    unlockSelectOptions('#from_minute');
                                    unlockSelectOptions('#from_ampm');
                                    unlockSelectOptions('#to_time');
                                    unlockSelectOptions('#to_minute');
                                    unlockSelectOptions('#to_ampm');

                                    generalInfo.html(`
                                        <i class="fas fa-exclamation-circle text-warning me-1"></i>
                                        <strong>Emergency Leave Rule:</strong> From date is restricted to <strong>Today</strong>. To date can be selected based on your need.
                                    `).show();
                                } else {
                                    fromDateInput.prop('readonly', false);
                                    toDateInput.prop('readonly', false);
                                    unlockSelectOptions('#from_time');
                                    unlockSelectOptions('#from_minute');
                                    unlockSelectOptions('#from_ampm');
                                    unlockSelectOptions('#to_time');
                                    unlockSelectOptions('#to_minute');
                                    unlockSelectOptions('#to_ampm');
                                }
                            }

                            // 🔑 UPDATED: Function to check General Leave Restriction and update UI
                            function checkGeneralLeaveRestriction(isEditing = false) {
                                const leaveTypeSelect = $('#leave_type_id');
                                const generalLeaveOption = leaveTypeSelect.find('option[data-type-name*="general"]');
                                const isGeneralLeaveEnabled = GENERAL_LEAVE_SETTING && GENERAL_LEAVE_SETTING.Is_Enabled == 1;
                                
                                // Get or create info div
                                let infoDiv = $('#general_leave_info');
                                infoDiv.empty().hide();

                                // 1. Client-Side Disabling Logic
                                // Disable the option for NEW applications if the admin has not enabled it.
                                if (!isEditing) { 
                                    if (!isGeneralLeaveEnabled) {
                                        generalLeaveOption.prop('disabled', true).attr('title', 'General Leave is currently disabled by the Admin.');
                                        if (leaveTypeSelect.val() === generalLeaveOption.val()) {
                                            leaveTypeSelect.val(''); // Reset selection if disabled option was selected
                                        }
                                    } else {
                                        generalLeaveOption.prop('disabled', false).removeAttr('title');
                                    }
                                } else {
                                    // For editing, ensure the selected option is not disabled even if it is General Leave
                                    leaveTypeSelect.find('option[data-general-disabled="true"]').prop('disabled', false);
                                }
                                
                                // 2. Display Restriction Range and set date attributes if General Leave is Selected
                                if (isGeneralLeaveEnabled && leaveTypeSelect.val() === generalLeaveOption.val()) {
                                    const fromDate = new Date(GENERAL_LEAVE_SETTING.From_Date);
                                    const toDate = new Date(GENERAL_LEAVE_SETTING.To_Date);
                                    
                                    infoDiv.html(`
                                        <i class="fas fa-info-circle text-info me-1"></i> 
                                        <strong>General Leave is active!</strong> 
                                        You can only apply for leave entirely between:<br>
                                        <strong>From:</strong> ${formatDateDisplay(fromDate)}<br>
                                        <strong>To:</strong> ${formatDateDisplay(toDate)}
                                    `).show();
                                    
                                    // Set min/max dates on date inputs for user experience
                                    const allowedMinDateStr = fromDate.toISOString().split('T')[0];
                                    const allowedMaxDateStr = toDate.toISOString().split('T')[0];

                                    // Set min date to TOMORROW (or the allowed start date, whichever is later)
                                    const today = new Date();
                                    today.setHours(0, 0, 0, 0);
                                    const tomorrow = new Date(today);
                                    tomorrow.setDate(tomorrow.getDate() + 1);
                                    const minDateEffective = fromDate > tomorrow ? fromDate : tomorrow;
                                    
                                    $('#from_date').attr('min', minDateEffective.toISOString().split('T')[0]);
                                    $('#to_date').attr('max', allowedMaxDateStr);
                                    
                                } else {
                                    // Clear min/max constraints when not General Leave
                                    $('#from_date').removeAttr('min'); 
                                    $('#to_date').removeAttr('max'); 
                                    setDateRestrictions(); // Re-apply default restrictions
                                }
                            }
                            
                            // Function to set the minimum date for date inputs based on leave type (Default logic)
                            function setDateRestrictions() {
                                const leaveType = getSelectedLeaveTypeName();
                                const fromDateInput = $('#from_date');
                                const toDateInput = $('#to_date');

                                // Clear existing custom min/max from General Leave checks
                                fromDateInput.removeAttr('min');
                                toDateInput.removeAttr('max'); 
                                
                                const today = new Date();
                                today.setHours(0, 0, 0, 0);
                                const todayStr = today.toISOString().split('T')[0];

                                const tomorrow = new Date(today);
                                tomorrow.setDate(today.getDate() + 1);
                                const tomorrowStr = tomorrow.toISOString().split('T')[0];

                                // Logic for setting min date restriction
                                if (leaveType.includes('emergency')) {
                                    // Emergency leave restrictions are handled by applyEmergencyLeaveRestrictions()
                                    fromDateInput.attr('min', todayStr);
                                } else if (leaveType.includes('od')) {
                                    fromDateInput.attr('min', tomorrowStr).val(tomorrowStr);
                                } else if (leaveType.includes('general')) {
                                    // As checkGeneralLeaveRestriction handles the min/max dates for general leave
                                    // But if General Leave is NOT active, it falls to the next else.
                                } else if (leaveType.includes('outing')) {
                                    // Outing is constrained to today's date only
                                    fromDateInput.attr('min', todayStr).attr('max', todayStr).val(todayStr);
                                    toDateInput.attr('min', todayStr).attr('max', todayStr).val(todayStr);
                                } else {
                                    // Default: minimum start is tomorrow
                                    fromDateInput.attr('min', tomorrowStr).val(tomorrowStr);
                                }

                                // Make sure To Date cannot be before From Date
                                fromDateInput.off('change').on('change', function () {
                                    const selectedFromDate = this.value;
                                    const currentToDate = toDateInput.val();

                                    toDateInput.attr('min', selectedFromDate);

                                    if (currentToDate && selectedFromDate > currentToDate) {
                                        toDateInput.val(selectedFromDate);
                                    }
                                }).trigger('change');
                            }
                            
                            // Re-run restrictions when leave type changes
                            $('#leave_type_id').on('change', function() {
                                checkGeneralLeaveRestriction($('#leave_id').val() !== '');
                                applyOutingRestrictions();
                                applyEmergencyLeaveRestrictions();
                            });

                            // 1. OPEN APPLY MODAL (NEW LEAVE)
                            $('#openApplyLeave').on('click', function () {
                                $('#leaveModalTitle').text('Apply for Leave');
                                $('#leaveForm')[0].reset();
                                $('#leave_id').val(''); // Critical: Empty leave_id for new application
                                $('#submitLeaveBtn').text('Submit Application').prop('disabled', false).removeClass('disabled');
                                $('#leaveForm').find('.is-invalid').removeClass('is-invalid');
                                $('#leaveForm').removeClass('was-validated');
                                $('#current_proof_preview').empty();
                                $('#proof-help').text('Upload proof if required (PDF, JPG, PNG).');
                                
                                // Set default time to 06:00 PM
                                $('#from_time').val('06');
                                $('#to_time').val('06');
                                $('#from_ampm').val('PM');
                                $('#to_ampm').val('PM');
                                $('#from_minute').val('00');
                                $('#to_minute').val('00');
                                
                                checkGeneralLeaveRestriction(false); // Check restriction for NEW leave
                                setDateRestrictions(); // Apply default date restrictions
                                applyOutingRestrictions(); // Apply outing-specific constraints if selected
                                applyEmergencyLeaveRestrictions(); // Apply emergency leave-specific constraints if selected
                                
                                // Enable leave type selection for new applications
                                $('#leave_type_id').prop('disabled', false);
                                
                                $('#leaveModal').modal('show');
                            });

                            // 2. OPEN EDIT MODAL (EDIT LEAVE)
                            $(document).on('click', '.edit-leave', function () {
                                const id = $(this).data('id');
                                const typeId = $(this).data('type-id');
                                const reason = $(this).data('reason');
                                const row = $(this).closest('tr');

                                const fromDateTime = row.find('td[data-from-date]').data('from-date');
                                const toDateTime = row.find('td[data-to-date]').data('to-date');
                                const proofUrl = normalizeProofUrl(row.find('.view-proof').data('proof-url'));
                                
                                const fromParts = parseDateTimeString(fromDateTime);
                                const toParts = parseDateTimeString(toDateTime);

                                // Populate form
                                $('#leaveModalTitle').text('Edit Leave');
                                $('#leaveForm')[0].reset();
                                $('#leave_id').val(id); // Critical: Set leave_id for edit application
                                $('#leave_type_id').val(typeId);
                                // Disable leave type selection during editing
                                $('#leave_type_id').prop('disabled', true);
                                
                                $('#from_date').val(fromParts.date); 
                                $('#to_date').val(toParts.date); 
                                
                                $('#from_time').val(fromParts.hour12); 
                                $('#to_time').val(toParts.hour12);
                                
                                $('#from_minute').val(fromParts.minute);
                                $('#to_minute').val(toParts.minute);
                                
                                $('#from_ampm').val(fromParts.ampm);
                                $('#to_ampm').val(toParts.ampm);
                                
                                $('#reason').val(reason);
                                $('#submitLeaveBtn').text('Update Application').prop('disabled', false).removeClass('disabled');
                                $('#leaveForm').find('.is-invalid').removeClass('is-invalid');
                                $('#leaveForm').removeClass('was-validated');


                                // Handle proof preview
                                $('#current_proof_preview').empty();
                                if (proofUrl) {
                                    $('#proof-help').text('Upload a new file to replace the existing proof. Leave blank to keep the current proof.');
                                    let previewHtml = '';
                                    const finalProofUrl = proofUrl; 
                                    
                                    if (finalProofUrl.toLowerCase().endsWith('.pdf')) {
                                        previewHtml = '<p class="text-info"><i class="fas fa-file-pdf"></i> <strong>Existing PDF Proof Attached.</strong></p>';
                                    } else if (/\.(jpe?g|png|webp)$/i.test(finalProofUrl)) {
                                        previewHtml = '<img src="' + finalProofUrl + '" alt="Current Proof" class="img-thumbnail" style="max-height: 100px;">';
                                    } else {
                                        previewHtml = '<p class="text-info"><strong>Existing Proof File Attached.</strong></p>';
                                    }
                                    $('#current_proof_preview').html(previewHtml);
                                } else {
                                    $('#proof-help').text('Upload proof if required (PDF, JPG, PNG).');
                                }

                                // For editing, disable leave type selection to prevent changes
                                $('#leave_type_id').prop('disabled', true);
                                
                                checkGeneralLeaveRestriction(true); // Ignore restriction for EDITING
                                setDateRestrictions(); // Re-apply default date restrictions
                                applyOutingRestrictions(); // Apply outing-specific constraints for edit view
                                applyEmergencyLeaveRestrictions(); // Apply emergency leave-specific constraints for edit view
                                $('#leaveModal').modal('show');
                            });

                            // 3. CANCEL LEAVE
                            $(document).on('click', '.cancel-leave', function () {
                                const leaveId = $(this).data('id');

                                Swal.fire({
                                    title: 'Are you sure?',
                                    text: "Do you want to cancel this leave application? This cannot be undone.",
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#d33',
                                    confirmButtonText: 'Yes, cancel it!',
                                    cancelButtonText: 'No, keep it'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        
                                        Swal.fire({
                                            title: 'Cancelling...',
                                            text: 'Attempting to cancel application...',
                                            allowOutsideClick: false,
                                            didOpen: () => {
                                                Swal.showLoading();
                                                $.ajax({
                                                    type: "POST",
                                                    url: "../api.php", 
                                                    data: {
                                                        action: 'cancel_leave',
                                                        leave_id: leaveId
                                                    },
                                                    dataType: 'json',
                                                    success: function (response) {
                                                        Swal.close();
                                                    
                                                        if (response.success) {
                                                            Swal.fire('Cancelled!', response.message, 'success').then(() => {
                                                                location.reload(); 
                                                            });
                                                        } else {
                                                            Swal.fire('Error!', response.message, 'error');
                                                        }
                                                    },
                                                    error: function (xhr) {
                                                        Swal.close();
                                                        let errorMessage = xhr.responseText || 'An unknown error occurred.';
                                                        Swal.fire('Error!', 'An error occurred while attempting to cancel the application. Status: ' + xhr.status + ', Message: ' + errorMessage, 'error');
                                                    }
                                                });
                                            }
                                        });
                                    }
                                });
                            });

                            // 4. VIEW PROOF MODAL
                            $(document).on('click', '.view-proof', function () {
                                const proofUrl = normalizeProofUrl($(this).data('proof-url'));
                                const modalBody = $('#proofModalBody');
                                modalBody.empty();
                                modalBody.html('<p class="text-center">Loading proof...</p>');
                                
                                const finalProofUrl = proofUrl; 
                                
                                let contentHtml = '';
                                if (!finalProofUrl) {
                                    contentHtml = '<div class="alert alert-warning text-center">No proof document found for this application.</div>';
                                } else if (finalProofUrl.toLowerCase().endsWith('.pdf')) {
                                    contentHtml = `<iframe src="${finalProofUrl}" style="width: 100%; height: 75vh;" frameborder="0"></iframe>`;
                                } else if (/\.(jpe?g|png|webp)$/i.test(finalProofUrl)) {
                                    contentHtml = `<div class="text-center"><img src="${finalProofUrl}" alt="Leave Proof" class="img-fluid border rounded shadow-sm" style="max-height: 80vh;"></div>`;
                                } else {
                                    contentHtml = `<div class="alert alert-info text-center">Unsupported file type. <a href="${finalProofUrl}" target="_blank">Download file</a> to view.</div>`;
                                }

                                modalBody.html(contentHtml);
                                $('#proofModal').modal('show');
                            });

                            // 5. FORM SUBMISSION VALIDATION (Bootstrap native)
                            $('#leaveForm').on('submit', function (event) {
                                event.preventDefault();
                                const form = this;
                                
                                // General Leave front-end check for better UX
                                const leaveId = $('#leave_id').val();
                                const leaveType = getSelectedLeaveTypeName();
                                
                                if (!leaveId && leaveType.includes('general')) {
                                    const isGeneralLeaveEnabled = GENERAL_LEAVE_SETTING && GENERAL_LEAVE_SETTING.Is_Enabled == 1;
                                    
                                    if (!isGeneralLeaveEnabled) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        Swal.fire('Restricted', 'General Leave applications are currently disabled by the Admin.', 'error');
                                        $('#leave_type_id').val('').addClass('is-invalid');
                                        return;
                                    }
                                    
                                    // Optional Client-Side Date Range Check (The server side is the authoritative check)
                                    if (isGeneralLeaveEnabled) {
                                        const fromDateInput = new Date($('#from_date').val() + " " + $('#from_time').val() + ":" + $('#from_minute').val() + " " + $('#from_ampm').val());
                                        const toDateInput = new Date($('#to_date').val() + " " + $('#to_time').val() + ":" + $('#to_minute').val() + " " + $('#to_ampm').val());
                                        const allowedFrom = new Date(GENERAL_LEAVE_SETTING.From_Date);
                                        const allowedTo = new Date(GENERAL_LEAVE_SETTING.To_Date);

                                        if (fromDateInput < allowedFrom || toDateInput > allowedTo) {
                                            event.preventDefault();
                                            event.stopPropagation();
                                            Swal.fire('Date Error', `General Leave must be entirely between ${formatDateDisplay(allowedFrom)} and ${formatDateDisplay(allowedTo)}.`, 'error');
                                            $('#from_date').addClass('is-invalid');
                                            $('#to_date').addClass('is-invalid');
                                            return;
                                        }
                                    }
                                }

                                if (leaveType.includes('outing')) {
                                    const todayStr = getLocalTodayYmd();
                                    const selectedFromDate = $('#from_date').val();
                                    const selectedToDate = $('#to_date').val();

                                    if (selectedFromDate !== todayStr || selectedToDate !== todayStr) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        Swal.fire('Date Error', 'Outing can be applied only for today.', 'error');
                                        $('#from_date').addClass('is-invalid');
                                        $('#to_date').addClass('is-invalid');
                                        return;
                                    }

                                    const endTime = to24Hour($('#to_time').val(), $('#to_minute').val(), $('#to_ampm').val());
                                    const isFixedOutingTime = (endTime.hour === 23 && endTime.minute === 30 && $('#to_ampm').val() === 'PM');

                                    if (!isFixedOutingTime) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        Swal.fire('Time Error', 'For outing, return time is fixed at 11:30 PM.', 'error');
                                        $('#to_time').addClass('is-invalid');
                                        $('#to_minute').addClass('is-invalid');
                                        $('#to_ampm').addClass('is-invalid');
                                        return;
                                    }
                                }

                                if (leaveType.includes('emergency')) {
                                    const todayStr = getTodayYmd();
                                    const selectedFromDate = $('#from_date').val();
                                    const selectedToDate = $('#to_date').val();

                                    if (selectedFromDate !== todayStr) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        Swal.fire('Date Error', 'Emergency leave from date must be today only.', 'error');
                                        $('#from_date').addClass('is-invalid');
                                        return;
                                    }

                                    if (!selectedToDate || new Date(selectedToDate) < new Date(todayStr)) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        Swal.fire('Date Error', 'Emergency leave to date must be on or after today.', 'error');
                                        $('#to_date').addClass('is-invalid');
                                        return;
                                    }
                                } else if (leaveType.includes('leave') && !leaveType.includes('general') && !leaveType.includes('outing') && !leaveType.includes('emergency')) {
                                    // Regular leave cannot be applied on the same day
                                    const todayStr = getTodayYmd();
                                    const selectedFromDate = $('#from_date').val();

                                    if (selectedFromDate === todayStr) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        Swal.fire('Date Error', 'Leave must be applied at least one day prior. Same day application is not allowed.', 'error');
                                        $('#from_date').addClass('is-invalid');
                                        return;
                                    }
                                }
                                
                                if (!form.checkValidity()) {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    if (!$('#submitLeaveBtn').prop('disabled')) {
                                        Swal.fire('Validation Error', 'Please fill out all required fields correctly.', 'error');
                                    }
                                } else {
                                    $('#submitLeaveBtn').text($('#leave_id').val() ? 'Updating...' : 'Submitting...').prop('disabled', true).addClass('disabled');
                                    
                                    // Submit form via AJAX
                                    const formData = new FormData(form);
                                    
                                    $.ajax({
                                        url: '../api.php?action=apply_leave',
                                        type: 'POST',
                                        data: formData,
                                        processData: false,
                                        contentType: false,
                                        success: function(response) {
                                            if (response.success) {
                                                Swal.fire('Success', response.message, 'success').then(() => {
                                                    location.reload();
                                                });
                                            } else if (response.errors && response.errors.length > 0) {
                                                Swal.fire('Error', response.errors.join('<br>'), 'error');
                                                $('#submitLeaveBtn').text($('#leave_id').val() ? 'Update Application' : 'Submit Application').prop('disabled', false).removeClass('disabled');
                                            } else {
                                                Swal.fire('Error', 'An unexpected error occurred.', 'error');
                                                $('#submitLeaveBtn').text($('#leave_id').val() ? 'Update Application' : 'Submit Application').prop('disabled', false).removeClass('disabled');
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                            let errorMessage = 'Unable to submit leave application right now. Please try again.';
                                            if (xhr && xhr.responseText) {
                                                try {
                                                    const parsed = JSON.parse(xhr.responseText);
                                                    if (parsed && parsed.errors && Array.isArray(parsed.errors) && parsed.errors.length > 0) {
                                                        errorMessage = parsed.errors.join('<br>');
                                                    } else if (parsed && parsed.message) {
                                                        errorMessage = parsed.message;
                                                    }
                                                } catch (e) {
                                                    if (error) {
                                                        errorMessage = 'An error occurred while submitting the form: ' + error;
                                                    }
                                                }
                                            } else if (error) {
                                                errorMessage = 'An error occurred while submitting the form: ' + error;
                                            }
                                            Swal.fire('Error', errorMessage, 'error');
                                            $('#submitLeaveBtn').text($('#leave_id').val() ? 'Update Application' : 'Submit Application').prop('disabled', false).removeClass('disabled');
                                        }
                                    });
                                }
                                form.classList.add('was-validated');
                            });

                            // Function to populate leave table
                            function populateLeaveTable() {
                                const tbody = $('#leaveTableBody');
                                tbody.empty();
                                
                                // Sort leave data by Applied_Date in descending order (newest first)
                                const sortedLeaveData = [...leaveData].sort((a, b) => {
                                    return new Date(b.Applied_Date) - new Date(a.Applied_Date);
                                });
                                
                                sortedLeaveData.forEach((row, index) => {
                                    // Simplified variable assignment and date formatting
                                    const leaveID = row.Leave_ID; 
                                    const fromDateObj = new Date(row.From_Date);
                                    const fromDateDisplay = fromDateObj.toLocaleDateString('en-GB');
                                    const fromTimeDisplay = fromDateObj.toLocaleTimeString('en-US', {
                                        hour: '2-digit',
                                        minute: '2-digit',
                                        hour12: true
                                    });
                                    
                                    const toDateObj = new Date(row.To_Date);
                                    const toDateDisplay = toDateObj.toLocaleDateString('en-GB');
                                    const toTimeDisplay = toDateObj.toLocaleTimeString('en-US', {
                                        hour: '2-digit',
                                        minute: '2-digit',
                                        hour12: true
                                    });
                                    
                                    const appliedDateObj = new Date(row.Applied_Date);
                                    const appliedDateDisplay = `${appliedDateObj.getDate()}-${(appliedDateObj.getMonth() + 1)}-${appliedDateObj.getFullYear()}`;
                                    const status = row.Status;
                                    const proofUrl = normalizeProofUrl(row.Proof || '');

                                    // Status badge logic using a cleaner ternary or match statement
                                    let statusClass = 'bg-warning';
                                    switch (status.trim()) {
                                        case 'Approved':
                                            statusClass = 'bg-success';
                                            break;
                                        case 'Rejected by Parents':
                                        case 'Rejected by Admin':
                                        case 'Rejected by HOD':
                                            statusClass = 'bg-danger';
                                            break;
                                        case 'Cancelled':
                                            statusClass = 'bg-secondary';
                                            break;
                                        case 'Forwarded to Admin':
                                            statusClass = 'bg-info';
                                            break;
                                        case 'out':
                                            statusClass = 'bg-primary';
                                            break;
                                        case 'closed':
                                            statusClass = 'bg-dark';
                                            break;
                                    }
                                    
                                    const rowHtml = `
                                        <tr data-leave-id="${leaveID}">
                                            <td class="small-text">${index + 1}</td> 
                                            <td class="small-text">${row.Leave_Type_Name}</td>
                                            <td class="small-text" data-from-date="${row.From_Date}">
                                                ${fromDateDisplay}<br><small>${fromTimeDisplay}</small>
                                            </td>
                                            <td class="small-text" data-to-date="${row.To_Date}">
                                                ${toDateDisplay}<br><small>${toTimeDisplay}</small>
                                            </td>
                                            <td class="small-text text-truncate" style="max-width: 150px;">
                                                ${row.Reason}
                                            </td>
                                            <td class="small-text">
                                                ${proofUrl ? 
                                                    `<button type="button" class="btn btn-primary btn-sm view-proof" data-proof-url="${proofUrl}">
                                                        View
                                                    </button>` : 
                                                    '-'
                                                }
                                            </td>
                                            <td class="small-text">${appliedDateDisplay}</td>
                                            <td class="small-text">
                                                <span class="badge ${statusClass}">${status}</span>
                                            </td>
                                            <td class="small-text">
                                                ${status == 'Pending' ? 
                                                    `<div class="btn-group btn-group-sm" role="group">
                                                        <button class="btn btn-sm btn-info edit-leave" data-id="${leaveID}"
                                                            data-type-id="${row.LeaveType_ID}"
                                                            data-reason="${row.Reason}"
                                                            title="Edit Leave">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger cancel-leave"
                                                            data-id="${leaveID}" title="Cancel Leave">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>` : 
                                                    'No action available'
                                                }
                                            </td>
                                        </tr>
                                    `;
                                    
                                    tbody.append(rowHtml);
                                });
                                
                                // Initialize DataTables for the Leave History
                                if ($.fn.DataTable.isDataTable('#dataTable')) {
                                    $('#dataTable').DataTable().destroy();
                                }
                                
                                $('#dataTable').DataTable({
                                    "order": [[0, "asc"]], 
                                    "pageLength": 10,
                                    "responsive": true
                                });
                            }

                            // Function to populate leave types dropdown
                            function populateLeaveTypes() {
                                const select = $('#leave_type_id');
                                select.empty();
                                select.append('<option value="">Select Leave Type</option>');
                                
                                leaveTypes.forEach(lt => {
                                    const lt_name_lower = lt.Leave_Type_Name.toLowerCase();
                                    const is_general = lt_name_lower.includes('general');
                                    const disabled_attr = (is_general && !(GENERAL_LEAVE_SETTING && GENERAL_LEAVE_SETTING.Is_Enabled)) ? 'disabled data-general-disabled="true"' : '';
                                    const title_attr = (is_general && !(GENERAL_LEAVE_SETTING && GENERAL_LEAVE_SETTING.Is_Enabled)) ? 'title="General Leave is currently disabled by the Admin."' : '';
                                    
                                    select.append(`
                                        <option value="${lt.LeaveType_ID}"
                                            data-type-name="${lt_name_lower}"
                                            ${disabled_attr} ${title_attr}>
                                            ${lt.Leave_Type_Name}
                                        </option>
                                    `);
                                });
                            }

                            // Initialize the page
                            $(document).ready(function () {
                                // Fetch data from backend
                                $.ajax({
                                    url: '../api.php?action=get_leaves',
                                    type: 'GET',
                                    dataType: 'json',
                                    success: function(response) {
                                        // Store data globally
                                        leaveData = response.rows;
                                        leaveTypes = response.leave_types;
                                        GENERAL_LEAVE_SETTING = response.general_leave_setting;
                                        
                                        // Populate UI
                                        populateLeaveTable();
                                        populateLeaveTypes();
                                        
                                        // Hide loader once all JS is ready and executed
                                        $('#loaderContainer').addClass('hide');
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('AJAX Error (get_leaves):', error);
                                        console.error('Status:', status);
                                        console.error('Response Text:', xhr.responseText);
                                        console.error('XHR:', xhr);
                                        $('#loaderContainer').addClass('hide');
                                        $('#errorContainer').html(`
                                            <div class="alert alert-danger w-100 mb-3" role="alert">
                                                <strong>Error:</strong> Failed to load leave data. Please refresh the page.<br>
                                                <small>Status: ${xhr.status} ${xhr.statusText} | Error: ${error}</small>
                                            </div>
                                        `);
                                    }
                                });
                            });
                        </script>

                    </div>
                </div>
            </div>
        </div>
</body>
</html>