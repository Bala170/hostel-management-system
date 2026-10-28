<?php

// export.php lives in faculty/; include db.php from project root
require_once __DIR__ . '/../db.php';

$action = strtolower($_GET['action'] ?? '');

function fetch_pending(mysqli $conn): array {
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
        while ($row = $res->fetch_assoc()) { $rows[] = $row; }
    }
    return $rows;
}

function fetch_approved(mysqli $conn): array {
    $sql = "SELECT la.*, s.name AS student_name, lt.Leave_Type_Name, r.room_number
            FROM leave_applications la
            JOIN students s ON la.Reg_No = s.roll_number
            LEFT JOIN rooms r ON s.room_id = r.room_id
            JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
            WHERE la.Status = 'Approved'
            ORDER BY la.Applied_Date DESC";
    $rows = [];
    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) { $rows[] = $row; }
    }
    return $rows;
}

function fetch_faculty_pending(mysqli $conn): array {
    $sql = "SELECT la.*, s.name AS student_name, lt.Leave_Type_Name
            FROM leave_applications la
            JOIN students s ON la.Reg_No = s.roll_number
            JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
            WHERE la.Status IN ('Pending')
              AND la.LeaveType_ID <> 1
            ORDER BY la.Applied_Date DESC";
    $rows = [];
    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) { $rows[] = $row; }
    }
    return $rows;
}

