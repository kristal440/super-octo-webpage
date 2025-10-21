<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'database.php';

// Get user information
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];
$user_status = $_SESSION['user_status'];

// Fetch user's full name
$stmt = $pdo->prepare('SELECT name FROM users WHERE id = :id');
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_name = $user ? $user['name'] : 'Uporabnik';

// Fetch user's enrolled subjects with teacher information
$stmt = $pdo->prepare('
    SELECT 
        s.id, 
        s.name, 
        s.description, 
        u.name as teacher_name
    FROM subjects s
    INNER JOIN user_subjects us ON s.id = us.subject_id
    LEFT JOIN users u ON s.teacher_id = u.id
    WHERE us.user_id = :user_id
    ORDER BY s.name
');
$stmt->execute(['user_id' => $user_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count statistics
$total_subjects = count($subjects);

// Count total materials available to user
$stmt = $pdo->prepare('
    SELECT COUNT(*) as count
    FROM materials m
    INNER JOIN user_subjects us ON m.subject_id = us.subject_id
    WHERE us.user_id = :user_id
');
$stmt->execute(['user_id' => $user_id]);
$total_materials = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Count submitted assignments by user
$stmt = $pdo->prepare('
    SELECT COUNT(*) as count
    FROM submissions
    WHERE user_id = :user_id
');
$stmt->execute(['user_id' => $user_id]);
$total_submissions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
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
        <span>👤 <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_status); ?>)</span>
        <button onclick="window.location.href='logout.php'">Odjava</button>
    </div>
</header>

<main>
    <h1>Učenčev portal</h1>
    <p>Dostop do gradiv in oddaja nalog</p>

    <div class="stats">
        <div class="stat-card">
            <h3>Moji predmeti</h3>
            <p><strong><?php echo $total_subjects; ?></strong></p>
            <i class="icon-book"></i>
        </div>
        <div class="stat-card">
            <h3>Dostopna gradiva</h3>
            <p><strong><?php echo $total_materials; ?></strong></p>
            <i class="icon-file"></i>
        </div>
        <div class="stat-card">
            <h3>Oddane naloge</h3>
            <p><strong><?php echo $total_submissions; ?></strong></p>
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
                <button class="btn-primary" onclick="window.location.href='enroll_subjects.php'">Izbirate predmete</button>
            </div>
        <?php else: ?>
            <ul class="subject-list">
                <?php foreach ($subjects as $subject): ?>
                    <li class="subject-item">
                        <h3><?php echo htmlspecialchars($subject['name']); ?></h3>
                        <p><strong>Predavatelj:</strong> <?php echo htmlspecialchars($subject['teacher_name'] ?? 'Ni določen'); ?></p>
                        <?php if (!empty($subject['description'])): ?>
                            <p><small><?php echo htmlspecialchars($subject['description']); ?></small></p>
                        <?php endif; ?>
                        <a href="subject.php?id=<?php echo $subject['id']; ?>">Odpri predmet</a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p>Za dostop do gradiv in oddajo nalog morate izbrati predmete.</p>
                <button class="btn-primary" onclick="window.location.href='enroll_subjects.php'">Izbirate predmete</button>
        <?php endif; ?>
    </section>
</main>

</body>
</html>