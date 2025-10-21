<?php
// Server-side login handling
// Assumptions:
// - there is a PostgreSQL `users` table with columns `id`, `email`, `password`
// - `password` stores a password hash created with PHP's password_hash()

session_start();

// Include DB connection (uses pg_connect)
require_once __DIR__ . '/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic input validation
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($email === '' || $password === '') {
        $error = 'Prosim vnesite e-pošto in geslo.';
    } else {
        // Query user by email using parameterized query
        $result = pg_query_params($conn, 'SELECT id, email, password FROM users WHERE email = $1', array($email));

        if ($result && pg_num_rows($result) === 1) {
            $user = pg_fetch_assoc($result);
            $hash = $user['password'];

            // Verify password against the hash
            if (password_verify($password, $hash)) {
                // Authentication successful
                // Store minimal user info in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];

                // Redirect to homepage
                header('Location: Homepage.php');
                exit;
            } else {
                $error = 'Neveljavno geslo.';
            }
        } else {
            $error = 'Uporabnik s to e-pošto ne obstaja.';
        }
    }
}

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

            <?php if (!empty(
$error
            )): ?>
                <div class="error-message" style="color: #b00020; margin-bottom: 12px;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form id="login-form" class="form active" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
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