<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Try both tables for results
$results = [];
try {
    $results = $pdo->query("SELECT student_name, test_code, topic, score, total_questions, attempt_date FROM test_results ORDER BY attempt_date DESC")->fetchAll();
} catch (PDOException $e) {
    // Try quiz_results joined with users
    try {
        $results = $pdo->query("
            SELECT u.name AS student_name, '' AS test_code, '' AS topic, qr.score, qr.total_questions, qr.attempt_date
            FROM quiz_results qr
            LEFT JOIN users u ON qr.user_id = u.id
            ORDER BY qr.attempt_date DESC
        ")->fetchAll();
    } catch (PDOException $e2) {
        $results = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Results — Admin</title>
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
        <h2>Student Results</h2>
        <a href="dashboard.php" class="back-link">← Dashboard</a>
    </div>

    <?php if (empty($results)): ?>
        <div class="empty-state">
            <div class="empty-icon">📊</div>
            <p style="color:var(--text-muted);">No test results yet. Results will appear here once students complete tests.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;" class="glass-card" style="padding:0;">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Test Code</th>
                        <th>Topic</th>
                        <th>Score</th>
                        <th>Result</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r):
                        $pct = $r['total_questions'] > 0 ? round(($r['score'] / $r['total_questions']) * 100) : 0;
                        $pctClass = $pct >= 75 ? 'pct-high' : ($pct >= 50 ? 'pct-mid' : 'pct-low');
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($r['student_name']); ?></strong></td>
                            <td>
                                <?php if (!empty($r['test_code'])): ?>
                                    <code style="color:var(--text-muted);"><?php echo htmlspecialchars($r['test_code']); ?></code>
                                <?php else: ?>
                                    <span style="color:var(--text-faint);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($r['topic'])): ?>
                                    <span class="tag"><?php echo htmlspecialchars($r['topic']); ?></span>
                                <?php else: ?>
                                    <span style="color:var(--text-faint);">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $r['score']; ?> / <?php echo $r['total_questions']; ?></td>
                            <td><span class="pct-badge <?php echo $pctClass; ?>"><?php echo $pct; ?>%</span></td>
                            <td style="color:var(--text-muted); font-size:0.85em;"><?php echo date('M j, Y g:i a', strtotime($r['attempt_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p style="font-size:0.78em; color:var(--text-faint); margin-top:12px;">
            <?php echo count($results); ?> result<?php echo count($results) != 1 ? 's' : ''; ?> total
        </p>
    <?php endif; ?>

</div>
</body>
</html>