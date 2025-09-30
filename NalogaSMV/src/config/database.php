<?php
// filepath: admin-dashboard/src/config/database.php

$host = 'localhost';
$db = 'mydb';
$user = 'myuser';
$password = 'mypassword';
$port = '5432'; 

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

function getConnection() {
    global $pdo;
    return $pdo;
}
?>