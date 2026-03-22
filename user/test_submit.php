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
$params = array_merge($test_info['q_ids'], $test_info['q_ids']);
$stmt = $pdo->prepare("SELECT id, correct_answer FROM test_questions WHERE id IN ($placeholders) UNION SELECT id, correct_answer FROM questions WHERE id IN ($placeholders)");
$stmt->execute($params);
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
    $tr_id = $pdo->lastInsertId();
    
    if ($tr_id) {
        $stmt3 = $pdo->prepare("INSERT INTO test_detailed_results (test_result_id, question_id, student_answer, is_correct, time_taken) VALUES (?, ?, ?, ?, ?)");
        foreach ($test_info['q_ids'] as $qid) {
            $ans = isset($_POST['answers'][$qid]) ? (int)$_POST['answers'][$qid] : null;
            $is_c = 0;
            if ($ans !== null && isset($raw_answers[$qid]) && $ans === (int)$raw_answers[$qid]) {
                $is_c = 1;
            }
            $tt = isset($_POST['time_taken'][$qid]) ? (int)$_POST['time_taken'][$qid] : 0;
            $stmt3->execute([$tr_id, $qid, $ans, $is_c, $tt]);
        }
    }
} catch (PDOException $e) {
    // table may not exist yet — that's okay
}

// Redirect to result page (which handles star award + confetti)
header("Location: result.php");
exit;
?>