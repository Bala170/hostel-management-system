<?php
session_start();
include './admin_scope.php';

if (!is_any_admin_role()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db.php';
date_default_timezone_set('Asia/Kolkata');

// Helper functions
if (!function_exists('esc_raw')) {
    function esc_raw($str) {
        global $conn;
        return mysqli_real_escape_string($conn, trim($str));
    }
}

function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
require_once('../TCPDF/tcpdf.php');
switch (true) {

    // ===================== PDF EXPORT using TCPDF =====================
    case (isset($_GET['download_pdf']) && $_GET['download_pdf'] == 1):
        if (!file_exists('../TCPDF/tcpdf.php')) {
            die('TCPDF library not found. Please download TCPDF and place it in TCPDF folder.');
        }
        require_once('../TCPDF/tcpdf.php');
        $hostelName = $_GET['hostel'] ?? '';
        $department = $_GET['department'] ?? '';
        $year = $_GET['year'] ?? '';
        $block = $_GET['block'] ?? '';
        $floor = $_GET['floor'] ?? '';
        $roomType = $_GET['room_type'] ?? '';
        $userType = $_GET['user_type'] ?? '';
        $scopeGender = get_hostel_gender_scope_for_role();
        
        $hostelId = null;
        if (!empty($hostelName)) {
            if ($scopeGender !== null) {
                $stmt = $conn->prepare("SELECT hostel_id FROM hostels WHERE hostel_name=? AND gender=?");
                $stmt->bind_param('ss', $hostelName, $scopeGender);
            } else {
                $stmt = $conn->prepare("SELECT hostel_id FROM hostels WHERE hostel_name=?");
                $stmt->bind_param('s', $hostelName);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && mysqli_num_rows($res) > 0) {
                $row = mysqli_fetch_assoc($res);
                $hostelId = $row['hostel_id'];
            }
            $stmt->close();
        }
        // Build the base query with joins
        $baseQuery = "SELECT 
            r.room_id, 
            r.hostel_id, 
            r.room_number, 
            r.block, 
            r.floor, 
            r.room_type, 
            r.capacity, 
            r.occupied, 
            r.status, 
            h.hostel_name,
            GROUP_CONCAT(DISTINCT CONCAT(s.name,' (',s.roll_number,')') SEPARATOR '|') AS student_info,
            GROUP_CONCAT(DISTINCT s.department SEPARATOR '|') AS departments,
            GROUP_CONCAT(DISTINCT s.academic_batch SEPARATOR '|') AS academic_years,
            GROUP_CONCAT(DISTINCT s.name SEPARATOR '|') AS student_names,
            GROUP_CONCAT(DISTINCT CONCAT(hf.f_name, ' (', hf.designation, ')') SEPARATOR '|') AS faculty_info
        FROM rooms r
        LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
        LEFT JOIN room_students rs ON r.room_id = rs.room_id AND rs.is_active = 1
        LEFT JOIN students s ON rs.student_id = s.student_id
        LEFT JOIN hostel_faculty hf ON r.room_id = hf.room_id AND hf.status = 1";
        
        // Add WHERE conditions
        $whereConditions = [];
        $params = [];
        $types = '';
        
        if (!empty($hostelId)) {
            $whereConditions[] = "h.hostel_id = ?";
            $params[] = $hostelId;
            $types .= 'i';
        }

        if ($scopeGender !== null) {
            $whereConditions[] = "h.gender = ?";
            $params[] = $scopeGender;
            $types .= 's';
        }
        
        if (!empty($department)) {
            $whereConditions[] = "s.department = ?";
            $params[] = $department;
            $types .= 's';
        }
        
        if (!empty($year)) {
            $whereConditions[] = "s.academic_batch = ?";
            $params[] = $year;
            $types .= 's';
        }
        
        if (!empty($block)) {
            $whereConditions[] = "r.block = ?";
            $params[] = $block;
            $types .= 's';
        }
        
        if (!empty($floor)) {
            $whereConditions[] = "r.floor = ?";
            $params[] = $floor;
            $types .= 's';
        }
        
        if (!empty($roomType)) {
            $whereConditions[] = "r.room_type = ?";
            $params[] = $roomType;
            $types .= 's';
        }
        
        // Add user type filtering
        if (!empty($userType)) {
            switch($userType) {
                case 'student':
                    $whereConditions[] = "(s.student_id IS NOT NULL OR hf.room_id IS NULL)";
                    break;
                case 'faculty':
                    $whereConditions[] = "hf.room_id IS NOT NULL";
                    break;
                case 'both':
                    $whereConditions[] = "(s.student_id IS NOT NULL AND hf.room_id IS NOT NULL)";
                    break;
            }
        }
        
        // Complete the query
        $query = $baseQuery;
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $query .= " GROUP BY r.room_id ORDER BY h.hostel_name, r.room_number ASC";
        
        // Execute the query
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                die("Prepare failed: " . $conn->error);
            }
        } else {
            $result = mysqli_query($conn, $query);
        }
        // Create new PDF document with styling matching vacated students report
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Hostel Management System');
        $pdf->SetAuthor('Hostel Management System');
        $title = !empty($hostelName) ? $hostelName . ' - Room Details' : 'All Hostels - Room Details';
        $pdf->SetTitle($title);
        $pdf->SetSubject('Room Details Report');
        
        // Set margins
        $pdf->SetMargins(15, 35, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(15);
        $pdf->SetAutoPageBreak(TRUE, 20);
        $pdf->SetPrintHeader(false);
        
        // Add a page
        $pdf->AddPage();
        
        // Define colors
        $headerColor = array(0, 109, 109); // Dark teal
        $borderGray = array(180, 180, 180);
        
        // College header
        $basePath = dirname(__FILE__) . '/../images/';
        $leftLogoFiles = [
            ['path' => $basePath . 'mkce_logo2.jpg', 'type' => 'JPG'],
            ['path' => $basePath . 'mkce.png', 'type' => 'PNG'],
        ];
        
        // Try to add left logo
        foreach ($leftLogoFiles as $logo) {
            if (file_exists($logo['path'])) {
                $pdf->Image($logo['path'], 15, 10, 18, 18, $logo['type'], '', 'T', false, 300, '', false, false, 0, false, false, false);
                break;
            }
        }
        
        // Try to add right logo
        $rightLogoPath = $basePath . 'kr.jpg';
        if (file_exists($rightLogoPath)) {
            $pdf->Image($rightLogoPath, 177, 10, 18, 18, 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // College name and address
        $pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(15, 25);
$pdf->Cell(180, 6, 'M.Kumarasamy College of Engineering, Karur - 639 113', 0, 1, 'C');
$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(180, 5, '(An Autonomous Institution Affiliated to Anna University, Chennai)', 0, 1, 'C');                                                                      
        
        // Report title
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);
        $pdf->Cell(180, 8, $title, 0, 1, 'C');
        
        // Generated date and info
        $pdf->SetFont('helvetica', '', 9);
        $generatedDate = date('d/m/Y g:i A');
        $pdf->Cell(90, 6, 'Generated Date: ' . $generatedDate, 0, 0, 'L');
        $pdf->Cell(90, 6, 'Generated by: Admin', 0, 1, 'R');

        // Add a horizontal line after Generated Date
        $pdf->Line(15, $pdf->GetY() + 2, 195, $pdf->GetY() + 2);
        $pdf->Ln(4);
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor($borderGray[0], $borderGray[1], $borderGray[2]);
        $pdf->SetLineWidth(0.1);
        
        // Column widths - adjusted to give more space for student names
        $w = array(10, 25, 20, 15, 100, 15);
        
        // Header row
        $pdf->Cell($w[0], 8, 'S.No', 1, 0, 'C', 1);
        $pdf->Cell($w[1], 8, 'Hostel', 1, 0, 'C', 1);
        $pdf->Cell($w[2], 8, 'Room No', 1, 0, 'C', 1);
        $pdf->Cell($w[3], 8, 'Capacity', 1, 0, 'C', 1);
        $pdf->Cell($w[4], 8, 'Occupants', 1, 0, 'C', 1);
        $pdf->Cell($w[5], 8, 'Status', 1, 1, 'C', 1);
        
        // Data rows
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(242, 247, 247);

        $i = 1;
        $fill = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            // Build display for occupants (show only students or faculty, not both)
            $occupantInfo = '';
            if (!empty($row['student_info']) && !empty($row['faculty_info'])) {
                // Both students and faculty
                $occupantInfo = $row['student_info'] . '|' . $row['faculty_info'];
            } elseif (!empty($row['student_info'])) {
                // Only students
                $occupantInfo = $row['student_info'];
            } elseif (!empty($row['faculty_info'])) {
                // Only faculty
                $occupantInfo = $row['faculty_info'];
            } else {
                $occupantInfo = 'No occupants';
            }
            
            // Split occupant info into separate lines
            $occupants = explode('|', $occupantInfo);
            $occupantText = '';
            foreach ($occupants as $index => $occupant) {
                if ($index > 0) $occupantText .= "\n";
                $occupantText .= $occupant;
            }
            
            // Alternate row colors
            $bgColor = $fill ? array(242, 247, 247) : array(255, 255, 255);
            $pdf->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);
            
            // Calculate row height based on number of occupants
            $occupantCount = count($occupants);
            $rowHeight = max(7, $occupantCount * 5); // Minimum 7, then 5 per occupant line
            
            $pdf->Cell($w[0], $rowHeight, $i, 1, 0, 'C', 1);
            $pdf->Cell($w[1], $rowHeight, $row['hostel_name'], 1, 0, 'C', 1);
            $pdf->Cell($w[2], $rowHeight, $row['room_number'], 1, 0, 'C', 1);
            $pdf->Cell($w[3], $rowHeight, $row['capacity'], 1, 0, 'C', 1);
            // Display each occupant on a separate line
            $pdf->MultiCell($w[4], $rowHeight, $occupantText, 1, 'L', 1, 0);
            $pdf->Cell($w[5], $rowHeight, $row['status'], 1, 1, 'C', 1);
            
            $fill = !$fill;
            $i++;
        }
        
        // Page number in footer
        $pdf->SetY(-15);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, 0, 'C');
        
        $filename = !empty($hostelName) ? $hostelName . "_rooms.pdf" : "all_rooms.pdf";
        $pdf->Output($filename, 'D');
        exit;
        break;

    // ===================== XLS EXPORT =====================
    case (isset($_GET['download_xls']) && $_GET['download_xls'] == 1):
        if (!isset($conn) || !($conn instanceof mysqli)) {
            die("Database connection failed");
        }

        $hostelName = $_GET['hostel'] ?? '';
        $department = $_GET['department'] ?? '';
        $year = $_GET['year'] ?? '';
        $block = $_GET['block'] ?? '';
        $floor = $_GET['floor'] ?? '';
        $roomType = $_GET['room_type'] ?? '';
        $userType = $_GET['user_type'] ?? '';
        $scopeGender = get_hostel_gender_scope_for_role();
        
        $hostelId = null;
        if (!empty($hostelName)) {
            if ($scopeGender !== null) {
                $stmt = $conn->prepare("SELECT hostel_id FROM hostels WHERE hostel_name=? AND gender=?");
                $stmt->bind_param('ss', $hostelName, $scopeGender);
            } else {
                $stmt = $conn->prepare("SELECT hostel_id FROM hostels WHERE hostel_name=?");
                $stmt->bind_param('s', $hostelName);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && mysqli_num_rows($res) > 0) {
                $row = mysqli_fetch_assoc($res);
                $hostelId = $row['hostel_id'];
            }
            $stmt->close();
        }
        
        // Build the base query with joins
        $baseQuery = "SELECT r.room_id, r.hostel_id, r.room_number, r.block, r.floor, r.room_type, r.capacity, r.occupied, r.status, h.hostel_name,
                  GROUP_CONCAT(DISTINCT CONCAT(s.name,' (',s.roll_number,')') SEPARATOR '|') AS student_info,
                  GROUP_CONCAT(DISTINCT CONCAT(hf.f_name,' (Faculty)') SEPARATOR '|') AS faculty_info,
                  GROUP_CONCAT(DISTINCT s.department SEPARATOR '|') AS departments,
                  GROUP_CONCAT(DISTINCT s.academic_batch SEPARATOR '|') AS academic_years,
                  GROUP_CONCAT(DISTINCT s.name SEPARATOR '|') AS student_names,
                  GROUP_CONCAT(DISTINCT hf.f_name SEPARATOR '|') AS faculty_names
                  FROM rooms r
                  LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                  LEFT JOIN room_students rs ON r.room_id = rs.room_id AND rs.is_active = 1
                  LEFT JOIN students s ON rs.student_id = s.student_id
                  LEFT JOIN hostel_faculty hf ON r.room_id = hf.room_id AND hf.status = 1";
        
        // Add WHERE conditions
        $whereConditions = [];
        $params = [];
        $types = '';
        
        if (!empty($hostelId)) {
            $whereConditions[] = "h.hostel_id = ?";
            $params[] = $hostelId;
            $types .= 'i';
        }

        if ($scopeGender !== null) {
            $whereConditions[] = "h.gender = ?";
            $params[] = $scopeGender;
            $types .= 's';
        }
        
        if (!empty($department)) {
            $whereConditions[] = "s.department = ?";
            $params[] = $department;
            $types .= 's';
        }
        
        if (!empty($year)) {
            $whereConditions[] = "s.academic_batch = ?";
            $params[] = $year;
            $types .= 's';
        }
        
        if (!empty($block)) {
            $whereConditions[] = "r.block = ?";
            $params[] = $block;
            $types .= 's';
        }
        
        if (!empty($floor)) {
            $whereConditions[] = "r.floor = ?";
            $params[] = $floor;
            $types .= 's';
        }
        
        if (!empty($roomType)) {
            $whereConditions[] = "r.room_type = ?";
            $params[] = $roomType;
            $types .= 's';
        }
        
        // Add user type filtering
        if (!empty($userType)) {
            switch($userType) {
                case 'student':
                    $whereConditions[] = "s.student_id IS NOT NULL";
                    break;
                case 'faculty':
                    // For faculty filtering, we would need to join with faculty table
                    // This is a simplified approach - in a real implementation, you might need a different approach
                    break;
                case 'both':
                    // Both students and faculty should be present
                    $whereConditions[] = "s.student_id IS NOT NULL";
                    break;
            }
        }
        
        // Complete the query
        $query = $baseQuery;
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $query .= " GROUP BY r.room_id ORDER BY h.hostel_name, r.room_number ASC";
        
        // Execute the query
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                die("Prepare failed: " . $conn->error);
            }
        } else {
            $result = mysqli_query($conn, $query);
        }

        $filename = !empty($hostelName) ? $hostelName . "_rooms.xls" : "all_rooms.xls";
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header("Cache-Control: max-age=0");
        
        // Excel styling to match vacated students report
        echo '<table border="0" cellspacing="0" cellpadding="0" style="width:100%;">';
        echo '<tr><th colspan="6" style="background:#0aa2a1;color:#fff;font-size:14px;">M.KUMARASAMY COLLEGE OF ENGINEERING, KARUR - 639 113</th></tr>';
        echo '<tr><th colspan="6" style="background:#f2f2f2;color:#000;font-size:11px;">(An Autonomous Institution Affiliated to Anna University, Chennai)</th></tr>';
        
        $title = !empty($hostelName) ? $hostelName . ' - Room Details' : 'All Hostels - Room Details';
        $generatedDate = date('d/m/Y g:i A');
        echo "<tr><th colspan=\"6\" style=\"text-align:center;font-weight:bold;\">{$title} - {$generatedDate}</th></tr>";
        echo '</table><br/>';

        // Add a horizontal line after Generated Date
        echo '<table border="0" cellspacing="0" cellpadding="0" style="width:100%;"><tr><td style="border-bottom: 1px solid #000;">&nbsp;</td></tr></table><br/>';

        echo '<table border="1" cellspacing="0" cellpadding="4">';
        echo "<tr style='background-color: #006d6d; color: white; font-weight: bold;'>";
        echo "<th>S.No</th><th>Hostel</th><th>Room No</th><th>Capacity</th><th>Occupants</th><th>Status</th>";
        echo "</tr>";
        
        // Data rows
        $i = 1;
        $rowColor = false;
        while ($row = mysqli_fetch_assoc($result)) {
            // Build display for occupants (show only students or faculty, not both)
            $occupantInfo = '';
            if (!empty($row['student_info']) && !empty($row['faculty_info'])) {
                // Both students and faculty
                $occupantInfo = $row['student_info'] . '|' . $row['faculty_info'];
            } elseif (!empty($row['student_info'])) {
                // Only students
                $occupantInfo = $row['student_info'];
            } elseif (!empty($row['faculty_info'])) {
                // Only faculty
                $occupantInfo = $row['faculty_info'];
            } else {
                $occupantInfo = 'No occupants';
            }
            
            // Split occupant info into separate lines
            $occupants = explode('|', $occupantInfo);
            $occupantText = '';
            foreach ($occupants as $index => $occupant) {
                if ($index > 0) $occupantText .= "<br>";
                $occupantText .= htmlspecialchars($occupant);
            }
            
            // Alternate row colors
            $bgColor = $rowColor ? '#f2f7f7' : '#ffffff';
            echo "<tr style='background-color: {$bgColor}'>";
            echo "<td>{$i}</td>";
            echo "<td>{$row['hostel_name']}</td>";
            echo "<td>{$row['room_number']}</td>";
            echo "<td>{$row['capacity']}</td>";
            echo "<td>{$occupantText}</td>";
            echo "<td>{$row['status']}</td>";
            echo "</tr>";
            
            $rowColor = !$rowColor;
            $i++;
        }
        echo "</table>";
        exit;
        break;

    // ===================== VACATED STUDENTS PDF EXPORT =====================
    case (isset($_GET['download_vacated_pdf']) && $_GET['download_vacated_pdf'] == 1):
        if (!file_exists('../TCPDF/tcpdf.php')) {
            die('TCPDF library not found. Please download TCPDF and place it in TCPDF folder.');
        }
        require_once('../TCPDF/tcpdf.php');
        
        // Build query with filters
        $query = "SELECT vsh.*, s.gender
                  FROM vacated_students_history vsh
                  LEFT JOIN students s ON vsh.student_id = s.student_id";
        
        // Add filters if provided
        $filters = [];
        if (!empty($_GET['department'])) {
            $query .= " AND s.department = ?";
            $filters[] = $_GET['department'];
        }
        if (!empty($_GET['year'])) {
            $query .= " AND s.academic_batch = ?";
            $filters[] = $_GET['year'];
        }
        if (!empty($_GET['hostel'])) {
            $query .= " AND vsh.hostel_name = ?";
            $filters[] = $_GET['hostel'];
        }
        if (!empty($_GET['room'])) {
            $query .= " AND vsh.room_number = ?";
            $filters[] = $_GET['room'];
        }
        if (!empty($_GET['gender'])) {
            $query .= " AND s.gender = ?";
            $filters[] = $_GET['gender'];
        }
        
        $query .= " ORDER BY vsh.vacated_at DESC";
        
        if (!empty($filters)) {
            $stmt = $conn->prepare($query);
            if ($stmt) {
                // Create types string (all strings)
                $types = str_repeat('s', count($filters));
                $stmt->bind_param($types, ...$filters);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = mysqli_query($conn, $query);
            }
        } else {
            $result = mysqli_query($conn, $query);
        }
        
        // Create new PDF document with better styling
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Hostel Management System');
        $pdf->SetAuthor('Hostel Management System');
        $pdf->SetTitle('Vacated Students Report');
        $pdf->SetSubject('Vacated Students Report');
        
        // Set margins
        $pdf->SetMargins(15, 35, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(15);
        $pdf->SetAutoPageBreak(TRUE, 20);
        
        // Add a page
        $pdf->AddPage();
        
        // Define colors
        $headerColor = array(0, 109, 109); // Dark teal
        $borderGray = array(180, 180, 180);
        
        // College header
        $basePath = dirname(__FILE__) . '/../images/';
        $leftLogoFiles = [
            ['path' => $basePath . 'mkce_logo2.jpg', 'type' => 'JPG'],
            ['path' => $basePath . 'mkce.png', 'type' => 'PNG'],
        ];
        
        // Try to add left logo
        foreach ($leftLogoFiles as $logo) {
            if (file_exists($logo['path'])) {
                $pdf->Image($logo['path'], 15, 10, 18, 18, $logo['type'], '', 'T', false, 300, '', false, false, 0, false, false, false);
                break;
            }
        }
        
        // Try to add right logo
        $rightLogoPath = $basePath . 'kr.jpg';
        if (file_exists($rightLogoPath)) {
            $pdf->Image($rightLogoPath, 177, 10, 18, 18, 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // College name and address
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->Cell(180, 6, 'M.Kumarasamy College of Engineering, Karur - 639 113', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->Cell( 5, '(An Autonomous Institution Affiliated to Anna University, Chennai)', 0, 1, 'C');
        
        // Report title
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);
        $pdf->Cell(180, 8, 'Vacated Students Report', 0, 1, 'C');
        
        // Generated date and info
        $pdf->SetFont('helvetica', '', 9);
        $generatedDate = date('d/m/Y g:i A');
        $pdf->Cell(90, 6, 'Generated Date: ' . $generatedDate, 0, 0, 'L');
        $pdf->Cell(90, 6, 'Generated by: Admin', 0, 1, 'R');
        
        // Add a horizontal line
     
        $pdf->Ln(2);
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor($borderGray[0], $borderGray[1], $borderGray[2]);
        $pdf->SetLineWidth(0.1);
        
        // Column widths
        $w = array(10, 30, 20, 25, 15, 25, 20, 40);
        
        // Header row
        $pdf->Cell($w[0], 8, 'S.No', 1, 0, 'C', 1);
        $pdf->Cell($w[1], 8, 'Student Name', 1, 0, 'C', 1);
        $pdf->Cell($w[2], 8, 'Roll Number', 1, 0, 'C', 1);
        $pdf->Cell($w[3], 8, 'Department', 1, 0, 'C', 1);
        $pdf->Cell($w[4], 8, 'Gender', 1, 0, 'C', 1);
        $pdf->Cell($w[5], 8, 'Previous Hostel', 1, 0, 'C', 1);
        $pdf->Cell($w[6], 8, 'Previous Room', 1, 0, 'C', 1);
        $pdf->Cell($w[7], 8, 'Vacated At', 1, 1, 'C', 1);
        
        // Data rows
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(242, 247, 247);
        
        $i = 1;
        $fill = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            // Alternate row colors
            $bgColor = $fill ? array(242, 247, 247) : array(255, 255, 255);
            $pdf->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);
            
            $pdf->Cell($w[0], 7, $i, 1, 0, 'C', 1);
            $pdf->Cell($w[1], 7, $row['student_name'] ?? 'N/A', 1, 0, 'L', 1);
            $pdf->Cell($w[2], 7, $row['roll_number'] ?? 'N/A', 1, 0, 'L', 1);
            $pdf->Cell($w[3], 7, $row['department'] ?? 'N/A', 1, 0, 'L', 1);
            $pdf->Cell($w[4], 7, $row['gender'] ?? 'N/A', 1, 0, 'L', 1);
            $pdf->Cell($w[5], 7, $row['hostel_name'] ?? 'N/A', 1, 0, 'L', 1);
            $pdf->Cell($w[6], 7, $row['room_number'] ?? 'N/A', 1, 0, 'L', 1);
            $pdf->Cell($w[7], 7, $row['vacated_at'], 1, 1, 'L', 1);
            
            $fill = !$fill;
            $i++;
        }
        
        // Page number in footer
        $pdf->SetY(-15);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, 0, 'C');
        
        $filename = "vacated_students.pdf";
        $pdf->Output($filename, 'D');
        exit;
        break;

    // ===================== VACATED STUDENTS XLS EXPORT =====================
    case (isset($_GET['download_vacated_xls']) && $_GET['download_vacated_xls'] == 1):
        if (!isset($conn) || !($conn instanceof mysqli)) {
            die("Database connection failed");
        }
        
        // Build query with filters
        $query = "SELECT vsh.*, s.gender
                  FROM vacated_students_history vsh
                  LEFT JOIN students s ON vsh.student_id = s.student_id";
        
        // Add filters if provided
        $filters = [];
        if (!empty($_GET['department'])) {
            $query .= " AND s.department = ?";
            $filters[] = $_GET['department'];
        }
        if (!empty($_GET['year'])) {
            $query .= " AND s.academic_batch = ?";
            $filters[] = $_GET['year'];
        }
        if (!empty($_GET['hostel'])) {
            $query .= " AND vsh.hostel_name = ?";
            $filters[] = $_GET['hostel'];
        }
        if (!empty($_GET['room'])) {
            $query .= " AND vsh.room_number = ?";
            $filters[] = $_GET['room'];
        }
        if (!empty($_GET['gender'])) {
            $query .= " AND s.gender = ?";
            $filters[] = $_GET['gender'];
        }
        
        $query .= " ORDER BY vsh.vacated_at DESC";
        
        if (!empty($filters)) {
            $stmt = $conn->prepare($query);
            if ($stmt) {
                // Create types string (all strings)
                $types = str_repeat('s', count($filters));
                $stmt->bind_param($types, ...$filters);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = mysqli_query($conn, $query);
            }
        } else {
            $result = mysqli_query($conn, $query);
        }
        
        $filename = "vacated_students.xls";
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header("Cache-Control: max-age=0");
        
        // Excel styling to match vacated students report
        echo '<table border="0" cellspacing="0" cellpadding="0" style="width:100%;">';
        echo '<tr><th colspan="6" style="background:#0aa2a1;color:#fff;font-size:14px;">M.KUMARASAMY COLLEGE OF ENGINEERING, KARUR - 639 113</th></tr>';
        echo '<tr><th colspan="6" style="background:#f2f2f2;color:#000;font-size:11px;">(An Autonomous Institution Affiliated to Anna University, Chennai)</th></tr>';

        $title = !empty($hostelName) ? $hostelName . ' - Room Details' : 'All Hostels - Room Details';
        $generatedDate = date('d/m/Y g:i A');
        echo "<tr><th colspan=\"6\" style=\"text-align:center;font-weight:bold;\">{$title} - {$generatedDate}</th></tr>";
        echo '</table><br/>';
        
        echo '<table border="1" cellspacing="0" cellpadding="4">';
        echo "<tr style='background-color: #006d6d; color: white; font-weight: bold;'>";
        echo "<th>S.No</th><th>Student Name</th><th>Roll Number</th><th>Department</th><th>Gender</th><th>Previous Hostel</th><th>Previous Room</th><th>Vacated At</th>";
        echo "</tr>";
        
        $i = 1;
        $rowColor = false;
        while ($row = mysqli_fetch_assoc($result)) {
            // Alternate row colors
            $bgColor = $rowColor ? '#f2f7f7' : '#ffffff';
            echo "<tr style='background-color: {$bgColor}'>";
            echo "<td>{$i}</td>";
            echo "<td>".($row['student_name'] ?? 'N/A')."</td>";
            echo "<td>".($row['roll_number'] ?? 'N/A')."</td>";
            echo "<td>".($row['department'] ?? 'N/A')."</td>";
            echo "<td>".($row['gender'] ?? 'N/A')."</td>";
            echo "<td>".($row['hostel_name'] ?? 'N/A')."</td>";
            echo "<td>".($row['room_number'] ?? 'N/A')."</td>";
            echo "<td>{$row['vacated_at']}</td>";
            echo "</tr>";
            
            $rowColor = !$rowColor;
            $i++;
        }
        echo "</table>";
        exit;
        break;

    default:
        // No export requested; do nothing (preserve original behavior)
        break;
}

