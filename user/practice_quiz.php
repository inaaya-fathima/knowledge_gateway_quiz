<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$topic = $_GET['topic'] ?? '';
$customTimer = max(0, (int)($_GET['timer'] ?? 0)); // 0 = no limit, >0 = minutes

if (empty($topic)) {
    die("Invalid topic. <a href='practice.php'>Go Back</a>");
}

try {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE topic = ? ORDER BY RAND() LIMIT 20");
    $stmt->execute([$topic]);
    $questions = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching questions: " . $e->getMessage());
}

if (empty($questions)) {
    die("No questions found for this topic. <a href='practice.php'>Go Back</a>");
}

$is_submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$score  = 0;
$total  = count($questions);

if ($is_submitted) {
    if (isset($_POST['answers']) && is_array($_POST['answers'])) {
        foreach ($questions as $index => $q) {
            $user_answer = $_POST['answers'][$index] ?? -1;
            if ((int)$user_answer === (int)$q['correct_answer']) {
                $score++;
            }
        }
    }

    // Award stars for practice completion
    $pct = $total > 0 ? round(($score / $total) * 100) : 0;
    $starsEarned = 0;
    if ($pct >= 90)      $starsEarned = 5;
    elseif ($pct >= 75)  $starsEarned = 4;
    elseif ($pct >= 60)  $starsEarned = 3;
    elseif ($pct >= 40)  $starsEarned = 2;
    else                 $starsEarned = 1;

    // Add stars (practice gives stars once per session)
    if (!isset($_SESSION['practice_stars_awarded_' . md5($topic . $_SESSION['user_id'])])) {
        add_user_stars($_SESSION['user_id'], $starsEarned);
        $_SESSION['practice_stars_awarded_' . md5($topic . $_SESSION['user_id'])] = true;
    }

    $totalStars = get_user_stars($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practice: <?php echo htmlspecialchars($topic); ?> — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 800px; margin: 20px auto; padding: 0 20px; }

        /* Result panel */
        .result-panel {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 36px 28px;
            text-align: center;
            margin-bottom: 32px;
            animation: slideUpFade 0.5s ease-out;
        }
        .result-panel .big-pct {
            font-size: 5em;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            line-height: 1;
            color: var(--text);
        }
        .result-panel .score-line {
            font-size: 1.1em;
            color: var(--text-muted);
            margin: 8px 0 16px;
        }
        .result-panel .grade-msg {
            font-family: 'Source Serif 4', serif;
            font-size: 1.5em;
            font-weight: 300;
            margin-bottom: 8px;
        }
        .stars-earned {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, rgba(255,193,7,0.15), rgba(255,152,0,0.1));
            border: 1px solid rgba(255,193,7,0.35);
            border-radius: 24px;
            padding: 8px 20px;
            font-size: 0.95em;
            font-weight: 600;
            color: #ffc107;
            margin: 16px auto 0;
            animation: starPop 0.6s 0.5s ease-out both;
        }

        .result-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        .result-actions .btn { padding: 12px 24px; }

        /* Review section */
        .review-title {
            font-size: 0.8em;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-faint);
            margin: 28px 0 14px;
        }

        /* Timer display */
        .practice-timer-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        @media (max-width: 500px) {
            .result-panel .big-pct { font-size: 3.5em; }
        }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap">

    <!-- Header -->
    <div class="sticky-header" style="margin-bottom:16px;">
        <h2>Practice: <?php echo htmlspecialchars($topic); ?></h2>
        <div style="display:flex; align-items:center; gap:14px;">
            <?php if (!$is_submitted && $customTimer > 0): ?>
                <div class="timer-box" id="practiceTimer">--:--</div>
            <?php elseif (!$is_submitted): ?>
                <span style="color:var(--text-faint); font-size:0.85em;">No limit</span>
            <?php endif; ?>
            <span style="color:var(--text-muted); font-size:0.85em;"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
    </div>

    <?php if ($is_submitted): ?>
        <!-- ── Results panel ── -->
        <?php
        $grade_color = '#2ecc71';
        $grade_msg   = 'Outstanding! 🎉';
        if ($pct < 90) { $grade_color = '#2ecc71'; $grade_msg = 'Excellent work! 🌟'; }
        if ($pct < 75) { $grade_color = '#27ae60'; $grade_msg = 'Great job! 👏'; }
        if ($pct < 60) { $grade_color = '#f39c12'; $grade_msg = 'Good effort!  Keep it up 👍'; }
        if ($pct < 40) { $grade_color = var_export($grade_color, true); $grade_msg = 'Keep practicing! You\'ll get there 💪'; $grade_color = '#e67e22'; }
        if ($pct < 25) { $grade_color = 'var(--red-bright)'; $grade_msg = 'More practice needed — don\'t give up! 🔋'; }
        ?>
        <div class="result-panel">
            <div class="big-pct" style="color:<?php echo $grade_color; ?>;"><?php echo $pct; ?>%</div>
            <div class="score-line"><?php echo $score; ?> / <?php echo $total; ?> correct</div>
            <div class="grade-msg" style="color:<?php echo $grade_color; ?>;"><?php echo $grade_msg; ?></div>

            <div class="stars-earned">
                ⭐ +<?php echo $starsEarned; ?> stars earned!
                &nbsp;(Total: <?php echo $totalStars; ?>)
            </div>

            <div class="result-actions">
                <a href="practice.php" class="btn">← Back to Topics</a>
                <a href="practice_quiz.php?topic=<?php echo urlencode($topic); ?>&timer=<?php echo $customTimer; ?>" class="btn submit-btn">🔄 Retry Topic</a>
            </div>
        </div>

        <div class="review-title">Review Your Answers</div>
    <?php endif; ?>

    <!-- Questions form -->
    <form action="practice_quiz.php?topic=<?php echo urlencode($topic); ?>&timer=<?php echo $customTimer; ?>" method="POST">
        <?php foreach ($questions as $index => $q):
            $opts = [0 => $q['opt_a'], 1 => $q['opt_b'], 2 => $q['opt_c'], 3 => $q['opt_d']];
            $optLabels = ['A','B','C','D'];
        ?>
            <div class="question-card">
                <h3>Q<?php echo $index + 1; ?>: <?php echo htmlspecialchars($q['question']); ?></h3>
                <div class="options">
                    <?php foreach ($opts as $oi => $opt_text):
                        $labelClass = '';
                        $checked    = '';
                        $disabled   = '';

                        if ($is_submitted) {
                            $selected = isset($_POST['answers'][$index]) ? (int)$_POST['answers'][$index] : -1;
                            $isCorrect = ((int)$q['correct_answer'] === $oi);
                            if ($selected === $oi) {
                                $checked    = 'checked';
                                $labelClass = $isCorrect ? 'correct-ans' : 'wrong-ans';
                            } elseif ($isCorrect) {
                                $labelClass = 'correct-ans';
                            }
                            $disabled = 'disabled';
                        }
                    ?>
                        <label class="<?php echo $labelClass; ?>" style="<?php echo $is_submitted ? 'cursor:default;' : ''; ?>">
                            <input type="radio"
                                   name="answers[<?php echo $index; ?>]"
                                   value="<?php echo $oi; ?>"
                                   <?php echo $checked; ?>
                                   <?php echo $disabled ?: 'required'; ?>>
                            <span><?php echo $optLabels[$oi]; ?>)&nbsp;<?php echo htmlspecialchars($opt_text); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$is_submitted): ?>
            <div style="margin-top:24px; margin-bottom:36px;">
                <button type="submit" class="btn submit-btn w-100" id="submit-practice-btn"
                        style="padding:14px; font-size:1em;"
                        onclick="return confirm('Submit your practice answers? You can review them after.');">
                    Submit Practice
                </button>
                <div style="text-align:center; margin-top:12px;">
                    <a href="practice.php" class="back-link">Cancel &amp; go back</a>
                </div>
            </div>
        <?php endif; ?>
    </form>

