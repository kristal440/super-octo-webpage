<?php
$host = "localhost";
$port = "5432";
$dbname = "mydb";
$user = "myuser";
$password = "mypassword";

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("Povezava z bazo ni uspela: " . pg_last_error());
}
?>
