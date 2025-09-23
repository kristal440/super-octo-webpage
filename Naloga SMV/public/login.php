<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Prijava</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="login-page">
    <div class="container">
        <div class="card">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
            </div>

            <h2>Admin Dashboard</h2>

            <form id="login-form" action="index.php" method="POST">
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