<?php
session_start();
include './admin_scope.php';

if (!is_any_admin_role()) {
    header('Location: ../login');
    exit;
}

// Handle AJAX API requests first (before any HTML output)
if (isset($_GET['api']) && $_GET['api'] === '1') {
    include '../db.php';
    

    if (!isset($conn) || !$conn) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }

    $rawHostelId = $_GET['hostel_id'] ?? null;
    $normalizedHostelId = normalize_hostel_id_for_current_admin($conn, $rawHostelId);
    if (is_gender_admin_role() && $normalizedHostelId === null) {
        $normalizedHostelId = get_default_hostel_id_for_current_admin($conn);
    }

    // Pass the connection and other parameters properly
    $params = [
        'conn' => $conn,
        'action' => $_GET['action'] ?? '',
        'hostel_id' => $normalizedHostelId,
        'date' => $_GET['date'] ?? date('Y-m-d')
    ];

    dispatchFunction('handleDashboardAPI', $params);
    // handleDashboardAPI calls exit, so code below won't execute
}

// Regular page rendering starts here
// Include required files
include '../db.php';

// Main dispatcher function that handles all function calls through a single switch case
function dispatchFunction($functionName, $params = []) {
    global $conn;
    
    switch ($functionName) {
        case 'getStudentsByStatus':
            $today = date('Y-m-d');
            $students = [];
            $sql = "SELECT s.name, s.roll_number, r.room_number, s.department, s.year_of_study, a.marked_at
                    FROM students s
                    LEFT JOIN rooms r ON s.room_id = r.room_id
                    LEFT JOIN attendance a ON a.student_id = s.student_id AND a.date = ?
                    LEFT JOIN blocked_students b ON s.student_id = b.student_id
                    WHERE s.status = '1' AND b.id IS NULL";
            
            $status = $params['status'] ?? 'present';
            
            if ($status === 'present') {
                $sql .= " AND a.status = 'Present'";
            } else {
                // For absent, we want students who are either explicitly marked as absent OR have no attendance record
                $sql .= " AND (a.status = 'Absent' OR a.status IS NULL)";
            }
            
            $sql .= " ORDER BY s.department, s.year_of_study, s.name";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('s', $today);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $students[] = $row;
                }
                $stmt->close();
            }
            return $students;
            
        case 'getAbsentStudents':
            return dispatchFunction('getStudentsByStatus', ['status' => 'absent']);
            
        case 'getBlockedStudents':
            $blocked = [];
            $sql = "SELECT b.id, s.roll_number, s.name, r.room_number, s.department, s.year_of_study,
                    b.type, b.blocked_at, 
                    a.date as attendance_date, a.status as attendance_status
                    FROM blocked_students b
                    INNER JOIN students s ON b.student_id = s.student_id
                    LEFT JOIN rooms r ON s.room_id = r.room_id
                    LEFT JOIN attendance a ON b.attendance_id = a.attendance_id
                    ORDER BY b.blocked_at DESC";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $blocked[] = $row;
                }
                $stmt->close();
            }
            return $blocked;
            
        case 'getHostelStats':
            $result = [];
            // Query hostels and compute occupied/vacant rooms per hostel
            $sql = "SELECT h.hostel_id, h.hostel_name, h.hostel_code,
                           SUM(r.occupied) AS occupied_students,
                           SUM(r.capacity) AS total_capacity,
                           COUNT(r.room_id) AS total_rooms,
                           SUM(CASE WHEN r.occupied>0 THEN 1 ELSE 0 END) AS occupied_rooms,
                           SUM(CASE WHEN r.occupied=0 THEN 1 ELSE 0 END) AS vacant_rooms
                    FROM hostels h
                    LEFT JOIN rooms r ON r.hostel_id = h.hostel_id";
            
            $hostelId = $params['hostelId'] ?? null;
            $genderScope = get_hostel_gender_scope_for_role();
            
            if ($hostelId) {
                $sql .= " WHERE h.hostel_id = " . intval($hostelId);
            } elseif ($genderScope !== null) {
                $safeGender = mysqli_real_escape_string($conn, $genderScope);
                $sql .= " WHERE h.gender = '" . $safeGender . "'";
            }
            
            $sql .= " GROUP BY h.hostel_id
                    ORDER BY h.hostel_id ASC";
            
            if ($res = $conn->query($sql)) {
                while ($row = $res->fetch_assoc()) {
                    $result[] = $row;
                }
                $res->free();
            }
            return $result;
            
        case 'getAttendanceStats':
            $today = date('Y-m-d');
            $stats = ['total' => 0, 'present' => 0, 'absent' => 0, 'blocked' => 0];

            // Total active students
            $sqlTotal = "SELECT COUNT(*) AS cnt FROM students WHERE status='1'";
            if ($res = $conn->query($sqlTotal)) {
                $row = $res->fetch_assoc();
                $stats['total'] = (int)$row['cnt'];
                $res->free();
            }

            // Count present students for today
            $sqlPresent = "SELECT COUNT(*) AS cnt 
                           FROM attendance a 
                           JOIN students s ON a.student_id = s.student_id 
                           WHERE a.date = ? AND a.status = 'Present' AND s.status = '1'";
            if ($stmt = $conn->prepare($sqlPresent)) {
                $stmt->bind_param('s', $today);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $stats['present'] = (int)$row['cnt'];
                $stmt->close();
            }

            // Count absent students for today (including those without attendance records)
            // First, get students who are explicitly marked as absent today
            $sqlAbsent = "SELECT COUNT(*) AS cnt 
                          FROM attendance a 
                          JOIN students s ON a.student_id = s.student_id 
                          WHERE a.date = ? AND a.status = 'Absent' AND s.status = '1'";
            if ($stmt = $conn->prepare($sqlAbsent)) {
                $stmt->bind_param('s', $today);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $explicitAbsent = (int)$row['cnt'];
                $stmt->close();
            }

            // Then, count students who don't have attendance records for today (should be considered absent)
            $sqlNoRecord = "SELECT COUNT(*) AS cnt 
                            FROM students s 
                            LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = ? 
                            WHERE s.status = '1' AND a.student_id IS NULL";
            if ($stmt = $conn->prepare($sqlNoRecord)) {
                $stmt->bind_param('s', $today);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $noRecord = (int)$row['cnt'];
                $stmt->close();
            }

            $stats['absent'] = $explicitAbsent + $noRecord;

            // Blocked students count - count all blocked students (no unblocked_at column)
            $sqlBlocked = "SELECT COUNT(DISTINCT student_id) AS cnt FROM blocked_students";
            if ($res = $conn->query($sqlBlocked)) {
                $row = $res->fetch_assoc();
                $stats['blocked'] = (int)$row['cnt'];
                $res->free();
            }

            return $stats;
            
        case 'getAttendanceStatsForDate':
            $stats = ['total' => 0, 'present' => 0, 'absent' => 0, 'blocked' => 0];

            $date = $params['date'] ?? date('Y-m-d');
            $hostelId = $params['hostelId'] ?? null;

            // Build hostel filter clause - join with rooms table to filter by hostel
            $hostelJoin = "";
            $hostelFilter = "";
            if ($hostelId) {
                $hostelJoin = " LEFT JOIN rooms r ON s.room_id = r.room_id";
                $hostelFilter = " AND r.hostel_id = " . intval($hostelId);
            }

            // Total active students
            $sqlTotal = "SELECT COUNT(*) AS cnt FROM students s" . $hostelJoin . " WHERE s.status='1'" . $hostelFilter;
            if ($res = $conn->query($sqlTotal)) {
                $row = $res->fetch_assoc();
                $stats['total'] = (int)$row['cnt'];
                $res->free();
            }

            // Count present students for selected date
            $sqlPresent = "SELECT COUNT(*) AS cnt 
                           FROM attendance a 
                           JOIN students s ON a.student_id = s.student_id" . $hostelJoin . "
                           WHERE a.date = ? AND a.status = 'Present' AND s.status = '1'" . $hostelFilter;
            if ($stmt = $conn->prepare($sqlPresent)) {
                $stmt->bind_param('s', $date);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $stats['present'] = (int)$row['cnt'];
                $stmt->close();
            }

            // Count absent students for selected date (including those without attendance records)
            $sqlAbsent = "SELECT COUNT(*) AS cnt 
                          FROM attendance a 
                          JOIN students s ON a.student_id = s.student_id" . $hostelJoin . "
                          WHERE a.date = ? AND a.status = 'Absent' AND s.status = '1'" . $hostelFilter;
            if ($stmt = $conn->prepare($sqlAbsent)) {
                $stmt->bind_param('s', $date);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $explicitAbsent = (int)$row['cnt'];
                $stmt->close();
            }

            // Count students who don't have attendance records for the selected date
            $sqlNoRecord = "SELECT COUNT(*) AS cnt 
                            FROM students s" . $hostelJoin . "
                            LEFT JOIN attendance a ON s.student_id = a.student_id AND a.date = ? 
                            WHERE s.status = '1' AND a.student_id IS NULL" . $hostelFilter;
            if ($stmt = $conn->prepare($sqlNoRecord)) {
                $stmt->bind_param('s', $date);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $noRecord = (int)$row['cnt'];
                $stmt->close();
            }

            $stats['absent'] = $explicitAbsent + $noRecord;

            // Blocked students count - count all blocked students (no unblocked_at column)
            $sqlBlocked = "SELECT COUNT(DISTINCT bs.student_id) AS cnt 
                           FROM blocked_students bs
                           JOIN students s ON bs.student_id = s.student_id" . $hostelJoin . "
                           WHERE s.status = '1'" . $hostelFilter;
            if ($res = $conn->query($sqlBlocked)) {
                $row = $res->fetch_assoc();
                $stats['blocked'] = (int)$row['cnt'];
                $res->free();
            }
            
            return $stats;
            
        case 'getAllMessDays':
            // Return days of week present in mess_menu table, ordered Monday-Sunday
            $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            $found = [];
            $sql = "SELECT DISTINCT DAYNAME(date) AS dayname FROM mess_menu";
            if ($res = $conn->query($sql)) {
                while ($row = $res->fetch_assoc()) {
                    $found[] = $row['dayname'];
                }
                $res->free();
            }
            // Preserve ordering Monday-Sunday
            $ordered = [];
            foreach ($days as $d) {
                if (in_array($d, $found)) $ordered[] = $d;
            }
            // Fallback to full week if none found
            return !empty($ordered) ? $ordered : $days;
            
        case 'getMessMenu':
            // Find the most recent date that matches the given day name and fetch menu items grouped by meal_type
            $menu = [];
            $dayName = $params['dayName'] ?? '';
            
            $sqlDate = "SELECT date FROM mess_menu WHERE DAYNAME(date)=? ORDER BY date DESC LIMIT 1";
            if ($stmt = $conn->prepare($sqlDate)) {
                $stmt->bind_param('s', $dayName);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $stmt->close();
                if (!$row) return $menu;
                $date = $row['date'];

                $sqlItems = "SELECT meal_type, items FROM mess_menu WHERE date = ?";
                if ($stmt2 = $conn->prepare($sqlItems)) {
                    $stmt2->bind_param('s', $date);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();
                    while ($r = $res2->fetch_assoc()) {
                        $meal = strtolower($r['meal_type']);
                        // items stored as newline or comma separated - split into array
                        $items = preg_split('/\r?\n|,/', $r['items']);
                        $trimmed = array_values(array_filter(array_map('trim', $items), function($v){return $v!=='';}));
                        $menu[$meal] = $trimmed;
                    }
                    $stmt2->close();
                }
            }
            return $menu;
            
        case 'getTotalStudentsInRooms':
            $hostelId = $params['hostelId'] ?? null;
            
            $sql = "SELECT COUNT(*) as total FROM room_students rs 
                    INNER JOIN rooms r ON rs.room_id = r.room_id 
                    WHERE rs.is_active = 1 AND rs.vacated_at IS NULL";
            
            if ($hostelId) {
                $sql .= " AND r.hostel_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $hostelId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                return (int)$row['total'];
            } else {
                $result = $conn->query($sql);
                if ($result) {
                    $row = $result->fetch_assoc();
                    $result->free();
                    return (int)$row['total'];
                }
                return 0;
            }
            
        case 'synchronizeRoomOccupancy':
            // Update all rooms' occupied counts based on actual student assignments
            $sql = "UPDATE rooms r 
                    SET occupied = (
                        SELECT COUNT(*) 
                        FROM room_students rs 
                        WHERE rs.room_id = r.room_id 
                        AND rs.is_active = 1 
                        AND rs.vacated_at IS NULL
                    )";
            return $conn->query($sql);
            
        case 'getPendingLeaveCount':
            $hostelId = $params['hostelId'] ?? null;
            
            $sql = "SELECT COUNT(*) as count FROM leave_applications la WHERE la.Status='Pending'";
            if ($hostelId) {
                $sql .= " AND la.Reg_No IN (SELECT s.roll_number FROM students s LEFT JOIN rooms r ON s.room_id = r.room_id WHERE r.hostel_id = ?)";
            }
            
            if ($hostelId) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $hostelId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                return (int)$row['count'];
            } else {
                $result = $conn->query($sql);
                if ($result) {
                    $row = $result->fetch_assoc();
                    $result->free();
                    return (int)$row['count'];
                }
                return 0;
            }
            
        case 'getBlockedStudentsCount':
            $hostelId = $params['hostelId'] ?? null;
            
            // Count all blocked students (no unblocked_at column in schema)
            $sql = "SELECT COUNT(DISTINCT bs.student_id) as count FROM blocked_students bs WHERE 1=1";
            if ($hostelId) {
                $sql .= " AND bs.student_id IN (SELECT s.student_id FROM students s LEFT JOIN rooms r ON s.room_id = r.room_id WHERE r.hostel_id = ?)";
            }
            
            if ($hostelId) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $hostelId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                return (int)$row['count'];
            } else {
                $result = $conn->query($sql);
                if ($result) {
                    $row = $result->fetch_assoc();
                    $result->free();
                    return (int)$row['count'];
                }
                return 0;
            }
            
        case 'getTotalStudentsCount':
            $hostelId = $params['hostelId'] ?? null;
            
            $sql = "SELECT COUNT(*) as count FROM students s";
            if ($hostelId) {
                $sql .= " LEFT JOIN rooms r ON s.room_id = r.room_id WHERE s.status='1' AND r.hostel_id = ?";
            } else {
                $sql .= " WHERE s.status='1'";
            }
            
            if ($hostelId) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $hostelId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                return (int)$row['count'];
            } else {
                $result = $conn->query($sql);
                if ($result) {
                    $row = $result->fetch_assoc();
                    $result->free();
                    return (int)$row['count'];
                }
                return 0;
            }
            
        case 'getTotalRoomsCount':
            $hostelId = $params['hostelId'] ?? null;
            
            $sql = "SELECT COUNT(*) as count FROM rooms WHERE 1=1";
            if ($hostelId) {
                $sql .= " AND hostel_id = ?";
            }
            
            if ($hostelId) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $hostelId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                return (int)$row['count'];
            } else {
                $result = $conn->query($sql);
                if ($result) {
                    $row = $result->fetch_assoc();
                    $result->free();
                    return (int)$row['count'];
                }
                return 0;
            }
            
        case 'handleDashboardAPI':
            header('Content-Type: application/json');
            error_reporting(E_ALL);
            ini_set('display_errors', 0);
            
            // Extract parameters properly
            $conn = $params['conn'] ?? null;
            $action = $params['action'] ?? ($_GET['action'] ?? '');
            $hostelId = $params['hostel_id'] ?? ($_GET['hostel_id'] ?? null);
            $hostelId = normalize_hostel_id_for_current_admin($conn, $hostelId);
            if (is_gender_admin_role() && $hostelId === null) {
                $hostelId = get_default_hostel_id_for_current_admin($conn);
            }
            $date = $params['date'] ?? ($_GET['date'] ?? date('Y-m-d'));
            
            if (!isset($conn) || !$conn) {
                echo json_encode(['error' => 'Database connection failed']);
                exit;
            }
            
            // Synchronize room occupancy
            dispatchFunction('synchronizeRoomOccupancy');
            
            $response = [];
            
            switch ($action) {
                case 'get_stats':
                    $response = [
                        'pending_leaves' => dispatchFunction('getPendingLeaveCount', ['hostelId' => $hostelId]),
                        'blocked_students' => dispatchFunction('getBlockedStudentsCount', ['hostelId' => $hostelId]),
                        'total_students' => dispatchFunction('getTotalStudentsCount', ['hostelId' => $hostelId]),
                        'total_rooms' => dispatchFunction('getTotalRoomsCount', ['hostelId' => $hostelId])
                    ];
                    break;
                    
                case 'get_attendance':
                    $attendanceStats = dispatchFunction('getAttendanceStatsForDate', ['date' => $date, 'hostelId' => $hostelId]);
                    $totalStudents = $attendanceStats['total'];
                    
                    $response = [
                        'total' => $totalStudents,
                        'present' => $attendanceStats['present'],
                        'absent' => $attendanceStats['absent'],
                        'present_percentage' => $totalStudents > 0 ? round(($attendanceStats['present'] / $totalStudents) * 100, 1) : 0,
                        'absent_percentage' => $totalStudents > 0 ? round(($attendanceStats['absent'] / $totalStudents) * 100, 1) : 0,
                        'date' => $date
                    ];
                    break;
                    
                case 'get_leave_summary':
                    $leaveSummarySql = "SELECT 
                                            lt.Leave_Type_Name,
                                            COUNT(CASE WHEN la.Status = 'Pending' THEN 1 END) as pending_count,
                                            COUNT(CASE WHEN la.Status = 'Approved' THEN 1 END) as approved_count,
                                            COUNT(CASE WHEN la.Status = 'Rejected' OR la.Status LIKE 'Rejected%' THEN 1 END) as rejected_count,
                                            COUNT(la.Leave_ID) as total_count
                                        FROM leave_types lt
                                        LEFT JOIN leave_applications la ON lt.LeaveType_ID = la.LeaveType_ID";
                    
                    if ($hostelId) {
                        $leaveSummarySql .= " AND la.Reg_No IN (SELECT s.roll_number FROM students s LEFT JOIN rooms r ON s.room_id = r.room_id WHERE r.hostel_id = " . intval($hostelId) . ")";
                    }
                    
                    $leaveSummarySql .= " GROUP BY lt.LeaveType_ID, lt.Leave_Type_Name
                                        HAVING total_count > 0
                                        ORDER BY total_count DESC";
                    
                    $result = $conn->query($leaveSummarySql);
                    $leaveSummaryData = [];
                    
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $leaveSummaryData[] = $row;
                        }
                    }
                    
                    $response = ['leave_summary' => $leaveSummaryData];
                    break;
                    
                case 'get_daily_leaves':
                    $dailyLeaveSql = "SELECT 
                                        DATE(Applied_Date) as date, 
                                        COUNT(*) as count 
                                      FROM leave_applications la
                                      WHERE la.Applied_Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    
                    if ($hostelId) {
                        $dailyLeaveSql .= " AND la.Reg_No IN (SELECT s.roll_number FROM students s LEFT JOIN rooms r ON s.room_id = r.room_id WHERE r.hostel_id = " . intval($hostelId) . ")";
                    }
                    
                    $dailyLeaveSql .= " GROUP BY DATE(Applied_Date) 
                                      ORDER BY DATE(Applied_Date)";
                    
                    $result = $conn->query($dailyLeaveSql);
                    $labels = [];
                    $data = [];
                    
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $labels[] = date('M d', strtotime($row['date']));
                            $data[] = (int)$row['count'];
                        }
                    }
                    
                    $response = [
                        'labels' => $labels,
                        'data' => $data
                    ];
                    break;
                    
                case 'get_leave_by_type':
                    $leaveTypeStatsSql = "SELECT 
                                            lt.Leave_Type_Name,
                                            COUNT(la.Leave_ID) as count
                                          FROM leave_types lt
                                          LEFT JOIN leave_applications la ON lt.LeaveType_ID = la.LeaveType_ID
                                              AND ? BETWEEN DATE(la.From_Date) AND DATE(la.To_Date)";
                    
                    if ($hostelId) {
                        $leaveTypeStatsSql .= " AND la.Reg_No IN (SELECT s.roll_number FROM students s LEFT JOIN rooms r ON s.room_id = r.room_id WHERE r.hostel_id = " . intval($hostelId) . ")";
                    }
                    
                    $leaveTypeStatsSql .= " GROUP BY lt.LeaveType_ID, lt.Leave_Type_Name
                                          HAVING count > 0
                                          ORDER BY count DESC";
                    
                    $stmt = $conn->prepare($leaveTypeStatsSql);
                    if ($stmt) {
                        $stmt->bind_param('s', $date);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        $labels = [];
                        $data = [];
                        
                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $labels[] = $row['Leave_Type_Name'];
                                $data[] = (int)$row['count'];
                            }
                        }
                        $stmt->close();
                        
                        $response = [
                            'labels' => $labels,
                            'data' => $data,
                            'has_data' => count($labels) > 0,
                            'date' => $date
                        ];
                    } else {
                        $response = ['error' => 'Failed to prepare statement'];
                    }
                    break;
                    
                case 'get_room_occupancy':
                    $hostelStats = dispatchFunction('getHostelStats', ['hostelId' => $hostelId]);
                    
                    if (!empty($hostelStats) && $hostelId) {
                        $selectedHostelStats = null;
                        foreach ($hostelStats as $stats) {
                            if ($stats['hostel_id'] == $hostelId) {
                                $selectedHostelStats = $stats;
                                break;
                            }
                        }
                        
                        if ($selectedHostelStats) {
                            $occupancyRate = $selectedHostelStats['total_rooms'] > 0 
                                ? round(($selectedHostelStats['occupied_rooms'] / $selectedHostelStats['total_rooms']) * 100, 1) 
                                : 0;
                            
                            $response = [
                                'hostel_id' => $selectedHostelStats['hostel_id'],
                                'hostel_name' => $selectedHostelStats['hostel_name'],
                                'occupied_rooms' => (int)$selectedHostelStats['occupied_rooms'],
                                'vacant_rooms' => (int)$selectedHostelStats['vacant_rooms'],
                                'total_rooms' => (int)$selectedHostelStats['total_rooms'],
                                'occupied_students' => (int)$selectedHostelStats['occupied_students'],
                                'total_capacity' => (int)$selectedHostelStats['total_capacity'],
                                'occupancy_rate' => $occupancyRate
                            ];
                        } else {
                            $response = ['error' => 'No data for selected hostel'];
                        }
                    } else {
                        $response = ['error' => 'Hostel ID required'];
                    }
                    break;
                    
                // Notice Board Actions
                case 'send_notice':
                    $message = $_POST['message'] ?? '';

                    if (empty($message)) {
                        $response = ['success' => false, 'message' => 'Message cannot be empty.'];
                        break;
                    }

                    // Get current admin role
                    $currentRole = $_SESSION['role'] ?? 'admin';
                    
                    // Determine target gender based on admin role
                    $targetGender = 'all';
                    if ($currentRole === 'male_admin') {
                        $targetGender = 'male';
                    } elseif ($currentRole === 'female_admin') {
                        $targetGender = 'female';
                    }

                    // Set title to empty string
                    $title = '';
                    
                    // First, try simple insert with just title and content (guaranteed to work)
                    $stmt = $conn->prepare("INSERT INTO notices (title, content) VALUES (?, ?)");
                    
                    if ($stmt === false) {
                        error_log("NOTICE ERROR: Failed to prepare basic statement: " . $conn->error);
                        $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
                        break;
                    }
                    
                    $stmt->bind_param('ss', $title, $message);
                    
                    if (!$stmt->execute()) {
                        error_log("NOTICE ERROR: Execute failed: " . $stmt->error);
                        $response = ['success' => false, 'message' => 'Failed to send notice: ' . $stmt->error];
                        $stmt->close();
                        break;
                    }
                    
                    $insertedId = $stmt->insert_id;
                    $stmt->close();
                    
                    // Now try to update the new columns if they exist
                    if ($targetGender !== 'all' || $currentRole !== 'admin') {
                        $updateStmt = $conn->prepare("UPDATE notices SET posted_by_role = ?, target_gender = ? WHERE id = ?");
                        if ($updateStmt !== false) {
                            $updateStmt->bind_param('ssi', $currentRole, $targetGender, $insertedId);
                            $updateStmt->execute();
                            $updateStmt->close();
                        }
                    }
                    
                    $response = ['success' => true, 'message' => 'Notice sent successfully.'];
                    break;
                    
                case 'get_notices':
                    // Get notices from database - filtered by student's gender if columns exist
                    $studentGender = $_POST['student_gender'] ?? ($_GET['student_gender'] ?? '');
                    
                    // Try to use gender filtering if columns exist
                    if (!empty($studentGender)) {
                        // With gender filtering
                        $sql = "SELECT id, title, content, created_at, 
                                COALESCE(posted_by_role, 'admin') as posted_by_role,
                                COALESCE(target_gender, 'all') as target_gender
                                FROM notices 
                                WHERE (COALESCE(target_gender, 'all') = ? OR COALESCE(target_gender, 'all') = 'all') 
                                ORDER BY created_at DESC";
                        $stmt = $conn->prepare($sql);
                        if ($stmt !== false) {
                            $stmt->bind_param('s', $studentGender);
                            $stmt->execute();
                        } else {
                            // Fallback if target_gender column doesn't exist
                            $sql = "SELECT id, title, content, created_at FROM notices ORDER BY created_at DESC";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute();
                        }
                    } else {
                        // Show all notices if no gender provided
                        $sql = "SELECT id, title, content, created_at, 
                                COALESCE(posted_by_role, 'admin') as posted_by_role,
                                COALESCE(target_gender, 'all') as target_gender
                                FROM notices 
                                ORDER BY created_at DESC";
                        $stmt = $conn->prepare($sql);
                        if ($stmt !== false) {
                            $stmt->execute();
                        } else {
                            // Fallback
                            $sql = "SELECT id, title, content, created_at FROM notices ORDER BY created_at DESC";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute();
                        }
                    }
                    
                    $result = $stmt->get_result();
                    
                    $notices = [];
                    while ($row = $result->fetch_assoc()) {
                        $notices[] = $row;
                    }
                    
                    $stmt->close();
                    $response = ['success' => true, 'notices' => $notices];
                    break;
                    
                case 'update_notice':
                    $id = $_POST['id'] ?? '';
                    $message = $_POST['message'] ?? '';
                    
                    if (empty($id) || empty($message)) {
                        $response = ['success' => false, 'message' => 'Missing required fields'];
                        break;
                    }
                    
                    $stmt = $conn->prepare("UPDATE notices SET content = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param('si', $message, $id);
                    
                    if ($stmt->execute()) {
                        $response = ['success' => true, 'message' => 'Notice updated successfully'];
                    } else {
                        $response = ['success' => false, 'message' => 'Failed to update notice'];
                    }
                    $stmt->close();
                    break;
                    
                case 'delete_notice':
                    $id = $_POST['id'] ?? '';
                    
                    if (empty($id)) {
                        $response = ['success' => false, 'message' => 'Missing notice ID'];
                        break;
                    }
                    
                    $stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
                    $stmt->bind_param('i', $id);
                    
                    if ($stmt->execute()) {
                        $response = ['success' => true, 'message' => 'Notice deleted successfully'];
                    } else {
                        $response = ['success' => false, 'message' => 'Failed to delete notice'];
                    }
                    $stmt->close();
                    break;
                    
                case 'get_total_leaves':
                    // Get total number of leave applications (approved, pending, rejected)
                    $sql = "SELECT COUNT(*) as count FROM leave_applications WHERE 1=1";
                    if ($hostelId) {
                        $sql .= " AND Reg_No IN (SELECT s.roll_number FROM students s LEFT JOIN rooms r ON s.room_id = r.room_id WHERE r.hostel_id = ?)";
                    }
                    
                    if ($hostelId) {
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $hostelId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        $stmt->close();
                        $totalLeaves = (int)$row['count'];
                    } else {
                        $result = $conn->query($sql);
                        if ($result) {
                            $row = $result->fetch_assoc();
                            $result->free();
                            $totalLeaves = (int)$row['count'];
                        } else {
                            $totalLeaves = 0;
                        }
                    }
                    
                    $response = ['total_leaves' => $totalLeaves];
                    break;
                    
                default:
                    $response = ['error' => 'Invalid action'];
                    break;
            }
            
            echo json_encode($response);
            $conn->close();
            exit;
            
        case 'getDashboardData':
            // Synchronize room occupancy to ensure data consistency
            dispatchFunction('synchronizeRoomOccupancy');
            
            $selectedHostelId = $params['selectedHostelId'] ?? null;

            // Get hostel statistics (filtered by selected hostel if applicable)
            $hostelStats = dispatchFunction('getHostelStats', ['hostelId' => $selectedHostelId]);

            // Get selected attendance date or use today
            $selectedAttendanceDate = date('Y-m-d');

            // Get attendance statistics for the selected date (filtered by selected hostel if applicable)
            $attendanceStats = dispatchFunction('getAttendanceStatsForDate', ['date' => $selectedAttendanceDate, 'hostelId' => $selectedHostelId]);

            // Get additional statistics (filtered by selected hostel if applicable)
            $pendingLeaveCount = dispatchFunction('getPendingLeaveCount', ['hostelId' => $selectedHostelId]);
            $blockedStudentsCount = dispatchFunction('getBlockedStudentsCount', ['hostelId' => $selectedHostelId]);
            $totalStudentsCount = dispatchFunction('getTotalStudentsCount', ['hostelId' => $selectedHostelId]);
            $totalRoomsCount = dispatchFunction('getTotalRoomsCount', ['hostelId' => $selectedHostelId]);

            // Calculate percentages
            $totalStudents = $attendanceStats['total'];
            $presentCount = $attendanceStats['present'];
            $absentCount = $attendanceStats['absent'];
            $blockedCount = $attendanceStats['blocked'];

            $presentPercentage = $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100, 1) : 0;
            $absentPercentage = $totalStudents > 0 ? round(($absentCount / $totalStudents) * 100, 1) : 0;
            $blockedPercentage = $totalStudents > 0 ? round(($blockedCount / $totalStudents) * 100, 1) : 0;

            // Get leave statistics for charts
            // Daily leave applications (last 7 days)
            $dailyLeaveSql = "SELECT 
                                DATE(Applied_Date) as date, 
                                COUNT(*) as count 
                              FROM leave_applications 
                              WHERE Applied_Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            
            if ($selectedHostelId) {
                $dailyLeaveSql .= " AND Reg_No IN (SELECT s.roll_number FROM students s LEFT JOIN rooms r ON s.room_id = r.room_id WHERE r.hostel_id = " . intval($selectedHostelId) . ")";
            }
            
            $dailyLeaveSql .= " GROUP BY DATE(Applied_Date) 
                              ORDER BY DATE(Applied_Date)";
            
            $dailyLeaveResult = $conn->query($dailyLeaveSql);

            // Prepare daily leave data for chart
            $dailyLabels = [];
            $dailyData = [];
            if ($dailyLeaveResult && $dailyLeaveResult->num_rows > 0) {
                while($row = $dailyLeaveResult->fetch_assoc()) {
                    $dailyLabels[] = date('M d', strtotime($row['date']));
                    $dailyData[] = $row['count'];
                }
            }

            // Leave applications by type - get all dates for dropdown
            $allDatesQuery = "SELECT DISTINCT DATE(Applied_Date) as app_date 
                              FROM leave_applications 
                              WHERE Applied_Date IS NOT NULL 
                              ORDER BY app_date DESC 
                              LIMIT 30";
            $allDatesResult = $conn->query($allDatesQuery);
            $availableDates = [];
            if ($allDatesResult && $allDatesResult->num_rows > 0) {
                while($row = $allDatesResult->fetch_assoc()) {
                    $availableDates[] = $row['app_date'];
                }
            }

            // Get selected date or use today
            $selectedDate = date('Y-m-d');

            // Leave applications by type for selected date - filter by From_Date to To_Date range (only approved leaves)
            $leaveTypeStatsSql = "SELECT 
                                lt.Leave_Type_Name,
                                COUNT(la.Leave_ID) as count
                              FROM leave_types lt
                              LEFT JOIN leave_applications la ON lt.LeaveType_ID = la.LeaveType_ID
                                  AND ? BETWEEN DATE(la.From_Date) AND DATE(la.To_Date)
                                  AND la.Status = 'Approved'";

            // Add hostel filtering if a hostel is selected
            if ($selectedHostelId !== null && $selectedHostelId > 0) {
                $leaveTypeStatsSql .= " AND la.Reg_No IN (SELECT s.roll_number FROM students s LEFT JOIN rooms r ON s.room_id = r.room_id WHERE r.hostel_id = ?)";
            }

            $leaveTypeStatsSql .= "
                              GROUP BY lt.LeaveType_ID, lt.Leave_Type_Name
                              HAVING count > 0
                              ORDER BY count DESC";

            $stmt = $conn->prepare($leaveTypeStatsSql);
            if ($stmt) {
                if ($selectedHostelId !== null && $selectedHostelId > 0) {
                    $stmt->bind_param('si', $selectedDate, $selectedHostelId);
                } else {
                    $stmt->bind_param('s', $selectedDate);
                }
                $stmt->execute();
                $leaveTypeStatsResult = $stmt->get_result();
            } else {
                $leaveTypeStatsResult = null;
            }

            // Prepare leave type data for chart with specific colors for each type
            $typeLabels = [];
            $typeData = [];
            $typeColors = [];
            $typeBorderColors = [];

            // Define specific colors for each leave type
            $leaveTypeColors = [
                'General Leave' => ['bg' => 'rgba(78, 115, 223, 0.7)', 'border' => 'rgba(78, 115, 223, 1)'],
                'Outing' => ['bg' => 'rgba(28, 200, 138, 0.7)', 'border' => 'rgba(28, 200, 138, 1)'],
                'onduty' => ['bg' => 'rgba(54, 185, 204, 0.7)', 'border' => 'rgba(54, 185, 204, 1)'],
                'Leave' => ['bg' => 'rgba(246, 194, 62, 0.7)', 'border' => 'rgba(246, 194, 62, 1)'],
                'emergency Leave' => ['bg' => 'rgba(231, 74, 59, 0.7)', 'border' => 'rgba(231, 74, 59, 1)'],
            ];

            // Default colors for any other leave types
            $defaultColors = [
                'rgba(133, 135, 150, 0.7)',
                'rgba(156, 39, 176, 0.7)',
                'rgba(255, 152, 0, 0.7)',
                'rgba(0, 188, 212, 0.7)'
            ];
            $defaultBorderColors = [
                'rgba(133, 135, 150, 1)',
                'rgba(156, 39, 176, 1)',
                'rgba(255, 152, 0, 1)',
                'rgba(0, 188, 212, 1)'
            ];

            $colorIndex = 0;
            if ($leaveTypeStatsResult && $leaveTypeStatsResult->num_rows > 0) {
                while($row = $leaveTypeStatsResult->fetch_assoc()) {
                    $typeLabels[] = $row['Leave_Type_Name'];
                    $typeData[] = $row['count'];
                    
                    // Assign color based on leave type name
                    if (isset($leaveTypeColors[$row['Leave_Type_Name']])) {
                        $typeColors[] = $leaveTypeColors[$row['Leave_Type_Name']]['bg'];
                        $typeBorderColors[] = $leaveTypeColors[$row['Leave_Type_Name']]['border'];
                    } else {
                        // Use default colors for unmapped types
                        $typeColors[] = $defaultColors[$colorIndex % count($defaultColors)];
                        $typeBorderColors[] = $defaultBorderColors[$colorIndex % count($defaultBorderColors)];
                        $colorIndex++;
                    }
                }
            }
            
            // Return all the data as an associative array
            return [
                'selectedHostelId' => $selectedHostelId,
                'hostelStats' => $hostelStats,
                'pendingLeaveCount' => $pendingLeaveCount,
                'blockedStudentsCount' => $blockedStudentsCount,
                'totalStudentsCount' => $totalStudentsCount,
                'totalRoomsCount' => $totalRoomsCount,
                'selectedAttendanceDate' => $selectedAttendanceDate,
                'attendanceStats' => $attendanceStats,
                'totalStudents' => $totalStudents,
                'presentCount' => $presentCount,
                'absentCount' => $absentCount,
                'blockedCount' => $blockedCount,
                'presentPercentage' => $presentPercentage,
                'absentPercentage' => $absentPercentage,
                'blockedPercentage' => $blockedPercentage,
                'dailyLabels' => $dailyLabels,
                'dailyData' => $dailyData,
                'availableDates' => $availableDates,
                'typeLabels' => $typeLabels,
                'typeData' => $typeData,
                'typeColors' => $typeColors,
                'typeBorderColors' => $typeBorderColors
            ];
            
        default:
            return null;
    }
}


