<?php
// dashboard.php

// 1. Session and Security Check
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? $_SESSION['user_type'] ?? 'student') !== 'student') {
    // This part is crucial for production. For local testing, ensure your API sets a mock session ID.
    header("Location: ../login"); 
    exit;
}

if (isset($_SESSION['user_id'])) {
    error_log("Session User ID: " . $_SESSION['user_id']);
} else {
    error_log("Session User ID is NOT set. Dashboard will rely on mock data or fail.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Management</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../images/icons/mkce_s.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="stu-mess.css"> 
<style>
        /* Additional dashboard-specific styles */
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #e7e9f3ff 0%, #f9f9faff 100%);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
        }

        /* Main Content Area Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
            transition: var(--transition);
            margin-top: var(--topbar-height); /* Add top margin to account for fixed topbar */
        }

        body.sidebar-collapsed .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            backdrop-filter: blur(10px);
        }

        .dashboard-header h1 {
            color: var(--text-dark);
            font-size: 28px;
            font-weight: 600;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-card {
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }

        /* Statistics Container */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 30px;
        }

        /* Stat Box Styles with Gradient Colors */
        .stat-box {
            position: relative;
            overflow: hidden;
            padding: 30px 20px;
            border-radius: 15px;
            color: white;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px; /* Fixed height for all cards */
        }

        /* Icon styling */
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Hover Effect */
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
        }

        /* Gradient Backgrounds */
        .stat-box:nth-child(1) {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }

        .stat-box:nth-child(2) {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
        }

        .stat-box:nth-child(3) {
            background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);
        }

        .stat-box:nth-child(4) {
            background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%);
        }

        .stat-box:nth-child(5) {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        }

        .stat-box:nth-child(6) {
            background: linear-gradient(135deg, #9e9e9e 0%, #616161 100%);
        }

        /* Stat Value (Number) */
        .stat-box h3 {
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Stat Label (Text) */
        .stat-box p {
            font-size: 1.1rem;
            margin: 0;
            font-weight: 500;
            opacity: 0.95;
            letter-spacing: 0.5px;
        }

        /* Decorative Background Effect */
        .stat-box::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
            transition: all 0.5s ease;
        }

        .stat-box:hover::before {
            transform: rotate(45deg) translateY(-10%);
        }

        .recent-activity {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }

        .activity-list {
            list-style: none;
            padding: 0;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .activity-time {
            font-size: 0.85rem;
            color: var(--secondary-color);
        }

        .student-name {
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .student-details {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        /* Breadcrumb Area */
        .breadcrumb-area {
            background-image: linear-gradient(to top, #fff1eb 0%, #ace0f9 100%);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin: 20px 0 30px 0;
            padding: 18px 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .breadcrumb {
            margin: 0;
            padding: 0;
            background: transparent;
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
        }

        .breadcrumb-item {
            display: flex;
            align-items: center;
            white-space: nowrap;
        }

        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 400;
        }

        .breadcrumb-item a:hover {
            color: #224abe;
        }

        .breadcrumb-item.active {
            color: var(--text-light);
            font-weight: 500;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            content: "/";
            color: var(--text-light);
            padding: 0 10px;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .stat-box {
                padding: 25px 15px;
                min-height: 180px; /* Adjusted height for medium screens */
            }

            .stat-box h3 {
                font-size: 2rem; /* Reduced from 2.5rem to 2rem for medium screens */
            }

            .stat-box p {
                font-size: 1rem;
            }
            
            .stat-icon {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .stat-box {
                min-height: 160px; /* Adjusted height for small screens */
            }
            
            .stat-box h3 {
                font-size: 1.8rem; /* Reduced from 2rem to 1.8rem for small screens */
            }
            
            .stat-icon {
                font-size: 1.8rem;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
                margin-top: var(--topbar-height);
            }
        }
    </style>
</head>

<body>
    <?php include '../assets/topbar.php'; ?>
    <?php include '../assets/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <div class="breadcrumb-area custom-gradient">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Welcome &nbsp; <span id="studentName"><?php echo htmlspecialchars($student_data['name'] ?? 'Student'); ?></span></li>
                </ol>
            </nav>
        </div>

        
    

        <div class="stats-container">
            
            <div class="stat-box">
                <div class="stat-icon"> <i class="fas fa-chart-line"></i> </div>
                <p>Attendance</p>
                <h3 id="attendancePercentage">...%</h3>
            </div>

            <div class="stat-box">
                <div class="stat-icon"> <i class="fas fa-building"></i> </div>
                <p>Department</p>
                <h3 id="department">N/A</h3>
            </div>

            <div class="stat-box">
                <div class="stat-icon"> <i class="fas fa-graduation-cap"></i> </div>
                <p>Batch</p>
                <h3 id="batch">N/A</h3>
            </div>

            <div class="stat-box">
                <div class="stat-icon"> <i class="fas fa-home"></i> </div>
                <p>Block Name</p>
                <h3 id="block">N/A</h3>
            </div>

            <div class="stat-box">
                <div class="stat-icon"> <i class="fas fa-door-open"></i> </div>
                <p>Room Number</p>
                <h3 id="roomNumber">N/A</h3>
            </div>

            <div class="stat-box">
                <div class="stat-icon"> <i class="fas fa-file-alt"></i> </div>
                <p>Pending Leaves</p>
                <h3 id="pendingLeaves">...</h3>
            </div>
            
        </div>
        
    </div>
    
    <!-- Notices Modal -->
    <div class="modal fade" id="noticesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content form-model-style">
                <div class="modal-header text-white p-3 rounded-top">
                    <h5 class="modal-title">Important Notices</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="notices-list" class="notices-list">
                        <!-- Notices will be loaded here dynamically -->
                        <div class="notice-placeholder">Loading notices...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../assets/footer.php'; ?>
    
    <style>
        /* Modal Styles - Updated to match leave apply modal */
        .modal-header {
            background: linear-gradient(to right, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 15px 20px;
            border-bottom: none;
        }
        
        .modal-header .modal-title {
            font-weight: 200;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header .btn-close {
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
        
        .modal-header .btn-close:hover {
            background-color: rgba(255, 255, 255, 0.4);
            transform: scale(1.1);
        }
        
        .modal-header .btn-close:focus {
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
            outline: none;
        }
        
        .modal-body {
            padding: 25px;
            max-height: 60vh;
            overflow-y: auto;
        }

        /* Notices Styles in Modal */
        .notice-item {
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .notice-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .notice-title {
            font-weight: 600;
            font-size: 1.3rem;
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        
        .notice-content {
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 12px;
        }
        
        .notice-date {
            font-size: 0.9rem;
            color: var(--secondary-color);
            font-style: italic;
        }
        
        .notice-placeholder {
            text-align: center;
            padding: 30px;
            color: var(--text-light);
            font-style: italic;
        }
        
        .no-notices {
            text-align: center;
            padding: 30px;
            color: var(--text-light);
        }
        

        

    
        /* Draggable Notification Button */
        .draggable-notification {
            position: fixed;
            bottom: 80px; /* Above footer */
            right: 20px;
            z-index: 1000;
            cursor: move;
            user-select: none;
        }
        
        .notification-btn {
            background: linear-gradient(135deg, var(--primary-color), #2e59d9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.4);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .notification-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.6);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
    </style>

    <script>
        
        const API_URL = '../api.php'; 
        // Make API_URL globally accessible
        window.API_URL = API_URL;
        
        // Store student gender globally for use in loadNotices
        let studentGender = 'N/A';

        /**
         * Fetches dashboard statistics from the backend API using AJAX (Fetch API).
         */
        async function loadDashboardStats() {
            // Display loading indicators
            document.getElementById('studentName').textContent = 'Loading...';
            document.getElementById('attendancePercentage').textContent = '...%';
            document.getElementById('pendingLeaves').textContent = '...';
            
            const formData = new FormData();
            formData.append('action', 'loadDashboardStats');

            try {
                const response = await fetch(window.API_URL, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success && result.data) {
                    const data = result.data;
                    
                    // Store student gender for use in loadNotices
                    studentGender = data.gender || 'N/A';
                    
                    document.getElementById('studentName').textContent = data.name;
                    document.getElementById('attendancePercentage').textContent = `${data.attendance_percentage}%`;
                    document.getElementById('department').textContent = data.department;
                    document.getElementById('batch').textContent = data.batch;
                    document.getElementById('block').textContent = data.block;
                    document.getElementById('roomNumber').textContent = data.room_number;
                    document.getElementById('pendingLeaves').textContent = data.pending_leaves;
                } else {
                    console.error('API Logic Error:', result.message || 'Failed to retrieve stats data.');
                    document.getElementById('studentName').textContent = 'Error Loading Data (API Logic)';
                    // Display the specific error message to the user for immediate feedback
                    alert('Error: ' + (result.message || 'Failed to retrieve stats data. Check console for details.'));
                }

            } catch (error) {
                console.error('Fetch/Network Error:', error);
                document.getElementById('studentName').textContent = 'Connection Failed';
            }
        }
        
        /**
         * Fetches notices from the backend API
         */
        async function loadNotices() {
            const formData = new FormData();
            formData.append('action', 'get_notices');
            
            try {
                const response = await fetch(window.API_URL, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success && result.notices) {
                    displayNotices(result.notices);
                    // Check if there are new notices and show modal automatically
                    checkForNewNotices(result.notices);
                } else {
                    document.getElementById('notices-list').innerHTML = '<div class="no-notices">Failed to load notices.</div>';
                }
            } catch (error) {
                console.error('Error fetching notices:', error);
                document.getElementById('notices-list').innerHTML = '<div class="no-notices">Error loading notices.</div>';
            }
        }
        
        /**
         * Check for new notices and show modal automatically
         */
        function checkForNewNotices(notices) {
            // Update notification badge
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.textContent = notices.length;
                // Hide badge if no notices
                badge.style.display = notices.length > 0 ? 'flex' : 'none';
            }
            
            // For now, we'll just show the modal if there are any notices
            // In a real implementation, you might check against previously seen notices
            if (notices.length > 0) {
                // You can uncomment this line if you want to automatically show the modal on page load
                // openNoticesModal();
            }
        }
        
        /**
         * Displays notices in the notices container
         */
        function displayNotices(notices) {
            const noticesList = document.getElementById('notices-list');
            
            if (notices.length === 0) {
                noticesList.innerHTML = '<div class="no-notices">No notices available at the moment.</div>';
                return;
            }
            
            let noticesHTML = '';
            notices.forEach(notice => {
                // Display all notices, using title if available or default text
                const title = notice.title || 'Notice';
                const content = notice.content || 'No content available';
                
                // Since title is derived from content, we'll just display the content
                noticesHTML += `
                    <div class="notice-item">
                        <div class="notice-content">${content}</div>
                        <div class="notice-date">Posted on ${notice.formatted_date}</div>
                    </div>
                `;
            });
            
            noticesList.innerHTML = noticesHTML;
        }

        // Modal functionality
        function openNoticesModal() {
            // Use Bootstrap modal show method
            const modal = new bootstrap.Modal(document.getElementById('noticesModal'));
            modal.show();
            // Load notices when modal opens
            loadNotices();
            // Clear notification badge
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.textContent = '0';
                badge.style.display = 'none';
            }
        }
        
        function closeNoticesModal() {
            // Use Bootstrap modal hide method
            const modalElement = document.getElementById('noticesModal');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        }
        
        // Make modal functions globally accessible
        window.openNoticesModal = openNoticesModal;
        window.closeNoticesModal = closeNoticesModal;
        window.displayNotices = displayNotices;
        window.loadNotices = loadNotices;
        window.checkForNewNotices = checkForNewNotices;
        
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardStats();
            // Load notices when page loads but don't show modal yet
            loadNotices();
        });
    </script>
    
    <!-- Draggable Notification Button -->
    <div class="draggable-notification" id="draggableNotification">
        <button class="notification-btn" id="openNoticesBtn">
            <i class="fas fa-bell"></i>
            <span class="notification-badge" id="notificationBadge">0</span>
        </button>
    </div>
    
    <script>
        // Make the notification button draggable
        const draggableNotification = document.getElementById('draggableNotification');
        let isDragging = false;
        let offsetX, offsetY;
        
        // Mouse events for dragging
        draggableNotification.addEventListener('mousedown', (e) => {
            // Only allow dragging when clicking on the button itself, not the badge
            if (e.target.classList.contains('notification-badge')) return;
            
            isDragging = true;
            const rect = draggableNotification.getBoundingClientRect();
            offsetX = e.clientX - rect.left;
            offsetY = e.clientY - rect.top;
            
            // Add dragging class for visual feedback
            draggableNotification.style.cursor = 'grabbing';
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            
            // Calculate new position
            let x = e.clientX - offsetX;
            let y = e.clientY - offsetY;
            
            // Constrain to viewport
            const maxX = window.innerWidth - draggableNotification.offsetWidth;
            const maxY = window.innerHeight - draggableNotification.offsetHeight;
            
            x = Math.max(0, Math.min(x, maxX));
            y = Math.max(0, Math.min(y, maxY));
            
            // Apply new position
            draggableNotification.style.left = x + 'px';
            draggableNotification.style.top = y + 'px';
            draggableNotification.style.right = 'auto';
            draggableNotification.style.bottom = 'auto';
        });
        
        document.addEventListener('mouseup', () => {
            isDragging = false;
            draggableNotification.style.cursor = 'move';
        });
        
        // Touch events for mobile dragging
        draggableNotification.addEventListener('touchstart', (e) => {
            // Only allow dragging when touching the button itself, not the badge
            if (e.target.classList.contains('notification-badge')) return;
            
            isDragging = true;
            const touch = e.touches[0];
            const rect = draggableNotification.getBoundingClientRect();
            offsetX = touch.clientX - rect.left;
            offsetY = touch.clientY - rect.top;
            e.preventDefault();
        });
        
        document.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            
            const touch = e.touches[0];
            
            // Calculate new position
            let x = touch.clientX - offsetX;
            let y = touch.clientY - offsetY;
            
            // Constrain to viewport
            const maxX = window.innerWidth - draggableNotification.offsetWidth;
            const maxY = window.innerHeight - draggableNotification.offsetHeight;
            
            x = Math.max(0, Math.min(x, maxX));
            y = Math.max(0, Math.min(y, maxY));
            
            // Apply new position
            draggableNotification.style.left = x + 'px';
            draggableNotification.style.top = y + 'px';
            draggableNotification.style.right = 'auto';
            draggableNotification.style.bottom = 'auto';
            e.preventDefault();
        });
        
        document.addEventListener('touchend', () => {
            isDragging = false;
        });
        
        // Add event listeners for modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add click event listener to the notification button
            const openNoticesBtn = document.getElementById('openNoticesBtn');
            
            if (openNoticesBtn) {
                openNoticesBtn.addEventListener('click', window.openNoticesModal);
            }
            
            // Bootstrap handles closing the modal, so we don't need separate event listeners for close button and outside click
            // Close modal with Escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    window.closeNoticesModal();
                }
            });
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>

</html>