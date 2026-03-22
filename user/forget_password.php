<?php
session_start();
require_once '../config/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $institution = trim($_POST['institution'] ?? '');

    if (empty($username) || empty($name) || empty($mobile) || empty($email)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO official_requests (username, name, dob, address, mobile, email, institution) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $name, $dob, $address, $mobile, $email, $institution]);
            $success = "Your password reset request has been submitted to the officials. Please check your email or SMS later.";
        } catch (PDOException $e) {
            $error = "Failed to submit request: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forget Password — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="bg-gradient split-layout">
    <div class="split-left">
        <div class="left-content">
            <h1 style="font-family:'Syne', sans-serif; font-size:3.5em; font-weight:700; color:white; margin:0 0 10px;">Recover Access</h1>
            <p style="font-size:1.1em; color:rgba(255,255,255,0.7); max-width:400px; line-height:1.6;">Provide your details. An official will review your request and reset your password.</p>
        </div>
    </div>
    
    <div class="split-right">
        <div class="auth-box">
            <div class="auth-header">
                <h2>Forget Password</h2>
                <p>Submit a request to officials</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div style="background: rgba(46,204,113,0.1); border: 1px solid rgba(46,204,113,0.3); color:#2ecc71; padding:15px; border-radius:8px; margin-bottom:20px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <div style="margin-top:20px; text-align:center;">
                    <a href="login.php" class="btn">Return to Login</a>
                </div>
            <?php else: ?>
                <form action="forget_password.php" method="POST">
                    <div class="form-group">
                        <label>Username (Required)</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Full Name (Required)</label>
                        <input type="text" name="name" required>
                    </div>
                    <div style="display: flex; gap:10px;">
                        <div class="form-group" style="flex:1;">
                            <label>Date of Birth</label>
                            <input type="date" name="dob" required>
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Mobile Number (Required)</label>
                            <input type="text" name="mobile" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email Address (Required)</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Educational Institution</label>
                        <input type="text" name="institution" required>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" required>
                    </div>
                    <button type="submit" class="btn submit-btn w-100" style="margin-top: 10px;">Submit Request</button>
                    <div class="auth-footer" style="margin-top:20px;">
                        <p>Remembered your password? <a href="login.php">Back to Login</a></p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
