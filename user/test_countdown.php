<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['current_test'])) {
    header("Location: dashboard.php");
    exit;
}

$test = $_SESSION['current_test'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Starting Test… — Knowledge Gateway</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&family=Source+Serif+4:ital,opsz,wght@0,8..60,300&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0f0f0f;
            --red: #c0392b;
            --red-bright: #e74c3c;
            --text: #e8e8e8;
            --text-muted: #666;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* ── Ripple circles ── */
        .ripple-wrap {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .ripple {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(192, 57, 43, 0.3);
            animation: rippleOut 2s ease-out infinite;
        }
        .ripple:nth-child(2) { animation-delay: 0.5s; }
        .ripple:nth-child(3) { animation-delay: 1s; }

        @keyframes rippleOut {
            from { width: 80px; height: 80px; opacity: 1; }
            to   { width: 300px; height: 300px; opacity: 0; }
        }

        /* ── Number display ── */
        .countdown-number {
            font-size: 10em;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            color: var(--red-bright);
            text-shadow: 0 0 60px rgba(231, 76, 60, 0.5);
            position: relative;
            z-index: 10;
            animation: countPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            line-height: 1;
        }

        @keyframes countPop {
            from { transform: scale(0.4); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }
        .count-out {
            animation: countOut 0.4s ease-in forwards !important;
        }
        @keyframes countOut {
            to { transform: scale(1.8); opacity: 0; }
        }

        /* ── GO! display ── */
        .go-text {
            font-family: 'Source Serif 4', serif;
            font-size: 5em;
            font-weight: 300;
            color: var(--text);
            position: relative;
            z-index: 10;
            animation: goPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            display: none;
        }
        @keyframes goPop {
            from { transform: scale(0.5); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        /* ── Info text ── */
        .info-text {
            position: absolute;
            bottom: 80px;
            text-align: center;
            z-index: 10;
        }
        .info-text .topic-label {
            font-size: 0.85em;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .info-text .topic-name {
            font-size: 1.3em;
            color: var(--text);
            margin-top: 4px;
        }
        .info-time {
            font-size: 0.8em;
            color: var(--text-muted);
            margin-top: 6px;
        }

        /* ── Progress dots ── */
        .dots {
            position: absolute;
            bottom: 30px;
            display: flex;
            gap: 10px;
            z-index: 10;
        }
        .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: rgba(192, 57, 43, 0.3);
            transition: background 0.3s;
        }
        .dot.active { background: var(--red-bright); }
    </style>
</head>
<body>

    <!-- Ripple circles -->
    <div class="ripple-wrap">
        <div class="ripple"></div>
        <div class="ripple"></div>
        <div class="ripple"></div>
    </div>

    <!-- Main countdown number -->
    <div class="countdown-number" id="countNum">3</div>
    <div class="go-text" id="goText">Let's Go!</div>

    <!-- Topic info -->
    <div class="info-text">
        <div class="topic-label">Topic</div>
        <div class="topic-name"><?php echo htmlspecialchars($test['topic']); ?></div>
        <div class="info-time">
            <?php echo count($test['q_ids']); ?> Questions
            &nbsp;·&nbsp;
            <?php echo $test['time_limit'] > 0 ? $test['time_limit'] . ' minute time limit' : 'No time limit'; ?>
        </div>
    </div>

    <!-- Dots -->
    <div class="dots">
        <div class="dot active" id="dot3"></div>
        <div class="dot" id="dot2"></div>
        <div class="dot" id="dot1"></div>
        <div class="dot" id="dotgo"></div>
    </div>

<script>
    const el    = document.getElementById('countNum');
    const goEl  = document.getElementById('goText');

    function animateStep(num, dotId, nextFn) {
        el.textContent = num;
        el.style.animation = 'none';
        el.offsetHeight; // reflow
        el.style.animation = 'countPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards';

        document.getElementById('dot' + dotId).classList.add('active');

        setTimeout(() => {
            el.classList.add('count-out');
            setTimeout(() => {
                el.classList.remove('count-out');
                nextFn();
            }, 380);
        }, 900);
    }

    function showGo() {
        el.style.display = 'none';
        goEl.style.display = 'block';
        document.getElementById('dotgo').classList.add('active');
        setTimeout(() => {
            window.location.href = 'test_quiz.php';
        }, 900);
    }

    // Chain: 3 → 2 → 1 → Go
    setTimeout(() => {
        animateStep(3, '3', () => {
            animateStep(2, '2', () => {
                animateStep(1, '1', () => {
                    showGo();
                });
            });
        });
    }, 600);
</script>
</body>
</html>
