<?php
session_start();
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include '../db.php';
include './admin_scope.php';

if (!is_any_admin_role()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$scopeGender = get_hostel_gender_scope_for_role();

// Test database connection
if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit;
}

// Debug: Check what database we're connected to
$database_result = $conn->query("SELECT DATABASE() as db_name");
if ($database_result) {
    $database_row = $database_result->fetch_assoc();
    error_log("API Connected to database: " . $database_row['db_name']);
} else {
    error_log("API Failed to get database name: " . $conn->error);
}

date_default_timezone_set('Asia/Kolkata');

// Only set JSON header for API requests, not for export requests
$action = strtolower($_GET['action'] ?? '');

function respond(array $payload, int $status = 200): void
{
    // Ensure client receives proper JSON Content-Type for API responses
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function bindStatementParams(mysqli_stmt $stmt, string $types, array &$params): void
{
    $bindValues = [];
    $bindValues[] = $types;
    foreach ($params as $key => $value) {
        $bindValues[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindValues);
}

// Functions for pending case
function fetch_pending(mysqli $conn): array
{
    $sql = "SELECT la.*, s.name AS student_name, lt.Leave_Type_Name, r.room_number
            FROM leave_applications la
            JOIN students s ON la.Reg_No = s.roll_number
            LEFT JOIN rooms r ON s.room_id = r.room_id
            JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
            WHERE la.Status IN ('Pending', 'Forwarded to Admin')
              AND la.LeaveType_ID <> 1
            ORDER BY la.Applied_Date DESC";
    $rows = [];
    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function normalize_rows(array $rows): array
{
    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'reg' => $row['Reg_No'] ?? '',
            'name' => $row['student_name'] ?? '',
            'room' => $row['room_number'] ?? '-',
            'type' => $row['Leave_Type_Name'] ?? '',
            'applied' => !empty($row['Applied_Date']) ? date('d-m-Y H:i', strtotime($row['Applied_Date'])) : '',
            'from' => !empty($row['From_Date']) ? date('d-m-Y H:i', strtotime($row['From_Date'])) : '',
            'to' => !empty($row['To_Date']) ? date('d-m-Y H:i', strtotime($row['To_Date'])) : '',
            'reason' => $row['Reason'] ?? '',
            'status' => $row['Status'] ?? ''
        ];
    }
    return $out;
}

// Functions for approved case
function fetch_approved(mysqli $conn): array
{
    $sql = "SELECT la.*, s.name AS student_name, lt.Leave_Type_Name, r.room_number
            FROM leave_applications la
            JOIN students s ON la.Reg_No = s.roll_number
            LEFT JOIN rooms r ON s.room_id = r.room_id
            JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
            WHERE la.Status = 'Approved'
            ORDER BY la.Applied_Date DESC";
    $rows = [];
    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

// Functions for report_data, report_pdf and report_excel cases
function get_department_variations(mysqli $conn, string $selectedDepartment): array
{
    // Get all distinct departments from the database
    $departments = [];
    global $scopeGender;
    $sql = "SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department <> ''";
    if ($scopeGender !== null) {
        $sql .= " AND gender = '" . $conn->real_escape_string($scopeGender) . "'";
    }
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row['department'];
        }
    }

    // Always find all departments that map to the same short name as the selected department
    $targetShortName = shorten_department_name($selectedDepartment);
    $matchingDepartments = [];

    foreach ($departments as $dept) {
        if (shorten_department_name($dept) === $targetShortName) {
            $matchingDepartments[] = $dept;
        }
    }

    return $matchingDepartments ?: [$selectedDepartment];
}

