<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$results = [];
try {
    $stmt = $pdo->prepare("
        SELECT student_name, topic, score, total_questions, time_taken, practice_date
        FROM practice_results
        ORDER BY practice_date DESC
    ");
    $stmt->execute();
    $results = $stmt->fetchAll();
} catch (PDOException $e) {
    $results = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practice Results — Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .pct-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.82em;
            font-weight: 700;
        }
        .pct-high { background: rgba(46,204,113,0.12); color: #2ecc71; border: 1px solid rgba(46,204,113,0.25); }
        .pct-mid  { background: rgba(243,156,18,0.12);  color: #f39c12; border: 1px solid rgba(243,156,18,0.25); }
        .pct-low  { background: rgba(192,57,43,0.12);   color: #e74c3c; border: 1px solid rgba(192,57,43,0.25); }
        .empty-state { text-align:center; padding: 60px 20px; }
        .empty-icon { font-size:3em; opacity:0.4; margin-bottom:12px; }
        @media (max-width: 600px) {
            table td, table th { padding: 10px 8px; font-size: 0.8em; }
        }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap">

    <div class="header-bar">
        <h2>Student Practice Sessions</h2>
        <a href="dashboard.php" class="back-link">← Dashboard</a>
    </div>

    <?php if (empty($results)): ?>
        <div class="empty-state">
            <div class="empty-icon">📖</div>
            <p style="color:var(--text-muted);">No practice results yet. Results will appear here once students practice.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;" class="glass-card" style="padding:0;">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Topic</th>
                        <th>Score</th>
                        <th>Result</th>
                        <th>Time Taken</th>
                        <th>Practice Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r):
                        $pct = $r['total_questions'] > 0 ? round(($r['score'] / $r['total_questions']) * 100) : 0;
                        $pctClass = $pct >= 75 ? 'pct-high' : ($pct >= 50 ? 'pct-mid' : 'pct-low');
                        
                        $m = floor($r['time_taken'] / 60);
                        $s = $r['time_taken'] % 60;
                        $time_str = $m . "m " . $s . "s";
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($r['student_name']); ?></strong></td>
                            <td><span class="tag"><?php echo htmlspecialchars($r['topic']); ?></span></td>
                            <td><?php echo $r['score']; ?> / <?php echo $r['total_questions']; ?></td>
                            <td><span class="pct-badge <?php echo $pctClass; ?>"><?php echo $pct; ?>%</span></td>
                            <td><?php echo $time_str; ?></td>
                            <td style="color:var(--text-muted); font-size:0.85em;"><?php echo date('M j, Y g:i a', strtotime($r['practice_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p style="font-size:0.78em; color:var(--text-faint); margin-top:12px;">
            <?php echo count($results); ?> session<?php echo count($results) != 1 ? 's' : ''; ?> total
        </p>
    <?php endif; ?>

</div>
</body>
</html>
