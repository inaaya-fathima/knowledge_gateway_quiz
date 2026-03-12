<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Get star count for this user
$stars = get_user_stars($_SESSION['user_id']);

// Fetch recent results
try {
    $stmt = $pdo->prepare("SELECT score, total_questions, attempt_date FROM quiz_results WHERE user_id = ? ORDER BY attempt_date DESC LIMIT 3");
    $stmt->execute([$_SESSION['user_id']]);
    $recentResults = $stmt->fetchAll();
} catch (PDOException $e) {
    $recentResults = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 860px; margin: 30px auto; padding: 0 20px; }

        /* Top bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }
        .greeting h2 { margin: 0 0 4px; font-size: 1.8em; font-weight: 300; }
        .greeting p  { margin: 0; font-size: 0.88em; color: var(--text-muted); }
        .top-actions { display: flex; gap: 10px; align-items: center; }

        /* Main action cards */
        .hub-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }
        .hub-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 32px 28px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            text-decoration: none;
            transition: all var(--transition);
            position: relative;
            overflow: hidden;
        }
        .hub-card::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 80px; height: 80px;
            border-radius: 50%;
            opacity: 0;
            transition: opacity var(--transition);
        }
        .hub-card.test-card::before  { background: radial-gradient(circle, rgba(192,57,43,0.2), transparent 70%); }
        .hub-card.prac-card::before  { background: radial-gradient(circle, rgba(80,80,80,0.3), transparent 70%); }

        .hub-card:hover { transform: translateY(-4px); box-shadow: 0 12px 36px rgba(0,0,0,0.4); }
        .hub-card:hover::before { opacity: 1; }
        .hub-card.test-card:hover { border-color: var(--red-dim); }
        .hub-card.prac-card:hover { border-color: var(--border-hover); }

        .hub-card .card-icon { font-size: 2.4em; line-height: 1; }
        .hub-card h3 { margin: 0; color: var(--text); font-size: 1.3em; font-family: 'Inter', sans-serif; font-weight: 600; }
        .hub-card p  { margin: 0; font-size: 0.87em; color: var(--text-muted); line-height: 1.5; flex: 1; }
        .hub-card .arrow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8em;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-top: 6px;
        }
        .hub-card.test-card .arrow { color: var(--red-bright); }
        .hub-card.prac-card .arrow { color: var(--text-muted); }

        /* Stats row */
        .stats-small {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }
        .mini-stat {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
            text-align: center;
        }
        .mini-stat .val { font-size: 1.8em; font-weight: 700; color: var(--text); font-family: 'Inter',sans-serif; line-height: 1; }
        .mini-stat .lbl { font-size: 0.75em; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px; }

        /* Recent results */
        .recent-title {
            font-size: 0.78em;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-faint);
            margin-bottom: 10px;
        }
        .result-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 0.88em;
        }
        .result-row:last-child { border-bottom: none; }
        .result-pct {
            font-weight: 700;
            font-family: 'Inter', sans-serif;
        }
        .pct-high { color: var(--ok); }
        .pct-mid  { color: #f39c12; }
        .pct-low  { color: var(--red-bright); }

        @media (max-width: 600px) {
            .hub-grid { grid-template-columns: 1fr; }
            .stats-small { grid-template-columns: repeat(3, 1fr); gap: 8px; }
            .top-bar { flex-direction: column; align-items: flex-start; gap: 12px; }
        }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap">

    <!-- Top bar -->
    <div class="top-bar">
        <div class="greeting">
            <h2>Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋</h2>
            <p>What would you like to do today?</p>
        </div>
        <div class="top-actions">
            <!-- Star badge -->
            <div class="star-badge" id="star-badge">
                <span class="star-icon">⭐</span>
                <span><?php echo $stars; ?> Stars</span>
            </div>
            <a href="../admin/logout.php" class="btn" style="padding: 8px 16px; font-size:0.85em;">Logout</a>
        </div>
    </div>

    <!-- Main options -->
    <div class="hub-grid">
        <!-- Official Test -->
        <a href="test_agreement.php" class="hub-card test-card" id="official-test-card">
            <div class="card-icon">🎯</div>
            <h3>Official Test</h3>
            <p>Enter your instructor-provided test code to begin a timed, scored exam. Results are recorded.</p>
            <div class="arrow">Start Exam →</div>
        </a>

        <!-- Practice -->
        <a href="practice.php" class="hub-card prac-card" id="practice-card">
            <div class="card-icon">📖</div>
            <h3>Practice Zone</h3>
            <p>Choose any topic, set your own optional timer, and practice at your own pace. No pressure!</p>
            <div class="arrow">Browse Topics →</div>
        </a>
    </div>

    <!-- Stats + Recent -->
    <?php
    $totalAttempts = count($recentResults);
    $avgScore = 0;
    if ($totalAttempts > 0) {
        $pcts = array_map(fn($r) => round(($r['score'] / max($r['total_questions'], 1)) * 100), $recentResults);
        $avgScore = round(array_sum($pcts) / count($pcts));
    }

    // Streak (simple: check if last test was today or yesterday)
    $streak = get_user_streak($_SESSION['user_id']);
    ?>

    <div class="stats-small">
        <div class="mini-stat">
            <div class="val"><?php echo $totalAttempts; ?></div>
            <div class="lbl">Recent Tests</div>
        </div>
        <div class="mini-stat">
            <div class="val"><?php echo $avgScore; ?>%</div>
            <div class="lbl">Avg Score</div>
        </div>
        <div class="mini-stat">
            <div class="val"><?php echo $streak; ?>🔥</div>
            <div class="lbl">Day Streak</div>
        </div>
    </div>

    <?php if (!empty($recentResults)): ?>
        <div class="glass-card" style="padding: 20px;">
            <div class="recent-title">Recent Activity</div>
            <?php foreach ($recentResults as $r):
                $pct = $r['total_questions'] > 0 ? round(($r['score'] / $r['total_questions']) * 100) : 0;
                $pctClass = $pct >= 80 ? 'pct-high' : ($pct >= 50 ? 'pct-mid' : 'pct-low');
            ?>
                <div class="result-row">
                    <span style="color:var(--text-muted);"><?php echo date('M j, g:i a', strtotime($r['attempt_date'])); ?></span>
                    <span><?php echo $r['score']; ?> / <?php echo $r['total_questions']; ?> correct</span>
                    <span class="result-pct <?php echo $pctClass; ?>"><?php echo $pct; ?>%</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($stars >= 100): ?>
        <!-- 100-star celebration banner -->
        <div class="glass-card" style="margin-top:20px; border-color:rgba(255,193,7,0.4); background:linear-gradient(135deg,rgba(255,193,7,0.08),rgba(255,152,0,0.05)); text-align:center; padding:28px;">
            <div style="font-size:2.5em; margin-bottom:8px;">🏆</div>
            <h3 style="color:#ffc107; font-family:'Inter',sans-serif; margin-bottom:6px;">You've reached 100 Stars!</h3>
            <p style="margin-bottom:16px;">Outstanding dedication! You've unlocked a special achievement.</p>
            <a href="certificate.php" class="btn" style="background:rgba(255,193,7,0.15); border-color:rgba(255,193,7,0.3); color:#ffc107;">🎓 Claim Your Certificate</a>
        </div>
    <?php endif; ?>

</div>
</body>
</html>