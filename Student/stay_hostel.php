<?php
session_start();

if (!isset($_SESSION['user_id']) || (!isset($_SESSION['role']) && !isset($_SESSION['user_type'])) || ($_SESSION['role'] ?? $_SESSION['user_type'] ?? 'student') !== 'student') {
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

        body {
            background: linear-gradient(135deg, #e7e9f3ff 0%, #f9f9faff 100%);
            padding-bottom: var(--footer-height);
        }

        .content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            padding-bottom: calc(var(--footer-height) + 10px);
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .sidebar.collapsed + .content {
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

        .gradient-header {
            --bs-table-bg: transparent;
            --bs-table-color: #fff;
            background: linear-gradient(135deg, #4CAF50, #2196F3) !important;
            text-align: center;
            font-size: 0.9em;
        }

        .custom-tabs {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        }

        .custom-tabs .nav-tabs {
            border: none;
            gap: 10px;
            padding: 6px;
            background: #f8f9fd;
            border-radius: 12px;
        }

        .custom-tabs .nav-link {
            border: none !important;
            border-radius: 10px !important;
            padding: 10px 20px !important;
            font-weight: 600 !important;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            position: relative;
            overflow: hidden;
            transition: all 0.35s ease !important;
        }

        .custom-tabs .nav-link.active {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        #apply-tab {
            background: linear-gradient(135deg, #4E65FF, #92EFFD);
            color: #fff;
        }

        #apply-tab:not(.active) {
            background: #fff;
            color: #4E65FF;
        }

        #apply-tab:hover:not(.active) {
            background: linear-gradient(135deg, #4E65FF, #92EFFD);
            color: #fff;
        }

        #history-tab {
            background: linear-gradient(135deg, #34720d, #47e369);
            color: #fff;
        }

        #history-tab:not(.active) {
            background: #fff;
            color: #34720d;
        }

        #history-tab:hover:not(.active) {
            background: linear-gradient(135deg, #34720d, #47e369);
            color: #fff;
        }

        .tab-icon {
            margin-right: 8px;
            font-size: 0.9em;
            transition: transform 0.3s ease;
        }

        .custom-tabs .nav-link:hover .tab-icon {
            transform: rotate(15deg) scale(1.1);
        }

        .custom-tabs .nav-link.active .tab-icon {
            animation: tabBounce 0.5s ease infinite alternate;
        }

        @keyframes tabBounce {
            from { transform: translateY(0); }
            to { transform: translateY(-2px); }
        }

        .custom-tabs .tab-content {
            padding: 20px;
            margin-top: 15px;
            background: #fff;
            border-radius: 12px;
            min-height: 200px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
        }

        .footer {
            z-index: 2001 !important;
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .proof-preview-frame {
            width: 100%;
            height: 70vh;
            border: 0;
            border-radius: 8px;
            background: #f8f9fa;
        }

        #proofPreviewContainer img {
            max-height: 70vh;
            width: auto;
        }

        #proofModal .modal-header {
            background: linear-gradient(to right, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 15px 20px;
            border-bottom: none;
        }

        #proofModal .modal-header .modal-title {
            font-weight: 200;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        #proofModal .modal-header .btn-close {
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

        #proofModal .modal-header .btn-close:hover {
            background-color: rgba(255, 255, 255, 0.4);
            transform: scale(1.1);
        }

        #proofModal .modal-header .btn-close:focus {
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
            outline: none;
        }

        #stayEditModal .modal-header {
            background: linear-gradient(to right, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 15px 20px;
            border-bottom: none;
        }

        #stayEditModal .modal-header .modal-title {
            font-weight: 200;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        #stayEditModal .modal-header .btn-close {
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

        #stayEditModal .modal-header .btn-close:hover {
            background-color: rgba(255, 255, 255, 0.4);
            transform: scale(1.1);
        }

        #stayEditModal .modal-header .btn-close:focus {
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
            outline: none;
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body>
<?php include '../assets/sidebar.php'; ?>
<div class="content">
    <?php include '../assets/topbar.php'; ?>

    <div class="breadcrumb-area custom-gradient">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Stay In Hostel</li>
            </ol>
        </nav>
    </div>

    <div class="container-fluid">
        <div class="custom-tabs">
                <ul class="nav nav-tabs" id="stayHostelTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="apply-tab" data-bs-toggle="tab" href="#apply-pane" role="tab" aria-selected="true">
                            <span class="hidden-xs-down" style="font-size: 0.9em;">
                                <i class="fas fa-file-signature tab-icon"></i> Apply
                            </span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="history-tab" data-bs-toggle="tab" href="#history-pane" role="tab" aria-selected="false">
                            <span class="hidden-xs-down" style="font-size: 0.9em;">
                                <i class="fas fa-clock-rotate-left tab-icon"></i> My Requests
                            </span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="apply-pane" role="tabpanel">
                        <h6 class="mb-3">
                            <i class="fas fa-bed me-2"></i>Stay In Hostel Request Form
                        </h6>
                        <form id="stayHostelForm" enctype="multipart/form-data" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="from_date" class="form-label">From Date</label>
                                    <input type="date" id="from_date" name="from_date" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="to_date" class="form-label">To Date</label>
                                    <input type="date" id="to_date" name="to_date" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label for="reason" class="form-label">Reason</label>
                                    <textarea id="reason" name="reason" class="form-control" rows="4" required placeholder="Enter reason for staying in hostel"></textarea>
                                </div>
                                <div class="col-12">
                                    <label for="proof" class="form-label">Proof (Optional)</label>
                                    <input type="file" id="proof" name="proof" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                    <small class="text-muted">Allowed: JPG, PNG, PDF (Max: 5MB)</small>
                                </div>
                            </div>
                            <div class="mt-3 d-flex justify-content-end">
                                <button type="submit" id="submitStayBtn" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i>Submit Request
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="history-pane" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped w-100" id="stayRequestsTable">
                                <thead class="gradient-header">
                                <tr>
                                    <th>S.No</th>
                                    <th>From Date</th>
                                    <th>To Date</th>
                                    <th>Reason</th>
                                    <th>Proof</th>
                                    <th>Submitted At</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody id="stayRequestsBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
        </div>
    </div>

    <div class="modal fade" id="proofModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-circle-check me-2"></i>Proof Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="proofPreviewContainer" class="text-center"></div>
                </div>
                <div class="modal-footer">
                    <a id="proofDownloadBtn" href="#" class="btn btn-primary" download>
                        <i class="fas fa-download me-1"></i>Download
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="stayEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form id="stayEditForm" enctype="multipart/form-data" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Stay Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_request_id" name="request_id" value="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_from_date" class="form-label">From Date</label>
                                <input type="date" id="edit_from_date" name="from_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_to_date" class="form-label">To Date</label>
                                <input type="date" id="edit_to_date" name="to_date" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label for="edit_reason" class="form-label">Reason</label>
                                <textarea id="edit_reason" name="reason" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="col-12">
                                <label for="edit_proof" class="form-label">Proof (Optional)</label>
                                <input type="file" id="edit_proof" name="proof" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">Allowed: JPG, PNG, PDF (Max: 5MB)</small>
                            </div>
                            <div class="col-12" id="editRemoveProofWrap" style="display: none;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="edit_remove_proof" name="remove_proof">
                                    <label class="form-check-label" for="edit_remove_proof">Remove existing proof</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="editSubmitBtn" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '..\assets\footer.php'; ?>
