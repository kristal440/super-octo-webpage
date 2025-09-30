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
                    <label for="name">Ime in priimek</label>
                    <input type="text" id="name" name="name" required />
                </div>

                <div class="input-group">
                    <label for="reg-email">E-pošta</label>
                    <input type="email" id="reg-email" name="email" required />
                </div>

                <div class="input-group">
                    <label for="reg-password">Geslo</label>
                    <input type="password" id="reg-password" name="password" required />
                </div>

               

                <button type="submit" class="btn-primary">Registracija</button>
            </form>
    </body>
</html>