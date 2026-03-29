<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Welcome - FreePanel</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #6a11cb, #2575fc);
    padding: 20px;
}

/* Card */
.card {
    width: 100%;
    max-width: 520px;   /* bigger default */
    padding: 35px;
    border-radius: 20px;
    backdrop-filter: blur(25px) saturate(180%);
    background: rgba(255,255,255,0.12);
    box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    color: white;
    text-align: center;
    animation: fadeIn 0.4s ease;
}

@media (min-width: 1440px) {
    .card {
        max-width: 700px;
        padding: 55px;
    }
}

/* Desktop enhancement */
@media (min-width: 1024px) {
    .card {
        max-width: 600px;
        padding: 45px;
    }
}

/* Tablet */
@media (max-width: 768px) {
    .card {
        max-width: 95%;
        padding: 25px;
    }
}

/* Mobile */
@media (max-width: 480px) {
    body {
        padding: 15px;
    }

    .card {
        max-width: 100%;
        padding: 20px;
        border-radius: 16px;
    }
}

/* Text */
.card h2 {
    margin-bottom: 10px;
    font-size: 22px;
}

.sub {
    font-size: 14px;
    opacity: 0.8;
    margin-bottom: 20px;
}

/* Agreement box */
.agreement {
    text-align: left;
    font-size: 13px;
    background: rgba(255,255,255,0.08);
    padding: 12px;
    border-radius: 10px;
    max-height: 140px;
    overflow-y: auto;
    margin-bottom: 15px;
}

/* Smaller height on mobile */
@media (max-width: 480px) {
    .agreement {
        max-height: 200px;
    }
}

/* Checkbox */
.checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    margin-bottom: 15px;
}

/* Buttons */
button {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.primary {
    background: white;
    color: #333;
}

.primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

button:hover {
    transform: translateY(-2px);
}

/* Animation */
@keyframes fadeIn {
    from {opacity: 0; transform: scale(0.96);}
    to {opacity: 1; transform: scale(1);}
}



/* ===== Custom Scrollbar ===== */

/* Works in Chrome, Edge, Safari */
.agreement::-webkit-scrollbar {
    width: 8px;
}

.agreement::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 10px;
}

.agreement::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #ffffff55, #ffffff22);
    border-radius: 10px;
    backdrop-filter: blur(10px);
}

.agreement::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #ffffffaa, #ffffff55);
}

/* Firefox */
.agreement {
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.5) rgba(255,255,255,0.1);
}


</style>
</head>

<body>

<div class="card">
    <h2>Welcome to FreePanel</h2>
    <p class="sub">Before we begin, please accept the terms</p>

    <div class="agreement">
        <strong>License Agreement</strong><br><br>
        By using this panel, you agree that:<br>
        • You will not misuse the system<br>
        • You accept all risks<br>
        • This software is provided "as is"<br>
        • Redistribution without permission is prohibited<br><br>

        Continue only if you agree to these terms.
    </div>

    <div class="checkbox">
        <input type="checkbox" id="agree" onchange="toggleBtn()">
        <label for="agree">I accept the agreement</label>
    </div>

    <button id="startBtn" class="primary" disabled onclick="goNext()">
        🚀 Let's Go
    </button>
</div>

<script>
function toggleBtn() {
    document.getElementById('startBtn').disabled = !document.getElementById('agree').checked;
}

function goNext() {
    window.location.href = "license.php";
}
</script>

</body>
</html>