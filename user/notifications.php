<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/db.php';
require_once '../config/auth_helper.php';

$success = '';
$error = '';

// Handle actions (accept/reject request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['req_id'])) {
        $req_id = (int)$_POST['req_id'];
        $action = $_POST['action'];
        
        // Ensure request belongs to this user
        $stmt = $pdo->prepare("SELECT * FROM communication_requests WHERE id = ? AND receiver_id = ?");
        $stmt->execute([$req_id, $_SESSION['user_id']]);
        $req = $stmt->fetch();
        
        if ($req) {
            if ($action === 'accept') {
                $pdo->prepare("UPDATE communication_requests SET status = 'accepted' WHERE id = ?")->execute([$req_id]);
                // Send request back automatically (per requirement: "After accepting, the receiver should also send a request back to the original sender.")
                // Ensure no existing request back
                $chk = $pdo->prepare("SELECT id FROM communication_requests WHERE sender_id = ? AND receiver_id = ?");
                $chk->execute([$_SESSION['user_id'], $req['sender_id']]);
                if (!$chk->fetch()) {
                    $pdo->prepare("INSERT INTO communication_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')")->execute([$_SESSION['user_id'], $req['sender_id']]);
                }
                
                // Add notification to other user
                $msg = $_SESSION['user_name'] . " accepted your communication request.";
                $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'communication', ?)")->execute([$req['sender_id'], $msg]);
                
                $success = "Request accepted.";
            } elseif ($action === 'reject') {
                $pdo->prepare("UPDATE communication_requests SET status = 'rejected' WHERE id = ?")->execute([$req_id]);
                $success = "Request rejected.";
            }
        } else {
            $error = "Invalid request.";
        }
    }
}

// Mark notifications as read
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);

// Fetch notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

// Fetch pending communication requests
$stmt = $pdo->prepare("
    SELECT c.*, u.name as sender_name 
    FROM communication_requests c
    JOIN users u ON c.sender_id = u.id
    WHERE c.receiver_id = ? AND c.status = 'pending'
    ORDER BY c.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$pendingRequests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .notif-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 15px;
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .notif-icon {
            font-size: 1.5em;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .notif-content { flex: 1; }
        .notif-time { font-size: 0.8em; color: var(--text-muted); margin-top: 5px; }
        .notif-unread { border-left: 3px solid var(--red-bright); }
        
        .req-actions { margin-top: 10px; display: flex; gap: 8px; }
        .req-actions form { margin: 0; }
        .btn-small { padding: 6px 12px; font-size: 0.85em; border-radius: 4px; border: none; cursor: pointer; color: white; }
        .btn-accept { background: #2ecc71; }
        .btn-reject { background: #e74c3c; }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap" style="max-width: 700px; margin: 30px auto; padding: 0 20px;">
    <div class="header-bar" style="display: flex; justify-content: space-between; align-items:flex-start; margin-bottom: 28px;">
        <div class="page-intro">
            <h2 style="margin-bottom:4px;">Notifications</h2>
            <p style="font-size: 0.93em; color: var(--text-muted); margin-top: 6px;">Stay updated on requests and announcements.</p>
        </div>
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

    <!-- Pending Requests -->
    <?php if (!empty($pendingRequests)): ?>
        <h3 style="margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Connection Requests</h3>
        <?php foreach ($pendingRequests as $req): ?>
            <div class="notif-card" style="border-color: rgba(52,152,219,0.3); background: rgba(52,152,219,0.05);">
                <div class="notif-icon">🤝</div>
                <div class="notif-content">
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($req['sender_name']); ?> (User ID: <?php echo $req['sender_id']; ?>) sent you a connection request.</div>
                    <div class="notif-time"><?php echo date('M j, Y g:i a', strtotime($req['created_at'])); ?></div>
                    <div class="req-actions">
                        <form method="POST">
                            <input type="hidden" name="req_id" value="<?php echo $req['id']; ?>">
                            <input type="hidden" name="action" value="accept">
                            <button type="submit" class="btn-small btn-accept">Accept</button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="req_id" value="<?php echo $req['id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn-small btn-reject">Reject</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h3 style="margin-bottom: 15px; margin-top: 30px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Recent Notifications</h3>
    <?php if (empty($notifications)): ?>
        <p style="color: var(--text-muted);">You have no notifications yet.</p>
    <?php else: ?>
        <?php foreach ($notifications as $n): 
            $icon = '🔔';
            if ($n['type'] === 'communication') $icon = '💬';
            if ($n['type'] === 'test') $icon = '📝';
            if ($n['type'] === 'system') $icon = '⚙️';
            if ($n['type'] === 'admin') $icon = '📢';
            
            $class = 'notif-card';
            if (!$n['is_read']) $class .= ' notif-unread';
        ?>
            <div class="<?php echo $class; ?>">
                <div class="notif-icon"><?php echo $icon; ?></div>
                <div class="notif-content">
                    <div><?php echo nl2br(htmlspecialchars($n['message'])); ?></div>
                    <div class="notif-time"><?php echo date('M j, Y g:i a', strtotime($n['created_at'])); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</body>
</html>
