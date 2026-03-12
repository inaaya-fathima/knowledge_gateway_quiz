<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_code = trim($_POST['test_code']);

    // Check test code in DB
    $stmt = $pdo->prepare("SELECT * FROM tests WHERE test_code = ?");
    $stmt->execute([$test_code]);
    $test = $stmt->fetch();

    if ($test) {
        // Fetch questions from the OFFICIAL test question pool (separate from practice)
        $qStmt = $pdo->prepare("SELECT id FROM test_questions WHERE topic = ? ORDER BY RAND() LIMIT ?");
        $qStmt->bindValue(1, $test['topic'], PDO::PARAM_STR);
        $qStmt->bindValue(2, (int) $test['num_questions'], PDO::PARAM_INT);
        $qStmt->execute();
        $qIds = $qStmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($qIds) == 0) {
            die("Error: This test is misconfigured. No questions found for the topic.");
        }

        $_SESSION['current_test'] = [
            'test_code' => $test_code,
            'topic' => $test['topic'],
            'time_limit' => $test['time_limit'],
            'q_ids' => $qIds,
            'start_time' => time()
        ];

        header("Location: test_quiz.php");
        exit;
    } else {
        // Invalid test code, redirect with error
        echo "<script>alert('Invalid Test Code. Please try again.'); window.location.href='dashboard.php';</script>";
        exit;
    }
} else {
    header("Location: dashboard.php");
    exit;
}
?>