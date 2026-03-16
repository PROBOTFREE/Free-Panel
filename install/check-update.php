<?php

header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$currentVersion = '0.0.0';

$domain = $_SERVER['HTTP_HOST']; // auto gets domain like gamercart.shop

function get_remote_data($url, $domain) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Domain: $domain"]);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

$latestVersionData = get_remote_data("https://upload.couponcart.in/latest_version.php", $domain);
$latestVersionJson = json_decode($latestVersionData, true);

if (!$latestVersionJson || !isset($latestVersionJson['version'])) {
    die(json_encode(['status' => 'error', 'message' => 'Failed to fetch latest version']));
}

$latestVersion = $latestVersionJson['version'];
$filename = $latestVersionJson['filename'];
$changelog = $latestVersionJson['changelog'];

// Compare versions
if (version_compare($currentVersion, $latestVersion, '>=')) {
    echo json_encode(['status' => 'up-to-date', 'message' => 'Already on the latest version']);
    exit;
}

// Return download details
echo json_encode([
    'status' => 'update_available',
    'latest_version' => $latestVersion,
    'filename' => $filename,
    'changelog' => $changelog
]);
?>