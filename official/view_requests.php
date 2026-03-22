<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['official_logged_in']) || $_SESSION['official_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $req_id = (int)$_POST['req_id'];
    $action = $_POST['action'] ?? '';
    
    if ($action === 'complete') {
        $pdo->prepare("UPDATE official_requests SET status = 'completed' WHERE id = ?")->execute([$req_id]);
    }
    header("Location: view_requests.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM official_requests ORDER BY created_at DESC");
$requests = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password Requests — Official</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 900px; margin: 40px auto; padding: 20px; }
        .req-card { background: var(--card-bg); border: 1px solid var(--border); padding: 20px; border-radius: var(--radius); margin-bottom: 20px; }
        .req-card.completed { opacity: 0.6; }
        .req-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px; }
        .info-label { color: var(--text-muted); font-size: 0.85em; margin-bottom: 2px; }
        .info-val { font-size: 1em; font-weight: 500; color: var(--text); }
        .action-bar { display: flex; justify-content: flex-end; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border); }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap">
    
    <div class="header-bar" style="margin-bottom: 30px;">
        <h2>Password Reset Requests</h2>
        <a href="dashboard.php" class="back-link">← Dashboard</a>
    </div>

    <?php if (empty($requests)): ?>
        <div style="text-align:center; padding:50px; color:var(--text-muted);">No requests pending.</div>
    <?php endif; ?>

    <?php foreach ($requests as $r): ?>
        <div class="req-card <?php echo $r['status'] === 'completed' ? 'completed' : ''; ?>">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0;">Request #<?php echo $r['id']; ?></h3>
                <span class="tag" style="background: <?php echo $r['status'] === 'completed' ? '#27ae60' : '#f39c12'; ?>;">
                    <?php echo ucfirst($r['status']); ?>
                </span>
            </div>
            
            <div class="req-grid">
                <div><div class="info-label">Username</div><div class="info-val"><?php echo htmlspecialchars($r['username']); ?></div></div>
                <div><div class="info-label">Full Name</div><div class="info-val"><?php echo htmlspecialchars($r['name']); ?></div></div>
                <div><div class="info-label">DOB</div><div class="info-val"><?php echo htmlspecialchars($r['dob']); ?></div></div>
                <div><div class="info-label">Mobile</div><div class="info-val"><?php echo htmlspecialchars($r['mobile']); ?></div></div>
                <div><div class="info-label">Email</div><div class="info-val"><?php echo htmlspecialchars($r['email']); ?></div></div>
                <div><div class="info-label">Institution</div><div class="info-val"><?php echo htmlspecialchars($r['institution']); ?></div></div>
                <div style="grid-column: 1 / -1;"><div class="info-label">Address</div><div class="info-val"><?php echo htmlspecialchars($r['address']); ?></div></div>
            </div>
            
            <?php if ($r['status'] === 'pending'): ?>
                <div class="action-bar">
                    <!-- Note: Official will need to look up the user manually by Username and change password from Dashboard -->
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="req_id" value="<?php echo $r['id']; ?>">
                        <input type="hidden" name="action" value="complete">
                        <button type="submit" class="btn submit-btn" onclick="return confirm('Mark this request as safely completed?');">Mark Completed</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    
</div>
</body>
</html>
