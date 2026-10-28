<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login');
    exit();
}
if (isset($_SESSION['user_id'])) {
    error_log("Session User ID: " . $_SESSION['user_id']);
} else {
    error_log("Session User ID is NOT set.");

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hostel Management </title>

<link rel="icon" type="image/png" sizes="32x32" href="../images/icons/mkce_s.png">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-5/bootstrap-5.css" rel="stylesheet">

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
        font-family: 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #e7e9f3ff 0%, #f9f9faff 100%);
        margin: 0;
        padding: 0;
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

    /* Breadcrumb Area - Styled like leave_apply.php's card base */
    .breadcrumb-area {
        background: white; /* Cleaner white background */
        border-radius: 10px;
        box-shadow: var(--card-shadow);
        margin: 20px;
        padding: 15px 20px;
    }

    /* Use the custom gradient background for the breadcrumb as a highlight */
    .breadcrumb-area.custom-gradient {
        background-image: linear-gradient(to top, #fff1eb 0%, #ace0f9 100%);
        border-radius: 10px;
        box-shadow: var(--card-shadow);
    }


    .breadcrumb-item a {
        color: var(--primary-color);
        text-decoration: none;
        transition: var(--transition);
    }

    .breadcrumb-item a:hover {
        color: #224abe;
    }

    /* Loader Styles (from leave_apply.php) */
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
    
    .sidebar.collapsed + .content .loader-container {
        left: var(--sidebar-collapsed-width);
    }
    
    .loader-container.hide {
        display: none;
    }
    
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
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Attendance-Specific Styles (Retained) */

    .nav-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 90%;
        max-width: 900px;
        margin: 0 auto 10px auto;
    }

    .nav-title {
        font-size: 20px;
        font-weight: bold;
        color: #343a40;
    }

    .nav-bar a {
        text-decoration: none;
        padding: 8px 15px;
        background: var(--primary-color);
        color: white;
        border-radius: 6px;
        transition: 0.3s;
    }

    .nav-bar a:hover {
        background: #2e59d9;
    }

    .calendar {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
        width: 95%;
        max-width: 900px;
        margin: auto;
    }

    .day-header {
        background: #343a40;
        color: white;
        padding: 8px;
        text-align: center;
        font-weight: bold;
        border-radius: 6px;
    }

    .day {
        min-height: 80px;
        padding: 5px;
        border-radius: 8px;
        text-align: center;
        font-size: 14px;
        color: #000;
        display: flex;
        flex-direction: column;
        justify-content: center;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease-in-out;
    }

    .day strong {
        font-size: 18px; /* Enhanced font size */
    }

    .day:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        z-index: 10;
    }

    .today {
        border: 3px solid #ff9800;
        box-shadow: 0 0 12px #ff9800;
    }

    .legend {
        width: 95%;
        max-width: 900px;
        margin: 20px auto;
        display: flex;
        justify-content: space-around;
        flex-wrap: wrap;
    }

    .legend div {
        display: flex;
        align-items: center;
        margin: 5px 10px;
    }

    .legend span {
        display: inline-block;
        width: 20px;
        height: 20px;
        margin-right: 6px;
        border-radius: 44px;
    }
    
    /* Responsive Styles (Adjusted for consistency) */
    @media (max-width: 768px) {
        .sidebar { transform: translateX(-100%); width: var(--sidebar-width) !important; }
        .sidebar.mobile-show { transform: translateX(0); }
        .topbar, .footer { left: 0 !important; }
        body.sidebar-open { overflow: hidden; }
        .day { min-height: 60px; font-size: 12px; }
        .day strong { font-size: 14px; }
        .nav-title { font-size: 16px; }
        .nav-bar a { padding: 6px 10px; font-size: 13px; }
        .content-nav ul { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 5px; }
        .content-nav ul::-webkit-scrollbar { height: 4px; }
        .content-nav ul::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.3); border-radius: 2px; }
        .content { margin-left: 0 !important; padding-top: 80px; }
        .loader-container { left: 0; }
    }
    .container-fluid {
        padding: 20px;
    }