function fetch_report_data(mysqli $conn, array $filters = []): array
{
    global $scopeGender;
    // Debug: Check what database we're connected to in this function
    $database_result = $conn->query("SELECT DATABASE() as db_name");
    if ($database_result) {
        $database_row = $database_result->fetch_assoc();
        error_log("fetch_report_data Connected to database: " . $database_row['db_name']);
    } else {
        error_log("fetch_report_data Failed to get database name: " . $conn->error);
    }

    $reportDate = $filters['report_date'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
        $reportDate = date('Y-m-d');
    }

    $department = trim($filters['department'] ?? '');
    $hostel = trim($filters['hostel'] ?? '');
    if ($scopeGender !== null && $hostel !== '') {
        $checkStmt = $conn->prepare("SELECT hostel_id FROM hostels WHERE hostel_name = ? AND gender = ? LIMIT 1");
        if ($checkStmt) {
            $checkStmt->bind_param('ss', $hostel, $scopeGender);
            $checkStmt->execute();
            $checkRes = $checkStmt->get_result();
            $isAllowedHostel = $checkRes && $checkRes->num_rows > 0;
            $checkStmt->close();
            if (!$isAllowedHostel) {
                return [];
            }
        }
    }
    $block = trim($filters['block'] ?? '');
    $floor = trim($filters['floor'] ?? '');
    $roomType = trim($filters['room_type'] ?? '');
    $academicBatch = trim($filters['academic_batch'] ?? '');
    $vacatedOnly = (isset($filters['vacated_only']) && $filters['vacated_only'] == '1');
    $typeOfStay = trim($filters['type_of_stay'] ?? 'all');
    $newlyJoinedMonth = $filters['newly_joined_month'] ?? 'all';
    $monthFilter = $filters['month_filter'] ?? 'all';

    $monthNumber = null;
    if ($newlyJoinedMonth !== 'all' && preg_match('/^(0[1-9]|1[0-2])$/', $newlyJoinedMonth)) {
        $monthNumber = (int) $newlyJoinedMonth;
    }

    $filterMonthNumber = null;
    if ($monthFilter !== 'all' && preg_match('/^(0[1-9]|1[0-2])$/', $monthFilter)) {
        $filterMonthNumber = (int) $monthFilter;
    }

    $rawAttendanceFilters = $filters['attendance_filters'] ?? '';
    $attendanceFiltersInput = [];
    if (is_array($rawAttendanceFilters)) {
        $attendanceFiltersInput = $rawAttendanceFilters;
    } elseif (is_string($rawAttendanceFilters) && $rawAttendanceFilters !== '') {
        $attendanceFiltersInput = explode(',', $rawAttendanceFilters);
    }

    $attendanceMap = [
        'present' => 'present',
        'absent' => 'absent',
        'onduty' => 'onduty',
        'od' => 'onduty',
        'onleave' => 'onleave',
        'studentsonleave' => 'onleave',
        'lateentry' => 'lateentry',
        'lateentrystudents' => 'lateentry',
        'blocked' => 'blocked',
        'blockedstudents' => 'blocked',
    ];

    $attendanceFilters = [];
    foreach ($attendanceFiltersInput as $input) {
        $normalized = strtolower(preg_replace('/[^a-z]/', '', (string) $input));
        if ($normalized === '') {
            continue;
        }
        if (isset($attendanceMap[$normalized])) {
            $attendanceFilters[] = $attendanceMap[$normalized];
        }
    }
    $attendanceFilters = array_values(array_unique($attendanceFilters));

    // Different approach for vacated vs active students
    if ($vacatedOnly) {
        // For vacated students, get data from vacated_students_history table
        $whereClauses = [];
        $types = 's';
        $params = [$reportDate];

        // Add basic status filter
        $whereClauses[] = "s.status = '1'";
        if ($scopeGender !== null) {
            $whereClauses[] = "s.gender = ?";
            $types .= 's';
            $params[] = $scopeGender;
        }

        // Add filters for vacated students
        if ($department !== '') {
            // Get all department variations that map to the same short name
            $departmentVariations = get_department_variations($conn, $department);
            if (!empty($departmentVariations)) {
                $placeholders = str_repeat('?,', count($departmentVariations) - 1) . '?';
                $whereClauses[] = "COALESCE(s.department, vsh.department) IN ($placeholders)";
                $types .= str_repeat('s', count($departmentVariations));
                $params = array_merge($params, $departmentVariations);
            } else {
                $whereClauses[] = "COALESCE(s.department, vsh.department) = ?";
                $types .= 's';
                $params[] = $department;
            }
        }
        if ($hostel !== '') {
            $whereClauses[] = "COALESCE(h.hostel_name, vsh.hostel_name) = ?";
            $types .= 's';
            $params[] = $hostel;
        }
        if ($block !== '') {
            $whereClauses[] = "r.block = ?";
            $types .= 's';
            $params[] = $block;
        }
        if ($floor !== '') {
            $whereClauses[] = "r.floor = ?";
            $types .= 's';
            $params[] = $floor;
        }
        if ($roomType !== '') {
            $whereClauses[] = "r.room_type = ?";
            $types .= 's';
            $params[] = $roomType;
        }
        if ($academicBatch !== '') {
            $whereClauses[] = "COALESCE(s.academic_batch, vsh.academic_batch) = ?";
            $types .= 's';
            $params[] = $academicBatch;
        }
        // Add month filter condition for vacated students
        if ($filterMonthNumber !== null) {
            $whereClauses[] = "MONTH(vsh.vacated_at) = ?";
            $types .= 'i';
            $params[] = $filterMonthNumber;
        }
        // Add type of stay filter for vacated students
        if ($typeOfStay !== 'all') {
            if ($typeOfStay === 'temporary') {
                $whereClauses[] = "EXISTS (SELECT 1 FROM temporary_stay ts WHERE ts.student_id = s.student_id)";
            } else if ($typeOfStay === 'permanent') {
                $whereClauses[] = "NOT EXISTS (SELECT 1 FROM temporary_stay ts WHERE ts.student_id = s.student_id)";
            }
        }

        $query = "
            SELECT 
                s.student_id,
                COALESCE(s.name, vsh.student_name) as student_name,
                COALESCE(s.roll_number, vsh.roll_number) as roll_number,
                COALESCE(s.department, vsh.department) as department,
                COALESCE(s.academic_batch, vsh.academic_batch) as academic_batch,
                s.date_of_join,
                vsh.assigned_at,
                vsh.vacated_at,
                0 as is_active,
                vsh.room_number,
                r.block,
                r.floor,
                r.room_type,
                COALESCE(h.hostel_name, vsh.hostel_name) as hostel_name,
                a.status AS attendance_status,
                a.marked_at,
                b.latest_blocked_at
            FROM vacated_students_history vsh
            LEFT JOIN students s ON vsh.student_id = s.student_id
            LEFT JOIN rooms r ON vsh.room_id = r.room_id
            LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
            LEFT JOIN attendance a ON a.student_id = s.student_id AND a.date = ?
            LEFT JOIN (
                SELECT student_id, MAX(blocked_at) AS latest_blocked_at
                FROM blocked_students
                GROUP BY student_id
            ) b ON b.student_id = s.student_id
            WHERE " . implode(' AND ', $whereClauses) . "
            ORDER BY COALESCE(h.hostel_name, vsh.hostel_name), r.block, r.floor, vsh.room_number, COALESCE(s.roll_number, vsh.roll_number)
        ";
    } else {
        // For active students, use the existing approach
        $assignmentJoin = "
            INNER JOIN (
                SELECT rs1.*
                FROM room_students rs1
                WHERE rs1.is_active = 1
                AND (rs1.vacated_at IS NULL OR rs1.vacated_at = '0000-00-00 00:00:00')
                AND rs1.id = (
                    SELECT MAX(rs2.id) 
                    FROM room_students rs2 
                    WHERE rs2.student_id = rs1.student_id 
                    AND rs2.is_active = 1
                )
            ) rs ON rs.student_id = s.student_id
        ";

        $whereClauses = ["s.status = '1'"];
        $types = 's';
        $params = [$reportDate];
        if ($scopeGender !== null) {
            $whereClauses[] = "s.gender = ?";
            $types .= 's';
            $params[] = $scopeGender;
        }

        if ($department !== '') {
            // Get all department variations that map to the same short name
            $departmentVariations = get_department_variations($conn, $department);
            if (!empty($departmentVariations)) {
                $placeholders = str_repeat('?,', count($departmentVariations) - 1) . '?';
                $whereClauses[] = "s.department IN ($placeholders)";
                $types .= str_repeat('s', count($departmentVariations));
                $params = array_merge($params, $departmentVariations);
            } else {
                $whereClauses[] = "s.department = ?";
                $types .= 's';
                $params[] = $department;
            }
        }
        if ($hostel !== '') {
            $whereClauses[] = "h.hostel_name = ?";
            $types .= 's';
            $params[] = $hostel;
        }
        if ($block !== '') {
            $whereClauses[] = "r.block = ?";
            $types .= 's';
            $params[] = $block;
        }
        if ($floor !== '') {
            $whereClauses[] = "r.floor = ?";
            $types .= 's';
            $params[] = $floor;
        }
        if ($roomType !== '') {
            $whereClauses[] = "r.room_type = ?";
            $types .= 's';
            $params[] = $roomType;
        }
        if ($academicBatch !== '') {
            $whereClauses[] = "s.academic_batch = ?";
            $types .= 's';
            $params[] = $academicBatch;
        }
        // Add month filter condition for active students (based on date_of_join) to main WHERE clause
        if ($filterMonthNumber !== null) {
            $whereClauses[] = "MONTH(s.date_of_join) = ?";
            $types .= 'i';
            $params[] = $filterMonthNumber;
        }
        if ($monthNumber !== null) {
            $whereClauses[] = "MONTH(s.date_of_join) = ?";
            $types .= 'i';
            $params[] = $monthNumber;
        }
        // Add type of stay filter for active students
        if ($typeOfStay !== 'all') {
            if ($typeOfStay === 'temporary') {
                $whereClauses[] = "EXISTS (SELECT 1 FROM temporary_stay ts WHERE ts.student_id = s.student_id)";
            } else if ($typeOfStay === 'permanent') {
                $whereClauses[] = "NOT EXISTS (SELECT 1 FROM temporary_stay ts WHERE ts.student_id = s.student_id)";
            }
        }

        $query = "
            SELECT 
                s.student_id,
                s.name,
                s.roll_number,
                s.department,
                s.academic_batch,
                s.date_of_join,
                rs.assigned_at,
                rs.vacated_at,
                rs.is_active,
                r.room_number,
                r.block,
                r.floor,
                r.room_type,
                h.hostel_name,
                a.status AS attendance_status,
                a.marked_at,
                b.latest_blocked_at
            FROM students s
            {$assignmentJoin}
            LEFT JOIN rooms r ON rs.room_id = r.room_id
            LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
            LEFT JOIN attendance a ON a.student_id = s.student_id AND a.date = ?
            LEFT JOIN (
                SELECT student_id, MAX(blocked_at) AS latest_blocked_at
                FROM blocked_students
                GROUP BY student_id
            ) b ON b.student_id = s.student_id
            WHERE " . implode(' AND ', $whereClauses) . "
            ORDER BY h.hostel_name, r.block, r.floor, r.room_number, s.roll_number
        ";
    }

    error_log("Final query: " . $query);
    error_log("Query params: " . json_encode($params));

    // Prepare statement with error handling
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return [];
    }

    // Bind parameters with error handling
    if (!empty($params)) {
        bindStatementParams($stmt, $types, $params);
    }

    // Execute statement with error handling
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return [];
    }

    $result = $stmt->get_result();
    if (!$result) {
        error_log("Get result failed: " . $stmt->error);
        $stmt->close();
        return [];
    }

    $formatDate = function (?string $value, string $format = 'd M Y') {
        if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '-';
        }
        $timestamp = strtotime($value);
        return $timestamp ? date($format, $timestamp) : '-';
    };

    $formatTime = function (?string $value) {
        if (empty($value)) {
            return '-';
        }
        $timestamp = strtotime($value);
        return $timestamp ? date('h:i A', $timestamp) : '-';
    };

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $blocked = !empty($row['latest_blocked_at']);
        $attendanceStatus = $row['attendance_status'] ?? 'Absent';
        $statusKey = 'absent';
        $statusLabel = 'Absent';

        if ($blocked) {
            $statusKey = 'blocked';
            $statusLabel = 'Blocked';
        } else {
            switch ($attendanceStatus) {
                case 'Present':
                    $statusKey = 'present';
                    $statusLabel = 'Present';
                    break;
                case 'Late Entry':
                    $statusKey = 'lateentry';
                    $statusLabel = 'Late Entry';
                    break;
                case 'On Duty':
                    $statusKey = 'onduty';
                    $statusLabel = 'On Duty';
                    break;
                case 'On Leave':
                    $statusKey = 'onleave';
                    $statusLabel = 'Students on Leave';
                    break;
                case 'Absent':
                default:
                    $statusKey = 'absent';
                    $statusLabel = 'Absent';
                    break;
            }
        }

        if (!empty($attendanceFilters) && !in_array($statusKey, $attendanceFilters, true)) {
            continue;
        }

        // Determine residency status based on room assignment
        if ($vacatedOnly) {
            // For vacated students, we're specifically fetching from vacated_students_history
            $vacatedDate = $formatDate($row['vacated_at'], 'd M Y');
            $residencyStatus = 'Vacated(' . $vacatedDate . ')';
        } else if ($row['is_active'] == 1) {
            // For active students
            $residencyStatus = 'Active';
        } else if ($row['is_active'] == 0 && !empty($row['vacated_at']) && $row['vacated_at'] !== '0000-00-00 00:00:00') {
            // For students who have been vacated
            $vacatedDate = $formatDate($row['vacated_at'], 'd M Y');
            $residencyStatus = 'Vacated(' . $vacatedDate . ')';
        } else {
            $residencyStatus = 'Unknown';
        }

        // Combine room number and room type for AC rooms
        $roomDisplay = $row['room_number'] ?? '-';
        if (!empty($row['room_type']) && $row['room_type'] === 'AC') {
            $roomDisplay = ($row['room_number'] ?? '-') . '(AC)';
        }

        $data[] = [
            'student_id' => (int) $row['student_id'],
            'student_name' => $row['student_name'] ?? $row['name'],
            'roll_number' => $row['roll_number'],
            'department' => shorten_department_name($row['department']),
            'academic_batch' => $row['academic_batch'],
            'hostel_name' => $row['hostel_name'] ?? '-',
            'block' => $row['block'] ?? '-',
            'floor' => $row['floor'] ?? '-',
            'room_number' => $roomDisplay,
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
            'attendance_time' => $formatTime($row['marked_at']),
            'date_of_join' => $formatDate($row['date_of_join']),
            'residency_status' => $residencyStatus,
        ];
    }

    $stmt->close();
    return $data;
}

