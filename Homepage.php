<?php
// ==== Database Connection ====
$host = "localhost";
$db   = "mydb";         // change if you used a different name
$user = "myuser";       // your DB user
$pass = "mypassword";   // your DB password
$port = "5432";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Homepage</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="logo">📚 My Subjects</div>
</header>

<main>
    <h1>Available Subjects</h1>
    <ul class="subject-list">
        <?php
        try {
            $stmt = $pdo->query("SELECT id, name, description FROM subjects ORDER BY id");
            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($subjects) {
                foreach ($subjects as $subj) {
                    echo "<li class='subject-item'>";
                    echo "<h3>" . htmlspecialchars($subj['name']) . "</h3>";
                    echo "<p>" . htmlspecialchars($subj['description']) . "</p>";
                    echo "</li>";
                }
            } else {
                echo "<div class='empty-state'><p>No subjects found.</p></div>";
            }
        } catch (PDOException $e) {
            echo "<div class='empty-state'><p>Error: " . htmlspecialchars($e->getMessage()) . "</p></div>";
        }
        ?>
    </ul>
</main>
</body>
</html>
