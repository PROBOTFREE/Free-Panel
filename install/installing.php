<?php
session_start();

// Validate secure token
$token = $_GET['token'] ?? $_POST['token'] ?? '';
if (!isset($_SESSION['install_token']) || $_SESSION['install_token'] !== $token) {
    http_response_code(403);
    die("<h3 style='color:red;text-align:center;'>❌ Unauthorized access.</h3>");
}
unset($_SESSION['install_token']); // Invalidate token after use

// Get filename
$filename = $_GET['filename'] ?? $_POST['filename'] ?? 'update.zip';
if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
    die("<h3 style='color:red;text-align:center;'>❌ Invalid filename.</h3>");
}

// At the top
$baseUrl = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/';


$root = realpath($_SERVER['DOCUMENT_ROOT']); // safer for cross-domain
$updatePath = $root . "/install/files/$filename";
$extractTo = $root;

// Protected files
$protectedPaths = [
    realpath($root . '/.env'),
    realpath($root . '/conn.php'),
    realpath($root . '/public/conn.php'),
    realpath($root . '/install'),
];

// Helper to check if path is protected
function isProtected($path, $protectedPaths) {
    $real = realpath($path);
    if (!$real) return false;
    foreach ($protectedPaths as $protected) {
        if ($protected && strpos($real, $protected) === 0) {
            return true;
        }
    }
    return false;
}

/*// Step 1: Backup (excluding protected)
function backupFiles($source, $destination, $protectedPaths) {
    if (!is_dir($destination)) mkdir($destination, 0777, true);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $realPath = $item->getRealPath();
        if (!$realPath || isProtected($realPath, $protectedPaths)) continue;
        if (strpos($realPath, '/backup_') !== false) continue;

        $targetPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        if ($item->isDir()) {
            if (!is_dir($targetPath)) mkdir($targetPath);
        } else {
            copy($realPath, $targetPath);
        }
    }
}
*/
// Step 2: Extract zip
function extractUpdate($zipPath, $destination, $protectedPaths) {
    $zip = new ZipArchive();
    if ($zip->open($zipPath) === TRUE) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            $fullPath = $destination . '/' . $entry;

            if (isProtected($fullPath, $protectedPaths)) continue;

            $dir = dirname($fullPath);
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            if (!$zip->extractTo($destination, [$entry])) {
                throw new Exception("Failed to extract: $entry");
            }
        }
        $zip->close();
    } else {
        throw new Exception("Failed to open update zip file.");
    }
}

function deleteFolder($folder) {
    if (!is_dir($folder)) return;

    $files = array_diff(scandir($folder), ['.', '..']);
    foreach ($files as $file) {
        $path = "$folder/$file";
        is_dir($path) ? deleteFolder($path) : unlink($path);
    }

    rmdir($folder);
}


// --- Run update ---
try {
    
    extractUpdate($updatePath, $extractTo, $protectedPaths);

    $message = "✅ Update installed successfully!";
   
} catch (Exception $e) {
    $message = "❌ Update failed: " . $e->getMessage();
    
} finally {
    //  Always delete the updates folder
    $updatesFolder = $root . '/install/files';
    deleteFolder($updatesFolder);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Installing Update - FreePanel</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

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
    max-width: 480px;
    padding: 40px;
    border-radius: 24px;
    backdrop-filter: blur(25px);
    background: rgba(255,255,255,0.12);
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    color: white;
    text-align: center;
    animation: fadeIn 0.5s ease;
}

/* === ICON === */
.icon {
    font-size: 40px;
    margin-bottom: 15px;
}

/* === TEXT === */
.title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 10px;
}

.success { color: #b6ffb3; }
.error { color: #ffb3b3; }

.sub-text {
    font-size: 13px;
    opacity: 0.8;
    margin-top: 8px;
}

/* === BUTTON === */
.btn {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 20px;
    border-radius: 10px;
    background: #fff;
    color: #333;
    font-weight: 600;
    text-decoration: none;
    transition: 0.25s;
}

.btn:hover {
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

    <?php if (strpos($message, '✅') !== false): ?>
        <div class="icon">🚀</div>
        <div class="title success">Update Installed Successfully</div>
    <?php else: ?>
        <div class="icon">⚠️</div>
        <div class="title error">Update Failed</div>
    <?php endif; ?>

    <div class="sub-text">
        <?= htmlspecialchars($message) ?>
    </div>

    <a href="<?= $baseUrl ?>" class="btn">
        Go to Dashboard
    </a>

</div>

</body>
</html>