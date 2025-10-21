<?php
session_start();
session_destroy();
session_start();
require_once 'database.php';

$error = '';
$success = '';
$step = 1; // Step 1: Enter email, Step 2: Enter new password

// Če uporabnik klikne "Nazaj na prijavo", pobrišemo session
if (isset($_GET['reset_session'])) {
    unset($_SESSION['reset_email']);
    $step = 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && !isset($_POST['new_password'])) {
        // Step 1: Check if email exists
        $email = trim($_POST['email']);
        
        if ($email === '') {
            $error = 'Prosim vnesite e-pošto.';
        } else {
            $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = :email');
            $stmt->execute(['email' => $email]);
            
            if ($stmt->rowCount() === 1) {
                // Email exists, proceed to password reset
                $_SESSION['reset_email'] = $email;
                $step = 2;
                $success = 'E-pošta najdena. Vnesite novo geslo.';
            } else {
                $error = 'Uporabnik s to e-pošto ne obstaja.';
            }
        }
    } elseif (isset($_POST['new_password']) && isset($_SESSION['reset_email'])) {
        // Step 2: Update password
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password === '' || $confirm_password === '') {
            $error = 'Prosim izpolnite vsa polja.';
            $step = 2;
        } elseif ($new_password !== $confirm_password) {
            $error = 'Gesli se ne ujemata.';
            $step = 2;
        } elseif (strlen($new_password) < 6) {
            $error = 'Geslo mora biti dolgo vsaj 6 znakov.';
            $step = 2;
        } else {
            // Update password in database
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE email = :email');
            
            if ($stmt->execute(['password' => $hashed_password, 'email' => $_SESSION['reset_email']])) {
                unset($_SESSION['reset_email']);
                $success = 'Geslo je bilo uspešno spremenjeno. Preusmeritev na prijavo...';
                header('refresh:2;url=login.php');
            } else {
                $error = 'Prišlo je do napake pri spreminjanju gesla.';
                $step = 2;
            }
        }
    }
}

// Check if we're in step 2 from session
if (isset($_SESSION['reset_email']) && $step === 1) {
    $step = 2;
}
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8" />
    <title>Pozabljeno geslo - Šolski sistem na daljavo</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
<div class="login-page">
    <div class="container">
        <div class="card">
            <div class="logo">
                <i class="fas fa-key"></i>
            </div>
            <h2>Pozabljeno geslo</h2>
            <p style="font-size: 0.9rem; color: #666; margin-bottom: 1.5rem;">
                <?php echo $step === 1 ? 'Vnesite svoj e-poštni naslov za ponastavitev gesla.' : 'Vnesite novo geslo.'; ?>
            </p>
            
            <?php if (!empty($error)): ?>
                <div class="error-message" style="color: #b00020; margin-bottom: 12px; background: #ffebee; padding: 10px; border-radius: 6px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message" style="color: #2e7d32; margin-bottom: 12px; background: #e8f5e9; padding: 10px; border-radius: 6px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step === 1): ?>
                <!-- Step 1: Enter Email -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="input-group">
                        <label for="email">E-pošta</label>
                        <input type="email" id="email" name="email" required />
                    </div>
                    <button type="submit" class="btn-primary">Nadaljuj</button>
                </form>
            <?php else: ?>
                <!-- Step 2: Enter New Password -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="input-group">
                        <label for="new_password">Novo geslo</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6" />
                    </div>
                    <div class="input-group">
                        <label for="confirm_password">Potrdi geslo</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6" />
                    </div>
                    <button type="submit" class="btn-primary">Spremeni geslo</button>
                </form>
            <?php endif; ?>
            
            <div style="margin-top: 1rem; text-align: center;">
                <a href="login.php?reset_session=1" style="color: #3498db; text-decoration: none; font-size: 0.9rem;">← Nazaj na prijavo</a>
            </div>
        </div>
    </div>
</div>
</body>

</html>
