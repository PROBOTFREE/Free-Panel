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
    <title>Download Update</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap & SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background: linear-gradient(to right, #e3f2fd, #fff);
            font-family: 'Segoe UI', sans-serif;
        }
        .card {
            border-radius: 16px;
        }
        .card-body {
            padding: 2.5rem;
        }
        .changelog-box {
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 10px;
            text-align: left;
            font-size: 0.95rem;
            max-height: 150px;
            overflow-y: auto;
        }
        .btn-primary {
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow-lg w-100" style="max-width: 600px;">
        <div class="card-body text-center">
            <h3 class="mb-4"><i class="bi bi-cloud-arrow-down-fill me-2"></i>Latest Panel Update</h3>
            <p class="mb-1"><strong>Version:</strong> <span id="latest-version" class="text-primary">Checking...</span></p>
            <div class="changelog-box mt-3">
                <strong>Changelog:</strong>
                <div id="changelog" class="mt-1 text-muted">Fetching...</div>
            </div>
            <button id="download-btn" class="btn btn-primary mt-4 px-4">
                <i class="bi bi-download me-1"></i> One-Click Setup
            </button>
        </div>
    </div>
</div>


<script>
const encodedPanelId = "<?php echo htmlspecialchars($panel_id); ?>";
const token = "<?php echo htmlspecialchars($token); ?>";


function disableCache(url) {
    return url + (url.includes('?') ? '&' : '?') + '_=' + new Date().getTime();
}

async function checkLatestVersion() {
    try {
        const res = await fetch('check-update.php');
        const data = await res.json();

        if (data.status === 'update_available') {
            document.getElementById('latest-version').textContent = data.latest_version;
            document.getElementById('changelog').textContent = data.changelog;
            document.getElementById('download-btn').onclick = () => logAndDownload(data.filename);
        } else {
            document.getElementById('latest-version').textContent = 'Up to date';
        }
    } catch (error) {
        console.error("Failed to fetch version info", error);
    }
}

async function logAndDownload(filename) {
    if (!encodedPanelId || !filename) {
        Swal.fire('Error', 'Missing download parameters.', 'error');
        return;
    }

        // Show sweetalert loader with "Installing update..."
    Swal.fire({
        title: 'Installing Update...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
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

        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

        const data = await response.json();

        if (data.status === 'success' && data.install_url) {
                window.location.href = data.install_url;
            } else {
                location.reload();
            }

    } catch (err) {
        Swal.fire('Error', 'Download failed: ' + err.message, 'error').then(() => {
           location.reload(); //  Reload on error too
        });
    }
}

checkLatestVersion();
</script>

</body>
</html>