function format_filter_info(array $filters): string
{
    $filterInfo = [];

    // Only add department filter if it's not "All Departments" (empty string)
    if (!empty($filters['department'])) {
        $filterInfo[] = 'Department: ' . $filters['department'];
    }

    // Only add hostel filter if it's not "All Hostels" (empty string)
    if (!empty($filters['hostel'])) {
        $filterInfo[] = 'Hostel: ' . $filters['hostel'];
    }

    // Only add block filter if it's not "All Blocks" (empty string)
    if (!empty($filters['block'])) {
        $filterInfo[] = 'Block: ' . $filters['block'];
    }

    // Only add floor filter if it's not "All Floors" (empty string)
    if (!empty($filters['floor'])) {
        $filterInfo[] = 'Floor: ' . $filters['floor'];
    }

    // Only add room type filter if it's not "All Room Types" (empty string)
    if (!empty($filters['room_type'])) {
        $filterInfo[] = 'Room Type: ' . $filters['room_type'];
    }

    // Only add academic batch filter if it's not "All Batches" (empty string)
    if (!empty($filters['academic_batch'])) {
        $filterInfo[] = 'Academic Batch: ' . $filters['academic_batch'];
    }

    if (!empty($filters['report_date'])) {
        $filterInfo[] = 'Report Date: ' . $filters['report_date'];
    }

    // Only show residency filter if it's not the default (active residents)
    if (!empty($filters['vacated_only']) && $filters['vacated_only'] == '1') {
        $filterInfo[] = 'Residency: Vacated Students';
    } elseif (empty($filters['vacated_only'])) {
        // Only show "Active Residents" if it's explicitly selected or default
        // But we'll skip showing it to keep the filter info concise
    }

    // Only add newly joined month filter if it's not "All Months" (value 'all')
    if (!empty($filters['newly_joined_month']) && $filters['newly_joined_month'] !== 'all') {
        $months = [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December'
        ];
        $monthName = $months[$filters['newly_joined_month']] ?? $filters['newly_joined_month'];
        $filterInfo[] = 'Joined Month: ' . $monthName;
    }

    // Only add month filter if it's not "All Months" (value 'all')
    if (!empty($filters['month_filter']) && $filters['month_filter'] !== 'all') {
        $months = [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December'
        ];
        $monthName = $months[$filters['month_filter']] ?? $filters['month_filter'];
        $filterInfo[] = 'Month Filter: ' . $monthName;
    }

    // Only add attendance filters if they're not all selected (non-empty)
    if (!empty($filters['attendance_filters'])) {
        $attendanceMap = [
            'present' => 'Present',
            'absent' => 'Absent',
            'onduty' => 'On Duty',
            'onleave' => 'Students on Leave',
            'lateentry' => 'Late Entry Students',
            'blocked' => 'Blocked'
        ];

        $filtersList = explode(',', $filters['attendance_filters']);
        $attendanceLabels = [];
        foreach ($filtersList as $filter) {
            if (isset($attendanceMap[$filter])) {
                $attendanceLabels[] = $attendanceMap[$filter];
            }
        }

        // Only show attendance filter if not all filters are selected
        $allAttendanceFilters = ['present', 'absent', 'onduty', 'onleave', 'lateentry', 'blocked'];
        sort($filtersList);
        sort($allAttendanceFilters);

        if (!empty($attendanceLabels) && $filtersList !== $allAttendanceFilters) {
            $filterInfo[] = 'Attendance Status: ' . implode(', ', $attendanceLabels);
        }
    }

    // Only add type of stay filter if it's not "All" (value 'all')
    if (!empty($filters['type_of_stay']) && $filters['type_of_stay'] !== 'all') {
        $typeOfStayLabels = [
            'temporary' => 'Temporary',
            'permanent' => 'Permanent'
        ];
        if (isset($typeOfStayLabels[$filters['type_of_stay']])) {
            $filterInfo[] = 'Type of Stay: ' . $typeOfStayLabels[$filters['type_of_stay']];
        }
    }

    return implode(' | ', $filterInfo);
}

