<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$topic = $_GET['topic'] ?? '';
$customTimer = max(0, (int)($_GET['timer'] ?? 0));

if (empty($topic)) {
    die("Invalid topic. <a href='practice.php'>Go Back</a>");
}

$is_submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$score  = 0;
$total  = 0;

if ($is_submitted) {
    $score = (int)($_POST['score'] ?? 0);
    $total = (int)($_POST['total'] ?? 0);
    $time_taken = (int)($_POST['time_taken'] ?? 0);

    // Record the result
    try {
        $pdo->prepare("INSERT INTO practice_results (user_id, student_name, topic, score, total_questions, time_taken) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$_SESSION['user_id'], $_SESSION['user_name'], $topic, $score, $total, $time_taken]);
    } catch (PDOException $e) { }

    // Award stars
    $pct = $total > 0 ? round(($score / $total) * 100) : 0;
    $starsEarned = 0;
    if ($pct >= 90)      $starsEarned = 5;
    elseif ($pct >= 75)  $starsEarned = 4;
    elseif ($pct >= 60)  $starsEarned = 3;
    elseif ($pct >= 40)  $starsEarned = 2;
    else                 $starsEarned = 1;

    if (!isset($_SESSION['practice_stars_awarded_' . md5($topic . $_SESSION['user_id'])])) {
        add_user_stars($_SESSION['user_id'], $starsEarned);
        $_SESSION['practice_stars_awarded_' . md5($topic . $_SESSION['user_id'])] = true;
    }
    $totalStars = get_user_stars($_SESSION['user_id']);
} else {
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
    $total = count($questions);
    
    // Prep JSON for JS
    $qList = [];
    foreach ($questions as $q) {
        $qList[] = [
            'question' => $q['question'],
            'options' => [$q['opt_a'], $q['opt_b'], $q['opt_c'], $q['opt_d']],
            'correct' => (int)$q['correct_answer']
        ];
    }
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
        .result-panel {
            background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 36px 28px; text-align: center; margin-bottom: 32px; animation: slideUpFade 0.5s ease-out;
        }
        .result-panel .big-pct { font-size: 5em; font-weight: 700; font-family: 'Inter', sans-serif; line-height: 1; color: var(--text); }
        .result-panel .score-line { font-size: 1.1em; color: var(--text-muted); margin: 8px 0 16px; }
        .result-panel .grade-msg { font-family: 'Source Serif 4', serif; font-size: 1.5em; font-weight: 300; margin-bottom: 8px; }
        .stars-earned { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, rgba(255,193,7,0.15), rgba(255,152,0,0.1)); border: 1px solid rgba(255,193,7,0.35); border-radius: 24px; padding: 8px 20px; font-size: 0.95em; font-weight: 600; color: #ffc107; margin: 16px auto 0; }
        .result-actions { display: flex; gap: 12px; justify-content: center; margin-top: 24px; flex-wrap: wrap; }
        .timer-box { font-family: monospace; font-size: 1.2em; font-weight: bold; }
        
        .question-card { margin-bottom: 20px; animation: slideUpFade 0.3s; }
        .option-btn {
            display: block; width: 100%; text-align: left; padding: 15px 20px; margin-bottom: 10px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); font-size: 1em; cursor: pointer; transition: all 0.2s;
        }
        .option-btn:hover:not(:disabled) { background: rgba(52,152,219,0.1); border-color: rgba(52,152,219,0.5); }
        
        /* states */
        .option-btn.correct { background: rgba(46,204,113,0.15); border-color: #2ecc71; color: #2ecc71; pointer-events: none; }
        .option-btn.wrong { background: rgba(231,76,60,0.15); border-color: #e74c3c; color: #e74c3c; pointer-events: none; }
        .option-btn:disabled { cursor: default; }

        .feedback-box {
            margin-top: 15px; padding: 15px; border-radius: 8px; display: none; text-align: left; animation: slideUpFade 0.3s;
        }
        .feedback-box.correct-fb { background: rgba(46,204,113,0.1); border: 1px solid rgba(46,204,113,0.3); color: #2ecc71; display: block; }
        .feedback-box.wrong-fb { background: rgba(231,76,60,0.1); border: 1px solid rgba(231,76,60,0.3); color: #e74c3c; display: block; }
        
        .progress-bar { width: 100%; background: var(--border); height: 6px; border-radius: 3px; margin-bottom: 20px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--red-bright); width: 0%; transition: width 0.3s ease; }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap">
    
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
        <?php
        $grade_color = '#2ecc71';
        $grade_msg   = 'Outstanding! 🎉';
        if ($pct < 90) { $grade_color = '#2ecc71'; $grade_msg = 'Excellent work! 🌟'; }
        if ($pct < 75) { $grade_color = '#27ae60'; $grade_msg = 'Great job! 👏'; }
        if ($pct < 60) { $grade_color = '#f39c12'; $grade_msg = 'Good effort!  Keep it up 👍'; }
        if ($pct < 40) { $grade_color = '#e67e22'; $grade_msg = 'Keep practicing! You\'ll get there 💪'; }
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
        <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.0/dist/confetti.browser.min.js"></script>
        <script>
            const duration = <?php echo $pct >= 75 ? 4000 : 2000; ?>;
            const end = Date.now() + duration;
            const colors = ['#c0392b', '#e74c3c', '#ffc107', '#f39c12', '#ffffff'];
            (function frame() {
                confetti({ particleCount: 4, angle: 60, spread: 55, origin: {x: 0}, colors });
                confetti({ particleCount: 4, angle: 120, spread: 55, origin: {x: 1}, colors });
                if (Date.now() < end) requestAnimationFrame(frame);
            }());
        </script>
    <?php else: ?>
        <div class="progress-bar"><div class="progress-fill" id="pbar"></div></div>
        
        <div id="quiz-container">
            <!-- Dynamic content goes here -->
        </div>

        <form id="submit-form" method="POST" style="display:none;">
            <input type="hidden" name="score" id="final-score" value="0">
            <input type="hidden" name="total" id="final-total" value="<?php echo $total; ?>">
            <input type="hidden" name="time_taken" id="final-time" value="0">
        </form>

        <script>
            const startTime = Date.now();
            const questions = <?php echo json_encode($qList); ?>;
            const labels = ['A', 'B', 'C', 'D'];
            let currentIndex = 0;
            let currentScore = 0;
            let container = document.getElementById('quiz-container');
            let pbar = document.getElementById('pbar');

            function renderQuestion(index) {
                if (index >= questions.length) {
                    endQuiz();
                    return;
                }
                
                pbar.style.width = ((index / questions.length) * 100) + "%";
                let q = questions[index];
                
                let html = `
                    <div class="question-card">
                        <h3 style="margin-bottom:20px; line-height:1.4;">Q${index + 1}/${questions.length}: ${q.question}</h3>
                        <div class="options-container">
                `;
                
                for(let i=0; i<4; i++) {
                    html += `<button class="option-btn" onclick="checkAnswer(${i})"><strong>${labels[i]})</strong> ${q.options[i]}</button>`;
                }
                
                html += `
                        </div>
                        <div id="feedback" class="feedback-box"></div>
                    </div>
                `;
                
                container.innerHTML = html;
            }

            function checkAnswer(selectedIndex) {
                let q = questions[currentIndex];
                let isCorrect = (selectedIndex === q.correct);
                if (isCorrect) currentScore++;
                
                let btns = container.querySelectorAll('.option-btn');
                btns.forEach((btn, i) => {
                    btn.disabled = true;
                    if (i === q.correct) btn.classList.add('correct');
                    else if (i === selectedIndex && !isCorrect) btn.classList.add('wrong');
                });
                
                let fb = document.getElementById('feedback');
                if (isCorrect) {
                    fb.innerHTML = `<strong>Correct!</strong> <br><small>Option ${labels[q.correct]} is the correct answer.</small>`;
                    fb.className = 'feedback-box correct-fb';
                } else {
                    fb.innerHTML = `<strong>Wrong!</strong> <br><small>The correct answer is Option ${labels[q.correct]}: ${q.options[q.correct]}</small>`;
                    fb.className = 'feedback-box wrong-fb';
                }
                
                setTimeout(() => {
                    currentIndex++;
                    renderQuestion(currentIndex);
                }, 2500); // Wait 2.5s before moving to next automatically
            }

            function endQuiz() {
                pbar.style.width = "100%";
                document.getElementById('final-score').value = currentScore;
                document.getElementById('final-time').value = Math.round((Date.now() - startTime) / 1000);
                document.getElementById('submit-form').submit();
            }

            // Init
            if (questions.length > 0) {
                renderQuestion(0);
            }

            <?php if ($customTimer > 0): ?>
            let timeLeft = <?php echo $customTimer * 60; ?>;
            let timerEl = document.getElementById('practiceTimer');
            function tick() {
                if (timeLeft <= 0) {
                    timerEl.textContent = '00:00';
                    alert('Time is up!');
                    endQuiz();
                    return;
                }
                let m = Math.floor(timeLeft / 60);
                let s = timeLeft % 60;
                timerEl.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
                if (timeLeft <= 60) timerEl.style.animation = 'pulse 1s infinite';
                timeLeft--;
                setTimeout(tick, 1000);
            }
            tick();
            <?php endif; ?>
        </script>
    <?php endif; ?>
</div>
</body>
</html>