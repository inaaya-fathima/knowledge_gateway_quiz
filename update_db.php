<?php
// update_db.php — Run this ONCE to ensure all required tables and columns exist
// Access: http://localhost/ca2.0/quiz-system/update_db.php

require_once 'config/db.php';

$steps = [];
$errors = [];

// ── Helper ──
function run(PDO $pdo, string $sql, string $label, array &$steps, array &$errors): void {
    try {
        $pdo->exec($sql);
        $steps[] = "✅ $label";
    } catch (PDOException $e) {
        $errors[] = "⚠️ $label: " . $e->getMessage();
    }
}

// ── 1. users table ──
run($pdo, "CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "users table", $steps, $errors);

// ── 2. questions table ──
run($pdo, "CREATE TABLE IF NOT EXISTS `questions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `topic` varchar(100) NOT NULL DEFAULT 'General',
    `question` text NOT NULL,
    `opt_a` varchar(255) NOT NULL,
    `opt_b` varchar(255) NOT NULL,
    `opt_c` varchar(255) NOT NULL,
    `opt_d` varchar(255) NOT NULL,
    `correct_answer` varchar(10) NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_topic` (`topic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "questions table", $steps, $errors);

// ── 3. quiz_results table ──
run($pdo, "CREATE TABLE IF NOT EXISTS `quiz_results` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `score` int(11) NOT NULL,
    `total_questions` int(11) NOT NULL,
    `attempt_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "quiz_results table", $steps, $errors);

// ── 4. tests table ──
run($pdo, "CREATE TABLE IF NOT EXISTS `tests` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `test_code` varchar(20) NOT NULL,
    `topic` varchar(100) NOT NULL,
    `num_questions` int(11) NOT NULL DEFAULT 10,
    `time_limit` int(11) NOT NULL DEFAULT 15,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `test_code` (`test_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "tests table", $steps, $errors);

// ── 5. test_results table ──
run($pdo, "CREATE TABLE IF NOT EXISTS `test_results` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `student_name` varchar(100) NOT NULL,
    `test_code` varchar(20) NOT NULL DEFAULT '',
    `topic` varchar(100) NOT NULL DEFAULT '',
    `score` int(11) NOT NULL,
    `total_questions` int(11) NOT NULL,
    `attempt_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "test_results table", $steps, $errors);

// ── 6. Ensure topic column exists in questions ──
run($pdo, "ALTER TABLE questions ADD COLUMN IF NOT EXISTS `topic` varchar(100) NOT NULL DEFAULT 'General';",
    "questions.topic column", $steps, $errors);

// ── 7. Ensure foreign key doesn't break things (soft ignore) ──
// We skip strict FK enforcement to keep things flexible

// ── 8. contacts table ──
run($pdo, "CREATE TABLE IF NOT EXISTS `contacts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `message` text NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "contacts table", $steps, $errors);

// ── 9. communications (user requests) ──
run($pdo, "CREATE TABLE IF NOT EXISTS `communication_requests` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `sender_id` int(11) NOT NULL,
    `receiver_id` int(11) NOT NULL,
    `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "communication_requests table", $steps, $errors);

// ── 10. messages ──
run($pdo, "CREATE TABLE IF NOT EXISTS `messages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `sender_id` int(11) NOT NULL,
    `sender_type` varchar(10) NOT NULL DEFAULT 'user',
    `receiver_id` int(11) NOT NULL,
    `receiver_type` varchar(10) NOT NULL DEFAULT 'user',
    `message` text NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "messages table", $steps, $errors);

// ── 11. notifications ──
run($pdo, "CREATE TABLE IF NOT EXISTS `notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `type` varchar(20) NOT NULL DEFAULT 'system',
    `message` text NOT NULL,
    `is_read` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "notifications table", $steps, $errors);

// ── 12. Add is_ended to tests ──
run($pdo, "ALTER TABLE `tests` ADD COLUMN IF NOT EXISTS `is_ended` tinyint(1) NOT NULL DEFAULT 0;", "tests.is_ended column", $steps, $errors);

// ── 13. test_detailed_results ──
run($pdo, "CREATE TABLE IF NOT EXISTS `test_detailed_results` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `test_result_id` int(11) NOT NULL,
    `question_id` int(11) NOT NULL,
    `student_answer` varchar(255) DEFAULT NULL,
    `is_correct` tinyint(1) NOT NULL DEFAULT 0,
    `time_taken` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `test_result_id` (`test_result_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "test_detailed_results table", $steps, $errors);

// ── 14. Add status to contacts ──
run($pdo, "ALTER TABLE `contacts` ADD COLUMN IF NOT EXISTS `status` ENUM('pending', 'completed') NOT NULL DEFAULT 'pending';", "contacts.status column", $steps, $errors);

// ── 15. practice_results ──
run($pdo, "CREATE TABLE IF NOT EXISTS `practice_results` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `student_name` varchar(100) NOT NULL,
    `topic` varchar(100) NOT NULL,
    `score` int(11) NOT NULL,
    `total_questions` int(11) NOT NULL,
    `time_taken` int(11) NOT NULL DEFAULT 0,
    `practice_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "practice_results table", $steps, $errors);

// ── 16. official_requests ──
run($pdo, "CREATE TABLE IF NOT EXISTS `official_requests` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(100) NOT NULL,
    `name` varchar(100) NOT NULL,
    `dob` varchar(50) NOT NULL,
    `address` text NOT NULL,
    `mobile` varchar(20) NOT NULL,
    `email` varchar(100) NOT NULL,
    `institution` varchar(100) NOT NULL,
    `status` enum('pending','completed') NOT NULL DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", "official_requests table", $steps, $errors);

$total = count($steps);
$errCount = count($errors);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Update</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .wrap { max-width: 560px; width: 100%; }
        .step { padding: 8px 14px; margin: 6px 0; border-radius: 6px; font-size: 0.9em; font-family: monospace; }
        .step.ok  { background: rgba(46,204,113,0.08); color: #2ecc71; border: 1px solid rgba(46,204,113,0.2); }
        .step.err { background: rgba(231,76,60,0.08);  color: #e74c3c; border: 1px solid rgba(231,76,60,0.2); }
    </style>
</head>
<body class="bg-gradient">
<div class="wrap">
    <div class="glass-card">
        <h2 style="margin-bottom:4px;">Database Update</h2>
        <p style="font-size:0.85em; margin-bottom:20px;">Ensuring all tables and columns are in place…</p>

        <?php foreach ($steps as $s): ?>
            <div class="step ok"><?php echo htmlspecialchars($s); ?></div>
        <?php endforeach; ?>

        <?php foreach ($errors as $e): ?>
            <div class="step err"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>

        <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border);">
            <p style="font-size:0.85em; color:var(--text-muted);">
                <?php echo $total; ?> step<?php echo $total != 1 ? 's' : ''; ?> completed,
                <?php echo $errCount; ?> warning<?php echo $errCount != 1 ? 's' : ''; ?>.
                <?php if ($errCount == 0): ?>
                    <span style="color:var(--ok);">All good! ✓</span>
                <?php else: ?>
                    <span style="color:var(--text-muted);">Some warnings are normal if columns/tables already exist.</span>
                <?php endif; ?>
            </p>
            <a href="index.php" class="btn submit-btn" style="margin-top:10px; display:inline-block; padding:10px 20px;">← Back to Site</a>
        </div>
    </div>
</div>
</body>
</html>