function shorten_department_name(string $department): string
{
    $shortNames = [
        // AIDS variations
        'Artificial Intelligence & Data Science' => 'AIDS',
        'Artificial Intelligence & Data Science (AIDS)' => 'AIDS',
        'artificial_intelligence_data_science' => 'AIDS',
        'Artificial Intelligence & Data Science AIDS' => 'AIDS',

        // CSE variations
        'Computer Science & Engineering' => 'CSE',
        'computer_science_engineering' => 'CSE',
        'Computer Science and Business Systems' => 'CSBS',
        'computer_science_and_business_systems' => 'CSBS',
        'computer_science_business_systems' => 'CSBS',

        // IT variations
        'Information Technology' => 'IT',
        'information_technology' => 'IT',

        // MECH variations
        'Mechanical Engineering' => 'MECH',
        'mechanical_engineering' => 'MECH',
        'Mech' => 'MECH',

        // CIVIL variations
        'Civil Engineering' => 'CIVIL',
        'civil_engineering' => 'CIVIL',

        // ECE variations
        'Electronics & Communication Engineering' => 'ECE',
        'electronics_&_communication_engineering' => 'ECE',
        'electronics_communication_engineering' => 'ECE',

        // EEE variations
        'Electrical & Electronics Engineering' => 'EEE',
        'electrical_electronics_engineering' => 'EEE',

        // EIE variations
        'Electronics & Instrumentation Engineering' => 'EIE',
        'electronics_instrumentation_engineering' => 'EIE',

        // VLSI variations
        'VLSI Design' => 'VLSI',
        'vlsi_design' => 'VLSI'
    ];

    // Try exact match first
    if (isset($shortNames[$department])) {
        return $shortNames[$department];
    }

    // Try lowercase match
    $lowerDepartment = strtolower($department);
    $lowerShortNames = [];
    foreach ($shortNames as $key => $value) {
        $lowerShortNames[strtolower($key)] = $value;
    }

    return $lowerShortNames[$lowerDepartment] ?? $department;
}

