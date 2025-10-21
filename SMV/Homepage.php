<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_email']; // You can fetch the actual name from DB if needed
$user_status = $_SESSION['user_status'] ?? 'Student';

// Fetch user's full name
$stmt_user = $pdo->prepare('SELECT name FROM users WHERE id = :user_id');
$stmt_user->execute(['user_id' => $user_id]);
$user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
$user_name = $user_data['name'] ?? $user_name;

// Fetch subjects enrolled by the user
$stmt = $pdo->prepare('
    SELECT s.id, s.name, s.description, u.name as teacher
    FROM subjects s
    LEFT JOIN users u ON s.teacher_id = u.id
    INNER JOIN user_subjects us ON s.id = us.subject_id
    WHERE us.user_id = :user_id
');
$stmt->execute(['user_id' => $user_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count materials available to the user
$stmt_materials = $pdo->prepare('
    SELECT COUNT(*) as count
    FROM materials m
    INNER JOIN user_subjects us ON m.subject_id = us.subject_id
    WHERE us.user_id = :user_id
');
$stmt_materials->execute(['user_id' => $user_id]);
$materials_count = $stmt_materials->fetch(PDO::FETCH_ASSOC)['count'];

// Count submitted assignments
$stmt_submissions = $pdo->prepare('
    SELECT COUNT(*) as count
    FROM submissions
    WHERE user_id = :user_id
');
$stmt_submissions->execute(['user_id' => $user_id]);
$submissions_count = $stmt_submissions->fetch(PDO::FETCH_ASSOC)['count'];

// Translate status to Slovenian
$status_slovenian = [
    'Student' => 'Učenec',
    'Teacher' => 'Učitelj',
    'Admin' => 'Administrator'
];
$user_status_display = $status_slovenian[$user_status] ?? 'Učenec';
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Učenčev portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="logo"><a href="Homepage.php" class="reload">🎓 Šolski sistem na daljavo</a></div>
    <div class="user-info">
        <span>👤 <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_status_display); ?>)</span>
        <button onclick="window.location.href='logout.php'">Odjava</button>
    </div>
</header>
<main>
    <h1>Učenčev portal</h1>
    <p>Dostop do gradiv in oddaja nalog</p>
    <div class="stats">
        <div class="stat-card">
            <h3>Moji predmeti</h3>
            <p><strong><?php echo count($subjects); ?></strong></p>
            <i class="icon-book"></i>
        </div>
        <div class="stat-card">
            <h3>Dostopna gradiva</h3>
            <p><strong><?php echo $materials_count; ?></strong></p>
            <i class="icon-file"></i>
        </div>
        <div class="stat-card">
            <h3>Oddane naloge</h3>
            <p><strong><?php echo $submissions_count; ?></strong></p>
            <i class="icon-upload"></i>
        </div>
    </div>
    <section class="content">
        <h2>Moji predmeti</h2>
        <?php if (empty($subjects)): ?>
            <div class="empty-state">
                <i class="icon-book-large"></i>
                <h3>Nimate izbranih predmetov</h3>
                <p>Za dostop do gradiv in oddajo nalog morate izbrati predmete.</p>
                <button class="btn-primary" onclick="window.location.href='select_subjects.php'">Izbirajte predmete</button>
            </div>
        <?php else: ?>
            <ul class="subject-list">
                <?php foreach ($subjects as $subject): ?>
                    <li class="subject-item">
                        <h3><?php echo htmlspecialchars($subject['name']); ?></h3>
                        <p><strong>Predavatelj:</strong> <?php echo htmlspecialchars($subject['teacher'] ?? 'Ni dodeljen'); ?></p>
                        <p><small><?php echo htmlspecialchars($subject['description'] ?? 'Brez opisa'); ?></small></p>
                        <a href="subject.php?id=<?php echo $subject['id']; ?>">Odpri predmet</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
