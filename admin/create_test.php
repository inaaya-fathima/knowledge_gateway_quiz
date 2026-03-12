<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message   = '';
$error     = '';
$activeTab = 'form';
$testCode  = '';

// ── Helper: generate unique test code ──────────────────────
function generateTestCode(PDO $pdo, int $length = 6): string {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $check = $pdo->prepare("SELECT COUNT(*) FROM tests WHERE test_code = ?");
        $check->execute([$code]);
    } while ($check->fetchColumn() > 0);
    return $code;
}

// ── Handle POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['add_method'] ?? '';

    // ── Tab 1: Manual Single MCQ ──
    if ($method === 'form') {
        $activeTab      = 'form';
        $topic          = trim($_POST['topic']          ?? '');
        $question       = trim($_POST['question']       ?? '');
        $opt_a          = trim($_POST['opt_a']          ?? '');
        $opt_b          = trim($_POST['opt_b']          ?? '');
        $opt_c          = trim($_POST['opt_c']          ?? '');
        $opt_d          = trim($_POST['opt_d']          ?? '');
        $correct_answer = $_POST['correct_answer']      ?? '';

        if ($topic && $question && $opt_a && $opt_b && $opt_c && $opt_d && $correct_answer !== '') {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO test_questions (topic, question, opt_a, opt_b, opt_c, opt_d, correct_answer)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$topic, $question, $opt_a, $opt_b, $opt_c, $opt_d, (int)$correct_answer]);
                $message = "✓ Official test question added to topic: <strong>" . htmlspecialchars($topic) . "</strong>";
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Please fill in all fields and select the correct answer.";
        }

    // ── Tab 2: JSON Batch Import ──
    } elseif ($method === 'json') {
        $activeTab  = 'json';
        $json_input = trim($_POST['json_data']  ?? '');
        $topic      = trim($_POST['topic_json'] ?? '');

        if (!empty($json_input) && !empty($topic)) {
            $data = json_decode($json_input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $inserted = 0;
                $failed   = 0;
                foreach ($data as $q) {
                    if (isset($q['question'], $q['options'], $q['answer'])
                        && is_array($q['options']) && count($q['options']) === 4)
                    {
                        $answer_index = array_search($q['answer'], $q['options']);
                        if ($answer_index === false && is_numeric($q['answer'])
                            && isset($q['options'][(int)$q['answer']]))
                        {
                            $answer_index = (int)$q['answer'];
                        }
                        if ($answer_index !== false) {
                            try {
                                $stmt = $pdo->prepare(
                                    "INSERT INTO test_questions (topic, question, opt_a, opt_b, opt_c, opt_d, correct_answer)
                                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                                );
                                $stmt->execute([
                                    $topic,
                                    $q['question'],
                                    $q['options'][0],
                                    $q['options'][1],
                                    $q['options'][2],
                                    $q['options'][3],
                                    (int)$answer_index
                                ]);
                                $inserted++;
                            } catch (PDOException $e) { $failed++; }
                        } else { $failed++; }
                    } else { $failed++; }
                }
                if ($inserted > 0) {
                    $message = "✓ Imported <strong>$inserted</strong> question(s) into topic <strong>"
                             . htmlspecialchars($topic) . "</strong>"
                             . ($failed > 0 ? " ($failed skipped — check format)." : ".");
                } else {
                    $error = "No questions could be imported. Each entry needs 'question', 'options' (array of 4), and 'answer'.";
                }
            } else {
                $error = "Invalid JSON: " . json_last_error_msg() . ". Paste a valid JSON array.";
            }
        } else {
            $error = "Both Topic name and JSON data are required.";
        }

    // ── Tab 3: Test Room Setup ──
    } elseif ($method === 'testroom') {
        $activeTab     = 'testroom';
        $topic         = trim($_POST['topic']         ?? '');
        $num_questions = (int)($_POST['num_questions'] ?? 0);
        $time_limit    = (int)($_POST['time_limit']    ?? 0);

        if (empty($topic)) {
            $error = "Please select a topic.";
        } elseif ($num_questions < 1) {
            $error = "Number of questions must be at least 1.";
        } else {
            // Check available count from the OFFICIAL test question pool
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM test_questions WHERE topic = ?");
            $countStmt->execute([$topic]);
            $available = (int)$countStmt->fetchColumn();

            if ($num_questions > $available) {
                $error = "Only $available question(s) exist in the official test pool for this topic. Add more official questions first.";
            } else {
                try {
                    $testCode = generateTestCode($pdo);
                    $stmt = $pdo->prepare(
                        "INSERT INTO tests (test_code, topic, num_questions, time_limit) VALUES (?, ?, ?, ?)"
                    );
                    $stmt->execute([$testCode, $topic, $num_questions, $time_limit]);
                    $message = "Test room created! Share the code with your students.";
                } catch (PDOException $e) {
                    $error = "Failed to create test room: " . $e->getMessage();
                }
            }
        }
    }
}

