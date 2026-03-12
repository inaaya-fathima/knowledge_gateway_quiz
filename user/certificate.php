<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$stars = get_user_stars($_SESSION['user_id']);
$name  = $_SESSION['user_name'];
$date  = date('F j, Y');

if ($stars < 100) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achievement Certificate — Knowledge Gateway</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Source+Serif+4:ital,opsz,wght@0,8..60,300;0,8..60,400;1,8..60,300;1,8..60,400&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #0f0f0f; --text: #e8e8e8; --gold: #ffc107;
            --gold-dim: rgba(255,193,7,0.2); --red: #c0392b;
            --border: #2a2a2a; --card: #1a1a1a;
        }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
        }

        .cert-page { max-width: 680px; width: 100%; }

        /* Certificate frame */
        .certificate {
            background: var(--card);
            border: 1px solid rgba(255,193,7,0.4);
            border-radius: 16px;
            padding: 50px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: fadeInPage 0.8s ease-out;
            box-shadow: 0 0 60px rgba(255,193,7,0.08), 0 0 120px rgba(255,193,7,0.04);
        }

        /* Corner ornaments */
        .certificate::before,
        .certificate::after {
            content: '';
            position: absolute;
            width: 80px; height: 80px;
            border: 2px solid rgba(255,193,7,0.25);
        }
        .certificate::before { top: 16px; left: 16px; border-right: none; border-bottom: none; border-radius: 8px 0 0 0; }
        .certificate::after  { bottom: 16px; right: 16px; border-left: none; border-top: none; border-radius: 0 0 8px 0; }

        .cert-top-corners {
            position: absolute;
            top: 16px; right: 16px;
            width: 80px; height: 80px;
            border: 2px solid rgba(255,193,7,0.25);
            border-left: none; border-bottom: none;
            border-radius: 0 8px 0 0;
        }
        .cert-bot-corners {
            position: absolute;
            bottom: 16px; left: 16px;
            width: 80px; height: 80px;
            border: 2px solid rgba(255,193,7,0.25);
            border-right: none; border-top: none;
            border-radius: 0 0 0 8px;
        }

        /* Badge */
        .cert-badge {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #ffc107, #f57c00);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.4em;
            margin: 0 auto 20px;
            box-shadow: 0 0 30px rgba(255,193,7,0.4);
            animation: starPop 0.6s 0.3s cubic-bezier(0.34,1.56,0.64,1) both;
        }

        @keyframes starPop {
            from { transform: scale(0) rotate(-30deg); opacity: 0; }
            to   { transform: scale(1) rotate(0deg); opacity: 1; }
        }
        @keyframes fadeInPage { from { opacity:0; } to { opacity:1; } }

        .cert-org {
            font-size: 0.7em;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: rgba(255,193,7,0.6);
            margin-bottom: 8px;
        }
        .cert-type {
            font-family: 'Source Serif 4', serif;
            font-size: 1.1em;
            font-weight: 300;
            color: rgba(255,193,7,0.5);
            font-style: italic;
            margin-bottom: 24px;
        }

        .cert-presents {
            font-size: 0.75em;
            color: #666;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .cert-name {
            font-family: 'Source Serif 4', serif;
            font-size: 2.8em;
            font-weight: 400;
            color: var(--gold);
            letter-spacing: -0.01em;
            margin-bottom: 16px;
            line-height: 1.1;
            text-shadow: 0 0 30px rgba(255,193,7,0.3);
        }

        .divider {
            width: 120px; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,193,7,0.4), transparent);
            margin: 0 auto 18px;
        }

        .cert-reason {
            font-size: 0.9em;
            color: #888;
            line-height: 1.7;
            max-width: 420px;
            margin: 0 auto 22px;
        }
        .cert-reason strong { color: var(--gold); }

        .stars-display {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,193,7,0.08);
            border: 1px solid rgba(255,193,7,0.25);
            border-radius: 24px;
            padding: 10px 24px;
            font-size: 1.1em;
            font-weight: 600;
            color: var(--gold);
            margin-bottom: 28px;
        }

        .cert-date {
            font-size: 0.78em;
            color: #555;
            margin-top: 8px;
        }

        /* Signature line */
        .sig-row {
            display: flex;
            justify-content: center;
            gap: 60px;
            margin-top: 28px;
        }
        .sig-item { text-align: center; }
        .sig-line { width: 120px; height: 1px; background: var(--border); margin: 0 auto 6px; }
        .sig-label { font-size: 0.72em; color: #555; text-transform: uppercase; letter-spacing: 0.06em; }

        /* Print button */
        .cert-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 28px;
        }
        .btn {
            display: inline-block;
            padding: 12px 22px;
            border-radius: 8px;
            font-size: 0.9em;
            font-weight: 500;
            border: 1px solid var(--border);
            background: #1a1a1a;
            color: var(--text);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            font-family: inherit;
        }
        .btn:hover { background: #242424; border-color: #3a3a3a; }
        .btn-gold {
            background: rgba(255,193,7,0.12);
            border-color: rgba(255,193,7,0.35);
            color: var(--gold);
        }
        .btn-gold:hover { background: rgba(255,193,7,0.2); }

        @media print {
            body { background: white; padding: 0; }
            .certificate { border: 2px solid #333; box-shadow: none; }
            .cert-actions { display: none; }
        }
        @media (max-width: 480px) {
            .certificate { padding: 32px 20px; }
            .cert-name { font-size: 2em; }
            .sig-row { gap: 30px; }
        }
    </style>
</head>
<body>

<div class="cert-page">

    <div class="certificate">
        <div class="cert-top-corners"></div>
        <div class="cert-bot-corners"></div>

        <div class="cert-badge">🏆</div>

        <div class="cert-org">Knowledge Gateway Platform</div>
        <div class="cert-type">Certificate of Achievement</div>

        <div class="cert-presents">This certificate is proudly presented to</div>
        <div class="cert-name"><?php echo htmlspecialchars($name); ?></div>

        <div class="divider"></div>

        <p class="cert-reason">
            For outstanding dedication and commitment to learning,
            having earned <strong><?php echo $stars; ?> Stars</strong> through consistent practice,
            daily study streaks, and excellent test performance on the
            Knowledge Gateway platform.
        </p>

        <div class="stars-display">
            ⭐ <?php echo $stars; ?> Stars Earned
        </div>

        <div class="divider"></div>

        <div class="cert-date">Issued on <?php echo $date; ?></div>

        <div class="sig-row">
            <div class="sig-item">
                <div class="sig-line"></div>
                <div class="sig-label">Platform Seal</div>
            </div>
            <div class="sig-item">
                <div class="sig-line"></div>
                <div class="sig-label">Student Signature</div>
            </div>
        </div>
    </div>

    <div class="cert-actions">
        <a href="dashboard.php" class="btn">← Back to Hub</a>
        <button class="btn btn-gold" onclick="window.print()">🖨️ Print Certificate</button>
    </div>
</div>

<!-- Celebration burst -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.0/dist/confetti.browser.min.js"></script>
<script>
    setTimeout(() => {
        confetti({ particleCount: 200, spread: 160, origin: {y: 0.4},
                   colors: ['#ffc107', '#f57c00', '#ffffff', '#c0392b'] });
    }, 400);
</script>
</body>
</html>
