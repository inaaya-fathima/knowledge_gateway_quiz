<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = '';
$error   = '';
$adminUser = $_SESSION['admin_username'] ?? 'unknown'; // row-level tenancy

// ── Handle Delete Single Question ──
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $idToDelete = (int)$_GET['delete'];
    try {
        // Only delete if this admin owns the question
        $pdo->prepare("DELETE FROM questions WHERE id = ? AND admin_username = ?")->execute([$idToDelete, $adminUser]);
        $message = "Question #$idToDelete deleted successfully.";
    } catch (PDOException $e) {
        $error = "Failed to delete question: " . $e->getMessage();
    }
}

// ── Handle Delete Entire Topic ──
if (isset($_GET['delete_topic']) && !empty($_GET['delete_topic'])) {
    $topicToDelete = trim($_GET['delete_topic']);
    try {
        $stmt = $pdo->prepare("DELETE FROM questions WHERE topic = ? AND admin_username = ?");
        $stmt->execute([$topicToDelete, $adminUser]);
        $deleted = $stmt->rowCount();
        $message = "Topic \"" . htmlspecialchars($topicToDelete) . "\" and all $deleted question(s) deleted.";
    } catch (PDOException $e) {
        $error = "Failed to delete topic: " . $e->getMessage();
    }
}

// ── Fetch topic stats ──
try {
    $topicStats = $pdo->prepare("SELECT topic, COUNT(*) as count FROM questions WHERE admin_username = ? GROUP BY topic ORDER BY topic ASC");
    $topicStats->execute([$adminUser]);
    $topicStats = $topicStats->fetchAll();
} catch (PDOException $e) {
    die("Error fetching stats: " . $e->getMessage());
}

// ── Filter / search ──
$filterTopic = trim($_GET['filter_topic'] ?? '');
$searchQ     = trim($_GET['search_q']     ?? '');

