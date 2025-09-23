<?php
$host = "localhost";
$db   = "mydb";
$user = "myuser";
$pass = "mypassword";
$port = "5432";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "<h2>✅ Connected to PostgreSQL</h2>";

    $stmt = $pdo->query("SELECT id, name, description FROM subjects ORDER BY id");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
        echo "<ul>";
        foreach ($rows as $row) {
            echo "<li><b>" . htmlspecialchars($row['name']) . "</b>: " . htmlspecialchars($row['description']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No data found in 'subjects'.</p>";
    }

} catch (PDOException $e) {
    echo "<h2>❌ Database connection failed:</h2> " . htmlspecialchars($e->getMessage());
}
?>
