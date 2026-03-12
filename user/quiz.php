<?php
session_start();
require_once '../config/db.php';

// Ensure user has entered their name
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Fetch all questions
try {
    // Note: If you want random questions, you could add: ORDER BY RAND() LIMIT 10
    $stmt = $pdo->query("SELECT * FROM questions ORDER BY id ASC");
    $questions = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// If no questions exist, show a message
if (count($questions) == 0) {
    $no_questions = true;
} else {
    $no_questions = false;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz - Online Quiz System</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body class="bg-gradient">
    <div class="container glass-panel" style="max-width: 800px; margin-top: 40px;">
        <div class="header-bar"
            style="border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; color: #2c3e50;">Official PHP Quiz</h2>
            <div class="user-greeting">
                Participant: <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </div>
        </div>

        <?php if ($no_questions): ?>
            <div style="text-align: center; padding: 50px 0; border-radius: 8px;">
                <p style="font-size: 1.2em; color: #7f8c8d;">No questions available right now.</p>
                <p style="color: #95a5a6;">Please tell the admin to add some!</p>
            </div>
            <div class="center-btn">
                <a href="dashboard.php" class="btn">Go Back</a>
            </div>
        <?php else: ?>
            <form action="submit.php" method="POST">
                <?php foreach ($questions as $index => $q): ?>
                    <div class="question-card glass-card">
                        <h3>Question
                            <?php echo $index + 1; ?>:
                            <?php echo htmlspecialchars($q['question']); ?>
                        </h3>
                        <div class="options">
                            <!-- We use the question ID as the key in the $_POST['answers'] array -->
                            <label><input type="radio" name="answers[<?php echo $q['id']; ?>]" value="0" required>
                                <?php echo htmlspecialchars($q['opt_a']); ?>
                            </label><br>
                            <label><input type="radio" name="answers[<?php echo $q['id']; ?>]" value="1" required>
                                <?php echo htmlspecialchars($q['opt_b']); ?>
                            </label><br>
                            <label><input type="radio" name="answers[<?php echo $q['id']; ?>]" value="2" required>
                                <?php echo htmlspecialchars($q['opt_c']); ?>
                            </label><br>
                            <label><input type="radio" name="answers[<?php echo $q['id']; ?>]" value="3" required>
                                <?php echo htmlspecialchars($q['opt_d']); ?>
                            </label><br>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="center-btn" style="margin-top: 30px;">
                    <button type="submit" class="btn submit-btn" style="width: 100%; box-sizing: border-box;"
                        onclick="return confirm('Are you sure you want to submit your answers?');">Submit Official
                        Quiz</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>