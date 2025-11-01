<?php
session_start();

// Check if user is logged in and is a Teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_status'] !== 'Teacher') {
    header('Location: login.php');
    exit;
}

require_once 'database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? $_SESSION['user_email'];

// Fetch subjects taught by this teacher
$stmt = $pdo->prepare('
    SELECT s.id, s.name, s.description,
           COUNT(DISTINCT us.user_id) as student_count,
           COUNT(DISTINCT m.id) as materials_count,
           COUNT(DISTINCT a.id) as assignments_count
    FROM subjects s
    LEFT JOIN user_subjects us ON s.id = us.subject_id
    LEFT JOIN materials m ON s.id = m.subject_id
    LEFT JOIN assignments a ON s.id = a.subject_id
    WHERE s.teacher_id = :teacher_id
    GROUP BY s.id
    ORDER BY s.name
');
$stmt->execute(['teacher_id' => $user_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total statistics
$total_students = 0;
$total_materials = 0;
$total_assignments = 0;
foreach ($subjects as $subject) {
    $total_students += $subject['student_count'];
    $total_materials += $subject['materials_count'];
    $total_assignments += $subject['assignments_count'];
}

// Count pending submissions (submissions without grade)
$stmt = $pdo->prepare('
    SELECT COUNT(*) as count
    FROM submissions sub
    INNER JOIN assignments a ON sub.assignment_id = a.id
    INNER JOIN subjects s ON a.subject_id = s.id
    WHERE s.teacher_id = :teacher_id AND sub.grade IS NULL
');
$stmt->execute(['teacher_id' => $user_id]);
$pending_submissions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Učiteljski portal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .subject-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .subject-card h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .subject-card p {
            color: #7f8c8d;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .subject-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-item .number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
            display: block;
        }
        
        .stat-item .label {
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .action-buttons button {
            padding: 0.75rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }
        
        .btn-materials {
            background: #3498db;
            color: white;
        }
        
        .btn-materials:hover {
            background: #2980b9;
        }
        
        .btn-submissions {
            background: #9b59b6;
            color: white;
        }
        
        .btn-submissions:hover {
            background: #8e44ad;
        }
        
        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: #7f8c8d;
        }
    </style>
</head>
<body>
<header>
    <div class="logo">
        <a href="teacher_dashboard.php" class="reload">👨‍🏫 Učiteljski portal</a>
    </div>
    <div class="user-info">
        <span>👤 <?php echo htmlspecialchars($user_name); ?> (Učitelj)</span>
        <button onclick="window.location.href='logout.php'">Odjava</button>
    </div>
</header>

<main>
    <h1>Učiteljski portal</h1>
    <p>Upravljanje predmetov, gradiv in ocenjevanje nalog</p>

    <div class="stats">
        <div class="stat-card">
            <h3>Moji predmeti</h3>
            <p><strong><?php echo count($subjects); ?></strong></p>
            <i class="icon-book"></i>
        </div>
        <div class="stat-card">
            <h3>Skupaj študentov</h3>
            <p><strong><?php echo $total_students; ?></strong></p>
            <i class="icon-users"></i>
        </div>
        <div class="stat-card">
            <h3>Naložena gradiva</h3>
            <p><strong><?php echo $total_materials; ?></strong></p>
            <i class="icon-file"></i>
        </div>
        <div class="stat-card">
            <h3>Neocenjene oddaje</h3>
            <p><strong><?php echo $pending_submissions; ?></strong></p>
            <i class="icon-clock"></i>
        </div>
    </div>

    <section class="content">
        <h2>Moji predmeti</h2>
        
        <?php if (empty($subjects)): ?>
            <div class="empty-state">
                <i style="font-style: normal;">📚</i>
                <h3>Nimate dodeljenih predmetov</h3>
                <p>Kontaktirajte administratorja za dodelitev predmetov.</p>
            </div>
        <?php else: ?>
            <div class="subjects-grid">
                <?php foreach ($subjects as $subject): ?>
                    <div class="subject-card">
                        <h3><?php echo htmlspecialchars($subject['name']); ?></h3>
                        <p><?php echo htmlspecialchars($subject['description'] ?? 'Brez opisa'); ?></p>
                        
                        <div class="subject-stats">
                            <div class="stat-item">
                                <span class="number"><?php echo $subject['student_count']; ?></span>
                                <span class="label">Študentov</span>
                            </div>
                            <div class="stat-item">
                                <span class="number"><?php echo $subject['materials_count']; ?></span>
                                <span class="label">Gradiva</span>
                            </div>
                            <div class="stat-item">
                                <span class="number"><?php echo $subject['assignments_count']; ?></span>
                                <span class="label">Naloge</span>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="btn-materials" onclick="window.location.href='teacher_materials.php?subject_id=<?php echo $subject['id']; ?>'">
                                📁 Gradiva
                            </button>
                            <button class="btn-submissions" onclick="window.location.href='teacher_submissions.php?subject_id=<?php echo $subject['id']; ?>'">
                                📝 Oddaje
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>