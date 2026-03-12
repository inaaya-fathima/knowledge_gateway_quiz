<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

try {
    $stmt   = $pdo->query("SELECT topic, COUNT(*) as count FROM questions GROUP BY topic ORDER BY topic ASC");
    $topics = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching practice topics: " . $e->getMessage());
}

// Icons mapped to common topic names (fallback to 📝)
function topicIcon(string $topic): string {
    $t = strtolower($topic);
    if (str_contains($t, 'python'))     return '🐍';
    if (str_contains($t, 'php'))        return '🐘';
    if (str_contains($t, 'java'))       return '☕';
    if (str_contains($t, 'javascript')) return '✨';
    if (str_contains($t, 'database') || str_contains($t, 'sql')) return '🗄️';
    if (str_contains($t, 'network'))    return '🌐';
    if (str_contains($t, 'algorithm'))  return '⚙️';
    if (str_contains($t, 'math'))       return '📐';
    if (str_contains($t, 'english'))    return '📖';
    if (str_contains($t, 'class'))      return '🏫';
    if (str_contains($t, 'practice'))   return '✏️';
    if (str_contains($t, 'general'))    return '🎯';
    return '📝';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practice Zone — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 900px; margin: 30px auto; padding: 0 20px; }

        .page-intro {
            margin-bottom: 28px;
        }
        .page-intro p { font-size: 0.93em; margin-top: 6px; }

        /* Timer modal */
        .timer-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            z-index: 200;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .timer-modal-overlay.open { display: flex; }

        .timer-modal {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 36px 32px;
            max-width: 400px;
            width: 90%;
            animation: slideUpFade 0.3s ease-out forwards;
            text-align: center;
        }
        .timer-modal h3 { margin-bottom: 6px; font-size: 1.3em; }
        .timer-modal p  { font-size: 0.88em; margin-bottom: 24px; }

        .timer-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .timer-opt {
            padding: 12px 8px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.88em;
            text-align: center;
            transition: all var(--transition);
            font-family: inherit;
        }
        .timer-opt:hover, .timer-opt.selected {
            border-color: var(--red);
            color: var(--red-bright);
            background: var(--red-glow);
        }
        .timer-opt.no-limit { grid-column: 1 / -1; }

        .modal-actions { display: flex; gap: 12px; margin-top: 6px; }
        .modal-actions .btn { flex: 1; padding: 12px; }

        /* Custom timer input */
        .custom-timer-wrap {
            margin-top: 10px;
            display: none;
        }
        .custom-timer-wrap.show { display: block; }
        .custom-timer-wrap input {
            width: 100%;
            padding: 10px 14px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text);
            font-size: 0.93em;
            text-align: center;
            outline: none;
            font-family: inherit;
        }
        .custom-timer-wrap input:focus { border-color: var(--red); }
        .custom-timer-wrap label { font-size: 0.8em; color: var(--text-muted); margin-bottom: 6px; display: block; text-align: left; }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        .empty-icon { font-size: 3.5em; margin-bottom: 14px; opacity: 0.4; }

        @media (max-width: 500px) {
            .timer-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap">

    <div class="header-bar">
        <div class="page-intro">
            <h2 style="margin-bottom:4px;">Practice Zone</h2>
            <p>Select a topic to practice. Results here don't affect your official score.</p>
        </div>
        <a href="dashboard.php" class="back-link">← Back to Hub</a>
    </div>

    <?php if (empty($topics)): ?>
        <div class="empty-state">
            <div class="empty-icon">📚</div>
            <h3 style="color:var(--text-muted); font-weight:400;">No topics available yet</h3>
            <p style="font-size:0.9em;">Ask your instructor to add questions to get started.</p>
        </div>
    <?php else: ?>
        <p style="font-size:0.85em; color:var(--text-muted); margin-bottom:20px;">
            <?php echo count($topics); ?> topic<?php echo count($topics) != 1 ? 's' : ''; ?> available
            &nbsp;·&nbsp;
            <span style="color:var(--red-bright);">Click any card to set a timer and start practicing</span>
        </p>

        <div class="practice-grid">
            <?php foreach ($topics as $t): ?>
                <div class="practice-card"
                     onclick="openTimerModal('<?php echo htmlspecialchars(addslashes($t['topic'])); ?>')"
                     style="cursor:pointer;"
                     id="topic-card-<?php echo urlencode($t['topic']); ?>">
                    <div class="topic-icon"><?php echo topicIcon($t['topic']); ?></div>
                    <h3><?php echo htmlspecialchars($t['topic']); ?></h3>
                    <p><?php echo $t['count']; ?> question<?php echo $t['count'] != 1 ? 's' : ''; ?> available</p>
                    <div class="start-label">▶ Practice →</div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- ── Timer Selection Modal ── -->
<div class="timer-modal-overlay" id="timerModal">
    <div class="timer-modal">
        <div style="font-size:2em; margin-bottom:10px;" id="modalIcon">📝</div>
        <h3 id="modalTopicName">Topic</h3>
        <p id="modalTopicCount">Loading…</p>

        <p style="font-size:0.83em; color:var(--text-muted); margin-bottom:16px; margin-top:-10px;">
            Set an optional timer challenge for yourself:
        </p>

        <div class="timer-grid">
            <button class="timer-opt" onclick="selectTimer(0)" id="opt-0">⏳ No limit</button>
            <button class="timer-opt" onclick="selectTimer(5)"  id="opt-5">5 minutes</button>
            <button class="timer-opt" onclick="selectTimer(10)" id="opt-10">10 minutes</button>
            <button class="timer-opt" onclick="selectTimer(15)" id="opt-15">15 minutes</button>
            <button class="timer-opt" onclick="selectTimer(20)" id="opt-20">20 minutes</button>
            <button class="timer-opt" onclick="selectTimer(-1)" id="opt-custom">✏️ Custom…</button>
        </div>

        <div class="custom-timer-wrap" id="customWrap">
            <label>Enter time in minutes (1–120)</label>
            <input type="number" id="customMinutes" min="1" max="120" placeholder="e.g. 25">
        </div>

        <div class="modal-actions">
            <button class="btn" onclick="closeModal()">Cancel</button>
            <button class="btn submit-btn" id="startPracticeBtn" onclick="startPractice()">Start Practice</button>
        </div>
    </div>
</div>

<script>
    let selectedTopic = '';
    let selectedTimer = 0; // 0 = no limit, -1 = custom

    const topicIcons = {
        'python': '🐍', 'php': '🐘', 'java': '☕', 'javascript': '✨',
        'sql': '🗄️', 'database': '🗄️', 'network': '🌐',
        'algorithm': '⚙️', 'general': '🎯'
    };

    function topicIcon(topic) {
        const t = topic.toLowerCase();
        for (const [key, icon] of Object.entries(topicIcons)) {
            if (t.includes(key)) return icon;
        }
        return '📝';
    }

    function openTimerModal(topic) {
        selectedTopic = topic;
        selectedTimer = 0;

        document.getElementById('modalIcon').textContent = topicIcon(topic);
        document.getElementById('modalTopicName').textContent = topic;

        // Reset selections
        document.querySelectorAll('.timer-opt').forEach(el => el.classList.remove('selected'));
        document.getElementById('opt-0').classList.add('selected');
        document.getElementById('customWrap').classList.remove('show');
        document.getElementById('customMinutes').value = '';

        document.getElementById('timerModal').classList.add('open');
    }

    function closeModal() {
        document.getElementById('timerModal').classList.remove('open');
    }

    function selectTimer(val) {
        selectedTimer = val;
        document.querySelectorAll('.timer-opt').forEach(el => el.classList.remove('selected'));
        document.getElementById('opt-' + val).classList.add('selected');

        const customWrap = document.getElementById('customWrap');
        if (val === -1) {
            customWrap.classList.add('show');
        } else {
            customWrap.classList.remove('show');
        }
    }

    function startPractice() {
        if (!selectedTopic) return;

        let timer = selectedTimer;
        if (timer === -1) {
            const custom = parseInt(document.getElementById('customMinutes').value);
            if (!custom || custom < 1 || custom > 120) {
                alert('Please enter a valid time between 1 and 120 minutes.');
                return;
            }
            timer = custom;
        }

        const url = `practice_quiz.php?topic=${encodeURIComponent(selectedTopic)}&timer=${timer}`;
        window.location.href = url;
    }

    // Close modal on overlay click
    document.getElementById('timerModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // Keyboard support
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });
</script>
</body>
</html>