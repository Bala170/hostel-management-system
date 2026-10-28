<?php
session_start();
include '../db.php';
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
    <title>Student Restrictions - Hostel Management</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../images/icons/mkce_s.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-5/bootstrap-5.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --topbar-height: 60px;
            --footer-height: 60px;
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --danger-color: #dc3545;
            --success-color: #1cc88a;
            --dark-bg: #1a1c23;
            --light-bg: #f8f9fc;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background: linear-gradient(135deg, #e7e9f3ff 0%, #f9f9faff 100%);
        }

        .content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .container-fluid {
            padding: 20px;
        }

        .breadcrumb-area {
            background: linear-gradient(to top, #fff1eb 0%, #ace0f9 100%);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin: 20px;
            padding: 15px 20px;
        }

        .card {
            border: none;
            box-shadow: var(--card-shadow);
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .gradient-header {
            background: linear-gradient(135deg, #4CAF50, #2196F3);
            color: white;
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }

        .restriction-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .restriction-leave {
            background-color: #fff3cd;
            color: #856404;
        }

        .restriction-outing {
            background-color: #f8d7da;
            color: #721c24;
        }

        .btn-sm-custom {
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <?php include '../sidebar.php'; ?>
    <?php include '../topbar.php'; ?>

    <div class="content">
        <div class="container-fluid">
            <!-- Breadcrumb -->
            <div class="breadcrumb-area">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Student Restrictions</li>
                    </ol>
                </nav>
            </div>

            <!-- Add Restriction Card -->
            <div class="card">
                <div class="gradient-header">
                    <i class="fas fa-lock me-2"></i> Add Student Restriction
                </div>
                <div class="card-body">
                    <form id="addRestrictionForm">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Student Roll Number</label>
                                <input type="text" class="form-control" id="rollNumber" placeholder="Enter roll number" required>
                                <small class="text-muted">e.g., 927623bcs086</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Restriction Type</label>
                                <select class="form-select" id="restrictionType" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="leave">Leave</option>
                                    <option value="outing">Outing</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i> Add Restriction
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Restrictions List Card -->
            <div class="card">
                <div class="gradient-header">
                    <i class="fas fa-list me-2"></i> Active Student Restrictions
                </div>
                <div class="card-body">
                    <!-- Filter Controls -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Filter by Type</label>
                            <select class="form-select" id="filterType">
                                <option value="">All Types</option>
                                <option value="leave">Leave</option>
                                <option value="outing">Outing</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Search by Roll Number</label>
                            <input type="text" class="form-control" id="searchRoll" placeholder="Enter roll number">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-secondary w-100" id="filterBtn">
                                <i class="fas fa-search me-1"></i> Filter
                            </button>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-info w-100" id="refreshBtn">
                                <i class="fas fa-sync me-1"></i> Refresh
                            </button>
                        </div>
                    </div>

                    <!-- Restrictions Table -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="restrictionsTable">
                            <thead class="gradient-header">
                                <tr>
                                    <th>Roll Number</th>
                                    <th>Student Name</th>
                                    <th>Restriction Type</th>
                                    <th>Created By</th>
                                    <th>Created Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="restrictionsList">
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <script>
        // Load restrictions on page load
        $(document).ready(function() {
            loadRestrictions();
        });

        // Load restrictions from server
        function loadRestrictions(filterType = '', searchRoll = '') {
            $.ajax({
                url: '../api.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'fetch_restrictions',
                    filter_type: filterType,
                    search_roll: searchRoll
                },
                success: function(response) {
                    if (response.success) {
                        displayRestrictions(response.restrictions);
                    } else {
                        $('#restrictionsList').html('<tr><td colspan="6" class="text-center text-danger">Error loading restrictions</td></tr>');
                    }
                },
                error: function() {
                    $('#restrictionsList').html('<tr><td colspan="6" class="text-center text-danger">Error loading restrictions</td></tr>');
                }
            });
        }

        // Display restrictions in table
        function displayRestrictions(restrictions) {
            if (restrictions.length === 0) {
                $('#restrictionsList').html('<tr><td colspan="6" class="text-center text-muted">No restrictions found</td></tr>');
                return;
            }

            let html = '';
            restrictions.forEach(function(restriction) {
                const restrictionBadgeClass = restriction.restriction_type === 'leave' ? 'restriction-leave' : 'restriction-outing';
                const createdDate = new Date(restriction.created_at).toLocaleString('en-IN');
                
                html += `
                    <tr>
                        <td><strong>${restriction.roll_number}</strong></td>
                        <td>${restriction.name || 'N/A'}</td>
                        <td><span class="restriction-badge ${restrictionBadgeClass}">${restriction.restriction_type.toUpperCase()}</span></td>
                        <td>${restriction.created_by}</td>
                        <td>${createdDate}</td>
                        <td>
                            <button class="btn btn-danger btn-sm-custom" onclick="removeRestriction(${restriction.restriction_id}, '${restriction.roll_number}', '${restriction.restriction_type}')">
                                <i class="fas fa-trash me-1"></i> Remove
                            </button>
                        </td>
                    </tr>
                `;
            });

            $('#restrictionsList').html(html);
        }

        // Add restriction form submission
        $('#addRestrictionForm').on('submit', function(e) {
            e.preventDefault();

            const rollNumber = $('#rollNumber').val().trim();
            const restrictionType = $('#restrictionType').val();

            if (!rollNumber || !restrictionType) {
                Swal.fire('Warning', 'Please fill all fields', 'warning');
                return;
            }

            $.ajax({
                url: '../api.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'add_restriction',
                    roll_number: rollNumber,
                    restriction_type: restrictionType
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success', response.message, 'success').then(() => {
                            $('#addRestrictionForm')[0].reset();
                            loadRestrictions();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to add restriction', 'error');
                }
            });
        });

        // Remove restriction
        function removeRestriction(restrictionId, rollNumber, restrictionType) {
            Swal.fire({
                title: 'Remove Restriction?',
                text: `Remove ${restrictionType.toUpperCase()} restriction from ${rollNumber}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '../api.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'remove_restriction',
                            restriction_id: restrictionId
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Success', response.message, 'success').then(() => {
                                    loadRestrictions();
                                });
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to remove restriction', 'error');
                        }
                    });
                }
            });
        }

        // Filter button click
        $('#filterBtn').on('click', function() {
            const filterType = $('#filterType').val();
            const searchRoll = $('#searchRoll').val().trim();
            loadRestrictions(filterType, searchRoll);
        });

        // Refresh button click
        $('#refreshBtn').on('click', function() {
            $('#filterType').val('');
            $('#searchRoll').val('');
            loadRestrictions();
        });

        // Search on Enter key
        $('#searchRoll').on('keypress', function(e) {
            if (e.which === 13) {
                $('#filterBtn').click();
            }
        });
    </script>
</body>

</html>
