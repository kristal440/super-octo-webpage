<?php
  
?>
<!DOCTYPE html>
<html lang="sl">
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

            <div class="tabs">
                <button class="tab active" data-tab="login">Prijava</button>
                <button class="tab" data-tab="register">Registracija</button>
            </div>

            <form id="login-form" class="form active" action="Homepage.php">
                <div class="input-group">
                    <label for="email">E-pošta</label>
                    <input type="email" id="email" name="email" required />
                </div>

                <div class="input-group">
                    <label for="password">Geslo</label>
                    <input type="password" id="password" name="password" required />
                </div>

                <button type="submit" class="btn-primary">Prijava</button>
            </form>

            
        </div>
    </div>

</div>



</body>
</html>