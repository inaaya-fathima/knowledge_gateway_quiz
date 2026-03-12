<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = '';
$error   = '';
$activeTab = 'form';
$adminUser = $_SESSION['admin_username'] ?? 'unknown'; // data isolation by admin

// ── Handle Form Submission ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (($_POST['add_method'] ?? '') === 'form') {
        $activeTab      = 'form';
        $topic          = trim($_POST['topic']    ?? '');
        $question       = trim($_POST['question'] ?? '');
        $opt_a          = trim($_POST['opt_a']    ?? '');
        $opt_b          = trim($_POST['opt_b']    ?? '');
        $opt_c          = trim($_POST['opt_c']    ?? '');
        $opt_d          = trim($_POST['opt_d']    ?? '');
        $correct_answer = $_POST['correct_answer'] ?? '';

        if ($topic && $question && $opt_a && $opt_b && $opt_c && $opt_d && $correct_answer !== '') {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO questions (admin_username, topic, question, opt_a, opt_b, opt_c, opt_d, correct_answer)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$adminUser, $topic, $question, $opt_a, $opt_b, $opt_c, $opt_d, (int)$correct_answer]);
                $message = "✓ Question added to topic: <strong>" . htmlspecialchars($topic) . "</strong>";
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Please fill in all fields and select the correct answer.";
        }

    } elseif (($_POST['add_method'] ?? '') === 'json') {
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
                        // Answer can be the text value or a numeric index
                        $answer_index = array_search($q['answer'], $q['options']);
                        if ($answer_index === false && is_numeric($q['answer'])
                            && isset($q['options'][(int)$q['answer']]))
                        {
                            $answer_index = (int)$q['answer'];
                        }

                        if ($answer_index !== false) {
                            try {
                                $stmt = $pdo->prepare(
                                    "INSERT INTO questions (admin_username, topic, question, opt_a, opt_b, opt_c, opt_d, correct_answer)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                                );
                                $stmt->execute([
                                    $adminUser,
                                    $topic,
                                    $q['question'],
                                    $q['options'][0],
                                    $q['options'][1],
                                    $q['options'][2],
                                    $q['options'][3],
                                    (int)$answer_index
                                ]);
                                $inserted++;
                            } catch (PDOException $e) {
                                $failed++;
                            }
                        } else {
                            $failed++;
                        }
                    } else {
                        $failed++;
                    }
                }
                if ($inserted > 0) {
                    $message = "✓ Imported <strong>$inserted</strong> question(s) into topic <strong>" . htmlspecialchars($topic) . "</strong>"
                             . ($failed > 0 ? " ($failed skipped — check format)." : ".");
                } else {
                    $error = "No questions could be imported. Make sure each entry has 'question', 'options' (array of 4), and 'answer' fields.";
                }
            } else {
                $error = "Invalid JSON: " . json_last_error_msg() . ". Paste a valid JSON array.";
            }
        } else {
            $error = "Both Topic name and JSON data are required.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Questions — Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 860px; margin: 30px auto; padding: 0 20px; }

        /* Tab switcher */
        .method-tabs {
            display: flex;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .method-tab {
            flex: 1;
            padding: 12px 18px;
            text-align: center;
            cursor: pointer;
            font-size: 0.88em;
            font-weight: 500;
            color: var(--text-muted);
            background: var(--card-bg);
            border: none;
            border-right: 1px solid var(--border);
            transition: all var(--transition);
            font-family: inherit;
        }
        .method-tab:last-child { border-right: none; }
        .method-tab.active { background: var(--card-hover); color: var(--text); }
        .method-tab:hover:not(.active) { background: var(--bg); color: var(--text); }

        .method-panel { display: none; }
        .method-panel.active { display: block; }

        .opts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        /* JSON example code block */
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

        @media (max-width: 500px) {
            .opts-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap">

    <div class="header-bar">
        <h2>Add Questions</h2>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="manage_questions.php" class="btn">View All Questions</a>
            <a href="dashboard.php" class="back-link">← Dashboard</a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="success-msg"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Method Tabs -->
    <div class="method-tabs">
        <button class="method-tab" id="tab-form" onclick="switchTab('form')">📝 Manual MCQ</button>
        <button class="method-tab" id="tab-json" onclick="switchTab('json')">📦 JSON Batch Import</button>
    </div>

    <!-- ── Manual MCQ Form ── -->
    <div class="method-panel glass-card" id="panel-form">
        <h3 style="margin-bottom:4px; font-family:'Inter',sans-serif; font-size:1.05em;">Add a Single Question</h3>
        <p style="color:var(--text-muted); font-size:0.85em; margin-bottom:20px;">Fill in all fields to add one MCQ question.</p>

        <form action="add_question.php" method="POST">
            <input type="hidden" name="add_method" value="form">

            <div class="form-group">
                <label>Topic Name <span style="color:var(--red-bright);">*</span></label>
                <input type="text" name="topic" required placeholder="e.g. Python, PHP, Java"
                       value="<?php echo htmlspecialchars($_POST['topic'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Question Text <span style="color:var(--red-bright);">*</span></label>
                <input type="text" name="question" required placeholder="What does PHP stand for?"
                       value="<?php echo htmlspecialchars($_POST['question'] ?? ''); ?>">
            </div>

            <div class="opts-grid">
                <div class="form-group">
                    <label>Option A <span style="color:var(--red-bright);">*</span></label>
                    <input type="text" name="opt_a" required placeholder="First option"
                           value="<?php echo htmlspecialchars($_POST['opt_a'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Option B <span style="color:var(--red-bright);">*</span></label>
                    <input type="text" name="opt_b" required placeholder="Second option"
                           value="<?php echo htmlspecialchars($_POST['opt_b'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Option C <span style="color:var(--red-bright);">*</span></label>
                    <input type="text" name="opt_c" required placeholder="Third option"
                           value="<?php echo htmlspecialchars($_POST['opt_c'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Option D <span style="color:var(--red-bright);">*</span></label>
                    <input type="text" name="opt_d" required placeholder="Fourth option"
                           value="<?php echo htmlspecialchars($_POST['opt_d'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Correct Answer <span style="color:var(--red-bright);">*</span></label>
                <select name="correct_answer" required>
                    <option value="" disabled <?php echo !isset($_POST['correct_answer']) ? 'selected' : ''; ?>>
                        — Select the correct option —
                    </option>
                    <option value="0" <?php echo (($_POST['correct_answer'] ?? '') === '0') ? 'selected' : ''; ?>>A — Option A</option>
                    <option value="1" <?php echo (($_POST['correct_answer'] ?? '') === '1') ? 'selected' : ''; ?>>B — Option B</option>
                    <option value="2" <?php echo (($_POST['correct_answer'] ?? '') === '2') ? 'selected' : ''; ?>>C — Option C</option>
                    <option value="3" <?php echo (($_POST['correct_answer'] ?? '') === '3') ? 'selected' : ''; ?>>D — Option D</option>
                </select>
            </div>

            <button type="submit" class="btn submit-btn w-100" id="add-question-btn" style="padding:13px; margin-top:8px;">
                ➕ Add Question
            </button>
        </form>
    </div>

    <!-- ── JSON Batch Import ── -->
    <div class="method-panel glass-card" id="panel-json">
        <h3 style="margin-bottom:4px; font-family:'Inter',sans-serif; font-size:1.05em;">JSON Batch Import</h3>
        <p style="color:var(--text-muted); font-size:0.85em; margin-bottom:14px;">Paste a JSON array to import multiple questions at once.</p>

        <p style="font-size:0.8em; color:var(--text-faint); margin-bottom:8px;">Expected format:</p>
        <div class="json-example">[
  {
    "question": "What keyword is used to define a function in Python?",
    "options": ["func", "def", "function", "define"],
    "answer": "def"
  },
  {
    "question": "Which symbol starts a comment in PHP?",
    "options": ["#", "//", "--", "/*"],
    "answer": "//"
  }
]</div>

        <form action="add_question.php" method="POST">
            <input type="hidden" name="add_method" value="json">

            <div class="form-group">
                <label>Topic Name <span style="color:var(--red-bright);">*</span></label>
                <input type="text" name="topic_json" required placeholder="e.g. Python, PHP, Java"
                       value="<?php echo htmlspecialchars($_POST['topic_json'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>JSON Data <span style="color:var(--red-bright);">*</span></label>
                <textarea name="json_data" rows="14" required
                    placeholder='Paste your JSON array here…'><?php echo htmlspecialchars($_POST['json_data'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn submit-btn w-100" id="import-json-btn" style="padding:13px;">
                📦 Import JSON Questions
            </button>
        </form>
    </div>

</div>

<script>
    function switchTab(tab) {
        document.querySelectorAll('.method-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.method-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('panel-' + tab).classList.add('active');
    }

    // Set initial active tab (respects POST method)
    switchTab('<?php echo $activeTab; ?>');
</script>
</body>
</html>