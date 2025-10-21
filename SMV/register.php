<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ensure database connection is always available
require_once __DIR__ . '/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Pogleda če je tak emial že v tabeli
    $sql_check = "SELECT * FROM users WHERE email = $1";
    $result_check = pg_query_params($conn, $sql_check, array($email));

    if (pg_num_rows($result_check) > 0) {
        $error = "Ta e-poštni naslov je že uporabljen.";
    } else {
        //doda novega uporabnika
        $sql_insert = "INSERT INTO users (name, email, password) VALUES ($1, $2, $3)";
        $result_insert = pg_query_params($conn, $sql_insert, array($name, $email, $password));

        if ($result_insert) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Prišlo je do napake pri registraciji.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>Šolski sistem na daljavo</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
    <div class="login-page">
        <div class="container">
            <div class="card">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h2>Šolski sistem na daljavo</h2>

                <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

                <form id="register-form" class="form" action="register.php" method="POST">
                    <div class="input-group">
                        <label for="name">Ime in priimek</label>
                        <input type="text" id="name" name="name" required />
                    </div>

                    <div class="input-group">
                        <label for="email">E-pošta</label>
                        <input type="email" id="reg-email" name="email" required />
                    </div>

                    <div class="input-group">
                        <label for="password">Geslo</label>
                        <input type="password" id="reg-password" name="password" required />
                    </div>

                    <button type="submit" class="btn-primary">Registracija</button>
                </form>
                <a href="login.php">LOGIN</a>
            </div>
        </div>
    </div>
</body>
</html>