</style>
</head>
<body>
<?php include '../assets/sidebar.php'; ?>
<?php include '../assets/topbar.php'; ?>
<?php include '../assets/footer.php'; ?>
<div id="sidebar"></div>
<div class="content">
<div id="topbar"></div>

    <div class="breadcrumb-area custom-gradient">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Attendance View</li>
            </ol>
        </nav>
    </div>
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-black">
                <i class="fas fa-calendar-alt me-2"></i> Monthly Attendance Record
            </h6>
        </div>

        <div class="card-body">
            <div class="nav-bar mb-3">
                <button id="prevBtn" class="btn btn-primary"><i class="fas fa-chevron-left"></i> Previous</button>
                <div class="nav-title" id="monthTitle"></div>
                <button id="nextBtn" class="btn btn-primary">Next <i class="fas fa-chevron-right"></i></button>
            </div>

            <div class="calendar" id="calendar"></div>

           
        </div>
    </div>

    <!-- Detailed Attendance Records with Reason -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-black">
                <i class="fas fa-list me-2"></i> Detailed Attendance Records
            </h6>
        </div>

        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Reason</th>
                        <th>Marked At</th>
                    </tr>
                </thead>
                <tbody id="attendanceTableBody">
                    <tr>
                        <td colspan="4" class="text-center text-muted">Loading attendance records...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>

<script>
let month = new Date().getMonth() + 1;
let year = new Date().getFullYear();

// Load attendance from backend
function loadAttendance() {
    $.ajax({
        url: "../api.php",
        method: "POST",
        data: {
            action: "loadAttendance",
            month: month,
            year: year
        },
        dataType: "json",

        success: function(response) {
            console.log('Response received:', response);
            // Handle error response from backend (e.g., if user not logged in)
            if (response.success === false) {
                Swal.fire({
                    icon: 'error',
                    title: 'Authentication Error',
                    text: response.message || 'Could not fetch attendance. Please log in.',
                });
                $("#monthTitle").text("Attendance Error");
                $("#calendar").html("<p class='text-danger'>" + (response.message || "Attendance data could not be loaded.") + "</p>");
                return;
            }

            $("#monthTitle").text(response.month_text);

            let calendar = $("#calendar");
            calendar.html("");

            let daysOfWeek = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
            daysOfWeek.forEach(day => {
                calendar.append(`<div class='day-header'>${day}</div>`);
            });

            for (let i = 0; i < response.first_day; i++)
                calendar.append("<div></div>");

            response.days.forEach(function(d){
                calendar.append(`
                    <div class='day ${d.is_today ? "today" : ""}' style='background:${d.color}'>
                        <strong>${d.day}</strong>
                        <small>${d.status}</small>
                    </div>
                `);
            });
            
            // Load detailed attendance records
            loadDetailedAttendance(month, year);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Response:', xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Error Loading Attendance',
                text: 'Failed to load attendance data. Please try again. Error: ' + error,
            });
        }
    });
}

// Load detailed attendance records with reason
function loadDetailedAttendance(month, year) {
    $.ajax({
        url: "../api.php",
        method: "POST",
        data: {
            action: "loadDetailedAttendance",
            month: month,
            year: year
        },
        dataType: "json",
        success: function(response) {
            console.log('Detailed attendance response:', response);
            const tbody = $('#attendanceTableBody');
            tbody.empty();
            
            if (response.success && response.data && response.data.length > 0) {
                // Sort by date descending
                response.data.sort((a, b) => new Date(b.date) - new Date(a.date));
                
                response.data.forEach(record => {
                    let statusBadge = '';
                    const status = record.status;
                    
                    if (status === 'Present') {
                        statusBadge = '<span class="badge bg-success">Present</span>';
                    } else if (status === 'Absent') {
                        statusBadge = '<span class="badge bg-danger">Absent</span>';
                    } else if (status === 'On Leave') {
                        statusBadge = '<span class="badge bg-info">On Leave</span>';
                    } else if (status === 'Late Entry') {
                        statusBadge = '<span class="badge bg-warning text-dark">Late Entry</span>';
                    } else {
                        statusBadge = '<span class="badge bg-secondary">Not Marked</span>';
                    }
                    
                    const row = `
                        <tr>
                            <td>${record.date}</td>
                            <td>${statusBadge}</td>
                            <td>${record.reason ? record.reason : (status === 'Present' || status === 'Late Entry' ? 'N/A' : '-')}</td>
                            <td>${record.marked_at ? record.marked_at : '-'}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            } else {
                tbody.append('<tr><td colspan="4" class="text-center text-muted">No attendance records found</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading detailed attendance:', error);
            $('#attendanceTableBody').empty().append('<tr><td colspan="4" class="text-center text-danger">Error loading attendance records</td></tr>');
        }
    });
}


// Navigation
$("#prevBtn").click(function(){
    month--;
    if(month < 1){ month = 12; year--; }
    loadAttendance();
});
$("#nextBtn").click(function(){
    month++;
    if(month > 12){ month = 1; year++; }
    loadAttendance();
});

// Initial Load
loadAttendance();
</script>

</body>
</html>