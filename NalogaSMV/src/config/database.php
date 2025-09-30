<?php
// filepath: admin-dashboard/src/config/database.php

$host = 'localhost';
$dbname = 'admin_dashboard';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

function getConnection() {
    global $pdo;
    return $pdo;
}
?>