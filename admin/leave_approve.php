<?php 
session_start();
include '../db.php';
include './admin_scope.php';

if (!is_any_admin_role()) {
    header('Location: ../login');
    exit;
}

$scopeGender = get_hostel_gender_scope_for_role();
$scopeGenderEsc = $scopeGender !== null ? $conn->real_escape_string($scopeGender) : null;

// Handle AJAX requests for dynamic data loading
$ajaxAction = $_GET['ajax'] ?? $_POST['ajax'] ?? '';

if ($ajaxAction) {
    header('Content-Type: application/json');
    
    // Get Hostels List
    $hostelAction = $_GET['ajax'] ?? $_POST['ajax'] ?? '';
    if ($hostelAction === 'getHostels') {
        $sql = "SELECT hostel_id, hostel_name, gender FROM hostels";
        if ($scopeGender !== null) {
            $sql .= " WHERE gender = '" . $scopeGenderEsc . "'";
        }
        $sql .= " ORDER BY gender, hostel_name";
        $result = mysqli_query($conn, $sql);
        $hostels = [];
        while($row = mysqli_fetch_assoc($result)) {
            $hostels[] = [
                'hostel_id' => $row['hostel_id'],
                'hostel_name' => $row['hostel_name'],
                'gender' => $row['gender']
            ];
        }
        echo json_encode(['success' => true, 'data' => $hostels]);
        exit;
    }
    
    // Helper function for leave type badge
    function renderLeaveTypeBadge($type) {
        $raw = (string)$type;
        $v = strtolower(trim($raw));
        $vcompact = preg_replace('/\s+/', '', $v);
        $cls = 'lv-muted';
        if (strpos($v, 'emerg') !== false)        { $cls = 'lv-danger'; }
        elseif (strpos($v, 'leave') !== false)    { $cls = 'lv-info'; }
        elseif (strpos($v, 'On') !== false)   { $cls = 'lv-primary'; }
        elseif (strpos($v, 'Permission') !== false || $vcompact === 'od') { $cls = 'lv-success'; }
        elseif (strpos($v, 'Project') !== false)      { $cls = 'lv-warning'; }
        $label = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
        return "<span class='lv-badge $cls'>$label</span>";
    }
    
    switch ($ajaxAction) {
        case 'getPendingStats':
            // Get counts for each leave type (pending status only)
            $hostelId = intval($_GET['hostelId'] ?? 0);
            
            $sql = "SELECT lt.Leave_Type_Name, lt.LeaveType_ID, COUNT(la.Leave_ID) as count 
                    FROM leave_types lt
                    LEFT JOIN leave_applications la ON lt.LeaveType_ID = la.LeaveType_ID 
                        AND la.Status IN ('Pending', 'Forwarded to Admin')
                    LEFT JOIN students s ON la.Reg_No = s.roll_number
                    LEFT JOIN rooms r ON s.room_id = r.room_id";
            
            if ($hostelId > 0) {
                $sql .= " WHERE r.hostel_id = $hostelId";
            } else {
                $sql .= " WHERE 1=1";
            }

            if ($scopeGender !== null) {
                $sql .= " AND s.gender = '" . $scopeGenderEsc . "'";
            }
            
            $sql .= " AND lt.LeaveType_ID <> 1
                    GROUP BY lt.LeaveType_ID, lt.Leave_Type_Name
                    ORDER BY lt.LeaveType_ID";
            $result = mysqli_query($conn, $sql);
            
            // Get total pending count
            $totalSql = "SELECT COUNT(*) as total FROM leave_applications la
                         LEFT JOIN students s ON la.Reg_No = s.roll_number
                         LEFT JOIN rooms r ON s.room_id = r.room_id
                         WHERE la.Status IN ('Pending', 'Forwarded to Admin') 
                         AND la.LeaveType_ID <> 1";
            
            if ($hostelId > 0) {
                $totalSql .= " AND r.hostel_id = $hostelId";
            }

            if ($scopeGender !== null) {
                $totalSql .= " AND s.gender = '" . $scopeGenderEsc . "'";
            }
            
            $totalResult = mysqli_query($conn, $totalSql);
            $totalRow = mysqli_fetch_assoc($totalResult);
            $totalCount = $totalRow['total'];
            
            $leaveTypes = [];
            while($row = mysqli_fetch_assoc($result)) {
                $leaveTypes[] = [
                    'name' => $row['Leave_Type_Name'],
                    'count' => (int)$row['count']
                ];
            }
            
            echo json_encode(['success' => true, 'total' => (int)$totalCount, 'leaveTypes' => $leaveTypes]);
            exit;
            
        case 'getProcessedStats':
            // Get hostel filter
            $hostelId = intval($_GET['hostelId'] ?? 0);
            
            // Build hostel join condition
            $hostelJoin = "LEFT JOIN students s ON la.Reg_No = s.roll_number
                          LEFT JOIN rooms r ON s.room_id = r.room_id";
            $hostelWhere = $hostelId > 0 ? " AND r.hostel_id = $hostelId" : "";
            $scopeWhere = $scopeGender !== null ? " AND s.gender = '" . $scopeGenderEsc . "'" : "";
            
            // Get total processed count
            $totalSql = "SELECT COUNT(*) as total FROM leave_applications la
                         $hostelJoin
                         WHERE la.Status IN ('Rejected by HOD','Rejected by Admin','Rejected by Parents','Approved')
                         $hostelWhere
                         $scopeWhere";
            $totalResult = mysqli_query($conn, $totalSql);
            $totalRow = mysqli_fetch_assoc($totalResult);
            $totalCount = $totalRow['total'];
            
            // Get approved count by leave type
            $approvedSql = "SELECT lt.Leave_Type_Name, lt.LeaveType_ID, COUNT(la.Leave_ID) as count 
                            FROM leave_types lt
                            LEFT JOIN leave_applications la ON lt.LeaveType_ID = la.LeaveType_ID 
                                AND la.Status = 'Approved'
                            $hostelJoin
                            WHERE 1=1 $hostelWhere $scopeWhere
                            GROUP BY lt.LeaveType_ID, lt.Leave_Type_Name
                            ORDER BY lt.LeaveType_ID";
            $approvedResult = mysqli_query($conn, $approvedSql);
            
            // Get rejected count by leave type
            $rejectedSql = "SELECT lt.Leave_Type_Name, lt.LeaveType_ID, COUNT(la.Leave_ID) as count 
                            FROM leave_types lt
                            LEFT JOIN leave_applications la ON lt.LeaveType_ID = la.LeaveType_ID 
                                AND la.Status IN ('Rejected by HOD','Rejected by Admin','Rejected by Parents')
                            $hostelJoin
                            WHERE 1=1 $hostelWhere $scopeWhere
                            GROUP BY lt.LeaveType_ID, lt.Leave_Type_Name
                            ORDER BY lt.LeaveType_ID";
            $rejectedResult = mysqli_query($conn, $rejectedSql);
            
            // Get total approved and rejected counts
            $totalApprovedSql = "SELECT COUNT(*) as total FROM leave_applications la
                                $hostelJoin
                                WHERE la.Status = 'Approved'
                                $hostelWhere
                                $scopeWhere";
            $totalApprovedResult = mysqli_query($conn, $totalApprovedSql);
            $totalApprovedRow = mysqli_fetch_assoc($totalApprovedResult);
            $totalApprovedCount = $totalApprovedRow['total'];
            
            $totalRejectedSql = "SELECT COUNT(*) as total FROM leave_applications la
                                $hostelJoin
                                WHERE la.Status IN ('Rejected by HOD','Rejected by Admin','Rejected by Parents')
                                $hostelWhere
                                $scopeWhere";
            $totalRejectedResult = mysqli_query($conn, $totalRejectedSql);
            $totalRejectedRow = mysqli_fetch_assoc($totalRejectedResult);
            $totalRejectedCount = $totalRejectedRow['total'];
            
            $approvedCounts = [];
            $rejectedCounts = [];
            
            while($row = mysqli_fetch_assoc($approvedResult)) {
                $approvedCounts[$row['Leave_Type_Name']] = (int)$row['count'];
            }
            
            while($row = mysqli_fetch_assoc($rejectedResult)) {
                $rejectedCounts[$row['Leave_Type_Name']] = (int)$row['count'];
            }
            
            echo json_encode([
                'success' => true,
                'total' => (int)$totalCount,
                'totalApproved' => (int)$totalApprovedCount,
                'totalRejected' => (int)$totalRejectedCount,
                'approvedCounts' => $approvedCounts,
                'rejectedCounts' => $rejectedCounts
            ]);
            exit;
            
        case 'getPendingTable':
            $hostelId = intval($_GET['hostelId'] ?? 0);
            
            $sql = "SELECT la.*, s.name AS student_name, lt.Leave_Type_Name, r.room_number 
                    FROM leave_applications la
                    JOIN students s ON la.Reg_No = s.roll_number
                    LEFT JOIN rooms r ON s.room_id = r.room_id
                    JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
                    WHERE la.Status IN ('Pending', 'Forwarded to Admin')";
            
            if ($hostelId > 0) {
                $sql .= " AND r.hostel_id = $hostelId";
            }

            if ($scopeGender !== null) {
                $sql .= " AND s.gender = '" . $scopeGenderEsc . "'";
            }
            
            $sql .= " ORDER BY la.Applied_Date DESC";
            $result = mysqli_query($conn, $sql);
            
            $rows = [];
            while($row = mysqli_fetch_assoc($result)) {
                $room = isset($row['room_number']) && $row['room_number'] !== '' ? $row['room_number'] : '-';
                $appliedDate = date('d-m-Y h:i A', strtotime($row['Applied_Date']));
                $fromDate = date('d-m-Y h:i A', strtotime($row['From_Date']));
                $toDate = date('d-m-Y h:i A', strtotime($row['To_Date']));
                
                $rows[] = [
                    'Leave_ID' => $row['Leave_ID'],
                    'Reg_No' => $row['Reg_No'],
                    'student_name' => $row['student_name'],
                    'room_number' => $room,
                    'Leave_Type_Name' => $row['Leave_Type_Name'],
                    'Leave_Type_Badge' => renderLeaveTypeBadge($row['Leave_Type_Name']),
                    'Applied_Date' => $appliedDate,
                    'From_Date' => $fromDate,
                    'From_Date_Raw' => $row['From_Date'],
                    'To_Date' => $toDate,
                    'To_Date_Raw' => $row['To_Date'],
                    'Reason' => $row['Reason'],
                    'Proof' => $row['Proof'] ?? '',
                    'Status' => $row['Status']
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $rows]);
            exit;
            
        case 'getProcessedTable':
            $hostelId = intval($_GET['hostelId'] ?? 0);
            
            $sql = "SELECT la.*, 
                           s.name AS student_name, 
                           lt.Leave_Type_Name, 
                           r.room_number,
                           (
                               SELECT ic.call_status
                               FROM ivr_calls ic
                               WHERE ic.leave_id = la.Leave_ID
                               ORDER BY ic.id DESC
                               LIMIT 1
                           ) AS ivr_call_status
                    FROM leave_applications la
                    JOIN students s ON la.Reg_No = s.roll_number
                    LEFT JOIN rooms r ON s.room_id = r.room_id
                    JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
                    WHERE la.Status IN ('Rejected by HOD','Rejected by Admin','Rejected by Parents','Approved','out','closed','late entry','IVR Pending')";
            
            if ($hostelId > 0) {
                $sql .= " AND r.hostel_id = $hostelId";
            }

            if ($scopeGender !== null) {
                $sql .= " AND s.gender = '" . $scopeGenderEsc . "'";
            }
            
            $sql .= " ORDER BY la.Leave_ID DESC";
            $result = mysqli_query($conn, $sql);
            
            $rows = [];
            while($row = mysqli_fetch_assoc($result)) {
                $room = isset($row['room_number']) && $row['room_number'] !== '' ? $row['room_number'] : '-';
                $appliedDate = date('d-m-Y h:i A', strtotime($row['Applied_Date']));
                $fromDate = date('d-m-Y h:i A', strtotime($row['From_Date']));
                $toDate = date('d-m-Y h:i A', strtotime($row['To_Date']));
                $isEditable = ($row['Status'] == 'Approved' || $row['Status'] == 'out');
                
                $rows[] = [
                    'Leave_ID' => $row['Leave_ID'],
                    'Reg_No' => $row['Reg_No'],
                    'student_name' => $row['student_name'],
                    'room_number' => $room,
                    'Leave_Type_Name' => $row['Leave_Type_Name'],
                    'Leave_Type_Badge' => renderLeaveTypeBadge($row['Leave_Type_Name']),
                    'Applied_Date' => $appliedDate,
                    'From_Date' => $fromDate,
                    'From_Date_Raw' => $row['From_Date'],
                    'To_Date' => $toDate,
                    'To_Date_Raw' => $row['To_Date'],
                    'Reason' => $row['Reason'],
                    'Proof' => $row['Proof'] ?? '',
                    'Status' => $row['Status'],
                    'Remarks' => $row['Remarks'] ?? '',
                    'isEditable' => $isEditable,
                    'ivr_call_status' => $row['ivr_call_status'] ?? null
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $rows]);
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <?php 
    // Handle PDF/Excel export
    $action = strtolower($_GET['action'] ?? '');
    
    if (in_array($action, ['pending_pdf', 'pending_excel', 'approved_pdf', 'approved_excel'])) {
        // Fetch data functions
        function fetch_pending(mysqli $conn): array {
                        $hostelId = intval($_GET['hostelId'] ?? 0);
                        
                        $sql = "SELECT la.*, s.name AS student_name, lt.Leave_Type_Name, r.room_number
                                        FROM leave_applications la
                                        JOIN students s ON la.Reg_No = s.roll_number
                                        LEFT JOIN rooms r ON s.room_id = r.room_id
                                        JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
                                        WHERE la.Status IN ('Pending', 'Forwarded to Admin')
                                            AND la.LeaveType_ID <> 1";
                        
                        if ($hostelId > 0) {
                                $sql .= " AND r.hostel_id = $hostelId";
                        }

                        if ($scopeGender !== null) {
                            $sql .= " AND s.gender = '" . $scopeGenderEsc . "'";
                        }

                        // Apply global search filter (q) when provided from DataTables
                        $q = trim((string)($_GET['q'] ?? ''));
                        if ($q !== '') {
                                $qEsc = $conn->real_escape_string($q);
                                $sql .= " AND (la.Reg_No LIKE '%$qEsc%' OR s.name LIKE '%$qEsc%' OR r.room_number LIKE '%$qEsc%' OR lt.Leave_Type_Name LIKE '%$qEsc%' OR la.Reason LIKE '%$qEsc%' OR la.Status LIKE '%$qEsc%')";
                        }

                        $sql .= " ORDER BY la.Applied_Date DESC";
            $rows = [];
            if ($res = $conn->query($sql)) {
                while ($row = $res->fetch_assoc()) { $rows[] = $row; }
            }
            return $rows;
        }
        
        function fetch_approved(mysqli $conn): array {
            $hostelId = intval($_GET['hostelId'] ?? 0);
            
            $sql = "SELECT la.*, s.name AS student_name, lt.Leave_Type_Name, r.room_number
                    FROM leave_applications la
                    JOIN students s ON la.Reg_No = s.roll_number
                    LEFT JOIN rooms r ON s.room_id = r.room_id
                    JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
                    WHERE la.Status IN ('Rejected by HOD','Rejected by Admin','Rejected by Parents','Approved','out','closed','late entry')";
            
            if ($hostelId > 0) {
                    $sql .= " AND r.hostel_id = $hostelId";
            }

                if ($scopeGender !== null) {
                    $sql .= " AND s.gender = '" . $scopeGenderEsc . "'";
                }

            // Apply global search filter (q) when provided from DataTables
            $q = trim((string)($_GET['q'] ?? ''));
            if ($q !== '') {
                $qEsc = $conn->real_escape_string($q);
                $sql .= " AND (la.Reg_No LIKE '%$qEsc%' OR s.name LIKE '%$qEsc%' OR r.room_number LIKE '%$qEsc%' OR lt.Leave_Type_Name LIKE '%$qEsc%' OR la.Reason LIKE '%$qEsc%' OR la.Status LIKE '%$qEsc%')";
            }

            $sql .= " ORDER BY la.Applied_Date DESC";
            $rows = [];
            if ($res = $conn->query($sql)) {
                while ($row = $res->fetch_assoc()) { $rows[] = $row; }
            }
            return $rows;
        }
        
        function normalize_rows(array $rows): array {
            $out = [];
            foreach ($rows as $row) {
                $out[] = [
                    'reg'      => $row['Reg_No'] ?? '',
                    'name'     => $row['student_name'] ?? '',
                    'room'     => $row['room_number'] ?? '-',
                    'type'     => $row['Leave_Type_Name'] ?? '',
                    'applied'  => !empty($row['Applied_Date']) ? date('d-m-Y H:i', strtotime($row['Applied_Date'])) : '',
                    'from'     => !empty($row['From_Date']) ? date('d-m-Y H:i', strtotime($row['From_Date'])) : '',
                    'to'       => !empty($row['To_Date']) ? date('d-m-Y H:i', strtotime($row['To_Date'])) : '',
                    'reason'   => $row['Reason'] ?? '',
                    'status'   => $row['Status'] ?? ''
                ];
            }
            return $out;
        }
        
        function output_pdf(string $title, array $data, array $cols, string $filterInfo = ''): void
        {
            // Locate and include TCPDF. Try a few sensible paths.
            $tcpdf_candidates = [
                __DIR__ . '/TCPDF/tcpdf.php',
                __DIR__ . '/../TCPDF/tcpdf.php',
                __DIR__ . '/TCPDF-main/tcpdf.php',
                __DIR__ . '/../faculty/TCPDF-main/tcpdf.php',
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
                if (php_sapi_name() !== 'cli') {
                    header('Content-Type: text/plain; charset=utf-8');
                }
                die("TCPDF library not found. Tried paths: \n" . implode("\n", $tcpdf_candidates));
            }

            class ReportPDF extends TCPDF
            {
                public $hTitle = '';
                public $leftLogoPath = '';
                public $rightLogoPath = '';
                public $filterInfo = '';

                public function Header()
                {
                    $pageWidth = $this->getPageWidth();
                    $logoSize = 18;
                    $rightX = $pageWidth - 15 - $logoSize;

                    if (!empty($this->leftLogoPath) && file_exists($this->leftLogoPath)) {
                        $this->Image($this->leftLogoPath, 15, 10, $logoSize, $logoSize, '', '', '', false, 300, '', false, false, 0);
                    }
                    if (!empty($this->rightLogoPath) && file_exists($this->rightLogoPath)) {
                        $this->Image($this->rightLogoPath, $rightX, 10, $logoSize, $logoSize, '', '', '', false, 300, '', false, false, 0);
                    }

                    $this->SetFont('helvetica', 'B', 14);
                    $this->SetXY(0, 25);
                    $this->Cell($pageWidth, 8, 'M.Kumarasamy College of Engineering, Karur - 639 113', 0, 1, 'C');

                    $this->SetFont('helvetica', 'I', 10);
                    $this->SetXY(0, 33);
                    $this->Cell($pageWidth, 6, '(An Autonomous Institution Affiliated to Anna University, Chennai)', 0, 1, 'C');

                    $this->SetFont('helvetica', 'B', 11);
                    $this->SetTextColor(0, 0, 0);
                    $formattedDate = date('d/m/Y');
                    $reportTitle = $this->hTitle . ' (' . $formattedDate . ')';
                    $this->SetXY(0, 46);
                    $this->Cell($pageWidth, 10, $reportTitle, 0, 1, 'C');

                    $this->SetFont('helvetica', '', 9);
                    $dateY = 54;
                    $this->SetXY(15, $dateY);
                    $this->Cell(0, 5, 'Generated Date: ' . $formattedDate, 0, 0, 'L');
                    $this->SetXY(0, $dateY);
                    $this->Cell($pageWidth - 15, 5, 'Generated by : Admin', 0, 1, 'R');

                    $this->SetLineWidth(0.3);
                    $this->SetDrawColor(180, 180, 180);
                    $this->Line(10, $dateY + 5, $pageWidth - 10, $dateY + 5);
                    $this->Ln(15);
                }

                public function Footer()
                {
                    $this->SetY(-15);
                    $this->SetFont('dejavusans', 'B', 8);
                    $this->SetTextColor(0, 0, 139);
                    $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
                    $this->SetTextColor(0, 0, 0);
                }
            }

            $pdf = new ReportPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->hTitle = $title;
            $pdf->filterInfo = $filterInfo;
            $pdf->leftLogoPath = __DIR__ . '/image/mkce_logo2.jpg';
            $pdf->rightLogoPath = __DIR__ . '/image/logo-right.png';

            $pdf->SetCreator('Hostel Management System');
            $pdf->SetAuthor('Hostel Management System');
            $pdf->SetTitle($title);
            $pdf->SetMargins(15, 70, 15);
            $pdf->SetHeaderMargin(55);
            $pdf->SetFooterMargin(15);
            $pdf->SetAutoPageBreak(true, 25);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->setCellHeightRatio(1.2);
            $pdf->setCellPadding(2);

            $pdf->AddPage();
            $pdf->SetX(15);

            if (!empty($filterInfo)) {
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->SetFillColor(240, 240, 240);
                $pdf->SetX(15);
                $pdf->MultiCell(180, 10, 'Applied Filters: ' . $filterInfo, 0, 'L', true);
                $pdf->Ln(12);
            }

            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFillColor(10, 162, 161);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 8);

            $colWidths = [];
            $totalWidth = 180;
            $totalPercentage = 0;
            foreach ($cols as $col) {
                $widthPercent = (float) str_replace('%', '', $col['width']);
                $totalPercentage += $widthPercent;
            }
            $adjustedTotalWidth = ($totalPercentage > 100) ? $totalWidth * (100 / $totalPercentage) : $totalWidth;
            foreach ($cols as $col) {
                $widthPercent = (float) str_replace('%', '', $col['width']);
                $colWidths[] = ($widthPercent / 100) * $adjustedTotalWidth;
            }

            $pdf->SetX(15);
            foreach ($cols as $i => $col) {
                $label = $col['label'];
                $pdf->Cell($colWidths[$i], 6, $label, 1, 0, 'C', true);
            }
            $pdf->Ln();
            $pdf->SetX(15);

            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', '', 7);

            if (empty($data)) {
                $pdf->SetX(15);
                $pdf->Cell(array_sum($colWidths), 6, 'No records found.', 1, 0, 'C', true);
                $pdf->Ln();
            } else {
                $fill = false;
                foreach ($data as $row) {
                    $estimatedRowHeight = 8;
                    if ($pdf->GetY() + $estimatedRowHeight > 240) {
                        $pdf->AddPage();
                        $pdf->SetFillColor(10, 162, 161);
                        $pdf->SetTextColor(255, 255, 255);
                        $pdf->SetFont('helvetica', 'B', 8);
                        $pdf->SetX(15);
                        foreach ($cols as $i => $col) {
                            $label = $col['label'];
                            $pdf->Cell($colWidths[$i], 6, $label, 1, 0, 'C', true);
                        }
                        $pdf->Ln();
                        $pdf->SetX(15);
                        $pdf->SetFillColor(255, 255, 255);
                        $pdf->SetTextColor(0, 0, 0);
                        $pdf->SetFont('helvetica', '', 8);
                    }

                    $pdf->SetX(15);
                    foreach ($cols as $i => $col) {
                        $key = $col['key'];
                        $val = isset($row[$key]) ? $row[$key] : '';
                        $align = ($col['class'] == 'c') ? 'C' : (($col['class'] == 'l') ? 'L' : 'R');
                        $pdf->Cell($colWidths[$i], 5, $val, 1, 0, $align, $fill);
                    }
                    $pdf->Ln();
                    $fill = !$fill;
                }
            }

            $fname = strtolower(str_replace(' ', '_', $title)) . '.pdf';

            // Clear any previous output to allow TCPDF to send headers
            if (ob_get_length()) {
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
            }

            $pdf->Output($fname, 'I');
            exit;
        }
        
        function output_excel(string $title, array $data, array $headers, string $filterInfo = ''): void
        {
            // Replicated from admin/report_api.php to ensure identical behavior
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
        
        // Process export
        switch ($action) {
            case 'pending_pdf':
                $rows = fetch_pending($conn);
                $normalized = normalize_rows($rows);
                $cols = [
                    ['key' => 'reg',     'label' => 'Register No',  'width' => '13%', 'class' => 'c'],
                    ['key' => 'name',    'label' => 'Student Name', 'width' => '20%', 'class' => 'l'],
                    ['key' => 'room',    'label' => 'Room No',      'width' => '10%',  'class' => 'c'],
                    ['key' => 'type',    'label' => 'Leave Type',   'width' => '12%', 'class' => 'c'],
                    ['key' => 'applied', 'label' => 'Applied',      'width' => '15%', 'class' => 'c'],
                    ['key' => 'from',    'label' => 'From',         'width' => '15%', 'class' => 'c'],
                    ['key' => 'to',      'label' => 'To',           'width' => '15%', 'class' => 'c'],
                    ['key' => 'reason',  'label' => 'Reason',       'width' => '16%', 'class' => 'l'],
                    ['key' => 'status',  'label' => 'Status',       'width' => '8%',  'class' => 'c'],
                ];
                output_pdf('Pending Leave Applications', $normalized, $cols);
                break;
            
            case 'pending_excel':
                $rows = fetch_pending($conn);
                $normalized = normalize_rows($rows);
                $headers = ['Register No', 'Student Name', 'Room No', 'Leave Type', 'Applied', 'From', 'To', 'Reason', 'Status'];
                output_excel('Pending Leave Applications', $normalized, $headers);
                break;
            
            case 'approved_pdf':
                $rows = fetch_approved($conn);
                $normalized = normalize_rows($rows);
                $cols = [
                    ['key' => 'reg',     'label' => 'Register No',  'width' => '15%', 'class' => 'c'],
                    ['key' => 'name',    'label' => 'Student Name', 'width' => '20%', 'class' => 'l'],
                    ['key' => 'room',    'label' => 'Room No',      'width' => '10%',  'class' => 'c'],
                    ['key' => 'type',    'label' => 'Leave Type',   'width' => '15%', 'class' => 'c'],
                    ['key' => 'applied', 'label' => 'Applied',      'width' => '17%', 'class' => 'c'],
                    ['key' => 'from',    'label' => 'From',         'width' => '17%', 'class' => 'c'],
                    ['key' => 'to',      'label' => 'To',           'width' => '17%', 'class' => 'c'],
                    ['key' => 'reason',  'label' => 'Reason',       'width' => '20%', 'class' => 'l'],
                    ['key' => 'status',  'label' => 'Status',       'width' => '17%',  'class' => 'c'],
                ];
                output_pdf('Approved Leave Applications', $normalized, $cols);
                break;
            
            case 'approved_excel':
                $rows = fetch_approved($conn);
                $normalized = normalize_rows($rows);
                $headers = ['Register No', 'Student Name', 'Room No', 'Leave Type', 'Applied', 'From', 'To', 'Reason', 'Status'];
                output_excel('Approved Leave Applications', $normalized, $headers);
                break;
        }
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
    <!-- Alertify -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- Alertify -->
    <script src="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>
    <script>
        alertify.defaults.notifier.position = 'top-right';
        alertify.defaults.notifier.delay = 5;
    </script>

    <!-- DataTables Export Buttons -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>



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


    /* Student Leave Breakdown Modal Styling */
    #studentLeaveBreakdownModal .modal-content {
        border-radius: 12px;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        background: linear-gradient(145deg, #f0f0f0, #ffffff);
        border: none;
        overflow: hidden;
    }

    #studentLeaveBreakdownModal .modal-header {
        background: linear-gradient(to right, #6a11cb 0%, #2575fc 100%);
        color: white;
        padding: 15px 20px;
        border-bottom: none;
    }

    #studentLeaveBreakdownModal .modal-header .modal-title {
        font-weight: 200;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    }

    #studentLeaveBreakdownModal .modal-header .btn-close {
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

    #studentLeaveBreakdownModal .modal-header .btn-close:hover {
        background-color: rgba(255, 255, 255, 0.4);
        transform: scale(1.1);
    }

    #studentLeaveBreakdownModal .modal-body {
        padding: 20px;
        background: #f8f9fa;
    }

    #studentLeaveBreakdownModal .card {
        border-radius: 5px;
        box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        overflow: hidden;
    }

    #studentLeaveBreakdownModal .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 40px rgba(31, 38, 135, 0.2);
    }

    #studentLeaveBreakdownModal .card-header {
        background: linear-gradient(to left, #06b6d4, #0893b3);
        border-bottom: 2px solid rgba(37, 117, 252, 0.2);
        font-weight: 600;
        color: #ffffffff;
    }

    #studentLeaveBreakdownModal .table-responsive {
        max-height: 400px;
        overflow-y: auto;
    }

    #studentLeaveBreakdownModal .table-responsive::-webkit-scrollbar {
        width: 8px;
    }

    #studentLeaveBreakdownModal .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    /* #studentLeaveBreakdownModal .table-responsive::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #6a11cb, #2575fc);
        border-radius: 10px;
    }

    #studentLeaveBreakdownModal .table-responsive::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #2575fc, #6a11cb);
    } */

    #studentLeaveBreakdownModal .badge {
        font-size: 0.9em;
        padding: 5px 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        font-weight: normal;
    }

    /* Student Modal Animation */
    #studentLeaveBreakdownModal.show .modal-dialog {
        animation: modalEnter 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }

    /* Gradient Card Styles for Student Modal */
    #studentLeaveBreakdownModal .gradient-card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
        height: 100%;
        min-height: 150px;
        max-height: 180px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    #studentLeaveBreakdownModal .gradient-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }

    /* Decorative tilted corners */
    #studentLeaveBreakdownModal .gradient-card::before,
    #studentLeaveBreakdownModal .gradient-card::after {
        content: "";
        position: absolute;
        pointer-events: none;
        border-radius: 5px;
        transform: rotate(45deg);
        transition: transform 0.35s ease, opacity 0.35s ease;
        z-index: 0;
    }

    #studentLeaveBreakdownModal .gradient-card::before {
        top: -95px;
        right: -95px;
        width: 140px;
        height: 140px;
        background: rgba(0, 0, 0, 0.06);
    }

    #studentLeaveBreakdownModal .gradient-card::after {
        bottom: -105px;
        left: -105px;
        width: 200px;
        height: 140px;
        background: rgba(0, 0, 0, 0.06);
    }

    #studentLeaveBreakdownModal .gradient-card:hover::before {
        transform: translate(-4px, 4px) rotate(45deg) scale(1.03);
        opacity: 0.14;
    }

    #studentLeaveBreakdownModal .gradient-card:hover::after {
        transform: translate(4px, -4px) rotate(45deg) scale(1.03);
        opacity: 0.22;
    }

    /* Card Content */
    #studentLeaveBreakdownModal .gradient-card .card-body {
        padding: 1.25rem 1rem;
        position: relative;
        z-index: 1;
    }

    /* Icon Container */
    #studentLeaveBreakdownModal .gradient-card .icon-container {
        font-size: 40px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
        opacity: 0.9;
    }

    #studentLeaveBreakdownModal .gradient-card:hover .icon-container {
        transform: scale(1.2);
    }

    /* Card Title */
    #studentLeaveBreakdownModal .gradient-card .card-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    /* Card Value */
    #studentLeaveBreakdownModal .gradient-card .card-value {
        font-size: 1.3rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    /* Leave type badge styles */
    .lv-badge { display:inline-flex; align-items:center; gap:.35rem; padding:.2rem .6rem; border-radius:999px; font-size:.8rem; font-weight:700; border:1px solid transparent; white-space:nowrap; }
    .lv-success { background:#ecfdf5; color:#065f46; border-color:#a7f3d0; }
    .lv-warning { background:#fffbeb; color:#92400e; border-color:#fde68a; }
    .lv-danger  { background:#fef2f2; color:#991b1b; border-color:#fecaca; }
    .lv-info    { background:#eff6ff; color:#1e40af; border-color:#bfdbfe; }
    .lv-primary { background:#eef2ff; color:#3730a3; border-color:#c7d2fe; }
    .lv-muted   { background:#f3f4f6; color:#374151; border-color:#e5e7eb; }

    /* Gradient Card Styles */
    .gradient-card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
        height: 100%;
        min-height: 150px;
        max-height: 180px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    .gradient-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }

    .gradient-card::before,
    .gradient-card::after {
        content: "";
        position: absolute;
        pointer-events: none;
        border-radius: 5px;
        transform: rotate(45deg);
        transition: transform 0.35s ease, opacity 0.35s ease;
        z-index: 0;
    }

    .gradient-card::before {
        top: -95px;
        right: -95px;
        width: 140px;
        height: 140px;
        background: rgba(0, 0, 0, 0.06);
    }

    .gradient-card::after {
        bottom: -105px;
        left: -105px;
        width: 200px;
        height: 140px;
        background: rgba(0, 0, 0, 0.06);
    }

    .gradient-card:hover::before {
        transform: translate(-4px, 4px) rotate(45deg) scale(1.03);
        opacity: 0.14;
    }

    .gradient-card:hover::after {
        transform: translate(4px, -4px) rotate(45deg) scale(1.03);
        opacity: 0.22;
    }

    .gradient-primary { background: linear-gradient(135deg, #566eee 0%, #4e28b0 100%); }
    .gradient-success { background: linear-gradient(135deg, #42cbbd 0%, #21d9ab 100%); }
    .gradient-info { background: linear-gradient(135deg, #ffa41a 0%, #ff8a1a 100%); }
    .gradient-warning { background: linear-gradient(135deg, #f45a67ff 0%, #e84956 100%); }
    .gradient-danger { background: linear-gradient(135deg, #96a1b4 0%, #6e788c 100%); }
    .gradient-secondary { background: linear-gradient(135deg, #5ecdf2 0%, #539bfc 100%); }

    .gradient-card .card-body {
        padding: 1.25rem 1rem;
        position: relative;
        z-index: 1;
    }

    .gradient-card .icon-container {
        font-size: 40px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
        opacity: 0.9;
    }

    .gradient-card:hover .icon-container {
        transform: scale(1.2);
    }

    .gradient-card .card-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    .gradient-card .card-value {
        font-size: 1.3rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .pulse-value {
        animation: pulse 2s infinite;
    }

    .card-clickable { cursor: pointer; }

    /* Table column widths */
    #ivr-pending-leave-table th.col-sno,
    #ivr-pending-leave-table td.col-sno { width: 1% !important; white-space: nowrap; }
    #ivr-pending-leave-table th.col-action,
    #ivr-pending-leave-table td.col-action { width: 6% !important; white-space: nowrap; }

    @media (max-width: 768px) {
        .gradient-card .card-value { font-size: 1.1rem; }
        .gradient-card .card-title { font-size: 1rem; }
        .gradient-card .icon-container { font-size: 30px; }
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
                    <li class="breadcrumb-item active" aria-current="page">Leave</li>
                </ol>
            </nav>
        </div>

        <!-- Content Area -->
        <div class="container-fluid">
            <div class="custom-tabs">
                <ul class="nav nav-tabs" role="tablist">
                    <!-- Center the main tabs -->

                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="tab" id="family-main-tab" href="#pending-content"
                            role="tab" aria-selected="true">
                            <span class="hidden-xs-down" style="font-size: 0.9em;"><i
                                    class="fas fa-clock-rotate-left tab-icon"></i>
                                Pending </span>
                        </a>
                    </li>

                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" id="processed-main-tab" href="#processed-content"
                            role="tab" aria-selected="false">
                            <span class="hidden-xs-down" style="font-size: 0.9em;"><i
                                    class="fas fa-circle-check tab-icon"></i> Processed</span>
                        </a>
                    </li>
                </ul>



                <div class="tab-content mt-3">

                    <!-- Pending Tab Content -->
                    <div class="tab-pane fade show active" id="pending-content" role="tabpanel"
                        aria-labelledby="family-main-tab">

                        <!-- Stat Cards for Pending (inline) -->
                        <div id="leaveStatsCards">
                            <div class="row mb-4" id="pendingStatsRow">
                                <!-- Total Pending Card -->
                                <div class="col-xl col-lg-3 col-md-6 mb-4">
                                    <div class="gradient-card gradient-primary">
                                        <div class="card-body text-center">
                                            <div class="icon-container">
                                                <i class="fas fa-clipboard-list text-white"></i>
                                            </div>
                                            <h4 class="card-title text-white">Total Pending</h4>
                                            <h2 class="card-value text-white font-weight-bold pulse-value" id="pendingTotalCount">0</h2>
                                        </div>
                                    </div>
                                </div>
                                <!-- Dynamic leave type cards will be appended here via JS -->
                            </div>
                        </div>


                        <!-- Hostel Filter for Pending -->
                        <div class="mb-3" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <label for="pendingHostelFilter" class="form-label fw-bold mb-0">
                                        <i class="fas fa-building"></i> Filter by Hostel:
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <select id="pendingHostelFilter" class="form-select">
                                        <option value="0">All Hostels</option>
                                    </select>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button class="btn btn-sm btn-secondary" id="clearPendingHostelFilter">
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Export Buttons for Pending -->
                        <div class="d-flex justify-content-end mb-2">
                            <a href="?action=pending_pdf" data-base="?action=pending_pdf" target="_blank" style="margin-right: 5px;" class="btn btn-danger btn-sm export-pending-pdf">
                                <i class="fa-solid fa-file-pdf"></i> Export PDF
                            </a>
                            <a href="?action=pending_excel" data-base="?action=pending_excel" class="btn btn-success btn-sm export-pending-excel">
                                <i class="fa-solid fa-file-excel"></i> Export Excel
                            </a>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="ivr-pending-leave-table" width="100%" cellspacing="0">
                                    <thead class="gradient-header">
                                        <tr>
                                            <th>S.No</th>
                                            <th>Reg No</th>
                                            <th>Name</th>
                                            <th>Room No</th>
                                            <th>Leave Type</th>
                                            <th>Applied Date</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Reason</th>
                                            <th>Proof</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="pendingTableBody">
                                        <!-- Data loaded via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>



                    <!-- Processed Tab Content -->
                    <div class="tab-pane fade" id="processed-content" role="tabpanel"
                        aria-labelledby="processed-main-tab">

                        <!-- Stat Cards for Processed (inline) -->
                        <div id="processedLeaveStatsCards">
                            <!-- Hidden data for modal breakdown -->
                            <div id="breakdownData" style="display:none;" data-approved='{}' data-rejected='{}'></div>
                            
                            <div class="row mb-4">
                                <!-- Total Processed Card -->
                                <div class="col-xl col-lg-3 col-md-6 mb-4">
                                    <div class="gradient-card gradient-primary card-clickable" data-card-type="processed" data-title="Total Processed Breakdown">
                                        <div class="card-body text-center">
                                            <div class="icon-container">
                                                <i class="fas fa-tasks text-white"></i>
                                            </div>
                                            <h4 class="card-title text-white">Total Processed</h4>
                                            <h2 class="card-value text-white font-weight-bold pulse-value" id="processedTotalCount">0</h2>
                                        </div>
                                    </div>
                                </div>

                                <!-- Total Approved Card -->
                                <div class="col-xl col-lg-3 col-md-6 mb-4">
                                    <div class="gradient-card gradient-success card-clickable" data-card-type="approved" data-title="Total Approved Breakdown">
                                        <div class="card-body text-center">
                                            <div class="icon-container">
                                                <i class="fas fa-check-circle text-white"></i>
                                            </div>
                                            <h4 class="card-title text-white">Total Approved</h4>
                                            <h2 class="card-value text-white font-weight-bold pulse-value" id="processedApprovedCount">0</h2>
                                        </div>
                                    </div>
                                </div>

                                <!-- Total Rejected Card -->
                                <div class="col-xl col-lg-3 col-md-6 mb-4">
                                    <div class="gradient-card gradient-danger card-clickable" data-card-type="rejected" data-title="Total Rejected Breakdown">
                                        <div class="card-body text-center">
                                            <div class="icon-container">
                                                <i class="fas fa-times-circle text-white"></i>
                                            </div>
                                            <h4 class="card-title text-white">Total Rejected</h4>
                                            <h2 class="card-value text-white font-weight-bold pulse-value" id="processedRejectedCount">0</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Hostel Filter for Processed -->
                        <div class="mb-3" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <label for="processedHostelFilter" class="form-label fw-bold mb-0">
                                        <i class="fas fa-building"></i> Filter by Hostel:
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <select id="processedHostelFilter" class="form-select">
                                        <option value="0">All Hostels</option>
                                    </select>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button class="btn btn-sm btn-secondary" id="clearProcessedHostelFilter">
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Export Buttons for Processed -->
                        <div class="d-flex justify-content-end mb-2">
                            <a href="?action=approved_pdf" data-base="?action=approved_pdf" target="_blank" style="margin-right: 5px;" class="btn btn-danger btn-sm export-processed-pdf">
                                <i class="fa-solid fa-file-pdf"></i> Export PDF
                            </a>
                            <a href="?action=approved_excel" data-base="?action=approved_excel" class="btn btn-success btn-sm export-processed-excel">
                                <i class="fa-solid fa-file-excel"></i> Export Excel
                            </a>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="processed-leave-table" width="100%" cellspacing="0">
                                    <colgroup>
                                        <col style="width:2%;">  <!-- S.No -->
                                        <col style="width:8%;">  <!-- Reg No -->
                                        <col style="width:12%;"> <!-- Name -->
                                        <col style="width:7%;">  <!-- Room No -->
                                        <col style="width:9%;">  <!-- Leave Type -->
                                        <col style="width:13%;"> <!-- Applied Date -->
                                        <col style="width:12%;"> <!-- From -->
                                        <col style="width:12%;"> <!-- To -->
                                        <col style="width:12%;"> <!-- Reason -->
                                        <col style="width:8%;">  <!-- Proof -->
                                        <col style="width:13%;"> <!-- Status -->
                                    </colgroup>
                                    <thead class="gradient-header">
                                        <tr>
                                            <th>S.No</th>
                                            <th>Reg No</th>
                                            <th>Name</th>
                                            <th>Room No</th>
                                            <th>Leave Type</th>
                                            <th>Applied Date</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Reason</th>
                                            <th>Proof</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="processedTableBody">
                                        <!-- Data loaded via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>

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

    // Initialize DataTables for all tables
    $(document).ready(function() {

        $('#approved-table').DataTable({
            responsive: true
        });

    });
    </script>

    <!--My Scripts-->

    <script>
    $(document).ready(function() {

        // ===================== AJAX Data Loading Functions =====================
        
        // Load Hostels
        function loadHostels() {
            return $.ajax({
                url: 'leave_approve.php',
                type: 'GET',
                data: { ajax: 'getHostels' },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        // Group by gender
                        var femaleHostels = response.data.filter(h => h.gender.toLowerCase() === 'female');
                        var maleHostels = response.data.filter(h => h.gender.toLowerCase() === 'male');
                        
                        // Build option groups
                        var html = '<option value="0">All Hostels</option>';
                        
                        if (femaleHostels.length > 0) {
                            html += '<optgroup label="Girls Hostels">';
                            femaleHostels.forEach(function(hostel) {
                                html += '<option value="' + hostel.hostel_id + '">' + hostel.hostel_name + '</option>';
                            });
                            html += '</optgroup>';
                        }
                        
                        if (maleHostels.length > 0) {
                            html += '<optgroup label="Boys Hostels">';
                            maleHostels.forEach(function(hostel) {
                                html += '<option value="' + hostel.hostel_id + '">' + hostel.hostel_name + '</option>';
                            });
                            html += '</optgroup>';
                        }
                        
                        $('#pendingHostelFilter').html(html);
                        $('#processedHostelFilter').html(html);
                    }
                },
                error: function() {
                    console.error('Failed to load hostels');
                }
            });
        }
        
        // Icons for leave types
        var leaveTypeIcons = {
            'Medical Leave': 'fa-user-doctor',
            'Emergency Leave': 'fa-triangle-exclamation',
            'Leave': 'fa-house',
            'On Duty': 'fa-solid fa-book',
            'Outing': 'fa-solid fa-suitcase'
        };
        
        var gradients = ['success', 'info', 'warning', 'danger', 'secondary'];
        
        // Load Pending Stats
        function loadPendingStats() {
            var hostelId = $('#pendingHostelFilter').val();
            
            return $.ajax({
                url: 'leave_approve.php',
                type: 'GET',
                data: { ajax: 'getPendingStats', hostelId: hostelId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#pendingTotalCount').text(response.total);
                        
                        // Remove old dynamic cards (keep first card which is Total)
                        $('#pendingStatsRow .dynamic-card').remove();
                        
                        // Build leave type cards
                        response.leaveTypes.forEach(function(lt, index) {
                            var gradientClass = gradients[index % gradients.length];
                            var icon = leaveTypeIcons[lt.name] || 'fa-file-alt';
                            var cardHtml = `
                                <div class="col-xl col-lg-3 col-md-6 mb-4 dynamic-card">
                                    <div class="gradient-card gradient-${gradientClass}">
                                        <div class="card-body text-center">
                                            <div class="icon-container">
                                                <i class="fas ${icon} text-white"></i>
                                            </div>
                                            <h4 class="card-title text-white">${lt.name}</h4>
                                            <h2 class="card-value text-white font-weight-bold pulse-value">${lt.count}</h2>
                                        </div>
                                    </div>
                                </div>
                            `;
                            $('#pendingStatsRow').append(cardHtml);
                        });
                    }
                },
                error: function() {
                    console.error('Failed to load pending stats');
                }
            });
        }
        
        // Load Processed Stats
        function loadProcessedStats() {
            var hostelId = $('#processedHostelFilter').val();
            
            return $.ajax({
                url: 'leave_approve.php',
                type: 'GET',
                data: { ajax: 'getProcessedStats', hostelId: hostelId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#processedTotalCount').text(response.total);
                        $('#processedApprovedCount').text(response.totalApproved);
                        $('#processedRejectedCount').text(response.totalRejected);
                        
                        // Update breakdown data for modal
                        $('#breakdownData').attr('data-approved', JSON.stringify(response.approvedCounts));
                        $('#breakdownData').attr('data-rejected', JSON.stringify(response.rejectedCounts));
                    }
                },
                error: function() {
                    console.error('Failed to load processed stats');
                }
            });
        }
        
        // Load Pending Table
        function loadPendingTable() {
            var hostelId = $('#pendingHostelFilter').val();
            
            return $.ajax({
                url: 'leave_approve.php',
                type: 'GET',
                data: { ajax: 'getPendingTable', hostelId: hostelId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // If DataTables is already initialized, destroy it BEFORE replacing tbody
                        // (destroy() can revert DOM back to the previously-initialized state)
                        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#ivr-pending-leave-table')) {
                            $('#ivr-pending-leave-table').DataTable().destroy();
                        }

                        var html = '';
                        response.data.forEach(function(row, index) {
                            var proofHtml = row.Proof ? 
                                `<button type='button' class='btn btn-info btn-sm view-proof' 
                                    data-proof='student/${row.Proof}' 
                                    data-bs-toggle='modal' 
                                    data-bs-target='#viewProofModal'>
                                    <i class='fa-solid fa-eye'></i> View
                                </button>` : 
                                '<span class="text-muted">No Proof</span>';
                            
                            var statusHtml = '';
                            if (row.Status === 'Pending') {
                                statusHtml = "<button class='btn btn-warning btn-sm' disabled>Pending @ HOD</button>";
                            } else if (row.Status === 'Forwarded to Admin') {
                                statusHtml = "<button class='btn btn-info btn-sm' disabled>Pending @ Admin</button>";
                            } else {
                                statusHtml = row.Status;
                            }
                            
                            html += `<tr>
                                <td>${index + 1}</td>
                                <td>${row.Reg_No}</td>
                                <td><a href='#' class='student-name-link' data-reg-no='${row.Reg_No}' style='text-decoration:none; color:#166176; cursor:pointer;'>${row.student_name}</a></td>
                                <td>${row.room_number}</td>
                                <td>${row.Leave_Type_Badge}</td>
                                <td>${row.Applied_Date}</td>
                                <td>${row.From_Date} <button type='button' class='btn btn-link p-0 ms-2 edit-dates' title='Edit dates' data-id='${row.Leave_ID}' data-from='${row.From_Date_Raw}' data-to='${row.To_Date_Raw}'><i class='fa-regular fa-pen-to-square'></i></button></td>
                                <td>${row.To_Date} <button type='button' class='btn btn-link p-0 ms-2 edit-dates' title='Edit dates' data-id='${row.Leave_ID}' data-from='${row.From_Date_Raw}' data-to='${row.To_Date_Raw}'><i class='fa-regular fa-pen-to-square'></i></button></td>
                                <td>${row.Reason}</td>
                                <td class='text-center align-middle'>${proofHtml}</td>
                                <td class='text-center align-middle'>${statusHtml}</td>
                                <td class='text-center align-middle'>
                                    <button class='btn btn-success btn-sm Approve' id='adminApprove' data-id='${row.Leave_ID}'>
                                        <i class='fa-solid fa-check'></i> 
                                    </button>
                                    <button type='button' class='btn btn-danger btn-sm Reject' data-bs-toggle='modal' data-bs-target='#leaveRejectModal' id='adminReject' data-id='${row.Leave_ID}'>
                                        <i class='fa-solid fa-xmark'></i> 
                                    </button>
                                </td>
                            </tr>`;
                        });
                        
                        $('#pendingTableBody').html(html);
                        initIVRPendingDT();
                    }
                },
                error: function() {
                    console.error('Failed to load pending table');
                }
            });
        }
        
        // Load Processed Table
        function loadProcessedTable() {
            var hostelId = $('#processedHostelFilter').val();
            
            return $.ajax({
                url: 'leave_approve.php',
                type: 'GET',
                data: { ajax: 'getProcessedTable', hostelId: hostelId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // If DataTables is already initialized, destroy it BEFORE replacing tbody
                        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#processed-leave-table')) {
                            $('#processed-leave-table').DataTable().destroy();
                        }

                        var html = '';
                        response.data.forEach(function(row, index) {
                            var proofHtml = row.Proof ? 
                                `<button type='button' class='btn btn-info btn-sm view-proof' 
                                    data-proof='student/${row.Proof}' 
                                    data-bs-toggle='modal' 
                                    data-bs-target='#viewProofModal'>
                                    <i class='fa-solid fa-eye'></i> View
                                </button>` : 
                                '<span class="text-muted">No Proof</span>';
                            
                            var fromDateHtml = row.From_Date;
                            var toDateHtml = row.To_Date;
                            if (row.isEditable) {
                                fromDateHtml += ` <button type='button' class='btn btn-link p-0 ms-2 edit-dates' title='Edit dates' data-id='${row.Leave_ID}' data-from='${row.From_Date_Raw}' data-to='${row.To_Date_Raw}'><i class='fa-regular fa-pen-to-square'></i></button>`;
                                toDateHtml += ` <button type='button' class='btn btn-link p-0 ms-2 edit-dates' title='Edit dates' data-id='${row.Leave_ID}' data-from='${row.From_Date_Raw}' data-to='${row.To_Date_Raw}'><i class='fa-regular fa-pen-to-square'></i></button>`;
                            }
                            
                            var statusHtml = '';
                            if (row.Status === 'Approved') {
                                statusHtml = "<button class='btn btn-success btn-sm' disabled>Leave Approved</button><br><span class='text-muted'> (Approved by Parents)</span>";
                            } else if (row.Status === 'Rejected by HOD') {
                                statusHtml = `<button type='button' style='background-color:#f1a460' class='btn btn btn-sm reasonView' data-reason='${row.Remarks}'>Rejected</button><br><span class='text-muted'> (Rejected by HOD)</span>`;
                            } else if (row.Status === 'Rejected by Admin') {
                                statusHtml = `<button type='button' style='background-color:#f1a460' class='btn btn btn-sm reasonView' data-reason='${row.Remarks}'>Rejected</button><br><span class='text-muted'> (Rejected by Admin)</span>`;
                            } else if (row.Status === 'Rejected by Parents') {
                                statusHtml = `<button type='button' style='background-color:#f1a460' class='btn btn btn-sm reasonView' data-reason='${row.Status}'>Rejected</button><br><span class='text-muted'> (Rejected by Parents)</span>`;
                            } else if (row.Status === 'out') {
                                statusHtml = "<button class='btn btn-warning btn-sm' disabled>Out</button><br><span class='text-muted'> (Out)</span>";
                            } else if (row.Status === 'closed') {
                                statusHtml = "<button class='btn btn-secondary btn-sm' disabled>Closed</button><br><span class='text-muted'> (Closed)</span>";
                            } else if (row.Status === 'late entry') {
                                statusHtml = "<button class='btn btn-info btn-sm' disabled>Late Entry</button><br><span class='text-muted'> (Late Entry)</span>";
                            } else if (row.Status === 'IVR Pending') {
                                var ivrState = (row.ivr_call_status || '').toString().toLowerCase();
                                var ivrText = 'Awaiting Parent Response';
                                if (ivrState === 'busy') ivrText = 'Busy';
                                else if (ivrState === 'not_answered') ivrText = 'Not Answered';
                                else if (ivrState === 'failed') ivrText = 'Call Failed';
                                else if (ivrState === 'congestion') ivrText = 'Network Congestion';
                                else if (ivrState === 'dialed') ivrText = 'Dialing';
                                else if (ivrState === 'answered') ivrText = 'Answered (Waiting for DTMF)';

                                statusHtml = "<button class='btn btn-warning btn-sm' disabled><i class='fa-solid fa-phone-volume'></i> IVR Pending</button><br><span class='text-muted'> (" + ivrText + ")</span>";
                            }
                            
                            html += `<tr>
                                <td>${index + 1}</td>
                                <td>${row.Reg_No}</td>
                                <td><a href='#' class='student-name-link' data-reg-no='${row.Reg_No}' style='text-decoration:none; color:#166176; cursor:pointer;'>${row.student_name}</a></td>
                                <td>${row.room_number}</td>
                                <td>${row.Leave_Type_Badge}</td>
                                <td>${row.Applied_Date}</td>
                                <td>${fromDateHtml}</td>
                                <td>${toDateHtml}</td>
                                <td>${row.Reason}</td>
                                <td class='text-center align-middle'>${proofHtml}</td>
                                <td class='text-center align-middle'>${statusHtml}</td>
                            </tr>`;
                        });
                        
                        $('#processedTableBody').html(html);
                        initIVRProcessedDT();
                    }
                },
                error: function() {
                    console.error('Failed to load processed table');
                }
            });
        }
        
        // DataTable initialization functions
        window.initIVRPendingDT = function() {
            const sel = '#ivr-pending-leave-table';
            if (!$(sel).length) return;
            
            if ($.fn.DataTable.isDataTable(sel)) {
                $(sel).DataTable().destroy();
            }
            
            $(sel).find('thead th, tbody td').css('width', '');
            
            $(sel).DataTable({
                responsive: true,
                autoWidth: false,
                destroy: true,
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [9, 10, 11] },
                    { width: '1%', targets: 0, className: 'col-sno' },
                    { width: '8%', targets: 1 },
                    { width: '12%', targets: 2 },
                    { width: '7%', targets: 3 },
                    { width: '9%', targets: 4 },
                    { width: '13%', targets: 5 },
                    { width: '12%', targets: 6 },
                    { width: '12%', targets: 7 },
                    { width: '10%', targets: 8 },
                    { width: '8%', targets: 9 },
                    { width: '13%', targets: 10 },
                    { width: '6%', targets: 11, className: 'col-action' }
                ]
            });
        };
        
        window.initIVRProcessedDT = function() {
            const sel = '#processed-leave-table';
            if (!$(sel).length) return;
            
            if ($.fn.DataTable.isDataTable(sel)) {
                $(sel).DataTable().destroy();
            }
            
            $(sel).DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [9, 10] }
                ]
            });
        };
        
        // Reload ONLY tables (used after approve/reject/update APIs)
        window.reloadTablesOnly = function() {
            return $.when(loadPendingTable(), loadProcessedTable());
        };

        // Reload stats first, then load tables only after BOTH stats APIs succeed
        window.reloadAllData = function() {
            // Important: don't block table loading if stats calls fail (e.g., JSON parse errors / auth redirects)
            // Still waits until both stats requests finish (success or fail) before loading tables.
            return $.when(loadPendingStats(), loadProcessedStats())
                .fail(function(xhr, status, err) {
                    // This will run if either of the stats calls fails
                    console.error('Stats load failed:', status, err);
                })
                .always(function() {
                    reloadTablesOnly();
                });
        };
        
        // Initial load (stats -> tables)
        loadHostels().always(function() {
            reloadAllData();
        });
        
        // ===================== Hostel Filter Event Handlers =====================
        // Pending tab hostel filter
        $(document).on('change', '#pendingHostelFilter', function() {
            reloadAllData();
        });
        
        // Clear pending hostel filter
        $(document).on('click', '#clearPendingHostelFilter', function() {
            $('#pendingHostelFilter').val('0');
            reloadAllData();
        });
        
        // Processed tab hostel filter
        $(document).on('change', '#processedHostelFilter', function() {
            reloadAllData();
        });
        
        // Clear processed hostel filter
        $(document).on('click', '#clearProcessedHostelFilter', function() {
            $('#processedHostelFilter').val('0');
            reloadAllData();
        });

        // Export links: append DataTable search (global) as ?q=... so server filters exported data
        function buildExportUrl(base, tableSelector, hostelFilterSelector) {
            try {
                if (!tableSelector) return base;
                var dt = $(tableSelector).DataTable && $.fn.DataTable.isDataTable(tableSelector) ? $(tableSelector).DataTable() : null;
                var q = '';
                if (dt) q = dt.search() || '';
                
                var hostelId = $(hostelFilterSelector).val() || '0';
                
                var url = base;
                if (hostelId && hostelId !== '0') {
                    url += '&hostelId=' + encodeURIComponent(hostelId);
                }
                if (q) {
                    url += '&q=' + encodeURIComponent(q);
                }
                
                return url;
            } catch (e) {
                return base;
            }
        }

        // Pending exports
        $(document).on('click', '.export-pending-pdf, .export-pending-excel', function(e){
            var base = $(this).data('base') || $(this).attr('href');
            var url = buildExportUrl(base, '#ivr-pending-leave-table', '#pendingHostelFilter');
            $(this).attr('href', url);
            // allow normal navigation to continue
        });

        // Processed exports
        $(document).on('click', '.export-processed-pdf, .export-processed-excel', function(e){
            var base = $(this).data('base') || $(this).attr('href');
            var url = buildExportUrl(base, '#processed-leave-table', '#processedHostelFilter');
            $(this).attr('href', url);
        });

        //proof view

        $(document).on("click", ".view-proof", function() {
            let proofPath = $(this).data("proof") || '';
            // cache-busting timestamp
            let timestamp = new Date().getTime();

            function isAbsoluteUrl(u){ return /^(?:[a-z]+:)?\/\//i.test(u) || u.startsWith('/'); }

            let candidates = [];
            if (isAbsoluteUrl(proofPath)) {
                candidates.push(proofPath);
            } else {
                // If admin partial provided a prefixed value like 'student/...' or just 'proofs/...', try common server locations
                let m = proofPath.match(/^student\/(.*)/i);
                if (m) {
                    let rest = m[1];
                    candidates.push('../Student/' + rest);
                    candidates.push('../student/' + rest);
                    candidates.push('../' + proofPath);
                    candidates.push('/Student/' + rest);
                    candidates.push('/' + proofPath);
                } else if (proofPath.startsWith('proofs/')) {
                    candidates.push('../Student/' + proofPath);
                    candidates.push('/Student/' + proofPath);
                    candidates.push('../' + proofPath);
                    candidates.push('/' + proofPath);
                } else if (proofPath) {
                    candidates.push('../Student/' + proofPath);
                    candidates.push('../' + proofPath);
                    candidates.push('/Student/' + proofPath);
                    candidates.push('/' + proofPath);
                }
            }

            // Always try the raw value last
            candidates.push(proofPath);

            // Pick the first candidate to use for preview (best-effort). If you need strict existence checks, we can add an XHR probe.
            let finalPath = candidates.find(p => !!p) || '';
            let cacheBustedPath = finalPath + (finalPath.indexOf('?') === -1 ? '?t=' + timestamp : '&t=' + timestamp);
            let ext = (cacheBustedPath.split('.').pop() || '').split('?')[0].toLowerCase();

            let html = "";
            if (["jpg", "jpeg", "png", "gif", "webp"].includes(ext)) {
                html = `<img src="${cacheBustedPath}" class="img-fluid" alt="Proof">`;
            } else if (ext === "pdf") {
                html = `<iframe src="${cacheBustedPath}" width="100%" height="600px" style="border:none;"></iframe>`;
            } else if (finalPath) {
                // Fallback — provide a link if preview not supported or extension unknown
                html = `<div class="text-center"><p class="text-muted">Preview not available. <a href="${finalPath}" target="_blank">Open file</a></p></div>`;
            } else {
                html = `<p class="text-danger">No proof available</p>`;
            }

            $("#proofContainer").html(html);
        });


        $(document).on("click", ".reasonView", function() {
            Swal.fire({
                title: "Rejection - Reason",
                text: $(this).data("reason"),
                icon: "error"
            });
        });

        // Student Leave Breakdown Modal Handler
        $(document).on("click", ".student-name-link", function(e) {
            e.preventDefault();
            let regNo = $(this).data("reg-no");
            
            // Show modal and loader
            $('#studentLeaveBreakdownModal').modal('show');
            $('#leaveHistoryLoader').show();
            $('#leaveHistoryContent').hide();
            
            // Fetch student leave history
            $.ajax({
                url: "../api.php",
                type: "POST",
                data: {
                    action: "getStudentLeaveHistory",
                    reg_no: regNo
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        // Populate student info
                        $('#studentNameDisplay').text(response.student.name);
                        $('#studentRegNoDisplay').text(response.student.roll_number);
                        
                        // Populate totals
                        $('#totalApplications').text(response.totals.total_applications || 0);
                        $('#totalApproved').text(response.totals.total_approved || 0);
                        $('#totalRejected').text(response.totals.total_rejected || 0);
                        $('#totalPending').text(response.totals.total_pending || 0);
                        
                        // Populate leave type statistics
                        let leaveTypeStatsHtml = '';
                        if (response.leave_stats.length > 0) {
                            response.leave_stats.forEach(function(stat) {
                                leaveTypeStatsHtml += `
                                    <tr>
                                        <td>${stat.Leave_Type_Name}</td>
                                        <td class="text-center"><span color: black;">${stat.total_leaves}</span></td>
                                        <td class="text-center"><span color: black;">${stat.approved_count}</span></td>
                                        <td class="text-center"><span color: black;">${stat.rejected_count}</span></td>
                                        <td class="text-center"><span color: black;">${stat.pending_count}</span></td>
                                    </tr>
                                `;
                            });
                        } else {
                            leaveTypeStatsHtml = '<tr><td colspan="5" class="text-center text-muted">No leave statistics available</td></tr>';
                        }
                        $('#leaveTypeStats').html(leaveTypeStatsHtml);
                        
                        // Populate leave history
                        let leaveHistoryHtml = '';
                        if (response.leave_history.length > 0) {
                            response.leave_history.forEach(function(leave, index) {
                                let statusBadge = '';
                                let status = leave.Status || '';
                                
                                // Status badge mapping based on database enum
                                if (status === 'Approved') {
                                    statusBadge = '<center><span class="badge" style="background: #1cc88a; color: white;">Approved</span></center>';
                                } else if (['Rejected by HOD', 'Rejected by Admin', 'Rejected by Parents', 'Rejected'].includes(status)) {
                                    statusBadge = '<center><span class="badge" style="background: #e74a3b; color: white;">Rejected</span></center>';
                                } else if (status === 'Pending') {
                                    statusBadge = '<center><span class="badge" style="background: #f6c23e; color: #333;">Pending</span></center>';
                                } else if (status === 'Forwarded to Admin') {
                                    statusBadge = '<center><span class="badge" style="background: #3498db; color: white;">Forwarded</span></center>';
                                } else if (status === 'Closed') {
                                    statusBadge = '<center><span class="badge" style="background: #7f8c8d; color: white;">Closed</span></center>';
                                } else if (status === 'Out') {
                                    statusBadge = '<center><span class="badge" style="background: #9b59b6; color: white;">Out</span></center>';
                                } else if (status === 'Late Entry') {
                                    statusBadge = '<center><span class="badge" style="background: #e67e22; color: white;">Late Entry</span></center>';
                                } else {
                                    statusBadge = '<center><span class="badge" style="background: #858796; color: white;">' + status + '</span></center>';
                                }
                                
                                let appliedDate = new Date(leave.Applied_Date).toLocaleDateString('en-GB', {
                                    day: '2-digit', month: 'short', year: 'numeric'
                                });
                                let fromDate = new Date(leave.From_Date).toLocaleDateString('en-GB', {
                                    day: '2-digit', month: 'short', year: 'numeric'
                                });
                                let toDate = new Date(leave.To_Date).toLocaleDateString('en-GB', {
                                    day: '2-digit', month: 'short', year: 'numeric'
                                });
                                
                                leaveHistoryHtml += `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${leave.Leave_Type_Name}</td>
                                        <td>${appliedDate}</td>
                                        <td>${fromDate}</td>
                                        <td>${toDate}</td>
                                        <td><span>${leave.duration_days} day(s)</span></td>
                                        <td>${statusBadge}</td>
                                    </tr>
                                `;
                            });
                        } else {
                            leaveHistoryHtml = '<tr><td colspan="7" class="text-center text-muted">No leave history available</td></tr>';
                        }
                        $('#leaveHistoryTable').html(leaveHistoryHtml);
                        
                        // Hide loader and show content
                        $('#leaveHistoryLoader').hide();
                        $('#leaveHistoryContent').show();
                    } else {
                        $('#leaveHistoryLoader').hide();
                        Swal.fire({
                            title: "Error",
                            text: response.message || "Failed to load student leave history",
                            icon: "error"
                        });
                        $('#studentLeaveBreakdownModal').modal('hide');
                    }
                },
                error: function(xhr, status, error) {
                    $('#leaveHistoryLoader').hide();
                    Swal.fire({
                        title: "Error",
                        text: "Failed to fetch student leave history. Please try again.",
                        icon: "error"
                    });
                    $('#studentLeaveBreakdownModal').modal('hide');
                    console.error("AJAX Error:", status, error);
                }
            });
        });
        
        // ===================== Edit Leave Dates (Global) =====================
        function formatForDatetimeLocal(mysqlStr){
            if(!mysqlStr) return '';
            let s = mysqlStr.replace(' ', 'T');
            if (s.length >= 16) s = s.substring(0,16);
            return s;
        }

        // Open modal with current values
        $(document).on('click', '.edit-dates', function(e){
            e.preventDefault();
            const id = $(this).data('id');
            const from = $(this).data('from');
            const to = $(this).data('to');

            $('#editLeaveId').val(id);
            $('#editFromDate').val(formatForDatetimeLocal(from));
            $('#editToDate').val(formatForDatetimeLocal(to));

            $('#editDatesModal').modal('show');
        });

        // Save changes
        $(document).on('click', '#saveDateChanges', function(){
            const id = $('#editLeaveId').val();
            const fromVal = $('#editFromDate').val();
            const toVal = $('#editToDate').val();

            if(!id || !fromVal || !toVal){
                alertify.error('Please provide both From and To dates.');
                return;
            }
            const fromTs = new Date(fromVal).getTime();
            const toTs = new Date(toVal).getTime();
            if (isNaN(fromTs) || isNaN(toTs)){
                alertify.error('Invalid date value.');
                return;
            }
            if (fromTs > toTs){
                alertify.error('From date cannot be after To date.');
                return;
            }

            $.ajax({
                url: '../api.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'updateLeaveDates',
                    id: id,
                    from_date: fromVal,
                    to_date: toVal
                },
                success: function(resp){
                    if (resp.status === 'success'){
                        $('#editDatesModal').modal('hide');
                        // After API success: reload the tables alone
                        reloadTablesOnly();
                        alertify.success(resp.message || 'Leave dates updated.');
                    } else {
                        alertify.error(resp.message || 'Failed to update leave dates.');
                    }
                },
                error: function(){
                    alertify.error('Error: Failed to update leave dates.');
                }
            });
        });
        
        // ===================== Processed Stats Modal Handler =====================
        $(document).on('click', '.card-clickable', function() {
            var type = $(this).data('card-type');
            var title = $(this).data('title');
            
            // Get the breakdown data from hidden div
            var approvedData = JSON.parse($('#breakdownData').attr('data-approved') || '{}');
            var rejectedData = JSON.parse($('#breakdownData').attr('data-rejected') || '{}');

            var $modal = $('#processedBreakdownModal');
            $modal.find('.modal-title').text(title || 'Leave Type Breakdown');
            
            // Clear all breakdown containers first
            $modal.find('#processed-breakdown tbody').empty();
            $modal.find('#approved-breakdown tbody').empty();
            $modal.find('#rejected-breakdown tbody').empty();
            
            // Build the appropriate breakdown table
            if (type === 'processed') {
                var allTypes = new Set([...Object.keys(approvedData), ...Object.keys(rejectedData)]);
                var processedRows = [];
                
                allTypes.forEach(function(leaveType) {
                    var approved = parseInt(approvedData[leaveType]) || 0;
                    var rejected = parseInt(rejectedData[leaveType]) || 0;
                    var total = approved + rejected;
                    if (total > 0) {
                        processedRows.push({ type: leaveType, count: total });
                    }
                });
                
                processedRows.sort(function(a, b) { return a.type.localeCompare(b.type); });
                
                processedRows.forEach(function(row) {
                    $modal.find('#processed-breakdown tbody').append(
                        '<tr><td>' + row.type + '</td><td class="text-end fw-bold">' + row.count + '</td></tr>'
                    );
                });
                
                $modal.find('#processed-breakdown').show();
                $modal.find('#approved-breakdown').hide();
                $modal.find('#rejected-breakdown').hide();
            } else if (type === 'approved') {
                var approvedRows = [];
                Object.keys(approvedData).forEach(function(leaveType) {
                    var count = parseInt(approvedData[leaveType]) || 0;
                    if (count > 0) {
                        approvedRows.push({ type: leaveType, count: count });
                    }
                });
                
                approvedRows.sort(function(a, b) { return a.type.localeCompare(b.type); });
                
                approvedRows.forEach(function(row) {
                    $modal.find('#approved-breakdown tbody').append(
                        '<tr><td>' + row.type + '</td><td class="text-end fw-bold text-success">' + row.count + '</td></tr>'
                    );
                });
                
                $modal.find('#approved-breakdown').show();
                $modal.find('#processed-breakdown').hide();
                $modal.find('#rejected-breakdown').hide();
            } else if (type === 'rejected') {
                var rejectedRows = [];
                Object.keys(rejectedData).forEach(function(leaveType) {
                    var count = parseInt(rejectedData[leaveType]) || 0;
                    if (count > 0) {
                        rejectedRows.push({ type: leaveType, count: count });
                    }
                });
                
                rejectedRows.sort(function(a, b) { return a.type.localeCompare(b.type); });
                
                rejectedRows.forEach(function(row) {
                    $modal.find('#rejected-breakdown tbody').append(
                        '<tr><td>' + row.type + '</td><td class="text-end fw-bold text-danger">' + row.count + '</td></tr>'
                    );
                });
                
                $modal.find('#rejected-breakdown').show();
                $modal.find('#processed-breakdown').hide();
                $modal.find('#approved-breakdown').hide();
            }

            var modal = new bootstrap.Modal(document.getElementById('processedBreakdownModal'));
            modal.show();
        });
        
        // ===================== IVR Approval Functions =====================
        var currentLeaveId = null;
        var ivrCallId = null;
        var ivrSyncInterval = null;
        
        // IVR Approve Leave - Trigger actual IVR call
        $(document).on("click", "#adminApprove", function() {
            currentLeaveId = $(this).data("id");

            let row = $(this).closest('tr');
            let studentRoll = row.find('td:eq(1)').text().trim();
            let studentName = row.find('td:eq(2)').text().trim();
            let leaveType = row.find('td:eq(3)').text().trim();

            $('#studentRollNumber').text(studentRoll);
            $('#leaveType').text(leaveType);
            
            // Show confirmation before triggering IVR call
            Swal.fire({
                title: 'Trigger IVR Call?',
                html: `<p>This will call the parent of <strong>${studentName}</strong> (${studentRoll})</p>
                       <p>Parent can press:<br><b>1</b> = Approve<br><b>0</b> = Reject</p>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1cc88a',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-phone"></i> Call Parent',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    triggerIVRCall();
                }
            });
        });

        function triggerIVRCall() {
            // Show loading
            Swal.fire({
                title: 'Initiating IVR Call...',
                html: 'Calling parent for leave approval',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Call the IVR trigger API
            $.ajax({
                url: "../IVR/ivr_trigger.php",
                type: "GET",
                data: { leave_id: currentLeaveId },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        ivrCallId = response.data.ivr_call_id;
                        
                        // Reload tables immediately - leave moves to Processed with "IVR Pending" status
                        reloadTablesOnly();
                        
                        Swal.fire({
                            title: 'IVR Call Triggered!',
                            html: `<p>Calling: <strong>${response.data.contact_number}</strong></p>
                                   <p>Student: ${response.data.student_name}</p>
                                   <hr>
                                   <p class="text-info"><i class="fas fa-info-circle"></i> Leave moved to <strong>Processed</strong> tab with status <strong>IVR Pending</strong></p>
                                   <p class="text-muted">Parent will press 1 to approve or 0 to reject.</p>
                                   <p><small>Call ID: ${response.data.unique_id}</small></p>`,
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-sync"></i> Check Status',
                            cancelButtonText: 'Close',
                            confirmButtonColor: '#4e73df'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                checkIVRStatus();
                            }
                        });
                        
                    } else if (response.status === "warning") {
                        // Already has pending call
                        Swal.fire({
                            title: 'Call Already In Progress',
                            html: `<p>${response.message}</p>
                                   <p>Status: <strong>${response.call_status}</strong></p>`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-sync"></i> Check Status',
                            cancelButtonText: 'Close'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                ivrCallId = response.ivr_call_id;
                                checkIVRStatus();
                            }
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to trigger IVR call', 'error');
                    }
                },
                error: function(xhr) {
                    let msg = 'Failed to trigger IVR call';
                    try {
                        let resp = JSON.parse(xhr.responseText);
                        msg = resp.message || msg;
                    } catch(e) {}
                    Swal.fire('Error', msg, 'error');
                }
            });
        }
        
        function checkIVRStatus() {
            Swal.fire({
                title: 'Checking IVR Status...',
                html: 'Fetching call report from provider',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Call the sync API
            $.ajax({
                url: "../IVR/ivr_sync.php",
                type: "GET",
                data: ivrCallId ? { ivr_call_id: ivrCallId } : {},
                dataType: "json",
                success: function(response) {
                    if (response.status === "success" && response.results && response.results.length > 0) {
                        let result = response.results[0];
                        
                        if (result.leave_action === 'approved') {
                            Swal.fire({
                                title: 'Leave Approved!',
                                html: '<p>Parent pressed <strong>1</strong> - Leave has been approved.</p>',
                                icon: 'success'
                            });
                            reloadTablesOnly();
                            
                        } else if (result.leave_action === 'rejected') {
                            Swal.fire({
                                title: 'Leave Rejected',
                                html: '<p>Parent pressed <strong>0</strong> - Leave has been rejected.</p>',
                                icon: 'error'
                            });
                            reloadTablesOnly();
                            
                        } else if (result.call_status === 'answered' && result.dtmf) {
                            Swal.fire({
                                title: 'Call Answered',
                                html: `<p>DTMF: <strong>${result.dtmf}</strong></p><p>Duration: ${result.duration}s</p>`,
                                icon: 'info'
                            });
                            reloadTablesOnly();
                            
                        } else if (result.call_status === 'not_answered') {
                            Swal.fire({
                                title: 'Call Not Answered',
                                html: '<p>Parent did not answer the call.</p><p>You can try again or reject manually.</p>',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: '<i class="fas fa-redo"></i> Retry Call',
                                cancelButtonText: 'Close'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    triggerIVRCall();
                                }
                            });
                            
                        } else {
                            // Still pending
                            Swal.fire({
                                title: 'Call In Progress',
                                html: `<p>Status: <strong>${result.call_status || 'pending'}</strong></p>
                                       <p>Report: ${result.report || 'Waiting...'}</p>
                                       <p class="text-muted">Check again in a few seconds.</p>`,
                                icon: 'info',
                                showCancelButton: true,
                                confirmButtonText: '<i class="fas fa-sync"></i> Check Again',
                                cancelButtonText: 'Close'
                            }).then((res) => {
                                if (res.isConfirmed) {
                                    checkIVRStatus();
                                }
                            });
                        }
                    } else {
                        Swal.fire({
                            title: 'Waiting for Response',
                            html: '<p>Call report not yet available.</p><p>Try checking again in a few seconds.</p>',
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-sync"></i> Check Again',
                            cancelButtonText: 'Close'
                        }).then((res) => {
                            if (res.isConfirmed) {
                                checkIVRStatus();
                            }
                        });
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to check IVR status', 'error');
                }
            });
        }

        // Handle cancellation
        $(document).on("click", "#cancelApproval", function() {
            $('#ivrApprovalModal').modal('hide');
        });

        // Clear timer when modal is hidden
        $('#ivrApprovalModal').on('hidden.bs.modal', function() {
            if (timerInterval) {
                clearInterval(timerInterval);
            }
        });

        // IVR Reject Leave
        $(document).on("click", "#rejectApproval", function() {
            clearInterval(timerInterval);
            $('#ivrApprovalModal').modal('hide');
            attemptCount = 0;

            if (confirm("Are you sure you want to reject this leave?")) {
                $.ajax({
                    url: "../api.php",
                    type: "POST",
                    data: {
                        action: "ivrReject",
                        id: currentLeaveId
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.status === "success") {
                            // After API success: reload the tables alone
                            reloadTablesOnly();
                            alertify.success(response.message);
                        } else {
                            alertify.error('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alertify.error('Error: Failed to process request');
                    }
                });
            }
        });

        // Reject modal
        $(document).on("click", "#adminReject", function() {
            let id = $(this).data("id");
            $("#confirmReject").data("id", id);
        });

        // Confirm reject
        $(document).on("click", "#confirmReject", function() {
            let id = $(this).data("id");
            let rejectionreason = $("#rejectionReason").val().trim();

            if (!rejectionreason) {
                alertify.error("Please enter a rejection reason!");
                return;
            }

            $.ajax({
                url: "../api.php",
                type: "POST",
                data: {
                    action: "reject",
                    id: id,
                    rejectionreason: rejectionreason
                },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        $("#leaveRejectModal").modal("hide");
                        $("#rejectionReason").val('');
                        alertify.success(response.message);
                        // After API success: reload the tables alone
                        reloadTablesOnly();
                    } else {
                        alertify.error('Error: ' + response.message);
                    }
                },
                error: function() {
                    alertify.error('Error: Failed to process rejection request');
                }
            });
        });
    })
    </script>


    <!-- Proof Viewing Modal -->
    <div class="modal fade" id="viewProofModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Leave Proof</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="proofContainer"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Reject Modal -->
    <div class="modal fade" id="leaveRejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reason for Rejection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="rejectionForm">
                        <div class="mb-3">
                            <label for="rejectionReason" class="form-label">Reason for Rejection</label>
                            <textarea class="form-control" id="rejectionReason" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="confirmReject" class="btn btn-danger">Reject</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Custom Timer ivr Phone Confirmation Modal -->
    <div class="modal fade" id="ivrApprovalModal" tabindex="-1" aria-labelledby="ivrApprovalModalLabel"
        aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" style="color: #ffff;" id="ivrApprovalModalLabel">
                        IVR Approval Confirmation
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fa-solid fa-phone-volume fa-fade fa-3x text-success mb-3"></i>
                        <h6>Are you sure you want to approve this leave?</h6>
                        <p class="text-muted mb-0">Student Roll: <span id="studentRollNumber"></span></p>
                        <p class="text-muted">Leave Type: <span id="leaveType"></span></p>
                    </div>
                    <div class="alert alert-info">
                        <strong>Time remaining: <span id="countdown">25</span> seconds</strong>
                        <div class="progress mt-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning"
                                id="progressBar" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>
                    <p class="text-danger small">
                        <i class="fas fa-exclamation-triangle"></i>
                        If no action is taken, this leave will be automatically rejected after 2 attempts.
                    </p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-success btn" id="confirmApproval">
                        <i class="fas fa-check"></i> Approve IVR
                    </button>
                    <button type="button" class="btn btn-danger" id="rejectApproval">
                        <i class="fas fa-times"></i> Reject IVR
                    </button>
                    <button type="button" class="btn btn-secondary" id="cancelApproval">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>



    <!-- Processed Breakdown Modal (global) -->
    <div class="modal fade" id="processedBreakdownModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Leave Type Breakdown</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Total Processed breakdown -->
                    <div id="processed-breakdown" class="breakdown-container" style="display:none;">
                        <!-- <h6 class="text-center mb-3">Processed by Leave Type</h6> -->
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamically populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Approved breakdown -->
                    <div id="approved-breakdown" class="breakdown-container" style="display:none;">
                        <!-- <h6 class="text-center mb-3">Approved by Leave Type</h6> -->
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamically populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Rejected breakdown -->
                    <div id="rejected-breakdown" class="breakdown-container" style="display:none;">
                        <!-- <h6 class="text-center mb-3">Rejected by Leave Type</h6> -->
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamically populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Leave Breakdown Modal -->
    <div class="modal fade" id="studentLeaveBreakdownModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                         Student Leave History ( <span id="studentRegNoDisplay"></span> - <span id="studentNameDisplay"></span> )
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Loading Spinner -->
                    <div id="leaveHistoryLoader" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading leave history...</p>
                    </div>

                    <!-- Content Container -->
                    <div id="leaveHistoryContent" style="display:none;">

                        <!-- Overall Statistics -->
                        <div class="row mb-4">
                            <div class="col-xl-3 col-lg-3 col-md-6 mb-3">
                                <div class="gradient-card gradient-primary">
                                    <div class="card-body text-center">
                                        <div class="icon-container">
                                            <i class="fas fa-clipboard-list text-white"></i>
                                        </div>
                                        <h4 class="card-title text-white">Total Applications</h4>
                                        <h2 class="card-value text-white font-weight-bold pulse-value" id="totalApplications">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-md-6 mb-3">
                                <div class="gradient-card gradient-success">
                                    <div class="card-body text-center">
                                        <div class="icon-container">
                                            <i class="fas fa-check-circle text-white"></i>
                                        </div>
                                        <h4 class="card-title text-white">Approved</h4>
                                        <h2 class="card-value text-white font-weight-bold pulse-value" id="totalApproved">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-md-6 mb-3">
                                <div class="gradient-card gradient-warning">
                                    <div class="card-body text-center">
                                        <div class="icon-container">
                                            <i class="fas fa-times-circle text-white"></i>
                                        </div>
                                        <h4 class="card-title text-white">Rejected</h4>
                                        <h2 class="card-value text-white font-weight-bold pulse-value" id="totalRejected">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-md-6 mb-3">
                                <div class="gradient-card gradient-info">
                                    <div class="card-body text-center">
                                        <div class="icon-container">
                                            <i class="fas fa-clock text-white"></i>
                                        </div>
                                        <h4 class="card-title text-white">Pending</h4>
                                        <h2 class="card-value text-white font-weight-bold pulse-value" id="totalPending">0</h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Leave Type Statistics -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Leave Type Statistics</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0">
                                        <thead class="gradient-header">
                                            <tr>
                                                <th>Leave Type</th>
                                                <th class="text-center">Total</th>
                                                <th class="text-center">Approved</th>
                                                <th class="text-center">Rejected</th>
                                                <th class="text-center">Pending</th>
                                            </tr>
                                        </thead>
                                        <tbody id="leaveTypeStats">
                                            <!-- Dynamically populated -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Leave History -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-history me-2"></i>Recent Leave History (Last 10)</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0">
                                        <thead class="gradient-header">
                                            <tr>
                                                <th>S.No</th>
                                                <th>Leave Type</th>
                                                <th>Applied Date</th>
                                                <th>From</th>
                                                <th>To</th>
                                                <th>Days</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="leaveHistoryTable">
                                            <!-- Dynamically populated -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background: #f8f9fa; border-top: 1px solid rgba(0,0,0,0.1);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Leave Dates Modal (shared) -->
    <div class="modal fade" id="editDatesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Leave Dates</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editLeaveId" />
                    <div class="mb-3">
                        <label for="editFromDate" class="form-label">From</label>
                        <input type="datetime-local" class="form-control" id="editFromDate" />
                    </div>
                    <div class="mb-3">
                        <label for="editToDate" class="form-label">To</label>
                        <input type="datetime-local" class="form-control" id="editToDate" />
                    </div>
                    <!-- <small class="text-muted">Only Pending and Approved leaves can be edited.</small> -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveDateChanges">
                        <i class="fa-regular fa-floppy-disk"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>