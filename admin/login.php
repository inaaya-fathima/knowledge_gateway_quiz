<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth_helper.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $admin = authenticate_json_user($username, $password, 'admin');
        if ($admin !== false) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { padding: 0; display: flex; align-items: stretch; }

        .split-layout {
            display: flex;
            width: 100vw;
            min-height: 100vh;
        }

        /* Admin: form LEFT, illustration RIGHT */
        .admin-form-side {
            flex: 0 0 420px;
            padding: 60px 50px;
            background: var(--bg-2);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .admin-illust-side {
            flex: 1;
            background: var(--card-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .back-bar {
            position: absolute;
            top: 24px;
            left: 24px;
        }

        /* Role chip */
        .role-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--red-glow);
            border: 1px solid var(--red-dim);
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 0.75em;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--red-bright);
            margin-bottom: 20px;
        }
        .role-chip span { font-size: 1.1em; }

        .form-title { font-size: 2.2em; margin-bottom: 6px; }
        .form-subtitle { font-size: 0.92em; color: var(--text-muted); margin-bottom: 32px; }

        /* Illustration SVG styles */
        .illust-wrap {
            position: relative;
            width: 85%;
            max-width: 500px;
        }
        .illust-grid {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(var(--border) 1px, transparent 1px),
                              linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.3;
        }

        @media (max-width: 860px) {
            .admin-illust-side { display: none; }
            .admin-form-side { flex: 1; border-right: none; padding: 40px 28px; min-height: 100vh; }
        }
    </style>
