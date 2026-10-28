<?php
session_start();
include './admin_scope.php';

$session_role = $_SESSION['role'] ?? ($_SESSION['user_type'] ?? '');
if (!is_any_admin_role($session_role)) {
    header("Location: ../login");
    exit;
}

include '../db.php';

$action = strtolower($_GET['action'] ?? '');

if ($action === 'stay_pdf') {
    function fetch_stay_requests(mysqli $conn): array
    {
        $genderScope = get_hostel_gender_scope_for_role();
        $sql = "
            SELECT
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
            LEFT JOIN hostels h ON r.hostel_id = h.hostel_id
        ";

        $q = trim((string)($_GET['q'] ?? ''));
        $where = [];

        if ($genderScope !== null) {
            $where[] = "h.gender = '" . $conn->real_escape_string($genderScope) . "'";
        }

        if ($q !== '') {
            $qEsc = $conn->real_escape_string($q);
            $where[] = "(
                        s.roll_number LIKE '%$qEsc%' OR
                        s.name LIKE '%$qEsc%' OR
                        s.department LIKE '%$qEsc%' OR
                        sr.reason LIKE '%$qEsc%' OR
                        sr.from_date LIKE '%$qEsc%' OR
                        sr.to_date LIKE '%$qEsc%'
                    )";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY sr.requested_at DESC";

        $rows = [];
        if ($res = $conn->query($sql)) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    function normalize_stay_rows(array $rows): array
    {
        $out = [];
        $sno = 1;
        foreach ($rows as $row) {
            $out[] = [
                'sno' => $sno++,
                'reg' => $row['roll_number'] ?? '',
                'name' => $row['student_name'] ?? '',
                'dept' => $row['department'] ?? '',
                'year' => (string)($row['year_of_study'] ?? ''),
                'from' => !empty($row['from_date']) ? date('d-m-Y', strtotime($row['from_date'])) : '',
                'to' => !empty($row['to_date']) ? date('d-m-Y', strtotime($row['to_date'])) : '',
                'reason' => $row['reason'] ?? '',
                'proof' => !empty($row['proof_path']) ? 'Available' : '-',
                'submitted' => !empty($row['requested_at']) ? date('d-m-Y H:i', strtotime($row['requested_at'])) : ''
            ];
        }
        return $out;
    }

    function output_pdf(string $title, array $data, array $cols, string $filterInfo = ''): void
    {
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

        class StayReportPDF extends TCPDF
        {
            public $hTitle = '';
            public $leftLogoPath = '';
            public $rightLogoPath = '';

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

        $pdf = new StayReportPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->hTitle = $title;
        $pdf->leftLogoPath = __DIR__ . '/image/mkce_logo2.jpg';
        $pdf->rightLogoPath = __DIR__ . '/image/logo-right.png';

        $pdf->SetCreator('Hostel Management System');
        $pdf->SetAuthor('Hostel Management System');
        $pdf->SetTitle($title);
        $pdf->SetMargins(10, 70, 10);
        $pdf->SetHeaderMargin(55);
        $pdf->SetFooterMargin(15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->setCellHeightRatio(1.2);
        $pdf->setCellPadding(1.8);

        $pdf->AddPage();
        $pdf->SetX(10);

        if (!empty($filterInfo)) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->SetX(10);
            $pdf->MultiCell(277, 8, 'Applied Filters: ' . $filterInfo, 0, 'L', true);
            $pdf->Ln(6);
        }

        $pdf->SetFillColor(10, 162, 161);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);

        $colWidths = [];
        $totalWidth = 277;
        $totalPercentage = 0;
        foreach ($cols as $col) {
            $widthPercent = (float)str_replace('%', '', $col['width']);
            $totalPercentage += $widthPercent;
        }
        $adjustedTotalWidth = ($totalPercentage > 100) ? $totalWidth * (100 / $totalPercentage) : $totalWidth;
        foreach ($cols as $col) {
            $widthPercent = (float)str_replace('%', '', $col['width']);
            $colWidths[] = ($widthPercent / 100) * $adjustedTotalWidth;
        }

        $pdf->SetX(10);
        foreach ($cols as $i => $col) {
            $pdf->Cell($colWidths[$i], 6, $col['label'], 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 7);

        if (empty($data)) {
            $pdf->SetX(10);
            $pdf->Cell(array_sum($colWidths), 6, 'No records found.', 1, 0, 'C', true);
            $pdf->Ln();
        } else {
            $fill = false;
            foreach ($data as $row) {
                if ($pdf->GetY() + 8 > 185) {
                    $pdf->AddPage();
                    $pdf->SetFillColor(10, 162, 161);
                    $pdf->SetTextColor(255, 255, 255);
                    $pdf->SetFont('helvetica', 'B', 8);
                    $pdf->SetX(10);
                    foreach ($cols as $i => $col) {
                        $pdf->Cell($colWidths[$i], 6, $col['label'], 1, 0, 'C', true);
                    }
                    $pdf->Ln();
                    $pdf->SetFillColor(255, 255, 255);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->SetFont('helvetica', '', 7);
                }

                $pdf->SetX(10);
                foreach ($cols as $i => $col) {
                    $val = isset($row[$col['key']]) ? (string)$row[$col['key']] : '';
                    $align = ($col['class'] === 'c') ? 'C' : (($col['class'] === 'r') ? 'R' : 'L');
                    $pdf->Cell($colWidths[$i], 5, $val, 1, 0, $align, $fill);
                }
                $pdf->Ln();
                $fill = !$fill;
            }
        }

        if (ob_get_length()) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        $fname = 'stay_in_hostel_requests_' . date('Ymd_His') . '.pdf';
        $pdf->Output($fname, 'I');
        exit;
    }

    $rows = fetch_stay_requests($conn);
    $normalized = normalize_stay_rows($rows);
    $q = trim((string)($_GET['q'] ?? ''));
    $filterInfo = $q !== '' ? 'Search: ' . $q : '';
    $cols = [
        ['key' => 'sno', 'label' => 'S.No', 'width' => '4%', 'class' => 'c'],
        ['key' => 'reg', 'label' => 'Roll No', 'width' => '8%', 'class' => 'c'],
        ['key' => 'name', 'label' => 'Student Name', 'width' => '14%', 'class' => 'l'],
        ['key' => 'dept', 'label' => 'Department', 'width' => '12%', 'class' => 'l'],
        ['key' => 'year', 'label' => 'Year', 'width' => '5%', 'class' => 'c'],
        ['key' => 'from', 'label' => 'From Date', 'width' => '9%', 'class' => 'c'],
        ['key' => 'to', 'label' => 'To Date', 'width' => '9%', 'class' => 'c'],
        ['key' => 'reason', 'label' => 'Reason', 'width' => '26%', 'class' => 'l'],
        ['key' => 'proof', 'label' => 'Proof', 'width' => '6%', 'class' => 'c'],
        ['key' => 'submitted', 'label' => 'Submitted At', 'width' => '12%', 'class' => 'c']
    ];
    output_pdf('Stay In Hostel Requests', $normalized, $cols, $filterInfo);
}
?>
<!DOCTYPE html>
<html lang="en">
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
        }

        .content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
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

        .stats-card {
            border: none;
            border-radius: 12px;
            color: #fff;
            box-shadow: var(--card-shadow);
        }

        .stats-total {
            background: linear-gradient(135deg, #4e73df, #224abe);
        }

        .stats-today {
            background: linear-gradient(135deg, #1cc88a, #13855c);
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
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card stats-card stats-total">
                    <div class="card-body">
                        <h6 class="mb-1">Total Requests</h6>
                        <h4 class="mb-0" id="countTotal">0</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stats-card stats-today">
                    <div class="card-body">
                        <h6 class="mb-1">Today Requests</h6>
                        <h4 class="mb-0" id="countToday">0</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <div class="d-flex justify-content-end mb-2">
                    <a href="?action=stay_pdf" data-base="?action=stay_pdf" target="_blank" class="btn btn-danger btn-sm export-stay-pdf">
                        <i class="fa-solid fa-file-pdf"></i> Export PDF
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped w-100" id="stayTable">
                        <thead class="gradient-header">
                        <tr>
                            <th>S.No</th>
                            <th>Roll No</th>
                            <th>Student Name</th>
                            <th>Department</th>
                            <th>Year</th>
                            <th>From Date</th>
                            <th>To Date</th>
                            <th>Reason</th>
                            <th>Proof</th>
                            <th>Submitted At</th>
                        </tr>
                        </thead>
                        <tbody id="stayBody"></tbody>
                    </table>
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

    <?php include '../assets/footer.php'; ?>
</div>

<script>
    let stayTable = null;
    let stayRefreshTimer = null;
    let proofModal = null;

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

    function loadCounts() {
        $.ajax({
            url: '../api.php',
            type: 'GET',
            dataType: 'json',
            cache: false,
            data: {
                action: 'get_stay_hostel_counts',
                _ts: Date.now()
            },
            success: function (res) {
                if (!res.success) return;
                const c = res.counts || {};
                $('#countTotal').text(c.total || 0);
                $('#countToday').text(c.today || 0);
            }
        });
    }

    function loadRequests() {
        $.ajax({
            url: '../api.php',
            type: 'GET',
            dataType: 'json',
            cache: false,
            data: {
                action: 'get_admin_stay_hostel_requests',
                _ts: Date.now()
            },
            success: function (res) {
                if (!res.success) {
                    return;
                }

                const rows = [];
                (res.rows || []).forEach((row, index) => {
                    rows.push([
                        index + 1,
                        escHtml(row.roll_number),
                        escHtml(row.student_name),
                        escHtml(row.department),
                        escHtml(row.year_of_study),
                        escHtml(row.from_date),
                        escHtml(row.to_date),
                        escHtml(row.reason),
                        buildProofCell(row.proof_path),
                        fmtDateTime(row.requested_at)
                    ]);
                });

                if (!stayTable) {
                    stayTable = $('#stayTable').DataTable({
                        pageLength: 10,
                        responsive: true,
                        order: [[9, 'desc']]
                    });
                }

                stayTable.clear();
                stayTable.rows.add(rows).draw(false);
            },
            error: function () {
                // Keep silent to avoid repeated popup during background refresh.
            }
        });
    }

    function buildExportUrl(base) {
        try {
            if (!stayTable) return base;
            const q = stayTable.search() || '';
            return q ? (base + '&q=' + encodeURIComponent(q)) : base;
        } catch (e) {
            return base;
        }
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

        loadCounts();
        loadRequests();
        stayRefreshTimer = setInterval(function () {
            loadCounts();
            loadRequests();
        }, 10000);

        $(document).on('click', '.export-stay-pdf', function () {
            const base = $(this).data('base') || $(this).attr('href');
            $(this).attr('href', buildExportUrl(base));
        });

        $(document).on('click', '.view-proof-btn', function () {
            const path = $(this).attr('data-proof') || '';
            openProofModal(path);
        });

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                loadCounts();
                loadRequests();
            }
        });

        $(window).on('beforeunload', function () {
            if (stayRefreshTimer) {
                clearInterval(stayRefreshTimer);
                stayRefreshTimer = null;
            }
        });
    });
</script>
</body>
</html>