</div>

<!-- Confetti (on completion) -->
<?php if ($is_submitted): ?>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.0/dist/confetti.browser.min.js"></script>
<script>
    (function launchConfetti() {
        const pct    = <?php echo $pct; ?>;
        const colors = pct >= 60
            ? ['#c0392b', '#e74c3c', '#ffc107', '#f39c12', '#ffffff']
            : ['#555', '#888', '#c0392b', '#ffffff'];

        const duration = pct >= 75 ? 4000 : 2000;
        const end      = Date.now() + duration;

        (function frame() {
            confetti({ particleCount: 4, angle: 60,  spread: 55, origin: {x: 0}, colors });
            confetti({ particleCount: 4, angle: 120, spread: 55, origin: {x: 1}, colors });
            if (Date.now() < end) requestAnimationFrame(frame);
        }());
    })();
</script>
<?php endif; ?>

<!-- Practice timer (optional) -->
<?php if (!$is_submitted && $customTimer > 0): ?>
<script>
    var timeLeft = <?php echo $customTimer * 60; ?>;
    var timerEl  = document.getElementById('practiceTimer');
    var form     = document.querySelector('form');

    function tick() {
        if (timeLeft <= 0) {
            timerEl.textContent = '00:00';
            alert('Time is up! Submitting your practice answers.');
            form.submit();
            return;
        }
        var m = Math.floor(timeLeft / 60);
        var s = timeLeft % 60;
        timerEl.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;

        if (timeLeft <= 60) {
            timerEl.style.animation = 'pulse 1s infinite';
        }
        timeLeft--;
        setTimeout(tick, 1000);
    }
    tick();
</script>
<?php endif; ?>

</body>
</html>