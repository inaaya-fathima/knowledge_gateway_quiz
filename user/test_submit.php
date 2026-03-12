<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth_helper.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['current_test'])) {
    header("Location: dashboard.php");
    exit;
}

$test_info = $_SESSION['current_test'];
$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Prevent re-submission
unset($_SESSION['current_test']);

$score = 0;
$total = count($test_info['q_ids']);

// Fetch correct answers
$placeholders = implode(',', array_fill(0, $total, '?'));
$stmt = $pdo->prepare("SELECT id, correct_answer FROM questions WHERE id IN ($placeholders)");
$stmt->execute($test_info['q_ids']);
$raw_answers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [id => correct_answer_index]

if (isset($_POST['answers']) && is_array($_POST['answers'])) {
    foreach ($test_info['q_ids'] as $qid) {
        if (isset($_POST['answers'][$qid])) {
            if ((int)$_POST['answers'][$qid] === (int)$raw_answers[$qid]) {
                $score++;
            }
        }
    }
}

// Save to quiz_results (used by result page)
$result_id = null;
try {
    $stmt = $pdo->prepare("INSERT INTO quiz_results (user_id, score, total_questions) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $score, $total]);
    $result_id = $pdo->lastInsertId();
    $_SESSION['last_result_id'] = $result_id;
} catch (PDOException $e) {
    // If quiz_results fails, try test_results table
}

// Also try saving to test_results table (original schema)
try {
    $stmt2 = $pdo->prepare("INSERT INTO test_results (user_id, student_name, test_code, topic, score, total_questions) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt2->execute([$user_id, $user_name, $test_info['test_code'], $test_info['topic'], $score, $total]);
} catch (PDOException $e) {
    // table may not exist yet — that's okay
}

// Redirect to result page (which handles star award + confetti)
header("Location: result.php");
exit;
?>