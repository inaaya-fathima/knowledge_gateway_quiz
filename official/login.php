<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth_helper.php';

if (isset($_SESSION['official_logged_in']) && $_SESSION['official_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $official = authenticate_json_user($username, $password, 'official');
        if ($official !== false) {
            $_SESSION['official_logged_in'] = true;
            $_SESSION['official_username'] = $official['username'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Login — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; padding: 20px;}
        .login-card { max-width: 400px; width: 100%; padding: 40px; background: var(--bg-2); border: 1px solid var(--border); border-radius: var(--radius-lg); text-align: center; }
        .login-card h2 { margin-bottom: 20px; font-weight: 400; font-size: 1.8em; }
        .login-card .form-group { text-align: left; margin-bottom: 15px; }
    </style>
</head>
<body class="bg-gradient">
    <div class="login-card glass-card">
        <div style="font-size: 3em; margin-bottom: 10px;">🛡️</div>
        <h2>Official Login</h2>
        <?php if ($error): ?>
            <div class="error-msg" style="margin-bottom: 15px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn submit-btn" style="width: 100%; margin-top: 10px;">Access System</button>
        </form>
        <p style="margin-top:20px; font-size: 0.85em; color: var(--text-muted);"><a href="../index.php">Return to Home</a></p>
    </div>
</body>
</html>
