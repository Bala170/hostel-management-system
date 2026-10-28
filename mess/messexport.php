<?php
session_start();
require_once dirname(__DIR__) . '/admin/admin_scope.php';

function is_mess_export_access_role(): bool {
    $role = $_SESSION['role'] ?? ($_SESSION['user_type'] ?? '');
    return in_array($role, ['mess_supervisor', 'admin', 'male_admin', 'female_admin'], true);
}

if (!is_mess_export_access_role()) {
    http_response_code(403);
    die('Unauthorized');
}

$messScopeGender = get_hostel_gender_scope_for_role();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/mess_export_errors.log');

// Log the start of the script
error_log("Starting messexport.php with parameters: " . print_r($_GET, true));

// Clean any output buffer before PDF generation
if (ob_get_length()) {
    ob_end_clean();
}
ob_start();

// Try to include TCPDF from different possible locations
$tcpdf_paths = [
    dirname(__DIR__) . '/TCPDF/tcpdf.php',
    dirname(__DIR__) . '/TCPDF-main/tcpdf.php',
    'TCPDF/tcpdf.php'
];

$tcpdf_loaded = false;
foreach ($tcpdf_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $tcpdf_loaded = true;
        break;
    }
}

if (!$tcpdf_loaded) {
    $error_message = 'Error: TCPDF library not found. Tried paths: ' . print_r($tcpdf_paths, true);
    error_log($error_message);
    die($error_message);
} else {
    error_log('TCPDF library loaded successfully');
}

// Include database connection
require_once '../db.php';

// Get database connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    $error_message = "Database connection failed: " . $conn->connect_error;
    error_log($error_message);
    die($error_message);
} else {
    error_log('Database connection successful');
}

$conn->set_charset("utf8mb4");

// Get parameters
$type = $_GET['type'] ?? '';
$filterMonth = $_GET['filter_month'] ?? '';
$filterDate = $_GET['filter_date'] ?? '';
$filterMealType = $_GET['filter_meal_type'] ?? '';
$filterItem = $_GET['filter_item'] ?? '';

