<?php
session_start();

$validation_result = null;
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $license_key = trim($_POST['license_key'] ?? '');

    if (empty($license_key)) {
        $error = "Please enter a license key.";
    } else {

        // 🔐 Generate device ID
        $device_info = php_uname();
        $secret_key = 'YOUR_SECRET_SALT';
        $device_id = hash_hmac('sha256', $device_info, $secret_key);

        // ✅ JSON payload (your API expects this)
        $payload = json_encode([
            'license_key' => $license_key,
            'device_id' => $device_id
        ]);

        $domain = $_SERVER['HTTP_HOST'];

        $ch = curl_init('https://auth.freepanel.shop/api/validate-license.php');

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "X-Domain: $domain"
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = "Connection error. Please try again.";
        } else {
            $validation_result = $response;

            $response_data = json_decode($response, true);

            // ✅ SUCCESS
            if (isset($response_data['success']) && $response_data['success']) {

                $_SESSION['panel_id'] = $license_key;

                // Save license file
                $licensePath = dirname(__DIR__) . '/license.key';
                file_put_contents($licensePath, $license_key);

                // Generate install token
                $token = bin2hex(random_bytes(16));
                $_SESSION['install_token'] = $token;

                header("Location: download.php?token=$token");
                exit;
            }

            // ❌ ERROR FROM API
            else {
                $error = $response_data['error'] ?? "Invalid license key.";
            }
        }

        curl_close($ch);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>License Activation - FreePanel</title>

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
    max-width: 420px;
    padding: 40px;
    border-radius: 20px;
    backdrop-filter: blur(25px);
    background: rgba(255,255,255,0.12);
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    color: white;
    animation: fadeIn 0.5s ease;
}

.card h2 {
    text-align: center;
    margin-bottom: 10px;
}

.sub-text {
    text-align: center;
    font-size: 14px;
    opacity: 0.8;
    margin-bottom: 25px;
}

/* === INPUT === */
.input-group {
    margin-bottom: 15px;
}

.input-group input {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: none;
    outline: none;
    font-size: 14px;
}

/* === BUTTON === */
button {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: none;
    background: #fff;
    color: #333;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

button:hover {
    transform: translateY(-2px);
}

/* === RESULT BOX === */
.result {
    margin-top: 15px;
    padding: 12px;
    border-radius: 10px;
    font-size: 14px;
}

.success {
    background: rgba(0,255,100,0.15);
    color: #b6ffb3;
}

.error {
    background: rgba(255,0,0,0.15);
    color: #ffb3b3;
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
    <h2>License Activation</h2>
    <p class="sub-text">Enter your license key to continue</p>

    <form method="POST">
        <div class="input-group">
            <input type="text" name="license_key" placeholder="Enter your license key" required>
        </div>

        <button type="submit">Validate License</button>
    </form>

    <?php if ($error): ?>
        <div class="result error">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
