<?php


session_start();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'database.php';

    $name = $_POST['ime'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 

    
    $sql_check = "SELECT * FROM uporabniki WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $error = "Ta e-poštni naslov je že uporabljen.";
    } else {
        
        $sql_insert = "INSERT INTO uporabniki (ime, email, geslo) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("sss", $ime, $email, $password);
        $stmt_insert->execute();

        
        header("Location: login.php");
        exit();
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

                <form id="register-form" class="form" action="login.php">
                <div class="input-group">
                    <label for="ime">Ime in priimek</label>
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