// Create new PDF document
class MYPDF extends TCPDF {
    public function Header() {
        // College name
        $collegeName = 'M.Kumarasamy College Of Engineering, Karur - 639 113';
        $subTitle = '(An Autonomous Institution Affiliated to Anna University, Chennai)';
        
        // Left logo - try different formats
        $basePath = dirname(__FILE__) . '/../images/';
        $leftLogoFiles = [
            ['path' => $basePath . 'mkce_logo2.jpg', 'type' => 'JPG'],
            ['path' => $basePath . 'mkce.png', 'type' => 'PNG'],
            ['path' => $basePath . 'mkceleft.png', 'type' => 'PNG']
        ];
        
        $leftLogo = null;
        foreach ($leftLogoFiles as $logo) {
            if (file_exists($logo['path'])) {
                $leftLogo = $logo;
                break;
            }
        }
        
        // Right logo - kr.jpg
        $rightLogoFiles = [
            ['path' => $basePath . 'kr.jpg', 'type' => 'JPG'],
            ['path' => $basePath . 'logo-right.png', 'type' => 'PNG']
        ];
        
        $rightLogo = null;
        foreach ($rightLogoFiles as $logo) {
            if (file_exists($logo['path'])) {
                $rightLogo = $logo;
                break;
            }
        }
        
        // Display left logo if exists (positioned at left)
        if ($leftLogo !== null) {
            try {
                // Use '' as the image type to let TCPDF auto-detect
                @$this->Image($leftLogo['path'], 10, 8, 23, 25, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
            } catch (Exception $e) {
                // If image fails, continue without logo
            }
        }
        
        // Display right logo (kr.jpg) if exists (positioned at right)
        if ($rightLogo !== null) {
            try {
                // Use '' as the image type to let TCPDF auto-detect
                @$this->Image($rightLogo['path'], 182, 13, 18, 18, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
            } catch (Exception $e) {
                // If image fails, continue without logo
            }
        }
        
        // College name - centered between logos
        $this->SetFont('helvetica', 'B', 13);
        $this->SetY(10);
        $this->SetX(40); // Start after left logo
        $this->Cell(130, 10, $collegeName, 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        
        // Reduce vertical spacing between college name and subtitle
        $this->Ln(0.5);
        
        // Subtitle - centered
        $this->SetFont('helvetica', 'I', 8);
        $this->SetX(40);
        $this->Cell(130, 9, $subTitle, 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        
        // Set starting Y position for content
        $this->SetY(20);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'R', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Hostel Management System');
$pdf->SetAuthor('M.Kumarasamy College of Engineering, Karur');
$pdf->SetTitle('Mess Report');

// Set margins
$pdf->SetMargins(15, 38, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 20);

$pdf->AddPage();

// Generate report based on type
switch ($type) {
    case 'token_requests':
        generateTokenRequestsReport($pdf, $conn, $filterMonth, $filterDate, $filterMealType, $filterItem);
        break;
    case 'revenue':
        generateRevenueReport($pdf, $conn, $filterMonth);
        break;
    case 'consumption':
        generateConsumptionReport($pdf, $conn, $filterMonth);
        break;
    default:
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Invalid report type', 0, 1, 'C');
        break;
}

// Close and output PDF
ob_end_clean(); // Clear any buffered output
$pdf->Output('Mess_Report_' . date('Y-m-d_His') . '.pdf', 'D');

// Close database connection safely
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
exit(); // Ensure no output after PDF

// Report generation functions
function generateTokenRequestsReport($pdf, $conn, $filterMonth, $filterDate, $filterMealType, $filterItem) {
    global $messScopeGender;
    // Further reduce spacing between subtitle and report title
    $pdf->SetY(22);
    
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'SPECIAL TOKEN REQUESTS REPORT', 0, 1, 'C');
    
    // Add report header information
    $generatedDate = date('d/m/Y');
    $generatedBy = 'Mess Supervisor'; // Changed from 'Admin' to 'Mess Supervisor'
    
    $pdf->SetFont('helvetica', 'R', 10);
    $pdf->Cell(95, 10, 'Generated Date: ' . $generatedDate, 0, 0, 'L');
    $pdf->Cell(95, 10, 'Generated by: ' . $generatedBy, 0, 1, 'R');
    
    // Add a horizontal line
    $pdf->Line(15, $pdf->GetY(), 205, $pdf->GetY());
    $pdf->Ln(2);
    
    // Filter info
    $pdf->SetFont('helvetica', '', 9);
    $filterInfo = 'Filters: ';
    if (!empty($filterDate)) {
        $filterInfo .= 'Date: ' . date('d-M-Y', strtotime($filterDate)) . ' | ';
    } elseif (!empty($filterMonth)) {
        $filterInfo .= 'Month: ' . date('F Y', strtotime($filterMonth . '-01')) . ' | ';
    }
    if (!empty($filterMealType)) {
        $filterInfo .= 'Meal Type: ' . $filterMealType . ' | ';
    }
    if (!empty($filterItem)) {
        $filterInfo .= 'Item: ' . $filterItem . ' | ';
    }
    $pdf->Cell(0, 6, rtrim($filterInfo, ' | '), 0, 1, 'L');
    $pdf->Ln(3);
    
    // Build query
    $sql = "SELECT 
                mt.roll_number, 
                COALESCE(s.name, 'Unknown') as student_name, 
                mt.meal_type, 
                COALESCE(st.menu_items, mt.menu) as menu_items, 
                COALESCE(mt.special_fee, 0) as fee, 
                mt.token_date, 
                DATE_FORMAT(mt.created_at, '%d-%m-%Y %h:%i %p') as requested_at 
            FROM mess_tokens mt 
            LEFT JOIN students s ON mt.roll_number = s.roll_number 
            LEFT JOIN specialtokenenable st ON mt.menu_id = st.menu_id 
            WHERE mt.token_type = 'Special'";

    $params = [];
    $types = '';

    if ($messScopeGender !== null) {
        $sql .= " AND s.gender = ?";
        $params[] = $messScopeGender;
        $types .= 's';
    }
    
    if (!empty($filterDate)) {
        $sql .= " AND mt.token_date = ?";
        $params[] = $filterDate;
        $types .= 's';
    } elseif (!empty($filterMonth)) {
        $sql .= " AND DATE_FORMAT(mt.token_date, '%Y-%m') = ?";
        $params[] = $filterMonth;
        $types .= 's';
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
    
    try {
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($sql);
            if ($result === false) {
                throw new Exception("Query failed: " . $conn->error);
            }
        }
    } catch (Exception $e) {
        // If there's an error with the complex query, fall back to a simpler one
        $sql = "SELECT mt.roll_number, 'Unknown' as student_name, mt.meal_type, mt.menu as menu_items, COALESCE(mt.special_fee, 0) as fee, mt.token_date, DATE_FORMAT(mt.created_at, '%d-%m-%Y %h:%i %p') as requested_at FROM mess_tokens mt WHERE mt.token_type = 'Special' ORDER BY mt.token_date DESC, mt.created_at DESC LIMIT 1000";
        $result = $conn->query($sql);
    }
    
    // Table header - centered
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(76, 175, 80);
    $pdf->SetTextColor(255, 255, 255);
    
    // Center the table on the page
    $pdf->SetX(15); // Start from left margin
    $pdf->Cell(10, 8, 'S.No', 1, 0, 'C', 1);
    $pdf->Cell(25, 8, 'Roll No.', 1, 0, 'C', 1);
    $pdf->Cell(35, 8, 'Student Name', 1, 0, 'C', 1);
    $pdf->Cell(20, 8, 'Meal Type', 1, 0, 'C', 1);
    $pdf->Cell(50, 8, 'Menu Items', 1, 0, 'C', 1);
    $pdf->Cell(15, 8, 'Fee', 1, 0, 'C', 1);
    $pdf->Cell(25, 8, 'Token Date', 1, 1, 'C', 1);
    
    // Table data - centered
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetX(15); // Start from left margin
    $sno = 1;
    $totalFee = 0;
    
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(10, 7, $sno++, 1, 0, 'C');
        $pdf->Cell(25, 7, $row['roll_number'], 1, 0, 'L');
        $pdf->Cell(35, 7, substr($row['student_name'], 0, 20), 1, 0, 'L');
        $pdf->Cell(20, 7, $row['meal_type'], 1, 0, 'C');
        $pdf->Cell(50, 7, substr($row['menu_items'], 0, 35), 1, 0, 'L');
        $pdf->Cell(15, 7, 'Rs.' . number_format($row['fee'], 2), 1, 0, 'R');
        $pdf->Cell(25, 7, date('d-M-Y', strtotime($row['token_date'])), 1, 1, 'C');
        $totalFee += $row['fee'];
        $pdf->SetX(15); // Start from left margin for each row
    }
    
    // Total - centered
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetX(15); // Start from left margin
    $pdf->Cell(140, 7, 'TOTAL', 1, 0, 'R');
    $pdf->Cell(15, 7, 'Rs.' . number_format($totalFee, 2), 1, 0, 'R');
    $pdf->Cell(25, 7, '', 1, 1, 'C');
    
    if (!empty($params)) {
        $stmt->close();
    }
}

function generateRevenueReport($pdf, $conn, $filterMonth) {
    global $messScopeGender;
    $month = !empty($filterMonth) ? $filterMonth : date('Y-m');
    
    // Further reduce spacing between subtitle and report title
    $pdf->SetY(22);
    
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'MONTHLY REVENUE REPORT', 0, 1, 'C');
    
    // Add report header information
    $generatedDate = date('d/m/Y');
    $generatedBy = 'Mess Supervisor'; // Changed from 'Admin' to 'Mess Supervisor'
    
    $pdf->SetFont('helvetica', 'R', 10);
    $pdf->Cell(95, 10, 'Generated Date: ' . $generatedDate, 0, 0, 'L');
    $pdf->Cell(95, 10, 'Generated by: ' . $generatedBy, 0, 1, 'R');
    
    // Add a horizontal line
    $pdf->Line(15, $pdf->GetY(), 205, $pdf->GetY());
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Month: ' . date('F Y', strtotime($month . '-01')), 0, 1, 'L');
    $pdf->Ln(3);
    
    $sql = "SELECT mt.token_date as date, COUNT(mt.token_id) as tokens_count, COALESCE(SUM(mt.special_fee), 0) as revenue 
            FROM mess_tokens mt 
            LEFT JOIN students s ON mt.roll_number = s.roll_number
            WHERE mt.token_type = 'Special' AND DATE_FORMAT(mt.token_date, '%Y-%m') = ?";
    if ($messScopeGender !== null) {
        $sql .= " AND s.gender = '" . $conn->real_escape_string($messScopeGender) . "'";
    }
    $sql .= " 
            GROUP BY mt.token_date 
            ORDER BY mt.token_date DESC";

    try {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $month);
        $stmt->execute();
        $result = $stmt->get_result();
    } catch (Exception $e) {
        // Fallback query without prepared statement
        $sql = "SELECT mt.token_date as date, COUNT(mt.token_id) as tokens_count, COALESCE(SUM(mt.special_fee), 0) as revenue 
            FROM mess_tokens mt 
            LEFT JOIN students s ON mt.roll_number = s.roll_number
            WHERE mt.token_type = 'Special' AND DATE_FORMAT(mt.token_date, '%Y-%m') = '$month'";
        if ($messScopeGender !== null) {
            $sql .= " AND s.gender = '" . $conn->real_escape_string($messScopeGender) . "'";
        }
        $sql .= " 
            GROUP BY mt.token_date 
            ORDER BY mt.token_date DESC";
        $result = $conn->query($sql);
    }
    
    // Table header - centered
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(33, 150, 243);
    $pdf->SetTextColor(255, 255, 255);
    
    // Center the table on the page
    $pdf->SetX(15); // Start from left margin
    $pdf->Cell(20, 8, 'S.No', 1, 0, 'C', 1);
    $pdf->Cell(50, 8, 'Date', 1, 0, 'C', 1);
    $pdf->Cell(50, 8, 'Tokens Count', 1, 0, 'C', 1);
    $pdf->Cell(60, 8, 'Revenue (Rs.)', 1, 1, 'C', 1);
    
    // Table data - centered
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetX(15); // Start from left margin
    $sno = 1;
    $totalRevenue = 0;
    $totalTokens = 0;
    
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(20, 7, $sno++, 1, 0, 'C');
        $pdf->Cell(50, 7, date('d-M-Y', strtotime($row['date'])), 1, 0, 'C');
        $pdf->Cell(50, 7, $row['tokens_count'], 1, 0, 'C');
        $pdf->Cell(60, 7, 'Rs.' . number_format($row['revenue'], 2), 1, 1, 'R');
        $totalRevenue += $row['revenue'];
        $totalTokens += $row['tokens_count'];
        $pdf->SetX(15); // Start from left margin for each row
    }
    
    // Total - centered
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetX(15); // Start from left margin
    $pdf->Cell(70, 8, 'TOTAL', 1, 0, 'R');
    $pdf->Cell(50, 8, $totalTokens, 1, 0, 'C');
    $pdf->Cell(60, 8, 'Rs.' . number_format($totalRevenue, 2), 1, 1, 'R');
    
    $stmt->close();
}

function generateConsumptionReport($pdf, $conn, $filterMonth) {
    global $messScopeGender;
    $month = !empty($filterMonth) ? $filterMonth : date('Y-m');
    
    // Further reduce spacing between subtitle and report title
    $pdf->SetY(22);
    
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'STUDENT CONSUMPTION REPORT', 0, 1, 'C');
    
