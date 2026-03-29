<?php
session_start();

if (!isset($_SESSION['panel_id'])) {
    header("Location: license.php");
    exit;
}

// Validate secure token
$token = $_GET['token'] ?? $_POST['token'] ?? '';
if (!isset($_SESSION['install_token']) || $_SESSION['install_token'] !== $token) {
    header("Location: license.php");
    exit;
}


$panelId = $_SESSION['panel_id'];

mysqli_report(MYSQLI_REPORT_OFF);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$panelURL = $protocol . $host . '/public';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host']);
    $dbName = trim($_POST['db_name']);
    $dbUser = trim($_POST['db_user']);
    $dbPass = trim($_POST['db_pass']);

    // Create conn.php
    $connContent = "<?php

\$servername = \"$dbHost\";
\$dbname = \"$dbName\";
\$username = \"$dbUser\";
\$password = \"$dbPass\";

\$conn = mysqli_connect(\$servername,\$username,\$password,\$dbname);

if(!\$conn) {
    die(\" PROBLEM WITH CONNECTION : \" . mysqli_connect_error());
}
?>";

    file_put_contents(__DIR__ . '/../conn.php', $connContent);
    
// Ensure 'public' folder exists
    $publicPath = __DIR__ . '/../public';
    if (!is_dir($publicPath)) {
        mkdir($publicPath, 0755, true);
    }

    // Write conn.php in public/
    file_put_contents($publicPath . '/conn.php', $connContent);
    
   // Save session (for reinstall)
    $_SESSION['db_host'] = $dbHost;
    $_SESSION['db_name'] = $dbName;
    $_SESSION['db_user'] = $dbUser;
    $_SESSION['db_pass'] = $dbPass;

    // ======================
    // 🔥 REINSTALL CHECK FIRST
    // ======================
    $isReinstall = false;

    if (isset($_GET['reinstall']) && $_GET['reinstall'] === 'true') {
        $isReinstall = true;

        $dbHost = $_SESSION['db_host'] ?? null;
        $dbName = $_SESSION['db_name'] ?? null;
        $dbUser = $_SESSION['db_user'] ?? null;
        $dbPass = $_SESSION['db_pass'] ?? null;
    }

    // ======================
    // VALIDATION
    // ======================
    $sqlError = null;

    if (!$dbHost || !$dbName || !$dbUser) {
        $sqlError = "Missing database credentials.";
    }

    // ======================
    // CONNECT DB
    // ======================
    if ($sqlError === null) {

        $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

        if ($conn->connect_error) {
            $sqlError = $conn->connect_error;
        }
    }

    
    
    // ======================
    // 🔥 DOWNLOAD SQL FROM API
    // ======================
    $apiUrl = "https://auth.freepanel.shop/api/secure_download_sql.php?panel_id=" . urlencode($panelId);
    
    $ch = curl_init($apiUrl);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            "X-Domain: " . $_SERVER['HTTP_HOST']
        ]
    ]);
    
    $sqlData = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $sqlError = "Download failed: " . curl_error($ch);
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Validate response
    if ($sqlError === null) {
        if ($httpCode !== 200 || !$sqlData) {
            $sqlError = "Failed to download SQL file from server.";
        }
    }
    
    // ======================
    // 🔥 USE DOWNLOADED SQL
    // ======================
    if ($sqlError === null) {
    
        $data = json_decode($sqlData, true);

        if (!$data) {
            $sqlError = "Invalid JSON response from API.";
        } elseif (isset($data['error'])) {
            $sqlError = $data['error'];
        } elseif (!isset($data['sql'])) {
            $sqlError = "SQL not found in API response.";
        } else {
            $sql = $data['sql'];
        }

        if (!$sql || trim($sql) === '') {
            $sqlError = "Downloaded SQL is empty.";
        }
        
    }
    
    // ======================
    // 🔥 IMPORT SQL
    // ======================
    if ($sqlError === null) {
    
        // 🔥 DROP TABLES (reinstall)
        if ($isReinstall) {
            $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
            $tables = $conn->query("SHOW TABLES");
            while ($row = $tables->fetch_array()) {
                $conn->query("DROP TABLE `" . $row[0] . "`");
            }
    
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        }
    
        // 🔥 IMPORT
        try {
            if (!$conn->multi_query($sql)) {
                $sqlError = $conn->error;
            }
        } catch (Throwable $e) {
            $sqlError = $e->getMessage();
        }
    
        while ($conn->more_results() && $conn->next_result()) {}
        $conn->close();
    }

    // ======================
    // SMART ERROR DETECTION
    // ======================
    $errorType = "unknown";

    if ($sqlError !== null) {
        if (strpos($sqlError, 'already exists') !== false) {
            $errorType = "table_exists";
        } elseif (strpos($sqlError, 'Access denied') !== false) {
            $errorType = "db_auth";
        } elseif (strpos($sqlError, 'Unknown database') !== false) {
            $errorType = "db_not_found";
        }
    }

    // ======================
    // ERROR UI
    // ======================
    if ($sqlError !== null) {

        $message = "Something went wrong during installation.";

        if ($errorType == "table_exists") {
            $message = "Database already installed. You can safely reinstall.";
        } elseif ($errorType == "db_auth") {
            $message = "Database authentication failed. Check username/password.";
        } elseif ($errorType == "db_not_found") {
            $message = "Database not found. Please create it first.";
        }

        echo "<!DOCTYPE html>
        <html>
        <head>
        <title>Error</title>
        <style>
        body{
            margin:0;
            height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            font-family:Poppins;
            background:linear-gradient(135deg,#6a11cb,#2575fc);
        }
        .popup{
            width:420px;
            padding:30px;
            border-radius:20px;
            backdrop-filter:blur(25px) saturate(180%);
            background:rgba(255,255,255,0.12);
            color:white;
            text-align:center;
        }
        .error-box{
            background:rgba(255,0,0,0.2);
            padding:10px;
            border-radius:10px;
            margin:10px 0;
        }
        button{
            padding:10px;
            border:none;
            border-radius:10px;
            cursor:pointer;
            margin-top:10px;
        }
        .danger{background:#ff4d4d;color:white;}
        </style>
        </head>
        <body>

        <div class='popup'>
            <h3>⚠️ Installation Failed</h3>
            <p>$message</p>

            <div class='error-box'>
                " . htmlspecialchars($sqlError) . "
            </div>

            <button class='danger' onclick='reinstall()'>Reinstall</button>
        </div>

        <script>
        function reinstall(){
            if(confirm('Delete all DB data?')){
                const form=document.createElement('form');
                form.method='POST';
                form.action=window.location.pathname+'?reinstall=true';
                document.body.appendChild(form);
                form.submit();
            }
        }
        </script>

        </body>
        </html>";
        exit;
    }
    

    // Create .env
    $envContent = <<<ENV
#--------------------------------------------------------------------
# Example Environment Configuration file
#
# This file can be used as a starting point for your own
# custom .env files, and contains most of the possible settings
# available in a default install.
#
# By default, all of the settings are commented out. If you want
# to override the setting, you must un-comment it by removing the '#'
# at the beginning of the line.
#--------------------------------------------------------------------

#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------

CI_ENVIRONMENT = development

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------

app.baseURL = '{$panelURL}'
# app.forceGlobalSecureRequests = false

# app.sessionDriver = 'CodeIgniter\Session\Handlers\FileHandler'
# app.sessionCookieName = 'ci_session'
# app.sessionExpiration = 7200
# app.sessionSavePath = NULL
# app.sessionMatchIP = false
# app.sessionTimeToUpdate = 300
# app.sessionRegenerateDestroy = false

# app.CSPEnabled = false

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------

database.default.hostname = {$dbHost}
database.default.database = {$dbName}
database.default.username = {$dbUser}
database.default.password = {$dbPass}
database.default.DBDriver = MySQLi
database.default.DBPrefix =

# database.tests.hostname = localhost
# database.tests.database = ci4
# database.tests.username = root
# database.tests.password = root
# database.tests.DBDriver = MySQLi
# database.tests.DBPrefix =

#--------------------------------------------------------------------
# CONTENT SECURITY POLICY
#--------------------------------------------------------------------

# contentsecuritypolicy.reportOnly = false
# contentsecuritypolicy.defaultSrc = 'none'
# contentsecuritypolicy.scriptSrc = 'self'
# contentsecuritypolicy.styleSrc = 'self'
# contentsecuritypolicy.imageSrc = 'self'
# contentsecuritypolicy.base_uri = null
# contentsecuritypolicy.childSrc = null
# contentsecuritypolicy.connectSrc = 'self'
# contentsecuritypolicy.fontSrc = null
# contentsecuritypolicy.formAction = null
# contentsecuritypolicy.frameAncestors = null
# contentsecuritypolicy.frameSrc = null
# contentsecuritypolicy.mediaSrc = null
# contentsecuritypolicy.objectSrc = null
# contentsecuritypolicy.pluginTypes = null
# contentsecuritypolicy.reportURI = null
# contentsecuritypolicy.sandbox = false
# contentsecuritypolicy.upgradeInsecureRequests = false

#--------------------------------------------------------------------
# COOKIE
#--------------------------------------------------------------------

# cookie.prefix = ''
# cookie.expires = 0
# cookie.path = '/'
# cookie.domain = ''
# cookie.secure = false
# cookie.httponly = false
# cookie.samesite = 'Lax'
# cookie.raw = false

#--------------------------------------------------------------------
# ENCRYPTION
#--------------------------------------------------------------------

# encryption.key =
# encryption.driver = OpenSSL
# encryption.blockSize = 16
# encryption.digest = SHA512

#--------------------------------------------------------------------
# HONEYPOT
#--------------------------------------------------------------------

# honeypot.hidden = 'true'
# honeypot.label = 'Fill This Field'
# honeypot.name = 'honeypot'
# honeypot.template = '<label>{label}</label><input type="text" name="{name}" value=""/>'
# honeypot.container = '<div style="display:none">{template}</div>'

#--------------------------------------------------------------------
# SECURITY
#--------------------------------------------------------------------

# security.tokenName = 'csrf_token_name'
# security.headerName = 'X-CSRF-TOKEN'
# security.cookieName = 'csrf_cookie_name'
# security.expires = 7200
# security.regenerate = true
# security.redirect = true
# security.samesite = 'Lax'

#--------------------------------------------------------------------
# LOGGER
#--------------------------------------------------------------------

# logger.threshold = 4

ENV;

    file_put_contents(__DIR__ . '/../.env', $envContent);

 
    // Show HTML with toast and redirect
    // ======================
    // SUCCESS
    // ======================
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Installation Complete</title>
    
    <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap' rel='stylesheet'>
    
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Poppins', sans-serif;
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #6a11cb, #2575fc);
    }
    
    /* === CARD === */
    .card {
        width: 100%;
        max-width: 420px;
        padding: 35px;
        border-radius: 20px;
        backdrop-filter: blur(25px) saturate(180%);
        background: rgba(255,255,255,0.12);
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        color: white;
        text-align: center;
        animation: fadeIn 0.5s ease;
    }
    
    .icon {
        font-size: 40px;
        margin-bottom: 10px;
    }
    
    h2 {
        margin-bottom: 10px;
    }
    
    p {
        font-size: 14px;
        opacity: 0.8;
    }
    
    .loader {
        margin-top: 20px;
        width: 40px;
        height: 40px;
        border: 4px solid rgba(255,255,255,0.3);
        border-top: 4px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* === ANIMATIONS === */
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    </style>
    </head>
    
    <body>
    
    <div class='card'>
        <div class='icon'>✅</div>
        <h2>Installation Successful</h2>
        <p>Your panel is ready. Redirecting to download...</p>
    
        <div class='loader'></div>
    </div>
    
    <script>
    setTimeout(() => {
        window.location = 'download.php?token=$token';
    }, 2000);
    </script>
    
    </body>
    </html>
    ";
    exit;
    
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Install Setup - FreePanel</title>

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
.install-card {
    width: 100%;
    max-width: 500px;
    padding: 40px;
    border-radius: 20px;
    backdrop-filter: blur(25px);
    background: rgba(255,255,255,0.12);
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    color: white;
    animation: fadeIn 0.5s ease;
}

.install-card h2 {
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

.input-group label {
    font-size: 13px;
    opacity: 0.8;
    display: block;
    margin-bottom: 6px;
}

.input-group input {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: none;
    outline: none;
    font-size: 14px;
}

/* === BUTTONS === */
.button-group {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

button {
    flex: 1;
    padding: 12px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.primary-btn {
    background: #fff;
    color: #333;
}

.secondary-btn {
    background: transparent;
    color: white;
    border: 1px solid rgba(255,255,255,0.4);
}

.error-box {
    background: rgba(255, 0, 0, 0.2);
    border: 1px solid rgba(255, 0, 0, 0.4);
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 15px;
    font-size: 13px;
}

.success-box {
    background: rgba(0, 255, 100, 0.15);
    border: 1px solid rgba(0, 255, 100, 0.3);
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 15px;
    font-size: 13px;
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
    
<?php if (!empty($sqlError)): ?>
    <div class="error-box">
        <strong>⚠️ Installation Failed</strong><br>
        <?= htmlspecialchars($message ?? 'Something went wrong') ?><br><br>
        <code><?= htmlspecialchars($sqlError) ?></code>
    </div>
<?php endif; ?>

<div class="install-card">
    <h2>Install Setup</h2>
    <p class="sub-text">Configure your database to continue</p>

    <form method="post">

        <div class="input-group">
            <label>Panel URL</label>
            <input type="text" value="<?php echo $panelURL ?? ''; ?>" readonly>
        </div>

        <div class="input-group">
            <label>Database Host</label>
            <input type="text" name="db_host" value="localhost" required>
        </div>

        <div class="input-group">
            <label>Database Name</label>
            <input type="text" name="db_name" required>
        </div>

        <div class="input-group">
            <label>Database Username</label>
            <input type="text" name="db_user" required>
        </div>

        <div class="input-group">
            <label>Database Password</label>
            <input type="password" name="db_pass">
        </div>

        <div class="button-group">
            <button type="submit" class="primary-btn">Generate Files</button>
            <button type="button" class="secondary-btn" onclick="skipSetup()">Skip</button>
        </div>

    </form>
</div>

<script>
function skipSetup() {
    window.location.href = 'license.php';
}
</script>

</body>
</html>
