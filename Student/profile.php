<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login');   
    exit();
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --topbar-height: 60px;
            --footer-height: 60px;
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --dark-bg:#1a1c23;
            --light-bg: #f8f9fc;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --dark-bg: #1a1a2e;
            --primary: #3498db;
            --secondary: #2ecc71;
            --accent: #e74c3c;
            --light-bg: #f8f9fa;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
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

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
            transition: var(--transition);
        }

        .loading {
            text-align: center;
            padding: 40px;
            font-size: 18px;
            color: var(--primary);
        }

        .loading i {
            margin-right: 10px;
        }
        .profile-photo { width: 100%; height: 100%; object-fit: cover; display: block; }

        .breadcrumb-area {
            background-image: linear-gradient(to top, #fff1eb 0%, #ace0f9 100%);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin: 80px 0 30px 0;
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
            color: var(--primary);
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

        .profile-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 25px;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            padding: 30px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 30%;
            margin: 0 auto 20px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: white;
            overflow: hidden;
            border: 5px solid white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 30%;
        }

        .profile-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--text-dark);
        }

        .profile-roll {
            color: var(--text-light);
            margin-bottom: 15px;
            font-size: 16px;
        }

        .profile-department {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            display: inline-block;
            margin-bottom: 25px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .photo-upload-overlay {
            position: absolute;
            bottom: 8px;
            right: 8px;
            width: 32px;
            height: 32px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            opacity: 0.9;
            transition: var(--transition);
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .photo-upload-overlay:hover {
            background: var(--secondary);
            opacity: 1;
            transform: scale(1.1);
        }

        .profile-stats {
            display: flex;
            justify-content: space-around;
            margin: 25px 0;
            padding: 20px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-value.attendance {
            color: var(--success);
        }

        .stat-value.leaves {
            color: var(--warning);
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-light);
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary), #2980b9);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .details-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            padding: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .info-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
        }
/* Hostel Details Section - Enhanced */
.info-section:nth-child(3) {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.info-section:nth-child(3) .info-item {
  flex: 1;
  display: flex;
  align-items: center;
  margin-bottom: 0;
  padding: 12px 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.info-section:nth-child(3) .info-item:last-child {
  border-bottom: none;
}

.info-section:nth-child(3) .info-label {
  width: 150px;
  font-weight: 600;
  color: var(--text-light);
  font-size: 14px;
  flex-shrink: 0;
}

.info-section:nth-child(3) .info-value {
  flex: 1;
  font-weight: 500;
  color: var(--text-dark);
  display: flex;
  align-items: center;
}

.info-section:nth-child(3) .info-item:last-child {
  border-bottom: none;
}

.info-section:nth-child(3) .info-label {
  width: 150px;
  font-weight: 600;
  color: var(--text-light);
  font-size: 14px;
  flex-shrink: 0;
}

.info-section:nth-child(3) .info-value {
  flex: 1;
  font-weight: 500;
  color: var(--text-dark);
  display: flex;
  align-items: center;
}
        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        /* Fix for Info Sections Styling */
.info-grid .info-section {
    background: white;
    padding: 20px;
    border-radius: 15px;
    border-left: 4px solid #3498db !important;
    transition: var(--transition);
    min-height: 200px;
}


.info-grid .info-section:nth-of-type(1) {
    border-left: 4px solid #3498db !important;
}

.info-grid .info-section:nth-of-type(2) {
    border-left: 4px solid #2ecc71 !important;
}

.info-grid .info-section:nth-of-type(3) {
    border-left: 4px solid #e74c3c !important;
}

.info-grid .info-section:nth-of-type(4) {
    border-left: 4px solid #f39c12 !important;
}


.info-grid .info-section:nth-of-type(1):hover {
    box-shadow: 0 4px 20px rgba(52, 152, 219, 0.2) !important;
    transform: translateY(-2px);
}

.info-grid .info-section:nth-of-type(2):hover {
    box-shadow: 0 4px 20px rgba(46, 204, 113, 0.2) !important;
    transform: translateY(-2px);
}

.info-grid .info-section:nth-of-type(3):hover {
    box-shadow: 0 4px 20px rgba(231, 76, 60, 0.2) !important;
    transform: translateY(-2px);
}

.info-grid .info-section:nth-of-type(4):hover {
    box-shadow: 0 4px 20px rgba(243, 156, 18, 0.2) !important;
    transform: translateY(-2px);
}



.info-item {
    display: flex;
    margin-bottom: 12px;
    padding: 8px 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.info-label {
    width: 150px;
    font-weight: 600;
    color: var(--text-light);
    font-size: 14px;
    flex-shrink: 0;
}

.info-value {
    flex: 1;
    font-weight: 500;
    color: var(--text-dark);
}


.section-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 20px;
    color: var(--text-dark);
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 10px;
    border-bottom: 2px solid rgba(0, 0, 0, 0.1);
}

.section-title i {
    color: var(--primary);
    font-size: 20px;
}


.parent-card {
    background: white;
    color: var(--text-dark);
    padding: 20px;
    border-radius: 15px;
    margin-bottom: 15px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-left: 4px solid #959296ff !important;
}

.parent-card .info-item {
    border-bottom-color: rgba(0, 0, 0, 0.1);
    margin-bottom: 10px;
    padding: 6px 0;
}

.parent-card .info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.parent-card .info-label {
    color: var(--text-light);
    width: 120px;
}

.parent-card .info-value {
    color: var(--text-dark);
}


.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    align-items: start;
}


@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .info-section:nth-of-type(3) .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .info-section:nth-of-type(3) .info-label {
        width: 100%;
        margin-bottom: 5px;
    }
    
    .info-label {
        width: 130px;
    }
}

        .info-item {
            display: flex;
            margin-bottom: 15px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .info-label {
            width: 150px;
            font-weight: 600;
            color: var(--text-light);
            font-size: 14px;
        }

        .info-value {
            flex: 1;
            font-weight: 500;
            color: var(--text-dark);
        }

        .parent-card {
            background: white;
            color: var(--text-dark);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .parent-photos-section {
            margin: 25px 0;
            padding: 20px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            text-align: center;
        }

        .parent-photos-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 40px;
            flex-wrap: wrap;
        }

        .parent-photo-item {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .parent-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 12px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            transition: var(--transition);
            position: relative;
        }

        .parent-image:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .parent-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .parent-relation {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 6px;
            font-weight: 500;
        }

        .success-message {
            background: var(--success);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error-message {
            background: var(--danger);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        @media (max-width: 1200px) {
            .profile-container {
                grid-template-columns: 1fr;
            }

            .profile-card {
                max-width: 500px;
                margin: 0 auto;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .profile-stats {
                flex-direction: column;
                gap: 15px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
 
        .pdf-container {
            width: 100%;
            max-width: 1200px;
            margin: 20px;
            padding: 20px;
            background: white;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.4;
            color: #000;
        }

        .pdf-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #000;
        }

        .pdf-logo {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10pt;
            text-align: center;
            padding: 5px;
        }

        .pdf-college-info {
            flex: 1;
            text-align: center;
            padding: 0 20px;
        }

        .pdf-college-name {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .pdf-college-subtitle {
            font-size: 12pt;
            font-style: italic;
        }

        .pdf-profile-container {
            display: block;
        }

        .pdf-profile-card {
            background: white;
            box-shadow: none;
            border: none;
            border-radius: 0;
            padding: 0;
            margin-bottom: 20px;
            text-align: left;
        }

        .pdf-profile-name {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .pdf-profile-roll {
            font-size: 14pt;
            margin-bottom: 10px;
        }

        .pdf-profile-department {
            background: none;
            color: #000;
            padding: 0;
            font-size: 12pt;
            box-shadow: none;
            margin-bottom: 15px;
        }

        .pdf-profile-stats {
            display: flex;
            justify-content: space-around;
            margin: 15px 0;
            padding: 10px 0;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .pdf-stat-value {
            font-size: 16pt;
        }

        .pdf-stat-label {
            font-size: 10pt;
        }

        .pdf-details-card {
            background: white;
            box-shadow: none;
            border: none;
            border-radius: 0;
            padding: 0;
            margin-bottom: 20px;
        }

        .pdf-card-header {
            border-bottom: 2px solid #000;
            margin-bottom: 15px;
            padding-bottom: 10px;
        }

        .pdf-card-title {
            font-size: 16pt;
            background: none;
            -webkit-text-fill-color: #000;
            color: #000;
        }

        .pdf-info-grid {
            display: block;
        }

        .pdf-info-section {
            background: white;
            border: 1px solid #000;
            border-radius: 0;
            padding: 15px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .pdf-section-title {
            font-size: 14pt;
            border-bottom: 1px solid #000;
            margin-bottom: 10px;
        }

        .pdf-info-item {
            display: flex;
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
        }

        .pdf-info-label {
            width: 150px;
            font-weight: bold;
            color: #000;
        }

        .pdf-info-value {
            color: #000;
        }

        .pdf-parent-card {
            background: white;
            color: #000;
            border: 1px solid #000;
            border-radius: 0;
            padding: 10px;
            margin-bottom: 10px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .pdf-primary-badge {
            background: #22e109ff;
            color: #000;
            border: 1px solid #000;
            padding: 2px 6px;
            font-size: 9pt;
        }

        /* Color classes for PDF stats */
        .pdf-stat-value.attendance {
            color: #27ae60;
        }

        .pdf-stat-value.leaves {
            color: #f39c12;
        }

        /* Info message style */
        .info-message {
            background: var(--primary);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
    <?php include '../assets/sidebar.php'; ?>
    <?php include '../assets/topbar.php'; ?>
    
    <div class="main-content" id="mainContent">
  
        <div id="messagesContainer"></div>

        <div class="breadcrumb-area mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Profile</li>
                </ol>
            </nav>
        </div>
        

        <div id="pdfContent" style="display: none;"></div>
        

        <div id="profileContainer">
            <div class="loading" id="profileLoading">
                <i class="fas fa-spinner fa-spin"></i> Loading profile data...
            </div>
        </div>
    </div>

    <?php include '../assets/footer.php'; ?>

<script>
    const userId = <?php echo $_SESSION['user_id']; ?>;
    let profileData = {};

    $(document).ready(function() {
        console.log('Page loaded, user ID:', userId);
        loadProfileData();
        initializeHamburgerMenu();
    });

    function loadProfileData() {
        console.log('Loading profile data...');
        $('#profileLoading').show();
        
        $.ajax({
            url: '../api.php',
            type: 'POST',
            data: {
                action: 'get_profile_data',
                user_id: userId
            },
            dataType: 'json',
            success: function(response) {
                console.log('API Response:', response);
                $('#profileLoading').hide();
                
                if (response.success) {
                    profileData = response.data;
                    displayProfileData(profileData);
                } else {
                    showMessage('error', response.message || 'Failed to load profile data');
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', error);
                console.log('Status:', status);
                console.log('XHR:', xhr);
                $('#profileLoading').hide();
                showMessage('error', 'Failed to connect to server. Please check if the API is running.');
            }
        });
    }

    function displayProfileData(data) {
        console.log('Displaying profile data:', data);
        
        const student = data.student_data || {};
        const parents = data.parent_data || [];
        const attendance = data.attendance_stats || {};
        const leaves = data.leave_stats || {};

        let html = `
            <div class="profile-container">
                <div class="profile-card">
                    <!-- Student Photo -->
                    <div class="profile-image-container">
                        <div class="profile-image" id="studentPhotoContainer">
                            ${student.photo_url ? 
                                `<img src="${student.photo_url}?t=${new Date().getTime()}" alt="Student Photo" class="profile-photo">` :
                                `<div class="default-photo"><i class="fas fa-user"></i></div>`
                            }
                            ${!student.photo_url ? `
                                <div class="photo-upload-overlay" data-type="student" data-id="${userId}">
                                    <i class="fas fa-camera"></i>
                                </div>
                            ` : ''}
                        </div>
                    </div>

                    <div class="profile-name">${student.name || 'N/A'}</div>
                    <div class="profile-roll">${student.roll_number || 'N/A'}</div>
                    <div class="profile-department">${student.department || 'N/A'}</div>

                    ${parents.length > 0 ? `
                    <div class="parent-photos-section">
                        <div class="section-title" style="margin: 20px 0 15px 0; font-size: 16px; justify-content: center;">
                            <i class="fas fa-users"></i> Parents/Guardians
                        </div>
                        <div class="parent-photos-container">
                            ${parents.map((parent, index) => `
                                <div class="parent-photo-item">
                                    <div class="parent-image-container">
                                        <div class="parent-image" id="parentPhotoContainer${index}">
                                            ${parent.photo_url ? 
                                                `<img src="${parent.photo_url}?t=${new Date().getTime()}" alt="${parent.name}" class="profile-photo">` :
                                                `<div class="default-photo"><i class="fas fa-user"></i></div>`
                                            }
                                            ${!parent.photo_url ? `
                                                <div class="photo-upload-overlay" 
                                                     data-type="parent" 
                                                     data-id="${parent.guardian_id}"
                                                     data-name="${parent.name}">
                                                    <i class="fas fa-camera"></i>
                                                </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                    <div class="parent-info">
                                        <div class="parent-name">${parent.name}</div>
                                        <div class="parent-relation">${parent.relation}</div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>` : ''}

                    <!-- Hidden file input for photo upload -->
                    <form id="photoUploadForm" method="POST" enctype="multipart/form-data" style="display: none;">
                        <input type="file" id="photoInput" name="photo" accept="image/*">
                        <input type="hidden" id="photoTypeInput" name="photo_type">
                        <input type="hidden" id="parentIdInput" name="parent_id">
                    </form>

                    <div class="profile-stats">
                        <div class="stat">
                            <div class="stat-value attendance">${attendance.attendance_percentage || '0'}%</div>
                            <div class="stat-label">Attendance</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value leaves">${leaves.active_leaves || '0'}</div>
                            <div class="stat-label">Active Leaves</div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="button" class="btn btn-primary" id="downloadPdf">
                            <i class="fas fa-file-pdf"></i> Download PDF
                        </button>

                    </div>
                </div>

                <!-- Details Card -->
                <div class="details-card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-id-card"></i> Student Information</div>
                    </div>
                    <br>
                    <div class="info-grid">
                        <!-- Personal Info -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-user-circle"></i> Personal Details
                            </div>
                            <div class="info-item">
                                <div class="info-label">Full Name:</div>
                                <div class="info-value">${student.name || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Date of Birth:</div>
                                <div class="info-value">${student.date_of_birth || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Gender:</div>
                                <div class="info-value">${student.gender || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email:</div>
                                <div class="info-value">${student.email || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Phone:</div>
                                <div class="info-value">${student.student_mobile_no || 'N/A'}</div>
                            </div>
                        </div>

                        <!-- Academic Info -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-graduation-cap"></i> Academic Details
                            </div>
                            <div class="info-item">
                                <div class="info-label">Roll Number:</div>
                                <div class="info-value">${student.roll_number || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Department:</div>
                                <div class="info-value">${student.department || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Admission:</div>
                                <div class="info-value">${student.admission_type || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Batch:</div>
                                <div class="info-value">${student.academic_batch || 'N/A'}</div>
                            </div>
                        </div>

                        <!-- Hostel Info -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-bed"></i> Hostel Details
                            </div>
                            <div class="info-item">
                                <div class="info-label">Hostel Name:</div>
                                <div class="info-value">${student.hostel_name || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Hostel Code:</div>
                                <div class="info-value">${student.hostel_code || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Hostel Address:</div>
                                <div class="info-value">${student.address || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Admission Date:</div>
                                <div class="info-value">${student.created_at ? formatDate(student.created_at) : 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Room Number:</div>
                                <div class="info-value">${student.room_number || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Room Type:</div>
                                <div class="info-value">${student.room_type || 'N/A'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Room Status:</div>
                                <div class="info-value">${student.occupied == 1 ? 'Occupied' : 'Not Occupied'}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Room Capacity:</div>
                                <div class="info-value">${student.capacity || 'N/A'}</div>
                            </div>
                        </div>

                        <!-- Parent/Guardian Info -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-users"></i> Parent/Guardian Details
                            </div>
                            ${parents.length > 0 ? parents.map(parent => `
                                <div class="parent-card">
                                    <div class="info-item">
                                        <div class="info-label">Name:</div>
                                        <div class="info-value">${parent.name}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Relation:</div>
                                        <div class="info-value">${parent.relation}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Phone:</div>
                                        <div class="info-value">${parent.phone || 'N/A'}</div>
                                    </div>
                                </div>
                            `).join('') : '<div class="info-item"><div class="info-value" style="color: var(--text-light);">No parent/guardian information available</div></div>'}
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#profileContainer').html(html);
        initializeEventHandlers();
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    function initializeEventHandlers() {

        $(document).on('click', '.photo-upload-overlay', function(e) {
            e.stopPropagation();
            const photoType = $(this).data('type');
            const targetId = $(this).data('id');
            const targetName = $(this).data('name') || 'Student';
            
            $('#photoTypeInput').val(photoType);
            $('#parentIdInput').val(targetId);
            $('#photoInput').click();
        });

        $('#photoInput').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                validateAndUploadPhoto(file);
            }
        });


        $(document).on('click', '#downloadPdf', function() {
            downloadProfilePdf();
        });


        $(document).on('click', '#refreshProfile', function() {
            loadProfileData();
        });
    }

    function validateAndUploadPhoto(file) {

        if (file.size > 5 * 1024 * 1024) {
            showMessage('error', 'File size must be less than 5MB.');
            $('#photoInput').val('');
            return;
        }


        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            showMessage('error', 'Only JPG, PNG, and GIF images are allowed.');
            $('#photoInput').val('');
            return;
        }


        const reader = new FileReader();
        reader.onload = function(e) {
            if (confirm('Do you want to upload this photo?')) {
                uploadPhoto(file);
            } else {
                $('#photoInput').val('');
            }
        };
        reader.readAsDataURL(file);
    }

    function uploadPhoto(file) {
        const formData = new FormData();
        formData.append('action', 'upload_photo');
        formData.append('user_id', userId);
        formData.append('photo_type', $('#photoTypeInput').val());
        formData.append('parent_id', $('#parentIdInput').val());
        formData.append('photo', file);

        // Show uploading message
        showMessage('info', 'Uploading photo...');

        $.ajax({
            url: '../api.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.message);
                    // Update the photo immediately without reloading the whole page
                    updatePhotoDisplay(response.photo_type, response.target_id, response.photo_url);
                } else {
                    showMessage('error', response.message);
                }
                $('#photoInput').val('');
            },
            error: function(xhr, status, error) {
                console.log('Upload error:', error);
                showMessage('error', 'Failed to upload photo. Please try again.');
                $('#photoInput').val('');
            }
        });
    }

  function updatePhotoDisplay(photoType, targetId, photoUrl) {
    console.log('Updating photo display:', { photoType, targetId, photoUrl });
    
    // Add cache busting parameter
    const timestamp = new Date().getTime();
    const photoUrlWithCacheBust = `${photoUrl}?t=${timestamp}`;
    
    if (photoType === 'student') {
        // Update student photo
        const studentContainer = $('#studentPhotoContainer');
        console.log('Updating student photo container:', studentContainer);
        
        studentContainer.html(`
            <img src="${photoUrlWithCacheBust}" alt="Student Photo" class="profile-photo">
        `);
        
 
        showMessage('success', 'Student photo updated successfully!');
        
    } else if (photoType === 'parent') {

        console.log('Looking for parent container with ID:', targetId);
        const parentOverlay = $(`.photo-upload-overlay[data-id="${targetId}"]`);
        console.log('Found parent overlay:', parentOverlay.length);
        
        if (parentOverlay.length > 0) {
            const parentContainer = parentOverlay.closest('.parent-image');
            console.log('Found parent container:', parentContainer);
            
            parentContainer.html(`
                <img src="${photoUrlWithCacheBust}" alt="Parent Photo" class="profile-photo">
            `);
            

            const parentName = parentOverlay.data('name') || 'Parent';
            showMessage('success', `${parentName}'s photo updated successfully!`);
        } else {
            console.error('Parent container not found for ID:', targetId);
            showMessage('error', 'Failed to update parent photo display. Please refresh the page.');
        }
    }
}
    function downloadProfilePdf() {
        const { jsPDF } = window.jspdf;
        const originalText = $('#downloadPdf').html();
        
        $('#downloadPdf').html('<i class="fas fa-spinner fa-spin"></i> Generating PDF...');
        $('#downloadPdf').prop('disabled', true);
        
        generatePdfContent();
        const pdfContent = $('#pdfContent');
        pdfContent.show();

        const pdf = new jsPDF('p', 'mm', 'a3');
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        
        html2canvas(pdfContent[0], {
            scale: 2,
            useCORS: true,
            logging: false,
            width: pdfContent[0].scrollWidth,
            height: pdfContent[0].scrollHeight,
            backgroundColor: '#ffffff'
        }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const imgWidth = pageWidth - 20; // Margin
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            
   
            pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
            
            pdf.save('student_profile_' + new Date().getTime() + '.pdf');

            pdfContent.hide();
            $('#downloadPdf').html(originalText);
            $('#downloadPdf').prop('disabled', false);
            
            showMessage('success', 'PDF downloaded successfully!');
            
        }).catch(error => {
            console.error('Error generating PDF:', error);
            showMessage('error', 'Error generating PDF. Please try again.');
            pdfContent.hide();
            $('#downloadPdf').html(originalText);
            $('#downloadPdf').prop('disabled', false);
        });
    }

    function generatePdfContent() {
        const student = profileData.student_data || {};
        const parents = profileData.parent_data || [];
        const attendance = profileData.attendance_stats || {};
        const leaves = profileData.leave_stats || {};

        const pdfHtml = `
            <div class="pdf-container">
                <div class="pdf-header">
                    <div class="pdf-logo">
                        <img src="image/mkce_logo2.jpg" width="100px" height="100px" onerror="this.style.display='none'">
                    </div>
                    <div class="pdf-college-info">
                        <div class="pdf-college-name">M.KUMARASAMY COLLEGE OF ENGINEERING, Karur - 639 113</div>
                        <div class="pdf-college-subtitle">(An Autonomous Institution Affiliated to Anna University, Chennai)</div>
                    </div>
                </div>
                
                <div class="pdf-profile-container">
                    

                    <div class="pdf-profile-stats">
                        <div class="stat">
                            <div class="pdf-stat-value attendance">${attendance.attendance_percentage || '0'}%</div>
                            <div class="pdf-stat-label">Attendance</div>
                        </div>
                        <div class="stat">
                            <div class="pdf-stat-value leaves">${leaves.active_leaves || '0'}</div>
                            <div class="pdf-stat-label">Active Leaves</div>
                        </div>
                    </div>

                    <div class="pdf-details-card">
                        <div class="pdf-card-header">
                            <div class="pdf-card-title">Student Information</div>
                        </div>

                        <div class="pdf-info-grid">
                            <!-- Personal Info -->
                            <div class="pdf-info-section">
                                <div class="pdf-section-title">Personal Details</div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Full Name:</div>
                                    <div class="pdf-info-value">${student.name || 'N/A'}</div>
                                </div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Date of Birth:</div>
                                    <div class="pdf-info-value">${student.date_of_birth || 'N/A'}</div>
                                </div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Gender:</div>
                                    <div class="pdf-info-value">${student.gender || 'N/A'}</div>
                                </div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Email:</div>
                                    <div class="pdf-info-value">${student.email || 'N/A'}</div>
                                </div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Phone:</div>
                                    <div class="pdf-info-value">${student.student_phone || 'N/A'}</div>
                                </div>
                            </div>

                            <!-- Academic Info -->
                            <div class="pdf-info-section">
                                <div class="pdf-section-title">Academic Details</div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Roll Number:</div>
                                    <div class="pdf-info-value">${student.roll_number || 'N/A'}</div>
                                </div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Department:</div>
                                    <div class="pdf-info-value">${student.department || 'N/A'}</div>
                                </div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Academic Year:</div>
                                    <div class="pdf-info-value">${student.academic_year || 'N/A'}</div>
                                </div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Year of Study:</div>
                                    <div class="pdf-info-value">${student.Year_of_study || 'N/A'}</div>
                                </div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Batch:</div>
                                    <div class="pdf-info-value">${student.batch || 'N/A'}</div>
                                </div>
                            </div>

                            <!-- Hostel Info -->
                            <div class="pdf-info-section">
                                <div class="pdf-section-title">Hostel Details</div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Hostel Name:</div>
                                    <div class="pdf-info-value">${student.hostel_name || 'N/A'}</div>
                                </div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Hostel Code:</div>
                                    <div class="pdf-info-value">${student.hostel_code || 'N/A'}</div>
                                </div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Admission Date:</div>
                                    <div class="pdf-info-value">${student.created_at ? formatDate(student.created_at) : 'N/A'}</div>
                                </div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Room Number:</div>
                                    <div class="pdf-info-value">${student.room_number || 'N/A'}</div>
                                </div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Room Type:</div>
                                    <div class="pdf-info-value">${student.room_type || 'N/A'}</div>
                                </div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Room Status:</div>
                                    <div class="pdf-info-value">${student.occupied == 1 ? 'Occupied' : 'Not Occupied'}</div>
                                </div>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-label">Room Capacity:</div>
                                    <div class="pdf-info-value">${student.capacity || 'N/A'}</div>
                                </div>
                            </div>

                            <!-- Parent Info -->
                            <div class="pdf-info-section">
                                <div class="pdf-section-title">Parent/Guardian Details</div>
                                ${parents.length > 0 ? parents.map(parent => `
                                    <div class="pdf-parent-card">
                                        <div class="pdf-info-item">
                                            <div class="pdf-info-label">Name:</div>
                                            <div class="pdf-info-value">${parent.name}</div>
                                        </div>
                                        <div class="pdf-info-item">
                                            <div class="pdf-info-label">Relation:</div>
                                            <div class="pdf-info-value">${parent.relation}</div>
                                        </div>
                                        <div class="pdf-info-item">
                                            <div class="pdf-info-label">Phone:</div>
                                            <div class="pdf-info-value">${parent.phone || 'N/A'}</div>
                                        </div>
                                    </div>
                                `).join('') : '<div class="pdf-info-item"><div class="pdf-info-value">No parent/guardian information available</div></div>'}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#pdfContent').html(pdfHtml);
    }

    function showMessage(type, message) {
        const messageClass = type === 'success' ? 'success-message' : 
                           type === 'error' ? 'error-message' : 
                           type === 'info' ? 'info-message' : 'success-message';
        
        const icon = type === 'success' ? 'fa-check-circle' : 
                   type === 'error' ? 'fa-exclamation-triangle' : 
                   type === 'info' ? 'fa-info-circle' : 'fa-check-circle';
        
        const messageHtml = `
            <div class="${messageClass}">
                <i class="fas ${icon}"></i> ${message}
            </div>
        `;
        
        $('#messagesContainer').html(messageHtml);
        
 
        if (type !== 'info') {
            setTimeout(() => {
                $('#messagesContainer').empty();
            }, 5000);
        }
    }

    function initializeHamburgerMenu() {

        const hamburger = document.createElement('div');
        hamburger.id = 'hamburger';
        hamburger.innerHTML = '<i class="fas fa-bars"></i>';
        hamburger.style.cssText = `
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background: var(--primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 5px;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
        `;
        document.body.appendChild(hamburger);

        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.createElement('div');
        mobileOverlay.id = 'mobileOverlay';
        mobileOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        `;
        document.body.appendChild(mobileOverlay);

        function handleSidebarToggle() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('mobile-show');
                mobileOverlay.style.display = sidebar.classList.contains('mobile-show') ? 'block' : 'none';
                document.body.style.overflow = sidebar.classList.contains('mobile-show') ? 'hidden' : '';
            } else {
                sidebar.classList.toggle('collapsed');
                document.body.classList.toggle('sidebar-collapsed');
            }
        }

        hamburger.addEventListener('click', handleSidebarToggle);
        mobileOverlay.addEventListener('click', handleSidebarToggle);

        function handleResize() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('mobile-show');
                mobileOverlay.style.display = 'none';
                document.body.style.overflow = '';
            } else {
                sidebar.classList.remove('collapsed');
                document.body.classList.remove('sidebar-collapsed');
            }

            hamburger.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
        }

        window.addEventListener('resize', handleResize);
        handleResize();
    }


    if (!$('#info-message-style').length) {
        $('head').append(`
            <style id="info-message-style">
                .info-message {
                    background: var(--primary);
                    color: white;
                    padding: 15px;
                    border-radius: 10px;
                    margin-bottom: 20px;
                    text-align: center;
                }
            </style>
        `);
    }
</script>
</body>
</html>
