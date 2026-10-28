<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
            /* Reset */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html, body {
      height: 100%;
      overflow-x: hidden; /* prevent horizontal scroll */
      font-family: Arial, sans-serif;
    }

    body {
      display: flex;
      height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      background: url('images/sidebar_bg.jpg');
      background-size: 200px 150px;
      color: white;
      width: 220px;
      transition: width 0.3s;
      height: 100%;
      padding-top: 20px;
      overflow: hidden;
    }

    .sidebar.collapsed {
      width: 70px;
    }

    .sidebar ul {
      list-style: none;
    }

    .sidebar ul li {
      padding: 15px;
      display: flex;
      align-items: center;
    }

    .sidebar ul li .icon {
      margin-right: 12px;
      font-size: 18px;
      min-width: 20px; /* keeps icons aligned */
      text-align: center;
    }

    .sidebar.collapsed ul li .icon_name {
      display: none;
    }

    /* Main container */
    .main-container {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-width: 0; /* prevents flex overflow */
      transition: margin-left 0.3s;
    }

    /* Header */
    .header {
      height: 60px;
      background: linear-gradient(to bottom, #8a8a8a, #666666);
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 20px;
      flex-shrink: 0;
    }

    .three-dots {
      cursor: pointer;
      font-size: 22px;
    }

    /* Main content */
    .main {
      flex: 1;
      background: #f5f5f5;
      padding: 20px;
      overflow-y: auto; /* scroll only if content overflows vertically */
    }
  </style>
</head>
<body>
      <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <ul><li style="width: 100%; display: flex; align-items: center;">
      <div class="icon">
    <img src="images/mkce_s.png" alt="" style="width: 100%;">
  
  </div>
  <div class="icon_name">
  <span>
    <img src="images/mkce.png" alt="" style="width: 100%;">
  </span>
  </div>
</li>


 <li><div class="icon"><i class="fa fa-home"></i></div><div class="icon_name"><span>Rooms</span></div></li>
    </ul>
  </div>
</body>
</html>