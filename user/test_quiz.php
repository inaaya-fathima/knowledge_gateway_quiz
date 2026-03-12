<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['current_test'])) {
    header("Location: dashboard.php");
    exit;
}

$test_info      = $_SESSION['current_test'];
$time_limit_sec = $test_info['time_limit'] * 60;
$time_passed    = time() - $test_info['start_time'];
$time_remaining = $time_limit_sec - $time_passed;

if ($test_info['time_limit'] > 0 && $time_remaining <= 0) {
    header("Location: test_submit.php");
    exit;
}

// Fetch questions from the OFFICIAL test question pool (separate from practice)
$placeholders = implode(',', array_fill(0, count($test_info['q_ids']), '?'));
$stmt = $pdo->prepare("SELECT * FROM test_questions WHERE id IN ($placeholders)");
$stmt->execute($test_info['q_ids']);
$raw = $stmt->fetchAll();

// Index by ID so we can display in original random order
$qMap = [];
foreach ($raw as $rq) $qMap[$rq['id']] = $rq;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test: <?php echo htmlspecialchars($test_info['topic']); ?> — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 820px; margin: 0 auto; padding: 10px 20px 40px; }

        /* Question number indicator */
        .q-counter {
            font-size: 0.78em;
            color: var(--text-faint);
            font-family: 'Inter', sans-serif;
            font-variant-numeric: tabular-nums;
        }

        /* Warning color for timer low time */
        .timer-warning {
            color: var(--red-bright) !important;
            animation: pulse 1s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap">

    <!-- Sticky header -->
    <div class="sticky-header">
        <div>
            <h2 style="font-size:1.1em;"><?php echo htmlspecialchars($test_info['topic']); ?></h2>
            <div class="q-counter"><?php echo count($test_info['q_ids']); ?> questions &nbsp;·&nbsp; <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
        </div>
        <?php if ($test_info['time_limit'] > 0): ?>
            <div class="timer-box" id="timerDisplay">--:--</div>
        <?php else: ?>
            <div style="font-size:0.82em; color:var(--text-faint);">No time limit</div>
        <?php endif; ?>
    </div>

    <!-- Test form -->
    <form action="test_submit.php" method="POST" id="testForm">
        <?php
        $q_num = 1;
        foreach ($test_info['q_ids'] as $qid):
            $q = $qMap[$qid] ?? null;
            if (!$q) continue;
            $opts = [0 => $q['opt_a'], 1 => $q['opt_b'], 2 => $q['opt_c'], 3 => $q['opt_d']];
            $labels = ['A', 'B', 'C', 'D'];
        ?>
            <div class="question-card" style="animation: slideUpFade 0.4s ease-out <?php echo $q_num * 0.05; ?>s both;">
                <h3>Q<?php echo $q_num++; ?>: <?php echo htmlspecialchars($q['question']); ?></h3>
                <div class="options">
                    <?php foreach ($opts as $oi => $opt_text): ?>
                        <label>
                            <input type="radio" name="answers[<?php echo $qid; ?>]" value="<?php echo $oi; ?>" required>
                            <span><?php echo $labels[$oi]; ?>)&nbsp;<?php echo htmlspecialchars($opt_text); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="margin-top: 28px;">
            <button type="submit" class="btn submit-btn w-100" id="submit-test-btn"
                    style="padding: 15px; font-size:1em;"
                    onclick="return confirm('Submit your test? This cannot be undone.');">
                ✅ Finalize &amp; Submit Test
            </button>
        </div>
    </form>

</div>

<?php if ($test_info['time_limit'] > 0): ?>
<script>
    var timeLeft = <?php echo max(0, $time_remaining); ?>;
    var display  = document.getElementById('timerDisplay');
    var form     = document.getElementById('testForm');
    var warned   = false;

    function updateTimer() {
        if (timeLeft <= 0) {
            display.textContent = '00:00';
            display.classList.add('timer-warning');
            alert('⏰ Time is up! Your test is being auto-submitted.');
            form.submit();
            return;
        }

        var m = Math.floor(timeLeft / 60);
        var s = timeLeft % 60;
        display.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;

        if (timeLeft <= 60 && !warned) {
            display.classList.add('timer-warning');
            warned = true;
        }

        timeLeft--;
        setTimeout(updateTimer, 1000);
    }
    updateTimer();
</script>
<?php endif; ?>
</body>
</html>