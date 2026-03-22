<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$adminUser = $_SESSION['admin_username'] ?? 'unknown';
$tr_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$tr_id) {
    echo "Invalid Test Result ID";
    exit;
}

// Ensure this test result belongs to a test created by this admin
$stmt = $pdo->prepare("
    SELECT tr.*
    FROM test_results tr
    INNER JOIN tests t ON tr.test_code = t.test_code
    WHERE tr.id = ? AND t.admin_username = ?
");
$stmt->execute([$tr_id, $adminUser]);
$test_res = $stmt->fetch();

if (!$test_res) {
    echo "Result not found or access denied.";
    exit;
}

// Fetch detailed results
$stmt = $pdo->prepare("
    SELECT d.*, 
           q.question, q.opt_a, q.opt_b, q.opt_c, q.opt_d, q.correct_answer
    FROM test_detailed_results d
    LEFT JOIN test_questions q ON d.question_id = q.id
    WHERE d.test_result_id = ?
");
$stmt->execute([$tr_id]);
$details = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detailed Result — <?php echo htmlspecialchars($test_res['student_name']); ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .q-card { background: var(--card-bg); border: 1px solid var(--border); padding: 20px; margin-bottom: 15px; border-radius: 8px; }
        .q-card h4 { margin-top: 0; color: var(--text); font-size: 1.1em; }
        .q-options { margin-top: 10px; margin-bottom: 15px; font-size: 0.9em; color: var(--text-light); }
        .q-options div { margin-bottom: 5px; }
        .ans-block { display: flex; gap: 20px; font-size: 0.9em; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 6px; }
        .ans-correct { color: #2ecc71; font-weight: bold; }
        .ans-wrong { color: #e74c3c; font-weight: bold; }
        .time-tag { color: #f1c40f; font-weight: 500; }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap">
    <div class="header-bar">
        <div>
            <h2 style="margin-bottom: 5px;">Detailed Result: <?php echo htmlspecialchars($test_res['student_name']); ?></h2>
            <div style="font-size: 0.9em; color: var(--text-muted);">
                Test: <?php echo htmlspecialchars($test_res['topic']); ?> (<?php echo htmlspecialchars($test_res['test_code']); ?>) &bull; 
                Score: <?php echo $test_res['score']; ?>/<?php echo $test_res['total_questions']; ?> &bull; 
                Date: <?php echo date('M j, Y g:i a', strtotime($test_res['attempt_date'])); ?>
            </div>
        </div>
        <a href="view_results.php" class="back-link">← Back to Results</a>
    </div>

    <?php if (empty($details)): ?>
        <div class="glass-card" style="text-align:center; padding: 40px;">
            <p style="color:var(--text-muted);">No detailed data available for this older result.</p>
        </div>
    <?php else: ?>
        <?php foreach ($details as $index => $d): 
            $opts = [$d['opt_a'], $d['opt_b'], $d['opt_c'], $d['opt_d']];
            $opt_labels = ['A','B','C','D'];
            
            $given_idx_label = "Not Answered";
            $given_text = "";
            if ($d['student_answer'] !== null && $d['student_answer'] !== '') {
                $idx = (int)$d['student_answer'];
                $given_idx_label = $opt_labels[$idx] ?? '?';
                $given_text = $opts[$idx] ?? '';
            }

            $corr_idx = (int)$d['correct_answer'];
            $corr_idx_label = $opt_labels[$corr_idx] ?? '?';
            $corr_text = $opts[$corr_idx] ?? '';
        ?>
            <div class="q-card">
                <h4>Q<?php echo $index + 1; ?>: <?php echo htmlspecialchars($d['question']); ?></h4>
                <div class="q-options">
                    <?php foreach($opts as $i => $o): ?>
                        <div><?php echo $opt_labels[$i]; ?>) <?php echo htmlspecialchars($o); ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="ans-block">
                    <div>
                        <strong>Student's Answer:</strong> 
                        <?php if ($d['is_correct']): ?>
                            <span class="ans-correct"><?php echo $given_idx_label; ?> (Correct)</span>
                        <?php else: ?>
                            <span class="ans-wrong"><?php echo $given_idx_label; ?> (Incorrect)</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong>Correct Answer:</strong> <?php echo $corr_idx_label; ?> - <?php echo htmlspecialchars($corr_text); ?>
                    </div>
                    <div>
                        <strong>Time Taken:</strong> <span class="time-tag"><?php echo $d['time_taken']; ?> sec</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
