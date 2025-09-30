<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Upravljanje predmetov</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<?php include 'partials/header.php'; ?>

<main>
    <h1>Upravljanje predmetov</h1>
    <p>Dodajte, izbrišite ali si oglejte predmete.</p>

    <form action="../controllers/SubjectController.php" method="POST">
        <div class="input-group">
            <label for="subject-name">Ime predmeta</label>
            <input type="text" id="subject-name" name="name" required />
        </div>

        <div class="input-group">
            <label for="subject-description">Opis predmeta</label>
            <textarea id="subject-description" name="description" required></textarea>
        </div>

        <button type="submit" name="addSubject" class="btn-primary">Dodaj predmet</button>
    </form>

    <h2>Obstoječi predmeti</h2>
    <ul class="subject-list">
        <?php
        // Include the SubjectController to list subjects
        include '../controllers/SubjectController.php';
        $subjectController = new SubjectController();
        $subjects = $subjectController->listSubjects();

        foreach ($subjects as $subject) {
            echo '<li class="subject-item">';
            echo '<h3>' . htmlspecialchars($subject['name']) . '</h3>';
            echo '<p>' . htmlspecialchars($subject['description']) . '</p>';
            echo '<form action="../controllers/SubjectController.php" method="POST" style="display:inline;">';
            echo '<input type="hidden" name="id" value="' . $subject['id'] . '">';
            echo '<button type="submit" name="deleteSubject" class="btn-secondary">Izbriši</button>';
            echo '</form>';
            echo '</li>';
        }
        ?>
    </ul>
</main>

<?php include 'partials/footer.php'; ?>

</body>
</html>