</head>
<body>
    <div class="split-layout">

        <!-- Form side (LEFT) -->
        <div class="admin-form-side">
            <a href="../index.php" class="back-link" style="margin-bottom: 40px; display:inline-block;">← Back to Home</a>

            <div class="role-chip"><span>🔐</span> Admin Access</div>

            <h1 class="form-title">Admin <span style="color:var(--red-bright)">Login</span></h1>
            <p class="form-subtitle">Sign in to manage quizzes, students, and test sessions.</p>

            <?php if (!empty($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Enter admin username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter password">
                </div>
                <div style="margin-top: 28px;">
                    <button type="submit" class="btn submit-btn" id="admin-login-btn" style="width:100%; padding:14px;">Secure Login</button>
                </div>
            </form>

            <div class="form-footer">
                <p>New Administrator? <a href="signup.php">Create an account</a></p>
            </div>
        </div>

        <!-- Illustration side (RIGHT) — admin/teacher/instructor themed -->
        <div class="admin-illust-side">
            <div class="illust-wrap">
                <div class="illust-grid"></div>
                <!-- Admin Panel / Teacher / Instructor SVG illustration -->
                <svg viewBox="0 0 500 420" xmlns="http://www.w3.org/2000/svg"
                     style="position:relative; z-index:1; width:100%; height:auto; animation: softlyFloat 7s ease-in-out infinite;">

                    <!-- ── Desk surface ── -->
                    <rect x="60" y="300" width="380" height="10" rx="4" fill="#2a2a2a"/>
                    <rect x="80" y="310" width="8" height="80" rx="3" fill="#222"/>
                    <rect x="412" y="310" width="8" height="80" rx="3" fill="#222"/>

                    <!-- ── Monitor ── -->
                    <rect x="130" y="160" width="200" height="140" rx="8" fill="#1e1e1e" stroke="#333" stroke-width="2"/>
                    <rect x="135" y="165" width="190" height="130" rx="6" fill="#0f0f0f"/>

                    <!-- Monitor content: admin panel UI lines -->
                    <rect x="140" y="170" width="180" height="16" rx="3" fill="#1a1a1a"/>
                    <rect x="145" y="174" width="60" height="8" rx="2" fill="#c0392b" opacity="0.8"/>
                    <rect x="210" y="174" width="40" height="8" rx="2" fill="#2a2a2a"/>
                    <rect x="255" y="174" width="40" height="8" rx="2" fill="#2a2a2a"/>

                    <!-- Stats bars in monitor -->
                    <rect x="142" y="194" width="80" height="6" rx="3" fill="#333"/>
                    <rect x="142" y="194" width="55" height="6" rx="3" fill="#c0392b" opacity="0.7"/>
                    <rect x="142" y="206" width="80" height="6" rx="3" fill="#333"/>
                    <rect x="142" y="206" width="40" height="6" rx="3" fill="#7b7b7b" opacity="0.7"/>
                    <rect x="142" y="218" width="80" height="6" rx="3" fill="#333"/>
                    <rect x="142" y="218" width="65" height="6" rx="3" fill="#c0392b" opacity="0.5"/>

                    <!-- Mini chart on right of monitor -->
                    <rect x="240" y="194" width="80" height="8" rx="2" fill="#1e1e1e"/>
                    <rect x="245" y="196" width="10" height="28" rx="1" fill="#c0392b" opacity="0.6"/>
                    <rect x="259" y="202" width="10" height="22" rx="1" fill="#555"/>
                    <rect x="273" y="199" width="10" height="25" rx="1" fill="#c0392b" opacity="0.8"/>
                    <rect x="287" y="208" width="10" height="16" rx="1" fill="#444"/>
                    <rect x="301" y="203" width="10" height="21" rx="1" fill="#c0392b" opacity="0.5"/>

                    <!-- More rows in monitor -->
                    <rect x="142" y="234" width="175" height="6" rx="2" fill="#222"/>
                    <rect x="142" y="244" width="130" height="6" rx="2" fill="#222"/>
                    <rect x="142" y="254" width="155" height="6" rx="2" fill="#222"/>
                    <rect x="142" y="264" width="100" height="6" rx="2" fill="#c0392b" opacity="0.3"/>

                    <!-- Monitor stand -->
                    <rect x="215" y="300" width="30" height="8" rx="2" fill="#2a2a2a"/>
                    <rect x="210" y="307" width="40" height="6" rx="2" fill="#2a2a2a"/>

                    <!-- ── Keyboard ── -->
                    <rect x="160" y="315" width="140" height="18" rx="4" fill="#1e1e1e" stroke="#2a2a2a" stroke-width="1"/>
                    <?php
                    $cols = 9; $rows = 2;
                    for ($r = 0; $r < $rows; $r++) {
                        for ($c = 0; $c < $cols; $c++) {
                            echo '<rect x="' . (165 + $c * 14) . '" y="' . (318 + $r * 7) . '" width="11" height="5" rx="1" fill="#2a2a2a"/>';
                        }
                    }
                    ?>

                    <!-- ── Teacher/Person figure ── -->
                    <!-- Head -->
                    <circle cx="390" cy="195" r="28" fill="#3a3a3a"/>
                    <!-- Glasses -->
                    <circle cx="382" cy="193" r="9" fill="none" stroke="#555" stroke-width="2"/>
                    <circle cx="398" cy="193" r="9" fill="none" stroke="#555" stroke-width="2"/>
                    <line x1="373" y1="193" x2="370" y2="193" stroke="#555" stroke-width="2"/>
                    <line x1="391" y1="193" x2="389" y2="193" stroke="#555" stroke-width="2"/>
                    <line x1="407" y1="193" x2="410" y2="193" stroke="#555" stroke-width="2"/>
                    <!-- Mouth (slight smile) -->
                    <path d="M 383 203 Q 390 208 397 203" stroke="#555" stroke-width="1.5" fill="none" stroke-linecap="round"/>

                    <!-- Body / Blazer -->
                    <path d="M 355 300 Q 355 240 380 230 Q 390 227 400 230 Q 425 240 425 300 Z" fill="#2a2a2a"/>
                    <path d="M 380 230 L 375 300" stroke="#1a1a1a" stroke-width="2"/>
                    <path d="M 400 230 L 405 300" stroke="#1a1a1a" stroke-width="2"/>
                    <!-- Tie -->
                    <path d="M 385 232 L 382 260 L 390 265 L 398 260 L 395 232 Z" fill="#c0392b" opacity="0.8"/>

                    <!-- Arm pointing at board -->
                    <path d="M 360 260 Q 330 255 310 240" stroke="#3a3a3a" stroke-width="14" stroke-linecap="round" fill="none"/>
                    <!-- Pointer stick -->
                    <line x1="310" y1="240" x2="290" y2="228" stroke="#555" stroke-width="3" stroke-linecap="round"/>

                    <!-- ── Whiteboard / Chalkboard ── -->
                    <rect x="60" y="80" width="190" height="130" rx="6" fill="#141414" stroke="#2a2a2a" stroke-width="2"/>
                    <rect x="65" y="85" width="180" height="120" rx="4" fill="#111"/>

                    <!-- Board content: "QUIZ" heading -->
                    <rect x="75" y="92" width="60" height="8" rx="2" fill="#c0392b" opacity="0.8"/>
                    <rect x="75" y="106" width="160" height="4" rx="2" fill="#2a2a2a"/>
                    <rect x="75" y="115" width="130" height="4" rx="2" fill="#2a2a2a"/>
                    <rect x="75" y="124" width="145" height="4" rx="2" fill="#2a2a2a"/>
                    <rect x="75" y="133" width="110" height="4" rx="2" fill="#2a2a2a"/>

                    <!-- MCQ options on board -->
                    <circle cx="79" cy="147" r="4" fill="none" stroke="#444" stroke-width="1.5"/>
                    <rect x="87" y="144" width="80" height="4" rx="2" fill="#2a2a2a"/>
                    <circle cx="79" cy="158" r="4" fill="#c0392b" opacity="0.6"/>
                    <rect x="87" y="155" width="70" height="4" rx="2" fill="#333"/>
                    <circle cx="79" cy="169" r="4" fill="none" stroke="#444" stroke-width="1.5"/>
                    <rect x="87" y="166" width="90" height="4" rx="2" fill="#2a2a2a"/>
                    <circle cx="79" cy="180" r="4" fill="none" stroke="#444" stroke-width="1.5"/>
                    <rect x="87" y="177" width="65" height="4" rx="2" fill="#2a2a2a"/>

                    <!-- Board frame / tray -->
                    <rect x="60" y="208" width="190" height="6" rx="2" fill="#222"/>
                    <rect x="80" y="210" width="20" height="4" rx="1" fill="#555"/>

                    <!-- ── Floating accent dots ── -->
                    <circle cx="450" cy="120" r="4" fill="#c0392b" opacity="0.4" style="animation: pulseGlow 3s infinite;"/>
                    <circle cx="80" cy="360" r="3" fill="#c0392b" opacity="0.3" style="animation: pulseGlow 4s 1s infinite;"/>
                    <circle cx="100" cy="130" r="2" fill="#555" opacity="0.5"/>

                </svg>

                <!-- Caption -->
                <p style="text-align:center; color:var(--text-faint); font-size:0.75em; margin-top:16px; position:relative; z-index:1; letter-spacing:0.05em;">
                    INSTRUCTOR CONTROL PANEL
                </p>
            </div>
        </div>

    </div>
</body>
</html>