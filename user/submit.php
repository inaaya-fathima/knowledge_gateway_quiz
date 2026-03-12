<?php
session_start();
require_once '../config/db.php';

// Ensure user accessed via form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$submitted_answers = $_POST['answers'] ?? []; // Array of [question_id => selected_option_index]

// Fetch all questions to calculate score
try {
    $stmt = $pdo->query("SELECT id, correct_answer FROM questions");
    $questions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Returns [id => correct_answer]
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$total_questions = count($questions);
$score = 0;

if ($total_questions > 0) {
    foreach ($questions as $q_id => $correct_index) {
        if (isset($submitted_answers[$q_id]) && $submitted_answers[$q_id] == $correct_index) {
            $score++;
        }
    }

    // Save result to database
    try {
        $stmt = $pdo->prepare("INSERT INTO quiz_results (user_id, score, total_questions) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $score, $total_questions]);
        $result_id = $pdo->lastInsertId();

        // Store result ID in session to show on result page
        $_SESSION['last_result_id'] = $result_id;

        header("Location: result.php");
        exit;
    } catch (PDOException $e) {
        die("Error saving results: " . $e->getMessage());
    }
} else {
    // If no questions exist
    header("Location: quiz.php");
    exit;
}
?>