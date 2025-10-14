<?php
// Add these at the very top
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
session_start();

// Add debug output
echo "<h3>Debug Info:</h3>";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";

// Example: Get all available subjects
$stmt = $pdo->query("SELECT id, name, description FROM subjects ORDER BY name");
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Number of subjects: " . count($subjects) . "<br><br>";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p style='color: blue;'>Form was submitted!</p>";
    
    // Debug: Show what was received
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    $user_id = $_POST['user_id'] ?? null;
    $subject_id = $_POST['subject_id'] ?? null;
    $access_key = $_POST['access_key'] ?? null;
    
    if (!$user_id || !$subject_id || !$access_key) {
        echo "<p style='color: red;'>Missing required fields!</p>";
    } else {
        // Verify access key
        $stmt = $pdo->prepare("SELECT access_key FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($subject && $subject['access_key'] === $access_key) {
            // Check if already enrolled
            $check = $pdo->prepare("SELECT id FROM user_subjects WHERE user_id = ? AND subject_id = ?");
            $check->execute([$user_id, $subject_id]);
            
            if ($check->fetch()) {
                echo "<p style='color: orange;'>⚠️ Already enrolled in this subject!</p>";
            } else {
                // Enroll student
                $stmt = $pdo->prepare("INSERT INTO user_subjects (user_id, subject_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $subject_id]);
                echo "<p style='color: green;'>✅ Successfully enrolled!</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Invalid access key!</p>";
            echo "Expected: " . ($subject['access_key'] ?? 'NULL') . ", Got: " . $access_key;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Enroll in Subject</title>
</head>
<body>
    <h1>Enroll in a Subject</h1>
    <form method="POST" action="enroll_student.php">
        <label>User ID: <input type="number" name="user_id" required></label><br><br>
        <label>Subject: 
            <select name="subject_id" required>
                <option value="">-- Select Subject --</option>
                <?php foreach ($subjects as $subject): ?>
                    <option value="<?= $subject['id'] ?>">
                        <?= htmlspecialchars($subject['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br><br>
        <label>Access Key: <input type="text" name="access_key" maxlength="6" required></label><br><br>
        <button type="submit">Enroll</button>
    </form>
    
    <hr>
    <h2>Available Subjects:</h2>
    <ul>
        <?php foreach ($subjects as $subject): ?>
            <li>ID: <?= $subject['id'] ?> - <?= htmlspecialchars($subject['name']) ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>