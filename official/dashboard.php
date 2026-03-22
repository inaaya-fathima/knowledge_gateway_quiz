<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth_helper.php';

if (!isset($_SESSION['official_logged_in']) || $_SESSION['official_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';
$data = get_all_users();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete_user') {
        $db_id = (int)$_POST['db_id'];
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM quiz_results WHERE user_id = ?")->execute([$db_id]);
            $pdo->prepare("DELETE FROM test_results WHERE user_id = ?")->execute([$db_id]);
            $pdo->prepare("DELETE FROM contacts WHERE user_id = ?")->execute([$db_id]);
            $pdo->prepare("DELETE FROM communication_requests WHERE sender_id = ? OR receiver_id = ?")->execute([$db_id, $db_id]);
            $pdo->prepare("DELETE FROM messages WHERE (sender_id = ? AND sender_type = 'user') OR (receiver_id = ? AND receiver_type = 'user')")->execute([$db_id, $db_id]);
            $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$db_id]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$db_id]);
            $pdo->commit();
            delete_json_user($db_id, 'user');
            $success = "User deleted successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to delete user: " . $e->getMessage();
        }
    } elseif ($action === 'delete_admin') {
        $username = $_POST['username'];
        $nData = [];
        $found = false;
        foreach ($data['admins'] as $a) {
            if ($a['username'] === $username) { $found = true; continue; }
            $nData[] = $a;
        }
        if ($found) {
            $data['admins'] = $nData;
            save_all_users($data);
            $success = "Admin deleted successfully.";
        } else {
            $error = "Admin not found.";
        }
    } elseif ($action === 'add_admin') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        if (empty($username) || empty($password)) {
            $error = "Username and password required.";
        } else {
            if (register_json_user($username, $password, 'admin', [])) {
                $success = "Admin account '$username' created successfully.";
            } else {
                $error = "Username already exists.";
            }
        }
    } elseif ($action === 'add_user') {
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        
        $pwErrors = validate_password_strength($password);
        if (!empty($pwErrors)) {
            $error = 'Password error: ' . implode(', ', $pwErrors) . '.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO users (name) VALUES (?)");
                $stmt->execute([$name]);
                $db_id = $pdo->lastInsertId();
                if (register_json_user($username, $password, 'user', ['db_id' => $db_id, 'name' => $name])) {
                    $success = "User account created successfully.";
                } else {
                    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$db_id]);
                    $error = "Username already taken.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
    // Refresh data after changes
    $data = get_all_users();
}

$db_users = $pdo->query("SELECT id, name, created_at FROM users ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Dashboard — System Access</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { padding: 40px; margin: 0; display: block; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .card { background: var(--card-bg); padding: 30px; border-radius: var(--radius-lg); border: 1px solid var(--border); }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
        .table th { color: var(--text-muted); font-size: 0.9em; text-transform: uppercase; }
        .form-group label { display: block; margin-bottom: 5px; color: var(--text-muted); font-size: 0.9em; }
        .form-group input { width: 100%; padding: 10px; background: var(--bg); border: 1px solid var(--border); color: var(--text); border-radius: 4px; margin-bottom: 15px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 20px; margin-bottom: 30px; }
        .btn-small { padding: 6px 12px; font-size: 0.8em; border-radius: 4px; border: none; cursor: pointer; color: white; background: var(--red-bright); }
    </style>
</head>
<body class="bg-gradient">
<div style="max-width: 1200px; margin: auto;">
    <div class="header">
        <div>
            <h1 style="color:var(--text); font-weight:400; font-size: 2.2em; display:flex; align-items:center; gap:10px;">🛡️ Official Dashboard</h1>
            <p style="color:var(--text-muted);">Manage all system users and administrators.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="view_requests.php" class="btn" style="border: 1px solid #f39c12; color: #f39c12; padding: 8px 16px;">Password Requests</a>
            <a href="logout.php" class="btn" style="border: 1px solid var(--red-bright); color: var(--red-bright); padding: 8px 16px;">Logout</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="error-msg" style="margin-bottom:20px;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="background: rgba(46,204,113,0.1); border: 1px solid rgba(46,204,113,0.3); color:#2ecc71; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <!-- Admins Column -->
        <div>
            <div class="card" style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom:10px;">Administrators</h3>
                <table class="table">
                    <tr><th>Username</th><th>Created</th><th>Actions</th></tr>
                    <?php if (empty($data['admins'])): ?>
                        <tr><td colspan="3" style="color:var(--text-muted);">No admins found.</td></tr>
                    <?php else: ?>
                        <?php foreach($data['admins'] as $admin): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($admin['username'] ?? 'unknown'); ?></td>
                                <td style="color:var(--text-muted);"><?php echo htmlspecialchars($admin['created_at'] ?? 'N/A'); ?></td>
                                <td>
                                        <form method="POST" onsubmit="return confirm('Delete this admin?');" style="margin:0;">
                                            <input type="hidden" name="action" value="delete_admin">
                                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>">
                                            <a href="view_activity.php?type=admin&username=<?php echo urlencode($admin['username']); ?>" class="btn-small" style="background:#3498db; text-decoration:none; display:inline-block; margin-right:5px;">Activity</a>
                                            <button type="submit" class="btn-small">Remove</button>
                                        </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>
            </div>

            <div class="card glass-card">
                <h3>Add New Administrator</h3>
                <p style="color:var(--text-muted); font-size:0.9em; margin-bottom:15px;">Create a new instructor/admin account.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="add_admin">
                    <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
                    <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
                    <button type="submit" class="btn submit-btn" style="width:100%;">Create Admin</button>
                </form>
            </div>
        </div>

        <!-- Users Column -->
        <div>
            <div class="card" style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom:10px;">Students / Users</h3>
                <div style="max-height: 400px; overflow-y: auto;">
                    <table class="table">
                        <tr><th>ID</th><th>Name</th><th>Created</th><th>Actions</th></tr>
                        <?php if (empty($db_users)): ?>
                            <tr><td colspan="4" style="color:var(--text-muted);">No students found.</td></tr>
                        <?php else: ?>
                            <?php foreach($db_users as $user): ?>
                                <tr>
                                    <td style="color:var(--text-faint);">#<?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td style="color:var(--text-muted); font-size: 0.9em;"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Permanently delete this user and ALL their data?');" style="margin:0;">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="db_id" value="<?php echo $user['id']; ?>">
                                            <a href="view_activity.php?type=user&id=<?php echo $user['id']; ?>" class="btn-small" style="background:#3498db; text-decoration:none; display:inline-block; margin-right:5px;">Activity</a>
                                            <a href="change_password.php?id=<?php echo $user['id']; ?>" class="btn-small" style="background:#f39c12; text-decoration:none; display:inline-block; margin-right:5px;">Pass</a>
                                            <button type="submit" class="btn-small">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="card glass-card">
                <h3>Add New Student</h3>
                <p style="color:var(--text-muted); font-size:0.9em; margin-bottom:15px;">Create a new student account manually.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="add_user">
                    <div class="form-group"><label>Full Name</label><input type="text" name="name" required></div>
                    <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
                    <div class="form-group"><label>Password (8+ chars, upper, lower, num, special)</label><input type="password" name="password" required></div>
                    <button type="submit" class="btn submit-btn" style="width:100%;">Create Student</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
