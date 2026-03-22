<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['official_logged_in']) || $_SESSION['official_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$type = $_GET['type'] ?? '';
$activities = [];
$name = '';

if ($type === 'user') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if (!$user) die("User not found.");
    $name = $user['name'];
    
    // 1. Practice Sessions
    $stmt = $pdo->prepare("SELECT topic, score, practice_date as date_time, 'Practice Session' as act_type FROM practice_results WHERE user_id = ? ORDER BY practice_date DESC LIMIT 50");
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $r) {
        $activities[] = ['date' => $r['date_time'], 'desc' => "Practice on {$r['topic']} (Score: {$r['score']})", 'type' => 'Practice'];
    }
    
    // 2. Test Sessions
    $stmt = $pdo->prepare("SELECT test_code, score, submitted_at as date_time FROM test_results WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 50");
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $r) {
        $activities[] = ['date' => $r['date_time'], 'desc' => "Completed Test {$r['test_code']} (Score: {$r['score']})", 'type' => 'Test'];
    }
    
    // 3. Contacts
    $stmt = $pdo->prepare("SELECT message, created_at as date_time FROM contacts WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $r) {
        $activities[] = ['date' => $r['date_time'], 'desc' => "Contacted Admin: \"" . substr($r['message'], 0, 30) . "...\"", 'type' => 'Contact'];
    }
    
} elseif ($type === 'admin') {
    $username = $_GET['username'] ?? '';
    $name = $username;
    if (empty($username)) die("Invalid admin username.");
    
    // 1. Tests Created
    $stmt = $pdo->prepare("SELECT test_code, topic, created_at as date_time FROM tests WHERE admin_username = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$username]);
    foreach ($stmt->fetchAll() as $r) {
        $activities[] = ['date' => $r['date_time'], 'desc' => "Created Test Room {$r['test_code']} (Topic: {$r['topic']})", 'type' => 'Test Creation'];
    }
    
    // 2. Questions added
    // Questions don't have created_at. So we just show summary perhaps?
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt, topic FROM questions WHERE admin_username = ? GROUP BY topic");
    $stmt->execute([$username]);
    $qStats = $stmt->fetchAll();
    foreach ($qStats as $q) {
        $activities[] = ['date' => date('Y-m-d H:i:s'), 'desc' => "Added {$q['cnt']} question(s) in topic '{$q['topic']}'", 'type' => 'Questions'];
    }
} else {
    die("Invalid request type.");
}

// Sort activities by date DESC
usort($activities, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log — <?php echo htmlspecialchars($name); ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 800px; margin: 40px auto; padding: 20px; }
        .act-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px; padding: 15px; margin-bottom: 12px; display: flex; align-items: center; gap: 15px; }
        .act-icon { font-size: 1.5em; background: rgba(52,152,219,0.1); width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .act-date { color: var(--text-faint); font-size: 0.8em; margin-bottom: 4px; }
        .act-desc { color: var(--text-light); }
        .act-type { font-size: 0.75em; text-transform: uppercase; letter-spacing: 0.05em; font-weight: bold; background: var(--bg-2); padding: 2px 8px; border-radius: 4px; border: 1px solid var(--border); }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap">
    
    <div class="header-bar">
        <h2>Activity Log: <?php echo htmlspecialchars($name); ?> <span style="font-size: 0.5em; vertical-align: middle; margin-left:10px; color:var(--text-faint);">(<?php echo ucfirst($type); ?>)</span></h2>
        <a href="dashboard.php" class="back-link">← Dashboard</a>
    </div>

    <?php if (empty($activities)): ?>
        <div style="text-align:center; padding:50px; color:var(--text-muted);">No recorded activity.</div>
    <?php else: ?>
        <div style="margin-top: 30px;">
            <?php foreach ($activities as $act): ?>
                <?php
                    $icon = '📝';
                    if ($act['type'] == 'Practice') $icon = '📖';
                    if ($act['type'] == 'Test') $icon = '✏️';
                    if ($act['type'] == 'Contact') $icon = '📧';
                    if ($act['type'] == 'Test Creation') $icon = '🎯';
                ?>
                <div class="act-card glass-panel" style="padding:15px; border-radius:10px; margin-bottom:15px; display:flex;">
                    <div class="act-icon"><?php echo $icon; ?></div>
                    <div style="flex:1;">
                        <div class="act-date"><?php echo date('M j, Y g:i a', strtotime($act['date'])); ?> &middot; <span class="act-type"><?php echo $act['type']; ?></span></div>
                        <div class="act-desc"><?php echo htmlspecialchars($act['desc']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
</div>
</body>
</html>