// Get selected hostel from URL parameter or session
$selectedHostelId = isset($_GET['hostel_id']) ? intval($_GET['hostel_id']) : null;
if (isset($conn) && $conn) {
    $selectedHostelId = normalize_hostel_id_for_current_admin($conn, $selectedHostelId);
    if (is_gender_admin_role() && $selectedHostelId === null) {
        $selectedHostelId = get_default_hostel_id_for_current_admin($conn);
    }
}

// Get all dashboard data
if (isset($conn) && $conn) {
    $dashboardData = dispatchFunction('getDashboardData', ['selectedHostelId' => $selectedHostelId]);
} else {
    // Set default values for dashboard data
    $dashboardData = [
        'selectedHostelId' => null,
        'hostelStats' => [
            [
                'hostel_id' => '1',
                'hostel_name' => 'Muthulakshmi',
                'hostel_code' => 'MKCEML001',
                'occupied_students' => '6',
                'total_capacity' => '27',
                'total_rooms' => '9',
                'occupied_rooms' => '3',
                'vacant_rooms' => '6'
            ],
            [
                'hostel_id' => '2',
                'hostel_name' => 'Octa',
                'hostel_code' => 'MKCEOCTA002',
                'occupied_students' => '5',
                'total_capacity' => '24',
                'total_rooms' => '8',
                'occupied_rooms' => '4',
                'vacant_rooms' => '4'
            ],
            [
                'hostel_id' => '3',
                'hostel_name' => 'Veda',
                'hostel_code' => 'MKCEVEDA003',
                'occupied_students' => '6',
                'total_capacity' => '12',
                'total_rooms' => '4',
                'occupied_rooms' => '4',
                'vacant_rooms' => '0'
            ]
        ],
        'pendingLeaveCount' => 2,
        'blockedStudentsCount' => 29,
        'totalStudentsCount' => 33,
        'totalRoomsCount' => 21,
        'selectedAttendanceDate' => date('Y-m-d'),
        'attendanceStats' => [
            'total' => 33,
            'present' => 4,
            'absent' => 21,
            'blocked' => 29
        ],
        'totalStudents' => 33,
        'presentCount' => 4,
        'absentCount' => 21,
        'blockedCount' => 29,
        'presentPercentage' => 12.1,
        'absentPercentage' => 63.6,
        'blockedPercentage' => 87.9,
        'dailyLabels' => [date('M d')],
        'dailyData' => [10],
        'availableDates' => [date('Y-m-d')],
        'typeLabels' => ['Leave', 'onduty', 'Outing', 'emergency Leave'],
        'typeData' => [2, 2, 1, 1],
        'typeColors' => [
            'rgba(246, 194, 62, 0.7)',
            'rgba(54, 185, 204, 0.7)',
            'rgba(28, 200, 138, 0.7)',
            'rgba(231, 74, 59, 0.7)'
        ],
        'typeBorderColors' => [
            'rgba(246, 194, 62, 1)',
            'rgba(54, 185, 204, 1)',
            'rgba(28, 200, 138, 1)',
            'rgba(231, 74, 59, 1)'
        ]
    ];
}

