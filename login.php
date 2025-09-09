<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8" />
    <title>Šolski sistem na daljavo</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>

<!-- DODAJ WRAPPER Z UNIKATNIM RAZREDOM -->
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

            <form id="login-form" class="form active">
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

            <form id="register-form" class="form">
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
        </div>
    </div>

</div> <!-- .login-page -->

<script>
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.getAttribute('data-tab');
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.form').forEach(f => f.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(`${tabName}-form`).classList.add('active');
        });
    });
</script>

</body>
</html>