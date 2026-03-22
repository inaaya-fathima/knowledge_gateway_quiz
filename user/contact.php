<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    if (!empty($message)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO contacts (user_id, message) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $message]);
            
            // Notify admins
            $adminMsg = $_SESSION['user_name'] . " sent a new contact form message.";
            $pdo->prepare("INSERT INTO admin_notifications (type, message) VALUES ('contact', ?)")->execute([$adminMsg]);
            
            $success = "Your message has been sent to the admin successfully.";
        } catch (PDOException $e) {
            $error = "Failed to send message: " . $e->getMessage();
        }
    } else {
        $error = "Message cannot be empty.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Admin — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .contact-form textarea {
            width: 100%;
            height: 150px;
            padding: 12px;
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: var(--radius);
            font-family: inherit;
            resize: vertical;
            margin-bottom: 20px;
        }
        .contact-form textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap" style="max-width: 700px; margin: 30px auto; padding: 0 20px;">
    <div class="header-bar" style="display: flex; justify-content: space-between; align-items:flex-start; margin-bottom: 28px;">
        <div class="page-intro">
            <h2 style="margin-bottom:4px;">Contact Admin</h2>
            <p style="font-size: 0.93em; color: var(--text-muted); margin-top: 6px;">Send a one-way message, request, or feedback to the administrators.</p>
        </div>
        <a href="dashboard.php" class="back-link">← Back to Hub</a>
    </div>

    <div style="max-width: 700px;">
            <?php if ($error): ?>
                <div class="error-msg" style="margin-bottom:20px;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div style="background: rgba(46,204,113,0.1); border: 1px solid rgba(46,204,113,0.3); color:#2ecc71; padding:15px; border-radius:8px; margin-bottom:20px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="glass-card">
                <h3>New Message</h3>
                <p style="color:var(--text-muted); margin-bottom: 20px; font-size: 0.9em;">
                    Note: Admins will review your messages. If they reply, it will appear in your Notifications.
                </p>
                
                <form action="contact.php" method="POST" class="contact-form">
                    <textarea name="message" placeholder="Type your message or request here..." required></textarea>
                    <button type="submit" class="btn submit-btn" style="padding: 12px 24px;">Send Message</button>
                </form>
            </div>
            
            <div class="glass-card" style="margin-top: 30px;">
                <h3>Your Previous Messages</h3>
                <div style="margin-top: 20px;">
                    <?php
                    $stmt = $pdo->prepare("SELECT message, status, created_at FROM contacts WHERE user_id = ? ORDER BY created_at DESC");
                    $stmt->execute([$_SESSION['user_id']]);
                    $messages = $stmt->fetchAll();
                    
                    if (empty($messages)): ?>
                        <p style="color: var(--text-muted); font-size: 0.9em;">You haven't sent any messages yet.</p>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid var(--border);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <div style="font-size: 0.8em; color: var(--text-muted);">
                                        Sent on <?php echo date('M j, Y, g:i a', strtotime($msg['created_at'])); ?>
                                    </div>
                                    <div style="font-size: 0.8em; color: var(--text-muted);">
                                        <label style="display: flex; align-items: center; gap: 5px; cursor: default;">
                                            <input type="checkbox" disabled <?php echo ($msg['status'] === 'completed') ? 'checked' : ''; ?>>
                                            <?php echo ($msg['status'] === 'completed') ? 'Completed' : 'Pending Reply'; ?>
                                        </label>
                                    </div>
                                </div>
                                <div style="color: var(--text-light); line-height: 1.5;">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
    </div>
</div>
</body>
</html>
