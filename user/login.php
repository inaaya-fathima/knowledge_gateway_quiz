<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth_helper.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $user = authenticate_json_user($username, $password, 'user');
        if ($user !== false) {
            $_SESSION['user_id']   = $user['db_id'];
            $_SESSION['user_name'] = $user['name'];
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
    <title>Student Login — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { padding: 0; display: flex; align-items: stretch; }

        .split-layout {
            display: flex;
            width: 100vw;
            min-height: 100vh;
        }

        /* Student: illustration LEFT, form RIGHT */
        .student-illust-side {
            flex: 1;
            background: var(--card-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .student-form-side {
            flex: 0 0 420px;
            padding: 60px 50px;
            background: var(--bg-2);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .role-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(80,80,80,0.2);
            border: 1px solid rgba(100,100,100,0.3);
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 0.75em;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .form-title { font-size: 2.2em; margin-bottom: 6px; }
        .form-subtitle { font-size: 0.92em; color: var(--text-muted); margin-bottom: 32px; }

        .illust-wrap {
            position: relative;
            width: 82%;
            max-width: 500px;
        }

        @media (max-width: 860px) {
            .student-illust-side { display: none; }
            .student-form-side { flex: 1; border-left: none; padding: 40px 28px; min-height: 100vh; }
        }
    </style>
</head>
<body>
    <div class="split-layout">

        <!-- Illustration side (LEFT) — exam hall / students studying -->
        <div class="student-illust-side">
            <div class="illust-wrap">
                <svg viewBox="0 0 500 420" xmlns="http://www.w3.org/2000/svg"
                     style="width:100%; height:auto; animation: softlyFloat 6s ease-in-out infinite;">

                    <!-- ── Floor ── -->
                    <rect x="0" y="350" width="500" height="70" rx="0" fill="#0f0f0f"/>
                    <line x1="0" y1="350" x2="500" y2="350" stroke="#2a2a2a" stroke-width="1"/>

                    <!-- ── Exam desks (3 rows) ── -->
                    <!-- Row 1 desks -->
                    <rect x="60" y="260" width="90" height="8" rx="3" fill="#232323"/>
                    <rect x="70" y="268" width="6" height="50" rx="2" fill="#1a1a1a"/>
                    <rect x="134" y="268" width="6" height="50" rx="2" fill="#1a1a1a"/>

                    <rect x="200" y="260" width="90" height="8" rx="3" fill="#232323"/>
                    <rect x="210" y="268" width="6" height="50" rx="2" fill="#1a1a1a"/>
                    <rect x="274" y="268" width="6" height="50" rx="2" fill="#1a1a1a"/>

                    <rect x="340" y="260" width="90" height="8" rx="3" fill="#232323"/>
                    <rect x="350" y="268" width="6" height="50" rx="2" fill="#1a1a1a"/>
                    <rect x="414" y="268" width="6" height="50" rx="2" fill="#1a1a1a"/>

                    <!-- Row 2 desks -->
                    <rect x="130" y="180" width="90" height="8" rx="3" fill="#1e1e1e"/>
                    <rect x="140" y="188" width="6" height="45" rx="2" fill="#181818"/>
                    <rect x="204" y="188" width="6" height="45" rx="2" fill="#181818"/>

                    <rect x="270" y="180" width="90" height="8" rx="3" fill="#1e1e1e"/>
                    <rect x="280" y="188" width="6" height="45" rx="2" fill="#181818"/>
                    <rect x="344" y="188" width="6" height="45" rx="2" fill="#181818"/>

                    <!-- Answer papers on desks -->
                    <!-- Desk 1 paper -->
                    <rect x="65" y="250" width="50" height="12" rx="2" fill="#1e1e1e" stroke="#2a2a2a" stroke-width="1"/>
                    <rect x="68" y="253" width="30" height="2" rx="1" fill="#333"/>
                    <rect x="68" y="257" width="22" height="2" rx="1" fill="#333"/>

                    <!-- Desk 2 paper -->
                    <rect x="205" y="250" width="50" height="12" rx="2" fill="#1e1e1e" stroke="#2a2a2a" stroke-width="1"/>
                    <rect x="208" y="253" width="30" height="2" rx="1" fill="#c0392b" opacity="0.5"/>
                    <rect x="208" y="257" width="18" height="2" rx="1" fill="#333"/>

                    <!-- Desk 3 paper -->
                    <rect x="345" y="250" width="50" height="12" rx="2" fill="#1e1e1e" stroke="#2a2a2a" stroke-width="1"/>
                    <rect x="348" y="253" width="25" height="2" rx="1" fill="#333"/>
                    <rect x="348" y="257" width="35" height="2" rx="1" fill="#333"/>

                    <!-- ── Student 1 (front left desk) ── -->
                    <circle cx="110" cy="235" r="18" fill="#3a3a3a"/>
                    <path d="M 85 260 Q 88 242 110 238 Q 132 242 135 260 Z" fill="#2a2a2a"/>
                    <!-- Hair -->
                    <path d="M 95 222 Q 110 215 125 222" stroke="#222" stroke-width="6" fill="none" stroke-linecap="round"/>
                    <!-- Writing arm -->
                    <path d="M 125 250 Q 118 255 108 258" stroke="#3a3a3a" stroke-width="8" stroke-linecap="round" fill="none"/>
                    <!-- Pen -->
                    <line x1="108" y1="258" x2="100" y2="263" stroke="#888" stroke-width="2" stroke-linecap="round"/>

                    <!-- ── Student 2 (front right desk) ── -->
                    <circle cx="250" cy="235" r="18" fill="#444"/>
                    <path d="M 225 260 Q 228 242 250 238 Q 272 242 275 260 Z" fill="#333"/>
                    <!-- Thinking hand on head -->
                    <path d="M 232 248 Q 237 240 244 235" stroke="#444" stroke-width="7" stroke-linecap="round" fill="none"/>
                    <!-- Arm on desk -->
                    <path d="M 268 250 Q 260 256 252 258" stroke="#444" stroke-width="8" stroke-linecap="round" fill="none"/>
                    <!-- Pen -->
                    <line x1="252" y1="258" x2="244" y2="263" stroke="#888" stroke-width="2" stroke-linecap="round"/>

                    <!-- ── Student 3 (front right desk) ── -->
                    <circle cx="390" cy="235" r="18" fill="#383838"/>
                    <path d="M 365 260 Q 368 242 390 238 Q 412 242 415 260 Z" fill="#2e2e2e"/>
                    <!-- Writing arm -->
                    <path d="M 406 250 Q 398 256 390 258" stroke="#383838" stroke-width="8" stroke-linecap="round" fill="none"/>
                    <line x1="390" y1="258" x2="380" y2="263" stroke="#888" stroke-width="2" stroke-linecap="round"/>

                    <!-- ── Students in row 2 (smaller, in perspective) ── -->
                    <circle cx="175" cy="163" r="14" fill="#2a2a2a"/>
                    <path d="M 157 180 Q 159 167 175 164 Q 191 167 193 180 Z" fill="#222"/>

                    <circle cx="315" cy="163" r="14" fill="#2e2e2e"/>
                    <path d="M 297 180 Q 299 167 315 164 Q 331 167 333 180 Z" fill="#262626"/>

                    <!-- ── Clock on top ── -->
                    <circle cx="250" cy="55" r="32" fill="#1a1a1a" stroke="#2a2a2a" stroke-width="2"/>
                    <circle cx="250" cy="55" r="28" fill="#0f0f0f" stroke="#222" stroke-width="1"/>
                    <!-- Clock hands -->
                    <line x1="250" y1="55" x2="250" y2="34" stroke="#c0392b" stroke-width="2.5" stroke-linecap="round"/>
                    <line x1="250" y1="55" x2="265" y2="62" stroke="#555" stroke-width="1.8" stroke-linecap="round"/>
                    <circle cx="250" cy="55" r="3" fill="#c0392b"/>
                    <!-- Clock tick marks -->
                    <?php
                    for ($i = 0; $i < 12; $i++) {
                        $angle = $i * 30 * M_PI / 180;
                        $x1 = 250 + 22 * sin($angle);
                        $y1 = 55  - 22 * cos($angle);
                        $x2 = 250 + 26 * sin($angle);
                        $y2 = 55  - 26 * cos($angle);
                        echo "<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"#333\" stroke-width=\"1.5\"/>";
                    }
                    ?>

                    <!-- ── Books (stacked, side of room) ── -->
                    <rect x="430" y="290" width="50" height="10" rx="2" fill="#c0392b" opacity="0.6"/>
                    <rect x="432" y="280" width="46" height="12" rx="2" fill="#555"/>
                    <rect x="435" y="269" width="42" height="13" rx="2" fill="#444"/>
                    <rect x="430" y="258" width="48" height="13" rx="2" fill="#c0392b" opacity="0.4"/>
                    <rect x="437" y="248" width="40" height="12" rx="2" fill="#333"/>

                    <!-- Spine lines on books -->
                    <line x1="450" y1="290" x2="450" y2="300" stroke="rgba(255,255,255,0.1)" stroke-width="1"/>
                    <line x1="455" y1="280" x2="455" y2="292" stroke="rgba(255,255,255,0.1)" stroke-width="1"/>

                    <!-- ── Accent dots ── -->
                    <circle cx="40" cy="160" r="3" fill="#c0392b" opacity="0.3" style="animation: pulseGlow 3s infinite;"/>
                    <circle cx="468" cy="80"  r="4" fill="#555" opacity="0.4" style="animation: pulseGlow 4s 1s infinite;"/>
                    <circle cx="30" cy="300"  r="2" fill="#c0392b" opacity="0.25"/>

                </svg>
                <p style="text-align:center; color:var(--text-faint); font-size:0.75em; margin-top:14px; letter-spacing:0.05em;">
                    EXAMINATION HALL
                </p>
            </div>
        </div>

        <!-- Form side (RIGHT) -->
        <div class="student-form-side">
            <a href="../index.php" class="back-link" style="margin-bottom: 40px; display:inline-block;">← Back to Home</a>

            <div class="role-chip"><span>📚</span> Student Portal</div>

            <h1 class="form-title">Student <span style="color:var(--red-bright)">Login</span></h1>
            <p class="form-subtitle">Welcome back! Sign in to access your tests and practice sets.</p>

            <?php if (!empty($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Enter your username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                <div style="margin-top: 28px;">
                    <button type="submit" class="btn submit-btn" id="student-login-btn" style="width:100%; padding:14px;">Log In</button>
                </div>
            </form>

            <div class="form-footer">
                <p>Don't have an account? <a href="signup.php">Register here</a></p>
            </div>
        </div>

    </div>
</body>
</html>