</div>

<script>
    let stayTable = null;
    let stayLoadXhr = null;
    let proofModal = null;
    let editStayModal = null;

    function escHtml(str) {
        return (str || '').toString().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function escAttr(str) {
        return (str || '')
            .toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function fmtDateTime(val) {
        if (!val) return '-';
        const d = new Date(val);
        if (isNaN(d.getTime())) return val;
        return d.toLocaleString();
    }

    function buildProofCell(path) {
        if (!path) return '-';
        return `<button type="button" class="btn btn-sm btn-primary view-proof-btn" data-proof="${escAttr(path)}"><i class="fas fa-eye me-1"></i>View</button>`;
    }

    function openProofModal(path) {
        const cleanPath = (path || '').toString().replace(/^\/+/, '');
        if (!cleanPath) return;

        const proofUrl = encodeURI('../' + cleanPath);
        const ext = (cleanPath.split('.').pop() || '').toLowerCase().split('?')[0].split('#')[0];
        const imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

        const $container = $('#proofPreviewContainer');
        $container.empty();
        $('#proofDownloadBtn').attr('href', proofUrl);

        if (imageTypes.includes(ext)) {
            $('<img>', {
                src: proofUrl,
                alt: 'Proof Image',
                class: 'img-fluid rounded shadow-sm'
            }).appendTo($container);
        } else if (ext === 'pdf') {
            $('<iframe>', {
                src: proofUrl,
                class: 'proof-preview-frame'
            }).appendTo($container);
        } else {
            $container.html('<div class="alert alert-info mb-0">Preview is not available for this file type. Use download.</div>');
        }

        if (proofModal) {
            proofModal.show();
        }
    }

    function resetStayForm() {
        const form = document.getElementById('stayHostelForm');
        form.reset();
        form.classList.remove('was-validated');
        $('#submitStayBtn').html('<i class="fas fa-paper-plane me-1"></i>Submit Request');
    }

    function resetEditStayForm() {
        const form = document.getElementById('stayEditForm');
        if (!form) return;
        form.reset();
        form.classList.remove('was-validated');
        $('#edit_request_id').val('');
        $('#editRemoveProofWrap').hide();
        $('#edit_remove_proof').prop('checked', false);
        $('#editSubmitBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Update Request');
    }

    function loadStayRequests(options = {}) {
        const silent = options.silent === true;

        if (stayLoadXhr && stayLoadXhr.readyState !== 4) {
            stayLoadXhr.abort();
        }

        stayLoadXhr = $.ajax({
            url: '../api.php',
            type: 'GET',
            dataType: 'json',
            cache: false,
            data: {
                action: 'get_student_stay_hostel_requests',
                _ts: Date.now()
            },
            success: function (res) {
                if (!res.success) {
                    if (!silent) {
                        Swal.fire('Error', res.message || 'Failed to load requests', 'error');
                    }
                    return;
                }

                const rows = [];
                (res.rows || []).forEach((row, index) => {
                    rows.push([
                        index + 1,
                        escHtml(row.from_date),
                        escHtml(row.to_date),
                        escHtml(row.reason),
                        buildProofCell(row.proof_path),
                        fmtDateTime(row.requested_at),
                        `<div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-info btn-edit"
                                data-id="${row.request_id}"
                                data-from="${escAttr(row.from_date)}"
                                data-to="${escAttr(row.to_date)}"
                                data-reason="${escAttr(row.reason)}"
                                data-proof="${escAttr(row.proof_path || '')}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-delete" data-id="${row.request_id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>`
                    ]);
                });

                if (!stayTable) {
                    stayTable = $('#stayRequestsTable').DataTable({
                        pageLength: 10,
                        responsive: true,
                        order: [[5, 'desc']],
                        columnDefs: [{ orderable: false, targets: [6] }]
                    });
                }

                stayTable.clear();
                stayTable.rows.add(rows).draw(false);
            },
            error: function (xhr, status) {
                if (status === 'abort') return;
                if (!silent) {
                    Swal.fire('Error', 'Unable to load request history', 'error');
                }
            }
        });
    }

    $(document).ready(function () {
        const proofModalEl = document.getElementById('proofModal');
        if (proofModalEl) {
            proofModal = new bootstrap.Modal(proofModalEl);
            proofModalEl.addEventListener('hidden.bs.modal', function () {
                $('#proofPreviewContainer').empty();
                $('#proofDownloadBtn').attr('href', '#');
            });
        }

        const editModalEl = document.getElementById('stayEditModal');
        if (editModalEl) {
            editStayModal = new bootstrap.Modal(editModalEl);
            editModalEl.addEventListener('hidden.bs.modal', function () {
                resetEditStayForm();
            });
        }

        loadStayRequests({ silent: true });

        $('#history-tab').on('shown.bs.tab', function () {
            loadStayRequests({ silent: true });
            if (stayTable) {
                stayTable.columns.adjust().draw(false);
            }
        });

        $('#stayHostelForm').on('submit', function (e) {
            e.preventDefault();
            const form = this;
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                Swal.fire('Validation', 'Please fill all required fields', 'warning');
                return;
            }

            const btn = $('#submitStayBtn');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Submitting...');

            const formData = new FormData(form);
            formData.append('action', 'submit_stay_hostel_request');

            $.ajax({
                url: '../api.php',
                type: 'POST',
                dataType: 'json',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res.success) {
                        Swal.fire('Success', res.message, 'success');
                        resetStayForm();
                        loadStayRequests({ silent: true });
                    } else {
                        Swal.fire('Error', res.message || 'Submission failed', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Unable to submit request', 'error');
                },
                complete: function () {
                    btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i>Submit Request');
                }
            });
        });

        $('#stayEditForm').on('submit', function (e) {
            e.preventDefault();
            const form = this;
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                Swal.fire('Validation', 'Please fill all required fields', 'warning');
                return;
            }

            const btn = $('#editSubmitBtn');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Updating...');

            const formData = new FormData(form);
            formData.append('action', 'submit_stay_hostel_request');

            $.ajax({
                url: '../api.php',
                type: 'POST',
                dataType: 'json',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res.success) {
                        Swal.fire('Success', res.message, 'success');
                        if (editStayModal) {
                            editStayModal.hide();
                        }
                        loadStayRequests({ silent: true });
                    } else {
                        Swal.fire('Error', res.message || 'Update failed', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Unable to update request', 'error');
                },
                complete: function () {
                    btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Update Request');
                }
            });
        });

        $(document).on('click', '.btn-edit', function () {
            const requestId = $(this).data('id');
            const fromDate = $(this).data('from');
            const toDate = $(this).data('to');
            const reason = $(this).data('reason');
            const proof = $(this).data('proof');

            $('#edit_request_id').val(requestId);
            $('#edit_from_date').val(fromDate);
            $('#edit_to_date').val(toDate);
            $('#edit_reason').val(reason);
            $('#edit_remove_proof').prop('checked', false);
            if (proof) {
                $('#editRemoveProofWrap').show();
            } else {
                $('#editRemoveProofWrap').hide();
            }
            if (editStayModal) {
                editStayModal.show();
            }
        });

        $(document).on('click', '.view-proof-btn', function () {
            const path = $(this).attr('data-proof') || '';
            openProofModal(path);
        });

        $(document).on('click', '.btn-delete', function () {
            const requestId = $(this).data('id');
            Swal.fire({
                title: 'Delete Request?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: '../api.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'delete_stay_hostel_request',
                        request_id: requestId
                    },
                    success: function (res) {
                        if (res.success) {
                            Swal.fire('Deleted', res.message, 'success');
                            loadStayRequests({ silent: true });
                        } else {
                            Swal.fire('Error', res.message || 'Delete failed', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Unable to delete request', 'error');
                    }
                });
            });
        });
    });
</script>
</body>
</html>
