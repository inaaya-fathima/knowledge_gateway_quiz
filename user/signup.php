<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth_helper.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name             = trim($_POST['name']             ?? '');
    $username         = trim($_POST['username']         ?? '');
    $password         = $_POST['password']              ?? '';
    $confirm_password = $_POST['confirm_password']      ?? '';

    if ($name && $username && $password && $confirm_password) {
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Strong password validation
            $pwErrors = validate_password_strength($password);
            if (!empty($pwErrors)) {
                $error = 'Password does not meet requirements: ' . implode(', ', $pwErrors) . '.';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (name) VALUES (?)");
                    $stmt->execute([$name]);
                    $db_user_id = $pdo->lastInsertId();

                    if (register_json_user($username, $password, 'user', ['db_id' => $db_user_id, 'name' => $name])) {
                        $success = "Account created! You can now log in.";
                    } else {
                        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$db_user_id]);
                        $error = "Username already taken. Please choose another.";
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { padding: 0; display: flex; align-items: stretch; }

        .split-layout { display: flex; width: 100vw; min-height: 100vh; }

        /* Student signup: illustration LEFT, form RIGHT */
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
            flex: 0 0 460px;
            padding: 50px 50px;
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

        .row-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        .illust-wrap { position: relative; width: 82%; max-width: 500px; }

        .success-card {
            background: var(--card-bg);
            border: 1px solid rgba(46,204,113,0.3);
            border-radius: var(--radius);
            padding: 28px 24px;
            text-align: center;
        }
        .success-icon { font-size: 3em; margin-bottom: 12px; }
        .success-card h3 { color: var(--ok); margin-bottom: 8px; font-family: 'Inter', sans-serif; }
        .success-card p { font-size: 0.9em; margin-bottom: 20px; }

        @media (max-width: 860px) {
            .student-illust-side { display: none; }
            .student-form-side { flex: 1; border-left: none; padding: 36px 24px; min-height: 100vh; }
        }
        @media (max-width: 480px) {
            .row-2col { grid-template-columns: 1fr; }
        }

        /* ── Password strength meter ── */
        .pw-strength-bar { height: 4px; border-radius: 2px; background: var(--border); margin-top: 8px; overflow: hidden; }
        .pw-strength-bar-fill { height: 100%; width: 0%; border-radius: 2px; transition: width .3s ease, background .3s ease; }
        .pw-strength-label { font-size: .74em; margin-top: 5px; color: var(--text-faint); transition: color .3s; }
        .pw-reqs { margin-top: 8px; padding: 8px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); display: none; }
        .pw-reqs.visible { display: block; }
        .pw-req-item { font-size: .76em; color: var(--text-faint); padding: 2px 0; display: flex; align-items: center; gap: 7px; transition: color .2s; }
        .pw-req-item.ok { color: #2ecc71; }
        .pw-req-item .dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--border-hover); flex-shrink: 0; transition: background .2s; }
        .pw-req-item.ok .dot { background: #2ecc71; }
    </style>
</head>
<body>
<div class="split-layout">

    <!-- Illustration side (LEFT) — same exam-hall SVG as login -->
    <div class="student-illust-side">
        <div class="illust-wrap">
            <svg viewBox="0 0 400 380" xmlns="http://www.w3.org/2000/svg"
                 style="width:100%; height:auto; animation: softlyFloat 6s ease-in-out infinite; opacity:0.85;">

                <!-- Stack of books -->
                <rect x="60" y="280" width="110" height="14" rx="3" fill="#c0392b" opacity="0.7"/>
                <rect x="65" y="266" width="100" height="16" rx="3" fill="#444"/>
                <rect x="70" y="251" width="95"  height="17" rx="3" fill="#2a2a2a"/>
                <rect x="62" y="238" width="108" height="15" rx="3" fill="#c0392b" opacity="0.4"/>
                <rect x="72" y="225" width="90"  height="15" rx="3" fill="#333"/>

                <!-- Book spines -->
                <line x1="115" y1="280" x2="115" y2="294" stroke="rgba(255,255,255,0.08)" stroke-width="1"/>
                <line x1="110" y1="266" x2="110" y2="282" stroke="rgba(255,255,255,0.08)" stroke-width="1"/>

                <!-- Pencil on books -->
                <rect x="170" y="270" width="6" height="60" rx="2" fill="#f39c12" transform="rotate(-20,170,270)"/>
                <polygon points="167,325 173,325 170,335" fill="#e8e8e8" transform="rotate(-20,170,270)"/>
                <rect x="167" y="268" width="6" height="5" rx="1" fill="#cc7700" transform="rotate(-20,170,270)"/>

                <!-- Notebook / paper -->
                <rect x="220" y="220" width="130" height="100" rx="6" fill="#1e1e1e" stroke="#2a2a2a" stroke-width="1.5"/>
                <rect x="227" y="228" width="116" height="84" rx="4" fill="#0f0f0f"/>
                <!-- Lines on notebook -->
                <rect x="232" y="236" width="96" height="3" rx="1" fill="#222"/>
                <rect x="232" y="244" width="80" height="3" rx="1" fill="#222"/>
                <rect x="232" y="252" width="90" height="3" rx="1" fill="#c0392b" opacity="0.4"/>
                <rect x="232" y="260" width="70" height="3" rx="1" fill="#222"/>
                <rect x="232" y="268" width="85" height="3" rx="1" fill="#222"/>
                <rect x="232" y="276" width="60" height="3" rx="1" fill="#222"/>
                <rect x="232" y="284" width="78" height="3" rx="1" fill="#222"/>
                <!-- Spiral rings -->
                <?php for ($i = 0; $i < 8; $i++): ?>
                    <circle cx="223" cy="<?php echo 232 + $i * 11; ?>" r="4" fill="none" stroke="#333" stroke-width="1.5"/>
                <?php endfor; ?>

                <!-- Student figure studying -->
                <circle cx="290" cy="140" r="35" fill="#2e2e2e"/>
                <!-- Head detail -->
                <circle cx="280" cy="133" r="10" fill="#3a3a3a"/>
                <circle cx="300" cy="133" r="10" fill="#3a3a3a"/>
                <!-- Glasses -->
                <circle cx="280" cy="133" r="8" fill="none" stroke="#444" stroke-width="2"/>
                <circle cx="300" cy="133" r="8" fill="none" stroke="#444" stroke-width="2"/>
                <line x1="272" y1="133" x2="270" y2="133" stroke="#444" stroke-width="2"/>
                <line x1="288" y1="133" x2="292" y2="133" stroke="#444" stroke-width="2"/>
                <line x1="308" y1="133" x2="311" y2="133" stroke="#444" stroke-width="2"/>

                <!-- Body -->
                <path d="M 260 220 Q 262 170 290 162 Q 318 170 320 220 Z" fill="#252525"/>
                <!-- Arm reaching to desk -->
                <path d="M 260 185 Q 245 200 240 220" stroke="#2e2e2e" stroke-width="12" stroke-linecap="round" fill="none"/>

                <!-- GPA / Grade bubble -->
                <rect x="310" y="90" width="60" height="36" rx="8" fill="#1a1a1a" stroke="#c0392b" stroke-width="1.5"/>
                <text x="340" y="108" text-anchor="middle" fill="#c0392b" font-size="11" font-family="monospace" font-weight="bold">A+</text>
                <text x="340" y="120" text-anchor="middle" fill="#555" font-size="7" font-family="monospace">Score</text>
                <!-- Bubble tail -->
                <polygon points="318,126 328,126 322,134" fill="#1a1a1a"/>

                <!-- Stars floating -->
                <text x="80" y="80"  fill="#ffc107" font-size="18" opacity="0.6" style="animation: softlyFloat 3s 0.5s infinite ease-in-out;">⭐</text>
                <text x="340" y="50" fill="#ffc107" font-size="14" opacity="0.4" style="animation: softlyFloat 4s 1s   infinite ease-in-out;">⭐</text>
                <text x="150" y="110" fill="#ffc107" font-size="10" opacity="0.3" style="animation: softlyFloat 5s 0s   infinite ease-in-out;">⭐</text>

                <!-- Desk -->
                <rect x="50" y="294" width="310" height="8" rx="3" fill="#1e1e1e"/>
                <rect x="60" y="302" width="7" height="50" rx="2" fill="#181818"/>
                <rect x="350" y="302" width="7" height="50" rx="2" fill="#181818"/>
            </svg>
            <p style="text-align:center; color:var(--text-faint); font-size:0.75em; margin-top:14px; letter-spacing:0.05em;">
                START YOUR LEARNING JOURNEY
            </p>
        </div>
    </div>

    <!-- Form side (RIGHT) -->
    <div class="student-form-side">
        <a href="../index.php" class="back-link" style="margin-bottom: 36px; display:inline-block;">← Back to Home</a>

        <div class="role-chip"><span>✏️</span> New Student</div>
        <h1 class="form-title">Create <span style="color:var(--red-bright)">Account</span></h1>
        <p class="form-subtitle">Join the platform and start learning. It's free!</p>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-card">
                <div class="success-icon">🎉</div>
                <h3>Welcome aboard!</h3>
                <p><?php echo htmlspecialchars($success); ?></p>
                <a href="login.php" class="btn submit-btn" style="display:block; padding:13px;">Log In Now →</a>
            </div>
        <?php else: ?>
            <form action="signup.php" method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required placeholder="John Smith"
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Choose a unique username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="row-2col">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required
                               placeholder="Min. 8 chars + A-Z + 0-9 + symbol"
                               oninput="checkStrength(this.value)">
                        <!-- Strength bar -->
                        <div class="pw-strength-bar"><div class="pw-strength-bar-fill" id="pwBar"></div></div>
                        <div class="pw-strength-label" id="pwLabel">Enter a password</div>
                        <!-- Checklist -->
                        <div class="pw-reqs" id="pwReqs">
                            <div class="pw-req-item" id="req-len"><span class="dot"></span>8+ characters</div>
                            <div class="pw-req-item" id="req-up"><span class="dot"></span>Uppercase letter</div>
                            <div class="pw-req-item" id="req-lo"><span class="dot"></span>Lowercase letter</div>
                            <div class="pw-req-item" id="req-num"><span class="dot"></span>Number (0–9)</div>
                            <div class="pw-req-item" id="req-sp"><span class="dot"></span>Special character</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repeat password">
                    </div>
                </div>
                <div style="margin-top: 22px;">
                    <button type="submit" class="btn submit-btn" id="create-account-btn" style="width:100%; padding:14px;">Create Account</button>
                </div>
            </form>
            <div class="form-footer">
                <p>Already have an account? <a href="login.php">Log in here</a></p>
            </div>
        <?php endif; ?>
    </div>

</div>
<script>
    const levels = [
        { label: 'Too weak',   color: '#e74c3c', pct: 15  },
        { label: 'Weak',       color: '#e67e22', pct: 35  },
        { label: 'Fair',       color: '#f1c40f', pct: 60  },
        { label: 'Strong',     color: '#2ecc71', pct: 85  },
        { label: 'Very strong',color: '#27ae60', pct: 100 },
    ];
    function checkStrength(val) {
        const reqs = document.getElementById('pwReqs');
        if (!val.length) { reqs.classList.remove('visible'); resetBar(); return; }
        reqs.classList.add('visible');
        let score = 0;
        const set = (id, ok) => { document.getElementById(id).classList.toggle('ok', ok); if (ok) score++; };
        set('req-len', val.length >= 8);
        set('req-up',  /[A-Z]/.test(val));
        set('req-lo',  /[a-z]/.test(val));
        set('req-num', /[0-9]/.test(val));
        set('req-sp',  /[^A-Za-z0-9]/.test(val));
        const lvl = levels[Math.max(0, score - 1)];
        document.getElementById('pwBar').style.cssText = `width:${lvl.pct}%; background:${lvl.color}`;
        const lbl = document.getElementById('pwLabel');
        lbl.textContent = lvl.label; lbl.style.color = lvl.color;
    }
    function resetBar() {
        document.getElementById('pwBar').style.cssText = 'width:0%';
        const lbl = document.getElementById('pwLabel');
        lbl.textContent = 'Enter a password'; lbl.style.color = '';
    }
</script>
</body>
</html>