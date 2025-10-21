<?php
// Server-side login handling
session_start();
// Include DB connection (uses PDO)
require_once 'database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic input validation
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if ($email === '' || $password === '') {
        $error = 'Prosim vnesite e-pošto in geslo.';
    } else {
        // Query user by email using PDO prepared statement
        $stmt = $pdo->prepare('SELECT id, email, password, status FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $hash = $user['password'];
            
            // Verify password against the hash
            if (password_verify($password, $hash)) {
                // Authentication successful
                // Store user info in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_status'] = $user['status']; // Store status too (Student/Teacher/Admin)
                
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
            </div>
            <?php if (!empty($error)): ?>
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
                <a href="register.php">Registreraj se</a>
            </form>
            
        </div>
    </div>
</div>
</body>
</html>
