<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../config/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['mark_read'])) {
    $pdo->exec("UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0");
    header("Location: notifications.php");
    exit;
}

// Mark specific as read
if (isset($_GET['read_id'])) {
    $r_id = (int)$_GET['read_id'];
    $pdo->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ?")->execute([$r_id]);
    header("Location: notifications.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 50");
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .notif-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 18px 20px;
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            transition: all 0.2s;
        }
        .notif-card.unread {
            background: rgba(52,152,219,0.08); /* slight blue tint */
            border-color: rgba(52,152,219,0.3);
            border-left: 4px solid var(--primary);
        }
        .notif-icon {
            font-size: 1.5em;
            background: var(--bg);
            border-radius: 50%;
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
        }
        .notif-content { flex: 1; }
        .notif-msg { color: var(--text); font-size: 0.95em; line-height: 1.4; margin-bottom: 5px; }
        .notif-time { color: var(--text-faint); font-size: 0.8em; }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap">
    
    <div class="header-bar" style="margin-bottom: 24px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="margin:0;">System Notifications</h2>
            <p style="margin:4px 0 0; font-size: 0.85em; color:var(--text-muted);">Updates on user requests and system events</p>
        </div>
        <div style="display:flex; gap:10px;">
            <?php if (!empty($notifications)): ?>
                <form method="POST" style="margin:0;">
                    <button type="submit" name="mark_read" class="btn">Mark All Read</button>
                </form>
            <?php endif; ?>
            <a href="dashboard.php" class="back-link">← Back to Hub</a>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="glass-card" style="text-align: center; padding: 50px 20px;">
            <div style="font-size: 3em; margin-bottom: 15px; opacity: 0.6;">📭</div>
            <h3 style="color: var(--text-muted);">No notifications yet</h3>
            <p style="font-size: 0.9em; color: var(--text-faint);">You're all caught up!</p>
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $n): 
            $icon = '🔔';
            if ($n['type'] === 'contact') $icon = '📧';
            elseif ($n['type'] === 'message') $icon = '💬';
            elseif ($n['type'] === 'communication') $icon = '🤝';
        ?>
            <div class="notif-card <?php echo $n['is_read'] ? '' : 'unread'; ?>">
                <div class="notif-icon"><?php echo $icon; ?></div>
                <div class="notif-content">
                    <div class="notif-msg"><?php echo htmlspecialchars($n['message']); ?></div>
                    <div class="notif-time"><?php echo date('M j, Y, g:i a', strtotime($n['created_at'])); ?></div>
                </div>
                <?php if (!$n['is_read']): ?>
                    <a href="?read_id=<?php echo $n['id']; ?>" class="btn" style="padding: 6px 10px; font-size:0.75em;">Mark Read</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</body>
</html>