try {
    if (!empty($filterTopic)) {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE admin_username = ? AND topic = ? ORDER BY id DESC");
        $stmt->execute([$adminUser, $filterTopic]);
    } elseif (!empty($searchQ)) {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE admin_username = ? AND (question LIKE ? OR topic LIKE ?) ORDER BY topic ASC, id DESC");
        $stmt->execute([$adminUser, '%' . $searchQ . '%', '%' . $searchQ . '%']);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE admin_username = ? ORDER BY topic ASC, id DESC");
        $stmt->execute([$adminUser]);
    }
    $questions = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions — Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 1100px; margin: 30px auto; padding: 0 20px; }

        .section-title {
            font-family: 'Inter', sans-serif;
            font-size: 0.78em;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-faint);
            margin: 24px 0 12px;
        }

        /* Question rows */
        .q-row {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 14px 18px;
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            transition: all var(--transition);
        }
        .q-row:hover { background: var(--card-hover); border-color: var(--border-hover); }
        .q-num { font-size: 0.75em; color: var(--text-faint); min-width: 36px; padding-top: 2px; }
        .q-body { flex: 1; min-width: 0; }
        .q-text { color: var(--text); font-size: 0.92em; font-weight: 500; margin-bottom: 5px; }
        .q-options { font-size: 0.78em; color: var(--text-muted); line-height: 1.9; }
        .q-correct { color: #2ecc71; font-weight: 600; }
        .q-actions { display: flex; gap: 8px; align-items: flex-start; flex-shrink: 0; }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .empty-icon { font-size: 3em; margin-bottom: 12px; opacity: 0.4; }

        /* ── Custom Confirm Modal ── */
        .confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(4px);
            z-index: 500;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .confirm-overlay.open { display: flex; }
        .confirm-modal {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 32px 28px;
            max-width: 400px;
            width: 90%;
            animation: slideUpFade 0.25s ease-out;
            text-align: center;
        }
        .confirm-modal .warn-icon { font-size: 2.4em; margin-bottom: 12px; }
        .confirm-modal h3 { margin-bottom: 8px; font-size: 1.15em; font-family: 'Inter', sans-serif; }
        .confirm-modal p  { font-size: 0.88em; color: var(--text-muted); margin-bottom: 24px; line-height: 1.5; }
        .confirm-btns { display: flex; gap: 12px; justify-content: center; }
        .confirm-btns .btn { flex: 1; padding: 11px; }

        @media (max-width: 600px) {
            .q-row { flex-direction: column; }
            .q-actions { flex-direction: row; }
        }
    </style>
</head>
<body class="bg-gradient">

<!-- ── Custom Confirm Modal ── -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-modal">
        <div class="warn-icon">⚠️</div>
        <h3 id="confirmTitle">Are you sure?</h3>
        <p id="confirmMsg">This action cannot be undone.</p>
        <div class="confirm-btns">
            <button class="btn" onclick="closeConfirm()">Cancel</button>
            <a href="#" class="btn btn-danger" id="confirmYesBtn">Yes, Delete</a>
        </div>
    </div>
</div>

<div class="page-wrap">

    <div class="header-bar">
        <h2>Manage Questions</h2>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="add_question.php" class="btn submit-btn" id="add-questions-btn">+ Add Questions</a>
            <a href="dashboard.php" class="back-link">← Dashboard</a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="success-msg"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- ── Topic Grid ── -->
    <div class="section-title">Topics (<?php echo count($topicStats); ?>)</div>

    <?php if (empty($topicStats)): ?>
        <div class="empty-state">
            <div class="empty-icon">📂</div>
            <p>No topics yet. <a href="add_question.php" style="color:var(--red-bright);">Add some questions</a> to get started.</p>
        </div>
    <?php else: ?>
        <div class="topic-grid" style="margin-bottom: 32px;">
            <?php foreach ($topicStats as $ts):
                $isActive = ($filterTopic === $ts['topic']);
            ?>
                <div class="topic-card" style="<?php echo $isActive ? 'border-color:var(--red); background:rgba(192,57,43,0.08);' : ''; ?>">
                    <h3><?php echo htmlspecialchars($ts['topic']); ?></h3>
                    <div class="q-count"><?php echo $ts['count']; ?> question<?php echo $ts['count'] != 1 ? 's' : ''; ?></div>
                    <div class="card-actions">
                        <a href="manage_questions.php?filter_topic=<?php echo urlencode($ts['topic']); ?>"
                           class="btn-tiny" id="filter-<?php echo urlencode($ts['topic']); ?>">
                            <?php echo $isActive ? '✓ Viewing' : 'View'; ?>
                        </a>
                        <button class="btn-tiny danger"
                                id="del-topic-btn-<?php echo urlencode($ts['topic']); ?>"
                                onclick="askConfirm(
                                    'Delete Entire Topic?',
                                    'This will delete ALL <?php echo $ts['count']; ?> question(s) in &quot;<?php echo htmlspecialchars(addslashes($ts['topic'])); ?>&quot;. This cannot be undone.',
                                    'manage_questions.php?delete_topic=<?php echo urlencode($ts['topic']); ?>'
                                )">
                            🗑 Delete Topic
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ── Questions List ── -->
    <div class="header-bar" style="margin-top:10px; flex-wrap:wrap; gap:12px;">
        <div class="section-title" style="margin:0;">
            <?php if (!empty($filterTopic)): ?>
                Questions in: <span class="tag"><?php echo htmlspecialchars($filterTopic); ?></span>
                &nbsp;<a href="manage_questions.php" style="color:var(--text-faint); font-size:0.85em; text-decoration:none;">× Clear</a>
            <?php else: ?>
                All Questions (<?php echo count($questions); ?>)
            <?php endif; ?>
        </div>
        <form method="GET" action="manage_questions.php" style="display:flex; gap:8px;">
            <input type="text" name="search_q" placeholder="Search questions…"
                   value="<?php echo htmlspecialchars($searchQ); ?>"
                   style="padding:7px 12px; background:var(--card-bg); border:1px solid var(--border); border-radius:var(--radius-sm); color:var(--text); font-size:0.85em; outline:none; width:200px; transition:border-color var(--transition);"
                   onfocus="this.style.borderColor='var(--red)'" onblur="this.style.borderColor='var(--border)'">
            <button type="submit" class="btn" style="padding:7px 14px; font-size:0.85em;">Search</button>
            <?php if (!empty($searchQ)): ?>
                <a href="manage_questions.php" class="btn" style="padding:7px 14px; font-size:0.85em;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($questions)): ?>
        <div class="empty-state">
            <div class="empty-icon">🔍</div>
            <p>No questions found<?php echo !empty($filterTopic) ? ' for this topic' : ''; ?>.</p>
        </div>
    <?php else: ?>
        <div style="margin-top:8px;">
            <?php
            $optLabels = ['A', 'B', 'C', 'D'];
            foreach ($questions as $q):
                $opts = [$q['opt_a'], $q['opt_b'], $q['opt_c'], $q['opt_d']];
                $correctIdx = (int)$q['correct_answer'];
            ?>
                <div class="q-row">
                    <div class="q-num">#<?php echo $q['id']; ?></div>
                    <div class="q-body">
                        <span class="tag" style="margin-bottom:6px; display:inline-block;"><?php echo htmlspecialchars($q['topic']); ?></span>
                        <div class="q-text"><?php echo htmlspecialchars($q['question']); ?></div>
                        <div class="q-options">
                            <?php foreach ($opts as $i => $opt): ?>
                                <span <?php echo $i == $correctIdx ? 'class="q-correct"' : ''; ?>>
                                    <?php echo $optLabels[$i]; ?>) <?php echo htmlspecialchars($opt); ?>
                                </span><?php if ($i < 3) echo '&nbsp;&nbsp;'; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="q-actions">
                        <button class="btn-tiny danger"
                                id="del-q-<?php echo $q['id']; ?>"
                                onclick="askConfirm(
                                    'Delete Question?',
                                    'Delete question #<?php echo $q['id']; ?>? This cannot be undone.',
                                    'manage_questions.php?delete=<?php echo $q['id']; ?><?php echo !empty($filterTopic) ? '&filter_topic=' . urlencode($filterTopic) : ''; ?>'
                                )">🗑 Delete</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
    function askConfirm(title, msg, url) {
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmMsg').innerHTML = msg;
        document.getElementById('confirmYesBtn').href = url;
        document.getElementById('confirmOverlay').classList.add('open');
    }

    function closeConfirm() {
        document.getElementById('confirmOverlay').classList.remove('open');
    }

    // Close on overlay background click
    document.getElementById('confirmOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeConfirm();
    });

    // Escape key closes modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeConfirm();
    });
</script>
</body>
</html>