    // Add report header information
    $generatedDate = date('d/m/Y');
    $generatedBy = 'Mess Supervisor'; // Changed from 'Admin' to 'Mess Supervisor'
    
    $pdf->SetFont('helvetica', 'R', 10);
    $pdf->Cell(95, 10, 'Generated Date: ' . $generatedDate, 0, 0, 'L');
    $pdf->Cell(95, 10, 'Generated by: ' . $generatedBy, 0, 1, 'R');
    
    // Add a horizontal line
    $pdf->Line(15, $pdf->GetY(), 205, $pdf->GetY());
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Month: ' . date('F Y', strtotime($month . '-01')), 0, 1, 'L');
    $pdf->Ln(3);
    
    $sql = "SELECT mt.roll_number, COALESCE(s.name, 'Unknown') as student_name, COUNT(mt.token_id) as tokens_count, COALESCE(SUM(mt.special_fee), 0) as total_spent 
            FROM mess_tokens mt 
            LEFT JOIN students s ON mt.roll_number = s.roll_number 
            WHERE mt.token_type = 'Special' AND DATE_FORMAT(mt.token_date, '%Y-%m') = ?";
    if ($messScopeGender !== null) {
        $sql .= " AND s.gender = '" . $conn->real_escape_string($messScopeGender) . "'";
    }
    $sql .= " 
            GROUP BY mt.roll_number, s.name 
            ORDER BY total_spent DESC 
            LIMIT 1000";

