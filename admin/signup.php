<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth_helper.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username         = trim($_POST['username']         ?? '');
    $password         =      $_POST['password']         ?? '';
    $confirm_password =      $_POST['confirm_password'] ?? '';

    if (!empty($username) && !empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Strong password validation
            $pwErrors = validate_password_strength($password);
            if (!empty($pwErrors)) {
                $error = 'Password does not meet requirements: ' . implode(', ', $pwErrors) . '.';
            } elseif (register_json_user($username, $password, 'admin')) {
                $success = "Admin account created! You can now login.";
            } else {
                $error = "Username already exists. Please choose a different one.";
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
    <title>Admin Registration — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { padding: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }

        .signup-wrap {
            width: 100%;
            max-width: 440px;
            padding: 20px;
            animation: slideUpFade 0.5s ease-out;
        }

        /* Password strength bar */
        .pw-strength-bar {
            height: 4px;
            border-radius: 2px;
            background: var(--border);
            margin-top: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .pw-strength-bar-fill {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: width 0.3s ease, background 0.3s ease;
        }
        .pw-strength-label {
            font-size: 0.74em;
            margin-top: 5px;
            color: var(--text-faint);
            transition: color 0.3s ease;
        }

        /* Requirements checklist */
        .pw-reqs {
            margin-top: 10px;
            padding: 10px 14px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            display: none;
        }
        .pw-reqs.visible { display: block; }
        .pw-req-item {
            font-size: 0.78em;
            color: var(--text-faint);
            padding: 3px 0;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s;
        }
        .pw-req-item.ok  { color: #2ecc71; }
        .pw-req-item .dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: var(--border-hover); flex-shrink: 0; transition: background 0.2s; }
        .pw-req-item.ok .dot { background: #2ecc71; }
    </style>
</head>
<body>
<div class="signup-wrap">

    <a href="login.php" class="back-link" style="display:inline-block; margin-bottom:24px;">← Back to Login</a>

    <div class="glass-card">
        <div style="text-align:center; margin-bottom:24px;">
            <div style="font-size:2.2em; margin-bottom:8px;">🛡️</div>
            <h1 class="form-title" style="font-size:1.9em; margin-bottom:4px;">Admin <span>Registration</span></h1>
            <p class="form-subtitle" style="margin-bottom:0;">Create a secure administrator account</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-msg" style="text-align:center; padding:20px;">
                <div style="font-size:2em; margin-bottom:8px;">✅</div>
                <strong><?php echo htmlspecialchars($success); ?></strong>
                <br><br>
                <a href="login.php" class="btn submit-btn" style="display:inline-block; padding:11px 30px;">Go to Login →</a>
            </div>
        <?php else: ?>
            <form action="signup.php" method="POST">

                <div class="form-group">
                    <label for="username">Admin Username <span style="color:var(--red-bright)">*</span></label>
                    <input type="text" id="username" name="username" required
                           placeholder="Choose a unique admin username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password <span style="color:var(--red-bright)">*</span></label>
                    <input type="password" id="password" name="password" required
                           placeholder="Create a strong password"
                           oninput="checkStrength(this.value)">

                    <!-- Strength bar -->
                    <div class="pw-strength-bar"><div class="pw-strength-bar-fill" id="pwBar"></div></div>
                    <div class="pw-strength-label" id="pwLabel">Enter a password</div>

                    <!-- Requirements checklist -->
                    <div class="pw-reqs" id="pwReqs">
                        <div class="pw-req-item" id="req-len"><span class="dot"></span>At least 8 characters</div>
                        <div class="pw-req-item" id="req-up"><span class="dot"></span>One uppercase letter (A–Z)</div>
                        <div class="pw-req-item" id="req-lo"><span class="dot"></span>One lowercase letter (a–z)</div>
                        <div class="pw-req-item" id="req-num"><span class="dot"></span>One number (0–9)</div>
                        <div class="pw-req-item" id="req-sp"><span class="dot"></span>One special character (!@#$%…)</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span style="color:var(--red-bright)">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           placeholder="Re-enter your password">
                </div>

                <button type="submit" class="btn submit-btn w-100" style="padding:13px; margin-top:6px;">
                    Create Admin Account
                </button>
            </form>

            <div class="form-footer">
                Already have an account? <a href="login.php">Sign in</a>
            </div>
        <?php endif; ?>
    </div>

    <div style="text-align:center; margin-top:16px;">
        <a href="../index.php" class="back-link">← Back to Home</a>
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
        if (val.length > 0) reqs.classList.add('visible');
        else { reqs.classList.remove('visible'); resetBar(); return; }

        let score = 0;
        const set = (id, ok) => {
            const el = document.getElementById(id);
            el.classList.toggle('ok', ok);
            if (ok) score++;
        };
        set('req-len', val.length >= 8);
        set('req-up',  /[A-Z]/.test(val));
        set('req-lo',  /[a-z]/.test(val));
        set('req-num', /[0-9]/.test(val));
        set('req-sp',  /[^A-Za-z0-9]/.test(val));

        const lvl = levels[Math.max(0, score - 1)];
        const bar = document.getElementById('pwBar');
        const lbl = document.getElementById('pwLabel');
        bar.style.width      = lvl.pct + '%';
        bar.style.background = lvl.color;
        lbl.textContent      = lvl.label;
        lbl.style.color      = lvl.color;
    }

    function resetBar() {
        document.getElementById('pwBar').style.width = '0%';
        document.getElementById('pwLabel').textContent = 'Enter a password';
        document.getElementById('pwLabel').style.color = '';
    }
</script>
</body>
</html>