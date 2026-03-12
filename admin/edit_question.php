<?php
session_start();
require_once '../config/db.php';

// Protect admin pages
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? 0;
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic = trim($_POST['topic']);
    $question = trim($_POST['question']);
    $opt_a = trim($_POST['opt_a']);
    $opt_b = trim($_POST['opt_b']);
    $opt_c = trim($_POST['opt_c']);
    $opt_d = trim($_POST['opt_d']);
    $correct_answer = $_POST['correct_answer'];

    if (!empty($topic) && !empty($question) && !empty($opt_a)) {
        try {
            $stmt = $pdo->prepare("UPDATE questions SET topic=?, question=?, opt_a=?, opt_b=?, opt_c=?, opt_d=?, correct_answer=? WHERE id=?");
            $stmt->execute([$topic, $question, $opt_a, $opt_b, $opt_c, $opt_d, $correct_answer, $id]);
            $message = "Question updated successfully.";
        } catch (PDOException $e) {
            $error = "Error updating question.";
        }
    } else {
        $error = "Please fill completely.";
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$id]);
    $q = $stmt->fetch();
    if (!$q) {
        die("Question not found.");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question - Admin</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body class="bg-gradient">
    <div class="container glass-panel" style="max-width: 600px; margin-top: 50px;">
        <h2 style="color:#2c3e50; margin-top:0;">Edit Question</h2>
        <a href="manage_questions.php" class="back-link">&larr; Back to Manage</a>
        <br><br>

        <?php if (!empty($message)): ?>
            <div class="success-msg">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="error-msg">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="glass-card">
            <form action="edit_question.php?id=<?php echo $id; ?>" method="POST">
                <div class="form-group">
                    <label>Topic</label>
                    <input type="text" name="topic" value="<?php echo htmlspecialchars($q['topic']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Question</label>
                    <input type="text" name="question" value="<?php echo htmlspecialchars($q['question']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Option A</label>
                    <input type="text" name="opt_a" value="<?php echo htmlspecialchars($q['opt_a']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Option B</label>
                    <input type="text" name="opt_b" value="<?php echo htmlspecialchars($q['opt_b']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Option C</label>
                    <input type="text" name="opt_c" value="<?php echo htmlspecialchars($q['opt_c']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Option D</label>
                    <input type="text" name="opt_d" value="<?php echo htmlspecialchars($q['opt_d']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Correct Answer</label>
                    <select name="correct_answer" required>
                        <option value="0" <?php if ($q['correct_answer'] == 0)
                            echo 'selected'; ?>>Option A</option>
                        <option value="1" <?php if ($q['correct_answer'] == 1)
                            echo 'selected'; ?>>Option B</option>
                        <option value="2" <?php if ($q['correct_answer'] == 2)
                            echo 'selected'; ?>>Option C</option>
                        <option value="3" <?php if ($q['correct_answer'] == 3)
                            echo 'selected'; ?>>Option D</option>
                    </select>
                </div>
                <button type="submit" class="btn submit-btn" style="width: 100%;">Update Question</button>
            </form>
        </div>
    </div>
</body>

</html>