function normalize_report_rows(array $rows): array
{
    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'roll_number' => $row['roll_number'] ?? '',
            'student_name' => $row['student_name'] ?? '',
            'department' => shorten_department_name($row['department'] ?? ''),
            'academic_batch' => $row['academic_batch'] ?? '',
            'hostel_name' => $row['hostel_name'] ?? '-',
            'block' => $row['block'] ?? '-',
            'floor' => $row['floor'] ?? '-',
            'room_number' => $row['room_number'] ?? '-',
            'status_key' => $row['status_key'] ?? 'absent',
            'status_label' => $row['status_label'] ?? '',
            'attendance_time' => $row['attendance_time'] ?? '-',
            'date_of_join' => $row['date_of_join'] ?? '-',
            'residency_status' => $row['residency_status'] ?? ''
        ];
    }
    return $out;
}

function output_pdf(string $title, array $data, array $cols, string $filterInfo = ''): void
{
    // Locate and include TCPDF. The project may keep TCPDF in different places
    // (e.g. project root `TCPDF/` or `admin/TCPDF/`). Try a few sensible paths
    // and show a helpful error if none are present.
    $tcpdf_candidates = [
        __DIR__ . '/TCPDF/tcpdf.php',         // admin/TCPDF/tcpdf.php
        __DIR__ . '/../TCPDF/tcpdf.php',      // project-root/TCPDF/tcpdf.php
        __DIR__ . '/TCPDF-main/tcpdf.php',    // admin/TCPDF-main/tcpdf.php (possible)
        __DIR__ . '/../faculty/TCPDF-main/tcpdf.php', // faculty bundle
        __DIR__ . '/../TCPDF/tcpdf.php'
    ];

    $tcpdf_included = false;
    foreach ($tcpdf_candidates as $candidate) {
        if (file_exists($candidate)) {
            require_once $candidate;
            $tcpdf_included = true;
            break;
        }
    }

    if (!$tcpdf_included) {
        // Provide a clearer runtime error to help debugging on developer machines
        $msg  = "TCPDF library not found. Tried these paths: \n" . implode("\n", $tcpdf_candidates) . "\n";
        $msg .= "Please install TCPDF in one of the above locations or update the path in admin/report_api.php.";
        // If this is running in a web context, emit an HTML-friendly error and exit.
        if (php_sapi_name() !== 'cli') {
            header('Content-Type: text/plain; charset=utf-8');
        }
        die($msg);
    }

    class ReportPDF extends TCPDF
    {
        public $hTitle = '';
        public $leftLogoPath = '';
        public $rightLogoPath = '';
        public $filterInfo = '';

        public function Header()
        {
            // Get page width for positioning
            $pageWidth = $this->getPageWidth();
            $lMargin = $this->lMargin;
            $rMargin = $this->rMargin;

            // Logo size
            $logoSize = 18;
            $rightX = $pageWidth - 15 - $logoSize; // 15mm right margin

            // Add left logo if path is set
            if (!empty($this->leftLogoPath) && file_exists($this->leftLogoPath)) {
                $this->Image($this->leftLogoPath, 15, 10, $logoSize, $logoSize, '', '', '', false, 300, '', false, false, 0);
            }

            // Add right logo if path is set
            if (!empty($this->rightLogoPath) && file_exists($this->rightLogoPath)) {
                $this->Image($this->rightLogoPath, $rightX, 10, $logoSize, $logoSize, '', '', '', false, 300, '', false, false, 0);
            }

            // Calculate center position between logos
            $leftLogoRightEdge = 15 + $logoSize; // right edge of left logo
            $rightLogoLeftEdge = $rightX;       // left edge of right logo
            $logosCenterX = ($leftLogoRightEdge + $rightLogoLeftEdge) / 2;

            // Institution header centered between logos
            $this->SetFont('helvetica', 'B', 14);
            $this->SetXY(0, 25);
            $this->Cell($pageWidth, 8, 'M.Kumarasamy College of Engineering, Karur - 639 113', 0, 1, 'C');

            $this->SetFont('helvetica', 'I', 10);
            $this->SetXY(0, 33);
            $this->Cell($pageWidth, 6, '(An Autonomous Institution Affiliated to Anna University, Chennai)', 0, 1, 'C');

            // Report title
            $this->SetFont('helvetica', 'B', 11);
            $this->SetTextColor(0, 0, 0); // Black color
            $formattedDate = date('d/m/Y');
            $reportTitle = $this->hTitle . ' (' . $formattedDate . ')';
            $this->SetXY(0, 46);
            $this->Cell($pageWidth, 10, $reportTitle, 0, 1, 'C');

            // Generated date and Generated by Admin
            $this->SetFont('helvetica', '', 9);
            $dateY = 54; // Position below the title
            $this->SetXY(15, $dateY);
            $this->Cell(0, 5, 'Generated Date: ' . $formattedDate, 0, 0, 'L');
            $this->SetXY(0, $dateY);
            $this->Cell($pageWidth - 15, 5, 'Generated by : Admin', 0, 1, 'R');

            // Divider below header
            $this->SetLineWidth(0.3);
            $this->SetDrawColor(180, 180, 180); // Gray color
            $this->Line(10, $dateY + 5, $pageWidth - 10, $dateY + 5);
            $this->Ln(15);
        }

        public function Footer()
        {
            $this->SetY(-15);
            $this->SetFont('dejavusans', 'B', 8);
            $this->SetTextColor(0, 0, 139); // Dark blue color
            $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
            $this->SetTextColor(0, 0, 0); // Reset to black
        }
    }

    $pdf = new ReportPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->hTitle = $title;
    $pdf->filterInfo = $filterInfo;

    // Set logo paths (update these paths to your actual logo files)
    $pdf->leftLogoPath = __DIR__ . '/image/mkce_logo2.jpg';
    $pdf->rightLogoPath = __DIR__ . '/image/logo-right.png';

    $pdf->SetCreator('Hostel Management System');
    $pdf->SetAuthor('Hostel Management System');
    $pdf->SetTitle($title);
    // Set appropriate margins for better table display
    $pdf->SetMargins(15, 70, 15);
    $pdf->SetHeaderMargin(55);
    $pdf->SetFooterMargin(15);
    $pdf->SetAutoPageBreak(true, 25);
    $pdf->SetFont('helvetica', '', 8);

    // Configure TCPDF for better table rendering
    $pdf->setCellHeightRatio(1.2);
    $pdf->setCellPadding(2);

    $pdf->AddPage();
    // Set initial X position for proper alignment
    $pdf->SetX(15);

    // Add filter information if available
    if (!empty($filterInfo)) {
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetX(15); // Align with page margins
        $pdf->MultiCell(180, 10, 'Applied Filters: ' . $filterInfo, 0, 'L', true);
        $pdf->Ln(12);
    }

    // Set table font
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);

    // Table header
    $pdf->SetFillColor(10, 162, 161); // #0aa2a1
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 8);

    // Column widths (matching the provided code style)
    $colWidths = [];
    $totalWidth = 180; // Standard table width for A4 portrait
    $totalPercentage = 0;
    foreach ($cols as $col) {
        // Convert percentage widths to actual mm values
        $widthPercent = (float) str_replace('%', '', $col['width']);
        $totalPercentage += $widthPercent;
    }

    // Adjust total width based on actual percentages
    $adjustedTotalWidth = ($totalPercentage > 100) ? $totalWidth * (100 / $totalPercentage) : $totalWidth;

    foreach ($cols as $col) {
        // Convert percentage widths to actual mm values
        $widthPercent = (float) str_replace('%', '', $col['width']);
        $colWidths[] = ($widthPercent / 100) * $adjustedTotalWidth;
    }

    // Draw header row
    $pdf->SetX(15); // Align with page margins
    foreach ($cols as $i => $col) {
        $label = $col['label'];
        $pdf->Cell($colWidths[$i], 6, $label, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Set X position for table to align with page margins
    $pdf->SetX(15);

    // Table body
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 7);

    if (empty($data)) {
        // No data row
        $pdf->SetX(15); // Align with page margins
        $pdf->Cell(array_sum($colWidths), 6, 'No records found.', 1, 0, 'C', true);
        $pdf->Ln();
    } else {
        $fill = false;
        foreach ($data as $row) {
            // Check if we need a page break (account for potential row height)
            $estimatedRowHeight = 8; // Estimate based on typical content
            if ($pdf->GetY() + $estimatedRowHeight > 240) { // Adjust based on page size for portrait
                $pdf->AddPage();
                // Re-draw header on new page
                $pdf->SetFillColor(10, 162, 161);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetX(15); // Align with page margins

                foreach ($cols as $i => $col) {
                    $label = $col['label'];
                    $pdf->Cell($colWidths[$i], 6, $label, 1, 0, 'C', true);
                }
                $pdf->Ln();
                $pdf->SetX(15); // Align with page margins
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('helvetica', '', 8);
            }

            $pdf->SetX(15); // Align with page margins
            foreach ($cols as $i => $col) {
                $key = $col['key'];
                $val = isset($row[$key]) ? $row[$key] : '';
                $align = ($col['class'] == 'c') ? 'C' : (($col['class'] == 'l') ? 'L' : 'R');
                $pdf->Cell($colWidths[$i], 5, $val, 1, 0, $align, $fill);
            }
            $pdf->Ln();
            $fill = !$fill; // Alternate row colors
        }
    }
    $fname = strtolower(str_replace(' ', '_', $title)) . '.pdf';
    $pdf->Output($fname, 'I');
    exit;
}