function fetch_faculty_processed(mysqli $conn): array {
    $sql = "SELECT la.*, s.name AS student_name, lt.Leave_Type_Name
            FROM leave_applications la
            JOIN students s ON la.Reg_No = s.roll_number
            JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
            WHERE la.Status IN ('Rejected by HOD','Rejected by Admin','Rejected by Parents','Approved', 'Forwarded to Admin')
            ORDER BY la.Leave_ID DESC";
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

function normalize_faculty_rows(array $rows): array {
    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'reg'      => $row['Reg_No'] ?? '',
            'name'     => $row['student_name'] ?? '',
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

function output_pdf(string $title, array $data, bool $includeRoom = true): void {
    // TCPDF folder is in project root alongside this file
    require_once __DIR__ . '/TCPDF-main/tcpdf.php';

    class LeavesReportPDF extends TCPDF {
        public $hTitle = '';
        public $leftLogoPath = '';
        public $rightLogoPath = '';
        
        public function Header() {
            // Get page width for positioning
            $pageWidth = $this->getPageWidth();
            $lMargin = $this->lMargin;
            $rMargin = $this->rMargin;
            $usableWidth = $pageWidth - $lMargin - $rMargin;
            
            // Add logos if paths are set
            if (!empty($this->leftLogoPath) && file_exists($this->leftLogoPath)) {
                $this->Image($this->leftLogoPath, $lMargin, 2, 60, 35, '', '', '', false, 300, '', false, false, 0);
            }
            if (!empty($this->rightLogoPath) && file_exists($this->rightLogoPath)) {
                $this->Image($this->rightLogoPath, $pageWidth - $rMargin - 20, 8, 20, 20, '', '', '', false, 300, '', false, false, 0);
            }
            
            // Institution header
            $this->SetFont('dejavusans', 'B', 12);
            $this->Cell(0, 6, 'M.KUMARASAMY COLLEGE OF ENGINEERING, KARUR - 639 113', 0, 1, 'C');
            $this->SetFont('dejavusans', '', 9);
            $this->Cell(0, 6, '(An Autonomous Institution Affiliated to Anna University, Chennai)', 0, 1, 'C');
            $this->Ln(2);
            $this->SetFont('dejavusans', 'B', 12);
            $this->Cell(0, 7, $this->hTitle, 0, 1, 'C');

            // Meta row (left/right)
            $this->SetFont('dejavusans', '', 9);
            $leftMeta  = 'Generated Date: ' . date('d/m/Y');
            $rightMeta = 'Report Generated by: Faculty' ;
            $this->Cell(0, 0, $leftMeta, 0, 0, 'L');
            $this->Cell(0, 0, $rightMeta, 0, 1, 'R');
            $this->Ln(3);

            // A thin line
            $this->SetLineWidth(0.3);
            $this->Line($this->GetX(), $this->GetY(), $this->getPageWidth() - $this->rMargin, $this->GetY());
            $this->Ln(3);
        }
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('dejavusans', '', 8);
            $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
        }
    }

    $pdf = new LeavesReportPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->hTitle = $title;
    
    // Set logo paths (update these paths to your actual logo files)
    $pdf->leftLogoPath = __DIR__ . '/image/mkceleft.png';
    $pdf->rightLogoPath = __DIR__ . '/image/kr.jpg';
    
    $pdf->SetCreator('Hostel Leave Portal');
    $pdf->SetAuthor('Hostel Leave Portal');
    $pdf->SetTitle($title);
    // Increase top margins and header margin to avoid any overlap between header and table
    $pdf->SetMargins(10, 42, 10);
    $pdf->SetHeaderMargin(14);
    $pdf->SetFooterMargin(12);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->SetFont('dejavusans', '', 9);
    
    // Configure TCPDF for better table rendering (uniform row height & padding)
    $pdf->setCellHeightRatio(1.15);
    $pdf->setCellPadding(1.5);
    
    $pdf->AddPage();

    // Build columns spec: single source of truth for label, width and alignment
    if ($includeRoom) {
        $cols = [
            ['key' => 'reg',     'label' => 'Register No',  'width' => '12%', 'class' => 'c'],
            ['key' => 'name',    'label' => 'Student Name', 'width' => '18%', 'class' => 'l'],
            ['key' => 'room',    'label' => 'Room No',      'width' => '7%',  'class' => 'c'],
            ['key' => 'type',    'label' => 'Leave Type',   'width' => '12%', 'class' => 'c'],
            ['key' => 'applied', 'label' => 'Applied',      'width' => '10%', 'class' => 'c'],
            ['key' => 'from',    'label' => 'From',         'width' => '10%', 'class' => 'c'],
            ['key' => 'to',      'label' => 'To',           'width' => '10%', 'class' => 'c'],
            ['key' => 'reason',  'label' => 'Reason',       'width' => '16%', 'class' => 'l'],
            ['key' => 'status',  'label' => 'Status',       'width' => '8%',  'class' => 'c'],
        ];
    } else {
        $cols = [
            ['key' => 'reg',     'label' => 'Register No',  'width' => '13%', 'class' => 'c'],
            ['key' => 'name',    'label' => 'Student Name', 'width' => '20%', 'class' => 'l'],
            ['key' => 'type',    'label' => 'Leave Type',   'width' => '13%', 'class' => 'c'],
            ['key' => 'applied', 'label' => 'Applied',      'width' => '11%', 'class' => 'c'],
            ['key' => 'from',    'label' => 'From',         'width' => '11%', 'class' => 'c'],
            ['key' => 'to',      'label' => 'To',           'width' => '11%', 'class' => 'c'],
            ['key' => 'reason',  'label' => 'Reason',       'width' => '16%', 'class' => 'l'],
            ['key' => 'status',  'label' => 'Status',       'width' => '8%',  'class' => 'c'],
        ];
    }

    // Minimal CSS TCPDF honors; single 1px grid and vertical centering
    $html = '<style>
                table { width:100%; table-layout:fixed; border-collapse:collapse; }
                th, td { border:1px solid #444; padding:4px; font-size:9pt; vertical-align:middle; word-wrap:break-word; }
                thead th { background:#0aa2a1 ; color:#fff; text-align:center; }
                .c { text-align:center; }
                .l { text-align:left; }
                th {background:#0aa2a1 !important; color:#fff; text-align:center; }
            </style>';

    // Render table header using $cols widths exactly
    $html .= '<table>';
    $html .= '<thead><tr>';
    foreach ($cols as $col) {
        $label = htmlspecialchars($col['label'], ENT_QUOTES, 'UTF-8');
        $html .= '<th width="'.$col['width'].'">'.$label.'</th>';
    }
    $html .= '</tr></thead><tbody>';

    // Render body rows using the same widths from $cols
    if (empty($data)) {
        $html .= '<tr><td class="c" colspan="'.count($cols).'">No records found.</td></tr>';
    } else {
        $i = 0;
        foreach ($data as $row) {
            $i++;
            $html .= '<tr>';
            foreach ($cols as $col) {
                $key   = $col['key'];
                $cls   = $col['class'];
                $width = $col['width'];
                $val   = isset($row[$key]) ? $row[$key] : '';
                $val   = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
                $html .= '<td class="'.$cls.'" width="'.$width.'">'.$val.'</td>';
            }
            $html .= '</tr>';
        }
    }
    $html .= '</tbody></table>';

    // Write HTML with specific settings for better rendering
    $pdf->writeHTML($html, true, false, true, false, '');
    $fname = strtolower(str_replace(' ', '_', $title)).'.pdf';
    $pdf->Output($fname, 'I');
    exit;
}

function output_excel(string $title, array $data, bool $includeRoom = true): void {
    // Generate simple HTML table with Excel headers so it downloads as .xls
    $fname = strtolower(str_replace(' ', '_', $title)).'.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename='.$fname);
    echo "\xEF\xBB\xBF"; // BOM
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1" cellspacing="0" cellpadding="4">';
    
    if ($includeRoom) {
        echo '<tr style="background:#0aa2a1;color:#fff;font-weight:bold;">'
           .     '<th>Register No</th>'
           .     '<th>Student Name</th>'
           .     '<th>Room No</th>'
           .     '<th>Leave Type</th>'
           .     '<th>Applied</th>'
           .     '<th>From</th>'
           .     '<th>To</th>'
           .     '<th>Reason</th>'
           .     '<th>Status</th>'
           . '</tr>';
        if (empty($data)) {
            echo '<tr><td colspan="9" align="center">No records found.</td></tr>';
        } else {
            foreach ($data as $row) {
                echo '<tr>'
                   . '<td>'.htmlspecialchars($row['reg']).'</td>'
                   . '<td>'.htmlspecialchars($row['name']).'</td>'
                   . '<td>'.htmlspecialchars($row['room']).'</td>'
                   . '<td>'.htmlspecialchars($row['type']).'</td>'
                   . '<td>'.htmlspecialchars($row['applied']).'</td>'
                   . '<td>'.htmlspecialchars($row['from']).'</td>'
                   . '<td>'.htmlspecialchars($row['to']).'</td>'
                   . '<td>'.htmlspecialchars($row['reason']).'</td>'
                   . '<td>'.htmlspecialchars($row['status']).'</td>'
                   . '</tr>';
            }
        }
    } else {
        echo '<tr style="background:#0aa2a1;color:#fff;font-weight:bold;">'
           .     '<th>Register No</th>'
           .     '<th>Student Name</th>'
           .     '<th>Leave Type</th>'
           .     '<th>Applied</th>'
           .     '<th>From</th>'
           .     '<th>To</th>'
           .     '<th>Reason</th>'
           .     '<th>Status</th>'
           . '</tr>';
        if (empty($data)) {
            echo '<tr><td colspan="8" align="center">No records found.</td></tr>';
        } else {
            foreach ($data as $row) {
                echo '<tr>'
                   . '<td>'.htmlspecialchars($row['reg']).'</td>'
                   . '<td>'.htmlspecialchars($row['name']).'</td>'
                   . '<td>'.htmlspecialchars($row['type']).'</td>'
                   . '<td>'.htmlspecialchars($row['applied']).'</td>'
                   . '<td>'.htmlspecialchars($row['from']).'</td>'
                   . '<td>'.htmlspecialchars($row['to']).'</td>'
                   . '<td>'.htmlspecialchars($row['reason']).'</td>'
                   . '<td>'.htmlspecialchars($row['status']).'</td>'
                   . '</tr>';
            }
        }
    }
    echo '</table></body></html>';
    exit;
}

switch ($action) {
    case 'general_pdf':
        // General Leave notifications (from general_Leave table)
        $rows = [];
        $sql = "SELECT * FROM general_Leave WHERE Is_Enabled = 0 ORDER BY GeneralLeave_ID DESC";
        if ($res = $conn->query($sql)) {
            while ($row = $res->fetch_assoc()) { $rows[] = $row; }
        }

        // Normalize rows to a simple shape for rendering
        $grows = [];
        foreach ($rows as $r) {
            $grows[] = [
                'name'   => $r['Leave_Name'] ?? '',
                'created'=> !empty($r['Created_Date']) ? date('d-m-Y H:i', strtotime($r['Created_Date'])) : '',
                'from'   => !empty($r['From_Date']) ? date('d-m-Y H:i', strtotime($r['From_Date'])) : '',
                'to'     => !empty($r['To_Date']) ? date('d-m-Y H:i', strtotime($r['To_Date'])) : '',
                'inst'   => $r['Instructions'] ?? ''
            ];
        }

        // Render PDF with the same styling as processed table exports
        (function(string $title, array $data) {
            require_once __DIR__ . '/TCPDF-main/tcpdf.php';

            if (!class_exists('LeavesReportPDF', false)) {
                class LeavesReportPDF extends TCPDF {
                    public $hTitle = '';
                    public $leftLogoPath = '';
                    public $rightLogoPath = '';
                    public function Header() {
                        $pageWidth = $this->getPageWidth();
                        $lMargin = $this->lMargin; $rMargin = $this->rMargin;
                        if (!empty($this->leftLogoPath) && file_exists($this->leftLogoPath)) {
                            $this->Image($this->leftLogoPath, $lMargin, 2, 60, 35, '', '', '', false, 300, '', false, false, 0);
                        }
                        if (!empty($this->rightLogoPath) && file_exists($this->rightLogoPath)) {
                            $this->Image($this->rightLogoPath, $pageWidth - $rMargin - 20, 8, 20, 20, '', '', '', false, 300, '', false, false, 0);
                        }
                        $this->SetFont('dejavusans', 'B', 12);
                        $this->Cell(0, 6, 'M.KUMARASAMY COLLEGE OF ENGINEERING, KARUR - 639 113', 0, 1, 'C');
                        $this->SetFont('dejavusans', '', 9);
                        $this->Cell(0, 6, '(An Autonomous Institution Affiliated to Anna University, Chennai)', 0, 1, 'C');
                        $this->Ln(2);
                        $this->SetFont('dejavusans', 'B', 12);
                        $this->Cell(0, 7, $this->hTitle, 0, 1, 'C');
                        $this->SetFont('dejavusans', '', 9);
                        $leftMeta  = 'Generated Date: ' . date('d/m/Y');
                        $rightMeta = 'Report Generated by: Admin';
                        $this->Cell(0, 0, $leftMeta, 0, 0, 'L');
                        $this->Cell(0, 0, $rightMeta, 0, 1, 'R');
                        $this->Ln(3);
                        $this->SetLineWidth(0.3);
                        $this->Line($this->GetX(), $this->GetY(), $this->getPageWidth() - $this->rMargin, $this->GetY());
                        $this->Ln(3);
                    }
                    public function Footer() {
                        $this->SetY(-15);
                        $this->SetFont('dejavusans', '', 8);
                        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
                    }
                }
            }

            $pdf = new LeavesReportPDF('L', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->hTitle = $title;
            $pdf->leftLogoPath = __DIR__ . '/image/mkceleft.png';
            $pdf->rightLogoPath = __DIR__ . '/image/kr.jpg';
            $pdf->SetCreator('Hostel Leave Portal');
            $pdf->SetAuthor('Hostel Leave Portal');
            $pdf->SetTitle($title);
            $pdf->SetMargins(10, 42, 10);
            $pdf->SetHeaderMargin(14);
            $pdf->SetFooterMargin(12);
            $pdf->SetAutoPageBreak(true, 18);
            $pdf->SetFont('dejavusans', '', 9);
            $pdf->setCellHeightRatio(1.15);
            $pdf->setCellPadding(1.5);
            $pdf->AddPage();

            // Column spec for General Leave notifications
            $cols = [
                ['key' => 'name',    'label' => 'Leave Name',   'width' => '26%', 'class' => 'l'],
                ['key' => 'created', 'label' => 'Created Date', 'width' => '16%', 'class' => 'c'],
                ['key' => 'from',    'label' => 'From',         'width' => '16%', 'class' => 'c'],
                ['key' => 'to',      'label' => 'To',           'width' => '16%', 'class' => 'c'],
                ['key' => 'inst',    'label' => 'Instructions', 'width' => '26%', 'class' => 'l'],
            ];

            $html = '<style>
                        table { width:100%; table-layout:fixed; border-collapse:collapse; }
                        th, td { border:1px solid #444; padding:4px; font-size:9pt; vertical-align:middle; word-wrap:break-word; }
                        thead th { background:#0aa2a1; color:#fff; text-align:center; }
                        .c { text-align:center; }
                        .l { text-align:left; }
                        th {background:#0aa2a1 !important; color:#fff; text-align:center; }
                    </style>';
            $html .= '<table><thead><tr>';
            foreach ($cols as $col) {
                $label = htmlspecialchars($col['label'], ENT_QUOTES, 'UTF-8');
                $html .= '<th width="'.$col['width'].'">'.$label.'</th>';
            }
            $html .= '</tr></thead><tbody>';
            if (empty($data)) {
                $html .= '<tr><td class="c" colspan="'.count($cols).'">No records found.</td></tr>';
            } else {
                foreach ($data as $row) {
                    $html .= '<tr>';
                    foreach ($cols as $col) {
                        $key = $col['key']; $cls = $col['class']; $w = $col['width'];
                        $val = isset($row[$key]) ? $row[$key] : '';
                        $val = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
                        $html .= '<td class="'.$cls.'" width="'.$w.'">'.$val.'</td>';
                    }
                    $html .= '</tr>';
                }
            }
            $html .= '</tbody></table>';

            $pdf->writeHTML($html, true, false, true, false, '');
            $fname = strtolower(str_replace(' ', '_', $title)).'.pdf';
            $pdf->Output($fname, 'I');
            exit;
        })('General Leave Notifications', $grows);
        break;
    case 'general_excel':
        // General Leave notifications export to Excel
        $rows = [];
        $sql = "SELECT * FROM general_Leave WHERE Is_Enabled = 0 ORDER BY GeneralLeave_ID DESC";
        if ($res = $conn->query($sql)) {
            while ($row = $res->fetch_assoc()) { $rows[] = $row; }
        }
        $grows = [];
        foreach ($rows as $r) {
            $grows[] = [
                'name'   => $r['Leave_Name'] ?? '',
                'created'=> !empty($r['Created_Date']) ? date('d-m-Y H:i', strtotime($r['Created_Date'])) : '',
                'from'   => !empty($r['From_Date']) ? date('d-m-Y H:i', strtotime($r['From_Date'])) : '',
                'to'     => !empty($r['To_Date']) ? date('d-m-Y H:i', strtotime($r['To_Date'])) : '',
                'inst'   => $r['Instructions'] ?? ''
            ];
        }
        $fname = 'general_leave_notifications.xls';
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $fname);
        echo "\xEF\xBB\xBF"; // BOM
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<table border="1" cellspacing="0" cellpadding="4">';
        echo '<tr style="background:#0aa2a1;color:#fff;font-weight:bold;">'
           . '<th>Leave Name</th>'
           . '<th>Created Date</th>'
           . '<th>From</th>'
           . '<th>To</th>'
           . '<th>Instructions</th>'
           . '</tr>';
        if (empty($grows)) {
            echo '<tr><td colspan="5" align="center">No records found.</td></tr>';
        } else {
            foreach ($grows as $r) {
                echo '<tr>'
                   . '<td>'.htmlspecialchars($r['name']).'</td>'
                   . '<td>'.htmlspecialchars($r['created']).'</td>'
                   . '<td>'.htmlspecialchars($r['from']).'</td>'
                   . '<td>'.htmlspecialchars($r['to']).'</td>'
                   . '<td>'.htmlspecialchars($r['inst']).'</td>'
                   . '</tr>';
            }
        }
        echo '</table></body></html>';
        exit;
        break;
    case 'pending_pdf':
        $rows = normalize_rows(fetch_pending($conn));
        output_pdf('Pending Leave Applications', $rows);
        break;
    case 'pending_excel':
        $rows = normalize_rows(fetch_pending($conn));
        output_excel('Pending Leave Applications', $rows);
        break;
    case 'approved_pdf':
        $rows = normalize_rows(fetch_approved($conn));
        output_pdf('Approved Leave Applications', $rows);
        break;
    case 'approved_excel':
        $rows = normalize_rows(fetch_approved($conn));
        output_excel('Approved Leave Applications', $rows);
        break;
    case 'faculty_pending_pdf':
        $rows = normalize_faculty_rows(fetch_faculty_pending($conn));
        output_pdf('Faculty Pending Leave Applications', $rows, false);
        break;
    case 'faculty_pending_excel':
        $rows = normalize_faculty_rows(fetch_faculty_pending($conn));
        output_excel('Faculty Pending Leave Applications', $rows, false);
        break;
    case 'faculty_processed_pdf':
        $rows = normalize_faculty_rows(fetch_faculty_processed($conn));
        output_pdf('Faculty Processed Leave Applications', $rows, false);
        break;
    case 'faculty_processed_excel':
        $rows = normalize_faculty_rows(fetch_faculty_processed($conn));
        output_excel('Faculty Processed Leave Applications', $rows, false);
        break;
    default:
        http_response_code(400);
        echo 'Invalid action. Use one of: pending_pdf, pending_excel, approved_pdf, approved_excel, faculty_pending_pdf, faculty_pending_excel, faculty_processed_pdf, faculty_processed_excel';
        exit;
}