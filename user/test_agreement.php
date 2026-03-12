<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

// Handle test code verification + agreement submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_code = strtoupper(trim($_POST['test_code'] ?? ''));
    $agreed    = isset($_POST['agreed']) ? true : false;

    if (empty($test_code)) {
        $error = "Please enter a test code.";
    } elseif (!$agreed) {
        $error = "You must agree to the exam rules before proceeding.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tests WHERE test_code = ?");
        $stmt->execute([$test_code]);
        $test = $stmt->fetch();

        if (!$test) {
            $error = "Invalid Test Code. Please check and try again.";
        } else {
            // Fetch random question IDs from the OFFICIAL test question pool (separate from practice)
            $qStmt = $pdo->prepare("SELECT id FROM test_questions WHERE topic = ? ORDER BY RAND() LIMIT ?");
            $qStmt->bindValue(1, $test['topic'], PDO::PARAM_STR);
            $qStmt->bindValue(2, (int)$test['num_questions'], PDO::PARAM_INT);
            $qStmt->execute();
            $qIds = $qStmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($qIds) == 0) {
                $error = "This test is temporarily unavailable — no questions found for the topic.";
            } else {
                $_SESSION['current_test'] = [
                    'test_code'  => $test_code,
                    'topic'      => $test['topic'],
                    'time_limit' => $test['time_limit'],
                    'q_ids'      => $qIds,
                    'start_time' => time()
                ];
                // Go to countdown
                header("Location: test_countdown.php");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Agreement — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .agree-wrap {
            max-width: 560px;
            width: 100%;
            animation: slideUpFade 0.5s ease-out forwards;
        }
        .step-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--red-glow);
            border: 1px solid var(--red-dim);
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 0.75em;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--red-bright);
            margin-bottom: 16px;
        }
        .shield-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--red) 0%, #8b1c0d 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 0 24px rgba(192,57,43,0.3);
        }
        .rules-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .rules-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.9em;
            color: var(--text);
            line-height: 1.5;
        }
        .rules-list li:last-child { border-bottom: none; }
        .rules-list .rule-no {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            height: 24px;
            background: var(--card-hover);
            border: 1px solid var(--border);
            border-radius: 50%;
            font-size: 0.75em;
            font-weight: 700;
            color: var(--text-muted);
            flex-shrink: 0;
        }

        .code-input {
            font-size: 1.3em;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-weight: 700;
            padding: 14px 20px !important;
            color: var(--text) !important;
        }

        .submit-row {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        .submit-row .btn { flex: 1; padding: 13px; }
    </style>
</head>
<body>
    <div class="agree-wrap">
        <a href="dashboard.php" class="back-link" style="display:inline-block; margin-bottom:20px;">← Back to Hub</a>

        <div class="shield-icon">
            <svg viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" width="32" height="32">
                <path d="M14 3L4 8V13.5C4 19.3 8.8 24.6 14 26.3C19.2 24.6 24 19.3 24 13.5V8L14 3Z"
                      fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.5)" stroke-width="1.2"/>
                <path d="M9.5 14L12.5 17L18.5 11" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <div class="step-badge">📝 Step 1 of 2 — Agreement</div>
        <h1 class="form-title">Before You Begin</h1>
        <p class="form-subtitle">Read and accept the exam rules, then enter your test code to start.</p>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="test_agreement.php" method="POST">

            <!-- Rules -->
            <div class="glass-card" style="margin-bottom: 20px;">
                <h3 style="margin-bottom: 14px; font-size:1em; font-family:'Inter',sans-serif; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.06em;">
                    Examination Code of Conduct
                </h3>
                <ul class="rules-list">
                    <li><span class="rule-no">1</span>You must not communicate with other students during the test.</li>
                    <li><span class="rule-no">2</span>No external resources, notes, or internet browsing is allowed.</li>
                    <li><span class="rule-no">3</span>Do not refresh or navigate away — your session will end.</li>
                    <li><span class="rule-no">4</span>The timer will auto-submit when time expires.</li>
                    <li><span class="rule-no">5</span>All activity may be logged and reviewed by the instructor.</li>
                    <li><span class="rule-no">6</span>Any attempt to cheat will result in disqualification.</li>
                </ul>
            </div>

            <!-- Checkbox agreement -->
            <label class="checkbox-label">
                <input type="checkbox" name="agreed" id="agreed" required>
                <span>I have read and agree to the examination rules above. I confirm I will not cheat or use any unauthorized materials during this test.</span>
            </label>

            <!-- Test Code -->
            <div class="form-group" style="margin-top: 20px;">
                <label for="test_code">Test Code (provided by your instructor)</label>
                <input type="text" id="test_code" name="test_code"
                       class="code-input"
                       placeholder="e.g. AB12CD"
                       maxlength="10" required
                       value="<?php echo htmlspecialchars(strtoupper($_POST['test_code'] ?? '')); ?>">
            </div>

            <div class="submit-row">
                <a href="dashboard.php" class="btn">Cancel</a>
                <button type="submit" class="btn submit-btn" id="start-test-btn">Agree &amp; Proceed →</button>
            </div>
        </form>
    </div>
</body>
</html>
