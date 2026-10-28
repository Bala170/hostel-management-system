<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Mess Token Printer</title>

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #e9eff8;
    color: #1f2937;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
}

.container {
    width: min(95vw, 560px);
    background: #ffffff;
    border: 1px solid #d3dce8;
    border-radius: 14px;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.1);
    padding: 24px;
}

h2 {
    margin: 0 0 14px;
    font-size: 1.7rem;
    font-weight: 700;
    text-align: center;
    color: #0f172a;
}

.control-row {
    display: flex;
    gap: 10px;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.control-row input {
    width: 190px;
    min-width: 140px;
    padding: 10px 12px;
    border: 1px solid #b9cae3;
    border-radius: 10px;
    background: #f8fbff;
    font-size: 14px;
    color: #0f172a;
}

.control-row button {
    border: 0;
    border-radius: 10px;
    padding: 9px 16px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.control-row button:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(15, 23, 42, 0.18);
}

#startBtn {
    background: #2563eb;
    color: #fff;
}

#stopBtn {
    background: #ef4444;
    color: #fff;
}

#status {
    margin: 0 auto;
    max-width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    font-size: 14px;
    text-align: center;
    color: #334155;
}
</style>

</head>

<body>

<div class="container">
    <h2>Mess Token Auto Printer</h2>

    <div class="control-row">
        <input id="printer" placeholder="Shared Printer Name (e.g. POS58C)">
        <input id="device_ip" placeholder="Device IP">
        <button id="startBtn" onclick="startPrinter()">Start</button>
        <button id="stopBtn" onclick="stopPrinter()" style="display:none;">Stop</button>
    </div>

    <div id="status">Waiting for action...</div>
</div>

<script>

let running = false;

const statusEl = document.getElementById('status');
const startBtn = document.getElementById('startBtn');
const stopBtn = document.getElementById('stopBtn');

function setStatus(text, type = 'info') {
    statusEl.innerText = text;
    const colors = {
        info: '#F2AA4C',
        success: '#6ECB63',
        error: '#FF6666'
    };
    statusEl.style.color = colors[type] || colors.info;
}

function startPrinter() {
    if (running) return;

    const printerName = document.getElementById('printer').value.trim();
    const deviceIP = document.getElementById('device_ip').value.trim();

    if (!printerName || !deviceIP) {
        setStatus('Shared printer name and device IP are required', 'error');
        return;
    }

    running = true;
    window.printerName = printerName;
    window.deviceIP = deviceIP;

    startBtn.style.display = 'none';
    stopBtn.style.display = 'inline-block';

    setStatus('Printer started. Polling every 1s...', 'success');
    pollServer();
}

function stopPrinter() {
    running = false;
    startBtn.style.display = 'inline-block';
    stopBtn.style.display = 'none';
    setStatus('Printer stopped.', 'info');
}


async function pollServer() {
    if (!running) return;

    try {
        const res = await fetch("messprint.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                action: "get_messtokens",
                device_ip: window.deviceIP
            })
        });

        const data = await res.json();

        if (data.status !== 'success') {
            setStatus('Error: ' + (data.message || 'Failed to fetch tokens'), 'error');
            setTimeout(pollServer, 1000);
            return;
        }

        if (!Array.isArray(data.tokens) || data.tokens.length === 0) {
            setStatus('No tokens right now. Waiting...', 'info');
            setTimeout(pollServer, 1000);
            return;
        }

        setStatus('Received ' + data.tokens.length + ' token(s). Printing...', 'info');
        await processTokens(data.tokens);

    } catch (e) {
        setStatus('Server error: ' + e.message, 'error');
    }

    setTimeout(pollServer, 1000);
}



async function processTokens(tokens)
{
    let successIds = [];

    for(const token of tokens)
    {
        try{

            const escposData = atob(token.print_data);

            const res = await fetch("http://localhost:5000/print",{
                method:"POST",
                headers:{ "Content-Type":"application/json"},
                body:JSON.stringify({
                    shareName: printerName,
                    data: escposData
                })
            });

            const result = await res.json();

            if (result.success) {
                successIds.push(token.token_id);
            }

        } catch (e) {
            console.error('Printer error token', token.token_id, e);
        }
    }

    if (successIds.length > 0) {
        await markGenerated(successIds);
        setStatus('Printed and marked ' + successIds.length + ' token(s)', 'success');
    } else {
        setStatus('No tokens printed this cycle', 'error');
    }
}



async function markGenerated(ids)
{
    try{

        const res = await fetch("messprint.php",{
            method:"POST",
            headers:{ "Content-Type":"application/x-www-form-urlencoded"},
            body:new URLSearchParams({
                action:"mark_generation",
                token_ids: ids
            })
        });

        const data = await res.json();

        if (data.status !== 'success') {
            setStatus('Failed to mark generated: ' + (data.message || 'unknown'), 'error');
        }

    } catch (e) {
        setStatus('Update error: ' + e.message, 'error');
    }
}

</script>

</body>
</html>