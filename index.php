<?php
// Mode Selection — choose Admin or Student portal
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knowledge Gateway — Select Portal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .mode-wrap {
            max-width: 820px;
            width: 100%;
            text-align: center;
            animation: slideUpFade 0.6s ease-out forwards;
        }

        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin-bottom: 16px;
        }
        .brand-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--red) 0%, #8b1c0d 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px rgba(192,57,43,0.3);
        }
        .brand-icon svg { width: 26px; height: 26px; }
        .brand-name {
            font-family: 'Source Serif 4', serif;
            font-size: 1.9em;
            font-weight: 400;
            color: var(--text);
            letter-spacing: -0.02em;
        }
        .brand-name span { color: var(--red-bright); }

        .mode-heading {
            font-size: 2.6em;
            margin: 24px 0 10px;
            font-weight: 300;
            line-height: 1.2;
        }
        .mode-sub {
            font-size: 1em;
            color: var(--text-muted);
            margin-bottom: 50px;
        }

        .role-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .role-card {
            text-align: left;
            position: relative;
        }
        .role-card .role-badge {
            display: inline-block;
            font-size: 0.7em;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--red-bright);
            background: var(--red-glow);
            border: 1px solid var(--red-dim);
            padding: 3px 10px;
            border-radius: 20px;
            margin-bottom: 14px;
        }
        .role-card .role-icon-wrap {
            font-size: 3em;
            margin-bottom: 12px;
            line-height: 1;
        }
        .role-card h2 {
            font-size: 1.7em;
            margin-bottom: 8px;
            font-weight: 400;
        }
        .role-card p {
            font-size: 0.88em;
            color: var(--text-muted);
            margin-bottom: 28px;
            line-height: 1.6;
        }

        .role-card .btn {
            width: 100%;
            padding: 13px;
            font-size: 0.95em;
        }

        .admin-card .btn {
            background: var(--btn-primary-bg);
            color: var(--btn-primary-text);
            border: none;
            font-weight: 600;
        }
        .admin-card .btn:hover {
            background: var(--btn-primary-hover);
            box-shadow: 0 4px 18px rgba(192,57,43,0.4);
        }

        .divider-line {
            width: 1px;
            background: var(--border);
            position: absolute;
            top: 10%;
            bottom: 10%;
            left: 50%;
            transform: translateX(-50%);
        }

        .footer-note {
            margin-top: 36px;
            font-size: 0.8em;
            color: var(--text-faint);
        }
        .footer-note a { color: var(--text-muted); text-decoration: none; }
        .footer-note a:hover { color: var(--text); }

        @media (max-width: 600px) {
            .role-grid { grid-template-columns: 1fr; gap: 16px; }
            .mode-heading { font-size: 2em; }
        }
    </style>
</head>
<body>
    <div class="mode-wrap">

        <!-- Brand -->
        <div class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13 3L3 8.5V13C3 18.2 7.8 23.1 13 25C18.2 23.1 23 18.2 23 13V8.5L13 3Z" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.6)" stroke-width="1"/>
                    <path d="M9 13L11.5 15.5L17 10" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="brand-name">Knowledge <span>Gateway</span></div>
        </div>

        <h1 class="mode-heading">Select your portal</h1>
        <p class="mode-sub">Choose a role to continue. Each portal is tailored to your specific needs.</p>

        <div class="role-grid">
            <!-- Admin Card -->
            <div class="role-card glass-card admin-card">
                <div class="role-badge">Admin</div>
                <div class="role-icon-wrap">🏫</div>
                <h2>Instructor Portal</h2>
                <p>Create and manage quiz sessions, add questions by topic, set test rooms with timers, and review student performance.</p>
                <a href="admin/login.php" class="btn submit-btn" id="admin-portal-btn">Enter as Admin</a>
            </div>

            <!-- Student Card -->
            <div class="role-card glass-card">
                <div class="role-badge">Student</div>
                <div class="role-icon-wrap">📚</div>
                <h2>Student Portal</h2>
                <p>Take your official test using a provided test code, or practice any topic at your own pace and improve your skills.</p>
                <a href="user/index.php" class="btn" id="student-portal-btn" style="display:block; background:var(--btn-sec-bg); border:1px solid var(--border);">Enter as Student</a>
            </div>
        </div>

        <p class="footer-note">
            New here? Start with the <a href="user/signup.php">student registration</a> &nbsp;|&nbsp;
            First time? <a href="intro.php">Watch intro again</a><br><br>
            <a href="official/login.php" style="opacity: 0.5; font-size: 0.85em;">System Official? Login here</a>
        </p>
    </div>
</body>
</html>