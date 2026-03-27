<?php
session_start();
$panel_id = $_SESSION['panel_id'] ?? '';

// Validate secure token
$token = $_GET['token'] ?? $_POST['token'] ?? '';
if (!isset($_SESSION['install_token']) || $_SESSION['install_token'] !== $token) {
    http_response_code(403);
    die("<h3 style='color:red;text-align:center;'>❌ Unauthorized access.</h3>");
}

// Delete updates folder on page refresh
$updatesDir = __DIR__ . '/files';
if (is_dir($updatesDir)) {
    function deleteFolder($folder) {
        foreach (scandir($folder) as $item) {
            if ($item == '.' || $item == '..') continue;
            $path = $folder . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                deleteFolder($path);
            } else {
                unlink($path);
            }
        }
        rmdir($folder);
    }

    deleteFolder($updatesDir);
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Download Update - FreePanel</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* === GLOBAL === */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    background: linear-gradient(135deg, #6a11cb, #2575fc);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* === CARD === */
.card {
    width: 100%;
    max-width: 600px;
    padding: 40px;
    border-radius: 24px;
    backdrop-filter: blur(25px);
    background: rgba(255,255,255,0.12);
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    color: white;
    text-align: center;
    animation: fadeIn 0.5s ease;
}

/* === TITLE === */
.card h2 {
    margin-bottom: 10px;
}

.sub-text {
    font-size: 14px;
    opacity: 0.8;
    margin-bottom: 25px;
}

/* === VERSION === */
.version {
    font-size: 16px;
    margin-bottom: 10px;
}

.version span {
    color: #fff;
    font-weight: 600;
}

/* === CHANGELOG === */
.changelog {
    background: rgba(255,255,255,0.15);
    padding: 15px;
    border-radius: 12px;
    text-align: left;
    font-size: 13px;
    max-height: 150px;
    overflow-y: auto;
    margin-top: 15px;
}

/* === BUTTON === */
button {
    width: 100%;
    padding: 14px;
    margin-top: 20px;
    border-radius: 12px;
    border: none;
    background: #fff;
    color: #333;
    font-weight: 600;
    cursor: pointer;
    transition: 0.25s;
}

button:hover {
    transform: translateY(-2px);
}

/* === ANIMATION === */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.97);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
</head>

<body>

<div class="card">
    <h2>🚀 Panel Update</h2>
    <p class="sub-text">Install the latest version of your panel</p>

    <div class="version">
        Version: <span id="latest-version">Checking...</span>
    </div>

    <div class="changelog">
        <strong>Changelog:</strong>
        <div id="changelog">Fetching...</div>
    </div>

    <button id="download-btn">One-Click Setup</button>
</div>

<script>
const encodedPanelId = "<?php echo htmlspecialchars($panel_id); ?>";
const token = "<?php echo htmlspecialchars($token); ?>";

async function checkLatestVersion() {
    try {
        const res = await fetch('check_update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                panel_id: encodedPanelId,
            })
        });
        const data = await res.json();

        if (data.status === 'update_available') {
            document.getElementById('latest-version').textContent = data.latest_version;
            document.getElementById('changelog').textContent = data.changelog;
            document.getElementById('download-btn').onclick = () => logAndDownload(data.filename);
        } else {
            document.getElementById('latest-version').textContent = 'Up to date';
            document.getElementById('changelog').textContent = 'You are already on the latest version.';
        }
    } catch (error) {
        document.getElementById('latest-version').textContent = 'Error';
        document.getElementById('changelog').textContent = 'Failed to fetch updates.';
    }
}

async function logAndDownload(filename) {
    if (!encodedPanelId || !filename) {
        Swal.fire('Error', 'Missing parameters.', 'error');
        return;
    }

    Swal.fire({
        title: 'Installing Update...',
        text: 'Please wait while we set things up',
        allowOutsideClick: false,
        showConfirmButton: false,
        backdrop: 'rgba(0,0,0,0.6)',
        didOpen: () => Swal.showLoading()
    });

    try {
        const response = await fetch('save_update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                token: token,
                panel_id: encodedPanelId,
                filename: filename
            })
        });

        const data = await response.json();

        if (data.status === 'success' && data.install_url) {
            window.location.href = data.install_url;
        } else {
            location.reload();
        }

    } catch (err) {
        Swal.fire('Error', err.message, 'error').then(() => location.reload());
    }
}

checkLatestVersion();
</script>

</body>
</html>
