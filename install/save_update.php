<?php
session_start();
header('Content-Type: application/json');


$data = json_decode(file_get_contents('php://input'), true);

$panel_id = urlencode($data['panel_id'] ?? '');
$filename = urlencode($data['filename'] ?? '');
$token = urlencode($data['token'] ?? '');

if (!isset($_SESSION['install_token']) || $_SESSION['install_token'] !== $token) {
    http_response_code(403);
    die("<h3 style='color:red;text-align:center;'>❌ Unauthorized access.</h3>");
}


if (!$panel_id || !$filename) {
    echo json_encode(['status' => 'error', 'message' => 'Missing panel_id or filename']);
    exit;
}

$url = "https://auth.freepanel.shop/secure_download.php?panel_id=$panel_id&file=$filename";

// Detect customer's domain
$domain = $_SERVER['HTTP_HOST'];
$headers = [
    "X-Domain: $domain"
];

// Initialize cURL session
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // 🟢 Add custom domain header

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['status' => 'error', 'message' => "cURL error: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}

if ($http_code !== 200) {
    echo json_encode(['status' => 'error', 'message' => "Download failed. Server responded with: $http_code"]);
    curl_close($ch);
    exit;
}

// Save the file in BINARY mode to avoid corruption
$localPath = __DIR__ . "/files/$filename";
$dir = dirname($localPath);
if (!is_dir($dir)) mkdir($dir, 0777, true);

$fp = fopen($localPath, 'wb'); // Open in binary mode
fwrite($fp, $response);
fclose($fp);

curl_close($ch);

echo json_encode([
    'status' => 'success',
    'message' => "Download success!",
    'file' => $localPath,
    'install_url' => "installing.php?token=$token&filename=$filename"
]);
?>