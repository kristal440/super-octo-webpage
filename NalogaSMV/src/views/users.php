<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Upravljanje uporabnikov</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<?php include 'partials/header.php'; ?>

<main>
    <h1>Upravljanje uporabnikov</h1>
    <p>Dodajte, izbrišite ali prikažite uporabnike.</p>

    <form action="../controllers/UserController.php" method="POST">
        <div class="input-group">
            <label for="name">Ime in priimek</label>
            <input type="text" id="name" name="name" required />
        </div>

        <div class="input-group">
            <label for="email">E-pošta</label>
            <input type="email" id="email" name="email" required />
        </div>

        <div class="input-group">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="Student">Študent</option>
                <option value="Teacher">Učitelj</option>
                <option value="Admin">Administrator</option>
            </select>
        </div>

        <button type="submit" class="btn-primary">Dodaj uporabnika</button>
    </form>

    <h2>Obstoječi uporabniki</h2>
    <ul class="user-list">
        <?php
        // Here you would typically fetch users from the database
        // For demonstration, we will use a static array
        $users = []; // This should be replaced with actual user fetching logic

        if (empty($users)) {
            echo "<li>Noben uporabnik ni dodan.</li>";
        } else {
            foreach ($users as $user) {
                echo "<li>
                        <span>{$user['name']} ({$user['email']}) - {$user['status']}</span>
                        <form action='../controllers/UserController.php' method='POST' style='display:inline;'>
                            <input type='hidden' name='user_id' value='{$user['id']}' />
                            <button type='submit' class='btn-secondary'>Izbriši</button>
                        </form>
                      </li>";
            }
        }
        ?>
    </ul>
</main>

<?php include 'partials/footer.php'; ?>

</body>
</html>