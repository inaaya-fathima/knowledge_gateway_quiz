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

$adminUser = $_SESSION['admin_username'] ?? 'unknown';

// Unread admin notifs
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0");
    $unread_admin = $stmt->fetchColumn();
} catch (PDOException $e) {
    $unread_admin = 0;
}

try {
    $userCount     = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $questionCount = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE admin_username = ?");
    $questionCount->execute([$adminUser]);
    $questionCount = $questionCount->fetchColumn();

    $testCount = $pdo->prepare("SELECT COUNT(*) FROM tests WHERE admin_username = ?");
    $testCount->execute([$adminUser]);
    $testCount = $testCount->fetchColumn();

    $topicCount = $pdo->prepare("SELECT COUNT(DISTINCT topic) FROM questions WHERE admin_username = ?");
    $topicCount->execute([$adminUser]);
    $topicCount = $topicCount->fetchColumn();

    $resultCount = $pdo->prepare("
        SELECT COUNT(*) FROM test_results tr
        INNER JOIN tests t ON tr.test_code = t.test_code AND t.admin_username = ?
    ");
    $resultCount->execute([$adminUser]);
    $resultCount = $resultCount->fetchColumn();

    // Fetch active tests
    $activeTests = $pdo->prepare("SELECT id, test_code, topic, num_questions, created_at FROM tests WHERE admin_username = ? AND is_ended = 0 ORDER BY created_at DESC");
    $activeTests->execute([$adminUser]);
    $activeTests = $activeTests->fetchAll();

    // End test logic
    if (isset($_POST['end_test'])) {
        $test_id = (int)$_POST['test_id'];
        
        // get test info
        $tInfo = $pdo->prepare("SELECT topic FROM tests WHERE id = ? AND admin_username = ?");
        $tInfo->execute([$test_id, $adminUser]);
        $test = $tInfo->fetch();
        
        if ($test) {
            $topic = $test['topic'];
            $pdo->beginTransaction();
            // Move from test_questions to questions
            $move = $pdo->prepare("
                INSERT INTO questions (admin_username, topic, question, opt_a, opt_b, opt_c, opt_d, correct_answer)
                SELECT admin_username, topic, question, opt_a, opt_b, opt_c, opt_d, correct_answer
                FROM test_questions WHERE topic = ? AND admin_username = ?
            ");
            $move->execute([$topic, $adminUser]);
            
            // Delete from test_questions
            $del = $pdo->prepare("DELETE FROM test_questions WHERE topic = ? AND admin_username = ?");
            $del->execute([$topic, $adminUser]);
            
            // Mark test as ended
            $mark = $pdo->prepare("UPDATE tests SET is_ended = 1 WHERE id = ?");
            $mark->execute([$test_id]);
            
            $pdo->commit();
            header("Location: dashboard.php?ended=1");
            exit;
        }
    }
} catch (PDOException $e) {
    $userCount = $questionCount = $resultCount = $topicCount = $testCount = 0;
    $activeTests = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px 20px;
            text-align: center;
        }
        .stat-card .stat-number { font-size: 2.4em; }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }
        .action-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px 24px;
            display: flex;
            flex-direction: column;
            transition: all var(--transition);
        }
        .action-card:hover {
            background: var(--card-hover);
            border-color: var(--border-hover);
        }
        .action-card .card-icon { font-size: 2em; margin-bottom: 10px; }
        .action-card h3 { margin: 0 0 6px; color: var(--text); font-size: 1.15em; font-family: 'Inter', sans-serif; font-weight: 600; }
        .action-card p { color: var(--text-muted); font-size: 0.87em; margin-bottom: 20px; flex: 1; }

        @media (max-width: 700px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .action-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 420px) {
            .stats-row { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body class="bg-gradient">
    <div class="container glass-panel" style="max-width: 960px; margin-top: 30px;">

        <div class="header-bar">
            <div>
                <h2 style="margin-bottom:2px;">Admin Dashboard</h2>
                <p style="margin:0; font-size:0.85em; color:var(--text-faint);">Welcome back, <strong style="color:var(--text-muted);"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></p>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="notifications.php" class="btn" style="padding:10px 16px;">
                    🔔 Notifications <?php if($unread_admin > 0) echo "<span style='background:var(--red-bright);color:#fff;border-radius:10px;padding:2px 6px;margin-left:4px;font-size:0.8em;'>$unread_admin</span>"; ?>
                </a>
                <a href="logout.php" class="btn" style="padding:10px 16px;">Logout</a>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number"><?php echo $userCount; ?></div>
                <div class="stat-label">Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $questionCount; ?></div>
                <div class="stat-label">Questions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $topicCount; ?></div>
                <div class="stat-label">Topics</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $resultCount; ?></div>
                <div class="stat-label">Attempts</div>
            </div>
        </div>

        <!-- Actions -->
        <div class="action-grid">
            <div class="action-card">
                <div class="card-icon">➕</div>
                <h3>Add Questions</h3>
                <p>Add new questions manually via MCQ form or import batches through JSON. Organise by topic.</p>
                <a href="add_question.php" class="btn submit-btn" id="add-questions-btn">Add Questions</a>
            </div>

            <div class="action-card">
                <div class="card-icon">📋</div>
                <h3>Manage Questions</h3>
                <p>View all topics as grid cards. Edit or delete individual questions, or remove entire topics.</p>
                <a href="manage_questions.php" class="btn" id="manage-questions-btn">Manage Questions</a>
            </div>

            <div class="action-card">
                <div class="card-icon">🎯</div>
                <h3>Create Test Room</h3>
                <p>Generate a unique test code. Set topic, number of questions, and time limit for students.</p>
                <a href="create_test.php" class="btn" id="create-test-btn">Create Test Room</a>
            </div>

            <div class="action-card">
                <div class="card-icon">📊</div>
                <h3>View Results</h3>
                <p>Review student performance, scores and quiz attempt history across all sessions.</p>
                <a href="view_results.php" class="btn" id="view-results-btn">View Results</a>
            </div>

            <div class="action-card">
                <div class="card-icon">📖</div>
                <h3>Practice Results</h3>
                <p>View practice session marks of every student along with time taken.</p>
                <a href="view_practice.php" class="btn" style="border-color: rgba(46,204,113,0.5);">View Practice Results</a>
            </div>

            <div class="action-card">
                <div class="card-icon">📧</div>
                <h3>Contact Management</h3>
                <p>Read messages sent by students and reply directly to their notification inbox.</p>
                <a href="contacts.php" class="btn" style="border-color: rgba(52,152,219,0.5);">Manage Contacts</a>
            </div>

            <div class="action-card">
                <div class="card-icon">🔔</div>
                <h3>System Notifications</h3>
                <p>View alerts for incoming chat messages, connection requests, and system events.</p>
                <a href="notifications.php" class="btn" style="border-color: rgba(155,89,182,0.5);">View Notifications</a>
            </div>
        </div>

        <?php if (isset($_GET['ended'])): ?>
            <div style="background: rgba(46,204,113,0.1); border: 1px solid rgba(46,204,113,0.3); color:#2ecc71; padding:15px; border-radius:8px; margin-top:20px; text-align:center;">
                Test ended successfully. Questions moved to Practice Section.
            </div>
        <?php endif; ?>

        <!-- Active Tests -->
        <h3 style="margin-top: 40px; margin-bottom: 20px; font-weight: 400; font-size: 1.5em; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Active Tests</h3>
        <?php if (empty($activeTests)): ?>
            <p style="color: var(--text-muted);">No active test rooms.</p>
        <?php else: ?>
            <div class="action-grid" style="grid-template-columns: 1fr;">
                <?php foreach ($activeTests as $at): ?>
                    <div class="action-card" style="flex-direction: row; justify-content: space-between; align-items: center; padding: 20px;">
                        <div>
                            <span class="user-chip" style="margin-bottom: 10px; display:inline-block; font-family: monospace; font-weight: bold; background: rgba(52,152,219,0.2); color: #3498db;">Code: <?php echo htmlspecialchars($at['test_code']); ?></span>
                            <h3 style="font-size: 1.1em; color: var(--text);"><?php echo htmlspecialchars($at['topic']); ?> — <?php echo $at['num_questions']; ?> questions</h3>
                            <p style="margin:0; font-size: 0.85em;">Created: <?php echo date('M j, Y g:i a', strtotime($at['created_at'])); ?></p>
                        </div>
                        <form method="POST" style="margin: 0;" onsubmit="return confirm('End this test? Students will no longer be able to submit, and questions will move to the practice section.');">
                            <input type="hidden" name="end_test" value="1">
                            <input type="hidden" name="test_id" value="<?php echo $at['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="background: var(--red-bright); color: white; border: none; cursor: pointer; border-radius: 6px;">End Test</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>