<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth_helper.php';

if (!isset($_SESSION['official_logged_in']) || $_SESSION['official_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$db_id = (int)($_GET['id'] ?? 0);
if ($db_id <= 0) {
    die("Invalid user ID.");
}

$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$db_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pwd = $_POST['new_password'] ?? '';
    
    $pwErrors = validate_password_strength($new_pwd);
    if (empty($pwErrors)) {
        if (change_password_json_user($db_id, $new_pwd, 'user')) {
            $success = "Password successfully reset for " . htmlspecialchars($user['name']);
        } else {
            $error = "Failed to update password. User might not exist in JSON.";
        }
    } else {
        $error = 'Password error: ' . implode(', ', $pwErrors) . '.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change User Password — Official</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 600px; margin: 40px auto; padding: 20px; }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap glass-panel">
    
    <div class="header-bar" style="border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 25px;">
        <h2>Reset Password for 📋 <?php echo htmlspecialchars($user['name']); ?></h2>
        <a href="dashboard.php" class="back-link">← Dashboard</a>
    </div>

    <?php if ($error): ?>
        <div class="error-msg" style="margin-bottom:20px;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="background: rgba(46,204,113,0.1); border: 1px solid rgba(46,204,113,0.3); color:#2ecc71; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" required placeholder="Enter new strong password">
            <small style="color:var(--text-faint); margin-top:5px; display:block;">Require 8+ chars, upper, lower, number, special char.</small>
        </div>
        <button type="submit" class="btn submit-btn" style="width:100%;">Change Password</button>
    </form>
    
</div>
</body>
</html>
