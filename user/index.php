<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .portal-wrap {
            max-width: 400px;
            width: 100%;
            text-align: center;
            animation: slideUpFade 0.5s ease-out forwards;
        }
        .portal-icon { font-size: 3.5em; margin-bottom: 14px; }
        .portal-wrap h1 { font-size: 2em; margin-bottom: 8px; font-weight: 300; }
        .portal-wrap p { font-size: 0.9em; color: var(--text-muted); margin-bottom: 30px; }
        .portal-actions { display: flex; flex-direction: column; gap: 12px; }
        .portal-actions .btn { padding: 13px; font-size: 0.95em; }
    </style>
</head>
<body class="bg-gradient">
<div class="portal-wrap">
    <div class="portal-icon">📚</div>
    <h1>Student <span style="color:var(--red-bright);">Portal</span></h1>
    <p>Sign in to access your tests and practice sessions, or create a new account.</p>

    <div class="portal-actions">
        <a href="login.php" class="btn submit-btn" id="login-btn">Log In</a>
        <a href="signup.php" class="btn" id="signup-btn">Create Account</a>
    </div>

    <div style="margin-top: 28px; font-size: 0.8em; color: var(--text-faint);">
        <a href="../index.php" class="back-link">← Back to Home</a>
    </div>
</div>
</body>
</html>