function output_excel(string $title, array $data, array $headers, string $filterInfo = ''): void
{
    // Generate simple HTML table with Excel headers so it downloads as .xls
    $fname = strtolower(str_replace(' ', '_', $title)) . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $fname);
    echo "\xEF\xBB\xBF"; // BOM
    echo '<html><head><meta charset="UTF-8"></head><body>';

    // Add filter information if available
    if (!empty($filterInfo)) {
        echo '<div style="font-weight:bold; margin-bottom:10px;">Filters: ' . htmlspecialchars($filterInfo) . '</div>';
    }

    echo '<table border="1" cellspacing="0" cellpadding="4">';
    echo '<tr style="background:#0aa2a1;color:#fff;font-weight:bold;">';
    foreach ($headers as $header) {
        echo '<th>' . $header . '</th>';
    }
    echo '</tr>';
    if (empty($data)) {
        echo '<tr><td colspan="' . count($headers) . '" align="center">No records found.</td></tr>';
    } else {
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
    }
    echo '</table></body></html>';
    exit;
}

// Handle all actions through switch-case
switch ($action) {
    case 'pending':
        $rows = fetch_pending($conn);
        $normalized_rows = normalize_rows($rows);
        respond(['success' => true, 'count' => count($normalized_rows), 'data' => $normalized_rows]);
        break;

    case 'approved':
        $rows = fetch_approved($conn);
        $normalized_rows = normalize_rows($rows);
        respond(['success' => true, 'count' => count($normalized_rows), 'data' => $normalized_rows]);
        break;

    case 'report_data':
        // Get filters from GET parameters
        $filters = [
            'report_date' => $_GET['report_date'] ?? '',
            'department' => $_GET['department'] ?? '',
            'hostel' => $_GET['hostel'] ?? '',
            'block' => $_GET['block'] ?? '',
            'floor' => $_GET['floor'] ?? '',
            'room_type' => $_GET['room_type'] ?? '',
            'academic_batch' => $_GET['academic_batch'] ?? '',
            'attendance_filters' => $_GET['attendance_filters'] ?? '',
            'vacated_only' => $_GET['vacated_only'] ?? '',
            'type_of_stay' => $_GET['type_of_stay'] ?? 'all',
            'newly_joined_month' => $_GET['newly_joined_month'] ?? '',
            'month_filter' => $_GET['month_filter'] ?? ''
        ];


    error_log('report_data: incoming filters: ' . json_encode($filters));
    $start_time = microtime(true);
    $rows = fetch_report_data($conn, $filters);
    $duration = microtime(true) - $start_time;
    error_log('report_data: fetch_report_data returned rows=' . count($rows) . ' duration=' . round($duration, 3) . 's');

    $normalized_rows = normalize_report_rows($rows);
    respond(['success' => true, 'count' => count($normalized_rows), 'data' => $normalized_rows]);
    case 'report_pdf':
        // Additional database connection validation for PDF export
        if (!$conn) {
            die('Database connection failed: Connection object is null');
        }

        if ($conn->connect_errno) {
            die('Database connection failed: ' . $conn->connect_error);
        }

        // Get filters from GET parameters
        $filters = [
            'report_date' => $_GET['report_date'] ?? '',
            'department' => $_GET['department'] ?? '',
            'hostel' => $_GET['hostel'] ?? '',
            'block' => $_GET['block'] ?? '',
            'floor' => $_GET['floor'] ?? '',
            'room_type' => $_GET['room_type'] ?? '',
            'academic_batch' => $_GET['academic_batch'] ?? '',
            'attendance_filters' => $_GET['attendance_filters'] ?? '',
            'vacated_only' => $_GET['vacated_only'] ?? '',
            'type_of_stay' => $_GET['type_of_stay'] ?? 'all',
            'newly_joined_month' => $_GET['newly_joined_month'] ?? '',
            'month_filter' => $_GET['month_filter'] ?? ''
        ];

        $rows = fetch_report_data($conn, $filters);
        $normalized_rows = normalize_report_rows($rows);

        // Format filter information
        $filterInfo = format_filter_info($filters);

        // Define columns for PDF with improved widths
        $cols = [
            ['key' => 'roll_number', 'label' => 'Roll No', 'width' => '15%', 'class' => 'c'],
            ['key' => 'student_name', 'label' => 'Name', 'width' => '25%', 'class' => 'l'],
            ['key' => 'department', 'label' => 'Dept', 'width' => '7%', 'class' => 'c'],
            ['key' => 'academic_batch', 'label' => 'Batch', 'width' => '13%', 'class' => 'c'],
            ['key' => 'hostel_name', 'label' => 'Hostel', 'width' => '13%', 'class' => 'c'],
            ['key' => 'block', 'label' => 'Block', 'width' => '6%', 'class' => 'c'],
            ['key' => 'floor', 'label' => 'Floor', 'width' => '6%', 'class' => 'c'],
            ['key' => 'room_number', 'label' => 'Room No', 'width' => '10%', 'class' => 'c'],
            ['key' => 'status_label', 'label' => 'Attend', 'width' => '11%', 'class' => 'c'],
            ['key' => 'attendance_time', 'label' => 'Attend Time', 'width' => '13%', 'class' => 'c'],
            ['key' => 'date_of_join', 'label' => 'DOJ', 'width' => '12%', 'class' => 'c'],
            ['key' => 'residency_status', 'label' => 'Residency', 'width' => '19%', 'class' => 'c'],
        ];

        output_pdf('Hostel Activity Report', $normalized_rows, $cols, $filterInfo);
        break;

    case 'report_excel':
        // Additional database connection validation for Excel export
        if (!$conn) {
            die('Database connection failed: Connection object is null');
        }

        if ($conn->connect_errno) {
            die('Database connection failed: ' . $conn->connect_error);
        }

        // Get filters from GET parameters
        $filters = [
            'report_date' => $_GET['report_date'] ?? '',
            'department' => $_GET['department'] ?? '',
            'hostel' => $_GET['hostel'] ?? '',
            'block' => $_GET['block'] ?? '',
            'floor' => $_GET['floor'] ?? '',
            'room_type' => $_GET['room_type'] ?? '',
            'academic_batch' => $_GET['academic_batch'] ?? '',
            'attendance_filters' => $_GET['attendance_filters'] ?? '',
            'vacated_only' => $_GET['vacated_only'] ?? '',
            'type_of_stay' => $_GET['type_of_stay'] ?? 'all',
            'newly_joined_month' => $_GET['newly_joined_month'] ?? '',
            'month_filter' => $_GET['month_filter'] ?? ''
        ];

        $rows = fetch_report_data($conn, $filters);
        $normalized_rows = normalize_report_rows($rows);

        // Format filter information
        $filterInfo = format_filter_info($filters);

        // Define headers for Excel
        $headers = [
            'Roll No',
            'Name',
            'Dept',
            'Batch',
            'Hostel',
            'Block',
            'Floor',
            'Room No',
            'Attend',
            'Attend Time',
            'DOJ',
            'Residency'
        ];

        output_excel('Hostel Activity Report', $normalized_rows, $headers, $filterInfo);
        break;

    default:
        respond(['success' => false, 'message' => 'Invalid action'], 400);
        break;
}