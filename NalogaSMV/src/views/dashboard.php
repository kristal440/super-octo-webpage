<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../../public/style.css">
</head>
<body>

<?php include 'partials/header.php'; ?>

<main>
    <h1>Admin Dashboard</h1>
    <p>Welcome to the admin dashboard. Here you can manage users and subjects.</p>

    <section>
        <h2>Manage Users</h2>
        <a href="users.php" class="btn-primary">View Users</a>
    </section>

    <section>
        <h2>Manage Subjects</h2>
        <a href="subjects.php" class="btn-primary">View Subjects</a>
    </section>
</main>

<?php include 'partials/footer.php'; ?>

</body>
</html>