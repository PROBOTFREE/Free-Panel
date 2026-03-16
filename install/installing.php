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
<html>
<head>
    <title>Installing Update</title>
    <style>
        body {
            background: #f2f2f2;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .result-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
            text-align: center;
        }
        .result-box h2 {
            margin-bottom: 10px;
        }
        .success { color: green; }
        .error { color: red; }
        .small {
            margin-top: 10px;
            font-size: 13px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="result-box">
        <h2 class="<?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </h2>
<!-- In HTML -->
<a href="<?= $baseUrl ?>" style="
    display: inline-block;
    margin-top: 20px;
    padding: 10px 20px;
    background-color: #007bff;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
">
    Back to Website
</a>

    </div>
</body>
</html>
