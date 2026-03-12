<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth_helper.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_result_id'])) {
    header("Location: index.php");
    exit;
}

$result_id = $_SESSION['last_result_id'];

try {
    $stmt = $pdo->prepare("SELECT score, total_questions FROM quiz_results WHERE id = ? AND user_id = ?");
    $stmt->execute([$result_id, $_SESSION['user_id']]);
    $result = $stmt->fetch();

    if (!$result) die("Result not found. <a href='dashboard.php'>Go back</a>");

    $score      = $result['score'];
    $total      = $result['total_questions'];
    $percentage = $total > 0 ? round(($score / $total) * 100) : 0;

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// ── Award stars for test ──
$starsEarned = 0;
if ($percentage >= 90)      $starsEarned = 10;
elseif ($percentage >= 80)  $starsEarned = 8;
elseif ($percentage >= 70)  $starsEarned = 6;
elseif ($percentage >= 60)  $starsEarned = 4;
elseif ($percentage >= 40)  $starsEarned = 2;
else                         $starsEarned = 1;

if (!isset($_SESSION['test_stars_awarded_' . $result_id])) {
    add_user_stars($_SESSION['user_id'], $starsEarned);
    $_SESSION['test_stars_awarded_' . $result_id] = true;
}

$totalStars = get_user_stars($_SESSION['user_id']);

// ── Grade info ──
if ($percentage == 100) {
    $grade = 'Perfect Score!';
    $sub   = 'An incredible achievement. You nailed every question! 🏆';
    $color = '#ffc107';
} elseif ($percentage >= 80) {
    $grade = 'Excellent!';
    $sub   = 'Great performance. You clearly know this topic well. 🌟';
    $color = '#2ecc71';
} elseif ($percentage >= 60) {
    $grade = 'Good Job!';
    $sub   = 'Solid effort — keep studying to push even higher. 👍';
    $color = '#27ae60';
} elseif ($percentage >= 40) {
    $grade = 'Keep Going!';
    $sub   = 'You passed, but there\'s room to improve. Practice more! 📖';
    $color = '#f39c12';
} else {
    $grade = 'Keep Practicing!';
    $sub   = 'Don\'t be discouraged — every attempt makes you stronger. 💪';
    $color = '#e74c3c';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Results — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .results-wrap {
            max-width: 540px;
            width: 100%;
            animation: slideUpFade 0.6s ease-out forwards;
        }

        /* Score circle */
        .score-circle-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .score-circle {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: conic-gradient(
                var(--grade-color) calc(var(--pct) * 1%),
                var(--card-hover) 0
            );
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 0 40px color-mix(in srgb, var(--grade-color) 30%, transparent);
        }
        .score-circle::before {
            content: '';
            position: absolute;
            inset: 12px;
            background: var(--bg);
            border-radius: 50%;
        }
        .score-inner {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        .score-inner .pct-num {
            font-size: 2.4em;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            line-height: 1;
            color: var(--text);
        }
        .score-inner .pct-sign {
            font-size: 0.7em;
            color: var(--text-muted);
        }

        /* Card body */
        .result-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 32px 28px;
            text-align: center;
        }
        .grade-title {
            font-size: 1.8em;
            font-weight: 300;
            margin-bottom: 6px;
        }
        .grade-sub {
            font-size: 0.92em;
            color: var(--text-muted);
            margin-bottom: 22px;
            line-height: 1.5;
        }

        /* Score breakdown */
        .score-breakdown {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 20px 0 24px;
        }
        .breakdown-item {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 14px 10px;
        }
        .breakdown-item .val { font-size: 1.5em; font-weight: 700; font-family: 'Inter',sans-serif; color: var(--text); }
        .breakdown-item .lbl { font-size: 0.72em; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 3px; }

        /* Stars earned */
        .stars-panel {
            background: linear-gradient(135deg, rgba(255,193,7,0.1), rgba(255,152,0,0.06));
            border: 1px solid rgba(255,193,7,0.3);
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .stars-panel .stars-left { text-align: left; }
        .stars-panel .stars-left .earned { font-size: 1.3em; font-weight: 700; color: #ffc107; }
        .stars-panel .stars-left .label  { font-size: 0.78em; color: #a08a30; margin-top: 2px; }
        .stars-panel .stars-total { font-size: 0.85em; color: #a08a30; text-align: right; }
        .stars-panel .stars-total span { display: block; font-size: 1.6em; font-weight: 700; color: #ffc107; }

        /* Actions */
        .result-actions {
            display: flex;
            gap: 12px;
            flex-direction: column;
        }
        .result-actions .btn { padding: 13px; font-size: 0.95em; }

        /* 100 stars banner */
        .century-banner {
            background: linear-gradient(135deg, rgba(255,193,7,0.12), rgba(255,152,0,0.08));
            border: 1px solid rgba(255,193,7,0.4);
            border-radius: var(--radius);
            padding: 20px;
            margin-top: 16px;
            text-align: center;
        }
        .century-banner h3 { color: #ffc107; font-family: 'Inter',sans-serif; margin-bottom: 6px; }
        .century-banner p  { font-size:0.88em; margin-bottom: 14px; color: #a08a30; }

        @media (max-width: 400px) {
            .score-breakdown { grid-template-columns: repeat(3, 1fr); gap: 8px; }
        }
    </style>
</head>
<body class="bg-gradient">
<div class="results-wrap">

    <!-- Score ring -->
    <div class="score-circle-wrap">
        <div class="score-circle"
             style="--grade-color: <?php echo $color; ?>; --pct: <?php echo $percentage; ?>;">
            <div class="score-inner">
                <div class="pct-num"><?php echo $percentage; ?><span class="pct-sign">%</span></div>
            </div>
        </div>
    </div>

    <div class="result-card">
        <h2 class="grade-title" style="color:<?php echo $color; ?>;"><?php echo $grade; ?></h2>
        <p class="grade-sub"><?php echo $sub; ?></p>

        <!-- Breakdown -->
        <div class="score-breakdown">
            <div class="breakdown-item">
                <div class="val"><?php echo $score; ?></div>
                <div class="lbl">Correct</div>
            </div>
            <div class="breakdown-item">
                <div class="val"><?php echo $total - $score; ?></div>
                <div class="lbl">Wrong</div>
            </div>
            <div class="breakdown-item">
                <div class="val"><?php echo $total; ?></div>
                <div class="lbl">Total</div>
            </div>
        </div>

        <!-- Stars panel -->
        <div class="stars-panel">
            <div class="stars-left">
                <div class="earned">+<?php echo $starsEarned; ?> ⭐</div>
                <div class="label">Stars earned this test</div>
            </div>
            <div class="stars-total">
                Total Stars
                <span><?php echo $totalStars; ?></span>
            </div>
        </div>

        <?php if ($totalStars >= 100): ?>
            <div class="century-banner">
                <h3>🏆 100 Stars Milestone!</h3>
                <p>You've hit 100 stars — a remarkable achievement! Unlock your certificate below.</p>
                <a href="certificate.php" class="btn" style="background:rgba(255,193,7,0.15); border-color:rgba(255,193,7,0.4); color:#ffc107;">
                    🎓 Claim Certificate
                </a>
            </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="result-actions" style="margin-top:20px;">
            <a href="dashboard.php" class="btn submit-btn" id="return-hub-btn">Return to Hub</a>
            <a href="practice.php" class="btn" id="try-practice-btn">Try Practice Mode</a>
        </div>

        <div style="text-align:center; margin-top:16px; font-size:0.8em; color:var(--text-faint);">
            Participant: <strong style="color:var(--text-muted);"><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
        </div>
    </div>

</div>

<!-- Confetti -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.0/dist/confetti.browser.min.js"></script>
<script>
    const pct = <?php echo $percentage; ?>;
    const colors = ['#c0392b', '#e74c3c', '#ffc107', '#ffffff', '#888888'];

    if (pct >= 40) {
        const duration = pct >= 80 ? 5000 : 3000;
        const end = Date.now() + duration;
        (function frame() {
            confetti({ particleCount: pct >= 80 ? 6 : 3, angle: 60,  spread: 55, origin: {x: 0}, colors });
            confetti({ particleCount: pct >= 80 ? 6 : 3, angle: 120, spread: 55, origin: {x: 1}, colors });
            if (Date.now() < end) requestAnimationFrame(frame);
        }());
    }

    // Special burst for perfect score
    <?php if ($percentage == 100): ?>
    confetti({ particleCount: 200, spread: 120, origin: {y: 0.5}, colors: ['#ffc107', '#fff', '#c0392b'] });
    <?php endif; ?>
</script>
</body>
</html>