<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$adminUser = $_SESSION['admin_username'] ?? 'unknown';

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
} catch (PDOException $e) {
    $userCount = $questionCount = $resultCount = $topicCount = $testCount = 0;
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
            <a href="logout.php" class="btn">Logout</a>
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
        </div>
    </div>
</body>
</html>