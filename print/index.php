<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mess Token Printer</title>

<link rel="icon" type="image/png" sizes="32x32" href="../images/icons/mkce_s.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<!-- Bootstrap & Font Awesome (for shared layout components) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

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

*{
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body{
    margin:0;
}

.content {
    margin-left: var(--sidebar-width);
    padding-top: var(--topbar-height);
    min-height: 100vh;
    transition: all 0.3s ease;
}

.sidebar.collapsed~.content {
    margin-left: var(--sidebar-collapsed-width);
}

.card-wrapper {
    min-height: calc(100vh - var(--topbar-height) - var(--footer-height));
    display:flex;
    align-items:center;
    justify-content:center;
}

/* Card */
.card{
    width:380px;
    padding:35px;
    border-radius:18px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.25);
    color:#fff;
    text-align:center;
    position:relative;
    animation: fadeIn 0.6s ease;
    background: linear-gradient(135deg, #667eea, #764ba2);
}

@keyframes fadeIn{
    from{opacity:0; transform:translateY(20px);}
    to{opacity:1; transform:translateY(0);}
}

h2{
    margin-bottom:25px;
    font-weight:600;
}

/* Input */
input{
    width:100%;
    padding:14px;
    border-radius:10px;
    border:none;
    outline:none;
    font-size:15px;
    margin-bottom:20px;
}

/* Buttons */
button{
    width:100%;
    padding:14px;
    border:none;
    border-radius:10px;
    font-size:16px;
    font-weight:500;
    cursor:pointer;
    transition: all 0.3s ease;
}

.start-btn{
    background: linear-gradient(135deg,#00c6ff,#0072ff);
    color:#fff;
}

.start-btn:hover{
    transform: translateY(-2px);
    box-shadow:0 10px 20px rgba(0,114,255,0.4);
}

/* STOP button */
#stopBtn{
    position:absolute;
    top:18px;
    right:18px;
    width:auto;
    padding:8px 16px;
    background:#ff4d4f;
    display:none;
}

#stopBtn:hover{
    background:#d9363e;
}

/* Printing View */
#printing{
    display:none;
}

/* Loader */
.loader{
    width:70px;
    height:70px;
    border-radius:50%;
    border:6px solid rgba(255,255,255,0.2);
    border-top:6px solid #fff;
    animation: spin 1s linear infinite;
    margin:25px auto;
}

@keyframes spin{
    100%{transform:rotate(360deg);}
}

/* Status badge */
.status{
    display:inline-block;
    padding:6px 14px;
    border-radius:20px;
    font-size:13px;
    background: rgba(0,0,0,0.3);
    margin-top:10px;
}

.footer-text{
    font-size:12px;
    opacity:0.7;
    margin-top:20px;
}
/* Keep original card styling */
</style>
</head>

<body>
    <?php include '../assets/sidebar.php'; ?>

    <div class="content">
        <?php include '../assets/topbar.php'; ?>

        <div class="container-fluid">
            <div class="card-wrapper">

<div class="card">

    <button id="stopBtn" onclick="stopPrinting()">STOP</button>

    <!-- FORM -->
    <div id="form">
        <h2>🍽️ Outpass Printer</h2>
        <input type="text" id="device_ip" placeholder="Enter Device IP">
        <button class="start-btn" onclick="startPrinting()">Start Printing</button>
        <div class="footer-text">Secure • Real-time • Automated</div>
    </div>

    <!-- PRINTING -->
    <div id="printing">
        <h2>Printing Tokens</h2>
        <div class="loader"></div>
        <div class="status" id="statusText">Waiting for tokens…</div>
    </div>

            </div>
        </div>
    </div>

    <?php include '../assets/footer.php'; ?>

<script>
let printInterval = null;
let deviceIP = "";

function startPrinting(){
    deviceIP = document.getElementById('device_ip').value.trim();

    if(!deviceIP){
        alert("Please enter Device IP");
        return;
    }

    document.getElementById('form').style.display = "none";
    document.getElementById('printing').style.display = "block";
    document.getElementById('stopBtn').style.display = "block";

    callPrinter();
    printInterval = setInterval(callPrinter,3000);
}

function callPrinter(){
    fetch('outprint.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'device_ip='+encodeURIComponent(deviceIP)
    })
    .then(res=>res.text())
    .then(()=>{
        document.getElementById('statusText').innerText =
            "Printing… " + new Date().toLocaleTimeString();
    })
    .catch(()=>{
        document.getElementById('statusText').innerText = "Printer error!";
    });
}

function stopPrinting(){
    clearInterval(printInterval);

    document.getElementById('form').style.display = "block";
    document.getElementById('printing').style.display = "none";
    document.getElementById('stopBtn').style.display = "none";
}
</script>

</body>
</html>
