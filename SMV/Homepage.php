<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];
$user_status = $_SESSION['user_status'];

// Get user's enrolled subjects
$subjects_sql = "
    SELECT s.id, s.name, s.description, u.name as teacher_name 
    FROM subjects s 
    LEFT JOIN users u ON s.teacher_id = u.id 
    WHERE s.id IN (SELECT subject_id FROM user_subjects WHERE user_id = :user_id)
    ORDER BY s.name
";
$stmt = $pdo->prepare($subjects_sql);
$stmt->execute(['user_id' => $user_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
// Total subjects count
$total_subjects_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_subjects WHERE user_id = :user_id");
$total_subjects_stmt->execute(['user_id' => $user_id]);
$total_subjects = $total_subjects_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total materials count
$total_materials_stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM materials m 
    JOIN user_subjects us ON m.subject_id = us.subject_id 
    WHERE us.user_id = :user_id
");
$total_materials_stmt->execute(['user_id' => $user_id]);
$total_materials = $total_materials_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total assignments count
$total_assignments_stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM assignments a 
    JOIN user_subjects us ON a.subject_id = us.subject_id 
    WHERE us.user_id = :user_id
");
$total_assignments_stmt->execute(['user_id' => $user_id]);
$total_assignments = $total_assignments_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Pending assignments count (assignments without submission)
$pending_assignments_stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM assignments a 
    JOIN user_subjects us ON a.subject_id = us.subject_id 
    WHERE us.user_id = :user_id 
    AND a.id NOT IN (
        SELECT assignment_id FROM submissions WHERE user_id = :user_id
    )
");
$pending_assignments_stmt->execute(['user_id' => $user_id]);
$pending_assignments = $pending_assignments_stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8" />
    <title>Šolski sistem na daljavo - Domov</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body class="dashboard-page">
    <header>
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span>Šolski sistem na daljavo</span>
        </div>
        <div class="user-info">
            <span>Pozdravljeni, <?php echo htmlspecialchars($user_email); ?> (<?php echo htmlspecialchars($user_status); ?>)</span>
            <a href="logout.php" class="btn-primary">Odjava</a>
        </div>
    </header>

    <main>
        <h1>Nadzorna plošča</h1>
        <p>Pregled vaših predmetov in aktivnosti</p>

        <!-- Statistics Cards -->
        <div class="stats">
            <div class="stat-card">
                <h3>Moji predmeti</h3>
                <p><?php echo $total_subjects; ?></p>
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-card">
                <h3>Gradiva</h3>
                <p><?php echo $total_materials; ?></p>
                <i class="fas fa-file"></i>
            </div>
            <div class="stat-card">
                <h3>Naloge</h3>
                <p><?php echo $total_assignments; ?></p>
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-card">
                <h3>Čakajoče naloge</h3>
                <p><?php echo $pending_assignments; ?></p>
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <!-- My Subjects Section -->
        <div class="content">
            <h2>Moji predmeti</h2>
            
            <?php if (empty($subjects)): ?>
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>Nimate še nobenega predmeta</h3>
                    <p>Pridružite se predmetu z dostopno kodo ali počakajte, da vas učitelj doda.</p>
                    <button class="btn-primary" onclick="alert('Funkcija za pridružitev predmetu bo na voljo kmalu.')">Pridruži se predmetu</button>
                </div>
            <?php else: ?>
                <ul class="subject-list">
                    <?php foreach ($subjects as $subject): ?>
                        <li class="subject-item">
                            <h3><?php echo htmlspecialchars($subject['name']); ?></h3>
                            <p><?php echo htmlspecialchars($subject['description']); ?></p>
                            <p><strong>Učitelj:</strong> <?php echo htmlspecialchars($subject['teacher_name'] ?? 'Ni določen'); ?></p>
                            <a href="#" onclick="alert('Podrobnosti predmeta bodo na voljo kmalu.')">Ogled predmeta →</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Recent Activities Section -->
        <div class="content">
            <h2>Zadnje aktivnosti</h2>
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <h3>Ni nedavnih aktivnosti</h3>
                <p>Ko boste začeli uporabljati sistem, se bodo tukaj prikazale vaše zadnje aktivnosti.</p>
            </div>
        </div>
    </main>
</body>
</html>
