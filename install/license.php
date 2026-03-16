<?php

session_start();

$validation_result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $license_key = trim($_POST['license_key'] ?? '');
    
    $_SESSION['panel_id'] = $_POST['license_key'];

    if (empty($license_key)) {
        $error = "Please enter a license key.";
    } else {
        // Generate secure device ID (HMAC SHA-256)
        $device_info = php_uname(); // You can enhance this with other data for better uniqueness
        $secret_key = 'YOUR_SECRET_SALT'; // Must match your backend's secret
        $device_id = hash_hmac('sha256', $device_info, $secret_key);

        $payload = json_encode([
            'license_key' => $license_key,
            'device_id' => $device_id
        ]);

        $domain = $_SERVER['HTTP_HOST'];
        $headers = [
            'Content-Type: application/json',
            "X-Domain: $domain"
        ];

        $ch = curl_init('https://upload.couponcart.in/api/validate-license.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        
        $_SESSION['validation_result'] = $response;
        // Redirect to same page to prevent resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;


    }
}

// After redirect
if (isset($_SESSION['validation_result'])) {
    $validation_result = $_SESSION['validation_result'];
    unset($_SESSION['validation_result']);

    $response = json_decode($validation_result, true);
    if (isset($response['success']) && $response['success']) {
        // Generate a secure token for installing.php
        $token = bin2hex(random_bytes(16));
        $_SESSION['install_token'] = $token;
        
         // ✅ Save the license key to /license.key
            $licensePath = dirname(__DIR__) . '/license.key';
            file_put_contents($licensePath, $_SESSION['panel_id']);

        // Redirect to installing.php with token and optional zip filename
        header("Location: download.php?token=$token");
        exit;
    }
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>License Activation</title>
    <style>
    body {
      background: #f5f7fa;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 400px;
      margin: 100px auto;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #333;
    }

    input[type="text"],
    input[type="submit"] {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      box-sizing: border-box;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 16px;
    }

    input[type="submit"] {
      background-color: #4CAF50;
      color: white;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    input[type="submit"]:hover {
      background-color: #45a049;
    }

    .result {
      margin-top: 15px;
      font-size: 14px;
      color: #333;
      padding: 10px;
      background: #f1f1f1;
      border-radius: 6px;
    }
  </style>
</head>
<body>
    <div class="container">
        <h2>Enter License Key</h2>
        <form method="POST">
            <input type="text" name="license_key" placeholder="Enter your license key" required>
            <input type="submit" value="Validate License">
        </form>


        <?php if ($validation_result): ?>
  <?php
    $response = json_decode($validation_result, true);
    if (isset($response['success']) && $response['success']) {
        echo "<div class='result' style='color: green;'>
                 {$response['message']}<br>
                📅 Expires on: {$response['expires']}
              </div>";
    } else {
        echo "<div class='result' style='color: red;'>❌ Error: " . htmlspecialchars($response['error'] ?? 'Unknown error') . "</div>";
    }
  ?>
<?php endif; ?>

    </div>
</body>
</html>