    try {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $month);
        $stmt->execute();
        $result = $stmt->get_result();
    } catch (Exception $e) {
        // Fallback query without prepared statement
        $sql = "SELECT mt.roll_number, COALESCE(s.name, 'Unknown') as student_name, COUNT(mt.token_id) as tokens_count, COALESCE(SUM(mt.special_fee), 0) as total_spent 
            FROM mess_tokens mt 
            LEFT JOIN students s ON mt.roll_number = s.roll_number 
            WHERE mt.token_type = 'Special' AND DATE_FORMAT(mt.token_date, '%Y-%m') = '$month'";
        if ($messScopeGender !== null) {
            $sql .= " AND s.gender = '" . $conn->real_escape_string($messScopeGender) . "'";
        }
        $sql .= " 
            GROUP BY mt.roll_number, s.name 
            ORDER BY total_spent DESC 
            LIMIT 1000";
        $result = $conn->query($sql);
    }
    
    // Table header - centered
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(255, 152, 0);
    $pdf->SetTextColor(255, 255, 255);
    
    // Center the table on the page
    $pdf->SetX(15); // Start from left margin
    $pdf->Cell(15, 8, 'S.No', 1, 0, 'C', 1);
    $pdf->Cell(40, 8, 'Roll Number', 1, 0, 'C', 1);
    $pdf->Cell(65, 8, 'Student Name', 1, 0, 'C', 1);
    $pdf->Cell(30, 8, 'Tokens', 1, 0, 'C', 1);
    $pdf->Cell(30, 8, 'Total Spent (Rs.)', 1, 1, 'C', 1);
    
    // Table data - centered
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetX(15); // Start from left margin
    $sno = 1;
    $totalSpent = 0;
    $totalTokens = 0;
    
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(15, 7, $sno++, 1, 0, 'C');
        $pdf->Cell(40, 7, $row['roll_number'], 1, 0, 'L');
        $pdf->Cell(65, 7, substr($row['student_name'], 0, 35), 1, 0, 'L');
        $pdf->Cell(30, 7, $row['tokens_count'], 1, 0, 'C');
        $pdf->Cell(30, 7, 'Rs.' . number_format($row['total_spent'], 2), 1, 1, 'R');
        $totalSpent += $row['total_spent'];
        $totalTokens += $row['tokens_count'];
        $pdf->SetX(15); // Start from left margin for each row
    }
    
    // Total - centered
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetX(15); // Start from left margin
    $pdf->Cell(120, 8, 'TOTAL', 1, 0, 'R');
    $pdf->Cell(30, 8, $totalTokens, 1, 0, 'C');
    $pdf->Cell(30, 8, 'Rs.' . number_format($totalSpent, 2), 1, 1, 'R');
    
    $stmt->close();
}
?>
