<?php
ob_start(); // Start output buffering to catch any stray output
session_start();
include 'db.php'; // your DB connection ($conn)
include __DIR__ . '/admin/admin_scope.php';

// Ensure only JSON is output
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Utility function for sanitizing input
function sanitize_input($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

// Helper: convert server file path to web URL for student photos
function path_to_url_student($filePath)
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'];
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $p = str_replace('\\', '/', $filePath);

    if (strpos($p, $docRoot) === 0) {
        $relative = substr($p, strlen($docRoot));
        return $baseUrl . '/' . ltrim($relative, '/');
    } else {
        return $baseUrl . '/Student/uploads/' . basename($p);
    }
}

function get_current_admin_scope_gender()
{
    return get_hostel_gender_scope_for_role();
}

function require_any_admin_api_role()
{
    if (!is_any_admin_role()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

function is_mess_access_role(): bool
{
    $role = $_SESSION['role'] ?? ($_SESSION['user_type'] ?? '');
    return in_array($role, ['mess_supervisor', 'admin', 'male_admin', 'female_admin'], true);
}

function require_mess_access_role(): void
{
    if (!is_mess_access_role()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

function get_mess_scope_gender(): ?string
{
    $role = $_SESSION['role'] ?? ($_SESSION['user_type'] ?? '');
    if ($role === 'male_admin') {
        return 'Male';
    }
    if ($role === 'female_admin') {
        return 'Female';
    }
    return null;
}

function get_general_leave_enabled_by_for_current_admin(): string
{
    $role = $_SESSION['role'] ?? ($_SESSION['user_type'] ?? '');
    if ($role === 'male_admin') {
        return 'male_admin';
    }
    if ($role === 'female_admin') {
        return 'female_admin';
    }
    return 'admin';
}

header('Content-Type: application/json');

// Accept both form data and JSON body payloads
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $jsonData = json_decode($raw, true);
    if (is_array($jsonData)) {
        $_POST = array_merge($_POST, $jsonData);
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Process requests whether they come from POST or GET
if (!empty($action)) {
    switch ($action) {

        // Admin leave approval

        case 'approve':
            $id = $_POST['id'] ?? '';

            if ($id) {
                $status = "Approved";
                $update_sql = "UPDATE leave_applications SET Status=?, Remarks='Approved by HOD' WHERE leave_id=?";
                $stmt = mysqli_prepare($conn, $update_sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ss", $status, $id);
                    if (mysqli_stmt_execute($stmt)) {
                        echo json_encode(['status' => 'success', 'message' => 'Leave Approved successfully.']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_stmt_error($stmt)]);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
            }
            break;

        // Faculty leave approval - forwards to admin
        case 'faculty_approve':
            $id = $_POST['id'] ?? '';

            if ($id) {
                $status = "Forwarded to Admin";
                $remarks = "Approved by Faculty";
                $update_sql = "UPDATE leave_applications SET Status=?, Remarks=? WHERE leave_id=?";
                $stmt = mysqli_prepare($conn, $update_sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sss", $status, $remarks, $id);
                    if (mysqli_stmt_execute($stmt)) {
                        echo json_encode(['status' => 'success', 'message' => 'Leave forwarded to Admin for final approval.']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_stmt_error($stmt)]);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
            }
            break;


        case 'reject':
            $id = $_POST['id'] ?? '';
            $rejectionreason = $_POST['rejectionreason'] ?? '';

            if (empty($id)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request: Missing ID']);
                break;
            }

            if (empty($rejectionreason)) {
                echo json_encode(['status' => 'error', 'message' => 'Rejection reason is required']);
                break;
            }

            error_log("Reject Leave - ID: $id, Reason: $rejectionreason");

            // Set Status as REJECTED BY ADMIN, store reason in Remarks
            $status = "Rejected by Admin";

            $update_sql = "UPDATE leave_applications SET Status=?, Remarks=? WHERE leave_id=?";
            $stmt = mysqli_prepare($conn, $update_sql);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sss", $status, $rejectionreason, $id);
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['status' => 'success', 'message' => 'Leave rejected successfully.']);
                } else {
                    $error = mysqli_stmt_error($stmt);
                    error_log("SQL Error: $error");
                    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $error]);
                }
                mysqli_stmt_close($stmt);
            } else {
                $error = mysqli_error($conn);
                error_log("SQL Error: $error");
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $error]);
            }
            break;



        case 'ivrApprove':
            $id = $_POST['id'] ?? '';

            if ($id) {
                $update_sql = "UPDATE leave_applications SET Status='Approved' WHERE leave_id=?";
                $stmt = mysqli_prepare($conn, $update_sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "s", $id);
                    if (mysqli_stmt_execute($stmt)) {
                        echo json_encode(['status' => 'success', 'message' => 'Leave IVR Approved successfully.']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_stmt_error($stmt)]);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
            }
            break;

        case 'ivrReject':
            $id = $_POST['id'] ?? '';

            if ($id) {
                $status = "Rejected by Parents";

                $update_sql = "UPDATE leave_applications SET Status=? WHERE leave_id=?";
                $stmt = mysqli_prepare($conn, $update_sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ss", $status, $id);
                    if (mysqli_stmt_execute($stmt)) {
                        echo json_encode(['status' => 'success', 'message' => 'Leave rejected by parents successfully.']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_stmt_error($stmt)]);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
            }
            break;



        // General Leave Enable/Disable Actions

        case 'enableGeneralLeave':
            require_any_admin_api_role();
            $leaveEnabledBy = get_general_leave_enabled_by_for_current_admin();
            $isSuperGeneralLeave = ($leaveEnabledBy === 'admin');
            $leave_name = $_POST['leave_name'] ?? '';
            $from_date = $_POST['from_date'] ?? '';
            $to_date = $_POST['to_date'] ?? '';
            $instructions = $_POST['instructions'] ?? '';

            // Validate required fields
            if (empty($leave_name) || empty($from_date) || empty($to_date)) {
                echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
                exit;
            }

            // Validate date range
            if (strtotime($from_date) >= strtotime($to_date)) {
                echo json_encode(['success' => false, 'message' => 'From date must be earlier than To date.']);
                exit;
            }

            // Check if there's already an active general leave
            $check_sql = $isSuperGeneralLeave
                ? "SELECT * FROM general_Leave WHERE Is_Enabled = ? AND (Enabled_By = 'admin' OR Enabled_By = '' OR Enabled_By IS NULL)"
                : "SELECT * FROM general_Leave WHERE Is_Enabled = ? AND Enabled_By = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            if (!$check_stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error: Failed to prepare statement.']);
                exit;
            }

            $active_status = 1;
            if ($isSuperGeneralLeave) {
                mysqli_stmt_bind_param($check_stmt, "i", $active_status);
            } else {
                mysqli_stmt_bind_param($check_stmt, "is", $active_status, $leaveEnabledBy);
            }
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($check_result) > 0) {
                mysqli_stmt_close($check_stmt);
                echo json_encode(['success' => false, 'message' => 'There is already an active general leave. Please disable it first.']);
                exit;
            }
            mysqli_stmt_close($check_stmt);

            // Insert new general leave
            $insert_sql = "INSERT INTO general_Leave (Leave_Name, From_Date, To_Date, Enabled_By, Instructions, Is_Enabled, Created_Date) 
                                       VALUES (?, ?, ?, ?, ?, 1, NOW())";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);

            if ($insert_stmt) {
                mysqli_stmt_bind_param($insert_stmt, "sssss", $leave_name, $from_date, $to_date, $leaveEnabledBy, $instructions);
                if (mysqli_stmt_execute($insert_stmt)) {
                    echo json_encode(['success' => true, 'message' => 'General Leave has been enabled successfully.']);
                } else {
                    $error = mysqli_stmt_error($insert_stmt);
                    error_log("SQL Error: $error");
                    echo json_encode(['success' => false, 'message' => 'Database error: Failed to enable general leave.']);
                }
                mysqli_stmt_close($insert_stmt);
            } else {
                $error = mysqli_error($conn);
                error_log("SQL Error: $error");
                echo json_encode(['success' => false, 'message' => 'Database error: Failed to prepare statement.']);
            }
            break;

        case 'disableGeneralLeave':
            require_any_admin_api_role();
            $leaveEnabledBy = get_general_leave_enabled_by_for_current_admin();
            $isSuperGeneralLeave = ($leaveEnabledBy === 'admin');
            $leave_id = $_POST['leave_id'] ?? '';

            if (empty($leave_id)) {
                echo json_encode(['success' => false, 'message' => 'Invalid request: Missing leave ID.']);
                exit;
            }

            // Update the general leave status to 0 (disabled)
            $update_sql = $isSuperGeneralLeave
                ? "UPDATE general_Leave SET Is_Enabled = 0 WHERE GeneralLeave_ID = ? AND Is_Enabled = 1"
                : "UPDATE general_Leave SET Is_Enabled = 0 WHERE GeneralLeave_ID = ? AND Is_Enabled = 1 AND Enabled_By = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);

            if ($update_stmt) {
                if ($isSuperGeneralLeave) {
                    mysqli_stmt_bind_param($update_stmt, "i", $leave_id);
                } else {
                    mysqli_stmt_bind_param($update_stmt, "is", $leave_id, $leaveEnabledBy);
                }
                if (mysqli_stmt_execute($update_stmt)) {
                    if (mysqli_stmt_affected_rows($update_stmt) > 0) {
                        echo json_encode(['success' => true, 'message' => 'General Leave has been disabled successfully.']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'No active general leave found with the specified ID.']);
                    }
                } else {
                    $error = mysqli_stmt_error($update_stmt);
                    error_log("SQL Error: $error");
                    echo json_encode(['success' => false, 'message' => 'Database error: Failed to disable general leave.']);
                }
                mysqli_stmt_close($update_stmt);
            } else {
                $error = mysqli_error($conn);
                error_log("SQL Error: $error");
                echo json_encode(['success' => false, 'message' => 'Database error: Failed to prepare statement.']);
            }
            break;


        case 'updateGeneralLeave':
            require_any_admin_api_role();
            $leaveEnabledBy = get_general_leave_enabled_by_for_current_admin();
            $isSuperGeneralLeave = ($leaveEnabledBy === 'admin');
            $leave_id = $_POST['leave_id'] ?? '';
            $leave_name = $_POST['leave_name'] ?? '';
            $from_date = $_POST['from_date'] ?? '';
            $to_date = $_POST['to_date'] ?? '';
            $instructions = $_POST['instructions'] ?? '';

            // Validate required fields
            if (empty($leave_id) || empty($leave_name) || empty($from_date) || empty($to_date)) {
                echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
                exit;
            }

            // Validate date range
            if (strtotime($from_date) >= strtotime($to_date)) {
                echo json_encode(['success' => false, 'message' => 'From date must be earlier than To date.']);
                exit;
            }

            // Update general leave
            $update_sql = "UPDATE general_Leave 
                                       SET Leave_Name = ?, From_Date = ?, To_Date = ?, Instructions = ? 
                                       WHERE GeneralLeave_ID = ? AND Is_Enabled = 1 " . ($isSuperGeneralLeave ? "" : "AND Enabled_By = ?");
            $update_stmt = mysqli_prepare($conn, $update_sql);

            if ($update_stmt) {
                if ($isSuperGeneralLeave) {
                    mysqli_stmt_bind_param($update_stmt, "ssssi", $leave_name, $from_date, $to_date, $instructions, $leave_id);
                } else {
                    mysqli_stmt_bind_param($update_stmt, "ssssis", $leave_name, $from_date, $to_date, $instructions, $leave_id, $leaveEnabledBy);
                }
                if (mysqli_stmt_execute($update_stmt)) {
                    if (mysqli_stmt_affected_rows($update_stmt) > 0) {
                        echo json_encode(['success' => true, 'message' => 'General Leave has been updated successfully.']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'No changes were made or leave not found.']);
                    }
                } else {
                    $error = mysqli_stmt_error($update_stmt);
                    error_log("SQL Error: $error");
                    echo json_encode(['success' => false, 'message' => 'Database error: Failed to update general leave.']);
                }
                mysqli_stmt_close($update_stmt);
            } else {
                $error = mysqli_error($conn);
                error_log("SQL Error: $error");
                echo json_encode(['success' => false, 'message' => 'Database error: Failed to prepare statement.']);
            }
            break;


        case 'deleteGeneralLeave':
            require_any_admin_api_role();
            $leaveEnabledBy = get_general_leave_enabled_by_for_current_admin();
            $isSuperGeneralLeave = ($leaveEnabledBy === 'admin');
            $leave_id = $_POST['leave_id'] ?? '';

            if (empty($leave_id)) {
                echo json_encode(['success' => false, 'message' => 'Invalid request: Missing leave ID.']);
                exit;
            }

            // Update the general leave status to 0 (disabled)
            $update_sql = $isSuperGeneralLeave
                ? "DELETE FROM general_Leave WHERE GeneralLeave_ID = ? AND Is_Enabled = 1"
                : "DELETE FROM general_Leave WHERE GeneralLeave_ID = ? AND Is_Enabled = 1 AND Enabled_By = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);

            if ($update_stmt) {
                if ($isSuperGeneralLeave) {
                    mysqli_stmt_bind_param($update_stmt, "i", $leave_id);
                } else {
                    mysqli_stmt_bind_param($update_stmt, "is", $leave_id, $leaveEnabledBy);
                }
                if (mysqli_stmt_execute($update_stmt)) {
                    if (mysqli_stmt_affected_rows($update_stmt) > 0) {
                        echo json_encode(['success' => true, 'message' => 'General Leave has been deleted successfully.']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'No active general leave found with the specified ID.']);
                    }
                } else {
                    $error = mysqli_stmt_error($update_stmt);
                    error_log("SQL Error: $error");
                    echo json_encode(['success' => false, 'message' => 'Database error: Failed to delete general leave.']);
                }
                mysqli_stmt_close($update_stmt);
            } else {
                $error = mysqli_error($conn);
                error_log("SQL Error: $error");
                echo json_encode(['success' => false, 'message' => 'Database error: Failed to prepare statement.']);
            }
            break;



        case 'get_active':
            require_any_admin_api_role();
            $leaveEnabledBy = get_general_leave_enabled_by_for_current_admin();
            $isSuperGeneralLeave = ($leaveEnabledBy === 'admin');
            // Get currently active general leave
            $sql = $isSuperGeneralLeave
                ? "SELECT * FROM general_Leave WHERE Is_Enabled = ? AND (Enabled_By = 'admin' OR Enabled_By = '' OR Enabled_By IS NULL) ORDER BY GeneralLeave_ID DESC LIMIT 1"
                : "SELECT * FROM general_Leave WHERE Is_Enabled = ? AND Enabled_By = ? ORDER BY GeneralLeave_ID DESC LIMIT 1";
            $stmt = mysqli_prepare($conn, $sql);

            if ($stmt) {
                $active_status = 1;
                if ($isSuperGeneralLeave) {
                    mysqli_stmt_bind_param($stmt, "i", $active_status);
                } else {
                    mysqli_stmt_bind_param($stmt, "is", $active_status, $leaveEnabledBy);
                }
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if ($result && mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_assoc($result);
                    echo json_encode(['success' => true, 'data' => $row]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No active general leave found.']);
                }
                mysqli_stmt_close($stmt);
            } else {
                $error = mysqli_error($conn);
                error_log("SQL Error: $error");
                echo json_encode(['success' => false, 'message' => 'Database error: Failed to prepare statement.']);
            }
            break;

        case 'getStudentLeaveHistory':
            $reg_no = $_POST['reg_no'] ?? '';

            if (empty($reg_no)) {
                echo json_encode(['success' => false, 'message' => 'Invalid request: Missing registration number.']);
                exit;
            }

            // Get student info
            $student_sql = "SELECT name, roll_number FROM students WHERE roll_number = ?";
            $student_stmt = mysqli_prepare($conn, $student_sql);

            if (!$student_stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error.']);
                exit;
            }

            mysqli_stmt_bind_param($student_stmt, "s", $reg_no);
            mysqli_stmt_execute($student_stmt);
            $student_result = mysqli_stmt_get_result($student_stmt);

            if (mysqli_num_rows($student_result) == 0) {
                echo json_encode(['success' => false, 'message' => 'Student not found.']);
                exit;
            }

            $student = mysqli_fetch_assoc($student_result);
            mysqli_stmt_close($student_stmt);

            // Get leave statistics by type
            $stats_sql = "SELECT 
                                        lt.Leave_Type_Name,
                                        COUNT(*) as total_leaves,
                                        SUM(CASE WHEN la.Status = 'Approved' THEN 1 ELSE 0 END) as approved_count,
                                        SUM(CASE WHEN la.Status LIKE 'Rejected%' THEN 1 ELSE 0 END) as rejected_count,
                                        SUM(CASE WHEN la.Status IN ('Pending', 'Forwarded to Admin') THEN 1 ELSE 0 END) as pending_count
                                    FROM leave_applications la
                                    JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
                                    WHERE la.Reg_No = ?
                                    GROUP BY lt.Leave_Type_Name
                                    ORDER BY total_leaves DESC";

            $stats_stmt = mysqli_prepare($conn, $stats_sql);
            mysqli_stmt_bind_param($stats_stmt, "s", $reg_no);
            mysqli_stmt_execute($stats_stmt);
            $stats_result = mysqli_stmt_get_result($stats_stmt);

            $leave_stats = [];
            while ($row = mysqli_fetch_assoc($stats_result)) {
                $leave_stats[] = $row;
            }
            mysqli_stmt_close($stats_stmt);

            // Get recent leave history
            $history_sql = "SELECT 
                                            la.Leave_ID,
                                            lt.Leave_Type_Name,
                                            la.Applied_Date,
                                            la.From_Date,
                                            la.To_Date,
                                            la.Reason,
                                            la.Status,
                                            la.Remarks,
                                            DATEDIFF(la.To_Date, la.From_Date) + 1 as duration_days
                                        FROM leave_applications la
                                        JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
                                        WHERE la.Reg_No = ?
                                        ORDER BY la.Applied_Date DESC
                                        LIMIT 10";

            $history_stmt = mysqli_prepare($conn, $history_sql);
            mysqli_stmt_bind_param($history_stmt, "s", $reg_no);
            mysqli_stmt_execute($history_stmt);
            $history_result = mysqli_stmt_get_result($history_stmt);

            $leave_history = [];
            while ($row = mysqli_fetch_assoc($history_result)) {
                $leave_history[] = $row;
            }
            mysqli_stmt_close($history_stmt);

            // Get total counts
            $total_sql = "SELECT 
                                        COUNT(*) as total_applications,
                                        SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) as total_approved,
                                        SUM(CASE WHEN Status LIKE 'Rejected%' THEN 1 ELSE 0 END) as total_rejected,
                                        SUM(CASE WHEN Status IN ('Pending', 'Forwarded to Admin') THEN 1 ELSE 0 END) as total_pending
                                    FROM leave_applications
                                    WHERE Reg_No = ?";

            $total_stmt = mysqli_prepare($conn, $total_sql);
            mysqli_stmt_bind_param($total_stmt, "s", $reg_no);
            mysqli_stmt_execute($total_stmt);
            $total_result = mysqli_stmt_get_result($total_stmt);
            $totals = mysqli_fetch_assoc($total_result);
            mysqli_stmt_close($total_stmt);

            echo json_encode([
                'success' => true,
                'student' => $student,
                'leave_stats' => $leave_stats,
                'leave_history' => $leave_history,
                'totals' => $totals
            ]);
            break;

        case 'updateLeaveDates':
            $id = $_POST['id'] ?? '';
            $from_date = $_POST['from_date'] ?? '';
            $to_date = $_POST['to_date'] ?? '';

            if (empty($id) || empty($from_date) || empty($to_date)) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
                break;
            }

            // Normalize incoming values to MySQL DATETIME if possible
            $from_ts = strtotime($from_date);
            $to_ts = strtotime($to_date);
            if ($from_ts === false || $to_ts === false) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid date format.']);
                break;
            }

            // Validate range
            if ($from_ts > $to_ts) {
                echo json_encode(['status' => 'error', 'message' => 'From date cannot be after To date.']);
                break;
            }

            $from_mysql = date('Y-m-d H:i:s', $from_ts);
            $to_mysql = date('Y-m-d H:i:s', $to_ts);

            // Only allow update for Pending, Forwarded to Admin, or Approved
            $check_sql = "SELECT Status FROM leave_applications WHERE Leave_ID = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            if (!$check_stmt) {
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
                break;
            }
            mysqli_stmt_bind_param($check_stmt, 's', $id);
            mysqli_stmt_execute($check_stmt);
            $check_res = mysqli_stmt_get_result($check_stmt);
            $row = $check_res ? mysqli_fetch_assoc($check_res) : null;
            mysqli_stmt_close($check_stmt);

            if (!$row) {
                echo json_encode(['status' => 'error', 'message' => 'Leave not found.']);
                break;
            }



            // Perform update
            $upd_sql = "UPDATE leave_applications SET From_Date = ?, To_Date = ? WHERE Leave_ID = ?";
            $upd_stmt = mysqli_prepare($conn, $upd_sql);
            if ($upd_stmt) {
                mysqli_stmt_bind_param($upd_stmt, 'sss', $from_mysql, $to_mysql, $id);
                if (mysqli_stmt_execute($upd_stmt)) {
                    echo json_encode(['status' => 'success', 'message' => 'Leave dates updated successfully.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_stmt_error($upd_stmt)]);
                }
                mysqli_stmt_close($upd_stmt);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
            }
            break;

        //Admin User registration

        case 'get_hostels':
            $scopeGender = get_current_admin_scope_gender();
            if (isset($_POST['gender'])) {

                $requestedGender = $_POST['gender'];
                if ($scopeGender !== null && $requestedGender !== $scopeGender) {
                    break;
                }

                $stmt = $conn->prepare("SELECT hostel_id, hostel_name FROM hostels WHERE gender = ? ORDER BY hostel_name");
                $stmt->bind_param("s", $requestedGender);
                $stmt->execute();

                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . $row['hostel_id'] . '">' . $row['hostel_name'] . '</option>';
                    }
                }

                $stmt->close();
                break;
            }
            if ($scopeGender !== null) {
                $stmt = $conn->prepare("SELECT hostel_id, hostel_name FROM hostels WHERE gender = ? ORDER BY hostel_name");
                $stmt->bind_param("s", $scopeGender);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . $row['hostel_id'] . '">' . $row['hostel_name'] . '</option>';
                    }
                }
                $stmt->close();
            } else {
                $sql = "SELECT hostel_id, hostel_name FROM hostels ORDER BY hostel_name";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . $row['hostel_id'] . '">' . $row['hostel_name'] . '</option>';
                    }
                }
            }
            break;

        case 'get_academic_batch':
            $sql = "SELECT academic_batch FROM academic_batch ORDER BY id";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<option value="' . $row['academic_batch'] . '">' . $row['academic_batch'] . '</option>';
                }
            }
            break;

        case 'get_rooms':
            $hostel_id = $_POST['hostel_id'] ?? '';
            $block = $_POST['block'] ?? '';
            $floor = $_POST['floor'] ?? '';

            if (!is_hostel_id_allowed_for_current_admin($conn, (int)$hostel_id)) {
                echo "<option value=''>Unauthorized hostel access</option>";
                exit;
            }

            if (empty($hostel_id) || empty($block) || empty($floor)) {
                echo "<option value=''>Invalid parameters</option>";
                exit;
            }

            // Query to get available rooms (empty or already occupied by students only)
            // Exclude rooms assigned to faculty members
            $query = "SELECT r.room_id, r.room_number, r.capacity, r.occupied,
                     (SELECT COUNT(*) FROM students s WHERE s.room_id = r.room_id AND s.status = '1') as student_count
              FROM rooms r 
              WHERE r.hostel_id = ? AND r.block = ? AND r.floor = ? 
              AND (r.occupied = 0 OR r.room_id IN (SELECT DISTINCT room_id FROM students WHERE room_id IS NOT NULL AND status = '1'))
              AND r.room_id NOT IN (SELECT DISTINCT room_id FROM hostel_faculty WHERE room_id IS NOT NULL AND status = '1')
              ORDER BY r.room_number";

            $stmt = $conn->prepare($query);
            $stmt->bind_param('iss', $hostel_id, $block, $floor);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $available = $row['capacity'] - $row['student_count'];
                    // Always show the room if it has availability or is already occupied by students
                    echo "<option value='{$row['room_id']}'>{$row['room_number']} (Available: {$available})</option>";
                }
            } else {
                echo "<option value=''>No rooms available</option>";
            }

            $stmt->close();
            exit;

        case 'update_student':
            try {
                $conn->begin_transaction();

                function strpost($k)
                {
                    return isset($_POST[$k]) && $_POST[$k] !== '' ? trim($_POST[$k]) : null;
                }

                $student_id = (int) strpost('student_id');
                if (!$student_id)
                    throw new Exception("Missing student_id");

                // Normalize enums
                $admission_raw = strtolower((string) strpost('admission_type'));
                $admission = (strpos($admission_raw, 'manage') !== false) ? 'Management' : 'Counseling';


                $gender = strpost('gender');
                // Fields
                $register_no = strpost('register_no');
                $student_name = strpost('student_name');
                $date_of_join = strpost('date_of_join');
                $date_of_birth = strpost('dob') ?? strpost('date_of_birth');
                $email = strpost('email');
                $fingerprint_id = strpost('fingerprint_id');
                $language = strpost('language');
                $student_mobile_no = strpost('student_no') ?? strpost('student_mobile_no');
                $aadhaar = strpost('aadhaar');
                $department = strpost('department');
                $yos = strpost('year_of_study');
                $academic_batch = strpost('academic_batch');
                $stay_type = strpost('stay_type');
                $father_phone = strpost('father_no');
                $mother_phone = strpost('mother_no');
                $g_phone = strpost('guardian_no');
                $father_name = strpost('father_name');
                $mother_name = strpost('mother_name');
                $g_name = strpost('guardian_name') ?? strpost('g_name');
                $status = '1';

                // Convert year of study
                $year_map = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4];
                $year_of_study = $year_map[$yos] ?? null;

                // âœ… Update students table (excluding hostel/room/floor/block)
                $stmt = $conn->prepare("
            UPDATE students SET
                roll_number = ?, 
                name = ?, 
                date_of_join = ?, 
                date_of_birth = ?, 
                admission_type = ?, 
                email = ?, 
                fingerprint_id = ?, 
                gender = ?, 
                language = ?, 
                student_mobile_no = ?, 
                aadhaar = ?, 
                department = ?, 
                year_of_study = ?, 
                academic_batch = ?, 
                status = ?
            WHERE student_id = ?
        ");
                if (!$stmt)
                    throw new Exception("Prepare update student failed: " . $conn->error);

                $stmt->bind_param(
                    "ssssssssssssissi",
                    $register_no,
                    $student_name,
                    $date_of_join,
                    $date_of_birth,
                    $admission,
                    $email,
                    $fingerprint_id,
                    $gender,
                    $language,
                    $student_mobile_no,
                    $aadhaar,
                    $department,
                    $year_of_study,
                    $academic_batch,
                    $status,
                    $student_id
                );

                if (!$stmt->execute())
                    throw new Exception("Update student failed: " . $stmt->error);
                $stmt->close();

                // âœ… Update guardians
                $approval_primary = strtolower((string) strpost('approval_no'));
                $approval_alt = strtolower((string) strpost('alt_approval_no'));

                $decideApproval = function ($relationLabel, $approval_primary, $approval_alt) {
                    $relationLabel = strtolower($relationLabel);
                    if ($approval_primary === $relationLabel)
                        return 'primary';
                    if ($approval_alt === $relationLabel)
                        return 'alternate';
                    return 'none';
                };

                $relations = [
                    ['father', $father_name, $father_phone],
                    ['mother', $mother_name, $mother_phone],
                    ['guardian', $g_name, $g_phone]
                ];

                foreach ($relations as [$relation, $name, $phone]) {
                    $ap = $decideApproval($relation, $approval_primary, $approval_alt);
                    $stmtG = $conn->prepare("
                UPDATE guardians 
                SET name = ?, phone = ?, approval_type = ? 
                WHERE student_id = ? AND relation = ?
            ");
                    if (!$stmtG)
                        throw new Exception("Prepare update guardian failed: " . $conn->error);
                    $stmtG->bind_param("sssis", $name, $phone, $ap, $student_id, $relation);
                    if (!$stmtG->execute())
                        throw new Exception("Update guardian ($relation) failed: " . $stmtG->error);
                    $stmtG->close();
                }

                // âœ… Update temporary stay if applicable
                if (strtolower($stay_type) === 'temporary') {
                    $from_date = strpost('from_date');
                    $to_date = strpost('to_date');

                    // Check if record exists
                    $checkStay = $conn->prepare("SELECT COUNT(*) FROM temporary_stay WHERE student_id = ?");
                    $checkStay->bind_param("i", $student_id);
                    $checkStay->execute();
                    $checkStay->bind_result($cnt);
                    $checkStay->fetch();
                    $checkStay->close();

                    if ($cnt > 0) {
                        $stmtStay = $conn->prepare("UPDATE temporary_stay SET from_date = ?, to_date = ? WHERE student_id = ?");
                        $stmtStay->bind_param("ssi", $from_date, $to_date, $student_id);
                    } else {
                        $stmtStay = $conn->prepare("INSERT INTO temporary_stay (student_id, from_date, to_date) VALUES (?, ?, ?)");
                        $stmtStay->bind_param("iss", $student_id, $from_date, $to_date);
                    }
                    if (!$stmtStay->execute())
                        throw new Exception("Temporary stay update failed: " . $stmtStay->error);
                    $stmtStay->close();
                }

                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Student updated successfully']);
            } catch (Exception $ex) {
                @$conn->rollback();
                echo json_encode(['status' => 'error', 'message' => $ex->getMessage()]);
            } finally {
                $conn->close();
            }
            break;


        case 'create_student':
            try {
                // Begin transaction
                $conn->begin_transaction();

                // --- Helper: sanitize simple inputs ---
                function strpost($k)
                {
                    return isset($_POST[$k]) && $_POST[$k] !== '' ? trim($_POST[$k]) : null;
                }

                // --- Map/normalize a few values to match enums in DB ---
                $admission_raw = strtolower((string) strpost('admission_type'));
                if (strpos($admission_raw, 'manage') !== false)
                    $admission = 'Management';
                else
                    $admission = 'Counseling'; // fallback to Counseling



                // Collect student fields (leave room_id NULL initially)
                $register_no = strpost('register_no');

                // Check if student with this roll_number already exists
                $checkStmt = $conn->prepare("SELECT student_id, status FROM students WHERE roll_number = ?");
                if (!$checkStmt)
                    throw new Exception("Prepare check student failed: " . $conn->error);
                $checkStmt->bind_param("s", $register_no);
                if (!$checkStmt->execute())
                    throw new Exception("Execute check student failed: " . $checkStmt->error);
                $checkStmt->bind_result($existing_student_id, $existing_status);
                $student_exists = $checkStmt->fetch();
                $checkStmt->close();

                // If student exists and is re-registering (vacated and returning)
                if ($student_exists) {
                    // Rollback the transaction started earlier
                    $conn->rollback();

                    // Start a new transaction for the update operation
                    $conn->begin_transaction();

                    // Collect student fields for update
                    $student_name = strpost('student_name');
                    $date_of_join = strpost('date_of_join');
                    $date_of_birth = strpost('dob') ?? strpost('date_of_birth');
                    $email = strpost('email');
                    $fingerprint_id = strpost('fingerprint_id');
                    $language = strpost('language');
                    $student_mobile_no = strpost('student_no') ?? strpost('student_mobile_no');
                    $aadhaar = strpost('aadhaar');
                    $department = strpost('department');
                    $yos = strpost('year_of_study');
                    $academic_batch = strpost('academic_batch');

                    if ($yos == 'I') {
                        $year_of_study = 1;
                    } elseif ($yos == 'II') {
                        $year_of_study = 2;
                    } elseif ($yos == 'III') {
                        $year_of_study = 3;
                    } elseif ($yos == 'IV') {
                        $year_of_study = 4;
                    }

                    // Update existing student record with status = 1 (re-registration)
                    $updateStmt = $conn->prepare("
                        UPDATE students SET
                            name = ?,
                            date_of_join = ?,
                            date_of_birth = ?,
                            admission_type = ?,
                            email = ?,
                            fingerprint_id = ?,
                            language = ?,
                            student_mobile_no = ?,
                            aadhaar = ?,
                            department = ?,
                            year_of_study = ?,
                            academic_batch = ?,
                            status = 1
                        WHERE student_id = ?
                    ");

                    if (!$updateStmt)
                        throw new Exception("Prepare update student failed: " . $conn->error);

                    $updateStmt->bind_param(
                        "sssssssssssii",
                        $student_name,
                        $date_of_join,
                        $date_of_birth,
                        $admission,
                        $email,
                        $fingerprint_id,
                        $language,
                        $student_mobile_no,
                        $aadhaar,
                        $department,
                        $year_of_study,
                        $academic_batch,
                        $existing_student_id
                    );

                    if (!$updateStmt->execute())
                        throw new Exception("Update student failed: " . $updateStmt->error);
                    $updateStmt->close();

                    // Handle room assignment if provided
                    $assign_room_id = is_numeric(strpost('room')) ? (int) strpost('room') : (is_numeric(strpost('room_id')) ? (int) strpost('room_id') : null);
                    if ($assign_room_id !== null) {
                        // Remove student from old room if they were in one
                        $oldRoomStmt = $conn->prepare("SELECT room_id FROM students WHERE student_id = ?");
                        $oldRoomStmt->bind_param("i", $existing_student_id);
                        $oldRoomStmt->execute();
                        $oldRoomStmt->bind_result($old_room_id);
                        if ($oldRoomStmt->fetch() && $old_room_id) {
                            // Remove from old room
                            $removeStmt = $conn->prepare("DELETE FROM room_students WHERE student_id = ? AND room_id = ?");
                            $removeStmt->bind_param("ii", $existing_student_id, $old_room_id);
                            $removeStmt->execute();
                            $removeStmt->close();

                            // Decrement old room occupied count
                            $decStmt = $conn->prepare("UPDATE rooms SET occupied = occupied - 1 WHERE room_id = ? AND occupied > 0");
                            $decStmt->bind_param("i", $old_room_id);
                            $decStmt->execute();
                            $decStmt->close();
                        }
                        $oldRoomStmt->close();

                        // Assign to new room
                        $stmtRoom = $conn->prepare("SELECT capacity, occupied FROM rooms WHERE room_id = ? FOR UPDATE");
                        if (!$stmtRoom)
                            throw new Exception("Prepare select room failed: " . $conn->error);
                        $stmtRoom->bind_param("i", $assign_room_id);
                        if (!$stmtRoom->execute())
                            throw new Exception("Execute select room failed: " . $stmtRoom->error);
                        $stmtRoom->bind_result($capacity, $occupied);
                        if (!$stmtRoom->fetch()) {
                            $stmtRoom->close();
                            throw new Exception("Room not found with id: $assign_room_id");
                        }
                        $stmtRoom->close();

                        if ($occupied >= $capacity) {
                            throw new Exception("Room is full (occupied: $occupied, capacity: $capacity)");
                        }

                        // Insert into room_assignments
                        $stmtAssign = $conn->prepare("INSERT INTO room_students (room_id, student_id) VALUES (?, ?)");
                        if (!$stmtAssign)
                            throw new Exception("Prepare insert room_assignments failed: " . $conn->error);
                        $stmtAssign->bind_param("ii", $assign_room_id, $existing_student_id);
                        if (!$stmtAssign->execute())
                            throw new Exception("Insert room_assignments failed: " . $stmtAssign->error);
                        $stmtAssign->close();

                        // Update rooms occupied ++
                        $stmtUpdateRoom = $conn->prepare("UPDATE rooms SET occupied = occupied + 1 WHERE room_id = ?");
                        if (!$stmtUpdateRoom)
                            throw new Exception("Prepare update room occupied failed: " . $conn->error);
                        $stmtUpdateRoom->bind_param("i", $assign_room_id);
                        if (!$stmtUpdateRoom->execute())
                            throw new Exception("Update rooms occupied failed: " . $stmtUpdateRoom->error);
                        $stmtUpdateRoom->close();

                        // Update students.room_id
                        $stmtUpdateStudentRoom = $conn->prepare("UPDATE students SET room_id = ? WHERE student_id = ?");
                        if (!$stmtUpdateStudentRoom)
                            throw new Exception("Prepare update student room_id failed: " . $conn->error);
                        $stmtUpdateStudentRoom->bind_param("ii", $assign_room_id, $existing_student_id);
                        if (!$stmtUpdateStudentRoom->execute())
                            throw new Exception("Update student's room_id failed: " . $stmtUpdateStudentRoom->error);
                        $stmtUpdateStudentRoom->close();
                    }

                    $conn->commit();
                    echo json_encode(['status' => 'success', 'message' => 'Student re-registered successfully!', 'student_id' => $existing_student_id]);
                    break;
                }

                $student_name = strpost('student_name');
                $date_of_join = strpost('date_of_join');
                $date_of_birth = strpost('dob') ?? strpost('date_of_birth'); // accept either key
                $email = strpost('email');
                $fingerprint_id = strpost('fingerprint_id');
                $language = strpost('language');
                $student_mobile_no = strpost('student_no') ?? strpost('student_mobile_no');
                $aadhaar = strpost('aadhaar');
                $department = strpost('department');
                $yos = strpost('year_of_study');
                $academic_batch = strpost('academic_batch');
                $stay_type = strpost('stay_type');
                $father_phone = strpost('father_no');
                $mother_phone = strpost('mother_no');
                $g_phone = strpost('guardian_no');
                $father_name = strpost('father_name');
                $mother_name = strpost('mother_name');
                $gender = strpost('gender');
                $g_name = strpost('guardian_name') ?? strpost('g_name');
                $status = '1';

                if ($yos == 'I') {
                    $year_of_study = 1;
                }
                if ($yos == 'II') {
                    $year_of_study = 2;
                }
                if ($yos == 'III') {
                    $year_of_study = 3;
                }
                if ($yos == 'IV') {
                    $year_of_study = 4;
                }
                // 1) Insert into students (room_id null)
                $stmt = $conn->prepare("
        INSERT INTO students (
            roll_number, name, date_of_join, date_of_birth, admission_type, email,
            fingerprint_id, gender, language, student_mobile_no, aadhaar, department,
            year_of_study, academic_batch, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
                if (!$stmt)
                    throw new Exception("Prepare students insert failed: " . $conn->error);

                $stmt->bind_param(
                    "ssssssssssssiss",
                    $register_no,
                    $student_name,
                    $date_of_join,
                    $date_of_birth,
                    $admission,
                    $email,
                    $fingerprint_id,
                    $gender,
                    $language,
                    $student_mobile_no,
                    $aadhaar,
                    $department,
                    $year_of_study,
                    $academic_batch,
                    $status
                );


                if (!$stmt->execute())
                    throw new Exception("Insert student failed: " . $stmt->error);
                $student_id = $conn->insert_id;
                $stmt->close();

                $insertUserSQL = "
    INSERT INTO users (username, password, role)
    VALUES (?, ?, 'student')
";

                $stmt = $conn->prepare($insertUserSQL);
                $stmt->bind_param("ss", $register_no, $register_no);

                if ($stmt->execute()) {
                    // Step 2: Get the inserted user_id
                    $user_id = $conn->insert_id;

                    // Step 3: Update students table with this user_id
                    $updateStudentSQL = "
        UPDATE students 
        SET user_id = ? 
        WHERE student_id = ?
    ";

                    $stmt2 = $conn->prepare($updateStudentSQL);
                    if (!$stmt2)
                        throw new Exception("Prepare updateStudentSQL failed: " . $conn->error);
                    $stmt2->bind_param("ii", $user_id, $student_id);
                    if (!$stmt2->execute())
                        throw new Exception("Execute updateStudentSQL failed: " . $stmt2->error);
                    $stmt2->close();
                } else {
                    echo json_encode(["status" => "error", "message" => "Failed to create user account"]);
                }



                // 2) Insert guardians (father, mother, guardian) and set approval_type
                // We're expecting POST fields: father_name, mother_name, guardian_name and father_no, mother_no, guardian_no
                $approval_primary = strtolower((string) strpost('approval_no'));    // e.g. 'Father' or 'Mother' or 'Guardian'
                $approval_alt = strtolower((string) strpost('alt_approval_no'));

                $guardian_insert = $conn->prepare("
        INSERT INTO guardians (student_id, relation, name, phone, approval_type)
        VALUES (?, ?, ?, ?, ?)
    ");
                if (!$guardian_insert)
                    throw new Exception("Prepare guardian insert failed: " . $conn->error);

                // helper to determine approval_type
                $decideApproval = function ($relationLabel, $approval_primary, $approval_alt) {
                    $relationLabel = strtolower($relationLabel);
                    if ($approval_primary === $relationLabel)
                        return 'primary';
                    if ($approval_alt === $relationLabel)
                        return 'alternate';
                    return 'none';
                };

                // Father
                $relation = 'father';
                $ap = $decideApproval('father', $approval_primary, $approval_alt);
                $guardian_insert->bind_param("issss", $student_id, $relation, $father_name, $father_phone, $ap);
                if (!$guardian_insert->execute())
                    throw new Exception("Insert father guardian failed: " . $guardian_insert->error);

                // Mother
                $relation = 'mother';
                $ap = $decideApproval('mother', $approval_primary, $approval_alt);
                $guardian_insert->bind_param("issss", $student_id, $relation, $mother_name, $mother_phone, $ap);
                if (!$guardian_insert->execute())
                    throw new Exception("Insert mother guardian failed: " . $guardian_insert->error);

                // Guardian
                $relation = 'guardian';
                $ap = $decideApproval('guardian', $approval_primary, $approval_alt);
                $guardian_insert->bind_param("issss", $student_id, $relation, $g_name, $g_phone, $ap);
                if (!$guardian_insert->execute())
                    throw new Exception("Insert guardian failed: " . $guardian_insert->error);


                $guardian_insert->close();

                // 2b) Temporary stay insertion (if any)
                $stay_type = strtolower((string) strpost('stay_type'));
                if ($stay_type === 'temporary') {
                    $from_date = strpost('from_date');
                    $to_date = strpost('to_date');
                    $stmtStay = $conn->prepare("INSERT INTO temporary_stay (student_id, from_date, to_date) VALUES (?, ?, ?)");
                    if (!$stmtStay)
                        throw new Exception("Prepare temporary_stay failed: " . $conn->error);
                    $stmtStay->bind_param("iss", $student_id, $from_date, $to_date);
                    if (!$stmtStay->execute())
                        throw new Exception("Insert temporary_stay failed: " . $stmtStay->error);
                    $stmtStay->close();
                }

                // 3) Room assignment: if a room_id was provided in POST (only then assign)
                $assign_room_id = is_numeric(strpost('room')) ? (int) strpost('room') : (is_numeric(strpost('room_id')) ? (int) strpost('room_id') : null);
                if ($assign_room_id !== null) {
                    // Check room exists and capacity
                    $stmtRoom = $conn->prepare("SELECT capacity, occupied FROM rooms WHERE room_id = ? FOR UPDATE");
                    if (!$stmtRoom)
                        throw new Exception("Prepare select room failed: " . $conn->error);
                    $stmtRoom->bind_param("i", $assign_room_id);
                    if (!$stmtRoom->execute())
                        throw new Exception("Execute select room failed: " . $stmtRoom->error);
                    $stmtRoom->bind_result($capacity, $occupied);
                    if (!$stmtRoom->fetch()) {
                        $stmtRoom->close();
                        throw new Exception("Room not found with id: $assign_room_id");
                    }
                    $stmtRoom->close();

                    if ($occupied >= $capacity) {
                        throw new Exception("Room is full (occupied: $occupied, capacity: $capacity)");
                    }

                    // Insert into room_assignments
                    $stmtAssign = $conn->prepare("INSERT INTO room_students (room_id, student_id) VALUES (?, ?)");
                    if (!$stmtAssign)
                        throw new Exception("Prepare insert room_assignments failed: " . $conn->error);
                    $stmtAssign->bind_param("ii", $assign_room_id, $student_id);
                    if (!$stmtAssign->execute())
                        throw new Exception("Insert room_assignments failed: " . $stmtAssign->error);
                    $stmtAssign->close();

                    // Update rooms occupied ++
                    $stmtUpdateRoom = $conn->prepare("UPDATE rooms SET occupied = occupied + 1 WHERE room_id = ?");
                    if (!$stmtUpdateRoom)
                        throw new Exception("Prepare update room occupied failed: " . $conn->error);
                    $stmtUpdateRoom->bind_param("i", $assign_room_id);
                    if (!$stmtUpdateRoom->execute())
                        throw new Exception("Update rooms occupied failed: " . $stmtUpdateRoom->error);
                    $stmtUpdateRoom->close();

                    // Update students.room_id
                    $stmtUpdateStudentRoom = $conn->prepare("UPDATE students SET room_id = ? WHERE student_id = ?");
                    if (!$stmtUpdateStudentRoom)
                        throw new Exception("Prepare update student room_id failed: " . $conn->error);
                    $stmtUpdateStudentRoom->bind_param("ii", $assign_room_id, $student_id);
                    if (!$stmtUpdateStudentRoom->execute())
                        throw new Exception("Update student's room_id failed: " . $stmtUpdateStudentRoom->error);
                    $stmtUpdateStudentRoom->close();
                }

                // If everything OK, commit
                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Student created', 'student_id' => $student_id]);
            } catch (Exception $ex) {
                // rollback and return error
                if ($conn->errno || $conn->in_transaction) {
                    $conn->rollback();
                } else {
                    // ensure rollback even if flags show not in transaction
                    @$conn->rollback();
                }
                $msg = $ex->getMessage();
                echo json_encode(['status' => 'error', 'message' => $msg]);
            } finally {
                $conn->close();
            }
            break;

        case 'list_students':
            $scopeGender = get_current_admin_scope_gender();
            $query = "
                SELECT 
                    s.student_id,
                    s.roll_number,
                    s.name,
                    s.fingerprint_id,
                    r.room_number,
                    h.hostel_name,
                    g.guardian_id AS approval_no,
                    g.phone
                FROM students s
                LEFT JOIN rooms r ON s.room_id = r.room_id
                LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                LEFT JOIN guardians g ON s.student_id = g.student_id AND g.approval_type = 'primary'
                WHERE s.status = '1'
                ORDER BY s.student_id ASC
            ";

            if ($scopeGender !== null) {
                $query = str_replace(
                    "WHERE s.status = '1'",
                    "WHERE s.status = '1' AND s.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'",
                    $query
                );
            }


            $result = $conn->query($query);
            $data = [];

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = [
                        'student_id' => $row['student_id'],
                        'roll_number' => $row['roll_number'],
                        'name' => $row['name'],
                        'hostel_name' => $row['hostel_name'] ?? '',
                        'room_number' => $row['room_number'] ?? '',
                        'approval_no' => $row['phone'] ?? '',
                        'fingerprint_id' => $row['fingerprint_id'] ?? ''
                    ];
                }
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
            break;

        case 'deactivate_student':

            $status = '0';
            $id = intval($_POST['student_id']);
            $stmt = $conn->prepare("UPDATE students SET status = ? WHERE student_id = ?");
            $stmt->bind_param("si", $status, $id);
            if ($stmt->execute()) {
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to update status."]);
            }
            break;

        case 'get_update_student':
            if (isset($_POST['action']) && $_POST['action'] == 'get_update_student') {
                $student_id = intval($_POST['student_id'] ?? 0);

                if ($student_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
                    exit;
                }

                // --- Get Student Basic Info ---
                $query = "
                    SELECT 
                        s.*,
                        r.*,
                        h.hostel_id,
                        h.hostel_name
                    FROM students s
                    LEFT JOIN rooms r ON s.room_id = r.room_id
                    LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                    WHERE s.student_id = ?
                    LIMIT 1;

                ";

                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $student_result = $stmt->get_result();

                if ($student_result->num_rows === 0) {
                    echo json_encode(['success' => false, 'message' => 'Student not found']);
                    exit;
                }

                $student = $student_result->fetch_assoc();

                // --- Get Guardians (father, mother, guardian) ---
                // --- Get Guardians (father, mother, guardian) ---
                $guardians = [
                    'father' => null,
                    'mother' => null,
                    'guardian' => null
                ];

                $gquery = "
                    SELECT 
                        LOWER(relation) AS relation,
                        COALESCE(name, '') AS name,
                        COALESCE(phone, '') AS phone,
                        COALESCE(photo_path, '') AS photo_path,
                        COALESCE(approval_type, 'none') AS approval_type
                    FROM guardians 
                    WHERE student_id = ?
                ";
                $gstmt = $conn->prepare($gquery);
                $gstmt->bind_param("i", $student_id);
                $gstmt->execute();
                $gresult = $gstmt->get_result();

                while ($g = $gresult->fetch_assoc()) {
                    $rel = strtolower(trim($g['relation']));
                    if (array_key_exists($rel, $guardians)) {
                        $guardians[$rel] = $g;
                    }
                }


                // --- Get Temporary Stay Info (if any) ---
                $stayquery = "
                    SELECT stay_id, from_date, to_date 
                    FROM temporary_stay 
                    WHERE student_id = ? 
                    ORDER BY stay_id DESC 
                    LIMIT 1
                ";
                $sstmt = $conn->prepare($stayquery);
                $sstmt->bind_param("i", $student_id);
                $sstmt->execute();
                $stayresult = $sstmt->get_result();
                $stay = $stayresult->num_rows > 0 ? $stayresult->fetch_assoc() : null;

                // --- Return JSON Response ---
                echo json_encode([
                    'success' => true,
                    'student' => $student,
                    'guardians' => $guardians,
                    'stay' => $stay
                ]);
                exit;
            }

        case 'get_student':
            if (isset($_POST['action']) && $_POST['action'] == 'get_student') {
                $student_id = intval($_POST['student_id'] ?? 0);

                if ($student_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
                    exit;
                }

                // --- Get Student Basic Info ---
                $query = "
                    SELECT 
                        s.student_id,
                        s.roll_number,
                        s.name,
                        s.email,
                        s.department,
                        s.year_of_study,
                        s.academic_batch,
                        s.fingerprint_id,
                        s.gender,
                        s.admission_type,
                        s.date_of_birth,
                        s.date_of_join,
                        s.language,
                        s.student_mobile_no,
                        s.aadhaar,
                        s.status,
                        r.room_number,
                        h.hostel_name
                    FROM students s
                    LEFT JOIN rooms r ON s.room_id = r.room_id
                    LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                    WHERE s.student_id = ?
                    LIMIT 1
                ";

                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $student_result = $stmt->get_result();

                if ($student_result->num_rows === 0) {
                    echo json_encode(['success' => false, 'message' => 'Student not found']);
                    exit;
                }

                $student = $student_result->fetch_assoc();

                // --- Get Guardians (father, mother, guardian) ---
                // --- Get Guardians (father, mother, guardian) ---
                $guardians = [
                    'father' => null,
                    'mother' => null,
                    'guardian' => null
                ];

                $gquery = "
                    SELECT 
                        LOWER(relation) AS relation,
                        COALESCE(name, '') AS name,
                        COALESCE(phone, '') AS phone,
                        COALESCE(photo_path, '') AS photo_path,
                        COALESCE(approval_type, 'none') AS approval_type
                    FROM guardians 
                    WHERE student_id = ?
                ";
                $gstmt = $conn->prepare($gquery);
                $gstmt->bind_param("i", $student_id);
                $gstmt->execute();
                $gresult = $gstmt->get_result();

                while ($g = $gresult->fetch_assoc()) {
                    $rel = strtolower(trim($g['relation']));
                    if (array_key_exists($rel, $guardians)) {
                        $guardians[$rel] = $g;
                    }
                }


                // --- Get Temporary Stay Info (if any) ---
                $stayquery = "
                    SELECT stay_id, from_date, to_date 
                    FROM temporary_stay 
                    WHERE student_id = ? 
                    ORDER BY stay_id DESC 
                    LIMIT 1
                ";
                $sstmt = $conn->prepare($stayquery);
                $sstmt->bind_param("i", $student_id);
                $sstmt->execute();
                $stayresult = $sstmt->get_result();
                $stay = $stayresult->num_rows > 0 ? $stayresult->fetch_assoc() : null;

                // --- Return JSON Response ---
                echo json_encode([
                    'success' => true,
                    'student' => $student,
                    'guardians' => $guardians,
                    'stay' => $stay
                ]);
                exit;
            }

            // ===== Mess Admin APIs (moved from adminmessapi.php) =====

        case 'get_dashboard_stats':
            require_mess_access_role();
            $messScopeGender = get_mess_scope_gender();
            // Get dashboard statistics
            $stats = [];

            $today = date('Y-m-d');
            $sql = "SELECT COUNT(*) as cnt FROM mess_menu WHERE date = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $today);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['todays_menu_items'] = (int) $row['cnt'];
            $stmt->close();

            $sql = "SELECT COUNT(*) as cnt FROM specialtokenenable WHERE status = 'active'";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $stats['active_special_tokens'] = (int) $row['cnt'];

            $sql = "SELECT COALESCE(SUM(mt.special_fee), 0) as revenue FROM mess_tokens mt LEFT JOIN students s ON mt.roll_number = s.roll_number WHERE DATE(mt.token_date) = ? AND mt.token_type = 'Special'";
            if ($messScopeGender !== null) {
                $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $messScopeGender) . "'";
            }
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $today);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['todays_revenue'] = (float) $row['revenue'];
            $stmt->close();

            $currentMonth = date('Y-m');
            $sql = "SELECT COUNT(*) as cnt FROM mess_tokens mt LEFT JOIN students s ON mt.roll_number = s.roll_number WHERE DATE_FORMAT(mt.token_date, '%Y-%m') = ? AND mt.token_type = 'Special'";
            if ($messScopeGender !== null) {
                $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $messScopeGender) . "'";
            }
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $currentMonth);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['month_special_tokens'] = (int) $row['cnt'];
            $stmt->close();

            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'get_mess_menu':
            // Get mess menu for a specific date
            $date = $_POST['date'] ?? date('Y-m-d');

            $sql = "SELECT menu_id, date, meal_type, items, IFNULL(category, 'Regular') as category, IFNULL(fee, 0) as fee FROM mess_menu WHERE date = ? ORDER BY FIELD(meal_type, 'Breakfast', 'Lunch', 'Snacks', 'Dinner')";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];

            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    'menu_id' => (int) $row['menu_id'],
                    'date' => $row['date'],
                    'meal_type' => $row['meal_type'],
                    'items' => $row['items'],
                    'category' => $row['category'],
                    'fee' => number_format((float) $row['fee'], 2, '.', '')
                ];
            }

            $stmt->close();
            echo json_encode(['success' => true, 'data' => $data, 'total_items' => count($data)]);
            break;

        case 'get_special_tokens':
            // Get all special tokens
            $sql = "SELECT st.menu_id, IFNULL(st.from_date, '') as from_date, IFNULL(st.from_time, '') as from_time, IFNULL(st.to_date, '') as to_date, IFNULL(st.to_time, '') as to_time, st.token_date, st.meal_type, st.menu_items, IFNULL(st.fee, 0) as fee, IFNULL(st.status, 'active') as status FROM specialtokenenable st ORDER BY st.token_date DESC, st.from_time ASC";

            $result = $conn->query($sql);
            $data = [];

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = [
                        'menu_id' => (int) $row['menu_id'],
                        'from_date' => $row['from_date'],
                        'from_time' => $row['from_time'],
                        'to_date' => $row['to_date'],
                        'to_time' => $row['to_time'],
                        'token_date' => $row['token_date'],
                        'meal_type' => $row['meal_type'],
                        'menu_items' => $row['menu_items'],
                        'fee' => number_format((float) $row['fee'], 2, '.', ''),
                        'status' => $row['status']
                    ];
                }
            }

            echo json_encode(['success' => true, 'data' => $data, 'total_items' => count($data)]);
            break;

        case 'get_token_requests':
            require_mess_access_role();
            $messScopeGender = get_mess_scope_gender();
            // Get token requests with optional filters
            $filterMonth = $_POST['filter_month'] ?? '';
            $filterDate = $_POST['filter_date'] ?? '';
            $filterMealType = $_POST['filter_meal_type'] ?? '';
            $filterItem = $_POST['filter_item'] ?? '';

            // Build base query
            $sql = "SELECT 
                            mt.token_id, 
                            mt.roll_number, 
                            COALESCE(s.name, 'Unknown') as student_name, 
                            mt.meal_type, 
                            COALESCE(st.menu_items, mt.menu) as menu_items, 
                            COALESCE(mt.special_fee, 0) as fee, 
                            mt.token_date, 
                            mt.status,
                            DATE_FORMAT(mt.created_at, '%Y-%m-%d %H:%i:%s') as requested_at 
                        FROM mess_tokens mt 
                        LEFT JOIN students s ON mt.roll_number = s.roll_number 
                        LEFT JOIN specialtokenenable st ON mt.menu_id = st.menu_id 
                        WHERE mt.token_type = 'Special'";

            if ($messScopeGender !== null) {
                $sql .= " AND s.gender = ?";
                $params[] = $messScopeGender;
                $types .= 's';
            }

            // Add filters
            $params = $params ?? [];
            $types = $types ?? '';

            switch (true) {
                case !empty($filterDate):
                    $sql .= " AND mt.token_date = ?";
                    $params[] = $filterDate;
                    $types .= 's';
                    break;
                case !empty($filterMonth):
                    $sql .= " AND DATE_FORMAT(mt.token_date, '%Y-%m') = ?";
                    $params[] = $filterMonth;
                    $types .= 's';
                    break;
            }

            if (!empty($filterMealType)) {
                $sql .= " AND mt.meal_type = ?";
                $params[] = $filterMealType;
                $types .= 's';
            }

            if (!empty($filterItem)) {
                $sql .= " AND (st.menu_items LIKE ? OR mt.menu LIKE ?)";
                $searchTerm = '%' . $filterItem . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= 'ss';
            }

            $sql .= " ORDER BY mt.token_date DESC, mt.created_at DESC LIMIT 1000";

            // Prepare and execute
            switch (!empty($params)) {
                case true:
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    break;
                case false:
                    $result = $conn->query($sql);
                    break;
            }

            $data = [];

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Fallback for student names
                    $studentName = trim($row['student_name']);
                    switch (true) {
                        case $studentName === '' || $studentName === 'Unknown' || is_null($studentName):
                            $studentName = 'Unknown';
                            break;
                    }

                    // For menu_items, prefer specialtokenenable, fallback to mess_tokens.menu
                    $menuItems = $row['menu_items'];
                    switch (true) {
                        case trim($menuItems) === '' || strtolower($menuItems) === 'n/a' || is_null($menuItems):
                            $menuItems = $row['menu'];
                            break;
                    }

                    $data[] = [
                        'token_id' => (int) $row['token_id'],
                        'roll_number' => $row['roll_number'],
                        'student_name' => $studentName,
                        'meal_type' => $row['meal_type'],
                        'menu_items' => $menuItems,
                        'fee' => number_format((float) $row['fee'], 2, '.', ''),
                        'token_date' => $row['token_date'],
                        'status' => $row['status'],
                        'requested_at' => $row['requested_at']
                    ];
                }
            }

            switch (!empty($params)) {
                case true:
                    $stmt->close();
                    break;
            }

            echo json_encode(['success' => true, 'data' => $data, 'total_items' => count($data)]);
            break;

        case 'activate_token_request':
            require_admin_role();
            $tokenId = $_POST['token_id'] ?? null;
            
            if (!$tokenId) {
                echo json_encode(['success' => false, 'message' => 'Token ID is required']);
                break;
            }
            
            $sql = "UPDATE mess_tokens SET status = 'Used', used_at = NOW() WHERE token_id = ? AND status = 'Generated'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $tokenId);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Token approved successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Token is not in Generated status or does not exist']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update token status']);
            }
            $stmt->close();
            break;

        case 'deactivate_token_request':
            require_admin_role();
            $tokenId = $_POST['token_id'] ?? null;
            
            if (!$tokenId) {
                echo json_encode(['success' => false, 'message' => 'Token ID is required']);
                break;
            }
            
            $sql = "UPDATE mess_tokens SET status = 'Cancelled' WHERE token_id = ? AND status = 'Generated'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $tokenId);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Token request cancelled successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Token is not in Generated status or does not exist']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to cancel token request']);
            }
            $stmt->close();
            break;

        case 'get_monthly_revenue':
            require_mess_access_role();
            $messScopeGender = get_mess_scope_gender();
            // Get monthly revenue
            $month = $_POST['month'] ?? date('Y-m');

            $sql = "SELECT mt.token_date as date, COUNT(mt.token_id) as tokens_count, COALESCE(SUM(mt.special_fee), 0) as revenue FROM mess_tokens mt LEFT JOIN students s ON mt.roll_number = s.roll_number WHERE mt.token_type = 'Special' AND DATE_FORMAT(mt.token_date, '%Y-%m') = ?";
            if ($messScopeGender !== null) {
                $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $messScopeGender) . "'";
            }
            $sql .= " GROUP BY mt.token_date ORDER BY mt.token_date DESC";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $month);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            $total = 0;

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $revenue = (float) $row['revenue'];
                    $data[] = [
                        'date' => $row['date'],
                        'tokens_count' => (int) $row['tokens_count'],
                        'revenue' => number_format($revenue, 2, '.', '')
                    ];
                    $total += $revenue;
                }
            }

            $stmt->close();

            echo json_encode(['success' => true, 'data' => $data, 'total' => number_format($total, 2, '.', ''), 'month' => $month, 'total_items' => count($data)]);
            break;

        case 'get_student_consumption':
            require_mess_access_role();
            $messScopeGender = get_mess_scope_gender();
            // Get student consumption data
            $month = $_POST['month'] ?? date('Y-m');

            $sql = "SELECT mt.roll_number, COALESCE(s.name, 'N/A') as student_name, COUNT(mt.token_id) as tokens_count, COALESCE(SUM(mt.special_fee), 0) as total_spent FROM mess_tokens mt LEFT JOIN students s ON mt.roll_number = s.roll_number WHERE mt.token_type = 'Special' AND DATE_FORMAT(mt.token_date, '%Y-%m') = ?";
            if ($messScopeGender !== null) {
                $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $messScopeGender) . "'";
            }
            $sql .= " GROUP BY mt.roll_number, s.name ORDER BY total_spent DESC LIMIT 1000";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $month);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = [
                        'roll_number' => $row['roll_number'],
                        'student_name' => $row['student_name'] != 'N/A' ? $row['student_name'] : 'Unknown',
                        'tokens_count' => (int) $row['tokens_count'],
                        'total_spent' => number_format((float) $row['total_spent'], 2, '.', '')
                    ];
                }
            }

            $stmt->close();

            echo json_encode(['success' => true, 'data' => $data, 'total_items' => count($data)]);
            break;

        // room operations 
        case 'add_room':
            $hostel_id = isset($_POST['hostel_id']) ? intval($_POST['hostel_id']) : 0;
            $block = isset($_POST['block']) ? esc_raw($_POST['block']) : null;
            $floor = isset($_POST['floor']) ? esc_raw($_POST['floor']) : null;
            $room_number = isset($_POST['room_number']) ? esc_raw($_POST['room_number']) : '';
            $room_type = isset($_POST['room_type']) ? esc_raw($_POST['room_type']) : 'Non-AC';
            $capacity = isset($_POST['capacity']) ? intval($_POST['capacity']) : 3;
            $occupied = isset($_POST['occupied']) ? intval($_POST['occupied']) : 0;
            $status = isset($_POST['status']) ? esc_raw($_POST['status']) : 'Available';

            // Debug: Log the received data
            error_log("Add room request: hostel_id=$hostel_id, room_number='$room_number'");

            if (!$hostel_id) {
                jsonResponse(['success' => false, 'error' => 'Missing hostel ID']);
            }

            if (empty($room_number)) {
                jsonResponse(['success' => false, 'error' => 'Missing room number']);
            }

            // Check if room already exists
            $checkStmt = $conn->prepare("SELECT room_id FROM rooms WHERE hostel_id = ? AND room_number = ?");
            $checkStmt->bind_param('is', $hostel_id, $room_number);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                $checkStmt->close();
                jsonResponse(['success' => false, 'error' => 'Room number already exists']);
            }
            $checkStmt->close();

            $sql = "INSERT INTO rooms (hostel_id, block, floor, room_number, room_type, capacity, occupied, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt)
                jsonResponse(['success' => false, 'error' => $conn->error]);

            $stmt->bind_param('issssiis', $hostel_id, $block, $floor, $room_number, $room_type, $capacity, $occupied, $status);
            $success = $stmt->execute();

            if ($success) {
                $new_id = $stmt->insert_id;
                $stmt->close();

                // Get hostel name
                $hStmt = $conn->prepare("SELECT hostel_name FROM hostels WHERE hostel_id = ?");
                $hStmt->bind_param('i', $hostel_id);
                $hStmt->execute();
                $hRes = $hStmt->get_result();
                $hostel = $hRes->fetch_assoc();
                $hStmt->close();

                jsonResponse([
                    'success' => true,
                    'data' => [
                        'room_id' => $new_id,
                        'block' => $block,
                        'floor' => $floor,
                        'room_number' => $room_number,
                        'room_type' => $room_type,
                        'capacity' => $capacity,
                        'occupied' => $occupied,
                        'status' => $status,
                        'hostel_name' => $hostel['hostel_name'] ?? ''
                    ]
                ]);
            } else {
                $error = $stmt->error;
                $stmt->close();
                jsonResponse(['success' => false, 'error' => $error]);
            }
            break;

        // ---------- Update Room ----------
        case 'update_room':
            $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
            $hostel_id = isset($_POST['hostel_id']) ? intval($_POST['hostel_id']) : 0;
            $block = isset($_POST['block']) ? esc_raw($_POST['block']) : null;
            $floor = isset($_POST['floor']) ? esc_raw($_POST['floor']) : null;
            $room_number = isset($_POST['room_number']) ? esc_raw($_POST['room_number']) : '';
            $room_type = isset($_POST['room_type']) ? esc_raw($_POST['room_type']) : 'Non-AC';
            $capacity = isset($_POST['capacity']) ? intval($_POST['capacity']) : 3;
            $occupied = isset($_POST['occupied']) ? intval($_POST['occupied']) : 0;
            $status = isset($_POST['status']) ? esc_raw($_POST['status']) : 'Available';

            // Debug: Log the received data
            error_log("Update room request: room_id=$room_id, hostel_id=$hostel_id, room_number='$room_number'");

            if (!$room_id) {
                jsonResponse(['success' => false, 'error' => 'Missing room ID']);
            }

            if (!$hostel_id) {
                jsonResponse(['success' => false, 'error' => 'Missing hostel ID']);
            }

            if (empty($room_number)) {
                jsonResponse(['success' => false, 'error' => 'Missing room number']);
            }

            // Check if room number conflicts with another room
            $checkStmt = $conn->prepare("SELECT room_id FROM rooms WHERE hostel_id = ? AND room_number = ? AND room_id != ?");
            $checkStmt->bind_param('isi', $hostel_id, $room_number, $room_id);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                $checkStmt->close();
                jsonResponse(['success' => false, 'error' => 'Room number already exists']);
            }
            $checkStmt->close();

            $sql = "UPDATE rooms SET hostel_id = ?, block = ?, floor = ?, room_number = ?, room_type = ?, capacity = ?, occupied = ?, status = ? WHERE room_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt)
                jsonResponse(['success' => false, 'error' => $conn->error]);

            $stmt->bind_param('issssiiss', $hostel_id, $block, $floor, $room_number, $room_type, $capacity, $occupied, $status, $room_id);
            $success = $stmt->execute();
            $stmt->close();

            if ($success) {
                // Get hostel name
                $hStmt = $conn->prepare("SELECT hostel_name FROM hostels WHERE hostel_id = ?");
                $hStmt->bind_param('i', $hostel_id);
                $hStmt->execute();
                $hRes = $hStmt->get_result();
                $hostel = $hRes->fetch_assoc();
                $hStmt->close();

                jsonResponse([
                    'success' => true,
                    'data' => [
                        'room_id' => $room_id,
                        'block' => $block,
                        'floor' => $floor,
                        'room_number' => $room_number,
                        'room_type' => $room_type,
                        'capacity' => $capacity,
                        'occupied' => $occupied,
                        'status' => $status,
                        'hostel_name' => $hostel['hostel_name'] ?? ''
                    ]
                ]);
            } else {
                jsonResponse(['success' => false, 'error' => 'Update failed']);
            }
            break;

        // ---------- Delete Room ----------
        case 'delete_room':
            $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
            if (!$room_id)
                jsonResponse(['success' => false, 'error' => 'Invalid room id']);

            // Check if room has active students
            $checkStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM room_students WHERE room_id = ? AND is_active = 1");
            $checkStmt->bind_param('i', $room_id);
            $checkStmt->execute();
            $checkRes = $checkStmt->get_result();
            $checkRow = $checkRes->fetch_assoc();
            $checkStmt->close();

            if ($checkRow['cnt'] > 0) {
                jsonResponse(['success' => false, 'error' => 'Cannot delete room with active students. Please vacate students first.']);
            }

            // Delete from room_students (inactive records)
            $sql1 = "DELETE FROM room_students WHERE room_id = ?";
            $s1 = $conn->prepare($sql1);
            if (!$s1)
                jsonResponse(['success' => false, 'error' => $conn->error]);
            $s1->bind_param('i', $room_id);
            $s1->execute();
            $s1->close();

            // Delete room
            $sql2 = "DELETE FROM rooms WHERE room_id = ?";
            $s2 = $conn->prepare($sql2);
            if (!$s2)
                jsonResponse(['success' => false, 'error' => $conn->error]);
            $s2->bind_param('i', $room_id);
            $ok = $s2->execute();
            if ($ok) {
                $s2->close();
                jsonResponse(['success' => true]);
            } else {
                $err = $s2->error;
                $s2->close();
                jsonResponse(['success' => false, 'error' => $err]);
            }
            break;

        // ---------- Get students assigned to a room ----------
        case 'get_students':
            $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
            $out = [];
            if ($room_id) {
                $sql = "SELECT rs.*, s.student_id, s.name, s.roll_number, s.department, s.academic_batch
                        FROM room_students rs
                        LEFT JOIN students s ON rs.student_id = s.student_id
                        WHERE rs.room_id = ? AND rs.is_active = 1 AND (rs.vacated_at IS NULL OR rs.vacated_at = '0000-00-00 00:00:00')
                        ORDER BY rs.assigned_at ASC";
                $s = $conn->prepare($sql);
                if (!$s)
                    jsonResponse(['success' => false, 'error' => $conn->error]);
                $s->bind_param('i', $room_id);
                $s->execute();
                $res = $s->get_result();
                while ($r = $res->fetch_assoc())
                    $out[] = $r;
                $s->close();
            }
            jsonResponse(['success' => true, 'data' => $out]);
            break;

        // ---------- Get available students ----------
        case 'get_available_students':
            // Get the room ID to determine the hostel and its gender
            $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;

            // Get hostel gender for the room
            $hostel_gender = 'Male'; // Default
            if ($room_id) {
                $hostelQuery = "SELECT h.gender FROM rooms r JOIN hostels h ON r.hostel_id = h.hostel_id WHERE r.room_id = ?";
                $hostelStmt = $conn->prepare($hostelQuery);
                if ($hostelStmt) {
                    $hostelStmt->bind_param('i', $room_id);
                    $hostelStmt->execute();
                    $hostelRes = $hostelStmt->get_result();
                    if ($hostelRow = $hostelRes->fetch_assoc()) {
                        $hostel_gender = $hostelRow['gender'];
                    }
                    $hostelStmt->close();
                }
            }

            $out = [];
            // Improved query to correctly identify unassigned students
            // Only select students who are active (status = '1') and have never been assigned to a room
            $sql = "SELECT s.* FROM students s
                    WHERE s.status = '1' 
                    AND s.student_id NOT IN (SELECT DISTINCT rs.student_id FROM room_students rs WHERE rs.student_id IS NOT NULL)
                    AND s.gender = ?
                    ORDER BY s.name ASC";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('s', $hostel_gender);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc())
                    $out[] = $r;
                $stmt->close();
            } else {
                jsonResponse(['success' => false, 'error' => $conn->error]);
            }
            jsonResponse(['success' => true, 'data' => $out]);
            break;

        // ---------- Assign student to room - COMPLETELY FIXED ----------
        // room_backend.php

        case 'assign_student':
            $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;

            if (!$room_id || !$student_id) {
                jsonResponse(['success' => false, 'error' => 'Missing room or student ID']);
            }

            // Start transaction
            $conn->begin_transaction();

            try {
                // 1. Check if room exists and get details
                $roomQuery = "SELECT r.room_id, r.capacity, r.occupied, r.hostel_id, h.gender as hostel_gender, h.hostel_name
                             FROM rooms r
                             LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                             WHERE r.room_id = ?";
                $roomStmt = $conn->prepare($roomQuery);
                $roomStmt->bind_param('i', $room_id);
                $roomStmt->execute();
                $roomRes = $roomStmt->get_result();
                $roomData = $roomRes->fetch_assoc();
                $roomStmt->close();

                if (!$roomData) {
                    throw new Exception('Room not found in database.');
                }

                // 2. Check if room is full
                if (intval($roomData['occupied']) >= intval($roomData['capacity'])) {
                    throw new Exception('Room is already full.');
                }

                // 3. Check if student exists and get details
                $studentQuery = "SELECT student_id, name, roll_number, gender FROM students WHERE student_id = ?";
                $studentStmt = $conn->prepare($studentQuery);
                $studentStmt->bind_param('i', $student_id);
                $studentStmt->execute();
                $studentRes = $studentStmt->get_result();
                $studentData = $studentRes->fetch_assoc();
                $studentStmt->close();

                if (!$studentData) {
                    throw new Exception('Student not found in database.');
                }

                // 4. Check gender compatibility
                $hostelGender = $roomData['hostel_gender'];
                $studentGender = $studentData['gender'];

                if (
                    ($hostelGender == 'Male' && $studentGender != 'Male') ||
                    ($hostelGender == 'Female' && $studentGender != 'Female')
                ) {
                    throw new Exception('Gender mismatch: Cannot assign student to this hostel.');
                }

                $now = date('Y-m-d H:i:s');
                $hostel_id = $roomData['hostel_id'];

                // 5. Get room and student details for complete data
                $roomDetailsQuery = "SELECT r.room_number, r.hostel_id, h.hostel_name 
                                    FROM rooms r 
                                    LEFT JOIN hostels h ON r.hostel_id = h.hostel_id 
                                    WHERE r.room_id = ?";
                $roomDetailsStmt = $conn->prepare($roomDetailsQuery);
                $roomDetailsStmt->bind_param('i', $room_id);
                $roomDetailsStmt->execute();
                $roomDetailsResult = $roomDetailsStmt->get_result();
                $roomDetails = $roomDetailsResult->fetch_assoc();
                $roomDetailsStmt->close();

                // Get student roll number
                $studentRollQuery = "SELECT roll_number, name FROM students WHERE student_id = ?";
                $studentRollStmt = $conn->prepare($studentRollQuery);
                $studentRollStmt->bind_param('i', $student_id);
                $studentRollStmt->execute();
                $studentRollResult = $studentRollStmt->get_result();
                $studentRollData = $studentRollResult->fetch_assoc();
                $studentRollStmt->close();

                // 6. Insert into room_students with all required fields
                $insertQuery = "INSERT INTO room_students 
                                (room_id, student_id, room_number, roll_number, hostel_id, hostel_name, student_name, assigned_at, is_active) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param(
                    'iissssss',
                    $room_id,
                    $student_id,
                    $roomDetails['room_number'],
                    $studentRollData['roll_number'],
                    $roomDetails['hostel_id'],
                    $roomDetails['hostel_name'],
                    $studentRollData['name'],
                    $now
                );

                if (!$insertStmt->execute()) {
                    throw new Exception('room_students INSERT failed: ' . $insertStmt->error);
                }
                $insertStmt->close();

                // 7. Update room occupied count
                $updateRoomQuery = "UPDATE rooms SET occupied = occupied + 1 WHERE room_id = ?";
                $updateRoomStmt = $conn->prepare($updateRoomQuery);
                $updateRoomStmt->bind_param('i', $room_id);
                if (!$updateRoomStmt->execute()) {
                    throw new Exception('rooms UPDATE failed: ' . $updateRoomStmt->error);
                }
                $updateRoomStmt->close();

                // 8. Update student table with room_id and hostel_id (Based on your SQL schema)
                // NOTE: Your SQL file shows a Foreign Key for students.room_id.
                $updateStudentQuery = "UPDATE students SET room_id = ?, hostel_id = ? WHERE student_id = ?";
                $updateStudentStmt = $conn->prepare($updateStudentQuery);
                $updateStudentStmt->bind_param('iii', $room_id, $hostel_id, $student_id);
                if (!$updateStudentStmt->execute()) {
                    throw new Exception('students UPDATE failed: ' . $updateStudentStmt->error);
                }
                $updateStudentStmt->close();

                // Reset vacated flag if column exists
                $checkColumnQuery = "SHOW COLUMNS FROM students LIKE 'vacated'";
                $checkResult = $conn->query($checkColumnQuery);
                if ($checkResult && $checkResult->num_rows > 0) {
                    // Column exists, reset it to 0
                    $resetVacatedQuery = "UPDATE students SET vacated = 0 WHERE student_id = ?";
                    $resetVacatedStmt = $conn->prepare($resetVacatedQuery);
                    $resetVacatedStmt->bind_param('i', $student_id);
                    $resetVacatedStmt->execute();
                    $resetVacatedStmt->close();
                }

                // 9. Commit transaction if all updates succeed
                $conn->commit();

                // 10. Fetch final updated room info for display
                $finalQuery = "SELECT r.room_id, r.hostel_id, r.room_number, r.block, r.floor, r.room_type, r.capacity, r.occupied, r.status, h.hostel_name,
                               GROUP_CONCAT(DISTINCT CONCAT(s.name,' (',s.roll_number,')') SEPARATOR '<br>') AS student_info
                               FROM rooms r
                               LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                               LEFT JOIN room_students rs ON r.room_id = rs.room_id AND rs.is_active = 1 AND (rs.vacated_at IS NULL OR rs.vacated_at='0000-00-00 00:00:00')
                               LEFT JOIN students s ON rs.student_id = s.student_id
                               WHERE r.room_id = ?
                               GROUP BY r.room_id";

                $finalStmt = $conn->prepare($finalQuery);
                $finalStmt->bind_param('i', $room_id);
                $finalStmt->execute();
                $finalRes = $finalStmt->get_result();
                $updatedRoom = $finalRes->fetch_assoc();
                $finalStmt->close();

                jsonResponse([
                    'success' => true,
                    'message' => 'Student assigned successfully',
                    'updated_room' => $updatedRoom
                ]);
            } catch (Exception $e) {
                // 11. Rollback transaction on any failure
                $conn->rollback();

                // Log the detailed error to a file (optional but recommended)
                error_log("Assign Student Error: " . $e->getMessage());

                // Return the specific error to the client for better debugging
                jsonResponse(['success' => false, 'error' => 'Failed to assign student: ' . $e->getMessage()]);
            }
            break;
        // ---------- Remove (vacate) student ----------
        case 'remove_student':
            $rs_id = isset($_POST['rs_id']) ? intval($_POST['rs_id']) : 0;
            if (!$rs_id)
                jsonResponse(['success' => false, 'error' => 'Invalid id']);

            $now = date('Y-m-d H:i:s');
            $s0 = $conn->prepare("SELECT room_id, student_id FROM room_students WHERE id = ?");
            $s0->bind_param('i', $rs_id);
            $s0->execute();
            $res0 = $s0->get_result();
            $rs = $res0->fetch_assoc();
            $s0->close();
            if (!$rs)
                jsonResponse(['success' => false, 'error' => 'Not found']);

            $s1 = $conn->prepare("UPDATE room_students SET is_active = 0, vacated_at = ? WHERE id = ?");
            $s1->bind_param('si', $now, $rs_id);
            $s1->execute();
            $s1->close();

            $u = $conn->prepare("UPDATE rooms SET occupied = GREATEST(0, occupied - 1) WHERE room_id = ?");
            $u->bind_param('i', $rs['room_id']);
            $u->execute();
            $u->close();

            $u2 = $conn->prepare("UPDATE students SET room_id = NULL, hostel_id = NULL WHERE student_id = ?");
            $u2->bind_param('i', $rs['student_id']);
            $u2->execute();
            $u2->close();

            // Set vacated flag if column exists
            $checkColumnQuery = "SHOW COLUMNS FROM students LIKE 'vacated'";
            $checkResult = $conn->query($checkColumnQuery);
            if ($checkResult && $checkResult->num_rows > 0) {
                $setVacatedQuery = "UPDATE students SET vacated = 1 WHERE student_id = ?";
                $setVacatedStmt = $conn->prepare($setVacatedQuery);
                $setVacatedStmt->bind_param('i', $rs['student_id']);
                $setVacatedStmt->execute();
                $setVacatedStmt->close();
            }

            $s3 = $conn->prepare("SELECT room_id, occupied, capacity FROM rooms WHERE room_id = ?");
            $s3->bind_param('i', $rs['room_id']);
            $s3->execute();
            $res3 = $s3->get_result();
            $new = $res3->fetch_assoc();
            $s3->close();

            jsonResponse(['success' => true, 'updated_room' => $new]);
            break;

        // ---------- Transfer student - FIXED ----------
        case 'transfer_student':
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
            $from_room = isset($_POST['from_room_id']) ? intval($_POST['from_room_id']) : 0;
            $to_room = isset($_POST['to_room_id']) ? intval($_POST['to_room_id']) : 0;

            if (!$student_id || !$from_room || !$to_room) {
                jsonResponse(['success' => false, 'error' => 'Missing fields']);
            }

            // Get destination room info
            $destQuery = "SELECT r.capacity, r.occupied, r.room_number, r.hostel_id, h.hostel_name, h.gender 
                         FROM rooms r 
                         LEFT JOIN hostels h ON r.hostel_id = h.hostel_id 
                         WHERE r.room_id = ?";
            $destStmt = $conn->prepare($destQuery);
            $destStmt->bind_param('i', $to_room);
            $destStmt->execute();
            $destRes = $destStmt->get_result();
            $dest = $destRes->fetch_assoc();
            $destStmt->close();

            if (!$dest)
                jsonResponse(['success' => false, 'error' => 'Destination room not found']);
            if (intval($dest['occupied']) >= intval($dest['capacity'])) {
                jsonResponse(['success' => false, 'error' => 'Destination room is full']);
            }

            // Get student details
            $sStmt = $conn->prepare("SELECT roll_number, gender FROM students WHERE student_id = ?");
            $sStmt->bind_param('i', $student_id);
            $sStmt->execute();
            $sRes = $sStmt->get_result();
            $sData = $sRes->fetch_assoc();
            $sStmt->close();

            // Check gender compatibility
            if (
                ($dest['gender'] == 'Male' && $sData['gender'] != 'Male') ||
                ($dest['gender'] == 'Female' && $sData['gender'] != 'Female')
            ) {
                jsonResponse(['success' => false, 'error' => 'Gender mismatch: Cannot transfer student to this hostel']);
            }

            $now = date('Y-m-d H:i:s');

            // Get student and room details before vacating
            $detailsQuery = "SELECT rs.room_id, rs.student_id, r.room_number, s.roll_number, rs.assigned_at, h.hostel_id, h.hostel_name,
                            s.name as student_name, s.department, s.academic_batch
                            FROM room_students rs
                            LEFT JOIN students s ON rs.student_id = s.student_id
                            LEFT JOIN rooms r ON rs.room_id = r.room_id
                            LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                            WHERE rs.room_id = ? AND rs.student_id = ? AND rs.is_active = 1";
            $detailsStmt = $conn->prepare($detailsQuery);
            $detailsStmt->bind_param('ii', $from_room, $student_id);
            $detailsStmt->execute();
            $detailsResult = $detailsStmt->get_result();
            $studentDetails = $detailsResult->fetch_assoc();
            $detailsStmt->close();

            // Mark old assignment as vacated
            $vacateQuery = "UPDATE room_students 
                           SET is_active = 0, vacated_at = ? 
                           WHERE room_id = ? AND student_id = ? AND is_active = 1";
            $vacateStmt = $conn->prepare($vacateQuery);
            $vacateStmt->bind_param('sii', $now, $from_room, $student_id);
            $vacateStmt->execute();

            // Insert into vacated_students_history table
            if ($vacateStmt->affected_rows > 0 && $studentDetails) {
                $insertHistoryQuery = "INSERT INTO vacated_students_history 
                                      (room_id, student_id, room_number, roll_number, assigned_at, vacated_at, hostel_id, hostel_name, student_name, department, academic_batch) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $insertHistoryStmt = $conn->prepare($insertHistoryQuery);
                $insertHistoryStmt->bind_param(
                    'iissssissss',
                    $studentDetails['room_id'],
                    $studentDetails['student_id'],
                    $studentDetails['room_number'],
                    $studentDetails['roll_number'],
                    $studentDetails['assigned_at'],
                    $now,
                    $studentDetails['hostel_id'],
                    $studentDetails['hostel_name'],
                    $studentDetails['student_name'],
                    $studentDetails['department'],
                    $studentDetails['academic_batch']
                );
                $insertHistoryStmt->execute();
                $insertHistoryStmt->close();
            }

            $vacateStmt->close();

            // Add new assignment with all columns
            // Get room details for the destination room
            $roomDetailsQuery = "SELECT r.room_number, r.hostel_id, h.hostel_name 
                                FROM rooms r 
                                LEFT JOIN hostels h ON r.hostel_id = h.hostel_id 
                                WHERE r.room_id = ?";
            $roomDetailsStmt = $conn->prepare($roomDetailsQuery);
            $roomDetailsStmt->bind_param('i', $to_room);
            $roomDetailsStmt->execute();
            $roomDetailsResult = $roomDetailsStmt->get_result();
            $roomDetails = $roomDetailsResult->fetch_assoc();
            $roomDetailsStmt->close();

            // Get student roll number and name
            $studentRollQuery = "SELECT roll_number, name FROM students WHERE student_id = ?";
            $studentRollStmt = $conn->prepare($studentRollQuery);
            $studentRollStmt->bind_param('i', $student_id);
            $studentRollStmt->execute();
            $studentRollResult = $studentRollStmt->get_result();
            $studentRollData = $studentRollResult->fetch_assoc();
            $studentRollStmt->close();

            $assignQuery = "INSERT INTO room_students 
                           (room_id, student_id, room_number, roll_number, hostel_id, hostel_name, student_name, assigned_at, is_active) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $assignStmt = $conn->prepare($assignQuery);
            $assignStmt->bind_param(
                'iissssss',
                $to_room,
                $student_id,
                $roomDetails['room_number'],
                $studentRollData['roll_number'],
                $roomDetails['hostel_id'],
                $roomDetails['hostel_name'],
                $studentRollData['name'],
                $now
            );
            $assignStmt->execute();
            $assignStmt->close();

            // Update student table
            // room_backend.php (CORRECTED Code - Only updates the existing room_id column)

            // 9. Update student table with room_id
            $updateStudentQuery = "UPDATE students SET room_id = ? WHERE student_id = ?";
            $updateStudentStmt = $conn->prepare($updateStudentQuery);
            // Bind parameter has changed from 'iii' to 'ii'
            $updateStudentStmt->bind_param('ii', $to_room, $student_id);
            if (!$updateStudentStmt->execute()) {
                throw new Exception('students UPDATE failed: ' . $updateStudentStmt->error);
            }
            $updateStudentStmt->close();

            // Update room counts by recalculating actual occupancy
            $countFromQuery = "SELECT COUNT(*) as count FROM room_students WHERE room_id = ? AND is_active = 1";
            $countFromStmt = $conn->prepare($countFromQuery);
            $countFromStmt->bind_param('i', $from_room);
            $countFromStmt->execute();
            $countFromRes = $countFromStmt->get_result();
            $fromOccupancy = $countFromRes->fetch_assoc()['count'];
            $countFromStmt->close();

            $updateFromQuery = "UPDATE rooms SET occupied = ? WHERE room_id = ?";
            $updateFromStmt = $conn->prepare($updateFromQuery);
            $updateFromStmt->bind_param('ii', $fromOccupancy, $from_room);
            $updateFromStmt->execute();
            $updateFromStmt->close();

            $countToQuery = "SELECT COUNT(*) as count FROM room_students WHERE room_id = ? AND is_active = 1";
            $countToStmt = $conn->prepare($countToQuery);
            $countToStmt->bind_param('i', $to_room);
            $countToStmt->execute();
            $countToRes = $countToStmt->get_result();
            $toOccupancy = $countToRes->fetch_assoc()['count'];
            $countToStmt->close();

            $updateToQuery = "UPDATE rooms SET occupied = ? WHERE room_id = ?";
            $updateToStmt = $conn->prepare($updateToQuery);
            $updateToStmt->bind_param('ii', $toOccupancy, $to_room);
            $updateToStmt->execute();
            $updateToStmt->close();

            // Get updated room data for both rooms
            $roomDataQuery = "SELECT r.room_id, r.hostel_id, r.room_number, r.block, r.floor, r.room_type, r.capacity, r.occupied, r.status, h.hostel_name,
                             GROUP_CONCAT(DISTINCT CONCAT(s.name,' (',s.roll_number,')') SEPARATOR '<br>') AS student_info
                             FROM rooms r
                             LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                             LEFT JOIN room_students rs ON r.room_id = rs.room_id AND rs.is_active = 1 AND (rs.vacated_at IS NULL OR rs.vacated_at='0000-00-00 00:00:00')
                             LEFT JOIN students s ON rs.student_id = s.student_id
                             WHERE r.room_id = ?
                             GROUP BY r.room_id";

            $fromStmt = $conn->prepare($roomDataQuery);
            $fromStmt->bind_param('i', $from_room);
            $fromStmt->execute();
            $fromRes = $fromStmt->get_result();
            $from_updated = $fromRes->fetch_assoc();
            $fromStmt->close();

            $toStmt = $conn->prepare($roomDataQuery);
            $toStmt->bind_param('i', $to_room);
            $toStmt->execute();
            $toRes = $toStmt->get_result();
            $to_updated = $toRes->fetch_assoc();
            $toStmt->close();

            jsonResponse([
                'success' => true,
                'updated_from' => $from_updated,
                'updated_to' => $to_updated
            ]);
            break;

        // ---------- Transfer room - NEW FUNCTIONALITY ----------
        case 'transfer_room':
            $from_room_id = isset($_POST['from_room_id']) ? intval($_POST['from_room_id']) : 0;
            $to_room_id = isset($_POST['to_room_id']) ? intval($_POST['to_room_id']) : 0;

            if (!$from_room_id || !$to_room_id) {
                jsonResponse(['success' => false, 'error' => 'Missing room IDs']);
            }

            if ($from_room_id == $to_room_id) {
                jsonResponse(['success' => false, 'error' => 'Source and destination rooms cannot be the same']);
            }

            // Get source room info
            $fromQuery = "SELECT r.occupied, h.hostel_name, h.gender FROM rooms r LEFT JOIN hostels h ON r.hostel_id = h.hostel_id WHERE r.room_id = ?";
            $fromStmt = $conn->prepare($fromQuery);
            $fromStmt->bind_param('i', $from_room_id);
            $fromStmt->execute();
            $fromRes = $fromStmt->get_result();
            $fromRoom = $fromRes->fetch_assoc();
            $fromStmt->close();

            if (!$fromRoom) {
                jsonResponse(['success' => false, 'error' => 'Source room not found']);
            }

            if ($fromRoom['occupied'] == 0) {
                jsonResponse(['success' => false, 'error' => 'Source room has no students to transfer']);
            }

            // Get destination room info
            $toQuery = "SELECT r.capacity, r.occupied, r.room_number, r.hostel_id, h.hostel_name, h.gender 
                       FROM rooms r 
                       LEFT JOIN hostels h ON r.hostel_id = h.hostel_id 
                       WHERE r.room_id = ?";
            $toStmt = $conn->prepare($toQuery);
            $toStmt->bind_param('i', $to_room_id);
            $toStmt->execute();
            $toRes = $toStmt->get_result();
            $toRoom = $toRes->fetch_assoc();
            $toStmt->close();

            if (!$toRoom) {
                jsonResponse(['success' => false, 'error' => 'Destination room not found']);
            }

            // Check if destination room has enough capacity
            $availableCapacity = intval($toRoom['capacity']) - intval($toRoom['occupied']);
            if ($availableCapacity < intval($fromRoom['occupied'])) {
                jsonResponse(['success' => false, 'error' => 'Destination room does not have enough capacity. Available space: ' . $availableCapacity . ', Students to transfer: ' . $fromRoom['occupied']]);
            }

            // Check gender compatibility
            if ($fromRoom['gender'] != $toRoom['gender']) {
                jsonResponse(['success' => false, 'error' => 'Gender mismatch: Cannot transfer students between different gender hostels']);
            }

            $now = date('Y-m-d H:i:s');

            // Get all students from source room
            $studentsQuery = "SELECT rs.student_id, s.roll_number FROM room_students rs 
                             LEFT JOIN students s ON rs.student_id = s.student_id 
                             WHERE rs.room_id = ? AND rs.is_active = 1";
            $studentsStmt = $conn->prepare($studentsQuery);
            $studentsStmt->bind_param('i', $from_room_id);
            $studentsStmt->execute();
            $studentsRes = $studentsStmt->get_result();
            $students = [];
            while ($row = $studentsRes->fetch_assoc()) {
                $students[] = $row;
            }
            $studentsStmt->close();

            if (empty($students)) {
                jsonResponse(['success' => false, 'error' => 'No active students found in source room']);
            }

            $conn->begin_transaction();

            try {
                // Mark all students in source room as vacated and insert into history
                $studentsQuery = "SELECT rs.room_id, rs.student_id, r.room_number, s.roll_number, rs.assigned_at, h.hostel_id, h.hostel_name,
                                s.name as student_name, s.department, s.academic_batch
                                FROM room_students rs
                                LEFT JOIN students s ON rs.student_id = s.student_id
                                LEFT JOIN rooms r ON rs.room_id = r.room_id
                                LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                                WHERE rs.room_id = ? AND rs.is_active = 1";
                $studentsStmt = $conn->prepare($studentsQuery);
                $studentsStmt->bind_param('i', $from_room_id);
                $studentsStmt->execute();
                $studentsResult = $studentsStmt->get_result();

                // Mark all students in source room as vacated
                $vacateQuery = "UPDATE room_students SET is_active = 0, vacated_at = ? WHERE room_id = ? AND is_active = 1";
                $vacateStmt = $conn->prepare($vacateQuery);
                $vacateStmt->bind_param('si', $now, $from_room_id);
                $vacateStmt->execute();
                $vacateStmt->close();

                // Insert each student into vacated_students_history
                while ($studentDetails = $studentsResult->fetch_assoc()) {
                    $insertHistoryQuery = "INSERT INTO vacated_students_history 
                                          (room_id, student_id, room_number, roll_number, assigned_at, vacated_at, hostel_id, hostel_name, student_name, department, academic_batch) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $insertHistoryStmt = $conn->prepare($insertHistoryQuery);
                    $insertHistoryStmt->bind_param(
                        'iissssissss',
                        $studentDetails['room_id'],
                        $studentDetails['student_id'],
                        $studentDetails['room_number'],
                        $studentDetails['roll_number'],
                        $studentDetails['assigned_at'],
                        $now,
                        $studentDetails['hostel_id'],
                        $studentDetails['hostel_name'],
                        $studentDetails['student_name'],
                        $studentDetails['department'],
                        $studentDetails['academic_batch']
                    );
                    $insertHistoryStmt->execute();
                    $insertHistoryStmt->close();
                }
                $studentsStmt->close();

                // Add all students to destination room with complete data
                // Get destination room details
                $destRoomQuery = "SELECT r.room_number, r.hostel_id, h.hostel_name 
                                 FROM rooms r 
                                 LEFT JOIN hostels h ON r.hostel_id = h.hostel_id 
                                 WHERE r.room_id = ?";
                $destRoomStmt = $conn->prepare($destRoomQuery);
                $destRoomStmt->bind_param('i', $to_room_id);
                $destRoomStmt->execute();
                $destRoomResult = $destRoomStmt->get_result();
                $destRoomDetails = $destRoomResult->fetch_assoc();
                $destRoomStmt->close();

                // Prepare insert query with all required fields
                $assignQuery = "INSERT INTO room_students 
                               (room_id, student_id, room_number, roll_number, hostel_id, hostel_name, student_name, assigned_at, is_active) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
                $assignStmt = $conn->prepare($assignQuery);

                foreach ($students as $student) {
                    // Get student details
                    $studentDetailsQuery = "SELECT roll_number, name FROM students WHERE student_id = ?";
                    $studentDetailsStmt = $conn->prepare($studentDetailsQuery);
                    $studentDetailsStmt->bind_param('i', $student['student_id']);
                    $studentDetailsStmt->execute();
                    $studentDetailsResult = $studentDetailsStmt->get_result();
                    $studentDetails = $studentDetailsResult->fetch_assoc();
                    $studentDetailsStmt->close();

                    $assignStmt->bind_param(
                        'iissssss',
                        $to_room_id,
                        $student['student_id'],
                        $destRoomDetails['room_number'],
                        $studentDetails['roll_number'],
                        $destRoomDetails['hostel_id'],
                        $destRoomDetails['hostel_name'],
                        $studentDetails['name'],
                        $now
                    );
                    $assignStmt->execute();
                }
                $assignStmt->close();

                // Update student table for all students
                $updateStudentQuery = "UPDATE students SET room_id = ?, hostel_id = ? WHERE student_id = ?";
                $updateStudentStmt = $conn->prepare($updateStudentQuery);

                foreach ($students as $student) {
                    $updateStudentStmt->bind_param('iii', $to_room_id, $toRoom['hostel_id'], $student['student_id']);
                    $updateStudentStmt->execute();
                }
                $updateStudentStmt->close();

                // Update room counts
                $updateFromQuery = "UPDATE rooms SET occupied = 0 WHERE room_id = ?";
                $updateFromStmt = $conn->prepare($updateFromQuery);
                $updateFromStmt->bind_param('i', $from_room_id);
                $updateFromStmt->execute();
                $updateFromStmt->close();

                // Recalculate destination room occupancy
                $countQuery = "SELECT COUNT(*) as count FROM room_students WHERE room_id = ? AND is_active = 1";
                $countStmt = $conn->prepare($countQuery);
                $countStmt->bind_param('i', $to_room_id);
                $countStmt->execute();
                $countRes = $countStmt->get_result();
                $newOccupancy = $countRes->fetch_assoc()['count'];
                $countStmt->close();

                $updateToQuery = "UPDATE rooms SET occupied = ? WHERE room_id = ?";
                $updateToStmt = $conn->prepare($updateToQuery);
                $updateToStmt->bind_param('ii', $newOccupancy, $to_room_id);
                $updateToStmt->execute();
                $updateToStmt->close();

                $conn->commit();

                // Get updated room data for both rooms
                $roomDataQuery = "SELECT r.room_id, r.hostel_id, r.room_number, r.block, r.floor, r.room_type, r.capacity, r.occupied, r.status, h.hostel_name,
                                 GROUP_CONCAT(DISTINCT CONCAT(s.name,' (',s.roll_number,')') SEPARATOR '<br>') AS student_info
                                 FROM rooms r
                                 LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                                 LEFT JOIN room_students rs ON r.room_id = rs.room_id AND rs.is_active = 1 AND (rs.vacated_at IS NULL OR rs.vacated_at='0000-00-00 00:00:00')
                                 LEFT JOIN students s ON rs.student_id = s.student_id
                                 WHERE r.room_id IN (?, ?)
                                 GROUP BY r.room_id";

                $finalStmt = $conn->prepare($roomDataQuery);
                $finalStmt->bind_param('ii', $from_room_id, $to_room_id);
                $finalStmt->execute();
                $finalRes = $finalStmt->get_result();

                $rooms_changed = [];
                while ($room = $finalRes->fetch_assoc()) {
                    $rooms_changed[] = $room;
                }
                $finalStmt->close();

                jsonResponse([
                    'success' => true,
                    'message' => 'Successfully transferred ' . count($students) . ' students from room to another',
                    'updated_rooms' => $rooms_changed,
                    'transferred_count' => count($students)
                ]);
            } catch (Exception $e) {
                $conn->rollback();
                jsonResponse(['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()]);
            }
            break;

        // ---------- Swap students - FIXED ----------
        case 'swap_students':
            $student_a = isset($_POST['student_a_id']) ? intval($_POST['student_a_id']) : 0;
            $student_b = isset($_POST['student_b_id']) ? intval($_POST['student_b_id']) : 0;

            if (!$student_a || !$student_b) {
                jsonResponse(['success' => false, 'error' => 'Invalid students']);
            }

            // Get current assignments
            $getAssignmentQuery = "SELECT rs.id as rsid, rs.room_id 
                                  FROM room_students rs 
                                  WHERE rs.student_id = ? AND rs.is_active = 1 
                                  AND (rs.vacated_at IS NULL OR rs.vacated_at='0000-00-00 00:00:00') 
                                  LIMIT 1";

            $stmtA = $conn->prepare($getAssignmentQuery);
            $stmtA->bind_param('i', $student_a);
            $stmtA->execute();
            $resA = $stmtA->get_result();
            $ra = $resA->fetch_assoc();
            $stmtA->close();

            $stmtB = $conn->prepare($getAssignmentQuery);
            $stmtB->bind_param('i', $student_b);
            $stmtB->execute();
            $resB = $stmtB->get_result();
            $rb = $resB->fetch_assoc();
            $stmtB->close();

            if (!$ra || !$rb) {
                jsonResponse(['success' => false, 'error' => 'One or both students are not assigned to rooms']);
            }

            // Get room info for both rooms
            $getRoomInfoQuery = "SELECT r.room_number, r.hostel_id, h.hostel_name, h.gender 
                                FROM rooms r 
                                LEFT JOIN hostels h ON r.hostel_id = h.hostel_id 
                                WHERE r.room_id = ?";

            $roomAStmt = $conn->prepare($getRoomInfoQuery);
            $roomAStmt->bind_param('i', $rb['room_id']);
            $roomAStmt->execute();
            $roomAData = $roomAStmt->get_result()->fetch_assoc();
            $roomAStmt->close();

            $roomBStmt = $conn->prepare($getRoomInfoQuery);
            $roomBStmt->bind_param('i', $ra['room_id']);
            $roomBStmt->execute();
            $roomBData = $roomBStmt->get_result()->fetch_assoc();
            $roomBStmt->close();

            // Get student details
            $getStudentQuery = "SELECT roll_number, gender FROM students WHERE student_id = ?";

            $studentAStmt = $conn->prepare($getStudentQuery);
            $studentAStmt->bind_param('i', $student_a);
            $studentAStmt->execute();
            $studentAData = $studentAStmt->get_result()->fetch_assoc();
            $studentAStmt->close();

            $studentBStmt = $conn->prepare($getStudentQuery);
            $studentBStmt->bind_param('i', $student_b);
            $studentBStmt->execute();
            $studentBData = $studentBStmt->get_result()->fetch_assoc();
            $studentBStmt->close();

            // Check gender compatibility
            if (
                ($roomAData['gender'] == 'Male' && $studentAData['gender'] != 'Male') ||
                ($roomAData['gender'] == 'Female' && $studentAData['gender'] != 'Female')
            ) {
                jsonResponse(['success' => false, 'error' => 'Gender mismatch: Cannot swap student A to room B']);
            }

            if (
                ($roomBData['gender'] == 'Male' && $studentBData['gender'] != 'Male') ||
                ($roomBData['gender'] == 'Female' && $studentBData['gender'] != 'Female')
            ) {
                jsonResponse(['success' => false, 'error' => 'Gender mismatch: Cannot swap student B to room A']);
            }

            $now = date('Y-m-d H:i:s');

            // Get student and room details before vacating for both students
            $detailsQuery = "SELECT rs.id as rsid, rs.room_id, rs.student_id, r.room_number, s.roll_number, rs.assigned_at, h.hostel_id, h.hostel_name,
                            s.name as student_name, s.department, s.academic_batch
                            FROM room_students rs
                            LEFT JOIN students s ON rs.student_id = s.student_id
                            LEFT JOIN rooms r ON rs.room_id = r.room_id
                            LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                            WHERE rs.id IN (?, ?)";
            $detailsStmt = $conn->prepare($detailsQuery);
            $detailsStmt->bind_param('ii', $ra['rsid'], $rb['rsid']);
            $detailsStmt->execute();
            $detailsResult = $detailsStmt->get_result();

            $studentDetails = [];
            while ($row = $detailsResult->fetch_assoc()) {
                $studentDetails[$row['rsid']] = $row;
            }
            $detailsStmt->close();

            // Mark both current assignments as vacated
            $vacateQuery = "UPDATE room_students SET is_active = 0, vacated_at = ? WHERE id IN (?, ?)";
            $vacateStmt = $conn->prepare($vacateQuery);
            $vacateStmt->bind_param('sii', $now, $ra['rsid'], $rb['rsid']);
            $vacateStmt->execute();

            // Insert into vacated_students_history table for both students
            if ($vacateStmt->affected_rows > 0) {
                foreach ([$ra['rsid'], $rb['rsid']] as $rsid) {
                    if (isset($studentDetails[$rsid])) {
                        $details = $studentDetails[$rsid];
                        $insertHistoryQuery = "INSERT INTO vacated_students_history 
                                              (room_id, student_id, room_number, roll_number, assigned_at, vacated_at, hostel_id, hostel_name, student_name, department, academic_batch) 
                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $insertHistoryStmt = $conn->prepare($insertHistoryQuery);
                        $insertHistoryStmt->bind_param(
                            'iissssissss',
                            $details['room_id'],
                            $details['student_id'],
                            $details['room_number'],
                            $details['roll_number'],
                            $details['assigned_at'],
                            $now,
                            $details['hostel_id'],
                            $details['hostel_name'],
                            $details['student_name'],
                            $details['department'],
                            $details['academic_batch']
                        );
                        $insertHistoryStmt->execute();
                        $insertHistoryStmt->close();
                    }
                }
            }

            $vacateStmt->close();

            // Insert swapped assignments with all columns
            // Get room details for Student A (going to Room B)
            $roomADetailsQuery = "SELECT r.room_number, r.hostel_id, h.hostel_name 
                                 FROM rooms r 
                                 LEFT JOIN hostels h ON r.hostel_id = h.hostel_id 
                                 WHERE r.room_id = ?";
            $roomADetailsStmt = $conn->prepare($roomADetailsQuery);
            $roomADetailsStmt->bind_param('i', $rb['room_id']);
            $roomADetailsStmt->execute();
            $roomADetailsResult = $roomADetailsStmt->get_result();
            $roomADetails = $roomADetailsResult->fetch_assoc();
            $roomADetailsStmt->close();

            // Get student A details
            $studentADetailsQuery = "SELECT roll_number, name FROM students WHERE student_id = ?";
            $studentADetailsStmt = $conn->prepare($studentADetailsQuery);
            $studentADetailsStmt->bind_param('i', $student_a);
            $studentADetailsStmt->execute();
            $studentADetailsResult = $studentADetailsStmt->get_result();
            $studentADetails = $studentADetailsResult->fetch_assoc();
            $studentADetailsStmt->close();

            // Get room details for Student B (going to Room A)
            $roomBDetailsQuery = "SELECT r.room_number, r.hostel_id, h.hostel_name 
                                 FROM rooms r 
                                 LEFT JOIN hostels h ON r.hostel_id = h.hostel_id 
                                 WHERE r.room_id = ?";
            $roomBDetailsStmt = $conn->prepare($roomBDetailsQuery);
            $roomBDetailsStmt->bind_param('i', $ra['room_id']);
            $roomBDetailsStmt->execute();
            $roomBDetailsResult = $roomBDetailsStmt->get_result();
            $roomBDetails = $roomBDetailsResult->fetch_assoc();
            $roomBDetailsStmt->close();

            // Get student B details
            $studentBDetailsQuery = "SELECT roll_number, name FROM students WHERE student_id = ?";
            $studentBDetailsStmt = $conn->prepare($studentBDetailsQuery);
            $studentBDetailsStmt->bind_param('i', $student_b);
            $studentBDetailsStmt->execute();
            $studentBDetailsResult = $studentBDetailsStmt->get_result();
            $studentBDetails = $studentBDetailsResult->fetch_assoc();
            $studentBDetailsStmt->close();

            // Insert Student A to Room B with all required fields
            $insertSwapAQuery = "INSERT INTO room_students 
                                (room_id, student_id, room_number, roll_number, hostel_id, hostel_name, student_name, assigned_at, is_active) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $swapAStmt = $conn->prepare($insertSwapAQuery);
            $swapAStmt->bind_param(
                'iissssss',
                $rb['room_id'],
                $student_a,
                $roomADetails['room_number'],
                $studentADetails['roll_number'],
                $roomADetails['hostel_id'],
                $roomADetails['hostel_name'],
                $studentADetails['name'],
                $now
            );
            $swapAStmt->execute();
            $swapAStmt->close();

            // Insert Student B to Room A with all required fields
            $insertSwapBQuery = "INSERT INTO room_students 
                                (room_id, student_id, room_number, roll_number, hostel_id, hostel_name, student_name, assigned_at, is_active) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $swapBStmt = $conn->prepare($insertSwapBQuery);
            $swapBStmt->bind_param(
                'iissssss',
                $ra['room_id'],
                $student_b,
                $roomBDetails['room_number'],
                $studentBDetails['roll_number'],
                $roomBDetails['hostel_id'],
                $roomBDetails['hostel_name'],
                $studentBDetails['name'],
                $now
            );
            $swapBStmt->execute();
            $swapBStmt->close();

            // Update students table
            $updateStudentAQuery = "UPDATE students SET room_id = ?, hostel_id = ? WHERE student_id = ?";
            $updateStudentAStmt = $conn->prepare($updateStudentAQuery);
            $updateStudentAStmt->bind_param('iii', $rb['room_id'], $roomAData['hostel_id'], $student_a);
            $updateStudentAStmt->execute();
            $updateStudentAStmt->close();

            $updateStudentBQuery = "UPDATE students SET room_id = ?, hostel_id = ? WHERE student_id = ?";
            $updateStudentBStmt = $conn->prepare($updateStudentBQuery);
            $updateStudentBStmt->bind_param('iii', $ra['room_id'], $roomBData['hostel_id'], $student_b);
            $updateStudentBStmt->execute();
            $updateStudentBStmt->close();

            // Get updated room data
            $roomDataQuery = "SELECT r.room_id, r.hostel_id, r.room_number, r.block, r.floor, r.room_type, r.capacity, r.occupied, r.status, h.hostel_name,
                             GROUP_CONCAT(DISTINCT CONCAT(s.name,' (',s.roll_number,')') SEPARATOR '<br>') AS student_info
                             FROM rooms r
                             LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                             LEFT JOIN room_students rs ON r.room_id = rs.room_id AND rs.is_active = 1 AND (rs.vacated_at IS NULL OR rs.vacated_at='0000-00-00 00:00:00')
                             LEFT JOIN students s ON rs.student_id = s.student_id
                             WHERE r.room_id IN (?, ?)
                             GROUP BY r.room_id";

            $finalStmt = $conn->prepare($roomDataQuery);
            $finalStmt->bind_param('ii', $ra['room_id'], $rb['room_id']);
            $finalStmt->execute();
            $finalRes = $finalStmt->get_result();

            $rooms_changed = [];
            while ($room = $finalRes->fetch_assoc()) {
                $rooms_changed[] = $room;
            }
            $finalStmt->close();

            jsonResponse(['success' => true, 'updated_rooms' => $rooms_changed]);
            break;

        // ---------- Get rooms status list ----------
        case 'get_rooms_status':
            $hostel_id = isset($_POST['hostel_id']) ? intval($_POST['hostel_id']) : 0;
            $gender = '';

            // If hostel_id is provided, get the gender of that hostel
            if ($hostel_id > 0) {
                $genderStmt = $conn->prepare("SELECT gender FROM hostels WHERE hostel_id = ?");
                $genderStmt->bind_param('i', $hostel_id);
                $genderStmt->execute();
                $genderRes = $genderStmt->get_result();
                if ($genderRow = $genderRes->fetch_assoc()) {
                    $gender = $genderRow['gender'];
                }
                $genderStmt->close();
            }

            $out = [];
            $sql = "SELECT r.room_id, r.hostel_id, r.room_number, r.block, r.floor, r.capacity, r.occupied, h.hostel_name, h.gender, r.status, r.room_type
                    FROM rooms r LEFT JOIN hostels h ON r.hostel_id = h.hostel_id";

            if (!empty($gender)) {
                // Filter rooms by gender to allow transfers between same-gender hostels
                $sql .= " WHERE h.gender = ?";
                $sql .= " ORDER BY h.hostel_name, r.room_number ASC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('s', $gender);
                $stmt->execute();
                $res = $stmt->get_result();
            } else {
                $sql .= " ORDER BY h.hostel_name, r.room_number ASC";
                $res = $conn->query($sql);
            }

            if ($res) {
                while ($r = $res->fetch_assoc())
                    $out[] = $r;
                if (isset($stmt))
                    $stmt->close();
                $res->free();
                jsonResponse(['success' => true, 'data' => $out]);
            } else {
                if (isset($stmt))
                    $stmt->close();
                jsonResponse(['success' => false, 'error' => $conn->error]);
            }
            break;

        // ---------- Get room numbers for a hostel ----------
        case 'get_floors':
            $hostel_id = isset($_POST['hostel_id']) ? intval($_POST['hostel_id']) : 0;

            if (!$hostel_id) {
                jsonResponse(['success' => false, 'error' => 'Invalid hostel']);
            }

            // Get room numbers for this hostel
            $roomsQuery = "SELECT DISTINCT room_number FROM rooms WHERE hostel_id = ? ORDER BY room_number";
            $roomsStmt = $conn->prepare($roomsQuery);
            $roomsStmt->bind_param('i', $hostel_id);
            $roomsStmt->execute();
            $roomsRes = $roomsStmt->get_result();

            $rooms = [];
            while ($row = $roomsRes->fetch_assoc()) {
                $rooms[] = $row['room_number'];
            }
            $roomsStmt->close();

            jsonResponse(['success' => true, 'rooms' => $rooms]);
            break;

        // ---------- Get rooms for a hostel ----------
        case 'get_rooms_for_floor':
            $hostel_id = isset($_POST['hostel_id']) ? intval($_POST['hostel_id']) : 0;

            if (!$hostel_id) {
                jsonResponse(['success' => false, 'error' => 'Missing parameters']);
            }

            $roomsQuery = "SELECT room_id, room_number FROM rooms WHERE hostel_id = ? ORDER BY room_number";
            $roomsStmt = $conn->prepare($roomsQuery);
            $roomsStmt->bind_param('i', $hostel_id);
            $roomsStmt->execute();
            $roomsRes = $roomsStmt->get_result();

            $rooms = [];
            while ($row = $roomsRes->fetch_assoc()) {
                $rooms[] = $row;
            }
            $roomsStmt->close();

            jsonResponse(['success' => true, 'rooms' => $rooms]);
            break;
        // room_backend.php (Add this new case to your switch statement)

        // ---------- Get unassigned students with filters ----------
        case 'get_unassigned_students':
            $hostel_id = isset($_POST['hostel_id']) ? intval($_POST['hostel_id']) : 0;
            $filter = $_POST['filter'] ?? ''; // New: Get the filter string from room.php

            if (!$hostel_id) {
                jsonResponse(['success' => false, 'error' => 'Missing hostel ID']);
            }

            // Base Query: Get students not assigned to any room (room_id IS NULL)
            $query = "SELECT student_id, roll_number, name, gender, academic_year, department 
                      FROM students 
                      WHERE room_id IS NULL"; // Filters students already assigned to a room
            $params = [];
            $types = '';

            // Apply hostel-specific filter
            if (!empty($filter)) {
                list($key, $value) = explode('=', $filter);

                if ($key === 'gender') {
                    $query .= " AND gender = ?";
                    $types .= 's';
                    $params[] = $value;
                } elseif ($key === 'academic_year') {
                    $query .= " AND academic_year = ?";
                    $types .= 's';
                    $params[] = $value;
                }
            }

            // Finalize and execute the query
            $query .= " ORDER BY roll_number";
            $stmt = $conn->prepare($query);

            if (!empty($params)) {
                // If you are on PHP < 5.6, you need to use call_user_func_array
                $stmt->bind_param($types, ...$params);
                // For older PHP, replace the line above with:
                // call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));
            }

            $stmt->execute();
            $res = $stmt->get_result();

            $students = [];
            while ($row = $res->fetch_assoc()) {
                $students[] = $row;
            }
            $stmt->close();

            jsonResponse(['success' => true, 'students' => $students]);
            break;

        // ---------- Vacate students from room ----------
        case 'vacate_students':
            $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
            $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];

            if (!$room_id) {
                jsonResponse(['success' => false, 'error' => 'Missing room ID']);
            }

            if (empty($student_ids) || !is_array($student_ids)) {
                jsonResponse(['success' => false, 'error' => 'No students selected']);
            }

            $conn->begin_transaction();

            try {
                $now = date('Y-m-d H:i:s');
                $vacatedCount = 0;

                foreach ($student_ids as $student_id) {
                    $student_id = intval($student_id);

                    // Get student and room details before vacating
                    $detailsQuery = "SELECT rs.room_id, rs.student_id, r.room_number, s.roll_number, rs.assigned_at, h.hostel_id, h.hostel_name,
                                    s.name as student_name, s.department, s.academic_batch
                                    FROM room_students rs
                                    LEFT JOIN students s ON rs.student_id = s.student_id
                                    LEFT JOIN rooms r ON rs.room_id = r.room_id
                                    LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                                    WHERE rs.room_id = ? AND rs.student_id = ? AND rs.is_active = 1";
                    $detailsStmt = $conn->prepare($detailsQuery);
                    $detailsStmt->bind_param('ii', $room_id, $student_id);
                    $detailsStmt->execute();
                    $detailsResult = $detailsStmt->get_result();
                    $studentDetails = $detailsResult->fetch_assoc();
                    $detailsStmt->close();

                    // Mark student as vacated in room_students
                    $vacateQuery = "UPDATE room_students 
                                   SET is_active = 0, vacated_at = ? 
                                   WHERE room_id = ? AND student_id = ? AND is_active = 1";
                    $vacateStmt = $conn->prepare($vacateQuery);
                    $vacateStmt->bind_param('sii', $now, $room_id, $student_id);
                    $vacateStmt->execute();

                    if ($vacateStmt->affected_rows > 0) {
                        $vacatedCount++;

                        // Insert into vacated_students_history table
                        if ($studentDetails) {
                            $insertHistoryQuery = "INSERT INTO vacated_students_history 
                                                  (room_id, student_id, room_number, roll_number, assigned_at, vacated_at, hostel_id, hostel_name, student_name, department, academic_batch) 
                                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $insertHistoryStmt = $conn->prepare($insertHistoryQuery);
                            $insertHistoryStmt->bind_param(
                                'iissssissss',
                                $studentDetails['room_id'],
                                $studentDetails['student_id'],
                                $studentDetails['room_number'],
                                $studentDetails['roll_number'],
                                $studentDetails['assigned_at'],
                                $now,
                                $studentDetails['hostel_id'],
                                $studentDetails['hostel_name'],
                                $studentDetails['student_name'],
                                $studentDetails['department'],
                                $studentDetails['academic_batch']
                            );
                            $insertHistoryStmt->execute();
                            $insertHistoryStmt->close();
                        }
                    }
                    $vacateStmt->close();

                    // Update student table - remove room_id and hostel_id, set vacated flag if column exists
                    $updateStudentQuery = "UPDATE students SET room_id = NULL, hostel_id = NULL WHERE student_id = ?";
                    $updateStudentStmt = $conn->prepare($updateStudentQuery);
                    $updateStudentStmt->bind_param('i', $student_id);
                    $updateStudentStmt->execute();
                    $updateStudentStmt->close();

                    // Check if 'vacated' column exists and update it to 1
                    $checkColumnQuery = "SHOW COLUMNS FROM students LIKE 'vacated'";
                    $checkResult = $conn->query($checkColumnQuery);
                    if ($checkResult && $checkResult->num_rows > 0) {
                        // Column exists, update it
                        $updateVacatedQuery = "UPDATE students SET vacated = 1 WHERE student_id = ?";
                        $updateVacatedStmt = $conn->prepare($updateVacatedQuery);
                        $updateVacatedStmt->bind_param('i', $student_id);
                        $updateVacatedStmt->execute();
                        $updateVacatedStmt->close();
                    }
                }

                // Update room occupied count
                $updateRoomQuery = "UPDATE rooms SET occupied = GREATEST(0, occupied - ?) WHERE room_id = ?";
                $updateRoomStmt = $conn->prepare($updateRoomQuery);
                $updateRoomStmt->bind_param('ii', $vacatedCount, $room_id);
                $updateRoomStmt->execute();
                $updateRoomStmt->close();

                $conn->commit();

                // Get updated room data
                $roomDataQuery = "SELECT r.room_id, r.hostel_id, r.room_number, r.block, r.floor, r.room_type, r.capacity, r.occupied, r.status, h.hostel_name,
                                 GROUP_CONCAT(DISTINCT CONCAT(s.name,' (',s.roll_number,')') SEPARATOR '<br>') AS student_info
                                 FROM rooms r
                                 LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                                 LEFT JOIN room_students rs ON r.room_id = rs.room_id AND rs.is_active = 1 AND (rs.vacated_at IS NULL OR rs.vacated_at='0000-00-00 00:00:00')
                                 LEFT JOIN students s ON rs.student_id = s.student_id
                                 WHERE r.room_id = ?
                                 GROUP BY r.room_id";

                $finalStmt = $conn->prepare($roomDataQuery);
                $finalStmt->bind_param('i', $room_id);
                $finalStmt->execute();
                $finalRes = $finalStmt->get_result();
                $updatedRoom = $finalRes->fetch_assoc();
                $finalStmt->close();

                jsonResponse([
                    'success' => true,
                    'message' => 'Successfully vacated ' . $vacatedCount . ' student(s)',
                    'updated_room' => $updatedRoom,
                    'vacated_count' => $vacatedCount
                ]);
            } catch (Exception $e) {
                $conn->rollback();
                jsonResponse(['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()]);
            }
            break;

            // Attendance helper function for consistent JSON responses
            $__respond = function ($data, $status = 200) {
                http_response_code($status);
                if (function_exists('ob_get_length') && ob_get_length() !== false && ob_get_length() > 0) {
                    $extra = ob_get_clean();
                    if (is_string($extra) && trim($extra) !== '') {
                        $data['debug_output'] = mb_substr($extra, 0, 2000);
                    }
                }
                echo json_encode($data);
                exit;
            };

        case 'load_present':
            $selectedDate = $_POST['selectedDate'] ?? $_GET['selectedDate'] ?? date('Y-m-d');
            $ts = strtotime($selectedDate);
            $selectedDate = ($selectedDate && $ts) ? date('Y-m-d', $ts) : date('Y-m-d');
            $hostel_filter = trim($_POST['hostel_filter'] ?? $_GET['hostel_filter'] ?? '');
            $scopeGender = get_current_admin_scope_gender();

            $sql = "
        SELECT s.roll_number, s.name, s.department, s.academic_batch, r.room_number, r.floor, a.marked_at, a.status
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        LEFT JOIN room_students rs ON s.student_id = rs.student_id AND rs.is_active = 1 AND rs.vacated_at IS NULL
        LEFT JOIN rooms r ON rs.room_id = r.room_id
    ";

            if (!empty($hostel_filter)) {
                $sql .= "
            JOIN room_students rs2 ON s.student_id = rs2.student_id AND rs2.is_active = 1 AND rs2.vacated_at IS NULL
            JOIN rooms r2 ON rs2.room_id = r2.room_id
            JOIN hostels h ON r2.hostel_id = h.hostel_id AND h.hostel_name = ?
        ";
            }

            $sql .= " WHERE a.date = ? AND a.status = 'Present'";
            if ($scopeGender !== null) {
                $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'";
            }
            $sql .= " AND NOT EXISTS (
            SELECT 1 FROM attendance a2 WHERE a2.student_id = a.student_id AND a2.date = a.date AND a2.status = 'On Leave'
        ) ORDER BY (FIELD(r.floor,'I','II','III','IV','V')=0), FIELD(r.floor,'I','II','III','IV','V'), r.room_number, s.roll_number";

            $stmt = $conn->prepare($sql);
            if (!empty($hostel_filter)) {
                $stmt->bind_param('ss', $hostel_filter, $selectedDate);
            } else {
                $stmt->bind_param('s', $selectedDate);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            $data = [];
            if ($res) {
                while ($r = $res->fetch_assoc()) $data[] = $r;
            }
            $stmt->close();
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'load_absent':
            $selectedDate = $_POST['selectedDate'] ?? $_GET['selectedDate'] ?? date('Y-m-d');
            $ts = strtotime($selectedDate);
            $selectedDate = ($selectedDate && $ts) ? date('Y-m-d', $ts) : date('Y-m-d');
            $hostel_filter = trim($_POST['hostel_filter'] ?? $_GET['hostel_filter'] ?? '');
            $scopeGender = get_current_admin_scope_gender();

            $sql = "
         SELECT s.student_id, s.roll_number, s.name, s.department, s.academic_batch, 
             r.room_number, r.floor,
               COALESCE(a.marked_at, '-') AS marked_at,
               COALESCE(a.status, 'Absent') AS status
        FROM students s
        LEFT JOIN room_students rs ON s.student_id = rs.student_id AND rs.is_active = 1 AND rs.vacated_at IS NULL
        LEFT JOIN rooms r ON rs.room_id = r.room_id
    ";

            if (!empty($hostel_filter)) {
                $sql .= "
            JOIN room_students rs2 ON s.student_id = rs2.student_id AND rs2.is_active = 1 AND rs2.vacated_at IS NULL
            JOIN rooms r2 ON rs2.room_id = r2.room_id
            JOIN hostels h ON r2.hostel_id = h.hostel_id AND h.hostel_name = ?
        ";
            }

            $sql .= "
        LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = ?
        WHERE s.status = '1' 
        AND (a.status IS NULL OR a.status = 'Absent')
    ";

            if ($scopeGender !== null) {
                $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'";
            }

            $sql .= "
        AND NOT EXISTS (
            SELECT 1 FROM leave_applications la WHERE la.Reg_No = s.roll_number AND ? BETWEEN DATE(la.From_Date) AND DATE(la.To_Date) AND la.Status = 'out'
        )
        ORDER BY (FIELD(r.floor,'I','II','III','IV','V')=0), FIELD(r.floor,'I','II','III','IV','V'), r.room_number, s.roll_number
    ";

            $stmt = $conn->prepare($sql);
            if (!empty($hostel_filter)) {
                $stmt->bind_param('sss', $hostel_filter, $selectedDate, $selectedDate);
            } else {
                $stmt->bind_param('ss', $selectedDate, $selectedDate);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            $data = [];
            if ($res) {
                while ($r = $res->fetch_assoc()) $data[] = $r;
            }
            $stmt->close();
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'load_late_entry':
            $selectedDate = $_POST['selectedDate'] ?? $_GET['selectedDate'] ?? date('Y-m-d');
            $ts = strtotime($selectedDate);
            $selectedDate = ($selectedDate && $ts) ? date('Y-m-d', $ts) : date('Y-m-d');
            $hostel_filter = trim($_POST['hostel_filter'] ?? $_GET['hostel_filter'] ?? '');
            $scopeGender = get_current_admin_scope_gender();

            $sql = "
        SELECT s.roll_number, s.name, s.department, s.academic_batch, r.room_number, r.floor, a.marked_at, a.status
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        LEFT JOIN room_students rs ON s.student_id = rs.student_id AND rs.is_active = 1 AND rs.vacated_at IS NULL
        LEFT JOIN rooms r ON rs.room_id = r.room_id
    ";

            if (!empty($hostel_filter)) {
                $sql .= "
            JOIN room_students rs2 ON s.student_id = rs2.student_id AND rs2.is_active = 1 AND rs2.vacated_at IS NULL
            JOIN rooms r2 ON rs2.room_id = r2.room_id
            JOIN hostels h ON r2.hostel_id = h.hostel_id AND h.hostel_name = ?
        ";
            }

            $sql .= " WHERE a.date = ? AND LOWER(TRIM(a.status)) LIKE '%late%'";
            if ($scopeGender !== null) {
                $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'";
            }
            $sql .= " ORDER BY (FIELD(r.floor,'I','II','III','IV','V')=0), FIELD(r.floor,'I','II','III','IV','V'), r.room_number, s.roll_number";

            $stmt = $conn->prepare($sql);
            if (!empty($hostel_filter)) {
                $stmt->bind_param('ss', $hostel_filter, $selectedDate);
            } else {
                $stmt->bind_param('s', $selectedDate);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            $data = [];
            if ($res) {
                while ($r = $res->fetch_assoc()) $data[] = $r;
            }
            $stmt->close();
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'load_on_leave':
            $selectedDate = $_POST['selectedDate'] ?? $_GET['selectedDate'] ?? date('Y-m-d');
            $ts = strtotime($selectedDate);
            $selectedDate = ($selectedDate && $ts) ? date('Y-m-d', $ts) : date('Y-m-d');
            $hostel_filter = trim($_POST['hostel_filter'] ?? $_GET['hostel_filter'] ?? '');
            $scopeGender = get_current_admin_scope_gender();

            $sql = "
         SELECT s.roll_number, s.name, s.department, s.academic_batch, r.room_number, r.floor,
             a.marked_at, COALESCE(a.status, 'On Leave') AS status,
               COALESCE(la.Reason, '-') AS reason,
               COALESCE(lt.Leave_Type_Name, 'General Leave') AS leave_type
        FROM students s
        LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = ?
        LEFT JOIN room_students rs ON s.student_id = rs.student_id AND rs.is_active = 1 AND rs.vacated_at IS NULL
        LEFT JOIN rooms r ON rs.room_id = r.room_id
        LEFT JOIN leave_applications la ON s.roll_number = la.Reg_No 
            AND ? BETWEEN DATE(la.From_Date) AND DATE(la.To_Date)
            AND la.Status = 'out'
        LEFT JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
    ";

            if (!empty($hostel_filter)) {
                $sql .= "
            JOIN room_students rs2 ON s.student_id = rs2.student_id AND rs2.is_active = 1 AND rs2.vacated_at IS NULL
            JOIN rooms r2 ON rs2.room_id = r2.room_id
            JOIN hostels h ON r2.hostel_id = h.hostel_id AND h.hostel_name = ?
        ";
            }

            $sql .= " WHERE (a.status = 'On Leave' OR la.Leave_ID IS NOT NULL)";
            if ($scopeGender !== null) {
                $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'";
            }
            $sql .= " ORDER BY (FIELD(r.floor,'I','II','III','IV','V')=0), FIELD(r.floor,'I','II','III','IV','V'), r.room_number, s.roll_number";

            $stmt = $conn->prepare($sql);
            if (!empty($hostel_filter)) {
                $stmt->bind_param('sss', $selectedDate, $selectedDate, $hostel_filter);
            } else {
                $stmt->bind_param('ss', $selectedDate, $selectedDate);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            $data = [];
            if ($res) {
                while ($r = $res->fetch_assoc()) $data[] = $r;
            }
            $stmt->close();
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'load_present_or_late':
            $selectedDate = $_POST['selectedDate'] ?? $_GET['selectedDate'] ?? date('Y-m-d');
            $ts = strtotime($selectedDate);
            $selectedDate = ($selectedDate && $ts) ? date('Y-m-d', $ts) : date('Y-m-d');
            $hostel_filter = trim($_POST['hostel_filter'] ?? $_GET['hostel_filter'] ?? '');
            $scopeGender = get_current_admin_scope_gender();

            $sql = "
        SELECT s.roll_number, s.name, s.department, s.academic_batch, r.room_number, r.floor, a.marked_at, a.status, a.reason
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        LEFT JOIN room_students rs ON s.student_id = rs.student_id AND rs.is_active = 1 AND rs.vacated_at IS NULL
        LEFT JOIN rooms r ON rs.room_id = r.room_id
    ";

            if (!empty($hostel_filter)) {
                $sql .= "
            JOIN room_students rs2 ON s.student_id = rs2.student_id AND rs2.is_active = 1 AND rs2.vacated_at IS NULL
            JOIN rooms r2 ON rs2.room_id = r2.room_id
            JOIN hostels h ON r2.hostel_id = h.hostel_id AND h.hostel_name = ?
        ";
            }

            $sql .= " WHERE a.date = ? AND (a.status = 'Present' OR LOWER(TRIM(a.status)) LIKE '%late%')";
            if ($scopeGender !== null) {
                $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'";
            }
            $sql .= " AND NOT EXISTS (
            SELECT 1 FROM attendance a2 WHERE a2.student_id = a.student_id AND a2.date = a.date AND a2.status = 'On Leave'
        ) ORDER BY (FIELD(r.floor,'I','II','III','IV','V')=0), FIELD(r.floor,'I','II','III','IV','V'), r.room_number, s.roll_number";

            $stmt = $conn->prepare($sql);
            if (!empty($hostel_filter)) {
                $stmt->bind_param('ss', $hostel_filter, $selectedDate);
            } else {
                $stmt->bind_param('s', $selectedDate);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            $data = [];
            if ($res) {
                while ($r = $res->fetch_assoc()) $data[] = $r;
            }
            $stmt->close();
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'load_absent_or_leave':
            $selectedDate = $_POST['selectedDate'] ?? $_GET['selectedDate'] ?? date('Y-m-d');
            $ts = strtotime($selectedDate);
            $selectedDate = ($selectedDate && $ts) ? date('Y-m-d', $ts) : date('Y-m-d');
            $hostel_filter = trim($_POST['hostel_filter'] ?? $_GET['hostel_filter'] ?? '');
            $scopeGender = get_current_admin_scope_gender();

            $sql = "
        SELECT s.roll_number, s.name, s.department, s.academic_batch, r.room_number, r.floor, a.marked_at, a.status
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        LEFT JOIN room_students rs ON s.student_id = rs.student_id AND rs.is_active = 1 AND rs.vacated_at IS NULL
        LEFT JOIN rooms r ON rs.room_id = r.room_id
        LEFT JOIN leave_applications la ON s.roll_number = la.Reg_No AND ? BETWEEN DATE(la.From_Date) AND DATE(la.To_Date) AND la.Status = 'out'
    ";

            if (!empty($hostel_filter)) {
                $sql .= "
            JOIN room_students rs2 ON s.student_id = rs2.student_id AND rs2.is_active = 1 AND rs2.vacated_at IS NULL
            JOIN rooms r2 ON rs2.room_id = r2.room_id
            JOIN hostels h ON r2.hostel_id = h.hostel_id AND h.hostel_name = ?
        ";
            }

            $sql .= " WHERE a.date = ? AND (a.status = 'Absent' OR a.status = 'On Leave')";
            if ($scopeGender !== null) {
                $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'";
            }
            $sql .= " AND NOT (a.status = 'Absent' AND la.Leave_ID IS NOT NULL) ORDER BY (FIELD(r.floor,'I','II','III','IV','V')=0), FIELD(r.floor,'I','II','III','IV','V'), r.room_number, s.roll_number";

            $stmt = $conn->prepare($sql);
            if (!empty($hostel_filter)) {
                $stmt->bind_param('sss', $selectedDate, $hostel_filter, $selectedDate);
            } else {
                $stmt->bind_param('ss', $selectedDate, $selectedDate);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            $data = [];
            if ($res) {
                while ($r = $res->fetch_assoc()) $data[] = $r;
            }
            $stmt->close();
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'block_all_absent':
            $today = date('Y-m-d');
            $scopeGender = get_current_admin_scope_gender();

            $q = $conn->prepare(
                "SELECT s.student_id
             FROM students s
             WHERE s.status = '1'
               " . ($scopeGender !== null ? "AND s.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'" : "") . "
               AND NOT EXISTS (
                   SELECT 1 FROM attendance a WHERE a.student_id = s.student_id AND a.date = ?
               )
               AND NOT EXISTS (
                   SELECT 1 FROM leave_applications la WHERE la.Reg_No = s.roll_number AND ? BETWEEN DATE(la.From_Date) AND DATE(la.To_Date) AND la.Status = 'out'
               )"
            );
            if ($q === false) {
                echo json_encode(['success' => false, 'message' => 'DB prepare failed (block_all_absent select): ' . $conn->error], 500);
                break;
            }
            $q->bind_param('ss', $today, $today);
            $q->execute();
            $res = $q->get_result();
            $toBlock = [];
            while ($r = $res->fetch_assoc()) $toBlock[] = (int)$r['student_id'];
            $q->close();

            if (empty($toBlock)) {
                echo json_encode(['success' => true, 'message' => 'No unmarked students to block today', 'count' => 0]);
                break;
            }

            $insertStmt = $conn->prepare("INSERT INTO blocked_students (student_id, reason, blocked_at, type) VALUES (?, ?, NOW(), ?)");
            if ($insertStmt === false) {
                echo json_encode(['success' => false, 'message' => 'DB prepare failed (block_all_absent insert): ' . $conn->error], 500);
                break;
            }
            $reason = "Automatically blocked due to not marking attendance";
            $type = "Both";
            $blockedCount = 0;

            foreach ($toBlock as $student_id) {
                $chk = $conn->prepare("SELECT id, blocked_at, unblocked_at FROM blocked_students WHERE student_id = ? ORDER BY blocked_at DESC LIMIT 1");
                if ($chk === false) {
                    $chk = $conn->prepare("SELECT id, blocked_at, NULL AS unblocked_at FROM blocked_students WHERE student_id = ? ORDER BY blocked_at DESC LIMIT 1");
                    if ($chk === false) {
                        continue;
                    }
                }
                $chk->bind_param('i', $student_id);
                $chk->execute();
                $last = $chk->get_result()->fetch_assoc();
                $chk->close();

                $canBlock = true;
                if ($last) {
                    if ($last['unblocked_at'] === null) $canBlock = false;
                    else {
                        $blockedDate = date('Y-m-d', strtotime($last['blocked_at']));
                        $unblockedDate = $last['unblocked_at'] ? date('Y-m-d', strtotime($last['unblocked_at'])) : null;
                        if ($blockedDate === $today && $unblockedDate === $today) $canBlock = false;
                    }
                }

                if ($canBlock) {
                    $insertStmt->bind_param('iss', $student_id, $reason, $type);
                    if ($insertStmt->execute()) $blockedCount++;
                }
            }
            $insertStmt->close();
            echo json_encode(['success' => true, 'message' => "$blockedCount students automatically blocked (not marked attendance)", 'count' => $blockedCount]);
            break;

        case 'get_hostels_attendance':
            $scopeGender = get_current_admin_scope_gender();
            $sql = "SELECT hostel_id, hostel_code, hostel_name FROM hostels";
            if ($scopeGender !== null) {
                $sql .= " WHERE gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'";
            }
            $sql .= " ORDER BY hostel_name";
            $res = $conn->query($sql);
            $out = [];
            while ($r = $res->fetch_assoc()) $out[] = $r;
            echo json_encode(['success' => true, 'data' => $out]);
            break;

        case 'get_leave_types':
            $sql = "SELECT LeaveType_ID, Leave_Type_Name FROM leave_types ORDER BY Priority, Leave_Type_Name";
            $res = $conn->query($sql);
            $out = [];
            while ($r = $res->fetch_assoc()) $out[] = $r;
            echo json_encode(['success' => true, 'data' => $out]);
            break;

        case 'auto_mark_absent':
            $date = $_POST['selectedDate'] ?? $_GET['selectedDate'] ?? date('Y-m-d');
            $scopeGender = get_current_admin_scope_gender();
            $student_sql = "
        SELECT s.student_id, s.roll_number
        FROM students s
        WHERE s.status = '1'";
            if ($scopeGender !== null) {
                $student_sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'";
            }
            $student_sql .= "
    ";
            $stmt = $conn->prepare($student_sql);
            $stmt->execute();
            $res = $stmt->get_result();
            $all_students = [];
            if ($res) {
                while ($r = $res->fetch_assoc()) $all_students[] = $r;
            }
            $stmt->close();

            if (empty($all_students)) {
                echo json_encode(['success' => true, 'message' => 'No students found', 'marked' => 0]);
                break;
            }

            $marked_count = 0;
            foreach ($all_students as $student) {
                $student_id = $student['student_id'];
                $roll_number = $student['roll_number'];
                $check = $conn->prepare("SELECT attendance_id FROM attendance WHERE student_id = ? AND date = ?");
                $check->bind_param('is', $student_id, $date);
                $check->execute();
                $exists = $check->get_result();
                if ($exists->num_rows === 0) {
                    $chkLeave = $conn->prepare("SELECT Leave_ID FROM leave_applications WHERE Reg_No = ? AND ? BETWEEN DATE(From_Date) AND DATE(To_Date) AND Status = 'out' LIMIT 1");
                    if ($chkLeave) {
                        $chkLeave->bind_param('ss', $roll_number, $date);
                        $chkLeave->execute();
                        $leaveRes = $chkLeave->get_result();
                        $hasLeave = ($leaveRes && $leaveRes->num_rows > 0);
                        $chkLeave->close();
                    } else {
                        $hasLeave = false;
                    }

                    if ($hasLeave) {
                        // Do not insert Absent for students on 'out' leave
                    } else {
                        $insert = $conn->prepare("INSERT INTO attendance (student_id, roll_number, date, status, marked_at) VALUES (?, ?, ?, 'Absent', NOW())");
                        $insert->bind_param('iss', $student_id, $roll_number, $date);
                        if ($insert->execute()) {
                            $marked_count++;
                        }
                        $insert->close();
                    }
                }
                $check->close();
            }
            echo json_encode(['success' => true, 'message' => "$marked_count students marked as absent", 'marked' => $marked_count]);
            break;

        case 'report_generation':
            $reportsRaw = $_POST['reports'] ?? $_GET['reports'] ?? null;
            if (is_array($reportsRaw)) {
                $reports = $reportsRaw;
            } elseif (!$reportsRaw) {
                $reports = [];
            } else {
                $decoded = @json_decode($reportsRaw, true);
                if (is_array($decoded)) {
                    $reports = $decoded;
                } elseif (is_string($reportsRaw)) {
                    $parts = array_filter(array_map('trim', explode(',', $reportsRaw)));
                    $reports = $parts;
                } else {
                    $reports = [];
                }
            }
            if (empty($reports)) $reports = ['present', 'late_entry', 'on_leave', 'absent', 'blocked'];
            $date = $_POST['selectedDate'] ?? $_GET['selectedDate'] ?? date('Y-m-d');
            $hostel_filter = trim($_POST['hostel_filter'] ?? $_GET['hostel_filter'] ?? '');
            $scopeGender = get_current_admin_scope_gender();
            $response = [];

            foreach ($reports as $typeRaw) {
                $type = strtolower(trim($typeRaw));
                $rows = [];
                if (in_array($type, ['present', 'absent', 'late_entry', 'on_leave'])) {
                    $dbStatus = null;
                    switch ($type) {
                        case 'present':
                            $sql = "SELECT s.roll_number, s.name, s.department, s.academic_batch, r.room_number, r.floor, a.marked_at, a.status
                    FROM attendance a
                    JOIN students s ON a.student_id = s.student_id
                    LEFT JOIN room_students rs ON s.student_id = rs.student_id AND rs.is_active = 1 AND rs.vacated_at IS NULL
                    LEFT JOIN rooms r ON rs.room_id = r.room_id";
                            if (!empty($hostel_filter)) {
                                $sql .= "
                        JOIN room_students rs2 ON s.student_id = rs2.student_id AND rs2.is_active = 1 AND rs2.vacated_at IS NULL
                        JOIN rooms r2 ON rs2.room_id = r2.room_id
                        JOIN hostels h ON r2.hostel_id = h.hostel_id AND h.hostel_name = ?
                    ";
                            }
                            $sql .= " WHERE a.date = ? AND (a.status = 'Present' OR LOWER(TRIM(a.status)) LIKE '%late%')";
                            if ($scopeGender !== null) {
                                $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'";
                            }
                            $sql .= " AND NOT EXISTS (
                    SELECT 1 FROM attendance a2 WHERE a2.student_id = a.student_id AND a2.date = a.date AND a2.status = 'On Leave'
                ) ORDER BY (FIELD(r.floor,'I','II','III','IV','V')=0), FIELD(r.floor,'I','II','III','IV','V'), r.room_number, s.roll_number";
                            $stmt = $conn->prepare($sql);
                            if (!empty($hostel_filter)) {
                                $stmt->bind_param('ss', $hostel_filter, $date);
                            } else {
                                $stmt->bind_param('s', $date);
                            }
                            break;

                        case 'absent':
                            $sql = "
                    SELECT s.roll_number, s.name, s.department, s.academic_batch, 
                           r.room_number, r.floor,
                           COALESCE(a.marked_at, '-') AS marked_at,
                           COALESCE(a.status, 'Absent') AS status
                    FROM students s
                    LEFT JOIN room_students rs ON s.student_id = rs.student_id AND rs.is_active = 1 AND rs.vacated_at IS NULL
                    LEFT JOIN rooms r ON rs.room_id = r.room_id
                ";
                            if (!empty($hostel_filter)) {
                                $sql .= "
                        JOIN room_students rs2 ON s.student_id = rs2.student_id AND rs2.is_active = 1 AND rs2.vacated_at IS NULL
                        JOIN rooms r2 ON rs2.room_id = r2.room_id
                        JOIN hostels h ON r2.hostel_id = h.hostel_id AND h.hostel_name = ?
                    ";
                            }
                            $sql .= "
                    LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = ?
                    WHERE s.status = '1' 
                            AND (a.status IS NULL OR a.status = 'Absent')";
                                    if ($scopeGender !== null) {
                                        $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'";
                                    }
                                    $sql .= "
                    AND NOT EXISTS (
                        SELECT 1 FROM leave_applications la WHERE la.Reg_No = s.roll_number AND ? BETWEEN DATE(la.From_Date) AND DATE(la.To_Date) AND la.Status = 'out'
                    )
                    ORDER BY (FIELD(r.floor,'I','II','III','IV','V')=0), FIELD(r.floor,'I','II','III','IV','V'), r.room_number, s.roll_number
                ";
                            $stmt = $conn->prepare($sql);
                            if (!empty($hostel_filter)) {
                                $stmt->bind_param('sss', $hostel_filter, $date, $date);
                            } else {
                                $stmt->bind_param('ss', $date, $date);
                            }
                            break;

                        case 'late_entry':
                            $sql = "SELECT s.roll_number, s.name, s.department, s.academic_batch, r.room_number, r.floor, a.marked_at, a.status
                    FROM attendance a
                    JOIN students s ON a.student_id = s.student_id
                    LEFT JOIN room_students rs ON s.student_id = rs.student_id AND rs.is_active = 1 AND rs.vacated_at IS NULL
                    LEFT JOIN rooms r ON rs.room_id = r.room_id";
                            if (!empty($hostel_filter)) {
                                $sql .= "
                        JOIN room_students rs2 ON s.student_id = rs2.student_id AND rs2.is_active = 1 AND rs2.vacated_at IS NULL
                        JOIN rooms r2 ON rs2.room_id = r2.room_id
                        JOIN hostels h ON r2.hostel_id = h.hostel_id AND h.hostel_name = ?
                    ";
                            }
                            $sql .= " WHERE a.date = ? AND LOWER(TRIM(a.status)) LIKE '%late%'";
                            if ($scopeGender !== null) {
                                $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'";
                            }
                            $sql .= " ORDER BY (FIELD(r.floor,'I','II','III','IV','V')=0), FIELD(r.floor,'I','II','III','IV','V'), r.room_number, s.roll_number";
                            $stmt = $conn->prepare($sql);
                            if (!empty($hostel_filter)) {
                                $stmt->bind_param('ss', $hostel_filter, $date);
                            } else {
                                $stmt->bind_param('s', $date);
                            }
                            break;
                        case 'on_leave':
                            if ($type === 'on_leave') {
                                $sql = "SELECT s.roll_number, s.name, s.department, s.academic_batch, r.room_number, r.floor, a.marked_at, COALESCE(a.status,'On Leave') AS status
                    FROM students s
                    LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = ?
                    LEFT JOIN room_students rs ON s.student_id = rs.student_id AND rs.is_active = 1 AND rs.vacated_at IS NULL
                    LEFT JOIN rooms r ON rs.room_id = r.room_id
                    LEFT JOIN leave_applications la ON s.roll_number = la.Reg_No AND ? BETWEEN DATE(la.From_Date) AND DATE(la.To_Date) AND la.Status = 'out'";
                                if (!empty($hostel_filter)) {
                                    $sql .= "
                        JOIN room_students rs2 ON s.student_id = rs2.student_id AND rs2.is_active = 1 AND rs2.vacated_at IS NULL
                        JOIN rooms r2 ON rs2.room_id = r2.room_id
                        JOIN hostels h ON r2.hostel_id = h.hostel_id AND h.hostel_name = ?
                    ";
                                }
                                $sql .= " WHERE (a.status = 'On Leave' OR la.Leave_ID IS NOT NULL)";
                                if ($scopeGender !== null) {
                                    $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'";
                                }
                                $sql .= " ORDER BY (FIELD(r.floor,'I','II','III','IV','V')=0), FIELD(r.floor,'I','II','III','IV','V'), r.room_number, s.roll_number";
                                $stmt = $conn->prepare($sql);
                                if (!empty($hostel_filter)) {
                                    $stmt->bind_param('sss', $date, $date, $hostel_filter);
                                } else {
                                    $stmt->bind_param('ss', $date, $date);
                                }
                                break;
                            }
                        default:
                            if (!isset($dbStatus)) $dbStatus = $dbStatus ?? null;
                            if ($dbStatus === null) {
                                continue 2;
                            }
                            $sql = "SELECT s.roll_number, s.name, s.department, s.academic_batch, r.room_number, r.floor, a.marked_at, a.status
                    FROM attendance a
                    JOIN students s ON a.student_id = s.student_id
                    LEFT JOIN room_students rs ON s.student_id = rs.student_id AND rs.is_active = 1 AND rs.vacated_at IS NULL
                    LEFT JOIN rooms r ON rs.room_id = r.room_id";
                            if (!empty($hostel_filter)) {
                                $sql .= "
                        JOIN room_students rs2 ON s.student_id = rs2.student_id AND rs2.is_active = 1 AND rs2.vacated_at IS NULL
                        JOIN rooms r2 ON rs2.room_id = r2.room_id
                        JOIN hostels h ON r2.hostel_id = h.hostel_id AND h.hostel_name = ?
                    ";
                            }
                            $sql .= " WHERE a.date = ? AND a.status = ?";
                            if ($scopeGender !== null) {
                                $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'";
                            }
                            $sql .= " ORDER BY (FIELD(r.floor,'I','II','III','IV','V')=0), FIELD(r.floor,'I','II','III','IV','V'), r.room_number, s.roll_number";
                            $stmt = $conn->prepare($sql);
                            if (!empty($hostel_filter)) {
                                $stmt->bind_param('sss', $hostel_filter, $date, $dbStatus);
                            } else {
                                $stmt->bind_param('ss', $date, $dbStatus);
                            }
                            break;
                    }
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $rows = [];
                    if ($res) {
                        while ($r = $res->fetch_assoc()) $rows[] = $r;
                    }
                    $stmt->close();
                } elseif ($type === 'blocked') {
                    $sql = "SELECT s.roll_number, s.name, s.department, s.academic_batch, r.room_number, r.floor, b.blocked_at AS blocked_at, b.id AS blocked_id, COALESCE(a.status, 'Not Marked') AS attendance_status
                    FROM blocked_students b
                    JOIN students s ON b.student_id = s.student_id
                    LEFT JOIN room_students rs ON s.student_id = rs.student_id AND rs.is_active = 1 AND rs.vacated_at IS NULL
                    LEFT JOIN rooms r ON rs.room_id = r.room_id
                    LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = ?
                    WHERE b.attendance_id IS NULL";
                    if ($scopeGender !== null) {
                        $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'";
                    }

                    if (!empty($hostel_filter)) {
                        $sql .= " AND EXISTS (
                        SELECT 1 FROM room_students rsx
                        JOIN rooms rx ON rsx.room_id = rx.room_id
                        JOIN hostels hx ON rx.hostel_id = hx.hostel_id
                        WHERE rsx.student_id = s.student_id AND rsx.is_active = 1 AND rsx.vacated_at IS NULL AND hx.hostel_name = ?
                    )";
                    }

                    $sql .= " ORDER BY (FIELD(r.floor,'I','II','III','IV','V')=0), FIELD(r.floor,'I','II','III','IV','V'), r.room_number, b.blocked_at DESC";

                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        echo json_encode(['success' => false, 'message' => 'DB prepare failed (blocked): ' . $conn->error, 'sql' => $sql], 500);
                        break;
                    }

                    if (!empty($hostel_filter)) {
                        if (!$stmt->bind_param('ss', $date, $hostel_filter)) {
                            $err = $stmt->error ?: $conn->error;
                            $stmt->close();
                            echo json_encode(['success' => false, 'message' => 'DB bind failed (blocked): ' . $err], 500);
                            break;
                        }
                    } else {
                        if (!$stmt->bind_param('s', $date)) {
                            $err = $stmt->error ?: $conn->error;
                            $stmt->close();
                            echo json_encode(['success' => false, 'message' => 'DB bind failed (blocked): ' . $err], 500);
                            break;
                        }
                    }

                    if (!$stmt->execute()) {
                        $err = $stmt->error ?: $conn->error;
                        $stmt->close();
                        echo json_encode(['success' => false, 'message' => 'DB execute failed (blocked): ' . $err, 'sql' => $sql], 500);
                        break;
                    }

                    $res = $stmt->get_result();
                    $rows = [];
                    if ($res) {
                        while ($r = $res->fetch_assoc()) $rows[] = $r;
                    }
                    $stmt->close();
                } elseif ($type === 'manual_present') {
                    $sql = "SELECT s.roll_number, s.name, s.department, s.academic_batch, r.room_number, r.floor, a.marked_at, a.reason, a.status
                    FROM attendance a
                    JOIN students s ON a.student_id = s.student_id
                    LEFT JOIN room_students rs ON s.student_id = rs.student_id AND rs.is_active = 1 AND rs.vacated_at IS NULL
                    LEFT JOIN rooms r ON rs.room_id = r.room_id";
                    if (!empty($hostel_filter)) {
                        $sql .= "
                        JOIN room_students rs2 ON s.student_id = rs2.student_id AND rs2.is_active = 1 AND rs2.vacated_at IS NULL
                        JOIN rooms r2 ON rs2.room_id = r2.room_id
                        JOIN hostels h ON r2.hostel_id = h.hostel_id AND h.hostel_name = ?
                    ";
                    }
                    $sql .= " WHERE a.date = ? AND (a.status = 'Present' OR LOWER(TRIM(a.status)) LIKE '%late%') AND a.reason IS NOT NULL AND TRIM(a.reason) != '' ORDER BY (FIELD(r.floor,'I','II','III','IV','V')=0), FIELD(r.floor,'I','II','III','IV','V'), r.room_number, s.roll_number";
                    $stmt = $conn->prepare($sql);
                    if (!empty($hostel_filter)) {
                        $stmt->bind_param('ss', $hostel_filter, $date);
                    } else {
                        $stmt->bind_param('s', $date);
                    }
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $rows = [];
                    if ($res) {
                        while ($r = $res->fetch_assoc()) $rows[] = $r;
                    }
                    $stmt->close();
                } elseif ($type === 'manual_leave') {
                    $sql = "SELECT s.roll_number, s.name, s.department, s.academic_batch, r.room_number, r.floor, a.marked_at, a.reason, a.status
                    FROM attendance a
                    JOIN students s ON a.student_id = s.student_id
                    LEFT JOIN room_students rs ON s.student_id = rs.student_id AND rs.is_active = 1 AND rs.vacated_at IS NULL
                    LEFT JOIN rooms r ON rs.room_id = r.room_id";
                    if (!empty($hostel_filter)) {
                        $sql .= "
                        JOIN room_students rs2 ON s.student_id = rs2.student_id AND rs2.is_active = 1 AND rs2.vacated_at IS NULL
                        JOIN rooms r2 ON rs2.room_id = r2.room_id
                        JOIN hostels h ON r2.hostel_id = h.hostel_id AND h.hostel_name = ?
                    ";
                    }
                    $sql .= " WHERE a.date = ? AND a.status = 'On Leave' AND a.reason IS NOT NULL AND TRIM(a.reason) != '' ORDER BY (FIELD(r.floor,'I','II','III','IV','V')=0), FIELD(r.floor,'I','II','III','IV','V'), r.room_number, s.roll_number";
                    $stmt = $conn->prepare($sql);
                    if (!empty($hostel_filter)) {
                        $stmt->bind_param('ss', $hostel_filter, $date);
                    } else {
                        $stmt->bind_param('s', $date);
                    }
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $rows = [];
                    if ($res) {
                        while ($r = $res->fetch_assoc()) $rows[] = $r;
                    }
                    $stmt->close();
                } else {
                    continue;
                }

                $response[$type] = $rows ?: [];
            }

            echo json_encode(['success' => true, 'date' => $date, 'generated_date' => date('Y-m-d H:i:s'), 'data' => $response]);
            break;

        case 'load_blocked':
            // Include admin_scope functions
            if (!file_exists(__DIR__ . '/admin/admin_scope.php')) {
                echo json_encode(['success' => false, 'message' => 'Admin scope file not found']);
                break;
            }
            include_once __DIR__ . '/admin/admin_scope.php';
            
            $hostel_filter = trim($_POST['hostel_filter'] ?? $_GET['hostel_filter'] ?? '');
            $genderScope = get_hostel_gender_scope_for_role();
            
            $sql = "
            SELECT 
                s.roll_number,
                s.name,
                s.department,
                s.academic_batch,
                r.room_number,
                b.blocked_at,
                b.id AS blocked_id,
                b.reason,
                b.type
            FROM blocked_students b
            JOIN students s ON b.student_id = s.student_id
            LEFT JOIN room_students rs 
                ON s.student_id = rs.student_id 
                AND rs.is_active = 1 
                AND rs.vacated_at IS NULL
            LEFT JOIN rooms r ON rs.room_id = r.room_id
            LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
            WHERE b.unblocked_at IS NULL
        ";

            // Add gender scope filter for male_admin and female_admin
            if ($genderScope !== null) {
                $sql .= " AND h.gender = '" . addslashes($genderScope) . "'";
            }

            if ($hostel_filter !== '') {
                // Allow filtering by numeric hostel_id or by hostel_name (fallback)
                if (ctype_digit($hostel_filter)) {
                    $sql .= " AND EXISTS (
                    SELECT 1 FROM room_students rsx
                    JOIN rooms rx ON rsx.room_id = rx.room_id
                    JOIN hostels hx ON rx.hostel_id = hx.hostel_id
                    WHERE rsx.student_id = s.student_id AND rsx.is_active = 1 AND rsx.vacated_at IS NULL AND hx.hostel_id = ?
                )";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('i', $hostel_filter);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $sql .= " AND EXISTS (
                    SELECT 1 FROM room_students rsx
                    JOIN rooms rx ON rsx.room_id = rx.room_id
                    JOIN hostels hx ON rx.hostel_id = hx.hostel_id
                    WHERE rsx.student_id = s.student_id AND rsx.is_active = 1 AND rsx.vacated_at IS NULL AND hx.hostel_name = ?
                )";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('s', $hostel_filter);
                    $stmt->execute();
                    $result = $stmt->get_result();
                }
            } else {
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $result = $stmt->get_result();
            }

            $students = [];
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }

            echo json_encode(['success' => true, 'data' => $students]);
            break;

        case 'unblock_student':

            $blocked_id = intval($_POST['blocked_id'] ?? 0);

            if ($blocked_id > 0) {
                $sql = "UPDATE blocked_students SET unblocked_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $blocked_id);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Student unblocked']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to unblock']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid blocked ID']);
            }
            break;

        case 'unblock_all':
            // Unblock all currently blocked students (set unblocked_at)
            $sql = "UPDATE blocked_students SET unblocked_at = NOW() WHERE unblocked_at IS NULL";
            if ($conn->query($sql) === TRUE) {
                $affected = $conn->affected_rows;
                echo json_encode(['success' => true, 'message' => 'Unblocked all students', 'affected' => $affected]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            }
            break;

        case 'block_student':
            // Include admin_scope functions
            if (!file_exists(__DIR__ . '/admin/admin_scope.php')) {
                echo json_encode(['success' => false, 'message' => 'Admin scope file not found']);
                break;
            }
            include_once __DIR__ . '/admin/admin_scope.php';
            
            $roll_number = $_POST['roll_number'] ?? '';
            $reason = $_POST['reason'] ?? '';
            $type = $_POST['type'] ?? '';

            if (!$roll_number || !$reason) {
                echo json_encode(['success' => false, 'message' => 'Roll number and reason are required']);
                break;
            }

            // Get gender scope for current admin
            $genderScope = get_hostel_gender_scope_for_role();
            
            // Find student and verify they belong to admin's gender scope
            if ($genderScope !== null) {
                // For male_admin and female_admin, verify student is in their gender's hostel
                $stmt = $conn->prepare("
                    SELECT s.student_id 
                    FROM students s
                    LEFT JOIN room_students rs ON s.student_id = rs.student_id 
                        AND rs.is_active = 1 
                        AND rs.vacated_at IS NULL
                    LEFT JOIN rooms r ON rs.room_id = r.room_id
                    LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                    WHERE s.roll_number = ? AND h.gender = ?
                    LIMIT 1
                ");
                $stmt->bind_param("ss", $roll_number, $genderScope);
            } else {
                // For super admin, no gender restriction
                $stmt = $conn->prepare("SELECT student_id FROM students WHERE roll_number = ?");
                $stmt->bind_param("s", $roll_number);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Student not found']);
                $stmt->close();
                break;
            }

            $student = $result->fetch_assoc();
            $student_id = $student['student_id'];
            $stmt->close();

            //checking block status
            $stmt = $conn->prepare("SELECT id FROM blocked_students WHERE student_id = ? AND unblocked_at IS NULL");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $blockedResult = $stmt->get_result();

            if ($blockedResult->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Student already blocked']);
                $stmt->close();
                break;
            }
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO blocked_students (student_id, reason, type) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $student_id, $reason, $type);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Student blocked successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to block student']);
            }
            $stmt->close();
            break;

        case 'add_restriction':
            require_any_admin_api_role();
            
            $roll_number = sanitize_input($_POST['roll_number'] ?? '');
            $restriction_type = sanitize_input($_POST['restriction_type'] ?? '');
            
            if (empty($roll_number) || empty($restriction_type)) {
                echo json_encode(['success' => false, 'message' => 'Roll number and restriction type are required']);
                break;
            }
            
            if (!in_array($restriction_type, ['leave', 'outing'], true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid restriction type. Must be "leave" or "outing"']);
                break;
            }
            
            // Check student exists
            $stmt = $conn->prepare("SELECT student_id FROM students WHERE roll_number = ? LIMIT 1");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error']);
                break;
            }
            $stmt->bind_param("s", $roll_number);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Student not found']);
                $stmt->close();
                break;
            }
            
            $student = $result->fetch_assoc();
            $student_id = $student['student_id'];
            $stmt->close();
            
            // Check if restriction already exists and not removed
            $stmt = $conn->prepare("SELECT restriction_id FROM student_restrictions WHERE roll_number = ? AND restriction_type = ? AND removed_at IS NULL LIMIT 1");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error']);
                break;
            }
            $stmt->bind_param("ss", $roll_number, $restriction_type);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Student already has this restriction']);
                $stmt->close();
                break;
            }
            $stmt->close();
            
            // Add restriction
            $created_by = get_current_session_role();
            $stmt = $conn->prepare("INSERT INTO student_restrictions (student_id, roll_number, restriction_type, created_by) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error']);
                break;
            }
            $stmt->bind_param("isss", $student_id, $roll_number, $restriction_type, $created_by);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Restriction added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add restriction']);
            }
            $stmt->close();
            break;

        case 'remove_restriction':
            require_any_admin_api_role();
            
            $restriction_id = filter_var($_POST['restriction_id'] ?? 0, FILTER_VALIDATE_INT);
            
            if ($restriction_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid restriction ID']);
                break;
            }
            
            // Check restriction exists
            $stmt = $conn->prepare("SELECT restriction_id FROM student_restrictions WHERE restriction_id = ? AND removed_at IS NULL LIMIT 1");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error']);
                break;
            }
            $stmt->bind_param("i", $restriction_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Restriction not found']);
                $stmt->close();
                break;
            }
            $stmt->close();
            
            // Remove restriction
            $stmt = $conn->prepare("UPDATE student_restrictions SET removed_at = NOW() WHERE restriction_id = ?");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error']);
                break;
            }
            $stmt->bind_param("i", $restriction_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Restriction removed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove restriction']);
            }
            $stmt->close();
            break;

        case 'fetch_restrictions':
            require_any_admin_api_role();
            
            $filter_type = sanitize_input($_POST['filter_type'] ?? $_GET['filter_type'] ?? '');
            $search_roll = sanitize_input($_POST['search_roll'] ?? $_GET['search_roll'] ?? '');
            
            // Build query
            $sql = "SELECT 
                        sr.restriction_id, 
                        sr.student_id,
                        sr.roll_number,
                        sr.restriction_type,
                        sr.created_by,
                        sr.created_at,
                        s.name,
                        s.gender
                    FROM student_restrictions sr
                    LEFT JOIN students s ON sr.student_id = s.student_id
                    WHERE sr.removed_at IS NULL";
            
            $params = [];
            $types = "";
            
            if (!empty($filter_type)) {
                $sql .= " AND sr.restriction_type = ?";
                $params[] = $filter_type;
                $types .= "s";
            }
            
            if (!empty($search_roll)) {
                $sql .= " AND sr.roll_number LIKE ?";
                $search_roll = "%{$search_roll}%";
                $params[] = $search_roll;
                $types .= "s";
            }
            
            $sql .= " ORDER BY sr.created_at DESC";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error']);
                break;
            }
            
            if (!empty($params)) {
                $bind_names = [$types];
                for ($i = 0; $i < count($params); $i++) {
                    $bind_var_name = 'param_' . $i;
                    $$bind_var_name = $params[$i];
                    $bind_names[] = &$$bind_var_name;
                }
                call_user_func_array([$stmt, 'bind_param'], $bind_names);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $restrictions = [];
            while ($row = $result->fetch_assoc()) {
                $restrictions[] = $row;
            }
            $stmt->close();
            
            echo json_encode(['success' => true, 'restrictions' => $restrictions]);
            break;

        case 'load_late_attendance':
            // Include admin_scope functions
            if (!file_exists(__DIR__ . '/admin/admin_scope.php')) {
                echo json_encode(['success' => false, 'message' => 'Admin scope file not found']);
                break;
            }
            include_once __DIR__ . '/admin/admin_scope.php';
            
            $hostel_filter = trim($_POST['hostel_filter'] ?? $_GET['hostel_filter'] ?? '');
            $genderScope = get_hostel_gender_scope_for_role();
            
            $sql = "
            SELECT 
                s.student_id,
                s.roll_number,
                s.name,
                s.department,
                s.academic_batch,
                r.room_number,
                a.date as attendance_date,
                TIME(a.marked_at) as entry_time,
                a.status
            FROM attendance a
            JOIN students s ON a.student_id = s.student_id
            LEFT JOIN room_students rs 
                ON s.student_id = rs.student_id 
                AND rs.is_active = 1 
                AND rs.vacated_at IS NULL
            LEFT JOIN rooms r ON rs.room_id = r.room_id
            LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
            WHERE a.status = 'Late Entry'
        ";

            // Add gender scope filter for male_admin and female_admin
            if ($genderScope !== null) {
                $sql .= " AND h.gender = '" . addslashes($genderScope) . "'";
            }

            if ($hostel_filter !== '') {
                // Allow filtering by numeric hostel_id or by hostel_name (fallback)
                if (ctype_digit($hostel_filter)) {
                    $sql .= " AND EXISTS (
                    SELECT 1 FROM room_students rsx
                    JOIN rooms rx ON rsx.room_id = rx.room_id
                    JOIN hostels hx ON rx.hostel_id = hx.hostel_id
                    WHERE rsx.student_id = s.student_id AND rsx.is_active = 1 AND rsx.vacated_at IS NULL AND hx.hostel_id = ?
                )";
                    $sql .= " ORDER BY a.date DESC, a.marked_at DESC";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('i', $hostel_filter);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $sql .= " AND EXISTS (
                    SELECT 1 FROM room_students rsx
                    JOIN rooms rx ON rsx.room_id = rx.room_id
                    JOIN hostels hx ON rx.hostel_id = hx.hostel_id
                    WHERE rsx.student_id = s.student_id AND rsx.is_active = 1 AND rsx.vacated_at IS NULL AND hx.hostel_name = ?
                )";
                    $sql .= " ORDER BY a.date DESC, a.marked_at DESC";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('s', $hostel_filter);
                    $stmt->execute();
                    $result = $stmt->get_result();
                }
            } else {
                $sql .= " ORDER BY a.date DESC, a.marked_at DESC";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $result = $stmt->get_result();
            }

            $late_entries = [];
            while ($row = $result->fetch_assoc()) {
                $late_entries[] = $row;
            }

            echo json_encode(['success' => true, 'data' => $late_entries]);
            break;

        case 'mark_manual_present':
            try {
                $roll_number = trim($_POST['roll_number'] ?? '');
                $selectedDate = $_POST['selectedDate'] ?? date('Y-m-d');
                $reason = trim($_POST['reason'] ?? '');

                if (!$roll_number) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Roll number required']);
                    break;
                }

                if (!$reason) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Reason required']);
                    break;
                }

                if (!isset($conn) || $conn === null) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Database connection error']);
                    break;
                }

                $s = $conn->prepare("SELECT student_id FROM students WHERE roll_number = ? LIMIT 1");
                if (!$s) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Database query error: ' . $conn->error]);
                    break;
                }
                $s->bind_param('s', $roll_number);
                $s->execute();
                $sr = $s->get_result();

                if ($sr->num_rows === 0) {
                    $s->close();
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Student not found']);
                    break;
                }

                $student = $sr->fetch_assoc();
                $student_id = (int)$student['student_id'];
                $s->close();

                // Determine if present or late entry based on late_entry_time
                $late_time = null;
                $timeRes = $conn->query("SELECT late_entry_time FROM attendance_time_control WHERE status = 'enabled' ORDER BY id DESC LIMIT 1");
                if ($timeRes && $timeRes->num_rows > 0) {
                    $row = $timeRes->fetch_assoc();
                    if (!empty($row['late_entry_time'])) $late_time = $row['late_entry_time'];
                }
                $current_time = date('H:i:s');
                $statusToSet = ($late_time && strtotime($current_time) > strtotime($late_time)) ? 'Late Entry' : 'Present';

                // Check if attendance record exists
                $chk = $conn->prepare("SELECT attendance_id FROM attendance WHERE student_id = ? AND date = ?");
                if (!$chk) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Database query error: ' . $conn->error]);
                    break;
                }
                $chk->bind_param('is', $student_id, $selectedDate);
                $chk->execute();
                $chkResult = $chk->get_result();

                if ($chkResult->num_rows > 0) {
                    $update = $conn->prepare("UPDATE attendance SET status = ?, reason = ?, marked_at = NOW() WHERE student_id = ? AND date = ?");
                    if (!$update) {
                        ob_clean();
                        echo json_encode(['success' => false, 'message' => 'Database query error: ' . $conn->error]);
                        $chk->close();
                        break;
                    }
                    $update->bind_param('ssis', $statusToSet, $reason, $student_id, $selectedDate);
                } else {
                    $update = $conn->prepare("INSERT INTO attendance (student_id, roll_number, date, status, reason, marked_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    if (!$update) {
                        ob_clean();
                        echo json_encode(['success' => false, 'message' => 'Database query error: ' . $conn->error]);
                        $chk->close();
                        break;
                    }
                    $update->bind_param('issss', $student_id, $roll_number, $selectedDate, $statusToSet, $reason);
                }

                if ($update->execute()) {
                    $update->close();
                    $chk->close();
                    ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Student marked as ' . $statusToSet . ' successfully']);
                } else {
                    $err = $conn->error;
                    $update->close();
                    $chk->close();
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Failed to mark ' . $statusToSet . ': ' . $err]);
                }
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'mark_manual_leave':
            try {
                $roll_number = trim($_POST['roll_number'] ?? '');
                $reason = trim($_POST['reason'] ?? '');
                $leave_type_id = intval($_POST['leave_type_id'] ?? 0);
                $selectedDate = $_POST['selectedDate'] ?? date('Y-m-d');

                if (!$roll_number || !$reason) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Roll number and reason required']);
                    break;
                }

                if (!isset($conn) || $conn === null) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Database connection error']);
                    break;
                }

                // Lookup student_id
                $s = $conn->prepare("SELECT student_id FROM students WHERE roll_number = ? LIMIT 1");
                if (!$s) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Database query error: ' . $conn->error]);
                    break;
                }
                $s->bind_param('s', $roll_number);
                $s->execute();
                $sr = $s->get_result();
                if ($sr->num_rows === 0) {
                    $s->close();
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Student not found']);
                    break;
                }
                $student = $sr->fetch_assoc();
                $student_id = (int)$student['student_id'];
                $s->close();

                // Ensure leave application exists for on_leave when leave_type_id provided
                if ($leave_type_id > 0) {
                    $chkLeave = $conn->prepare("SELECT Leave_ID FROM leave_applications WHERE Reg_No = ? AND DATE(From_Date) = ? AND DATE(To_Date) = ?");
                    if ($chkLeave) {
                        $chkLeave->bind_param('sss', $roll_number, $selectedDate, $selectedDate);
                        $chkLeave->execute();
                        $leaveResult = $chkLeave->get_result();
                        if ($leaveResult->num_rows === 0) {
                            $insLeave = $conn->prepare("INSERT INTO leave_applications (Reg_No, LeaveType_ID, From_Date, To_Date, Reason, Status) VALUES (?, ?, ?, ?, ?, 'out')");
                            if ($insLeave) {
                                $insLeave->bind_param('sisss', $roll_number, $leave_type_id, $selectedDate, $selectedDate, $reason);
                                $insLeave->execute();
                                $insLeave->close();
                            }
                        }
                        $chkLeave->close();
                    }
                }

                $statusToSet = 'On Leave';
                $chk = $conn->prepare("SELECT attendance_id FROM attendance WHERE student_id = ? AND date = ?");
                if (!$chk) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Database query error: ' . $conn->error]);
                    break;
                }
                $chk->bind_param('is', $student_id, $selectedDate);
                $chk->execute();
                $chkResult = $chk->get_result();

                if ($chkResult->num_rows > 0) {
                    $update = $conn->prepare("UPDATE attendance SET status = ?, reason = ?, marked_at = NOW() WHERE student_id = ? AND date = ?");
                    if (!$update) {
                        ob_clean();
                        echo json_encode(['success' => false, 'message' => 'Database query error: ' . $conn->error]);
                        $chk->close();
                        break;
                    }
                    $update->bind_param('ssis', $statusToSet, $reason, $student_id, $selectedDate);
                } else {
                    $update = $conn->prepare("INSERT INTO attendance (student_id, roll_number, date, status, reason, marked_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    if (!$update) {
                        ob_clean();
                        echo json_encode(['success' => false, 'message' => 'Database query error: ' . $conn->error]);
                        $chk->close();
                        break;
                    }
                    $update->bind_param('issss', $student_id, $roll_number, $selectedDate, $statusToSet, $reason);
                }

                if ($update->execute()) {
                    $update->close();
                    $chk->close();
                    ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Student marked as ' . $statusToSet . ' successfully']);
                } else {
                    $err = $conn->error;
                    $update->close();
                    $chk->close();
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Failed to mark ' . $statusToSet . ': ' . $err]);
                }
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'attget_hostels':
            $out = [];
            $scopeGender = get_current_admin_scope_gender();
            if ($scopeGender !== null) {
                $stmt = $conn->prepare("SELECT hostel_id, hostel_name FROM hostels WHERE gender = ? ORDER BY hostel_name");
                $stmt->bind_param('s', $scopeGender);
                $stmt->execute();
                $q = $stmt->get_result();
                while ($r = $q->fetch_assoc()) {
                    $out[] = $r;
                }
                $stmt->close();
            } else {
                $q = $conn->query("SELECT hostel_id, hostel_name FROM hostels ORDER BY hostel_name");
                if ($q) {
                    while ($r = $q->fetch_assoc()) {
                        $out[] = $r;
                    }
                }
            }
            echo json_encode(['success' => true, 'data' => $out]);
            break;
        case 'get_attendance_time':
            $hostel_id = trim($_POST['hostel_id'] ?? $_GET['hostel_id'] ?? '');
            $out = null;

            // Prefer hostel-specific enabled record, fallback to global (hostel_id IS NULL)
            if ($hostel_id !== '' && ctype_digit($hostel_id)) {
                $stmt = $conn->prepare("SELECT * FROM attendance_time_control WHERE status='enabled' AND hostel_id = ? ORDER BY id DESC LIMIT 1");
                if ($stmt) {
                    $hid = (int)$hostel_id;
                    $stmt->bind_param('i', $hid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows > 0) $out = $res->fetch_assoc();
                    $stmt->close();
                }
            }

            if ($out === null) {
                // Global or any enabled record
                $stmt2 = $conn->prepare("SELECT * FROM attendance_time_control WHERE status='enabled' AND (hostel_id IS NULL OR hostel_id = '') ORDER BY id DESC LIMIT 1");
                if ($stmt2) {
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();
                    if ($res2 && $res2->num_rows > 0) $out = $res2->fetch_assoc();
                    $stmt2->close();
                }
            }

            if ($out) {
                // Format human-readable times
                $out['from_time_display'] = date('h:i A', strtotime($out['from_time']));
                $out['to_time_display'] = date('h:i A', strtotime($out['to_time']));
                $out['late_entry_time_display'] = date('h:i A', strtotime($out['late_entry_time']));
                echo json_encode(['success' => true, 'data' => $out]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No active attendance time found']);
            }
            break;
        // Faculty 

        case 'create_faculty':
            // Get form data
            $faculty_id = $_POST['faculty_id'] ?? '';
            $f_name = $_POST['faculty_name'] ?? '';
            $department = $_POST['faculty_department'] ?? '';
            $designation = $_POST['designation'] ?? '';
            $role = $_POST['role'] ?? '';
            $additional_role = $_POST['additional_role'] ?? '';
            $phone_number = $_POST['faculty_mobile'] ?? '';
            $email = $_POST['faculty_email'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $dob = $_POST['dob'] ?? '';
            $date_of_join = $_POST['date_of_join'] ?? '';
            $fingerprint_id = $_POST['faculty_fingerprint_id'] ?? '';
            $aadhaar_number = $_POST['aadhaar_number'] ?? '';
            $hostel_id = $_POST['faculty_hostel'] ?? null;
            $room_id = $_POST['faculty_room'] ?? null;

            if (!empty($hostel_id) && !is_hostel_id_allowed_for_current_admin($conn, (int)$hostel_id)) {
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized hostel access.']);
                exit;
            }

            // Validate required fields
            if (empty($faculty_id) || empty($f_name) || empty($department) || empty($designation) || empty($gender)) {
                echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields.']);
                exit;
            }

            // Begin transaction
            $conn->begin_transaction();

            try {
                // Insert into hostel_faculty table
                $insertQuery = "INSERT INTO hostel_faculty (faculty_id, f_name, department, designation, role, additional_role, phone_number, email, gender, dob, date_of_join, fingerprint_id, aadhaar_number, room_id, hostel_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param('ssssssssssssssi', $faculty_id, $f_name, $department, $designation, $role, $additional_role, $phone_number, $email, $gender, $dob, $date_of_join, $fingerprint_id, $aadhaar_number, $room_id, $hostel_id);

                if ($insertStmt->execute()) {
                    // If room is assigned, update room occupancy
                    if ($room_id && $hostel_id) {
                        $updateRoomQuery = "UPDATE rooms SET occupied = occupied + 1 WHERE room_id = ?";
                        $updateRoomStmt = $conn->prepare($updateRoomQuery);
                        $updateRoomStmt->bind_param('i', $room_id);
                        $updateRoomStmt->execute();
                        $updateRoomStmt->close();
                    }

                    $conn->commit();
                    echo json_encode(['status' => 'success', 'message' => 'Faculty registered successfully.']);
                } else {
                    throw new Exception("Failed to insert faculty record.");
                }

                $insertStmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
            }
            exit;

        case 'list_faculty':
                        $scopeGender = get_current_admin_scope_gender();
            // Query to get faculty data with room and hostel information
            $query = "SELECT hf.*, h.hostel_name, r.room_number 
              FROM hostel_faculty hf 
              LEFT JOIN hostels h ON hf.hostel_id = h.hostel_id 
              LEFT JOIN rooms r ON hf.room_id = r.room_id 
                            WHERE hf.status = '1' 
              ORDER BY hf.f_name";

            if ($scopeGender !== null) {
                                $query = "SELECT hf.*, h.hostel_name, r.room_number 
                            FROM hostel_faculty hf 
                            LEFT JOIN hostels h ON hf.hostel_id = h.hostel_id 
                            LEFT JOIN rooms r ON hf.room_id = r.room_id 
                            WHERE hf.status = '1' AND h.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'
                            ORDER BY hf.f_name";
            }

            $result = $conn->query($query);

            if ($result) {
                $faculty_data = [];
                while ($row = $result->fetch_assoc()) {
                    $faculty_data[] = $row;
                }
                echo json_encode(['success' => true, 'data' => $faculty_data]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to fetch faculty data.']);
            }
            exit;

        case 'get_faculty':
            $faculty_id = $_POST['faculty_id'] ?? '';
            $scopeGender = get_current_admin_scope_gender();

            if (empty($faculty_id)) {
                echo json_encode(['success' => false, 'message' => 'Faculty ID is required.']);
                exit;
            }

            // Query to get specific faculty data with room and hostel information
                        $query = "SELECT hf.*, h.hostel_name, r.room_number 
              FROM hostel_faculty hf 
              LEFT JOIN hostels h ON hf.hostel_id = h.hostel_id 
              LEFT JOIN rooms r ON hf.room_id = r.room_id 
              WHERE hf.f_id = ? AND hf.status = '1'";

                        if ($scopeGender !== null) {
                                $query .= " AND h.gender = '" . mysqli_real_escape_string($conn, $scopeGender) . "'";
                        }

            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $faculty_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $faculty_data = $result->fetch_assoc();
                echo json_encode(['success' => true, 'data' => $faculty_data]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Faculty not found.']);
            }

            $stmt->close();
            exit;

        case 'delete_faculty':
            $faculty_id = $_POST['faculty_id'] ?? '';

            if (empty($faculty_id)) {
                echo json_encode(['success' => false, 'message' => 'Faculty ID is required.']);
                exit;
            }

            // Update faculty status to 0 (inactive)
            $query = "UPDATE hostel_faculty SET status = '0' WHERE f_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $faculty_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Faculty deleted successfully.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Faculty not found.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete faculty.']);
            }

            $stmt->close();
            exit;

        case 'update_faculty':
            // Get form data
            $faculty_id = $_POST['faculty_id'] ?? '';
            $f_id = $_POST['f_id'] ?? '';
            $f_name = $_POST['faculty_name'] ?? '';
            $department = $_POST['faculty_department'] ?? '';
            $designation = $_POST['designation'] ?? '';
            $role = $_POST['role'] ?? '';
            $additional_role = $_POST['additional_role'] ?? '';
            $phone_number = $_POST['faculty_mobile'] ?? '';
            $email = $_POST['faculty_email'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $dob = $_POST['dob'] ?? '';
            $date_of_join = $_POST['date_of_join'] ?? '';
            $fingerprint_id = $_POST['faculty_fingerprint_id'] ?? '';
            $aadhaar_number = $_POST['aadhaar_number'] ?? '';
            $hostel_id = $_POST['faculty_hostel'] ?? null;
            $room_id = $_POST['faculty_room'] ?? null;

            if (!empty($hostel_id) && !is_hostel_id_allowed_for_current_admin($conn, (int)$hostel_id)) {
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized hostel access.']);
                exit;
            }

            // Validate required fields
            if (empty($f_id) || empty($f_name) || empty($department) || empty($designation) || empty($gender)) {
                echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields.']);
                exit;
            }

            // Begin transaction
            $conn->begin_transaction();

            try {
                // Update faculty record
                $updateQuery = "UPDATE hostel_faculty SET faculty_id = ?, f_name = ?, department = ?, designation = ?, role = ?, additional_role = ?, phone_number = ?, email = ?, gender = ?, dob = ?, date_of_join = ?, fingerprint_id = ?, aadhaar_number = ?, room_id = ?, hostel_id = ? WHERE f_id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param('sssssssssssssiii', $faculty_id, $f_name, $department, $designation, $role, $additional_role, $phone_number, $email, $gender, $dob, $date_of_join, $fingerprint_id, $aadhaar_number, $room_id, $hostel_id, $f_id);

                if (!$updateStmt->execute()) {
                    throw new Exception("Failed to update faculty record.");
                }

                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Faculty record updated successfully!']);

                $updateStmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
            }
            exit;

        case 'get_faculty_rooms':
            $hostel_id = $_POST['hostel_id'] ?? '';
            $block = $_POST['block'] ?? '';
            $floor = $_POST['floor'] ?? '';

            if (!is_hostel_id_allowed_for_current_admin($conn, (int)$hostel_id)) {
                echo "<option value=''>Unauthorized hostel access</option>";
                exit;
            }

            if (empty($hostel_id) || empty($block) || empty($floor)) {
                echo "<option value=''>Invalid parameters</option>";
                exit;
            }

            // Query to get available rooms (empty or already occupied by faculty only)
            $query = "SELECT r.room_id, r.room_number, r.capacity, r.occupied,
                     (SELECT COUNT(*) FROM hostel_faculty hf WHERE hf.room_id = r.room_id AND hf.status = '1') as faculty_count
              FROM rooms r 
              WHERE r.hostel_id = ? AND r.block = ? AND r.floor = ? 
              AND (r.occupied = 0 OR r.room_id IN (SELECT DISTINCT room_id FROM hostel_faculty WHERE room_id IS NOT NULL AND status = '1'))
              ORDER BY r.room_number";

            $stmt = $conn->prepare($query);
            $stmt->bind_param('iss', $hostel_id, $block, $floor);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $available = $row['capacity'] - $row['faculty_count'];
                    // Always show the room if it has availability or is already occupied by faculty
                    echo "<option value='{$row['room_id']}'>{$row['room_number']} (Available: {$available})</option>";
                }
            } else {
                echo "<option value=''>No rooms available</option>";
            }

            $stmt->close();
            exit;
        case 'fapprove':
            $id = $_POST['id'] ?? '';

            if ($id) {
                $status = "Forwarded to Admin";
                $remarks = "Approved by HOD";

                $update_sql = "UPDATE leave_applications SET Status=?, Remarks=? WHERE leave_id=?";
                $stmt = mysqli_prepare($conn, $update_sql);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sss", $status, $remarks, $id);
                    if (mysqli_stmt_execute($stmt)) {
                        echo json_encode(['status' => 'success', 'message' => 'Leave forwarded to Admin successfully.']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_stmt_error($stmt)]);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
            }
            break;


        case 'freject':
            $id = $_POST['id'] ?? '';
            $rejectionreason = $_POST['rejectionreason'] ?? '';

            // 1. Basic validation
            if (empty($id) || empty($rejectionreason)) {
                echo json_encode(['status' => 'error', 'message' => 'Missing Leave ID or Rejection Reason.']);
                break;
            }

            // 2. Define the new status and sanitize inputs
            // Assuming the current user is the HOD
            $status = 'Rejected by HOD';

            // Use mysqli_real_escape_string for security (even with prepared statements, it's a good habit)
            $leave_id = mysqli_real_escape_string($conn, $id);
            $remarks = mysqli_real_escape_string($conn, $rejectionreason);

            // 3. Prepare the update statement
            // Set the Status and the Remarks (reason) for the rejection
            $update_sql = "UPDATE leave_applications SET Status=?, Remarks=? WHERE Leave_ID=?";
            $stmt = mysqli_prepare($conn, $update_sql);

            if ($stmt) {
                // The 'ssi' represents: string (Status), string (Remarks), integer (Leave_ID)
                mysqli_stmt_bind_param($stmt, "ssi", $status, $remarks, $leave_id);

                if (mysqli_stmt_execute($stmt)) {
                    // Success Response
                    echo json_encode(['status' => 'success', 'message' => 'Leave successfully rejected and moved to processed list.']);
                } else {
                    // Execution Error Response
                    error_log("Reject SQL Execute Error: " . mysqli_stmt_error($stmt));
                    echo json_encode(['status' => 'error', 'message' => 'Database error: Failed to execute rejection update.']);
                }
                mysqli_stmt_close($stmt);
            } else {
                // Prepare Statement Error Response
                error_log("Reject SQL Prepare Error: " . mysqli_error($conn));
                echo json_encode(['status' => 'error', 'message' => 'Database error: Failed to prepare rejection statement.']);
            }
            break;

        case 'get_statistics':
            require_mess_access_role();
            $messScopeGender = get_mess_scope_gender();
            // Get statistics
            $stats = [];
            $result = $conn->query("SELECT COUNT(*) as count FROM specialtokenenable");
            $stats['total_special_tokens'] = $result->fetch_assoc()['count'];
            $result = $conn->query("SELECT COUNT(*) as count FROM mess_menu");
            $stats['total_menus'] = $result->fetch_assoc()['count'];
            $sql = "SELECT COUNT(*) as count FROM mess_tokens mt LEFT JOIN students s ON mt.roll_number = s.roll_number";
            if ($messScopeGender !== null) {
                $sql .= " WHERE s.gender = '" . mysqli_real_escape_string($conn, $messScopeGender) . "'";
            }
            $result = $conn->query($sql);
            $stats['total_tokens'] = $result->fetch_assoc()['count'];
            $result = $conn->query("SELECT COALESCE(SUM(fee), 0) as total FROM specialtokenenable");
            $stats['total_special_token_fees'] = $result->fetch_assoc()['total'];
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'create_menu':
            // Create menu
            $date = $_POST['date'] ?? '';
            $meal_type = $_POST['meal_type'] ?? '';
            $items = $_POST['items'] ?? '';
            $category = $_POST['category'] ?? null;
            $fee = $_POST['fee'] ?? 0.00;

            if (empty($date) || empty($meal_type) || empty($items)) {
                echo json_encode(['success' => false, 'message' => 'Required fields missing']);
                break;
            }
            $stmt = $conn->prepare("INSERT INTO mess_menu (date, meal_type, items, category, fee) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssd", $date, $meal_type, $items, $category, $fee);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Menu created', 'id' => $conn->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => $conn->error]);
            }
            $stmt->close();
            break;

        case 'read_menus':
            // Read menus
            $result = $conn->query("SELECT * FROM mess_menu ORDER BY created_at DESC");
            $menus = [];
            while ($row = $result->fetch_assoc()) {
                $menus[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $menus]);
            break;

        case 'update_menu':
            // Update menu
            $menu_id = intval($_POST['menu_id']);
            $date = $_POST['date'];
            $meal_type = $_POST['meal_type'];
            $items = $_POST['items'];
            $category = $_POST['category'] ?? null;
            $fee = floatval($_POST['fee']);
            $stmt = $conn->prepare("UPDATE mess_menu SET date=?, meal_type=?, items=?, category=?, fee=? WHERE menu_id=?");
            $stmt->bind_param("ssssdi", $date, $meal_type, $items, $category, $fee, $menu_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Menu updated']);
            } else {
                echo json_encode(['success' => false, 'message' => $conn->error]);
            }
            $stmt->close();
            break;

        case 'delete_menu':
            // Delete menu
            $menu_id = intval($_POST['menu_id']);
            $stmt = $conn->prepare("DELETE FROM mess_menu WHERE menu_id=?");
            $stmt->bind_param("i", $menu_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Menu deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => $conn->error]);
            }
            $stmt->close();
            break;

        case 'create_special_token':
            try {
                require_mess_access_role();

                $from_date  = $_POST['from_date'] ?? '';
                $from_time  = $_POST['from_time'] ?? '';
                $to_date    = $_POST['to_date'] ?? '';
                $to_time    = $_POST['to_time'] ?? '';
                $token_date = $_POST['token_date'] ?? '';
                $meal_type  = $_POST['meal_type'] ?? '';
                $menu_items = $_POST['menu_items'] ?? '';
                $fee        = floatval($_POST['fee'] ?? 0.00);
                $max_usage  = intval($_POST['max_usage'] ?? -1);

                if (!$from_date || !$from_time || !$to_date || !$to_time || !$token_date || !$meal_type || !$menu_items) {
                    echo json_encode(['success' => false, 'message' => 'All fields are required']);
                    break;
                }

                // Insert without free_limit - it will use default value
                $stmt = $conn->prepare("INSERT INTO specialtokenenable (from_date, from_time, to_date, to_time, token_date, meal_type, menu_items, fee, max_usage, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");

                if (!$stmt) {
                    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
                    break;
                }

                $stmt->bind_param("sssssssdi", $from_date, $from_time, $to_date, $to_time, $token_date, $meal_type, $menu_items, $fee, $max_usage);

                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Special token created successfully',
                        'id' => $conn->insert_id
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
                }

                $stmt->close();
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
            }
            break;


        case 'read_special_tokens':
            require_mess_access_role();
            // Read active special tokens (only status='active' and not expired)
            $sql = "SELECT * FROM specialtokenenable WHERE status='active' AND DATE_FORMAT(CONCAT(to_date, ' ', to_time), '%Y-%m-%d %H:%i:%s') > NOW() ORDER BY created_at DESC";
            $result = $conn->query($sql);
            $tokens = [];
            while ($row = $result->fetch_assoc()) {
                $tokens[] = $row;
            }
            echo json_encode(['success' => true, 'data' => $tokens]);
            break;

        case 'read_inactive_special_tokens':
            require_mess_access_role();

            $sql = "
        SELECT *
        FROM specialtokenenable
        WHERE status='inactive' OR (DATE_FORMAT(CONCAT(to_date,' ',to_time),'%Y-%m-%d %H:%i:%s') <= NOW() AND status != 'ended')
        ORDER BY created_at DESC
    ";

            $result = $conn->query($sql);
            $tokens = [];

            while ($row = $result->fetch_assoc()) {
                $tokens[] = $row;
            }

            echo json_encode(['success' => true, 'data' => $tokens]);
            break;


        case 'activate_special_token':
            try {
                $menu_id = intval($_POST['menu_id'] ?? 0);

                if (!$menu_id) {
                    echo json_encode(['success' => false, 'message' => 'Invalid token ID']);
                    break;
                }

                $stmt = $conn->prepare("UPDATE specialtokenenable SET status='active' WHERE menu_id=?");
                if (!$stmt) {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                    break;
                }

                $stmt->bind_param("i", $menu_id);

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        echo json_encode(['success' => true, 'message' => 'Token activated successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Token not found or already active']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error executing query: ' . $stmt->error]);
                }

                $stmt->close();
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'update_special_token':
            require_mess_access_role();

            $menu_id    = intval($_POST['menu_id']);
            $from_date  = $_POST['from_date'] ?? null;
            $from_time  = $_POST['from_time'] ?? null;
            $to_date    = $_POST['to_date'];
            $to_time    = $_POST['to_time'];
            $token_date = $_POST['token_date'] ?? null;
            $meal_type  = $_POST['meal_type'] ?? null;
            $menu_items = $_POST['menu_items'] ?? null;
            $fee        = $_POST['fee'] ?? null;

            // NEW
            $max_usage  = isset($_POST['max_usage']) ? intval($_POST['max_usage']) : null;

            if ($from_date && $from_time && $token_date && $meal_type && $menu_items && $fee !== null && $max_usage !== null) {

                $stmt = $conn->prepare("
            UPDATE specialtokenenable
            SET from_date=?, from_time=?, to_date=?, to_time=?, token_date=?,
                meal_type=?, menu_items=?, fee=?, max_usage=?, status='active'
            WHERE menu_id=?
        ");

                $stmt->bind_param(
                    "sssssssdis",
                    $from_date,
                    $from_time,
                    $to_date,
                    $to_time,
                    $token_date,
                    $meal_type,
                    $menu_items,
                    $fee,
                    $max_usage,
                    $menu_id
                );
            } else {

                $stmt = $conn->prepare("
            UPDATE specialtokenenable
            SET to_date=?, to_time=?, status='active'
            WHERE menu_id=?
        ");

                $stmt->bind_param("ssi", $to_date, $to_time, $menu_id);
            }

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Token updated']);
            } else {
                echo json_encode(['success' => false, 'message' => $conn->error]);
            }

            $stmt->close();
            break;


        case 'end_special_token':
            require_mess_access_role();
            // End special token
            $menu_id = intval($_POST['menu_id']);
            $stmt = $conn->prepare("UPDATE specialtokenenable SET status='ended' WHERE menu_id=?");
            $stmt->bind_param("i", $menu_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Token ended and moved to history']);
            } else {
                echo json_encode(['success' => false, 'message' => $conn->error]);
            }
            $stmt->close();
            break;

        case 'delete_special_token':
            require_mess_access_role();
            // Delete special token
            $menu_id = intval($_POST['menu_id']);
            $stmt = $conn->prepare("DELETE FROM specialtokenenable WHERE menu_id=?");
            $stmt->bind_param("i", $menu_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Special token deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => $conn->error]);
            }
            $stmt->close();
            break;

        case 'deactivate_special_token':
            require_mess_access_role();
            // Deactivate special token - change status to inactive
            $menu_id = intval($_POST['menu_id']);
            $stmt = $conn->prepare("UPDATE specialtokenenable SET status='inactive' WHERE menu_id=?");
            $stmt->bind_param("i", $menu_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Token deactivated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => $conn->error]);
            }
            $stmt->close();
            break;

        case 'activate_special_token_status':
            require_mess_access_role();
            // Activate special token - change status to active
            $menu_id = intval($_POST['menu_id']);
            $stmt = $conn->prepare("UPDATE specialtokenenable SET status='active' WHERE menu_id=?");
            $stmt->bind_param("i", $menu_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Token activated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => $conn->error]);
            }
            $stmt->close();
            break;

        case 'create_token':
            // Create token
            $roll_number = $_POST['roll_number'] ?? '';
            $menu_id = intval($_POST['menu_id'] ?? 0);
            $token_type = $_POST['token_type'] ?? 'Regular';
            $from_date = $_POST['from_date'] ?? null;
            $to_date = $_POST['to_date'] ?? null;
            $special_fee = floatval($_POST['special_fee'] ?? 0.00);
            $supervisor_id = !empty($_POST['supervisor_id']) ? intval($_POST['supervisor_id']) : null;

            if (empty($roll_number) || empty($menu_id)) {
                echo json_encode(['success' => false, 'message' => 'Student roll number and menu ID are required']);
                break;
            }
            $valid_token_types = ['Regular', 'Special'];
            if (!in_array($token_type, $valid_token_types)) {
                echo json_encode(['success' => false, 'message' => 'Invalid token type']);
                break;
            }
            $checkMenuSql = "SELECT menu_id FROM mess_menu WHERE menu_id = ?";
            $checkMenuStmt = $conn->prepare($checkMenuSql);
            $checkMenuStmt->bind_param("i", $menu_id);
            $checkMenuStmt->execute();
            $checkMenuResult = $checkMenuStmt->get_result();
            if ($checkMenuResult->num_rows == 0) {
                echo json_encode(['success' => false, 'message' => 'Menu does not exist']);
                $checkMenuStmt->close();
                break;
            }
            $checkMenuStmt->close();
            if (!empty($from_date) && !empty($to_date)) {
                if (strtotime($from_date) > strtotime($to_date)) {
                    echo json_encode(['success' => false, 'message' => 'From date cannot be later than to date']);
                    break;
                }
            }
            $sql = "INSERT INTO mess_tokens (roll_number, menu_id, token_type, from_date, to_date, special_fee, supervisor_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sisssdi", $roll_number, $menu_id, $token_type, $from_date, $to_date, $special_fee, $supervisor_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Token issued', 'id' => $conn->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => $conn->error]);
            }
            $stmt->close();
            break;

        case 'read_tokens':
            // Read tokens
            $sql = "SELECT t.token_id, t.roll_number, t.menu_id, t.token_type, t.token_date, t.meal_type, t.menu as menu_items, t.special_fee, t.created_at, s.name as student_name FROM mess_tokens t LEFT JOIN students s ON t.roll_number = s.roll_number ORDER BY t.created_at DESC";
            $result = $conn->query($sql);
            $tokens = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $tokens[] = $row;
                }
            }
            echo json_encode(['success' => true, 'data' => $tokens, 'count' => count($tokens)]);
            break;

        case 'delete_token':
            // Delete token
            if (empty($_POST['token_id'])) {
                echo json_encode(['success' => false, 'message' => 'Token ID required']);
                break;
            }
            $token_id = intval($_POST['token_id']);
            $checkSql = "SELECT token_id FROM mess_tokens WHERE token_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("i", $token_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult->num_rows == 0) {
                echo json_encode(['success' => false, 'message' => 'Token not found']);
                $checkStmt->close();
                break;
            }
            $checkStmt->close();
            $sql = "DELETE FROM mess_tokens WHERE token_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $token_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Token deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => $conn->error]);
            }
            $stmt->close();
            break;

        case 'get_filtered_tokens':
            // Get filtered tokens
            $filterMonth = $_POST['filter_month'] ?? '';
            $filterDate = $_POST['filter_date'] ?? '';
            $filterMealType = $_POST['filter_meal_type'] ?? '';
            $filterItem = $_POST['filter_item'] ?? '';

            $sql = "SELECT t.token_id, t.roll_number, t.menu_id, t.token_type, t.token_date, t.meal_type, 
                               t.menu as menu_items, t.special_fee, t.created_at, 
                               s.name as student_name 
                        FROM mess_tokens t 
                        LEFT JOIN students s ON t.roll_number = s.roll_number 
                        WHERE t.token_type = 'Special'";

            $params = [];
            $types = '';

            if (!empty($filterDate)) {
                $sql .= " AND t.token_date = ?";
                $params[] = $filterDate;
                $types .= 's';
            } elseif (!empty($filterMonth)) {
                $sql .= " AND DATE_FORMAT(t.token_date, '%Y-%m') = ?";
                $params[] = $filterMonth;
                $types .= 's';
            }

            if (!empty($filterMealType)) {
                $sql .= " AND t.meal_type = ?";
                $params[] = $filterMealType;
                $types .= 's';
            }

            if (!empty($filterItem)) {
                $sql .= " AND t.menu LIKE ?";
                $searchTerm = '%' . $filterItem . '%';
                $params[] = $searchTerm;
                $types .= 's';
            }

            $sql .= " ORDER BY t.created_at DESC LIMIT 1000";

            if (!empty($params)) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $conn->query($sql);
            }

            $tokens = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $tokens[] = $row;
                }
            }

            echo json_encode(['success' => true, 'data' => $tokens, 'count' => count($tokens)]);

            if (!empty($params)) {
                $stmt->close();
            }
            break;

        case 'get_menu_history':
            // Get menu history
            $sql = "SELECT 'Menu' as type, menu_id as id, COALESCE(date, 'N/A') as date, COALESCE(meal_type, 'N/A') as details, COALESCE(items, 'No items') as description, COALESCE(fee, 0) as amount, created_at as timestamp FROM mess_menu ORDER BY created_at DESC";
            $result = $conn->query($sql);
            $history = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $history[] = $row;
                }
            }
            echo json_encode(['success' => true, 'data' => $history, 'count' => count($history)]);
            break;

        case 'get_token_history':
            // Get token history
            $sql = "SELECT 'Token' as type, t.token_id as id, COALESCE(DATE(t.created_at), 'N/A') as date, COALESCE(t.token_type, 'Regular') as details, CONCAT('Roll: ', COALESCE(t.roll_number, 'Unknown')) as description, 0 as amount, t.created_at as timestamp FROM mess_tokens t ORDER BY t.created_at DESC";
            $result = $conn->query($sql);
            $history = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $history[] = $row;
                }
            }
            echo json_encode(['success' => true, 'data' => $history, 'count' => count($history)]);
            break;

        case 'get_special_token_history':
            // Get special token history
            $sql = "SELECT 'Special Token' as type, menu_id as id, COALESCE(token_date, 'N/A') as date, COALESCE(meal_type, 'N/A') as details, COALESCE(menu_items, 'No items') as description, COALESCE(fee, 0) as amount, created_at as timestamp FROM specialtokenenable WHERE status = 'ended' ORDER BY created_at DESC";
            $result = $conn->query($sql);
            $history = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $history[] = $row;
                }
            }
            echo json_encode(['success' => true, 'data' => $history, 'count' => count($history)]);
            break;

        case 'get_all_history':
            // Get all history
            $allHistory = [];
            $sql = "SELECT 'Menu' as type, menu_id as id, COALESCE(date, 'N/A') as date, COALESCE(meal_type, 'N/A') as details, COALESCE(items, 'No items') as description, COALESCE(fee, 0) as amount, created_at as timestamp FROM mess_menu";
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $allHistory[] = $row;
                }
            }
            $sql = "SELECT 'Token' as type, token_id as id, COALESCE(DATE(created_at), 'N/A') as date, COALESCE(token_type, 'Regular') as details, CONCAT('Roll: ', COALESCE(roll_number, 'Unknown')) as description, 0 as amount, created_at as timestamp FROM mess_tokens";
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $allHistory[] = $row;
                }
            }
            $sql = "SELECT 'Special Token' as type, menu_id as id, COALESCE(token_date, 'N/A') as date, COALESCE(meal_type, 'N/A') as details, COALESCE(menu_items, 'No items') as description, COALESCE(fee, 0) as amount, created_at as timestamp FROM specialtokenenable WHERE status = 'ended'";
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $allHistory[] = $row;
                }
            }
            usort($allHistory, function ($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });
            echo json_encode(['success' => true, 'data' => $allHistory, 'count' => count($allHistory)]);
            break;

        case 'get_revenue':
            require_mess_access_role();
            $messScopeGender = get_mess_scope_gender();
            // Get revenue
            $filterMonth = $_POST['filter_month'] ?? '';

            if (empty($filterMonth)) {
                echo json_encode(['success' => false, 'message' => 'Month filter is required']);
                break;
            }

            $sql = "SELECT mt.token_date as date, 
                               COUNT(mt.token_id) as tokens_count, 
                               COALESCE(SUM(mt.special_fee), 0) as revenue 
                        FROM mess_tokens mt 
                                                LEFT JOIN students s ON mt.roll_number = s.roll_number
                        WHERE mt.token_type = 'Special' 
                                                    AND DATE_FORMAT(mt.token_date, '%Y-%m') = ?";
                        if ($messScopeGender !== null) {
                                $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $messScopeGender) . "'";
                        }
                        $sql .= " 
                        GROUP BY mt.token_date 
                        ORDER BY mt.token_date DESC";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $filterMonth);
            $stmt->execute();
            $result = $stmt->get_result();

            $revenue = [];
            while ($row = $result->fetch_assoc()) {
                $revenue[] = $row;
            }

            echo json_encode(['success' => true, 'data' => $revenue]);
            $stmt->close();
            break;

        case 'get_consumption':
            require_mess_access_role();
            $messScopeGender = get_mess_scope_gender();
            // Get consumption
            $filterMonth = $_POST['filter_month'] ?? '';

            if (empty($filterMonth)) {
                echo json_encode(['success' => false, 'message' => 'Month filter is required']);
                break;
            }

            $sql = "SELECT mt.roll_number, 
                               COALESCE(s.name, 'Unknown') as student_name, 
                               COUNT(mt.token_id) as tokens_count, 
                               COALESCE(SUM(mt.special_fee), 0) as total_spent 
                        FROM mess_tokens mt 
                        LEFT JOIN students s ON mt.roll_number = s.roll_number 
                        WHERE mt.token_type = 'Special' 
                                                    AND DATE_FORMAT(mt.token_date, '%Y-%m') = ?";
                        if ($messScopeGender !== null) {
                                $sql .= " AND s.gender = '" . mysqli_real_escape_string($conn, $messScopeGender) . "'";
                        }
                        $sql .= " 
                        GROUP BY mt.roll_number, s.name 
                        ORDER BY total_spent DESC";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $filterMonth);
            $stmt->execute();
            $result = $stmt->get_result();

            $consumption = [];
            while ($row = $result->fetch_assoc()) {
                $consumption[] = $row;
            }

            echo json_encode(['success' => true, 'data' => $consumption]);
            $stmt->close();
            break;


        // rooms

        // ========== STUDENT API ACTIONS INTEGRATION ==========

        // ========== STUDENT: LEAVE MANAGEMENT ==========
        case 'apply_leave':
            try {
                // Authenticate student
                $user_id = null;
                $roll_no = null;
                $student_gender = '';
                $student_id = 0;
                if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
                    $user_id = $_SESSION['user_id'];

                // Get roll number for authenticated users
                    $stmt = $conn->prepare("SELECT s.student_id, s.roll_number, s.gender, s.user_id AS student_user_id, u.username FROM users u INNER JOIN students s ON s.user_id = u.user_id WHERE u.user_id = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();

                    if ($user) {
                            $student_id = (int)($user['student_id'] ?? 0);
                        $roll_no = $user['roll_number'];
                        $student_gender = strtolower(trim($user['gender'] ?? ''));
                    }
                }
            }

                if (!$user_id || !$roll_no || $student_id <= 0) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    break;
                }

                // If student is actively blocked, they cannot apply any leave category.
                $stmt_blocked = $conn->prepare("SELECT id FROM blocked_students WHERE student_id = ? AND unblocked_at IS NULL LIMIT 1");
                if ($stmt_blocked) {
                    $stmt_blocked->bind_param("i", $student_id);
                    $stmt_blocked->execute();
                    $blocked_result = $stmt_blocked->get_result();
                    $is_blocked = $blocked_result && $blocked_result->num_rows > 0;
                    $stmt_blocked->close();

                    if ($is_blocked) {
                        echo json_encode([
                            'success' => false,
                            'errors' => ['You are blocked by admin and cannot apply leave.']
                        ]);
                        break;
                    }
                }

            // Fetch general leave setting with gender scope fallback to common(admin)
            $general_leave_setting = ['Is_Enabled' => 0];
            if ($student_gender === 'male') {
                $stmt_gl = $conn->prepare("SELECT GeneralLeave_ID, From_Date, To_Date, Is_Enabled, Enabled_By FROM general_leave WHERE Is_Enabled = 1 AND (Enabled_By = 'male_admin' OR Enabled_By = 'admin' OR Enabled_By = '' OR Enabled_By IS NULL) ORDER BY CASE WHEN Enabled_By = 'male_admin' THEN 0 WHEN Enabled_By = 'admin' THEN 1 ELSE 2 END, GeneralLeave_ID DESC LIMIT 1");
            } elseif ($student_gender === 'female') {
                $stmt_gl = $conn->prepare("SELECT GeneralLeave_ID, From_Date, To_Date, Is_Enabled, Enabled_By FROM general_leave WHERE Is_Enabled = 1 AND (Enabled_By = 'female_admin' OR Enabled_By = 'admin' OR Enabled_By = '' OR Enabled_By IS NULL) ORDER BY CASE WHEN Enabled_By = 'female_admin' THEN 0 WHEN Enabled_By = 'admin' THEN 1 ELSE 2 END, GeneralLeave_ID DESC LIMIT 1");
            } else {
                $stmt_gl = $conn->prepare("SELECT GeneralLeave_ID, From_Date, To_Date, Is_Enabled, Enabled_By FROM general_leave WHERE Is_Enabled = 1 AND (Enabled_By = 'admin' OR Enabled_By = '' OR Enabled_By IS NULL) ORDER BY GeneralLeave_ID DESC LIMIT 1");
            }
            if ($stmt_gl) {
                $stmt_gl->execute();
                $result_gl = $stmt_gl->get_result();
                if ($result_gl->num_rows > 0) {
                    $general_leave_setting = $result_gl->fetch_assoc();
                }
                $stmt_gl->close();
            }

            $errors = [];
            $leave_id = sanitize_input(isset($_POST['leave_id']) ? $_POST['leave_id'] : '');
            $leave_type_id = sanitize_input(isset($_POST['leave_type_id']) ? $_POST['leave_type_id'] : '');
            $from_date = sanitize_input(isset($_POST['from_date']) ? $_POST['from_date'] : '');
            $from_time = sanitize_input(isset($_POST['from_time']) ? $_POST['from_time'] : '');
            $from_ampm = strtoupper(sanitize_input(isset($_POST['from_ampm']) ? $_POST['from_ampm'] : ''));
            $to_date = sanitize_input(isset($_POST['to_date']) ? $_POST['to_date'] : '');
            $to_time = sanitize_input(isset($_POST['to_time']) ? $_POST['to_time'] : '');
            $to_ampm = strtoupper(sanitize_input(isset($_POST['to_ampm']) ? $_POST['to_ampm'] : ''));
            $from_minute = sanitize_input(isset($_POST['from_minute']) ? $_POST['from_minute'] : '00');
            $to_minute = sanitize_input(isset($_POST['to_minute']) ? $_POST['to_minute'] : '00');
            $reason = sanitize_input(isset($_POST['reason']) ? $_POST['reason'] : '');

            if (empty($leave_type_id) && empty($leave_id)) {
                $errors[] = 'Leave type is required.';
            }
            if (empty($from_date) || empty($to_date) || empty($from_time) || empty($to_time)) {
                $errors[] = 'All date and time fields are required.';
            }
            if (empty($reason)) {
                $errors[] = 'Reason for leave is required.';
            }

            $start_datetime_obj = null;
            $end_datetime_obj = null;
            $start_datetime = null;
            $end_datetime = null;

            try {
                $from_datetime_str = "$from_date $from_time:$from_minute $from_ampm";
                $to_datetime_str = "$to_date $to_time:$to_minute $to_ampm";
                $start_datetime_obj = new DateTime($from_datetime_str);
                $end_datetime_obj = new DateTime($to_datetime_str);
                $start_datetime = $start_datetime_obj->format('Y-m-d H:i:s');
                $end_datetime = $end_datetime_obj->format('Y-m-d H:i:s');
                if ($start_datetime_obj >= $end_datetime_obj) {
                    $errors[] = 'From datetime must be before To datetime.';
                }
            } catch (Exception $e) {
                $errors[] = 'Invalid date or time format received.';
            }

            if (!empty($start_datetime_obj) && !empty($end_datetime_obj)) {
                $type_name = '';

                if (!empty($leave_id)) {
                    $stmt_check = $conn->prepare("SELECT lt.Leave_Type_Name FROM leave_applications la INNER JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID WHERE la.Leave_ID = ? AND la.Reg_No = ? LIMIT 1");
                    if ($stmt_check) {
                        $leave_id_int = (int) $leave_id;
                        $stmt_check->bind_param("is", $leave_id_int, $roll_no);
                        $stmt_check->execute();
                        $result_check = $stmt_check->get_result();
                        $leave_type_data = $result_check->fetch_assoc();
                        $stmt_check->close();
                        if ($leave_type_data) {
                            $type_name = strtolower($leave_type_data['Leave_Type_Name']);
                        }
                    }
                } elseif (!empty($leave_type_id)) {
                    $stmt_check = $conn->prepare("SELECT Leave_Type_Name FROM leave_types WHERE LeaveType_ID = ?");
                    if ($stmt_check) {
                        $stmt_check->bind_param("i", $leave_type_id);
                        $stmt_check->execute();
                        $result_check = $stmt_check->get_result();
                        $leave_type_data = $result_check->fetch_assoc();
                        $stmt_check->close();
                        if ($leave_type_data) {
                            $type_name = strtolower($leave_type_data['Leave_Type_Name']);
                        }
                    }
                }

                if (str_contains($type_name, 'general')) {
                    if (!($general_leave_setting['Is_Enabled'] ?? 0)) {
                        $errors[] = 'General Leave applications are currently disabled.';
                    } else {
                        try {
                            $allowed_from_db = new DateTime($general_leave_setting['From_Date']);
                            $allowed_to_db = new DateTime($general_leave_setting['To_Date']);
                            if ($start_datetime_obj < $allowed_from_db || $end_datetime_obj > $allowed_to_db) {
                                $errors[] = 'General Leave dates must be within the allowed period.';
                            }
                        } catch (Exception $e) {
                            $errors[] = 'Internal error with leave period dates.';
                        }
                    }
                }

                if (str_contains($type_name, 'outing')) {
                    if ($student_gender === 'female') {
                        $errors[] = 'Outing leave is not available for girls.';
                    }

                    $today = new DateTime('today');
                    $today_str = $today->format('Y-m-d');
                    $from_date_only = $start_datetime_obj->format('Y-m-d');
                    $to_date_only = $end_datetime_obj->format('Y-m-d');

                    if ($from_date_only !== $today_str || $to_date_only !== $today_str) {
                        $errors[] = 'Outing can be applied only for today.';
                    }

                    $fixed_outing_time = $today_str . ' 23:30:00';
                    if ($end_datetime_obj->format('Y-m-d H:i:s') !== $fixed_outing_time) {
                        $errors[] = 'For outing, return time must be exactly 11:30 PM.';
                    }
                }
            }

            $proof_file = '';
            if (!empty($_FILES['proof']['name'])) {
                $file = $_FILES['proof'];
                $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];

                if (in_array($file['type'], $allowed_types) && $file['error'] === UPLOAD_ERR_OK) {
                    $target_dir = "Student/proofs/";
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $file_extension = pathinfo($file["name"], PATHINFO_EXTENSION);
                    $proof_file = $target_dir . uniqid() . '_' . time() . '.' . $file_extension;
                    if (!move_uploaded_file($file["tmp_name"], $proof_file)) {
                        $errors[] = "Failed to upload proof file.";
                        $proof_file = '';
                    }
                } else if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = "Only JPG, PNG, and PDF files are allowed.";
                }
            }

            if (empty($errors)) {
                if (!empty($leave_id)) {
                    // Build UPDATE query dynamically
                    $sql = "UPDATE leave_applications SET From_Date=?, To_Date=?, Reason=?";
                    $params = [$start_datetime, $end_datetime, $reason];
                    $types = "sss";

                    if ($proof_file !== '') {
                        $sql .= ", Proof=?";
                        $types .= "s";
                        $params[] = $proof_file;
                    }

                    $sql .= " WHERE Leave_ID=? AND Reg_No=? AND Status='Pending'";
                    // Leave_ID is integer, Reg_No is string
                    $types .= "is";
                    $params[] = (int)$leave_id;
                    $params[] = $roll_no;

                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        // mysqli_stmt::bind_param requires arguments passed by reference.
                        // Build an array of references for call_user_func_array.
                        $bind_names = [];
                        $bind_names[] = $types;
                        for ($i = 0; $i < count($params); $i++) {
                            // Create variable variables so we can pass by reference
                            $bind_var_name = 'bind_' . $i;
                            $$bind_var_name = $params[$i];
                            $bind_names[] = &$$bind_var_name;
                        }

                        // Bind and execute
                        if (call_user_func_array([$stmt, 'bind_param'], $bind_names) && $stmt->execute()) {
                            if ($stmt->affected_rows > 0) {
                                echo json_encode(['success' => true, 'message' => 'Leave application updated successfully!']);
                            } else {
                                $errors[] = "Failed to update leave application.";
                            }
                        } else {
                            $errors[] = "Database error: " . ($stmt ? $stmt->error : $conn->error);
                        }
                        $stmt->close();
                    } else {
                        $errors[] = "Database error: " . $conn->error;
                    }
                } else {
                    if (empty($leave_type_id)) {
                        $errors[] = "Leave type is required for a new application.";
                    } else {
                        // Check if student has restrictions for this leave type
                        $restriction_type = '';
                        if (!empty($type_name)) {
                            if (str_contains($type_name, 'outing')) {
                                $restriction_type = 'outing';
                            } elseif (str_contains($type_name, 'leave') || str_contains($type_name, 'Leave')) {
                                $restriction_type = 'leave';
                            }
                        }

                        if (!empty($restriction_type)) {
                            try {
                                $stmt_restrict = $conn->prepare("SELECT restriction_id FROM student_restrictions WHERE roll_number = ? AND restriction_type = ? AND removed_at IS NULL LIMIT 1");
                                if ($stmt_restrict) {
                                    $stmt_restrict->bind_param("ss", $roll_no, $restriction_type);
                                    $stmt_restrict->execute();
                                    $restrict_result = $stmt_restrict->get_result();
                                    if ($restrict_result->num_rows > 0) {
                                        $errors[] = "You are restricted from applying for " . ucfirst($restriction_type) . " by admin.";
                                    }
                                    $stmt_restrict->close();
                                }
                            } catch (Throwable $restrictionError) {
                                // If restriction table/schema is unavailable, skip this optional check
                            }
                        }

                        if (empty($errors)) {
                            $stmt = $conn->prepare("INSERT INTO leave_applications (Reg_No, LeaveType_ID, From_Date, To_Date, Reason, Proof, Status, Applied_Date) VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())");
                            if ($stmt) {
                                // Correct types: Reg_No (string), LeaveType_ID (integer), rest strings
                                $stmt->bind_param("sissss", $roll_no, $leave_type_id, $start_datetime, $end_datetime, $reason, $proof_file);
                                if ($stmt->execute()) {
                                    echo json_encode(['success' => true, 'message' => 'Leave application submitted successfully!']);
                                } else {
                                    $errors[] = "Failed to apply for leave: " . $stmt->error;
                                }
                                $stmt->close();
                            } else {
                                $errors[] = "Database error: " . $conn->error;
                            }
                        }
                    }
                }
            }

                if (!empty($errors)) {
                    echo json_encode(['success' => false, 'errors' => $errors]);
                }
            } catch (Throwable $e) {
                http_response_code(200);
                echo json_encode([
                    'success' => false,
                    'errors' => ['Unable to submit leave application right now. Please try again.']
                ]);
            }
            break;

        case 'cancel_leave':
            // Authenticate student
            $user_id = null;
            $roll_no = null;
            if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
                $user_id = $_SESSION['user_id'];

                // Get roll number for authenticated users
                $stmt = $conn->prepare("SELECT s.roll_number, s.user_id AS student_user_id, u.username FROM users u INNER JOIN students s ON s.user_id = u.user_id WHERE u.user_id = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();

                    if ($user) {
                        $roll_no = $user['roll_number'];
                    }
                }
            }

            if (!$user_id || !$roll_no) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }

            $leave_id = filter_var($_POST['leave_id'] ?? 0, FILTER_VALIDATE_INT);
            if ($leave_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid leave ID']);
                break;
            }

            $verify = $conn->prepare("SELECT Reg_No FROM leave_applications WHERE Leave_ID = ? AND Reg_No = ? LIMIT 1");
            if (!$verify) {
                echo json_encode(['success' => false, 'message' => 'Database error']);
                break;
            }
            $verify->bind_param("is", $leave_id, $roll_no);
            $verify->execute();
            $verify_result = $verify->get_result();
            if ($verify_result->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                $verify->close();
                break;
            }
            $verify->close();

            $stmt = $conn->prepare("UPDATE leave_applications SET Status='Cancelled' WHERE Leave_ID=? AND Reg_No=? AND Status='Pending'");
            if ($stmt) {
                $stmt->bind_param("is", $leave_id, $roll_no);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        echo json_encode(['success' => true, 'message' => 'Leave cancelled successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Leave already processed']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
                }
                $stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
            break;

        case 'get_leaves':
            // Authenticate student
            $user_id = null;
            $roll_no = null;
            $student_gender = '';
            if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
                $user_id = $_SESSION['user_id'];

                // Get roll number for authenticated users
                $stmt = $conn->prepare("SELECT s.roll_number, s.gender, s.user_id AS student_user_id, u.username FROM users u LEFT JOIN students s ON s.user_id = u.user_id WHERE u.user_id = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    if (!$stmt->execute()) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
                        $stmt->close();
                        break;
                    }
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();

                    if ($user) {
                        $roll_no = !empty($user['roll_number']) ? $user['roll_number'] : $user['username'];
                        $student_gender = strtolower(trim($user['gender'] ?? ''));
                    }
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                    break;
                }
            }

            if (!$user_id || !$roll_no) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }

            // Fetch general leave setting with gender scope fallback to common(admin)
            $general_leave_setting = ['Is_Enabled' => 0];
            if ($student_gender === 'male') {
                $stmt_gl = $conn->prepare("SELECT GeneralLeave_ID, From_Date, To_Date, Is_Enabled, Enabled_By FROM general_leave WHERE Is_Enabled = 1 AND (Enabled_By = 'male_admin' OR Enabled_By = 'admin' OR Enabled_By = '' OR Enabled_By IS NULL) ORDER BY CASE WHEN Enabled_By = 'male_admin' THEN 0 WHEN Enabled_By = 'admin' THEN 1 ELSE 2 END, GeneralLeave_ID DESC LIMIT 1");
            } elseif ($student_gender === 'female') {
                $stmt_gl = $conn->prepare("SELECT GeneralLeave_ID, From_Date, To_Date, Is_Enabled, Enabled_By FROM general_leave WHERE Is_Enabled = 1 AND (Enabled_By = 'female_admin' OR Enabled_By = 'admin' OR Enabled_By = '' OR Enabled_By IS NULL) ORDER BY CASE WHEN Enabled_By = 'female_admin' THEN 0 WHEN Enabled_By = 'admin' THEN 1 ELSE 2 END, GeneralLeave_ID DESC LIMIT 1");
            } else {
                $stmt_gl = $conn->prepare("SELECT GeneralLeave_ID, From_Date, To_Date, Is_Enabled, Enabled_By FROM general_leave WHERE Is_Enabled = 1 AND (Enabled_By = 'admin' OR Enabled_By = '' OR Enabled_By IS NULL) ORDER BY GeneralLeave_ID DESC LIMIT 1");
            }
            if ($stmt_gl) {
                if ($stmt_gl->execute()) {
                    $result_gl = $stmt_gl->get_result();
                    if ($result_gl->num_rows > 0) {
                        $general_leave_setting = $result_gl->fetch_assoc();
                    }
                }
                $stmt_gl->close();
            }

            $rows = [];
            $sql = "SELECT la.Leave_ID, la.From_Date, la.To_Date, la.Reason, la.Proof, la.Status, la.Applied_Date, 
                     lt.Leave_Type_Name, la.LeaveType_ID
                FROM leave_applications la
                LEFT JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
                WHERE la.Reg_No = ?
                ORDER BY la.Applied_Date DESC";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Prepare error: ' . $conn->error]);
                break;
            }
            
            $stmt->bind_param("s", $roll_no);
            if (!$stmt->execute()) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Execute error: ' . $stmt->error]);
                $stmt->close();
                break;
            }
            
            $result = $stmt->get_result();
            if (!$result) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Get result error: ' . $stmt->error]);
                $stmt->close();
                break;
            }
            
            while ($r = $result->fetch_assoc()) {
                $rows[] = $r;
            }
            $stmt->close();

            // Fetch leave types
            $leave_types = [];
            if ($student_gender === 'female') {
                $stmt_lt = $conn->prepare("SELECT LeaveType_ID, Leave_Type_Name FROM leave_types WHERE LOWER(Leave_Type_Name) NOT LIKE '%outing%' ORDER BY Priority ASC, Leave_Type_Name ASC");
            } else {
                $stmt_lt = $conn->prepare("SELECT LeaveType_ID, Leave_Type_Name FROM leave_types ORDER BY Priority ASC, Leave_Type_Name ASC");
            }
            
            if ($stmt_lt) {
                if ($stmt_lt->execute()) {
                    $lt_res = $stmt_lt->get_result();
                    if ($lt_res) {
                        while ($lt = $lt_res->fetch_assoc()) {
                            $leave_types[] = $lt;
                        }
                    }
                }
                $stmt_lt->close();
            }

            echo json_encode([
                'success' => true,
                'rows' => $rows,
                'leave_types' => $leave_types,
                'general_leave_setting' => $general_leave_setting
            ]);
            break;

        case 'get_leave_types':
            $student_gender = '';
            if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
                $user_id = (int)$_SESSION['user_id'];
                $stmt_g = $conn->prepare("SELECT LOWER(TRIM(s.gender)) AS gender FROM students s WHERE s.user_id = ? LIMIT 1");
                if ($stmt_g) {
                    $stmt_g->bind_param('i', $user_id);
                    $stmt_g->execute();
                    $res_g = $stmt_g->get_result();
                    $row_g = $res_g ? $res_g->fetch_assoc() : null;
                    $student_gender = $row_g['gender'] ?? '';
                    $stmt_g->close();
                }
            }

            $generalEnabled = false;
            if ($student_gender === 'male') {
                $stmt_gl = $conn->prepare("SELECT GeneralLeave_ID FROM general_leave WHERE Is_Enabled = 1 AND (Enabled_By = 'male_admin' OR Enabled_By = 'admin' OR Enabled_By = '' OR Enabled_By IS NULL) LIMIT 1");
            } elseif ($student_gender === 'female') {
                $stmt_gl = $conn->prepare("SELECT GeneralLeave_ID FROM general_leave WHERE Is_Enabled = 1 AND (Enabled_By = 'female_admin' OR Enabled_By = 'admin' OR Enabled_By = '' OR Enabled_By IS NULL) LIMIT 1");
            } else {
                $stmt_gl = $conn->prepare("SELECT GeneralLeave_ID FROM general_leave WHERE Is_Enabled = 1 AND (Enabled_By = 'admin' OR Enabled_By = '' OR Enabled_By IS NULL) LIMIT 1");
            }
            if ($stmt_gl) {
                $stmt_gl->execute();
                $res_gl = $stmt_gl->get_result();
                $generalEnabled = $res_gl && $res_gl->num_rows > 0;
                $stmt_gl->close();
            }

            $leave_types = [];
            if ($student_gender === 'female') {
                $stmt_lt = $conn->prepare("SELECT LeaveType_ID, Leave_Type_Name FROM leave_types WHERE LOWER(Leave_Type_Name) NOT LIKE '%outing%' ORDER BY Priority ASC, Leave_Type_Name ASC");
            } else {
                $stmt_lt = $conn->prepare("SELECT LeaveType_ID, Leave_Type_Name FROM leave_types ORDER BY Priority ASC, Leave_Type_Name ASC");
            }
            if ($stmt_lt) {
                $stmt_lt->execute();
                $lt_res = $stmt_lt->get_result();
                while ($lt = $lt_res->fetch_assoc()) {
                    if (stripos($lt['Leave_Type_Name'], 'general') !== false && !$generalEnabled) {
                        continue;
                    }
                    $leave_types[] = $lt;
                }
                $stmt_lt->close();
                echo json_encode(['success' => true, 'leave_types' => $leave_types]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
            break;

        // ========== STUDENT: ATTENDANCE MANAGEMENT ==========
        case 'loadAttendance':
            // Authenticate student
            $user_id = null;
            if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
                $user_id = $_SESSION['user_id'];
            }

            // 1. Check for User ID and Month/Year
            if (!$user_id) {
                echo json_encode(['success' => false, 'message' => 'User not logged in. Please log in to view attendance.']);
                break;
            }

            $month = isset($_POST['month']) ? $_POST['month'] : null;
            $year = isset($_POST['year']) ? $_POST['year'] : null;

            if (!$month || !$year) {
                echo json_encode(['success' => false, 'message' => 'Invalid month or year parameters.']);
                break;
            }

            try {
                // --- Step A: Get roll_number from user_id (Attendance table uses roll_number) ---
                $stmt_student = $conn->prepare("SELECT roll_number FROM students WHERE user_id = ?");
                if (!$stmt_student) {
                    throw new Exception('Database error (student lookup): ' . $conn->error);
                }
                $stmt_student->bind_param("i", $user_id);
                $stmt_student->execute();
                $result_student = $stmt_student->get_result();
                $student_data = $result_student->fetch_assoc();
                $stmt_student->close();

                if (!$student_data) {
                    echo json_encode(['success' => false, 'message' => 'Student record not found for this user ID.']);
                    break;
                }

                $student_roll_number = $student_data['roll_number'];

                // --- Step B: Calculate calendar details ---
                // mktime calculates the Unix timestamp for the first day of the specified month/year
                $first_day_of_month = mktime(0, 0, 0, $month, 1, $year);
                $days_in_month = (int)date('t', $first_day_of_month); // Total days in the month
                $first_day_weekday = (int)date('w', $first_day_of_month); // Weekday of the first day (0=Sun, 6=Sat)
                $month_text = date('F Y', $first_day_of_month);
                $today = date('Y-m-d');

                // --- Step C: Fetch Attendance Data for the specific student and month/year ---
                $start_date = date("Y-m-01", $first_day_of_month);
                $end_date = date("Y-m-t", $first_day_of_month);

                $stmt_attendance = $conn->prepare(
                    "SELECT DATE_FORMAT(date, '%Y-%m-%d') AS day_date, status 
                    FROM attendance 
                    WHERE roll_number = ? AND date BETWEEN ? AND ?"
                );
                if (!$stmt_attendance) {
                    throw new Exception('Database error (attendance lookup): ' . $conn->error);
                }
                $stmt_attendance->bind_param("sss", $student_roll_number, $start_date, $end_date);
                $stmt_attendance->execute();
                $result_attendance = $stmt_attendance->get_result();

                $attendance_data = [];
                while ($row = $result_attendance->fetch_assoc()) {
                    $attendance_data[$row['day_date']] = $row['status'];
                }
                $stmt_attendance->close();

                // --- Step D: Prepare final JSON response for the calendar ---
                $days = [];
                // Define colors for UI representation
                $status_colors = [
                    'Present' => '#28a745',
                    'Absent' => '#dc3545',
                    'Late Entry' => '#ffc107',
                    'On Leave' => '#007bff',
                ];

                $not_recorded_color = '#e9ecef';

                for ($i = 1; $i <= $days_in_month; $i++) {
                    $current_date = sprintf('%d-%02d-%02d', $year, $month, $i);

                    $status = $attendance_data[$current_date] ?? 'Not Recorded';

                    // Logic: If a day has passed and no attendance was recorded, mark it as Absent.
                    if ($status === 'Not Recorded' && $current_date < $today) {
                        $status = 'Absent';
                    }

                    $color = $status_colors[$status] ?? $not_recorded_color;

                    $days[] = [
                        'day' => $i,
                        'status' => $status,
                        'color' => $color,
                        'is_today' => ($current_date == $today)
                    ];
                }

                echo json_encode([
                    'success' => true,
                    'month_text' => $month_text,
                    'first_day' => $first_day_weekday,
                    'days' => $days
                ]);
            } catch (Exception $e) {
                error_log("loadAttendance error for user $user_id: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'An internal error occurred while fetching attendance data.']);
            }
            break;

        case 'loadDetailedAttendance':
            // Authenticate student
            $user_id = null;
            if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
                $user_id = $_SESSION['user_id'];
            }

            if (!$user_id) {
                echo json_encode(['success' => false, 'message' => 'User not logged in']);
                break;
            }

            $month = isset($_POST['month']) ? (int)$_POST['month'] : null;
            $year = isset($_POST['year']) ? (int)$_POST['year'] : null;

            if (!$month || !$year) {
                echo json_encode(['success' => false, 'message' => 'Invalid month or year']);
                break;
            }

            try {
                // Get roll_number from user_id
                $stmt = $conn->prepare("SELECT roll_number FROM students WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $student = $result->fetch_assoc();
                $stmt->close();

                if (!$student) {
                    echo json_encode(['success' => false, 'message' => 'Student not found']);
                    break;
                }

                $roll_number = $student['roll_number'];

                // Get attendance records for the month
                $start_date = sprintf('%04d-%02d-01', $year, $month);
                $end_date = date('Y-m-t', strtotime($start_date));

                $stmt = $conn->prepare(
                    "SELECT DATE_FORMAT(date, '%Y-%m-%d') AS date, status, reason, marked_at 
                    FROM attendance 
                    WHERE roll_number = ? AND date BETWEEN ? AND ?
                    ORDER BY date DESC"
                );
                $stmt->bind_param("sss", $roll_number, $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result();

                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $stmt->close();

                echo json_encode(['success' => true, 'data' => $data]);
            } catch (Exception $e) {
                error_log("loadDetailedAttendance error for user $user_id: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error fetching attendance details']);
            }
            break;

        // ========== STUDENT: PROFILE MANAGEMENT ========== 
        case 'get_profile_data':
            // Authenticate student
            $user_id = null;
            if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
                $user_id = $_SESSION['user_id'];
            }

            try {
                if (!$user_id) {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                    break;
                }

                $data = [];

                // Get student data
                $sql = "SELECT s.*, r.room_number, r.room_type, r.capacity, r.occupied, 
                               h.hostel_name, h.hostel_code, h.address, r.created_at
                        FROM students s 
                        LEFT JOIN rooms r ON s.room_id = r.room_id 
                        LEFT JOIN hostels h ON r.hostel_id = h.hostel_id 
                        WHERE s.user_id = ?";

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }

                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $student_data = $result->fetch_assoc();
                $stmt->close();

                // If no student data found, return demo data for testing
                if (!$student_data) {
                    $data['student_data'] = [
                        'name' => 'Demo Student',
                        'roll_number' => 'DEMO001',
                        'department' => 'Computer Science',
                        'date_of_birth' => '2000-01-01',
                        'gender' => 'Male',
                        'email' => 'demo@college.edu',
                        'student_phone' => '1234567890',
                        'Year_of_study' => 'III',
                        'batch' => '2022-2026',
                        'hostel_name' => 'Demo Hostel',
                        'hostel_code' => 'DH001',
                        'address' => 'Demo Address',
                        'room_number' => '101',
                        'room_type' => 'Single',
                        'capacity' => '1',
                        'occupied' => '1',
                        'created_at' => date('Y-m-d H:i:s'),
                        'photo_path' => null
                    ];

                    $data['parent_data'] = [
                        [
                            'guardian_id' => 1,
                            'name' => 'Demo Father',
                            'relation' => 'father',
                            'phone' => '9876543210',
                            'photo_path' => null
                        ],
                        [
                            'guardian_id' => 2,
                            'name' => 'Demo Mother',
                            'relation' => 'mother',
                            'phone' => '9876543211',
                            'photo_path' => null
                        ]
                    ];

                    $data['attendance_stats'] = ['attendance_percentage' => '85.5', 'total_days' => '30', 'present_days' => '25'];
                    $data['leave_stats'] = ['active_leaves' => '3'];

                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                }

                $data['student_data'] = $student_data;

                // Get parent data
                $sql = "SELECT g.guardian_id, g.name, g.relation, g.phone, g.photo_path
                        FROM students s
                        INNER JOIN guardians g ON s.student_id = g.student_id
                        WHERE s.roll_number = ?";

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }

                $stmt->bind_param("s", $student_data['roll_number']);
                $stmt->execute();
                $result = $stmt->get_result();
                $parent_data = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                // Process parent photos
                foreach ($parent_data as &$parent) {
                    if (!empty($parent['photo_path']) && file_exists($parent['photo_path'])) {
                        $parent['photo_url'] = path_to_url_student($parent['photo_path']);
                    } else {
                        $parent['photo_url'] = null;
                    }
                }
                $data['parent_data'] = $parent_data;

                // Process student photo
                if (!empty($student_data['photo_path']) && file_exists($student_data['photo_path'])) {
                    $data['student_data']['photo_url'] = path_to_url_student($student_data['photo_path']);
                } else {
                    $data['student_data']['photo_url'] = null;
                }

                // Get attendance stats
                $sql = "SELECT 
                            COUNT(*) as total_days,
                            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
                            ROUND((SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
                        FROM attendance 
                        WHERE roll_number = ? 
                        AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }

                $stmt->bind_param("s", $student_data['roll_number']);
                $stmt->execute();
                $result = $stmt->get_result();
                $attendance_result = $result->fetch_assoc();
                $stmt->close();

                $data['attendance_stats'] = $attendance_result ?: ['attendance_percentage' => 0, 'total_days' => 0, 'present_days' => 0];

                // Get leave stats
                $sql = "SELECT 
                            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days
                        FROM attendance 
                        WHERE roll_number = ? 
                        AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }

                $stmt->bind_param("s", $student_data['roll_number']);
                $stmt->execute();
                $result = $stmt->get_result();
                $absent_result = $result->fetch_assoc();
                $stmt->close();

                $data['leave_stats'] = ['active_leaves' => $absent_result['absent_days'] ?? 0];

                echo json_encode(['success' => true, 'data' => $data]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'upload_photo':
            // Authenticate student
            $user_id = null;
            if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
                $user_id = $_SESSION['user_id'];
            }

            // Create uploads directory if it doesn't exist
            $uploadDir = __DIR__ . '/Student/uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            try {
                $photo_type = isset($_POST['photo_type']) ? $_POST['photo_type'] : '';
                $parent_id = isset($_POST['parent_id']) ? $_POST['parent_id'] : null;

                if (!$user_id || !$photo_type) {
                    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                    break;
                }

                if (!isset($_FILES['photo'])) {
                    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
                    break;
                }

                $file = $_FILES['photo'];
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024;

                // Validate file
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('File upload error. Please try again.');
                }

                if (!in_array($file['type'], $allowed_types)) {
                    throw new Exception('Only JPG, PNG, and GIF images are allowed.');
                }

                if ($file['size'] > $max_size) {
                    throw new Exception('File size must be less than 5MB.');
                }

                // Generate unique filename
                $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
                $filePath = $uploadDir . $fileName;

                // Move uploaded file
                if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                    throw new Exception('Failed to save uploaded file.');
                }

                // Update database with file path
                if ($photo_type === 'student') {
                    // First, get student roll number to create a better file path
                    $sql = "SELECT roll_number FROM students WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $student = $result->fetch_assoc();
                    $stmt->close();

                    if ($student) {
                        // Create a more organized file path
                        $studentFileName = 'student_' . $student['roll_number'] . '.' . $fileExtension;
                        $studentFilePath = $uploadDir . 'students/' . $studentFileName;

                        // Create students directory if it doesn't exist
                        $studentDir = $uploadDir . 'students/';
                        if (!file_exists($studentDir)) {
                            mkdir($studentDir, 0777, true);
                        }

                        // Move file to students directory
                        rename($filePath, $studentFilePath);
                        $filePath = $studentFilePath;
                    }

                    $stmt = $conn->prepare("UPDATE students SET photo_path = ? WHERE user_id = ?");
                    if (!$stmt) {
                        throw new Exception('Database error: ' . $conn->error);
                    }
                    $stmt->bind_param("si", $filePath, $user_id);
                } elseif ($photo_type === 'parent' && $parent_id) {
                    // Get parent name for better file naming
                    $sql = "SELECT name FROM guardians WHERE guardian_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $parent_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $parent = $result->fetch_assoc();
                    $stmt->close();

                    if ($parent) {
                        // Create a more organized file path
                        $parentFileName = 'parent_' . $parent_id . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $parent['name']) . '.' . $fileExtension;
                        $parentFilePath = $uploadDir . 'parents/' . $parentFileName;

                        // Create parents directory if it doesn't exist
                        $parentDir = $uploadDir . 'parents/';
                        if (!file_exists($parentDir)) {
                            mkdir($parentDir, 0777, true);
                        }

                        // Move file to parents directory
                        rename($filePath, $parentFilePath);
                        $filePath = $parentFilePath;
                    }

                    $stmt = $conn->prepare("UPDATE guardians SET photo_path = ? WHERE guardian_id = ?");
                    if (!$stmt) {
                        throw new Exception('Database error: ' . $conn->error);
                    }
                    $stmt->bind_param("si", $filePath, $parent_id);
                } else {
                    throw new Exception('Invalid photo type or parent ID.');
                }

                if ($stmt->execute()) {
                    // Return the photo URL for immediate display
                    $photoUrl = path_to_url_student($filePath);

                    echo json_encode([
                        'success' => true,
                        'message' => 'Photo uploaded successfully!',
                        'photo_url' => $photoUrl,
                        'photo_type' => $photo_type,
                        'target_id' => $photo_type === 'student' ? $user_id : $parent_id
                    ]);
                } else {
                    throw new Exception('Failed to update photo in database.');
                }

                $stmt->close();
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'download_profile_pdf':
            // For now, return success message
            echo json_encode(['success' => true, 'message' => 'PDF download functionality would be implemented here']);
            break;

        // ========== STUDENT: DASHBOARD MANAGEMENT ========== 
        case 'loadDashboardStats':
            // Authenticate student
            $user_id = null;
            if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
                $user_id = $_SESSION['user_id'];
            }

            if (!$user_id) {
                echo json_encode(['success' => false, 'message' => 'Access denied or user not logged in.']);
                break;
            }

            $dashboard_data = [
                'name' => 'N/A',
                'department' => 'N/A',
                'batch' => 'N/A', // Corresponds to academic_batch
                'block' => 'N/A',
                'room_number' => 'N/A',
                'attendance_percentage' => 0,
                'pending_leaves' => 0
            ];

            try {
                // --- Step A: Get Student Profile Data, Block, and Room Details ---
                $stmt_profile = $conn->prepare("
                    SELECT 
                        s.roll_number, s.name, s.department, s.academic_batch, 
                        s.gender,
                        r.room_number, r.block 
                    FROM students s
                    LEFT JOIN rooms r ON s.room_id = r.room_id
                    WHERE s.user_id = ?
                ");
                if (!$stmt_profile) {
                    throw new Exception('Profile DB error: ' . $conn->error);
                }
                $stmt_profile->bind_param("i", $user_id);
                $stmt_profile->execute();
                $result_profile = $stmt_profile->get_result();
                $student_data = $result_profile->fetch_assoc();
                $stmt_profile->close();

                if (!$student_data) {
                    echo json_encode(['success' => false, 'message' => 'Student profile not found for user ID: ' . $user_id . '. Check student.user_id link.']);
                    break;
                }

                $roll_number = $student_data['roll_number'];

                // Copy profile data to dashboard_data
                $dashboard_data['name'] = htmlspecialchars($student_data['name']);
                $dashboard_data['department'] = htmlspecialchars($student_data['department']);
                $dashboard_data['batch'] = htmlspecialchars($student_data['academic_batch'] ?? 'N/A');
                $dashboard_data['block'] = htmlspecialchars($student_data['block'] ?? 'N/A');
                $dashboard_data['room_number'] = htmlspecialchars($student_data['room_number'] ?? 'N/A');
                $dashboard_data['gender'] = htmlspecialchars($student_data['gender'] ?? 'N/A');

                // --- Step B: Calculate Attendance Percentage ---
                $stmt_attendance = $conn->prepare("
                    SELECT 
                        SUM(CASE WHEN status IN ('Present', 'Late Entry', 'On Leave', 'On Duty') THEN 1 ELSE 0 END) AS attended,
                        COUNT(status) AS total_recorded_days
                    FROM attendance
                    WHERE roll_number = ?
                ");
                if (!$stmt_attendance) {
                    throw new Exception('Attendance DB error: ' . $conn->error);
                }
                $stmt_attendance->bind_param("s", $roll_number);
                $stmt_attendance->execute();
                $result_attendance = $stmt_attendance->get_result();
                $stats = $result_attendance->fetch_assoc();
                $stmt_attendance->close();

                if ($stats && $stats['total_recorded_days'] > 0) {
                    $percentage = ($stats['attended'] / $stats['total_recorded_days']) * 100;
                    $dashboard_data['attendance_percentage'] = round($percentage, 1);
                }

                // --- Step C: Count Pending Leaves ---
                $stmt_leaves = $conn->prepare("
                    SELECT COUNT(*) AS pending_count 
                    FROM leave_applications 
                    WHERE Reg_No = ? AND Status = 'Pending'
                ");
                if (!$stmt_leaves) {
                    throw new Exception('Leaves DB error: ' . $conn->error);
                }
                $stmt_leaves->bind_param("s", $roll_number);
                $stmt_leaves->execute();
                $result_leaves = $stmt_leaves->get_result();
                $leave_count = $result_leaves->fetch_assoc();
                $stmt_leaves->close();

                if ($leave_count) {
                    $dashboard_data['pending_leaves'] = (int)$leave_count['pending_count'];
                }

                echo json_encode([
                    'success' => true,
                    'data' => $dashboard_data
                ]);
            } catch (Exception $e) {
                error_log("loadDashboardStats error for user $user_id: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'An internal error occurred while fetching dashboard data: ' . $e->getMessage()]);
            }
            break;

        // ========== STUDENT: MESS MANAGEMENT ========== 
        case 'get_monthly_bill':
            // Authenticate student
            $user_id = null;
            $roll_no = null;
            if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
                $user_id = $_SESSION['user_id'];

                // Get roll number for authenticated users
                $stmt = $conn->prepare("SELECT s.roll_number, s.user_id AS student_user_id, u.username FROM users u LEFT JOIN students s ON s.user_id = u.user_id WHERE u.user_id = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();

                    if ($user) {
                        $roll_no = !empty($user['roll_number']) ? $user['roll_number'] : $user['username'];
                    }
                }
            }

            // Check if user is student
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
                http_response_code(403);
                echo json_encode(['status' => false, 'msg' => 'Access denied. Students only.']);
                break;
            }

            try {
                // Check if user is logged in
                if (!$roll_no) {
                    echo json_encode(['status' => false, 'msg' => 'User not logged in']);
                    break;
                }

                // Get month and year from POST data
                $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
                $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

                // Validate month and year
                if ($month < 1 || $month > 12) {
                    echo json_encode(['status' => false, 'msg' => 'Invalid month']);
                    break;
                }

                if ($year < 2020 || $year > (date('Y') + 1)) {
                    echo json_encode(['status' => false, 'msg' => 'Invalid year']);
                    break;
                }

                // Prepare query to fetch special tokens for the specified month and year
                $stmt = $conn->prepare("SELECT *, DATE_FORMAT(created_at, '%d %b %Y') as formatted_date FROM mess_tokens 
                                        WHERE roll_number = ? 
                                        AND token_type = 'Special' 
                                        AND MONTH(created_at) = ? 
                                        AND YEAR(created_at) = ? 
                                        ORDER BY created_at ASC");
                $stmt->bind_param("sii", $roll_no, $month, $year);
                $stmt->execute();
                $result = $stmt->get_result();

                $tokens = [];
                while ($row = $result->fetch_assoc()) {
                    $tokens[] = $row;
                }

                echo json_encode(['status' => true, 'data' => $tokens]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'msg' => 'Server error: ' . $e->getMessage()]);
            }
            break;

        case 'request_special_token':
            // Authenticate student
            $user_id = null;
            $roll_no = null;
            if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
                $user_id = $_SESSION['user_id'];

                // Get roll number for authenticated users
                $stmt = $conn->prepare("SELECT s.roll_number, s.user_id AS student_user_id, u.username FROM users u LEFT JOIN students s ON s.user_id = u.user_id WHERE u.user_id = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    $stmt->close();

                    if ($user) {
                        $roll_no = !empty($user['roll_number']) ? $user['roll_number'] : $user['username'];
                    }
                }
            }

            // Check if user is student
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
                http_response_code(403);
                echo json_encode(['status' => false, 'msg' => 'Access denied. Students only.']);
                break;
            }

            // If roll_number is not provided, try to fetch it from session
            if (!$roll_no && isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $roll_query = "SELECT roll_number FROM students WHERE user_id = ?";
                $roll_stmt = $conn->prepare($roll_query);
                $roll_stmt->bind_param("i", $user_id);
                $roll_stmt->execute();
                $roll_result = $roll_stmt->get_result();
                $roll_data = $roll_result->fetch_assoc();
                if ($roll_data) {
                    $roll_no = $roll_data['roll_number'];
                }
            }

            if (!$roll_no) {
                echo json_encode(['status' => false, 'msg' => 'User not logged in']);
                break;
            }

            // Validate and sanitize input
            $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;

            if ($menu_id <= 0) {
                echo json_encode(['status' => false, 'msg' => 'Invalid menu ID']);
                break;
            }

            // Get special meal details
            $stmt = $conn->prepare("SELECT token_date, meal_type, menu_items, fee FROM specialtokenenable WHERE menu_id = ?");
            $stmt->bind_param("i", $menu_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                echo json_encode(['status' => false, 'msg' => 'Special meal not found']);
                break;
            }

            $meal = $result->fetch_assoc();

            // Check if already requested
            $stmt = $conn->prepare("SELECT t.token_id FROM mess_tokens t 
                                    JOIN mess_menu mm ON t.menu_id = mm.menu_id
                                    WHERE t.roll_number=? AND mm.date=? AND mm.meal_type=? AND mm.category='Special'");
            $stmt->bind_param("sss", $roll_no, $meal['token_date'], $meal['meal_type']);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows > 0) {
                // Already requested, return the existing token_id
                $existing_token = $res->fetch_assoc();

                // Fetch the existing token data to return with the response
                $stmt_fetch = $conn->prepare("SELECT t.*, s.name as student_name FROM mess_tokens t 
                                             LEFT JOIN students s ON t.roll_number = s.roll_number
                                             WHERE t.token_id=?");
                $stmt_fetch->bind_param("i", $existing_token['token_id']);
                $stmt_fetch->execute();
                $result = $stmt_fetch->get_result();
                $token_data = $result->fetch_assoc();

                echo json_encode([
                    'status' => true,
                    'msg' => 'Already requested',
                    'token_id' => $existing_token['token_id'],
                    'token_data' => $token_data
                ]);
                break;
            }

            $token_date = $meal['token_date'] ?? date('Y-m-d');

            // First, try to get the existing menu_id
            $stmt = $conn->prepare("SELECT menu_id FROM mess_menu WHERE date=? AND meal_type=? AND category='Special'");
            $stmt->bind_param("ss", $token_date, $meal['meal_type']);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows > 0) {
                // Use existing menu_id from mess_menu
                $mess_menu_row = $res->fetch_assoc();
                $mess_menu_id = $mess_menu_row['menu_id'];
            } else {
                // Insert the special meal into mess_menu first
                $stmt = $conn->prepare("INSERT INTO mess_menu (date, meal_type, items, fee, category) VALUES (?, ?, ?, ?, 'Special')");
                $stmt->bind_param("sssd", $token_date, $meal['meal_type'], $meal['menu_items'], $meal['fee']);

                if ($stmt->execute()) {
                    $mess_menu_id = $conn->insert_id;
                } else {
                    // Check if the error is due to a duplicate entry
                    if ($conn->errno == 1062) {
                        // Duplicate entry, try to fetch the existing menu_id
                        $stmt = $conn->prepare("SELECT menu_id FROM mess_menu WHERE date=? AND meal_type=? AND category='Special'");
                        $stmt->bind_param("ss", $token_date, $meal['meal_type']);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        $mess_menu_row = $res->fetch_assoc();
                        $mess_menu_id = $mess_menu_row['menu_id'];
                    } else {
                        echo json_encode(['status' => false, 'msg' => 'Error inserting into mess_menu: ' . $conn->error]);
                        break;
                    }
                }
            }

            // Insert request using the mess_menu_id
            $token_type = 'Special';
            $token_date = $meal['token_date'] ?? date('Y-m-d');
            $stmt = $conn->prepare("INSERT INTO mess_tokens (roll_number, menu_id, meal_type, menu, token_type, token_date, special_fee, created_at) VALUES (?,?,?,?,?,?,?,NOW())");
            $stmt->bind_param("sissssd", $roll_no, $mess_menu_id, $meal['meal_type'], $meal['menu_items'], $token_type, $token_date, $meal['fee']);

            if ($stmt->execute()) {
                $token_id = $conn->insert_id;

                // Fetch the inserted token data to return with the response
                $stmt_fetch = $conn->prepare("SELECT t.*, s.name as student_name FROM mess_tokens t 
                                             LEFT JOIN students s ON t.roll_number = s.roll_number
                                             WHERE t.token_id=?");
                $stmt_fetch->bind_param("i", $token_id);
                $stmt_fetch->execute();
                $result = $stmt_fetch->get_result();
                $token_data = $result->fetch_assoc();

                echo json_encode([
                    'status' => true,
                    'msg' => 'Requested successfully',
                    'token_id' => $token_id,
                    'token_data' => $token_data
                ]);
            } else {
                echo json_encode(['status' => false, 'msg' => 'Error in request: ' . $conn->error]);
            }
            break;

        case 'read_menus':
            try {
                $stmt = $conn->prepare("SELECT * FROM mess_menu ORDER BY date DESC, meal_type ASC");
                $stmt->execute();
                $result = $stmt->get_result();

                $menus = [];
                while ($row = $result->fetch_assoc()) {
                    $menus[] = $row;
                }

                echo json_encode(['status' => true, 'data' => $menus]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'msg' => 'Failed to load menus: ' . $e->getMessage()]);
            }
            break;

        case 'read_special_tokens':
            try {
                $stmt = $conn->prepare("SELECT * FROM specialtokenenable ORDER BY from_date DESC");
                $stmt->execute();
                $result = $stmt->get_result();

                $tokens = [];
                while ($row = $result->fetch_assoc()) {
                    $tokens[] = $row;
                }

                echo json_encode(['status' => true, 'data' => $tokens]);
            } catch (Exception $e) {
                echo json_encode(['status' => false, 'msg' => 'Failed to load special tokens: ' . $e->getMessage()]);
            }
            break;

        // ========== STUDENT: NOTICES ==========
        case 'get_notices':
            try {
                // Get student's gender for filtering notices
                $studentGender = null;
                
                // Get gender from session or database if student is logged in
                if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student') {
                    $stmt_gender = $conn->prepare("SELECT gender FROM students WHERE user_id = ?");
                    $stmt_gender->bind_param("i", $_SESSION['user_id']);
                    $stmt_gender->execute();
                    $result_gender = $stmt_gender->get_result();
                    if ($result_gender && $result_gender->num_rows > 0) {
                        $row_gender = $result_gender->fetch_assoc();
                        $studentGender = $row_gender['gender'];
                    }
                    $stmt_gender->close();
                }
                
                // Fetch notices filtered by gender (show notices targeted to student's gender or 'all')
                if ($studentGender) {
                    // Student is logged in - filter by gender
                    $stmt = $conn->prepare("SELECT id, title, content, created_at, 
                                           COALESCE(posted_by_role, 'admin') as posted_by_role,
                                           COALESCE(target_gender, 'all') as target_gender
                                           FROM notices 
                                           WHERE COALESCE(target_gender, 'all') IN (?, 'all')
                                           ORDER BY created_at DESC 
                                           LIMIT 5");
                    $stmt->bind_param("s", $studentGender);
                } else {
                    // No gender info - show only 'all' notices
                    $stmt = $conn->prepare("SELECT id, title, content, created_at, 
                                           COALESCE(posted_by_role, 'admin') as posted_by_role,
                                           COALESCE(target_gender, 'all') as target_gender
                                           FROM notices 
                                           WHERE COALESCE(target_gender, 'all') = 'all'
                                           ORDER BY created_at DESC 
                                           LIMIT 5");
                }
                
                $stmt->execute();
                $result = $stmt->get_result();

                $notices = [];
                while ($row = $result->fetch_assoc()) {
                    // Format the date for display
                    $created_at = new DateTime($row['created_at']);
                    $row['formatted_date'] = $created_at->format('M j, Y');
                    // Use title if available, otherwise use first part of content
                    if (empty($row['title']) || $row['title'] == '') {
                        $content = trim($row['content']);
                        $title = substr($content, 0, 50);
                        if (strlen($content) > 50) {
                            $title .= '...';
                        }
                        $row['title'] = $title ?: 'Notice';
                    }
                    $notices[] = $row;
                }

                echo json_encode([
                    'success' => true,
                    'notices' => $notices,
                    'student_gender' => $studentGender
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error fetching notices: ' . $e->getMessage()]);
            }
            break;

        // Biometric Device IP mapping
        case 'list_machines': {
                // Initialize response variables
                $success = false;
                $message = '';
                $extra = [];
                $httpStatus = 200;

                // Parse JSON body if needed
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (stripos($contentType, 'application/json') !== false) {
                    $raw = file_get_contents('php://input');
                    $decoded = json_decode($raw, true);
                    $data = is_array($decoded) ? $decoded : [];
                } else {
                    $data = $_POST;
                }

                $type = trim((string)($data['machine_type'] ?? ($_GET['machine_type'] ?? '')));
                $allowedTypes = ['mess', 'gate_in', 'gate_out', 'attendance'];
                if (!in_array($type, $allowedTypes, true)) {
                    $success = false;
                    $message = 'Invalid machine_type';
                    $httpStatus = 400;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $stmt = $conn->prepare('SELECT id, machine_type, machine_name, machine_ip, hostel_id, menu_id, status FROM biometric_machines WHERE machine_type = ? ORDER BY id DESC');
                if (!$stmt) {
                    $success = false;
                    $message = 'Failed to prepare query';
                    $extra = ['error' => $conn->error];
                    $httpStatus = 500;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $stmt->bind_param('s', $type);
                if (!$stmt->execute()) {
                    $success = false;
                    $message = 'Failed to fetch machines';
                    $extra = ['error' => $stmt->error];
                    $httpStatus = 500;
                    $stmt->close();
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $result = $stmt->get_result();
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                $stmt->close();

                $success = true;
                $message = 'OK';
                $extra = ['data' => $rows, 'count' => count($rows)];
                $httpStatus = 200;
                http_response_code($httpStatus);
                echo json_encode(array_merge([
                    'success' => $success,
                    'message' => $message,
                ], $extra), JSON_UNESCAPED_UNICODE);
                exit;
            }

        case 'get_machine': {
                $success = false;
                $message = '';
                $extra = [];
                $httpStatus = 200;
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (stripos($contentType, 'application/json') !== false) {
                    $raw = file_get_contents('php://input');
                    $decoded = json_decode($raw, true);
                    $data = is_array($decoded) ? $decoded : [];
                } else {
                    $data = $_POST;
                }

                $machineId = (int)($data['machine_id'] ?? ($_GET['machine_id'] ?? 0));
                if ($machineId <= 0) {
                    $success = false;
                    $message = 'Invalid machine_id';
                    $httpStatus = 400;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $stmt = $conn->prepare('SELECT id, machine_type, machine_name, machine_ip, hostel_id, menu_id, status FROM biometric_machines WHERE id = ?');
                if (!$stmt) {
                    $success = false;
                    $message = 'Failed to prepare query';
                    $extra = ['error' => $conn->error];
                    $httpStatus = 500;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $stmt->bind_param('i', $machineId);
                if (!$stmt->execute()) {
                    $success = false;
                    $message = 'Failed to fetch machine';
                    $extra = ['error' => $stmt->error];
                    $httpStatus = 500;
                    $stmt->close();
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();

                if (!$row) {
                    $success = false;
                    $message = 'Machine not found';
                    $httpStatus = 404;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $success = true;
                $message = 'OK';
                $extra = ['data' => $row];
                $httpStatus = 200;
                http_response_code($httpStatus);
                echo json_encode(array_merge([
                    'success' => $success,
                    'message' => $message,
                ], $extra), JSON_UNESCAPED_UNICODE);
                exit;
            }

        case 'add_machine': {
                $success = false;
                $message = '';
                $extra = [];
                $httpStatus = 200;
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (stripos($contentType, 'application/json') !== false) {
                    $raw = file_get_contents('php://input');
                    $decoded = json_decode($raw, true);
                    $data = is_array($decoded) ? $decoded : [];
                } else {
                    $data = $_POST;
                }

                if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                    $success = false;
                    $message = 'Method not allowed';
                    $httpStatus = 405;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $type = trim((string)($data['machine_type'] ?? ''));
                $allowedTypes = ['mess', 'gate_in', 'gate_out', 'attendance'];
                if (!in_array($type, $allowedTypes, true)) {
                    $success = false;
                    $message = 'Invalid machine_type';
                    $httpStatus = 400;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $machineName = trim((string)($data['machine_name'] ?? ''));
                $machineIp = trim((string)($data['machine_ip'] ?? ''));
                $machinePort = (int)($data['machine_port'] ?? 4370);
                $hostelId = isset($data['hostel_id']) && $data['hostel_id'] !== '' ? (int)$data['hostel_id'] : null;

                if ($machineName === '') {
                    $success = false;
                    $message = 'Machine name is required';
                    $httpStatus = 400;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if ($machineIp === '' || filter_var($machineIp, FILTER_VALIDATE_IP) === false) {
                    $success = false;
                    $message = 'Valid machine_ip is required';
                    $httpStatus = 400;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                // Handle menu_id for mess machine type
                $menuId = null;
                if ($type === 'mess') {
                    $menuId = isset($data['menu_id']) && $data['menu_id'] !== '' ? (int)$data['menu_id'] : null;
                }

                $stmt = $conn->prepare('INSERT INTO biometric_machines (machine_type, machine_name, machine_ip, hostel_id, menu_id) VALUES (?, ?, ?, ?, ?)');
                if (!$stmt) {
                    $success = false;
                    $message = 'Failed to prepare insert';
                    $extra = ['error' => $conn->error];
                    $httpStatus = 500;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $stmt->bind_param('sssii', $type, $machineName, $machineIp, $hostelId, $menuId);
                try {
                    $ok = $stmt->execute();
                } catch (mysqli_sql_exception $e) {
                    $ok = false;
                    $errMsg = $e->getMessage();
                    $errCode = (int)$e->getCode();
                    if ($errCode === 1062) {
                        $success = false;
                        if (stripos($errMsg, 'uq_machine_ip') !== false || stripos($errMsg, 'machine_ip') !== false) {
                            $message = 'Machine IP already exists';
                        } else {
                            $message = 'Duplicate entry';
                        }
                        $extra = ['error' => $errMsg];
                        $httpStatus = 409;
                        $stmt->close();
                        http_response_code($httpStatus);
                        echo json_encode(array_merge([
                            'success' => $success,
                            'message' => $message,
                        ], $extra), JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    $success = false;
                    $message = 'Failed to add machine';
                    $extra = ['error' => $errMsg];
                    $httpStatus = 500;
                    $stmt->close();
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                if (!$ok) {
                    $success = false;
                    $message = 'Failed to add machine';
                    $extra = ['error' => $stmt->error];
                    $httpStatus = 500;
                    $stmt->close();
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $newId = $stmt->insert_id;
                $stmt->close();

                $success = true;
                $message = 'Machine added';
                $extra = ['machine_id' => $newId];
                $httpStatus = 200;
                http_response_code($httpStatus);
                echo json_encode(array_merge([
                    'success' => $success,
                    'message' => $message,
                ], $extra), JSON_UNESCAPED_UNICODE);
                exit;
            }

        case 'update_machine': {
                $success = false;
                $message = '';
                $extra = [];
                $httpStatus = 200;
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (stripos($contentType, 'application/json') !== false) {
                    $raw = file_get_contents('php://input');
                    $decoded = json_decode($raw, true);
                    $data = is_array($decoded) ? $decoded : [];
                } else {
                    $data = $_POST;
                }

                if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                    $success = false;
                    $message = 'Method not allowed';
                    $httpStatus = 405;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $machineId = (int)($data['machine_id'] ?? 0);
                if ($machineId <= 0) {
                    $success = false;
                    $message = 'Invalid machine_id';
                    $httpStatus = 400;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $type = trim((string)($data['machine_type'] ?? ''));
                $allowedTypes = ['mess', 'gate_in', 'gate_out', 'attendance'];
                if (!in_array($type, $allowedTypes, true)) {
                    $success = false;
                    $message = 'Invalid machine_type';
                    $httpStatus = 400;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $machineName = trim((string)($data['machine_name'] ?? ''));
                $machineIp = trim((string)($data['machine_ip'] ?? ''));
                $machinePort = (int)($data['machine_port'] ?? 4370);
                $hostelId = isset($data['hostel_id']) && $data['hostel_id'] !== '' ? (int)$data['hostel_id'] : null;

                if ($machineName === '') {
                    $success = false;
                    $message = 'Machine name is required';
                    $httpStatus = 400;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if ($machineIp === '' || filter_var($machineIp, FILTER_VALIDATE_IP) === false) {
                    $success = false;
                    $message = 'Valid machine_ip is required';
                    $httpStatus = 400;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                // Handle menu_id for mess machine type
                $menuId = null;
                if ($type === 'mess') {
                    $menuId = isset($data['menu_id']) && $data['menu_id'] !== '' ? (int)$data['menu_id'] : null;
                }

                $stmt = $conn->prepare('UPDATE biometric_machines SET machine_type = ?, machine_name = ?, machine_ip = ?, machine_port = ?, hostel_id = ?, menu_id = ? WHERE id = ?');
                if (!$stmt) {
                    $success = false;
                    $message = 'Failed to prepare update';
                    $extra = ['error' => $conn->error];
                    $httpStatus = 500;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $stmt->bind_param('sssiiii', $type, $machineName, $machineIp, $machinePort, $hostelId, $menuId, $machineId);
                try {
                    $ok = $stmt->execute();
                } catch (mysqli_sql_exception $e) {
                    $ok = false;
                    $errMsg = $e->getMessage();
                    $errCode = (int)$e->getCode();
                    if ($errCode === 1062) {
                        $success = false;
                        if (stripos($errMsg, 'uq_machine_ip') !== false || stripos($errMsg, 'machine_ip') !== false) {
                            $message = 'Machine IP already exists';
                        } else {
                            $message = 'Duplicate entry';
                        }
                        $extra = ['error' => $errMsg];
                        $httpStatus = 409;
                        $stmt->close();
                        http_response_code($httpStatus);
                        echo json_encode(array_merge([
                            'success' => $success,
                            'message' => $message,
                        ], $extra), JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    $success = false;
                    $message = 'Failed to update machine';
                    $extra = ['error' => $errMsg];
                    $httpStatus = 500;
                    $stmt->close();
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                if (!$ok) {
                    $success = false;
                    $message = 'Failed to update machine';
                    $extra = ['error' => $stmt->error];
                    $httpStatus = 500;
                    $stmt->close();
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $stmt->close();

                $success = true;
                $message = 'Machine updated';
                $extra = ['machine_id' => $machineId];
                $httpStatus = 200;
                http_response_code($httpStatus);
                echo json_encode(array_merge([
                    'success' => $success,
                    'message' => $message,
                ], $extra), JSON_UNESCAPED_UNICODE);
                exit;
            }

        case 'delete_machine': {
                $success = false;
                $message = '';
                $extra = [];
                $httpStatus = 200;
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (stripos($contentType, 'application/json') !== false) {
                    $raw = file_get_contents('php://input');
                    $decoded = json_decode($raw, true);
                    $data = is_array($decoded) ? $decoded : [];
                } else {
                    $data = $_POST;
                }

                if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                    $success = false;
                    $message = 'Method not allowed';
                    $httpStatus = 405;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $machineId = (int)($data['machine_id'] ?? 0);
                if ($machineId <= 0) {
                    $success = false;
                    $message = 'Invalid machine_id';
                    $httpStatus = 400;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $stmt = $conn->prepare('DELETE FROM biometric_machines WHERE id = ?');
                if (!$stmt) {
                    $success = false;
                    $message = 'Failed to prepare delete';
                    $extra = ['error' => $conn->error];
                    $httpStatus = 500;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $stmt->bind_param('i', $machineId);
                if (!$stmt->execute()) {
                    $success = false;
                    $message = 'Failed to delete machine';
                    $extra = ['error' => $stmt->error];
                    $httpStatus = 500;
                    $stmt->close();
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $deleted = $stmt->affected_rows;
                $stmt->close();

                if ($deleted === 0) {
                    $success = false;
                    $message = 'Machine not found';
                    $httpStatus = 404;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $success = true;
                $message = 'Machine deleted';
                $extra = ['machine_id' => $machineId];
                $httpStatus = 200;
                http_response_code($httpStatus);
                echo json_encode(array_merge([
                    'success' => $success,
                    'message' => $message,
                ], $extra), JSON_UNESCAPED_UNICODE);
                exit;
            }

        case 'list_hostels': {
                $success = false;
                $message = '';
                $extra = [];
                $httpStatus = 200;
                $scopeGender = get_current_admin_scope_gender();

                if ($scopeGender !== null) {
                    $stmt = $conn->prepare('SELECT hostel_id, hostel_name FROM hostels WHERE gender = ? ORDER BY hostel_name');
                } else {
                    $stmt = $conn->prepare('SELECT hostel_id, hostel_name FROM hostels ORDER BY hostel_name');
                }
                if (!$stmt) {
                    $success = false;
                    $message = 'Failed to prepare query';
                    $extra = ['error' => $conn->error];
                    $httpStatus = 500;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                if ($scopeGender !== null) {
                    $stmt->bind_param('s', $scopeGender);
                }

                if (!$stmt->execute()) {
                    $success = false;
                    $message = 'Failed to fetch hostels';
                    $extra = ['error' => $stmt->error];
                    $httpStatus = 500;
                    $stmt->close();
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $result = $stmt->get_result();
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                $stmt->close();

                $success = true;
                $message = 'OK';
                $extra = ['data' => $rows, 'count' => count($rows)];
                $httpStatus = 200;
                http_response_code($httpStatus);
                echo json_encode(array_merge([
                    'success' => $success,
                    'message' => $message,
                ], $extra), JSON_UNESCAPED_UNICODE);
                exit;
            }

        case 'list_special_tokens': {
                $success = false;
                $message = '';
                $extra = [];
                $httpStatus = 200;

                $stmt = $conn->prepare('SELECT menu_id, token_date, meal_type, menu_items FROM specialtokenenable WHERE status = ? ORDER BY token_date DESC, meal_type ASC');
                if (!$stmt) {
                    $success = false;
                    $message = 'Failed to prepare query';
                    $extra = ['error' => $conn->error];
                    $httpStatus = 500;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $status = 'active';
                $stmt->bind_param('s', $status);
                if (!$stmt->execute()) {
                    $success = false;
                    $message = 'Failed to fetch tokens';
                    $extra = ['error' => $stmt->error];
                    $httpStatus = 500;
                    $stmt->close();
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $result = $stmt->get_result();
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                $stmt->close();

                $success = true;
                $message = 'OK';
                $extra = ['data' => $rows, 'count' => count($rows)];
                $httpStatus = 200;
                http_response_code($httpStatus);
                echo json_encode(array_merge([
                    'success' => $success,
                    'message' => $message,
                ], $extra), JSON_UNESCAPED_UNICODE);
                exit;
            }

        case 'check_status': {
                $success = false;
                $message = '';
                $extra = [];
                $httpStatus = 200;
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (stripos($contentType, 'application/json') !== false) {
                    $raw = file_get_contents('php://input');
                    $decoded = json_decode($raw, true);
                    $data = is_array($decoded) ? $decoded : [];
                } else {
                    $data = $_POST;
                }

                $ip = trim((string)($data['machine_ip'] ?? ($_GET['machine_ip'] ?? '')));
                if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
                    $success = false;
                    $message = 'Valid machine_ip is required';
                    $httpStatus = 400;
                    http_response_code($httpStatus);
                    echo json_encode(array_merge([
                        'success' => $success,
                        'message' => $message,
                    ], $extra), JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $ports = [4370, 80, 8080];
                $timeout = 0.8;
                $online = false;
                foreach ($ports as $port) {
                    $errno = 0;
                    $errstr = '';
                    $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
                    if (is_resource($fp)) {
                        fclose($fp);
                        $online = true;
                        break;
                    }
                }

                $success = true;
                $message = 'OK';
                $extra = ['online' => $online];
                $httpStatus = 200;
                http_response_code($httpStatus);
                echo json_encode(array_merge([
                    'success' => $success,
                    'message' => $message,
                ], $extra), JSON_UNESCAPED_UNICODE);
                exit;
            }

            // ========== STAY IN HOSTEL ==========
        case 'submit_stay_hostel_request':
            try {
                if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    break;
                }

                $user_id = (int)$_SESSION['user_id'];
                $request_id = (int)($_POST['request_id'] ?? 0);
                $from_date = trim($_POST['from_date'] ?? '');
                $to_date = trim($_POST['to_date'] ?? '');
                $reason = trim($_POST['reason'] ?? '');
                $remove_proof = (int)($_POST['remove_proof'] ?? 0) === 1;

                if ($from_date === '' || $to_date === '' || $reason === '') {
                    echo json_encode(['success' => false, 'message' => 'All fields are required']);
                    break;
                }

                if (strtotime($from_date) === false || strtotime($to_date) === false) {
                    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
                    break;
                }

                if (strtotime($from_date) > strtotime($to_date)) {
                    echo json_encode(['success' => false, 'message' => 'From date cannot be greater than To date']);
                    break;
                }

                $stmt_student = $conn->prepare("SELECT student_id FROM students WHERE user_id = ? LIMIT 1");
                if (!$stmt_student) {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                    break;
                }
                $stmt_student->bind_param("i", $user_id);
                $stmt_student->execute();
                $res_student = $stmt_student->get_result();
                $student = $res_student ? $res_student->fetch_assoc() : null;
                $stmt_student->close();

                if (!$student) {
                    echo json_encode(['success' => false, 'message' => 'Student record not found']);
                    break;
                }

                $student_id = (int)$student['student_id'];
                $existing_proof = null;

                if ($request_id > 0) {
                    $stmt_existing = $conn->prepare("SELECT proof_path FROM stay_in_hostel_requests WHERE request_id = ? AND student_id = ? LIMIT 1");
                    if (!$stmt_existing) {
                        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                        break;
                    }
                    $stmt_existing->bind_param("ii", $request_id, $student_id);
                    $stmt_existing->execute();
                    $res_existing = $stmt_existing->get_result();
                    $existing_row = $res_existing ? $res_existing->fetch_assoc() : null;
                    $stmt_existing->close();

                    if (!$existing_row) {
                        echo json_encode(['success' => false, 'message' => 'Request not found for update']);
                        break;
                    }
                    $existing_proof = $existing_row['proof_path'] ?? null;
                }

                $proof_path = $existing_proof;

                if (isset($_FILES['proof']) && ($_FILES['proof']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $file = $_FILES['proof'];

                    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                        echo json_encode(['success' => false, 'message' => 'Proof upload failed']);
                        break;
                    }

                    if (($file['size'] ?? 0) > (5 * 1024 * 1024)) {
                        echo json_encode(['success' => false, 'message' => 'Proof file must be less than 5MB']);
                        break;
                    }

                    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
                    if (!in_array($extension, $allowed_extensions, true)) {
                        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG and PDF files are allowed']);
                        break;
                    }

                    $mime_type = '';
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        if ($finfo) {
                            $mime_type = finfo_file($finfo, $file['tmp_name']);
                            finfo_close($finfo);
                        }
                    }
                    $allowed_mimes = ['image/jpeg', 'image/png', 'application/pdf'];
                    if ($mime_type !== '' && !in_array($mime_type, $allowed_mimes, true)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid proof file type']);
                        break;
                    }

                    $relative_dir = 'Student/proofs/stay_hostel/';
                    $absolute_dir = __DIR__ . '/' . $relative_dir;
                    if (!is_dir($absolute_dir) && !mkdir($absolute_dir, 0755, true)) {
                        echo json_encode(['success' => false, 'message' => 'Failed to create proof directory']);
                        break;
                    }

                    $safe_name = 'stay_' . $student_id . '_' . str_replace('.', '_', uniqid('', true)) . '.' . $extension;
                    $absolute_path = $absolute_dir . $safe_name;
                    $relative_path = $relative_dir . $safe_name;

                    if (!move_uploaded_file($file['tmp_name'], $absolute_path)) {
                        echo json_encode(['success' => false, 'message' => 'Failed to save proof file']);
                        break;
                    }

                    if (!empty($existing_proof)) {
                        $old_path = __DIR__ . '/' . ltrim($existing_proof, '/');
                        if (file_exists($old_path)) {
                            @unlink($old_path);
                        }
                    }

                    $proof_path = $relative_path;
                } elseif ($remove_proof && !empty($existing_proof)) {
                    $old_path = __DIR__ . '/' . ltrim($existing_proof, '/');
                    if (file_exists($old_path)) {
                        @unlink($old_path);
                    }
                    $proof_path = null;
                }

                if ($request_id > 0) {
                    $stmt_update = $conn->prepare("
                        UPDATE stay_in_hostel_requests
                        SET from_date = ?, to_date = ?, reason = ?, proof_path = ?
                        WHERE request_id = ? AND student_id = ?
                    ");
                    if (!$stmt_update) {
                        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                        break;
                    }
                    $stmt_update->bind_param("ssssii", $from_date, $to_date, $reason, $proof_path, $request_id, $student_id);
                    if ($stmt_update->execute()) {
                        echo json_encode(['success' => true, 'message' => 'Stay In Hostel request updated successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to update request: ' . $stmt_update->error]);
                    }
                    $stmt_update->close();
                } else {
                    $stmt_insert = $conn->prepare("
                        INSERT INTO stay_in_hostel_requests
                        (student_id, from_date, to_date, reason, proof_path, requested_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    if (!$stmt_insert) {
                        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                        break;
                    }
                    $stmt_insert->bind_param("issss", $student_id, $from_date, $to_date, $reason, $proof_path);
                    if ($stmt_insert->execute()) {
                        echo json_encode(['success' => true, 'message' => 'Stay In Hostel request submitted successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to submit request: ' . $stmt_insert->error]);
                    }
                    $stmt_insert->close();
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'get_student_stay_hostel_requests':
            try {
                if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    break;
                }

                $user_id = (int)$_SESSION['user_id'];
                $stmt_student = $conn->prepare("SELECT student_id FROM students WHERE user_id = ? LIMIT 1");
                if (!$stmt_student) {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                    break;
                }
                $stmt_student->bind_param("i", $user_id);
                $stmt_student->execute();
                $res_student = $stmt_student->get_result();
                $student = $res_student ? $res_student->fetch_assoc() : null;
                $stmt_student->close();

                if (!$student) {
                    echo json_encode(['success' => false, 'message' => 'Student record not found']);
                    break;
                }

                $student_id = (int)$student['student_id'];

                $stmt = $conn->prepare("
                    SELECT
                        request_id,
                        from_date,
                        to_date,
                        reason,
                        proof_path,
                        requested_at
                    FROM stay_in_hostel_requests
                    WHERE student_id = ?
                    ORDER BY requested_at DESC
                ");
                if (!$stmt) {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                    break;
                }
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $result = $stmt->get_result();

                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                $stmt->close();

                echo json_encode(['success' => true, 'rows' => $rows]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'get_admin_stay_hostel_requests':
            try {
                $session_role = $_SESSION['role'] ?? ($_SESSION['user_type'] ?? '');
                if (!is_any_admin_role($session_role)) {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    break;
                }

                $scopeGender = get_current_admin_scope_gender();

                $sql = "
                    SELECT
                        sr.request_id,
                        sr.student_id,
                        s.roll_number,
                        s.name AS student_name,
                        s.department,
                        s.year_of_study,
                        sr.from_date,
                        sr.to_date,
                        sr.reason,
                        sr.proof_path,
                        sr.requested_at
                    FROM stay_in_hostel_requests sr
                    INNER JOIN students s ON s.student_id = sr.student_id
                    LEFT JOIN rooms r ON s.room_id = r.room_id
                    LEFT JOIN hostels h ON r.hostel_id = h.hostel_id";

                if ($scopeGender !== null) {
                    $sql .= " WHERE h.gender = ?";
                }

                $sql .= " ORDER BY sr.requested_at DESC";

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                    break;
                }

                if ($scopeGender !== null) {
                    $stmt->bind_param('s', $scopeGender);
                }

                $stmt->execute();
                $result = $stmt->get_result();

                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                $stmt->close();

                echo json_encode(['success' => true, 'rows' => $rows]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'get_stay_hostel_counts':
            try {
                $session_role = $_SESSION['role'] ?? ($_SESSION['user_type'] ?? '');
                if (!is_any_admin_role($session_role)) {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    break;
                }

                $scopeGender = get_current_admin_scope_gender();

                $sql = "
                    SELECT
                        COUNT(*) AS total,
                        SUM(CASE WHEN DATE(requested_at) = CURDATE() THEN 1 ELSE 0 END) AS today
                    FROM stay_in_hostel_requests
                ";
                if ($scopeGender !== null) {
                    $safeGender = mysqli_real_escape_string($conn, $scopeGender);
                    $sql = "
                    SELECT
                        COUNT(*) AS total,
                        SUM(CASE WHEN DATE(sr.requested_at) = CURDATE() THEN 1 ELSE 0 END) AS today
                    FROM stay_in_hostel_requests sr
                    INNER JOIN students s ON s.student_id = sr.student_id
                    LEFT JOIN rooms r ON s.room_id = r.room_id
                    LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                    WHERE h.gender = '" . $safeGender . "'
                ";
                }
                $res = $conn->query($sql);
                if (!$res) {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                    break;
                }
                $row = $res->fetch_assoc();
                echo json_encode([
                    'success' => true,
                    'counts' => [
                        'total' => (int)($row['total'] ?? 0),
                        'today' => (int)($row['today'] ?? 0)
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'admin_update_stay_hostel_request':
            try {
                $session_role = $_SESSION['role'] ?? ($_SESSION['user_type'] ?? '');
                if (!is_any_admin_role($session_role)) {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    break;
                }

                $scopeGender = get_current_admin_scope_gender();

                $request_id = (int)($_POST['request_id'] ?? 0);
                $from_date = trim($_POST['from_date'] ?? '');
                $to_date = trim($_POST['to_date'] ?? '');
                $reason = trim($_POST['reason'] ?? '');

                if ($request_id <= 0 || $from_date === '' || $to_date === '' || $reason === '') {
                    echo json_encode(['success' => false, 'message' => 'All fields are required']);
                    break;
                }

                if (strtotime($from_date) > strtotime($to_date)) {
                    echo json_encode(['success' => false, 'message' => 'From date cannot be greater than To date']);
                    break;
                }

                if ($scopeGender !== null) {
                    $checkSql = "SELECT sr.request_id FROM stay_in_hostel_requests sr INNER JOIN students s ON s.student_id = sr.student_id LEFT JOIN rooms r ON s.room_id = r.room_id LEFT JOIN hostels h ON r.hostel_id = h.hostel_id WHERE sr.request_id = ? AND h.gender = ? LIMIT 1";
                    $checkStmt = $conn->prepare($checkSql);
                    $checkStmt->bind_param('is', $request_id, $scopeGender);
                    $checkStmt->execute();
                    $checkRes = $checkStmt->get_result();
                    $allowed = $checkRes && $checkRes->num_rows > 0;
                    $checkStmt->close();
                    if (!$allowed) {
                        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                        break;
                    }
                }

                $stmt = $conn->prepare("UPDATE stay_in_hostel_requests SET from_date = ?, to_date = ?, reason = ? WHERE request_id = ?");
                if (!$stmt) {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                    break;
                }
                $stmt->bind_param("sssi", $from_date, $to_date, $reason, $request_id);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Request updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update request: ' . $stmt->error]);
                }
                $stmt->close();
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'delete_stay_hostel_request':
            try {
                $request_id = (int)($_POST['request_id'] ?? 0);
                if ($request_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
                    break;
                }

                $role = $_SESSION['role'] ?? ($_SESSION['user_type'] ?? '');
                $student_id = null;

                if ($role === 'student') {
                    if (!isset($_SESSION['user_id'])) {
                        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                        break;
                    }
                    $user_id = (int)$_SESSION['user_id'];
                    $stmt_student = $conn->prepare("SELECT student_id FROM students WHERE user_id = ? LIMIT 1");
                    if (!$stmt_student) {
                        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                        break;
                    }
                    $stmt_student->bind_param("i", $user_id);
                    $stmt_student->execute();
                    $res_student = $stmt_student->get_result();
                    $student = $res_student ? $res_student->fetch_assoc() : null;
                    $stmt_student->close();
                    if (!$student) {
                        echo json_encode(['success' => false, 'message' => 'Student record not found']);
                        break;
                    }
                    $student_id = (int)$student['student_id'];

                    $stmt_find = $conn->prepare("SELECT proof_path FROM stay_in_hostel_requests WHERE request_id = ? AND student_id = ? LIMIT 1");
                    if (!$stmt_find) {
                        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                        break;
                    }
                    $stmt_find->bind_param("ii", $request_id, $student_id);
                } elseif (is_any_admin_role($role)) {
                    $stmt_find = $conn->prepare("SELECT proof_path FROM stay_in_hostel_requests WHERE request_id = ? LIMIT 1");
                    if (!$stmt_find) {
                        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                        break;
                    }
                    $stmt_find->bind_param("i", $request_id);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                    break;
                }

                $stmt_find->execute();
                $res_find = $stmt_find->get_result();
                $row_find = $res_find ? $res_find->fetch_assoc() : null;
                $stmt_find->close();

                if (!$row_find) {
                    echo json_encode(['success' => false, 'message' => 'Request not found']);
                    break;
                }

                if (is_gender_admin_role($role)) {
                    $scopeGender = get_current_admin_scope_gender();
                    $checkSql = "SELECT sr.request_id FROM stay_in_hostel_requests sr INNER JOIN students s ON s.student_id = sr.student_id LEFT JOIN rooms r ON s.room_id = r.room_id LEFT JOIN hostels h ON r.hostel_id = h.hostel_id WHERE sr.request_id = ? AND h.gender = ? LIMIT 1";
                    $checkStmt = $conn->prepare($checkSql);
                    $checkStmt->bind_param('is', $request_id, $scopeGender);
                    $checkStmt->execute();
                    $checkRes = $checkStmt->get_result();
                    $allowed = $checkRes && $checkRes->num_rows > 0;
                    $checkStmt->close();
                    if (!$allowed) {
                        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                        break;
                    }
                }

                $proof_path = $row_find['proof_path'] ?? null;

                if ($role === 'student') {
                    $stmt_delete = $conn->prepare("DELETE FROM stay_in_hostel_requests WHERE request_id = ? AND student_id = ?");
                    if (!$stmt_delete) {
                        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                        break;
                    }
                    $stmt_delete->bind_param("ii", $request_id, $student_id);
                } else {
                    $stmt_delete = $conn->prepare("DELETE FROM stay_in_hostel_requests WHERE request_id = ?");
                    if (!$stmt_delete) {
                        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                        break;
                    }
                    $stmt_delete->bind_param("i", $request_id);
                }

                if ($stmt_delete->execute()) {
                    if (!empty($proof_path)) {
                        $abs = __DIR__ . '/' . ltrim($proof_path, '/');
                        if (file_exists($abs)) {
                            @unlink($abs);
                        }
                    }
                    echo json_encode(['success' => true, 'message' => 'Request deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete request: ' . $stmt_delete->error]);
                }
                $stmt_delete->close();
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;



        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . htmlspecialchars($action)]);
            break;
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No action specified']);
}
