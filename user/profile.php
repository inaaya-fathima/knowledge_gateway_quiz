<?php
session_start();
// Prevent caching to guarantee fresh session loads
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/db.php';
require_once '../config/auth_helper.php';

$success = '';
$error = '';
$user_id = $_SESSION['user_id'];

// Fetch from DB
$stmt = $pdo->prepare("SELECT name, bio FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$db_user = $stmt->fetch();

$currentBio = $db_user['bio'] ?? '';
$currentUsername = '';

// Fetch username from JSON
$all = get_all_users();
foreach ($all['users'] as $u) {
    if (isset($u['db_id']) && (int)$u['db_id'] === $user_id) {
        $currentUsername = $u['username'];
        break;
    }
}

// Process update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username'] ?? '');
    $newBio      = trim($_POST['bio'] ?? '');

    if (empty($newUsername)) {
        $error = "Username cannot be empty.";
    } else {
        // Apply username change if it fired
        if ($newUsername !== $currentUsername) {
            if (change_username_json_user($user_id, $newUsername)) {
                $currentUsername = $newUsername;
            } else {
                $error = "Username is already taken by someone else.";
            }
        }
        
        // Apply bio change
        if (empty($error)) {
            $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?")->execute([$newBio, $user_id]);
            $currentBio = $newBio;
            $success = "Profile updated successfully!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 600px; margin: 40px auto; padding: 0 20px; }
        .glass-card { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--text-muted); font-size: 0.9em; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); font-family: inherit; font-size: 1em; }
        textarea { resize: vertical; min-height: 100px; }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap">
    
    <div class="header-bar" style="margin-bottom: 24px; display:flex; justify-content:space-between; align-items:center;">
        <h2 style="margin:0;">Edit Profile</h2>
        <a href="dashboard.php" class="back-link">← Back to Hub</a>
    </div>

    <?php if ($error): ?>
        <div class="error-msg" style="margin-bottom:20px;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="background: rgba(46,204,113,0.1); border: 1px solid rgba(46,204,113,0.3); color:#2ecc71; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="glass-card">
        <form method="POST">
            <div class="form-group">
                <label>Display Name (Unchangeable)</label>
                <input type="text" value="<?php echo htmlspecialchars($db_user['name']); ?>" disabled style="opacity:0.6; cursor:not-allowed;">
            </div>
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($currentUsername); ?>" required>
            </div>

            <div class="form-group">
                <label>About Me (Bio)</label>
                <textarea name="bio" placeholder="Tell us a little about yourself..."><?php echo htmlspecialchars($currentBio); ?></textarea>
            </div>

            <button type="submit" class="btn submit-btn" style="width: 100%; padding: 14px; font-size: 1.05em; margin-top: 10px;">Save Profile</button>
        </form>
    </div>

</div>
</body>
</html>
