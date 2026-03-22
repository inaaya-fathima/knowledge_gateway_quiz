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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $user_id = (int)$_POST['user_id'];
    $contact_id = (int)$_POST['contact_id'];
    $reply_msg = trim($_POST['reply_msg']);
    
    if (!empty($reply_msg)) {
        $full_msg = "Admin replied to your message:\n\n\"" . $reply_msg . "\"";
        $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'admin_reply', ?)")->execute([$user_id, $full_msg]);
        if ($contact_id > 0) {
            $pdo->prepare("UPDATE contacts SET status = 'completed' WHERE id = ?")->execute([$contact_id]);
        }
        $_SESSION['contact_success'] = "Reply sent successfully to User #" . $user_id;
    }
    header("Location: contacts.php");
    exit;
}

if (isset($_POST['delete'])) {
    $id = (int)$_POST['contact_id'];
    $pdo->prepare("DELETE FROM contacts WHERE id = ?")->execute([$id]);
    $_SESSION['contact_success'] = "Message removed.";
    header("Location: contacts.php");
    exit;
}

$success = isset($_SESSION['contact_success']) ? $_SESSION['contact_success'] : '';
unset($_SESSION['contact_success']);

$stmt = $pdo->query("SELECT c.id, c.user_id, c.message, c.status, c.created_at, u.name as user_name FROM contacts c LEFT JOIN users u ON c.user_id = u.id ORDER BY created_at DESC");
$msgs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Management — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .contact-box {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }
        .contact-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }
        .contact-body {
            font-size: 0.95em;
            line-height: 1.5;
            color: var(--text-light);
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(0,0,0,0.15);
            border-radius: 4px; border: 1px solid rgba(255,255,255,0.05);
        }
        .reply-area {
            display: flex; gap: 10px; margin-top: 10px;
        }
        .reply-area input {
            flex: 1; padding: 10px 14px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); font-family: inherit;
        }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap">
    
    <div class="header-bar" style="margin-bottom: 24px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="margin:0;">User Contacts</h2>
            <p style="margin:4px 0 0; font-size: 0.85em; color:var(--text-muted);">View and reply to messages sent by students</p>
        </div>
        <a href="dashboard.php" class="back-link">← Back to Hub</a>
    </div>

    <?php if ($success): ?>
        <div style="background: rgba(46,204,113,0.1); border: 1px solid rgba(46,204,113,0.3); color:#2ecc71; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($msgs)): ?>
        <div class="glass-card" style="text-align: center; padding: 50px 20px;">
            <p style="color: var(--text-muted);">Inbox is empty.</p>
        </div>
    <?php else: ?>
        <?php foreach ($msgs as $m): ?>
            <div class="contact-box">
                <div class="contact-header">
                    <div>
                        <strong><?php echo htmlspecialchars($m['user_name'] ?? 'Unknown User'); ?></strong>
                        <span style="font-size: 0.8em; color: var(--text-faint); margin-left:10px;">(ID: <?php echo $m['user_id']; ?>)</span>
                        <?php if($m['status'] == 'completed'): ?>
                            <span style="color:#2ecc71; font-size: 0.8em; margin-left: 10px;">✓ Replied</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 0.85em; color: var(--text-muted);">
                        <?php echo date('M j, Y, g:i a', strtotime($m['created_at'])); ?> 
                        &nbsp;&bull;&nbsp;
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this message?');">
                            <input type="hidden" name="contact_id" value="<?php echo $m['id']; ?>">
                            <button type="submit" name="delete" style="background:none; border:none; color:var(--red-bright); cursor:pointer; font-size:1em;">Delete</button>
                        </form>
                    </div>
                </div>
                
                <div class="contact-body">
                    <?php echo nl2br(htmlspecialchars($m['message'])); ?>
                </div>
                
                <form class="reply-area" method="POST">
                    <input type="hidden" name="user_id" value="<?php echo $m['user_id']; ?>">
                    <input type="hidden" name="contact_id" value="<?php echo $m['id']; ?>">
                    <input type="text" name="reply_msg" placeholder="Type a reply to send to their notifications..." required autocomplete="off">
                    <button type="submit" name="reply" class="btn submit-btn" style="padding: 10px 24px;">Send Reply</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</body>
</html>