// ===================== MAIN BACKEND LOGIC =====================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        // ---------- Add Room ----------
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
            if (!$stmt) jsonResponse(['success' => false, 'error' => $conn->error]);
            
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
            if (!$stmt) jsonResponse(['success' => false, 'error' => $conn->error]);
            
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
            if (!$room_id) jsonResponse(['success' => false, 'error' => 'Invalid room id']);

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
            if (!$s1) jsonResponse(['success'=>false,'error'=>$conn->error]);
            $s1->bind_param('i', $room_id);
            $s1->execute();
            $s1->close();

            // Delete room
            $sql2 = "DELETE FROM rooms WHERE room_id = ?";
            $s2 = $conn->prepare($sql2);
            if (!$s2) jsonResponse(['success'=>false,'error'=>$conn->error]);
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
                if (!$s) jsonResponse(['success'=>false,'error'=>$conn->error]);
                $s->bind_param('i', $room_id);
                $s->execute();
                $res = $s->get_result();
                while ($r = $res->fetch_assoc()) $out[] = $r;
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
                while ($r = $res->fetch_assoc()) $out[] = $r;
                $stmt->close();
            } else {
                jsonResponse(['success'=>false,'error'=>$conn->error]);
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
                
                if (($hostelGender == 'Male' && $studentGender != 'Male') || 
                    ($hostelGender == 'Female' && $studentGender != 'Female')) {
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
                                (room_id, student_id, room_number, roll_number, hostel_id, hostel_name, assigned_at, is_active) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param('iisssss', $room_id, $student_id, 
                    $roomDetails['room_number'], $studentRollData['roll_number'], 
                    $roomDetails['hostel_id'], $roomDetails['hostel_name'], 
                    $now);
                
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
                               LEFT JOIN room_students rs ON r.room_id = rs.room_id AND rs.is_active = 1
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
            if (!$rs_id) jsonResponse(['success' => false, 'error' => 'Invalid id']);

            $now = date('Y-m-d H:i:s');
            $s0 = $conn->prepare("SELECT room_id, student_id FROM room_students WHERE id = ?");
            $s0->bind_param('i', $rs_id);
            $s0->execute();
            $res0 = $s0->get_result();
            $rs = $res0->fetch_assoc();
            $s0->close();
            if (!$rs) jsonResponse(['success' => false, 'error' => 'Not found']);

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
                jsonResponse(['success'=>false,'error'=>'Missing fields']);
            }

            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Get destination room info
                $destQuery = "SELECT r.capacity, r.occupied, r.room_number, r.hostel_id, h.hostel_name, h.gender 
                             FROM rooms r 
                             LEFT JOIN hostels h ON r.hostel_id = h.hostel_id 
                             WHERE r.room_id = ?";
                $destStmt = $conn->prepare($destQuery);
                if (!$destStmt) {
                    throw new Exception('Prepare destination room query failed: ' . $conn->error);
                }
                $destStmt->bind_param('i', $to_room);
                $destStmt->execute();
                $destRes = $destStmt->get_result();
                $dest = $destRes->fetch_assoc();
                $destStmt->close();
                
                if (!$dest) {
                    throw new Exception('Destination room not found');
                }
                if (intval($dest['occupied']) >= intval($dest['capacity'])) {
                    throw new Exception('Destination room is full');
                }

                // Get student details
                $sStmt = $conn->prepare("SELECT roll_number, gender FROM students WHERE student_id = ?");
                if (!$sStmt) {
                    throw new Exception('Prepare student query failed: ' . $conn->error);
                }
                $sStmt->bind_param('i', $student_id);
                $sStmt->execute();
                $sRes = $sStmt->get_result();
                $sData = $sRes->fetch_assoc();
                $sStmt->close();

                // Check gender compatibility
                if (($dest['gender'] == 'Male' && $sData['gender'] != 'Male') || 
                    ($dest['gender'] == 'Female' && $sData['gender'] != 'Female')) {
                    throw new Exception('Gender mismatch: Cannot transfer student to this hostel');
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
                if (!$detailsStmt) {
                    throw new Exception('Prepare details query failed: ' . $conn->error);
                }
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
                if (!$vacateStmt) {
                    throw new Exception('Prepare vacate query failed: ' . $conn->error);
                }
                $vacateStmt->bind_param('sii', $now, $from_room, $student_id);
                $vacateStmt->execute();
                
                // Don't save transfer details to vacated_students_history table
                // Only actual vacates (not transfers) should be recorded in the history
                
                $vacateStmt->close();

                // Add new assignment with all columns
                // Get room details for the destination room
                $roomDetailsQuery = "SELECT r.room_number, r.hostel_id, h.hostel_name 
                                    FROM rooms r 
                                    LEFT JOIN hostels h ON r.hostel_id = h.hostel_id 
                                    WHERE r.room_id = ?";
                $roomDetailsStmt = $conn->prepare($roomDetailsQuery);
                if (!$roomDetailsStmt) {
                    throw new Exception('Prepare room details query failed: ' . $conn->error);
                }
                $roomDetailsStmt->bind_param('i', $to_room);
                $roomDetailsStmt->execute();
                $roomDetailsResult = $roomDetailsStmt->get_result();
                $roomDetails = $roomDetailsResult->fetch_assoc();
                $roomDetailsStmt->close();
                
                // Get student roll number and name
                $studentRollQuery = "SELECT roll_number, name FROM students WHERE student_id = ?";
                $studentRollStmt = $conn->prepare($studentRollQuery);
                if (!$studentRollStmt) {
                    throw new Exception('Prepare student roll query failed: ' . $conn->error);
                }
                $studentRollStmt->bind_param('i', $student_id);
                $studentRollStmt->execute();
                $studentRollResult = $studentRollStmt->get_result();
                $studentRollData = $studentRollResult->fetch_assoc();
                $studentRollStmt->close();
                
                $assignQuery = "INSERT INTO room_students 
                               (room_id, student_id, room_number, roll_number, hostel_id, hostel_name, assigned_at, is_active) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
                $assignStmt = $conn->prepare($assignQuery);
                if (!$assignStmt) {
                    throw new Exception('Prepare assign query failed: ' . $conn->error);
                }
                $assignStmt->bind_param(
                    'iisssss',
                    $to_room,
                    $student_id,
                    $roomDetails['room_number'],
                    $studentRollData['roll_number'],
                    $roomDetails['hostel_id'],
                    $roomDetails['hostel_name'],
                    $now
                );
                if (!$assignStmt->execute()) {
                    throw new Exception('Insert into room_students failed: ' . $assignStmt->error);
                }
                $assignStmt->close();

                // Update student table
                $updateStudentQuery = "UPDATE students SET room_id = ?, hostel_id = ? WHERE student_id = ?";
                $updateStudentStmt = $conn->prepare($updateStudentQuery);
                if (!$updateStudentStmt) {
                    throw new Exception('Prepare update student query failed: ' . $conn->error);
                }
                $updateStudentStmt->bind_param('iii', $to_room, $roomDetails['hostel_id'], $student_id); 
                if (!$updateStudentStmt->execute()) {
                    throw new Exception('students UPDATE failed: ' . $updateStudentStmt->error);
                }
                $updateStudentStmt->close();

                // Update room counts by recalculating actual occupancy
                $countFromQuery = "SELECT COUNT(*) as count FROM room_students WHERE room_id = ? AND is_active = 1";
                $countFromStmt = $conn->prepare($countFromQuery);
                if (!$countFromStmt) {
                    throw new Exception('Prepare count from query failed: ' . $conn->error);
                }
                $countFromStmt->bind_param('i', $from_room);
                $countFromStmt->execute();
                $countFromRes = $countFromStmt->get_result();
                $fromOccupancy = $countFromRes->fetch_assoc()['count'];
                $countFromStmt->close();
                
                $updateFromQuery = "UPDATE rooms SET occupied = ? WHERE room_id = ?";
                $updateFromStmt = $conn->prepare($updateFromQuery);
                if (!$updateFromStmt) {
                    throw new Exception('Prepare update from query failed: ' . $conn->error);
                }
                $updateFromStmt->bind_param('ii', $fromOccupancy, $from_room);
                $updateFromStmt->execute();
                $updateFromStmt->close();
                
                $countToQuery = "SELECT COUNT(*) as count FROM room_students WHERE room_id = ? AND is_active = 1";
                $countToStmt = $conn->prepare($countToQuery);
                if (!$countToStmt) {
                    throw new Exception('Prepare count to query failed: ' . $conn->error);
                }
                $countToStmt->bind_param('i', $to_room);
                $countToStmt->execute();
                $countToRes = $countToStmt->get_result();
                $toOccupancy = $countToRes->fetch_assoc()['count'];
                $countToStmt->close();
                
                $updateToQuery = "UPDATE rooms SET occupied = ? WHERE room_id = ?";
                $updateToStmt = $conn->prepare($updateToQuery);
                if (!$updateToStmt) {
                    throw new Exception('Prepare update to query failed: ' . $conn->error);
                }
                $updateToStmt->bind_param('ii', $toOccupancy, $to_room);
                $updateToStmt->execute();
                $updateToStmt->close();

                // Get updated room data for both rooms
                $roomDataQuery = "SELECT r.room_id, r.hostel_id, r.room_number, r.block, r.floor, r.room_type, r.capacity, r.occupied, r.status, h.hostel_name,
                                 GROUP_CONCAT(DISTINCT CONCAT(s.name,' (',s.roll_number,')') SEPARATOR '<br>') AS student_info
                                 FROM rooms r
                                 LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
                                 LEFT JOIN room_students rs ON r.room_id = rs.room_id AND rs.is_active = 1
                                 LEFT JOIN students s ON rs.student_id = s.student_id
                                 WHERE r.room_id = ?
                                 GROUP BY r.room_id";

                $fromStmt = $conn->prepare($roomDataQuery);
                if (!$fromStmt) {
                    throw new Exception('Prepare from room data query failed: ' . $conn->error);
                }
                $fromStmt->bind_param('i', $from_room);
                $fromStmt->execute();
                $fromRes = $fromStmt->get_result();
                $from_updated = $fromRes->fetch_assoc();
                $fromStmt->close();

                $toStmt = $conn->prepare($roomDataQuery);
                if (!$toStmt) {
                    throw new Exception('Prepare to room data query failed: ' . $conn->error);
                }
                $toStmt->bind_param('i', $to_room);
                $toStmt->execute();
                $toRes = $toStmt->get_result();
                $to_updated = $toRes->fetch_assoc();
                $toStmt->close();

                // Commit transaction
                $conn->commit();

                jsonResponse([
                    'success'=>true,
                    'updated_from'=>$from_updated,
                    'updated_to'=>$to_updated
                ]);
            } catch (Exception $e) {
                // Rollback transaction on any failure
                $conn->rollback();
                
                // Log the detailed error to a file (optional but recommended)
                error_log("Transfer Student Error: " . $e->getMessage());
                
                // Return the specific error to the client for better debugging
                jsonResponse(['success' => false, 'error' => 'Failed to transfer student: ' . $e->getMessage()]);
            }
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
                                          (room_id, student_id, room_number, roll_number, assigned_at, vacated_at, hostel_id, hostel_name, department, academic_batch) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $insertHistoryStmt = $conn->prepare($insertHistoryQuery);
                    $insertHistoryStmt->bind_param('iissssisss', 
                        $studentDetails['room_id'],
                        $studentDetails['student_id'],
                        $studentDetails['room_number'],
                        $studentDetails['roll_number'],
                        $studentDetails['assigned_at'],
                        $now,
                        $studentDetails['hostel_id'],
                        $studentDetails['hostel_name'],
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
                               (room_id, student_id, room_number, roll_number, hostel_id, hostel_name, assigned_at, is_active) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
                $assignStmt = $conn->prepare($assignQuery);
                
                foreach ($students as $student) {
                    // Get student details
                    $studentDetailsQuery = "SELECT roll_number FROM students WHERE student_id = ?";
                    $studentDetailsStmt = $conn->prepare($studentDetailsQuery);
                    $studentDetailsStmt->bind_param('i', $student['student_id']);
                    $studentDetailsStmt->execute();
                    $studentDetailsResult = $studentDetailsStmt->get_result();
                    $studentDetails = $studentDetailsResult->fetch_assoc();
                    $studentDetailsStmt->close();
                    
                    $assignStmt->bind_param(
                        'iisssss',
                        $to_room_id,
                        $student['student_id'],
                        $destRoomDetails['room_number'],
                        $studentDetails['roll_number'],
                        $destRoomDetails['hostel_id'],
                        $destRoomDetails['hostel_name'],
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
                                 LEFT JOIN room_students rs ON r.room_id = rs.room_id AND rs.is_active = 1
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
                jsonResponse(['success'=>false,'error'=>'Invalid students']);
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
                jsonResponse(['success'=>false,'error'=>'One or both students are not assigned to rooms']);
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
            if (($roomAData['gender'] == 'Male' && $studentAData['gender'] != 'Male') || 
                ($roomAData['gender'] == 'Female' && $studentAData['gender'] != 'Female')) {
                jsonResponse(['success' => false, 'error' => 'Gender mismatch: Cannot swap student A to room B']);
            }

            if (($roomBData['gender'] == 'Male' && $studentBData['gender'] != 'Male') || 
                ($roomBData['gender'] == 'Female' && $studentBData['gender'] != 'Female')) {
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
                                              (room_id, student_id, room_number, roll_number, assigned_at, vacated_at, hostel_id, hostel_name, department, academic_batch) 
                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $insertHistoryStmt = $conn->prepare($insertHistoryQuery);
                        $insertHistoryStmt->bind_param('iissssisss', 
                            $details['room_id'],
                            $details['student_id'],
                            $details['room_number'],
                            $details['roll_number'],
                            $details['assigned_at'],
                            $now,
                            $details['hostel_id'],
                            $details['hostel_name'],
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
                                (room_id, student_id, room_number, roll_number, hostel_id, hostel_name, assigned_at, is_active) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $swapAStmt = $conn->prepare($insertSwapAQuery);
            $swapAStmt->bind_param(
                'iisssss',
                $rb['room_id'],
                $student_a,
                $roomADetails['room_number'],
                $studentADetails['roll_number'],
                $roomADetails['hostel_id'],
                $roomADetails['hostel_name'],
                $now
            );
            $swapAStmt->execute();
            $swapAStmt->close();

            // Insert Student B to Room A with all required fields
            $insertSwapBQuery = "INSERT INTO room_students 
                                (room_id, student_id, room_number, roll_number, hostel_id, hostel_name, assigned_at, is_active) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $swapBStmt = $conn->prepare($insertSwapBQuery);
            $swapBStmt->bind_param(
                'iisssss',
                $ra['room_id'],
                $student_b,
                $roomBDetails['room_number'],
                $studentBDetails['roll_number'],
                $roomBDetails['hostel_id'],
                $roomBDetails['hostel_name'],
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
                             LEFT JOIN room_students rs ON r.room_id = rs.room_id AND rs.is_active = 1
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

            jsonResponse(['success'=>true, 'updated_rooms'=>$rooms_changed]);
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
                while ($r = $res->fetch_assoc()) $out[] = $r;
                if (isset($stmt)) $stmt->close();
                $res->free();
                jsonResponse(['success'=>true, 'data'=>$out]);
            } else {
                if (isset($stmt)) $stmt->close();
                jsonResponse(['success'=>false, 'error'=>$conn->error]);
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
                                                  (room_id, student_id, student_name, room_number, roll_number, assigned_at, vacated_at, hostel_id, hostel_name, department, academic_batch) 
                                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $insertHistoryStmt = $conn->prepare($insertHistoryQuery);
                            $insertHistoryStmt->bind_param('iisssssisss', 
                                $studentDetails['room_id'],
                                $studentDetails['student_id'],
                                $studentDetails['student_name'],
                                $studentDetails['room_number'],
                                $studentDetails['roll_number'],
                                $studentDetails['assigned_at'],
                                $now,
                                $studentDetails['hostel_id'],
                                $studentDetails['hostel_name'],
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
                                 LEFT JOIN room_students rs ON r.room_id = rs.room_id AND rs.is_active = 1
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
        // ---------- Default case ----------
        default:
            jsonResponse(['success'=>false, 'error'=>'Unknown action']);
            break;
    }
}

jsonResponse(['success'=>false, 'error'=>'No action specified']);
?>