<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_status'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

require_once 'database.php';

$error = '';
$success = '';

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject_id']) && isset($_POST['teacher_id'])) {
    try {
        $subject_id = $_POST['subject_id'];
        $teacher_id = $_POST['teacher_id'] === '' ? null : $_POST['teacher_id'];
        
        $stmt = $pdo->prepare("UPDATE subjects SET teacher_id = :teacher_id WHERE id = :subject_id");
        $stmt->execute(['teacher_id' => $teacher_id, 'subject_id' => $subject_id]);
        
        $success = "Učitelj uspešno dodeljen predmetu!";
    } catch (PDOException $e) {
        $error = "Napaka: " . $e->getMessage();
    }
}

// Fetch all subjects with their assigned teachers
$stmt = $pdo->query("
    SELECT s.*, u.name as teacher_name, u.email as teacher_email
    FROM subjects s
    LEFT JOIN users u ON s.teacher_id = u.id
    ORDER BY s.name
");
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all teachers
$stmt = $pdo->query("SELECT id, name, email FROM users WHERE status = 'Teacher' ORDER BY name");
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as count FROM subjects WHERE teacher_id IS NOT NULL");
$assigned_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM subjects WHERE teacher_id IS NULL");
$unassigned_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodelitev učiteljev</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-box {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-box h3 {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-box .number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .assignments-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .assignment-item {
            display: grid;
            grid-template-columns: 1fr 2fr auto;
            gap: 1.5rem;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .assignment-item:last-child {
            border-bottom: none;
        }
        
        .subject-info h3 {
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .subject-info p {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .teacher-select {
            position: relative;
        }
        
        .teacher-select select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
        }
        
        .teacher-select select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn-assign {
            padding: 0.8rem 1.5rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.95rem;
            white-space: nowrap;
        }
        
        .btn-assign:hover {
            background: #2980b9;
        }
        
        .current-teacher {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        
        .no-teacher {
            color: #e74c3c;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .assignment-item {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<header>
    <div class="logo">
        <a href="admin_dashboard.php">🎓 Admin Panel</a>
    </div>
    <div class="user-info">
        <button onclick="window.location.href='admin_dashboard.php'">Nazaj</button>
        <button onclick="window.location.href='logout.php'">Odjava</button>
    </div>
</header>

<main>
    <h1>🔗 Dodelitev učiteljev predmetom</h1>
    
    <?php if (!empty($error)): ?>
        <div class="error-message" style="color: #b00020; background: #ffebee; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="success-message" style="color: #2e7d32; background: #e8f5e9; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="stats-row">
        <div class="stat-box">
            <h3>Skupaj predmetov</h3>
            <div class="number"><?php echo count($subjects); ?></div>
        </div>
        <div class="stat-box">
            <h3>Dodeljeni</h3>
            <div class="number" style="color: #27ae60;"><?php echo $assigned_count; ?></div>
        </div>
        <div class="stat-box">
            <h3>Nedodeljeni</h3>
            <div class="number" style="color: #e74c3c;"><?php echo $unassigned_count; ?></div>
        </div>
        <div class="stat-box">
            <h3>Učiteljev</h3>
            <div class="number" style="color: #3498db;"><?php echo count($teachers); ?></div>
        </div>
    </div>

    <div class="assignments-container">
        <h2>Dodeljevanje učiteljev</h2>
        
        <?php if (empty($teachers)): ?>
            <p style="text-align: center; color: #e74c3c; padding: 2rem;">
                Ni učiteljev! Najprej dodajte učitelje v <a href="admin_teachers.php">upravljanju učiteljev</a>.
            </p>
        <?php elseif (empty($subjects)): ?>
            <p style="text-align: center; color: #e74c3c; padding: 2rem;">
                Ni predmetov! Najprej dodajte predmete v <a href="admin_subjects.php">upravljanju predmetov</a>.
            </p>
        <?php else: ?>
            <?php foreach ($subjects as $subject): ?>
            <form method="POST" action="">
                <div class="assignment-item">
                    <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                    
                    <div class="subject-info">
                        <h3><?php echo htmlspecialchars($subject['name']); ?></h3>
                        <?php if ($subject['teacher_name']): ?>
                            <p class="current-teacher">
                                📌 Trenutno: <?php echo htmlspecialchars($subject['teacher_name']); ?>
                            </p>
                        <?php else: ?>
                            <p class="no-teacher">Brez učitelja</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="teacher-select">
                        <select name="teacher_id">
                            <option value="">-- Brez učitelja --</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>" 
                                        <?php echo ($subject['teacher_id'] == $teacher['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($teacher['name']); ?> 
                                    (<?php echo htmlspecialchars($teacher['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-assign">Dodeli</button>
                </div>
            </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