// Extract all variables from the returned data
extract($dashboardData);

// Build hostel selector options based on current admin scope.
$hostelSelectorOptions = dispatchFunction('getHostelStats', ['hostelId' => null]);
$isScopedAdmin = is_gender_admin_role();
$hasSingleScopedHostel = $isScopedAdmin && is_array($hostelSelectorOptions) && count($hostelSelectorOptions) <= 1;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <head>
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
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                /* Donut chart colors */
                --veda: #4e73df;
                --octa: #1cc88a;
                --muthu: #36b9cc;
                --vacant: #e0e0e0;
                /* Mess.php card styles */
                --table-header-gradient: linear-gradient(135deg, #4CAF50, #2196F3);
            }

            /* General Styles with Enhanced Typography */
            body {
                font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
                color: #333;
                line-height: 1.6;
            }

            /* Content Area Styles */
            .content {
                margin-left: var(--sidebar-width);
                padding-top: var(--topbar-height);
                transition: all 0.3s ease;
                min-height: 100vh;
            }

            .sidebar.collapsed+.content {
                margin-left: var(--sidebar-collapsed-width);
            }

            .breadcrumb-area {
                background-image: linear-gradient(to top, #fff1eb 0%, #ace0f9 100%);
                border-radius: 10px;
                box-shadow: var(--card-shadow);
                margin: 20px;
                padding: 15px 20px;
                border: none;
            }

            .breadcrumb-item a {
                color: var(--primary-color);
                text-decoration: none;
                transition: var(--transition);
                font-weight: 500;
            }

            .breadcrumb-item a:hover {
                color: #224abe;
                text-decoration: underline;
            }

            .breadcrumb-item.active {
                color: #6c757d;
                font-weight: 500;
            }

            /* Stats card hover effects */
            .stats-card {
                position: relative;
                overflow: hidden;
                transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
                border-radius: 10px;
                box-shadow: var(--card-shadow);
                margin-bottom: 20px;
                background: #fff;
                border: none;
                padding: 20px;
            }

            .stats-card.present {
                background: linear-gradient(135deg, #1cc88a, #0f9d58);
                color: #fff;
            }

            .stats-card.absent {
                background: linear-gradient(135deg, #f6c23e, #ff9800);
                color: #fff;
            }

            .stats-card.blocked {
                background: linear-gradient(135deg, #e74a3b, #c0392b);
                color: #fff;
            }

            .stats-card.total {
                background: linear-gradient(135deg, #4e73df, #224abe);
                color: #fff;
            }

            /* New card specific styles */
            .pending-leaves-card {
                background: linear-gradient(135deg, #6f42c1, #59339d);
                color: #fff;
                border-radius: 10px;
                box-shadow: var(--card-shadow);
                transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
                padding: 15px;
                aspect-ratio: 1 / 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                border: none;
            }

            .blocked-students-card {
                background: linear-gradient(135deg, #fd7e14, #e86705);
                color: #fff;
                border-radius: 10px;
                box-shadow: var(--card-shadow);
                transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
                padding: 15px;
                aspect-ratio: 1 / 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                border: none;
            }

            .total-students-card {
                background: linear-gradient(135deg, #20c997, #1aa179);
                color: #fff;
                border-radius: 10px;
                box-shadow: var(--card-shadow);
                transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
                padding: 15px;
                aspect-ratio: 1 / 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                border: none;
            }

            .total-rooms-card {
                background: linear-gradient(135deg, #0dcaf0, #0aa2c0);
                color: #fff;
                border-radius: 10px;
                box-shadow: var(--card-shadow);
                transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
                padding: 15px;
                aspect-ratio: 1 / 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                border: none;
            }

            .card-body {
                padding: 35px 20px;
                min-height: 200px;
            }

            .stat-number {
                font-size: 3.3rem;
                font-weight: 700;
                line-height: 1;
            }

            .stat-label {
                font-size: 1.1rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 1.2px;
                opacity: 0.95;
            }

            .fa-file-alt,
            .fa-user-slash,
            .fa-graduation-cap,
            .fa-door-open {
                font-size: 2.5rem;
                opacity: 0.9;
            }

            /* Remove unused CSS classes */
            /* .stats-card.present .stat-label,
        .stats-card.present .stat-number,
        .stats-card.present .stat-percent,
        .stats-card.present i,
        .stats-card.absent .stat-label,
        .stats-card.absent .stat-number,
        .stats-card.absent .stat-percent,
        .stats-card.absent i,
        .stats-card.blocked .stat-label,
        .stats-card.blocked .stat-number,
        .stats-card.blocked .stat-percent,
        .stats-card.blocked i,
        .stats-card.total .stat-label,
        .stats-card.total .stat-number,
        .stats-card.total .stat-percent,
        .stats-card.total i {
            color: #fff;
        } */

            .stats-card::after {
                content: '';
                position: absolute;
                top: 0;
                right: 0;
                width: 100px;
                height: 100%;
                background: rgba(255, 255, 255, 0.2);
                transform: skewX(-30deg) translateX(80px);
                transition: all 0.5s ease;
            }

            .stats-card:hover {
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
                transform: translateY(-5px);
                transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            }

            /* Add similar hover effects to new cards - matching mess.php */
            .pending-leaves-card:hover,
            .blocked-students-card:hover,
            .total-students-card:hover,
            .total-rooms-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
                transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            }

            .stat-number {
                font-size: 2.5rem;
                font-weight: 700;
                margin: 10px 0;
            }

            .stat-label {
                font-size: 1.1rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            .stat-percent {
                font-size: 0.9rem;
                opacity: 0.9;
            }

            /* Override default styles for specific elements */
            .stats-card .stat-number {
                font-size: 3.3rem;
                font-weight: 700;
                line-height: 1;
            }

            .stats-card .stat-label {
                font-size: 1.1rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 1.2px;
                opacity: 0.95;
            }

            .stats-card .fa-file-alt,
            .stats-card .fa-user-slash,
            .stats-card .fa-graduation-cap,
            .stats-card .fa-door-open {
                font-size: 2.5rem;
                opacity: 0.9;
            }

            /* Update all card styles to match mess.php main-container */
            .card {
                border-radius: 10px;
                box-shadow: var(--card-shadow);
                padding: 20px;
                background: white;
                margin-bottom: 20px;
                border: none;
            }

            .card.shadow {
                box-shadow: var(--card-shadow);
                border: none;
            }

            .card.shadow:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
                transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            }

            .card-header-enhanced {
                background: var(--table-header-gradient);
                color: white;
                font-weight: 600;
                padding: 15px 20px;
                border-radius: 10px 10px 0 0;
                margin: -20px -20px 20px -20px;
                border: none;
            }

            .card-header-enhanced h3 {
                margin: 0;
                font-size: 1.25rem;
            }

            /* Container Styles from mess.php */
            .main-container {
                background: white;
                border-radius: 10px;
                box-shadow: var(--card-shadow);
                padding: 20px;
                margin-bottom: 20px;
                border: none;
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

            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }

            @keyframes shimmer {
                0% {
                    background-position: 0% 50%;
                }

                100% {
                    background-position: 200% 50%;
                }
            }

            /* Improved hostel room charts layout */
            #hostel-rooms {
                background: white;
                border-radius: 10px;
                box-shadow: var(--card-shadow);
                padding: 20px;
                margin: 20px 0;
                border: none;
            }

            #hostel-rooms .card-header {
                padding: 0 16px;
            }

            .donut-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                padding: 16px;
            }

            .donut-card {
                text-align: center;
                padding: 20px;
                transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
                background: white;
                border-radius: 10px;
                box-shadow: var(--card-shadow);
                margin-bottom: 20px;
                border: none;
            }

            .donut-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
                transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            }

            .donut-card h4 {
                margin-top: 0;
                margin-bottom: 12px;
                font-size: 1rem;
            }

            .chart-wrap.small {
                position: relative;
                height: 120px;
                width: 120px;
                margin: 0 auto;
            }

            .chart-wrap.small canvas {
                width: 100% !important;
                height: 100% !important;
            }

            .mini-legend {
                margin-top: 12px;
                font-size: 12px;
            }

            .dot {
                display: inline-block;
                width: 10px;
                height: 10px;
                border-radius: 50%;
                margin-right: 4px;
            }

            .dot.veda {
                background-color: var(--veda);
            }

            .dot.octa {
                background-color: var(--octa);
            }

            .dot.muthu {
                background-color: var(--muthu);
            }

            .dot.vacant {
                background-color: var(--vacant);
            }

            .dot.total {
                background-color: var(--primary-color);
            }

            /* Enhanced Mess Menu Styles */
            #mess-menu {
                background: white;
                border-radius: 10px;
                box-shadow: var(--card-shadow);
                padding: 20px;
                margin: 20px 0;
                border: none;
            }

            #mess-menu .card-header {
                padding: 0 16px;
            }

            #mess-card {
                background: white;
                border-radius: 10px;
                box-shadow: var(--card-shadow);
                padding: 20px;
                margin-top: 20px;
                transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
                margin-bottom: 20px;
                border: none;
            }

            #mess-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            }

            #mess-card h3 {
                margin-top: 0;
                color: var(--primary-color);
                border-bottom: 2px solid var(--primary-color);
                padding-bottom: 10px;
                text-align: center;
                font-weight: 700;
                font-size: 1.5rem;
            }

            .tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 20px;
                padding: 15px;
                background: linear-gradient(135deg, #f0f4f8, #e2e8f0);
                border-radius: 14px;
                box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.05);
            }

            .tab {
                border: none !important;
                border-radius: 12px !important;
                padding: 12px 18px !important;
                font-weight: 600 !important;
                font-size: 1rem;
                letter-spacing: 0.4px;
                position: relative;
                transition: background 0.2s !important;
                z-index: 1;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
                cursor: pointer;
                flex: 1;
                min-width: 60px;
                text-align: center;
            }

            /* Day-specific tab styling with distinct colors */
            .tab[data-day="0"] {
                /* Monday */
                background: linear-gradient(135deg, #4E65FF, #92EFFD);
                color: #fff;
            }

            .tab[data-day="1"] {
                /* Tuesday */
                background: linear-gradient(135deg, #4CAF50, #8BC34A);
                color: #fff;
            }

            .tab[data-day="2"] {
                /* Wednesday */
                background: linear-gradient(135deg, #2196F3, #64B5F6);
                color: #fff;
            }

            .tab[data-day="3"] {
                /* Thursday */
                background: linear-gradient(135deg, #9C27B0, #CE93D8);
                color: #fff;
            }

            .tab[data-day="4"] {
                /* Friday */
                background: linear-gradient(135deg, #FF9800, #FFB74D);
                color: #fff;
            }

            .tab[data-day="5"] {
                /* Saturday */
                background: linear-gradient(135deg, #F44336, #E57373);
                color: #fff;
            }

            .tab[data-day="6"] {
                /* Sunday */
                background: linear-gradient(135deg, #795548, #A1887F);
                color: #fff;
            }

            /* Inactive tab styles */
            .tab:not(.active) {
                background: #fff !important;
            }

            .tab[data-day="0"]:not(.active) {
                color: #4E65FF;
            }

            .tab[data-day="1"]:not(.active) {
                color: #4CAF50;
            }

            .tab[data-day="2"]:not(.active) {
                color: #2196F3;
            }

            .tab[data-day="3"]:not(.active) {
                color: #9C27B0;
            }

            .tab[data-day="4"]:not(.active) {
                color: #FF9800;
            }

            .tab[data-day="5"]:not(.active) {
                color: #F44336;
            }

            .tab[data-day="6"]:not(.active) {
                color: #795548;
            }

            /* Hover effects for inactive tabs - only color change */
            .tab[data-day="0"]:hover:not(.active) {
                background: #4E65FF !important;
                color: #fff;
                transition: background 0.2s;
            }

            .tab[data-day="1"]:hover:not(.active) {
                background: #4CAF50 !important;
                color: #fff;
                transition: background 0.2s;
            }

            .tab[data-day="2"]:hover:not(.active) {
                background: #2196F3 !important;
                color: #fff;
                transition: background 0.2s;
            }

            .tab[data-day="3"]:hover:not(.active) {
                background: #9C27B0 !important;
                color: #fff;
                transition: background 0.2s;
            }

            .tab[data-day="4"]:hover:not(.active) {
                background: #FF9800 !important;
                color: #fff;
                transition: background 0.2s;
            }

            .tab[data-day="5"]:hover:not(.active) {
                background: #F44336 !important;
                color: #fff;
                transition: background 0.2s;
            }

            .tab[data-day="6"]:hover:not(.active) {
                background: #795548 !important;
                color: #fff;
                transition: background 0.2s;
            }

            /* Remove gradient hover effects and animations */
            .tab::before {
                content: none;
            }

            /* Remove other hover effects */
            .tab:hover {
                transform: none !important;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05) !important;
            }

            /* Active tab styles */
            .tab.active {
                transform: translateY(-5px);
                box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2) !important;
                color: #fff !important;
                font-weight: 700 !important;
            }

            .menu {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                /* gap: 20px; */
                margin-top: 20px;
            }

            .meal {
                background: #ffffff;
                border-radius: 15px;
                padding: 20px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
                min-height: 250px;
                display: flex;
                flex-direction: column;
            }

            .meal:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            }

            .meal::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 5px;
            }

            .meal.breakfast::before {
                background: linear-gradient(90deg, #4E65FF, #92EFFD);
            }

            .meal.lunch::before {
                background: linear-gradient(90deg, #FF9800, #FFB74D);
            }

            .meal.snacks::before {
                background: linear-gradient(90deg, #9C27B0, #CE93D8);
            }

            .meal.dinner::before {
                background: linear-gradient(90deg, #4CAF50, #8BC34A);
            }

            .meal h4 {
                margin-top: 0;
                margin-bottom: 15px;
                color: #2d3748;
                border-bottom: 2px solid #f0f4f8;
                padding-bottom: 10px;
                font-size: 1.2rem;
                font-weight: 700;
                text-align: center;
            }

            .meal ul {
                list-style: none;
                padding: 0;
                margin: 0;
                flex-grow: 1;
            }

            .meal li {
                padding: 10px 0;
                border-bottom: 1px dashed #e2e8f0;
                font-size: 0.95rem;
                display: flex;
                align-items: center;
            }

            .meal li:last-child {
                border-bottom: none;
            }

            .meal li::before {
                content: "•";
                color: var(--primary-color);
                font-weight: bold;
                display: inline-block;
                width: 1em;
                margin-right: 10px;
                font-size: 1.2rem;
            }

            /* Ensure charts are visible */
            canvas {
                display: block;
                max-width: 100%;
            }

            /* Main content styles */
            .main-content {
                min-height: 100vh;
                position: relative;
            }

            /* Side by side container styles removed as they are no longer used */

            /* Hostel selector styles */
            .hostel-selector-container {
                position: relative;
                max-width: 250px;
            }

            .hostel-selector {
                appearance: none;
                width: 100%;
                padding: 10px 35px 10px 15px;
                font-size: 0.9rem;
                font-weight: 500;
                color: #2d3748;
                background: #fff;
                border: 2px solid #4e73df;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            .hostel-selector:hover {
                border-color: #224abe;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .hostel-selector:focus {
                outline: none;
                border-color: #224abe;
                box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.25);
            }

            .hostel-selector-container::after {
                content: '▼';
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
                color: #4e73df;
                pointer-events: none;
                font-size: 0.8rem;
            }

            /* Dropdown options styling */
            .hostel-selector option {
                padding: 10px;
                font-weight: 500;
                color: #2d3748;
                background: #fff;
            }

            /* Attendance Summary Styles */
            .border-left-success {
                border-left: 4px solid #1cc88a !important;
            }

            .border-left-warning {
                border-left: 4px solid #f6c23e !important;
            }

            .border-left-danger {
                border-left: 4px solid #e74a3b !important;
            }

            .border-left-primary {
                border-left: 4px solid #4e73df !important;
            }

            .text-gray-300 {
                color: #dddfeb !important;
            }

            .text-gray-800 {
                color: #5a5c69 !important;
            }

            /* Notice Board Styles - Unique Modern Design */
            .notice-board {
                background: linear-gradient(135deg, #f8f9fc 0%, #eef2f7 100%);
                border-radius: 12px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
                padding: 25px;
                margin-bottom: 20px;
                border: 1px solid rgba(0, 0, 0, 0.05);
                min-height: 400px;
                position: relative;
                overflow: hidden;
            }

            .notice-board::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 4px;
                background: linear-gradient(90deg, var(--primary-color), var(--success-color));
            }

            .notice-board h4 {
                font-weight: 600;
                margin-bottom: 25px;
                padding-bottom: 15px;
                color: #4a5568;
                position: relative;
            }

            .notice-board h4::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 50px;
                height: 3px;
                background: var(--primary-color);
                border-radius: 3px;
            }

            .notice-form textarea {
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                padding: 15px;
                font-size: 0.95rem;
                background: #ffffff;
                color: #4a5568;
                resize: vertical;
                transition: all 0.3s ease;
                box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
            }

            .notice-form textarea::placeholder {
                color: #a0aec0;
            }

            .notice-form textarea:focus {
                outline: none;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
            }

            .btn-send-notice {
                background: linear-gradient(135deg, var(--primary-color), #224abe);
                color: white;
                font-weight: 600;
                padding: 12px 30px;
                border: none;
                border-radius: 8px;
                transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
                box-shadow: 0 4px 6px rgba(78, 115, 223, 0.2);
                letter-spacing: 0.5px;
            }

            .btn-send-notice:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 12px rgba(78, 115, 223, 0.3);
                background: linear-gradient(135deg, #224abe, var(--primary-color));
            }

            .btn-send-notice:active {
                transform: translateY(0);
                box-shadow: 0 2px 4px rgba(78, 115, 223, 0.2);
            }

            /* Leave Types List Styles - Attractive line by line without small buttons */
            .leave-types-list {
                font-family: 'Nunito', sans-serif;
            }

            .leave-type-item {
                display: flex;
                justify-content: space-between;
                padding: 12px 15px;
                border-bottom: 1px solid #eaeaea;
                font-size: 0.95rem;
                transition: all 0.3s ease;
                border-radius: 6px;
                margin-bottom: 5px;
                background: #ffffff;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            }

            .leave-type-item:hover {
                background: #f8f9ff;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
                transform: translateY(-1px);
            }

            .leave-type-item:last-child {
                border-bottom: none;
                margin-bottom: 0;
            }

            .leave-type-name {
                font-weight: 600;
                color: #4a5568;
                display: flex;
                align-items: center;
            }

            .leave-type-name::before {
                content: '';
                display: inline-block;
                width: 10px;
                height: 10px;
                border-radius: 50%;
                margin-right: 10px;
                background: var(--dot-color, #4e73df);
            }

            .leave-type-details {
                display: flex;
                gap: 15px;
            }

            .leave-type-count {
                color: #4e73df;
                font-weight: 700;
                font-size: 1.05rem;
                min-width: 30px;
                text-align: right;
            }

            .leave-type-percentage {
                color: #36b9cc;
                font-weight: 600;
                min-width: 50px;
                text-align: right;
            }

            .leave-types-total {
                display: flex;
                justify-content: space-between;
                padding: 15px;
                border-top: 2px solid #e2e8f0;
                font-size: 1.1rem;
                font-weight: 700;
                color: #2d3748;
                margin-top: 10px;
                background: #f8f9ff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            /* Color variations for different leave types - now handled dynamically via JavaScript */
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
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                </ol>
            </nav>
        </div>


        <!-- Content Area -->
        <div class="container-fluid">
            <!-- Main Container for Hostel Selection and Stats Cards -->
            <div class="main-container">
                <!-- Hostel Selection Controls -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <label for="hostelSelector" class="form-label me-2 mb-0 text-gray-700"><?php echo $hasSingleScopedHostel ? 'Hostel (Auto):' : 'Hostel:'; ?></label>
                            <div class="hostel-selector-container">
                                <select id="hostelSelector" onchange="changeHostel()"
                                    class="hostel-selector form-select-sm border-primary" style="max-width: 240px;" <?php echo $hasSingleScopedHostel ? 'disabled' : ''; ?>>
                                    <?php if (!$isScopedAdmin): ?>
                                        <option value="">All Hostels</option>
                                    <?php endif; ?>
                                    <?php if (!empty($hostelSelectorOptions) && is_array($hostelSelectorOptions)): ?>
                                        <?php foreach ($hostelSelectorOptions as $hostelOption): ?>
                                            <option value="<?php echo (int)$hostelOption['hostel_id']; ?>" <?php echo ((int)$selectedHostelId === (int)$hostelOption['hostel_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($hostelOption['hostel_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Cards -->
                <div class="row mb-4 g-4">
                    <!-- Pending Leaves Card -->
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="stats-card pending-leaves-card">
                            <div
                                class="card-body text-center d-flex flex-column align-items-center justify-content-center p-0">
                                <i class="fas fa-file-alt mb-3"></i>
                                <div class="stat-label mb-2">Pending Leaves</div>
                                <div class="stat-number"><?php echo $pendingLeaveCount; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Blocked Students Card -->
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="stats-card blocked-students-card">
                            <div
                                class="card-body text-center d-flex flex-column align-items-center justify-content-center p-0">
                                <i class="fas fa-user-slash mb-3"></i>
                                <div class="stat-label mb-2">Blocked Students</div>
                                <div class="stat-number"><?php echo $blockedStudentsCount; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Students Card -->
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="stats-card total-students-card">
                            <div
                                class="card-body text-center d-flex flex-column align-items-center justify-content-center p-0">
                                <i class="fas fa-graduation-cap mb-3"></i>
                                <div class="stat-label mb-2">Total Students</div>
                                <div class="stat-number"><?php echo $totalStudentsCount; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Rooms Card -->
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <div class="stats-card total-rooms-card">
                            <div
                                class="card-body text-center d-flex flex-column align-items-center justify-content-center p-0">
                                <i class="fas fa-door-open mb-3"></i>
                                <div class="stat-label mb-2">Total Rooms</div>
                                <div class="stat-number"><?php echo $totalRoomsCount; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leave Records Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow h-100">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-chart-bar"></i> Approved
                                    Leave Applications</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <label class="me-2 mb-0 text-sm" style="font-size: 0.85rem;">Date:</label>
                                    <input type="date" id="leaveTypeDate" class="form-control form-control-sm"
                                        value="<?php echo date('Y-m-d'); ?>" style="width: auto;"
                                        onchange="handleDateChange()" max="<?php echo date('Y-m-d'); ?>">
                                    <select id="leaveChartTypeSelector" class="form-select form-select-sm ms-2"
                                        style="width: auto;" onchange="changeLeaveChartType(this.value)">
                                        <option value="pie">Pie Chart</option>
                                        <option value="doughnut">Doughnut Chart</option>
                                        <option value="bar">Bar Chart</option>
                                        <option value="line">Line Chart</option>
                                        <option value="polarArea">Polar Area</option>
                                        <option value="table">Table Chart</option>
                                    </select>
                                    <button class="btn btn-primary btn-sm ms-2" onclick="filterLeaveApplications()">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Chart Container -->
                                    <div class="col-xl-6 col-lg-6 mb-4">
                                        <div class="card shadow h-100">
                                            <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary">Leave Applications Chart
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <?php if (empty($typeLabels)): ?>
                                                    <div class="alert alert-info text-center" style="margin-top: 50px;">
                                                        <i class="fas fa-info-circle"></i> No approved leave applications
                                                        found for <?php echo date('F d, Y'); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="chart-container"
                                                        style="position: relative; height:250px; width:100%">
                                                        <canvas id="leaveTypeChart"></canvas>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Leave Types Breakdown Container -->
                                    <div class="col-xl-6 col-lg-6 mb-4">
                                        <div class="card shadow h-100">
                                            <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary">Leave Types Breakdown</h6>
                                            </div>
                                            <div class="card-body">
                                                <div id="leaveTypeCounts" class="leave-types-list">
                                                    <!-- Counts will be populated by JavaScript in line-by-line style -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Summary Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user-check"></i>
                                    Attendance Summary</h6>
                                <div class="d-flex align-items-center">
                                    <label class="me-2 mb-0 text-sm" style="font-size: 0.85rem;">Date:</label>
                                    <input type="date" id="attendanceDate" class="form-control form-control-sm"
                                        value="<?php echo $selectedAttendanceDate; ?>"
                                        onchange="changeAttendanceDate(this.value)" style="width: auto;">
                                    <select id="attendanceChartType" class="form-select form-select-sm ms-2">
                                        <option value="doughnut">Doughnut Chart</option>
                                        <option value="pie">Pie Chart</option>
                                        <option value="bar">Bar Chart</option>
                                        <option value="line">Line Chart</option>
                                        <option value="polarArea">Polar Area</option>
                                        <option value="table">Table Chart</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-xl-6 col-lg-6 mb-4">
                                        <div class="card shadow">
                                            <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary">Attendance for
                                                    <?php echo date('M d, Y', strtotime($selectedAttendanceDate)); ?>
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="chart-container"
                                                    style="position: relative; height:250px; width:100%">
                                                    <canvas id="attendanceChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 mb-4">
                                        <div class="card shadow">
                                            <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary">Attendance Statistics</h6>
                                            </div>
                                            <div class="card-body">
                                                <div id="attendanceStatsContainer" class="leave-types-list">
                                                    <!-- Attendance statistics will be populated by JavaScript -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notice Board Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow h-100">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Notice Board</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-xl-6 col-lg-6 mb-4">
                                        <div class="notice-board">
                                            <h4>Send Notice to All Students</h4>
                                            <div class="notice-form">
                                                <div class="mb-3">
                                                    <label class="form-label">Message</label>
                                                    <textarea id="noticeMessageBottom" class="form-control" rows="4"
                                                        placeholder="Type your message here..."></textarea>
                                                </div>
                                                <button onclick="sendNotice()" class="btn btn-send-notice w-100">
                                                    Send Notice
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6 mb-4">
                                        <div class="notice-board">
                                            <h4>Recent Notices</h4>
                                            <div id="noticesContainer">
                                                <!-- Notices will be loaded here by JavaScript -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>

        <?php include '../assets/footer.php'; ?>

        <script>
            // Loader functionality
            document.addEventListener('DOMContentLoaded', function () {
                // Hide loader after a short delay
                setTimeout(function () {
                    const loaderContainer = document.getElementById('loaderContainer');
                    if (loaderContainer) {
                        loaderContainer.classList.add('hide');
                    }
                }, 500);

                // Initialize donut charts
                setTimeout(function () {
                    initializeDonutCharts();
                }, 1000);

                // Initialize leave charts
                setTimeout(function () {
                    console.log('Initializing leave charts after 1 second delay');
                    initializeLeaveCharts();
                }, 1000);

                // Initialize attendance chart
                setTimeout(function () {
                    initializeAttendanceChart();
                }, 1000);
            });

            // Function to initialize donut charts
            function initializeDonutCharts() {
                // Only initialize if a hostel is selected
                <?php if ($selectedHostelId && !empty($hostelStats)): ?>
                    // Selected hostel chart (only if a hostel is selected)
                    const selectedHostelCtx = document.getElementById('donut-selected-hostel');
                    if (selectedHostelCtx && typeof Chart !== 'undefined') {
                        <?php
                        // Find the selected hostel stats
                        $selectedHostelStats = null;
                        if (!empty($hostelStats)) {
                            foreach ($hostelStats as $stats) {
                                if (intval($stats['hostel_id']) == $selectedHostelId) {
                                    $selectedHostelStats = $stats;
                                    break;
                                }
                            }
                        }
                        if ($selectedHostelStats):
                            ?>
                            new Chart(selectedHostelCtx.getContext('2d'), {
                                type: 'doughnut',
                                data: {
                                    labels: ['Occupied Rooms', 'Vacant Rooms'],
                                    datasets: [{
                                        data: [<?php echo $selectedHostelStats['occupied_rooms']; ?>, <?php echo $selectedHostelStats['vacant_rooms']; ?>],
                                        backgroundColor: [
                                            <?php
                                            if ($selectedHostelId == 1)
                                                echo "'var(--veda)'";
                                            elseif ($selectedHostelId == 2)
                                                echo "'var(--octa)'";
                                            elseif ($selectedHostelId == 3)
                                                echo "'var(--muthu)'";
                                            else
                                                echo "'var(--primary-color)'";
                                            ?>,
                                            'var(--vacant)'
                                        ],
                                        hoverBackgroundColor: [
                                            <?php
                                            if ($selectedHostelId == 1)
                                                echo "'#4e73df'";
                                            elseif ($selectedHostelId == 2)
                                                echo "'#1cc88a'";
                                            elseif ($selectedHostelId == 3)
                                                echo "'#36b9cc'";
                                            else
                                                echo "'#4e73df'";
                                            ?>,
                                            '#e0e0e0'
                                        ],
                                        borderWidth: 2,
                                        borderColor: '#fff'
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    cutout: '65%',
                                    plugins: {
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                padding: 15,
                                                font: {
                                                    size: 12
                                                }
                                            }
                                        },
                                        tooltip: {
                                            enabled: true,
                                            callbacks: {
                                                label: function (context) {
                                                    let label = context.label || '';
                                                    if (label) {
                                                        label += ': ';
                                                    }
                                                    label += context.parsed + ' rooms';
                                                    return label;
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        <?php endif; ?>
                    }
                <?php else: ?>
                    console.log('No hostel selected, skipping donut chart initialization');
                <?php endif; ?>
            }

            // Dynamic Data Fetching Functions - Declare global chart variables first
            let leaveTypeChart, attendanceChartInstance;

            // Function to change hostel and reload dashboard data
            function changeHostel() {
                const hostelSelector = document.getElementById('hostelSelector');
                console.log('Hostel selector element:', hostelSelector);

                if (!hostelSelector) {
                    console.error('Hostel selector not found!');
                    return;
                }

                const hostelId = hostelSelector.value;
                console.log('Selected hostel ID:', hostelId);

                // Update URL and reload the page to ensure all data is refreshed
                const currentUrl = new URL(window.location);
                console.log('Current URL before change:', currentUrl.toString());
                console.log('Current search params:', currentUrl.searchParams.toString());

                if (hostelId) {
                    currentUrl.searchParams.set('hostel_id', hostelId);
                    console.log('Setting hostel_id to:', hostelId);
                } else {
                    currentUrl.searchParams.delete('hostel_id');
                    console.log('Deleting hostel_id parameter');
                }

                console.log('Search params after change:', currentUrl.searchParams.toString());
                const newUrl = currentUrl.toString();
                console.log('New URL:', newUrl);

                // Use replace to avoid browser history issues
                window.location.replace(newUrl);
            }

            // Hostel selector is now updated via full page refresh, so this function is no longer needed

            // Hostel selector is initialized by PHP based on URL parameter, so no JavaScript initialization needed

            // Function to initialize leave charts
            function initializeLeaveCharts() {
                console.log('Initializing leave charts...');
                // Check if Chart.js is available
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js is not loaded');
                    return;
                }

                // Leave Type Chart (only if there's data)
                const typeCtx = document.getElementById('leaveTypeChart');
                console.log('Canvas element for initial chart:', typeCtx);
                if (typeCtx) {
                    const hasData = <?php echo !empty($typeLabels) ? 'true' : 'false'; ?>;
                    console.log('Has initial data:', hasData);

                    if (hasData) {
                        console.log('Creating initial chart with data:', {
                            labels: <?php echo json_encode($typeLabels); ?>,
                            data: <?php echo json_encode($typeData); ?>
                        });
                        leaveTypeChart = new Chart(typeCtx.getContext('2d'), {
                            type: 'pie',
                            data: {
                                labels: <?php echo json_encode($typeLabels); ?>,
                                datasets: [{
                                    data: <?php echo json_encode($typeData); ?>,
                                    backgroundColor: <?php echo json_encode($typeColors); ?>,
                                    borderColor: <?php echo json_encode($typeBorderColors); ?>,
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            padding: 10,
                                            font: {
                                                size: 11
                                            }
                                        }
                                    }
                                }
                            }
                        });
                        console.log('Initial chart created:', leaveTypeChart);

                        // Update counts below chart
                        const labels = <?php echo json_encode($typeLabels); ?>;
                        const data = <?php echo json_encode($typeData); ?>;
                        updateLeaveTypeCounts(labels, data);
                    }
                }
            }

            // Function to change leave date and reload page
            function changeLeaveDate(date) {
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.set('leave_date', date);
                window.history.pushState({}, '', currentUrl);

                // Refresh leave type chart dynamically
                const hostelId = document.getElementById('hostelSelector').value;
                refreshLeaveByType(date, hostelId);
            }

            // Function to filter leave applications by date and hostel
            function filterLeaveApplications() {
                console.log('Filtering leave applications...');
                const date = document.getElementById('leaveTypeDate').value;
                const hostelId = document.getElementById('hostelSelector').value;

                // Show minimal loading indicator only on button
                const filterBtn = document.querySelector('button[onclick="filterLeaveApplications()"]');
                console.log('Filter button:', filterBtn);
                if (filterBtn) {
                    filterBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    filterBtn.disabled = true;
                }

                // Refresh leave type chart dynamically
                refreshLeaveByType(date, hostelId);

                // Update URL without page reload
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.set('leave_date', date);
                if (hostelId) {
                    currentUrl.searchParams.set('hostel_id', hostelId);
                } else {
                    currentUrl.searchParams.delete('hostel_id');
                }
                window.history.pushState({}, '', currentUrl);

                // Button will be restored by the refreshLeaveByType function
            }

            // Function to handle date changes
            function handleDateChange() {
                console.log('Date changed, triggering filter...');
                // Add a small delay to ensure DOM is fully updated
                setTimeout(() => {
                    // Call filter function when date changes
                    filterLeaveApplications();

                    // Also update attendance date to match leave date
                    const leaveDate = document.getElementById('leaveTypeDate').value;
                    const attendanceDateInput = document.getElementById('attendanceDate');
                    if (attendanceDateInput && attendanceDateInput.value !== leaveDate) {
                        attendanceDateInput.value = leaveDate;
                        // Trigger change event for attendance date
                        changeAttendanceDate(leaveDate);
                    }
                }, 150);
            }

            // Function to update leave type counts below chart
            function updateLeaveTypeCounts(labels, data) {
                const countsContainer = document.getElementById('leaveTypeCounts');
                if (!countsContainer) return;

                if (!labels || !data || labels.length === 0) {
                    countsContainer.innerHTML = '<div class="text-center text-muted py-4">No leave data available</div>';
                    return;
                }

                let countsHtml = '';
                const total = data.reduce((sum, count) => sum + count, 0);

                // Define specific colors for each leave type to match chart colors
                const leaveTypeColors = {
                    'General Leave': '#4e73df',
                    'Outing': '#1cc88a',
                    'onduty': '#36b9cc',
                    'Leave': '#f6c23e',
                    'emergency Leave': '#e74a3b'
                };

                // Default colors for any other leave types
                const defaultColors = [
                    '#858796',
                    '#9c27b0',
                    '#ff9800',
                    '#00bcd4'
                ];

                // Attractive line-by-line styling without small buttons
                labels.forEach((label, index) => {
                    const count = data[index];
                    const percentage = total > 0 ? ((count / total) * 100).toFixed(1) : 0;

                    // Assign color based on leave type name
                    let color = leaveTypeColors[label] || defaultColors[index % defaultColors.length];

                    countsHtml += `
                    <div class="leave-type-item">
                        <span class="leave-type-name" style="--dot-color: ${color}">${label}</span>
                        <div class="leave-type-details">
                            <span class="leave-type-count">${count}</span>
                            <span class="leave-type-percentage">${percentage}%</span>
                        </div>
                    </div>
                `;
                });

                // Add total line
                countsHtml += `
                <div class="leave-types-total">
                    <span>Total</span>
                    <span>${total}</span>
                </div>
            `;

                countsContainer.innerHTML = countsHtml;
            }

            // Function to change leave chart type
            function changeLeaveChartType(chartType) {
                const leaveChartCtx = document.getElementById('leaveTypeChart');
                if (!leaveChartCtx) return;

                const chartContainer = leaveChartCtx.closest('.card-body');

                if (chartType === 'table') {
                    // Hide chart and show table
                    leaveChartCtx.closest('.chart-container').style.display = 'none';

                    // Create table if it doesn't exist
                    let tableContainer = chartContainer.querySelector('.leave-table-container');
                    if (!tableContainer) {
                        tableContainer = document.createElement('div');
                        tableContainer.className = 'leave-table-container';
                        chartContainer.insertBefore(tableContainer, leaveChartCtx.closest('.chart-container').nextSibling);
                    }

                    tableContainer.style.display = 'block';

                    // Get leave type data from the current chart
                    if (leaveTypeChart && leaveTypeChart.data) {
                        const leaveTypes = leaveTypeChart.data.labels;
                        const leaveCounts = leaveTypeChart.data.datasets[0].data;
                        const totalLeaves = leaveCounts.reduce((a, b) => a + b, 0);

                        let tableRows = '';
                        leaveTypes.forEach((type, index) => {
                            const count = leaveCounts[index];
                            const percentage = totalLeaves > 0 ? ((count / totalLeaves) * 100).toFixed(1) : 0;
                            const badgeColors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
                            const badgeColor = badgeColors[index % badgeColors.length];

                            tableRows += `
                            <tr>
                                <td><span class="badge bg-${badgeColor}"><i class="fas fa-file-alt"></i> ${type}</span></td>
                                <td><strong>${count}</strong></td>
                                <td><strong>${percentage}%</strong></td>
                            </tr>
                        `;
                        });

                        tableContainer.innerHTML = `
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Leave Type (Approved Only)</th>
                                        <th>Count</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${tableRows}
                                    <tr class="table-primary">
                                        <td><span class="badge bg-dark"><i class="fas fa-clipboard-list"></i> Total Approved</span></td>
                                        <td><strong>${totalLeaves}</strong></td>
                                        <td><strong>100%</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    `;

                        // Update counts below chart
                        if (leaveTypeChart && leaveTypeChart.data) {
                            updateLeaveTypeCounts(leaveTypeChart.data.labels, leaveTypeChart.data.datasets[0].data);
                        }
                    }
                } else {
                    // Show chart and hide table
                    leaveChartCtx.closest('.chart-container').style.display = 'block';
                    const tableContainer = chartContainer.querySelector('.leave-table-container');
                    if (tableContainer) {
                        tableContainer.style.display = 'none';
                    }

                    // Remove any overlays when switching back to chart view
                    const chartContainerElement = leaveChartCtx.closest('.chart-container');
                    if (chartContainerElement) {
                        const existingOverlay = chartContainerElement.querySelector('.error-overlay, .no-data-overlay');
                        if (existingOverlay) {
                            existingOverlay.remove();
                        }
                    }

                    // Ensure chart is properly rendered
                    if (leaveTypeChart) {
                        setTimeout(() => {
                            leaveTypeChart.resize();
                        }, 50);
                    }

                    if (leaveTypeChart) {
                        try {
                            // Add a small delay to ensure smooth transition
                            setTimeout(() => {
                                leaveTypeChart.config.type = chartType;
                                leaveTypeChart.update();

                                // Update counts below chart
                                updateLeaveTypeCounts(leaveTypeChart.data.labels, leaveTypeChart.data.datasets[0].data);
                            }, 75);
                        } catch (chartError) {
                            console.error('Error updating chart type:', chartError);
                        }
                    }
                }
            }

            // Function to update attendance statistics in line-by-line format
            function updateAttendanceStats(present, absent, total, presentPercentage, absentPercentage) {
                const statsContainer = document.getElementById('attendanceStatsContainer');
                if (!statsContainer) return;

                let statsHtml = '';

                // Present item
                statsHtml += `
                <div class="leave-type-item">
                    <span class="leave-type-name" style="--dot-color: #1cc88a">Present</span>
                    <div class="leave-type-details">
                        <span class="leave-type-count">${present}</span>
                        <span class="leave-type-percentage">${presentPercentage}%</span>
                    </div>
                </div>
            `;

                // Absent item
                statsHtml += `
                <div class="leave-type-item">
                    <span class="leave-type-name" style="--dot-color: #f6c23e">Absent</span>
                    <div class="leave-type-details">
                        <span class="leave-type-count">${absent}</span>
                        <span class="leave-type-percentage">${absentPercentage}%</span>
                    </div>
                </div>
            `;

                // Total item
                statsHtml += `
                <div class="leave-types-total">
                    <span>Total Students</span>
                    <span>${total}</span>
                </div>
            `;

                statsContainer.innerHTML = statsHtml;
            }

            // Function to change attendance date and reload page
            function changeAttendanceDate(date) {
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.set('attendance_date', date);
                // Also update leave_date parameter to keep them synchronized
                currentUrl.searchParams.set('leave_date', date);
                window.history.pushState({}, '', currentUrl);

                // Refresh attendance data dynamically
                const hostelId = document.getElementById('hostelSelector').value;
                refreshAttendance(hostelId);

                // Also update leave date input and refresh leave data
                const leaveDateInput = document.getElementById('leaveTypeDate');
                if (leaveDateInput && leaveDateInput.value !== date) {
                    leaveDateInput.value = date;
                    // Refresh leave data
                    refreshLeaveByType(date, hostelId);
                }
            }

            // Function to initialize attendance chart
            function initializeAttendanceChart() {
                // Check if Chart.js is available
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js is not loaded');
                    return;
                }

                const attendanceCtx = document.getElementById('attendanceChart');
                if (!attendanceCtx) return;

                const attendanceChartTypeSelector = document.getElementById('attendanceChartType');

                attendanceChartInstance = new Chart(attendanceCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Present', 'Absent'],
                        datasets: [{
                            data: [<?php echo $presentCount; ?>, <?php echo $absentCount; ?>],
                            backgroundColor: [
                                'rgba(28, 200, 138, 0.8)',
                                'rgba(246, 194, 62, 0.8)'
                            ],
                            borderColor: [
                                'rgba(28, 200, 138, 1)',
                                'rgba(246, 194, 62, 1)'
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += context.parsed;
                                        let total = <?php echo $totalStudents; ?>;
                                        let percentage = ((context.parsed / total) * 100).toFixed(1);
                                        label += ' (' + percentage + '%)';
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });

                // Initialize attendance statistics
                updateAttendanceStats(<?php echo $presentCount; ?>, <?php echo $absentCount; ?>, <?php echo $totalStudents; ?>, <?php echo $presentPercentage; ?>, <?php echo $absentPercentage; ?>);

                // Chart type change handler
                attendanceChartTypeSelector.addEventListener('change', function () {
                    const chartType = this.value;
                    const chartContainer = attendanceCtx.closest('.card-body');

                    if (chartType === 'table') {
                        // Hide chart and show table
                        attendanceCtx.closest('.chart-container').style.display = 'none';

                        // Create table if it doesn't exist
                        let tableContainer = chartContainer.querySelector('.attendance-table-container');
                        if (!tableContainer) {
                            tableContainer = document.createElement('div');
                            tableContainer.className = 'attendance-table-container';
                            chartContainer.insertBefore(tableContainer, attendanceCtx.closest('.chart-container').nextSibling);
                        }

                        tableContainer.style.display = 'block';
                        const presentCount = <?php echo $presentCount; ?>;
                        const absentCount = <?php echo $absentCount; ?>;
                        const totalCount = <?php echo $totalStudents; ?>;
                        const presentPct = <?php echo $presentPercentage; ?>;
                        const absentPct = <?php echo $absentPercentage; ?>;

                        tableContainer.innerHTML = `
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Status</th>
                                        <th>Count</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge bg-success"><i class="fas fa-check"></i> Present</span></td>
                                        <td><strong>${presentCount}</strong></td>
                                        <td><strong>${presentPct}%</strong></td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-warning text-dark"><i class="fas fa-times"></i> Absent</span></td>
                                        <td><strong>${absentCount}</strong></td>
                                        <td><strong>${absentPct}%</strong></td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td><span class="badge bg-primary"><i class="fas fa-users"></i> Total</span></td>
                                        <td><strong>${totalCount}</strong></td>
                                        <td><strong>100%</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    `;
                    } else {
                        // Show chart and hide table
                        attendanceCtx.closest('.chart-container').style.display = 'block';
                        const tableContainer = chartContainer.querySelector('.attendance-table-container');
                        if (tableContainer) {
                            tableContainer.style.display = 'none';
                        }

                        attendanceChartInstance.config.type = chartType;
                        attendanceChartInstance.update();
                    }
                });
            }

            // Function to refresh card statistics dynamically
            function refreshStats(hostelId) {
                // If hostelId is not provided, get it from the selector
                if (hostelId === undefined || hostelId === null) {
                    hostelId = document.getElementById('hostelSelector').value;
                }
                const hostelParam = hostelId ? `&hostel_id=${hostelId}` : '';

                console.log('Refreshing stats with hostelId:', hostelId, hostelParam);

                // Refresh stats data

                const url = `index.php?api=1&action=get_stats${hostelParam}`;
                console.log('Fetching stats from URL:', url);

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Stats data received:', data);
                        if (!data.error) {
                            updateCardValue('pending_leaves', data.pending_leaves);
                            updateCardValue('blocked_students', data.blocked_students);
                            updateCardValue('total_students', data.total_students);
                            updateCardValue('total_rooms', data.total_rooms);
                        }
                    })
                    .catch(error => console.error('Error fetching stats:', error));
            }

            // Function to update card value with animation
            function updateCardValue(cardId, newValue) {
                // Select the specific card based on cardId
                let selector = '';
                switch (cardId) {
                    case 'pending_leaves':
                        selector = '.pending-leaves-card .stat-number';
                        break;
                    case 'blocked_students':
                        selector = '.blocked-students-card .stat-number';
                        break;
                    case 'total_students':
                        selector = '.total-students-card .stat-number';
                        break;
                    case 'total_rooms':
                        selector = '.total-rooms-card .stat-number';
                        break;
                    default:
                        return;
                }

                const element = document.querySelector(selector);
                if (element) {
                    animateValue(element, parseInt(element.textContent) || 0, newValue, 500);
                }
            }

            // Function to animate number changes
            function animateValue(element, start, end, duration) {
                const range = end - start;
                const increment = range / (duration / 16);
                let current = start;

                const timer = setInterval(() => {
                    current += increment;
                    if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                        element.textContent = end;
                        clearInterval(timer);
                    } else {
                        element.textContent = Math.round(current);
                    }
                }, 16);
            }

            // Function to refresh attendance data dynamically
            function refreshAttendance(hostelId) {
                // If hostelId is not provided, get it from the selector
                if (hostelId === undefined || hostelId === null) {
                    hostelId = document.getElementById('hostelSelector').value;
                }
                const date = document.getElementById('attendanceDate').value;
                const hostelParam = hostelId ? `&hostel_id=${hostelId}` : '';

                console.log('Refreshing attendance with hostelId:', hostelId, hostelParam);

                // Refresh attendance data

                const url = `index.php?api=1&action=get_attendance${hostelParam}&date=${date}`;
                console.log('Fetching attendance from URL:', url);

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Attendance data received:', data);
                        if (!data.error && attendanceChartInstance) {
                            attendanceChartInstance.data.datasets[0].data = [data.present, data.absent];
                            attendanceChartInstance.update();

                            // Update attendance header with date
                            const attendanceHeader = document.querySelector('.card-header h6');
                            if (attendanceHeader && attendanceHeader.textContent.includes('Attendance for')) {
                                const formattedDate = new Date(date + 'T00:00:00').toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'short',
                                    day: '2-digit'
                                });
                                attendanceHeader.textContent = `Attendance for ${formattedDate}`;
                            }

                            // Update attendance statistics with new line-by-line format
                            updateAttendanceStats(data.present, data.absent, data.total, data.present_percentage, data.absent_percentage);

                            // Update badges for backward compatibility
                            document.querySelectorAll('.badge').forEach(badge => {
                                const text = badge.textContent;
                                if (text.includes('Present:')) {
                                    badge.innerHTML = `<i class="fas fa-check"></i> Present: ${data.present}`;
                                } else if (text.includes('Absent:')) {
                                    badge.innerHTML = `<i class="fas fa-times"></i> Absent: ${data.absent}`;
                                } else if (text.includes('Total:')) {
                                    badge.innerHTML = `<i class="fas fa-users"></i> Total: ${data.total}`;
                                }
                            });

                            // Update old card values for backward compatibility
                            document.querySelectorAll('.border-left-success, .border-left-warning, .border-left-primary').forEach(card => {
                                const title = card.querySelector('.text-uppercase')?.textContent.toLowerCase();
                                const valueEl = card.querySelector('.h5');
                                if (title && valueEl) {
                                    if (title.includes('present')) {
                                        valueEl.textContent = `${data.present} (${data.present_percentage}%)`;
                                    } else if (title.includes('absent')) {
                                        valueEl.textContent = `${data.absent} (${data.absent_percentage}%)`;
                                    } else if (title.includes('total')) {
                                        valueEl.textContent = data.total;
                                    }
                                }
                            });
                        }
                    })
                    .catch(error => console.error('Error fetching attendance:', error));
            }

            // Function to refresh leave by type chart
            function refreshLeaveByType(date, hostelId) {
                console.log('Refreshing leave by type chart...', { date, hostelId });
                // If hostelId is not provided, get it from the selector
                if (hostelId === undefined || hostelId === null) {
                    hostelId = document.getElementById('hostelSelector').value;
                }
                const leaveDate = date || document.getElementById('leaveTypeDate').value;
                const hostelParam = hostelId ? `&hostel_id=${hostelId}` : '';

                // Don't show loading indicator, just keep the existing chart while updating
                console.log('Updating chart without showing loading indicator...');

                // Refresh leave by type data

                const url = `index.php?api=1&action=get_leave_by_type${hostelParam}&date=${leaveDate}`;
                console.log('Fetching data from URL:', url);
                fetch(url)
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Received data:', data);
                        // Restore filter button
                        const filterBtn = document.querySelector('button[onclick="filterLeaveApplications()"]');
                        console.log('Restoring filter button:', filterBtn);
                        if (filterBtn) {
                            filterBtn.innerHTML = '<i class="fas fa-filter"></i> Filter';
                            filterBtn.disabled = false;
                        } else {
                            // Fallback: try to find button by class
                            const fallbackBtn = document.querySelector('.btn.btn-primary.btn-sm.ms-2');
                            if (fallbackBtn) {
                                fallbackBtn.innerHTML = '<i class="fas fa-filter"></i> Filter';
                                fallbackBtn.disabled = false;
                            }
                        }

                        console.log('Leave by type data:', data);
                        if (!data.error) {
                            const chartCardBody = document.querySelector('#leaveTypeChart')?.closest('.card-body');
                            console.log('Chart card body:', chartCardBody);
                            if (!chartCardBody) return;

                            if (!data.has_data || data.labels.length === 0) {
                                // Destroy existing chart if it exists
                                if (leaveTypeChart) {
                                    console.log('Destroying existing chart (no data)');
                                    leaveTypeChart.destroy();
                                    leaveTypeChart = null;
                                }

                                // Show no data message without replacing the whole container
                                const noDataHtml = `
                                <div class="d-flex justify-content-center align-items-center" style="height: 100%; position: absolute; top: 0; left: 0; width: 100%; background: rgba(255,255,255,0.9); z-index: 10;">
                                    <div class="alert alert-info text-center mb-0">
                                        <i class="fas fa-info-circle"></i> No approved leave applications found for ${new Date(data.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}
                                    </div>
                                </div>
                            `;

                                // Add no data overlay to existing chart
                                const chartContainer = chartCardBody.querySelector('.chart-container');
                                if (chartContainer) {
                                    // Remove any existing overlays
                                    const existingOverlay = chartContainer.querySelector('.error-overlay, .no-data-overlay');
                                    if (existingOverlay) {
                                        existingOverlay.remove();
                                    }

                                    // Add new no data overlay
                                    const overlayDiv = document.createElement('div');
                                    overlayDiv.className = 'no-data-overlay';
                                    overlayDiv.innerHTML = noDataHtml;
                                    chartContainer.style.position = 'relative';
                                    chartContainer.appendChild(overlayDiv);
                                }

                                // Clear counts below chart
                                updateLeaveTypeCounts([], []);
                            } else {
                                // Remove any existing overlays before updating chart
                                const chartContainer = chartCardBody.querySelector('.chart-container');
                                if (chartContainer) {
                                    // Remove any existing overlays
                                    const existingOverlay = chartContainer.querySelector('.error-overlay, .no-data-overlay');
                                    if (existingOverlay) {
                                        existingOverlay.remove();
                                    }
                                }

                                // If no chart exists, recreate the chart container
                                if (!document.getElementById('leaveTypeChart')) {
                                    chartCardBody.innerHTML = `
                                    <div class="chart-container" style="position: relative; height:300px; width:100%">
                                        <canvas id="leaveTypeChart"></canvas>
                                    </div>
                                    <div class="mt-3 text-center" id="leaveTypeCounts">
                                        <!-- Counts will be populated by JavaScript -->
                                    </div>
                                `;
                                }

                                // Create or update the chart
                                const ctx = document.getElementById('leaveTypeChart');
                                console.log('Canvas element:', ctx);
                                if (ctx) {
                                    // Destroy existing chart if it exists
                                    if (leaveTypeChart) {
                                        console.log('Destroying existing chart');
                                        leaveTypeChart.destroy();
                                        leaveTypeChart = null;
                                    }

                                    // Define leave type specific colors
                                    const leaveTypeColors = {
                                        'General Leave': 'rgba(78, 115, 223, 0.7)',
                                        'Outing': 'rgba(28, 200, 138, 0.7)',
                                        'onduty': 'rgba(54, 185, 204, 0.7)',
                                        'Leave': 'rgba(246, 194, 62, 0.7)',
                                        'emergency Leave': 'rgba(231, 74, 59, 0.7)'
                                    };
                                    const leaveTypeBorderColors = {
                                        'General Leave': 'rgba(78, 115, 223, 1)',
                                        'Outing': 'rgba(28, 200, 138, 1)',
                                        'onduty': 'rgba(54, 185, 204, 1)',
                                        'Leave': 'rgba(246, 194, 62, 1)',
                                        'emergency Leave': 'rgba(231, 74, 59, 1)'
                                    };

                                    const defaultColors = [
                                        'rgba(133, 135, 150, 0.7)', 'rgba(156, 39, 176, 0.7)',
                                        'rgba(255, 152, 0, 0.7)', 'rgba(0, 188, 212, 0.7)'
                                    ];
                                    const defaultBorderColors = [
                                        'rgba(133, 135, 150, 1)', 'rgba(156, 39, 176, 1)',
                                        'rgba(255, 152, 0, 1)', 'rgba(0, 188, 212, 1)'
                                    ];

                                    const bgColors = data.labels.map((label, index) => {
                                        return leaveTypeColors[label] || defaultColors[index % defaultColors.length];
                                    });
                                    const borderColors = data.labels.map((label, index) => {
                                        return leaveTypeBorderColors[label] || defaultBorderColors[index % defaultBorderColors.length];
                                    });

                                    // Create new chart
                                    console.log('Creating new chart with data:', data);

                                    // Ensure any existing chart is destroyed
                                    if (leaveTypeChart) {
                                        leaveTypeChart.destroy();
                                        leaveTypeChart = null;
                                    }

                                    try {
                                        leaveTypeChart = new Chart(ctx.getContext('2d'), {
                                            type: 'pie',
                                            data: {
                                                labels: data.labels,
                                                datasets: [{
                                                    data: data.data,
                                                    backgroundColor: bgColors,
                                                    borderColor: borderColors,
                                                    borderWidth: 2
                                                }]
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false
                                            }
                                        });
                                        console.log('Chart created:', leaveTypeChart);

                                        // Update counts below chart
                                        updateLeaveTypeCounts(data.labels, data.data);
                                    } catch (chartError) {
                                        console.error('Error creating chart:', chartError);
                                        // In case of chart creation error, show error message
                                        const errorHtml = `
                                        <div class="d-flex justify-content-center align-items-center" style="height: 100%; position: absolute; top: 0; left: 0; width: 100%; background: rgba(255,255,255,0.9); z-index: 10;">
                                            <div class="alert alert-danger text-center mb-0">
                                                <i class="fas fa-exclamation-triangle"></i> Error displaying chart. Please try again.
                                            </div>
                                        </div>
                                    `;

                                        // Add error overlay to existing chart
                                        const chartContainer = chartCardBody.querySelector('.chart-container');
                                        if (chartContainer) {
                                            // Remove any existing overlays
                                            const existingOverlay = chartContainer.querySelector('.error-overlay');
                                            if (existingOverlay) {
                                                existingOverlay.remove();
                                            }

                                            // Add new error overlay
                                            const overlayDiv = document.createElement('div');
                                            overlayDiv.className = 'error-overlay';
                                            overlayDiv.innerHTML = errorHtml;
                                            chartContainer.style.position = 'relative';
                                            chartContainer.appendChild(overlayDiv);
                                        }
                                        updateLeaveTypeCounts([], []);
                                    }
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching leave by type:', error);
                        console.log('Error occurred in fetch request');
                        // Restore filter button in case of error
                        const filterBtn = document.querySelector('button[onclick="filterLeaveApplications()"]');
                        if (filterBtn) {
                            filterBtn.innerHTML = '<i class="fas fa-filter"></i> Filter';
                            filterBtn.disabled = false;
                        }

                        // Show error in the existing chart container without replacing it completely
                        const chartCardBody = document.querySelector('#leaveTypeChart')?.closest('.card-body');
                        console.log('Chart card body (error):', chartCardBody);
                        if (chartCardBody) {
                            // Just show error message without replacing the whole container
                            const errorHtml = `
                            <div class="d-flex justify-content-center align-items-center" style="height: 100%; position: absolute; top: 0; left: 0; width: 100%; background: rgba(255,255,255,0.9); z-index: 10;">
                                <div class="alert alert-danger text-center mb-0">
                                    <i class="fas fa-exclamation-triangle"></i> Error loading leave data. Please try again.
                                </div>
                            </div>
                        `;

                            // Add error overlay to existing chart
                            const chartContainer = chartCardBody.querySelector('.chart-container');
                            if (chartContainer) {
                                // Remove any existing error overlays
                                const existingOverlay = chartContainer.querySelector('.error-overlay');
                                if (existingOverlay) {
                                    existingOverlay.remove();
                                }

                                // Add new error overlay
                                const overlayDiv = document.createElement('div');
                                overlayDiv.className = 'error-overlay';
                                overlayDiv.innerHTML = errorHtml;
                                chartContainer.style.position = 'relative';
                                chartContainer.appendChild(overlayDiv);
                            }

                            // Clear counts below chart
                            updateLeaveTypeCounts([], []);
                        }
                    });
            }

            // Function to refresh room occupancy


            // Function to refresh total leave count
            function refreshTotalLeaveCount(hostelId) {
                // If hostelId is not provided, get it from the selector
                if (hostelId === undefined || hostelId === null) {
                    hostelId = document.getElementById('hostelSelector').value;
                }
                const hostelParam = hostelId ? `&hostel_id=${hostelId}` : '';

                console.log('Refreshing total leave count with hostelId:', hostelId, hostelParam);

                const url = `index.php?api=1&action=get_total_leaves${hostelParam}`;
                console.log('Fetching total leave count from URL:', url);

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Total leave count data received:', data);
                        if (!data.error) {
                            const totalLeaveElement = document.querySelector('.h1.mb-0.font-weight-bold.text-gray-800');
                            if (totalLeaveElement) {
                                // Animate the number change
                                const startValue = parseInt(totalLeaveElement.textContent) || 0;
                                const endValue = data.total_leaves;
                                animateValue(totalLeaveElement, startValue, endValue, 500);
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching total leave count:', error));
            }

            // Notice Board Functions
            function sendNotice() {
                // Try to get the message from the bottom notice board first, then fallback to top
                const messageElement = document.getElementById('noticeMessageBottom') || document.getElementById('noticeMessage');
                const message = messageElement ? messageElement.value.trim() : '';

                if (!message) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Empty Message',
                        text: 'Please enter a message before sending!'
                    });
                    return;
                }

                fetch('index.php?api=1&action=send_notice', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `message=${encodeURIComponent(message)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Notice Sent!',
                                text: 'Your message has been sent to all students.',
                                timer: 2000
                            });
                            // Clear the message from the correct textarea
                            if (messageElement) {
                                messageElement.value = '';
                            }
                            // Reload notices
                            loadNotices();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to send notice'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to send notice'
                        });
                    });
            }

            // New functions for notice edit and delete
            function editNotice(id, content) {
                Swal.fire({
                    title: 'Edit Notice',
                    input: 'textarea',
                    inputValue: content,
                    inputAttributes: {
                        'aria-label': 'Edit notice content'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Update',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const newContent = result.value.trim();
                        if (!newContent) {
                            Swal.fire('Error', 'Notice content cannot be empty', 'error');
                            return;
                        }

                        fetch('index.php?api=1&action=update_notice', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `id=${id}&message=${encodeURIComponent(newContent)}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Success', 'Notice updated successfully', 'success');
                                    // Reload notices
                                    loadNotices();
                                } else {
                                    Swal.fire('Error', data.message || 'Failed to update notice', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire('Error', 'Failed to update notice', 'error');
                            });
                    }
                });
            }

            function deleteNotice(id) {
                Swal.fire({
                    title: 'Delete Notice',
                    text: 'Are you sure you want to delete this notice?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('index.php?api=1&action=delete_notice', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `id=${id}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Deleted!', 'Notice has been deleted.', 'success');
                                    // Reload notices
                                    loadNotices();
                                } else {
                                    Swal.fire('Error', data.message || 'Failed to delete notice', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire('Error', 'Failed to delete notice', 'error');
                            });
                    }
                });
            }

            function loadNotices() {
                fetch('index.php?api=1&action=get_notices')
                    .then(response => response.json())
                    .then(data => {
                        // Get the notices container in the new structure
                        let noticesContainer = document.getElementById('noticesContainer');
                        if (!noticesContainer) return;

                        if (!data.success || data.notices.length === 0) {
                            noticesContainer.innerHTML = `
                        <div class="no-notices">
                            <i class="fas fa-bell"></i>
                            <h5>No Notices Found</h5>
                            <p>There are no notices to display at the moment.</p>
                        </div>
                    `;
                            return;
                        }

                        let noticesHtml = '';
                        data.notices.forEach(notice => {
                            // Format the date
                            const noticeDate = new Date(notice.created_at);
                            const formattedDate = noticeDate.toLocaleString();

                            noticesHtml += `
                        <div class="notice-item border-start border-primary border-4 p-3 mb-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <p class="mb-1">${notice.content}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> ${formattedDate}
                                    </small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" id="noticeDropdown${notice.id}" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="noticeDropdown${notice.id}">
                                        <li><a class="dropdown-item" href="#" onclick="editNotice(${notice.id}, '${notice.content.replace(/'/g, "\\'")}'); return false;"><i class="fas fa-edit"></i> Edit</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="deleteNotice(${notice.id}); return false;"><i class="fas fa-trash"></i> Delete</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    `;
                        });

                        noticesContainer.innerHTML = noticesHtml;
                    })
                    .catch(error => {
                        console.error('Error loading notices:', error);
                    });
            }

            // Load notices on page load
            document.addEventListener('DOMContentLoaded', function () {
                // Notice functionality ready
                loadNotices();

                // Initial load - fetch fresh data immediately
                const leaveDate = document.getElementById('leaveTypeDate')?.value;
                const attendanceDate = document.getElementById('attendanceDate')?.value;
                const hostelId = document.getElementById('hostelSelector').value;
                console.log('Initial data refresh check', { leaveDate, attendanceDate, hostelId, leaveTypeChart, attendanceChartInstance });

                // Refresh stats data immediately
                console.log('Initial stats data refresh');
                refreshStats(hostelId);

                // Refresh leave data if we have a date
                if (leaveDate && leaveTypeChart) {
                    console.log('Initial leave data refresh');
                    setTimeout(function () {
                        refreshLeaveByType(leaveDate, hostelId);
                    }, 1000); // Wait 1 second after page load to refresh
                }

                // Refresh attendance data if we have a date
                if (attendanceDate && attendanceChartInstance) {
                    console.log('Initial attendance data refresh');
                    setTimeout(function () {
                        refreshAttendance(hostelId);
                    }, 1000); // Wait 1 second after page load to refresh
                }

                // Auto-refresh leave chart every 30 seconds
                setInterval(function () {
                    const leaveDate = document.getElementById('leaveTypeDate')?.value;
                    const hostelId = document.getElementById('hostelSelector').value;
                    if (leaveDate) {
                        console.log('Auto-refreshing leave chart', { leaveDate, hostelId });
                        refreshLeaveByType(leaveDate, hostelId);
                    }
                }, 30000); // 30 seconds

                // Auto-refresh attendance chart every 30 seconds
                setInterval(function () {
                    const attendanceDate = document.getElementById('attendanceDate')?.value;
                    const hostelId = document.getElementById('hostelSelector').value;
                    if (attendanceDate) {
                        console.log('Auto-refreshing attendance chart', { attendanceDate, hostelId });
                        refreshAttendance(hostelId);
                    }
                }, 30000); // 30 seconds

                // Auto-refresh stats cards every 60 seconds
                setInterval(function () {
                    const hostelId = document.getElementById('hostelSelector').value;
                    console.log('Auto-refreshing stats');
                    refreshStats(hostelId);
                }, 60000); // 60 seconds
            });
        </script>
</body>

</html>