// ── Fetch topics for test room dropdown (from test_questions only) ──
try {
    $topics = $pdo->query(
        "SELECT topic, COUNT(*) as cnt FROM test_questions GROUP BY topic ORDER BY topic ASC"
    )->fetchAll();
} catch (PDOException $e) {
    $topics = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Test / Add Practice Questions — Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 860px; margin: 30px auto; padding: 0 20px; }

        /* ── 3-tab switcher ── */
        .method-tabs {
            display: flex;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .method-tab {
            flex: 1;
            padding: 12px 10px;
            text-align: center;
            cursor: pointer;
            font-size: 0.86em;
            font-weight: 500;
            color: var(--text-muted);
            background: var(--card-bg);
            border: none;
            border-right: 1px solid var(--border);
            transition: all var(--transition);
            font-family: inherit;
            line-height: 1.3;
        }
        .method-tab:last-child { border-right: none; }
        .method-tab.active  { background: var(--card-hover); color: var(--text); }
        .method-tab:hover:not(.active) { background: var(--bg); color: var(--text); }

        /* Active tab highlight stripe */
        .method-tab.active { box-shadow: inset 0 -2px 0 var(--red); }

        .method-panel { display: none; }
        .method-panel.active { display: block; }

        /* 2-col options grid */
        .opts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        @media (max-width: 520px) { .opts-grid { grid-template-columns: 1fr; } }

        /* JSON sample */
        .json-example {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 14px 16px;
            font-family: 'Courier New', monospace;
            font-size: 0.78em;
            color: #7ec8e3;
            margin-bottom: 16px;
            white-space: pre;
            overflow-x: auto;
            line-height: 1.7;
        }

        /* Topic pills in testroom tab */
        .topic-select-info {
            font-size: 0.78em;
            color: var(--text-faint);
            margin-top: 5px;
        }
        #topicAvailCount { color: var(--ok); font-weight: 600; }

        .help-text { font-size: 0.78em; color: var(--text-faint); margin-top: 5px; }

        /* Test code reveal */
        .code-reveal {
            text-align: center;
            padding: 28px 20px;
            background: rgba(46,204,113,0.06);
            border: 1px solid rgba(46,204,113,0.2);
            border-radius: var(--radius);
            margin-bottom: 24px;
            animation: slideUpFade 0.4s ease-out;
        }
        .code-reveal p { font-size: 0.85em; color: var(--text-muted); margin-bottom: 10px; }
        .code-big {
            font-size: 3.2em;
            font-weight: 700;
            letter-spacing: 12px;
            font-family: 'Courier New', monospace;
            color: var(--ok);
            text-shadow: 0 0 20px rgba(46,204,113,0.3);
        }
        .copy-btn {
            margin-top: 14px;
            padding: 8px 22px;
            background: rgba(46,204,113,0.1);
            border: 1px solid rgba(46,204,113,0.25);
            border-radius: 20px;
            color: var(--ok);
            font-size: 0.82em;
            cursor: pointer;
            font-family: inherit;
            transition: all var(--transition);
            display: inline-block;
        }
        .copy-btn:hover { background: rgba(46,204,113,0.2); }
        .info-pills { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px; justify-content: center; }
        .info-pill {
            font-size: 0.78em;
            padding: 4px 12px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            color: var(--text-muted);
        }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap">

    <div class="header-bar">
        <div>
            <h2 style="margin-bottom:2px;">Create Test &amp; Add Questions</h2>
            <p style="margin:0; font-size:0.82em; color:var(--text-faint);">Add practice questions or set up an official test room</p>
        </div>
        <a href="dashboard.php" class="back-link">← Dashboard</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="success-msg"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Test code reveal (only after test room creation) -->
    <?php if (!empty($testCode) && $activeTab === 'testroom'): ?>
        <div class="code-reveal">
            <p>✅ Test room is live! Share this code with your students:</p>
            <div class="code-big" id="testCodeDisplay"><?php echo $testCode; ?></div>
            <div class="info-pills">
                <span class="info-pill">Topic: <?php echo htmlspecialchars($_POST['topic']); ?></span>
                <span class="info-pill"><?php echo (int)$_POST['num_questions']; ?> Questions</span>
                <span class="info-pill">
                    <?php echo (int)$_POST['time_limit'] > 0
                        ? (int)$_POST['time_limit'] . ' min limit'
                        : 'No time limit'; ?>
                </span>
            </div>
            <button class="copy-btn" onclick="copyCode()">📋 Copy Code</button>
        </div>
    <?php endif; ?>

    <!-- ── 3-Tab Switcher ── -->
    <div class="method-tabs">
        <button class="method-tab" id="tab-form"
                onclick="switchTab('form')">📝 Manual MCQ<br><small style="opacity:.6;">Add one question</small></button>
        <button class="method-tab" id="tab-json"
                onclick="switchTab('json')">📦 JSON Batch<br><small style="opacity:.6;">Import many at once</small></button>
        <button class="method-tab" id="tab-testroom"
                onclick="switchTab('testroom')">🎯 Test Room<br><small style="opacity:.6;">Generate test code</small></button>
    </div>


    <!-- ══════════════════════════════════════
         TAB 1 — Manual MCQ
    ══════════════════════════════════════ -->
    <div class="method-panel glass-card" id="panel-form">
        <h3 style="margin-bottom:4px; font-family:'Inter',sans-serif; font-size:1.05em;">Add an Official Test Question</h3>
        <p style="color:var(--text-muted); font-size:0.85em; margin-bottom:20px;">
            These questions are <strong style="color:var(--red-bright)">exclusive to official tests</strong> — they will <em>not</em> appear in the student practice zone.
        </p>

        <form action="create_test.php" method="POST">
            <input type="hidden" name="add_method" value="form">

            <div class="form-group">
                <label>Topic Name <span style="color:var(--red-bright)">*</span></label>
                <input type="text" name="topic" required
                       placeholder="e.g. Python, PHP, Java, Class Test"
                       value="<?php echo htmlspecialchars($_POST['topic'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Question Text <span style="color:var(--red-bright)">*</span></label>
                <input type="text" name="question" required
                       placeholder="e.g. What does HTML stand for?"
                       value="<?php echo htmlspecialchars($_POST['question'] ?? ''); ?>">
            </div>

            <div class="opts-grid">
                <div class="form-group">
                    <label>Option A <span style="color:var(--red-bright)">*</span></label>
                    <input type="text" name="opt_a" required placeholder="First option"
                           value="<?php echo htmlspecialchars($_POST['opt_a'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Option B <span style="color:var(--red-bright)">*</span></label>
                    <input type="text" name="opt_b" required placeholder="Second option"
                           value="<?php echo htmlspecialchars($_POST['opt_b'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Option C <span style="color:var(--red-bright)">*</span></label>
                    <input type="text" name="opt_c" required placeholder="Third option"
                           value="<?php echo htmlspecialchars($_POST['opt_c'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Option D <span style="color:var(--red-bright)">*</span></label>
                    <input type="text" name="opt_d" required placeholder="Fourth option"
                           value="<?php echo htmlspecialchars($_POST['opt_d'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Correct Answer <span style="color:var(--red-bright)">*</span></label>
                <select name="correct_answer" required>
                    <option value="" disabled
                        <?php echo !isset($_POST['correct_answer']) ? 'selected' : ''; ?>>
                        — Select the correct option —
                    </option>
                    <option value="0" <?php echo (($_POST['correct_answer'] ?? '') === '0') ? 'selected' : ''; ?>>A — Option A</option>
                    <option value="1" <?php echo (($_POST['correct_answer'] ?? '') === '1') ? 'selected' : ''; ?>>B — Option B</option>
                    <option value="2" <?php echo (($_POST['correct_answer'] ?? '') === '2') ? 'selected' : ''; ?>>C — Option C</option>
                    <option value="3" <?php echo (($_POST['correct_answer'] ?? '') === '3') ? 'selected' : ''; ?>>D — Option D</option>
                </select>
            </div>

            <button type="submit" class="btn submit-btn w-100" style="padding:13px; margin-top:8px;">
                ➕ Add Practice Question
            </button>
        </form>
    </div>


    <!-- ══════════════════════════════════════
         TAB 2 — JSON Batch Import
    ══════════════════════════════════════ -->
    <div class="method-panel glass-card" id="panel-json">
        <h3 style="margin-bottom:4px; font-family:'Inter',sans-serif; font-size:1.05em;">JSON Batch — Official Test Questions</h3>
        <p style="color:var(--text-muted); font-size:0.85em; margin-bottom:14px;">
            Bulk-import questions into the <strong style="color:var(--red-bright)">official test pool</strong>. These are hidden from practice mode.
        </p>

        <p style="font-size:0.8em; color:var(--text-faint); margin-bottom:8px;">Expected format:</p>
        <div class="json-example">[
  {
    "question": "What keyword defines a function in Python?",
    "options": ["func", "def", "function", "define"],
    "answer": "def"
  },
  {
    "question": "Which tag creates a hyperlink in HTML?",
    "options": ["&lt;a&gt;", "&lt;link&gt;", "&lt;href&gt;", "&lt;url&gt;"],
    "answer": "&lt;a&gt;"
  }
]</div>

        <form action="create_test.php" method="POST">
            <input type="hidden" name="add_method" value="json">

            <div class="form-group">
                <label>Topic Name <span style="color:var(--red-bright)">*</span></label>
                <input type="text" name="topic_json" required
                       placeholder="e.g. Python, PHP, Java"
                       value="<?php echo htmlspecialchars($_POST['topic_json'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>JSON Data <span style="color:var(--red-bright)">*</span></label>
                <textarea name="json_data" rows="14" required
                    placeholder="Paste your JSON array here…"><?php echo htmlspecialchars($_POST['json_data'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn submit-btn w-100" style="padding:13px;">
                📦 Import Official Test Questions
            </button>
        </form>
    </div>


    <!-- ══════════════════════════════════════
         TAB 3 — Test Room Setup
    ══════════════════════════════════════ -->
    <div class="method-panel glass-card" id="panel-testroom">
        <h3 style="margin-bottom:4px; font-family:'Inter',sans-serif; font-size:1.05em;">Generate Official Test Room</h3>
        <p style="color:var(--text-muted); font-size:0.85em; margin-bottom:20px;">
            Create a test session from the <strong style="color:var(--red-bright)">official test question pool</strong>. Students use the generated code to enter.
        </p>

        <?php if (empty($topics)): ?>
            <div style="text-align:center; padding:24px; color:var(--text-muted);">
                <p>No official test topics yet.<br>
                   Use the <strong>Manual MCQ</strong> or <strong>JSON Batch</strong> tabs to add official test questions first.</p>
                <button onclick="switchTab('form')" class="btn submit-btn" style="margin-top:12px; padding:10px 24px;">
                    ➕ Add Questions Now
                </button>
            </div>
        <?php else: ?>
            <form action="create_test.php" method="POST">
                <input type="hidden" name="add_method" value="testroom">

                <div class="form-group">
                    <label>Topic <span style="color:var(--red-bright)">*</span></label>
                    <select name="topic" id="topicSelect" required onchange="updateAvail(this)">
                        <option value="">— Select a topic —</option>
                        <?php foreach ($topics as $t): ?>
                            <option value="<?php echo htmlspecialchars($t['topic']); ?>"
                                    data-count="<?php echo $t['cnt']; ?>"
                                    <?php echo (($_POST['topic'] ?? '') === $t['topic']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['topic']); ?>
                                (<?php echo $t['cnt']; ?> questions)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="topic-select-info">
                        Available questions in selected topic:
                        <span id="topicAvailCount">—</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Number of Questions for Test <span style="color:var(--red-bright)">*</span></label>
                    <input type="number" name="num_questions" id="numQInput"
                           value="<?php echo htmlspecialchars($_POST['num_questions'] ?? '10'); ?>"
                           min="1" max="100" required>
                    <div class="help-text">Cannot exceed the number of questions available in the selected topic.</div>
                </div>

                <div class="form-group">
                    <label>Time Limit (minutes) <span style="color:var(--red-bright)">*</span></label>
                    <input type="number" name="time_limit"
                           value="<?php echo htmlspecialchars($_POST['time_limit'] ?? '15'); ?>"
                           min="0" max="300" required>
                    <div class="help-text">Set to <strong>0</strong> for no time limit.</div>
                </div>

                <div style="background:var(--bg); border:1px solid var(--border); border-radius:var(--radius-sm); padding:14px 16px; margin-bottom:18px; font-size:0.85em; color:var(--text-muted);">
                    💡 A unique 6-character test code will be auto-generated after you click the button below.
                    Share this code with students so they can enter the test.
                </div>

                <button type="submit" class="btn submit-btn w-100" style="padding:13px;">
                    🎯 Generate Test Code
                </button>
            </form>
        <?php endif; ?>
    </div>

</div><!-- /page-wrap -->

<script>
    // ── Tab switcher ───────────────────────────────────────────
    function switchTab(tab) {
        document.querySelectorAll('.method-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.method-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');
    }

    // Boot on the correct tab (PHP echoes active tab on errors/success)
    switchTab('<?php echo $activeTab; ?>');

    // ── Live available-count display ───────────────────────────
    function updateAvail(sel) {
        const opt = sel.options[sel.selectedIndex];
        const cnt = opt ? opt.dataset.count : null;
        const span = document.getElementById('topicAvailCount');
        if (cnt !== undefined && cnt !== null && sel.value) {
            span.textContent = cnt + ' question' + (parseInt(cnt) !== 1 ? 's' : '');
            // Set max on the number input
            const numInput = document.getElementById('numQInput');
            if (numInput) numInput.max = cnt;
        } else {
            span.textContent = '—';
        }
    }

    // Trigger on page load if a topic was pre-selected (e.g. after POST error)
    const topicSel = document.getElementById('topicSelect');
    if (topicSel && topicSel.value) updateAvail(topicSel);

    // ── Copy test code ─────────────────────────────────────────
    function copyCode() {
        const code = document.getElementById('testCodeDisplay')?.textContent?.trim();
        if (code && navigator.clipboard) {
            navigator.clipboard.writeText(code).then(() => {
                const btn = document.querySelector('.copy-btn');
                btn.textContent = '✓ Copied!';
                setTimeout(() => btn.textContent = '📋 Copy Code', 2000);
            });
        }
    }
</script>
</body>
</html>