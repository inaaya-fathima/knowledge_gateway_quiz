<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/db.php';
require_once '../config/auth_helper.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_account'])) {
        // Delete account
        try {
            $pdo->beginTransaction();
            
            // Cleanup test_detailed_results
            $stmt = $pdo->prepare("SELECT id FROM test_results WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $tr_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($tr_ids)) {
                $in = str_repeat('?,', count($tr_ids) - 1) . '?';
                $pdo->prepare("DELETE FROM test_detailed_results WHERE test_result_id IN ($in)")->execute($tr_ids);
            }
            
            // Delete results
            $pdo->prepare("DELETE FROM quiz_results WHERE user_id = ?")->execute([$_SESSION['user_id']]);
            $pdo->prepare("DELETE FROM test_results WHERE user_id = ?")->execute([$_SESSION['user_id']]);
            $pdo->prepare("DELETE FROM practice_results WHERE user_id = ?")->execute([$_SESSION['user_id']]);
            $pdo->prepare("DELETE FROM contacts WHERE user_id = ?")->execute([$_SESSION['user_id']]);
            $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$_SESSION['user_id']]);
            
            // Delete user
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_SESSION['user_id']]);
            $pdo->commit();
            
            delete_json_user($_SESSION['user_id'], 'user');
            header("Location: logout.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to delete account.";
        }
    } elseif (isset($_POST['change_name'])) {
        $new_name = trim($_POST['new_name']);
        if (!empty($new_name)) {
            $pdo->prepare("UPDATE users SET name = ? WHERE id = ?")->execute([$new_name, $_SESSION['user_id']]);
            $pdo->prepare("UPDATE test_results SET student_name = ? WHERE user_id = ?")->execute([$new_name, $_SESSION['user_id']]);
            $pdo->prepare("UPDATE practice_results SET student_name = ? WHERE user_id = ?")->execute([$new_name, $_SESSION['user_id']]);
            $_SESSION['user_name'] = $new_name;
            $success = "Display Name successfully updated.";
        } else {
            $error = "Name cannot be empty.";
        }
    } elseif (isset($_POST['change_username'])) {
        $new_uname = trim($_POST['new_username']);
        if (!empty($new_uname)) {
            if (change_username_json_user($_SESSION['user_id'], $new_uname, 'user')) {
                $success = "Username successfully updated.";
            } else {
                $error = "Username is already taken or invalid.";
            }
        } else {
            $error = "Username cannot be empty.";
        }
    } else {
        $old_pwd = $_POST['old_password'] ?? '';
        $new_pwd = $_POST['new_password'] ?? '';
        $conf_pwd = $_POST['confirm_password'] ?? '';
        
        if (verify_old_password_json_user($_SESSION['user_id'], $old_pwd, 'user')) {
            if ($new_pwd === $conf_pwd) {
                $pwErrors = validate_password_strength($new_pwd);
                if (empty($pwErrors)) {
                    if (change_password_json_user($_SESSION['user_id'], $new_pwd, 'user')) {
                        $success = "Password successfully updated.";
                    } else {
                        $error = "Error updating password.";
                    }
                } else {
                    $error = 'Password does not meet requirements: ' . implode(', ', $pwErrors) . '.';
                }
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Incorrect old password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="bg-gradient">
<div class="page-wrap" style="max-width: 700px; margin: 30px auto; padding: 0 20px;">
    <div class="header-bar" style="display: flex; justify-content: space-between; align-items:flex-start; margin-bottom: 28px;">
        <div class="page-intro">
            <h2 style="margin-bottom:4px;">Account Settings</h2>
            <p style="font-size: 0.93em; color: var(--text-muted); margin-top: 6px;">Manage your account security and data.</p>
        </div>
        <a href="dashboard.php" class="back-link">← Back to Hub</a>
    </div>
        
    <div style="max-width: 600px;">
            <?php if ($error): ?>
                <div class="error-msg" style="margin-bottom:20px;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div style="background: rgba(46,204,113,0.1); border: 1px solid rgba(46,204,113,0.3); color:#2ecc71; padding:15px; border-radius:8px; margin-bottom:20px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="glass-card" style="margin-bottom: 30px;">
                <h3>Change Password</h3>
                <form action="change_password.php" method="POST" style="margin-top:20px;">
                    <div class="form-group">
                        <label>Old Password</label>
                        <input type="password" name="old_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_pwd" class="btn submit-btn">Update Password</button>
                    <p style="font-size: 0.8em; color: var(--text-muted); margin-top: 10px;">* Disclaimer: Updating your password will log out all other active sessions for your account.</p>
                </form>
            </div>

            <div class="glass-card" style="margin-bottom: 30px;">
                <h3>Change Display Name</h3>
                <form action="change_password.php" method="POST" style="margin-top:20px;">
                    <div class="form-group">
                        <label>New Display Name</label>
                        <input type="text" name="new_name" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" required>
                    </div>
                    <button type="submit" name="change_name" class="btn submit-btn">Update Name</button>
                    <p style="font-size: 0.8em; color: var(--text-muted); margin-top: 10px;">* Disclaimer: Changing your name will update your name on all past and future test results.</p>
                </form>
            </div>
            
            <div class="glass-card" style="margin-bottom: 30px;">
                <h3>Change Username</h3>
                <form action="change_password.php" method="POST" style="margin-top:20px;">
                    <div class="form-group">
                        <label>New Username</label>
                        <input type="text" name="new_username" required>
                    </div>
                    <button type="submit" class="btn submit-btn">Update Username</button>
                    <p style="font-size: 0.8em; color: var(--text-muted); margin-top: 10px;">* Disclaimer: Your username is used for logging in. Please memorize it.</p>
                </form>
            </div>

            <div class="glass-card" style="border: 1px solid rgba(231, 76, 60, 0.4);">
                <h3 style="color: var(--red-bright);">Danger Zone</h3>
                <p style="color: var(--text-muted); margin-top: 10px; margin-bottom: 20px;">
                    Once you delete your account, there is no going back. Please be certain.
                </p>
                <form action="change_password.php" method="POST" onsubmit="return confirm('Are you sure you want to completely delete your account and all associated test data? This cannot be undone.');">
                    <input type="hidden" name="delete_account" value="1">
                    <button type="submit" class="btn" style="background:#c0392b; color:white; border:none; padding:10px 18px; border-radius:6px; cursor:pointer;">
                        Delete Account Permanently
                    </button>
                    <p style="font-size: 0.8em; color: var(--text-muted); margin-top: 10px;">* Disclaimer: Deleting your account will permanently remove all your data, scores, and practice records from the database.</p>
                </form>
            </div>
    </div>
    
    <div style="margin-top: 50px; text-align: center; border-top: 1px solid var(--border); padding-top: 20px; padding-bottom: 20px;">
        <p style="color: var(--text-muted); font-size: 0.9em; margin-bottom: 15px;">Site Navigation</p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; font-size: 0.85em;">
            <a href="dashboard.php" style="color: var(--primary);">Home Dashboard</a> |
            <a href="change_password.php" style="color: var(--primary);">Change Password</a> |
            <a href="change_password.php" style="color: var(--primary);">Change Name</a> |
            <a href="change_password.php" style="color: var(--primary);">Change Username</a> |
            <a href="change_password.php" style="color: var(--red-bright);">Delete Account</a> |
            <a href="logout.php" style="color: var(--primary);">Logout</a>
        </div>
    </div>
</div>
</body>
</html>
