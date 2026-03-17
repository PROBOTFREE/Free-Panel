<?php

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$panelURL = $protocol . $host . $scriptDir . '/public';

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

    echo "<div style='padding:20px;font-family:sans-serif'><h3>Setup Complete ✅</h3><p><strong>conn.php</strong> and <strong>.env</strong> have been created.</p></div>";
    
    // Show HTML with toast and redirect
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Setup Complete</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        <script>
            setTimeout(function() {
                window.location.href = 'license.php';
            }, 2000);
        </script>
    </head>
    <body class='bg-light'>
        <div class='container mt-5'>
            <div class='alert alert-success text-center shadow'>
                ✅ Setup Complete! Redirecting to <strong>license.php</strong> in 2 seconds...
            </div>
        </div>
    </body>
    </html>";
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
