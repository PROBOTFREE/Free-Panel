<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $panelURL = trim($_POST['panel_url']);
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
<html>
<head>
    <title>Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4">
        <h3 class="mb-4">Install Configuration</h3>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Panel URL (with https://)</label>
                <input type="text" name="panel_url" class="form-control" placeholder="https://example.com/public" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Database Host</label>
                <input type="text" name="db_host" class="form-control" value="localhost" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Database Name</label>
                <input type="text" name="db_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Database Username</label>
                <input type="text" name="db_user" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Database Password</label>
                <input type="password" name="db_pass" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Generate Files</button>
            <button type="button" class="btn btn-secondary" onclick="skipSetup()">Skip</button>
        </form>
    </div>
</div>

<script>
        function skipSetup() {
            window.location.href = 'license.php';
        }
    </script>

</body>
</html>
