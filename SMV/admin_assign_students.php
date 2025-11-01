<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_status'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

require_once 'database.php';

$error = '';
$success = '';

// Handle bulk enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id']) && isset($_POST['subject_ids'])) {
    try {
        $student_id = $_POST['student_id'];
        $subject_ids = $_POST['subject_ids'];
        
        $pdo->beginTransaction();
        
        // Delete existing enrollments
        $stmt = $pdo->prepare("DELETE FROM user_subjects WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $student_id]);
        
        // Insert new enrollments
        $stmt = $pdo->prepare("INSERT INTO user_subjects (user_id, subject_id) VALUES (:user_id, :subject_id)");
        foreach ($subject_ids as $subject_id) {
            $stmt->execute(['user_id' => $student_id, 'subject_id' => $subject_id]);
        }
        
        $pdo->commit();
        $success = "Vpis učenca v predmete uspešen!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Napaka: " . $e->getMessage();
    }
}

// Get selected student
$selected_student = null;
$enrolled_subjects = [];
if (isset($_GET['student_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND status = 'Student'");
    $stmt->execute(['id' => $_GET['student_id']]);
    $selected_student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_student) {
        // Get enrolled subjects
        $stmt = $pdo->prepare("SELECT subject_id FROM user_subjects WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $selected_student['id']]);
        $enrolled_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Fetch all students
$stmt = $pdo->query("
    SELECT u.id, u.name, u.email, COUNT(us.subject_id) as subject_count
    FROM users u
    LEFT JOIN user_subjects us ON u.id = us.user_id
    WHERE u.status = 'Student'
    GROUP BY u.id
    ORDER BY u.name
    LIMIT 100
");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all subjects
$stmt = $pdo->query("SELECT id, name, description FROM subjects ORDER BY name");
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vpis učencev v predmete</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .students-list, .subjects-selection {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .student-item {
            padding: 1rem;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .student-item:hover {
            border-color: #3498db;
            background: #f8f9fa;
        }
        
        .student-item.selected {
            border-color: #3498db;
            background: #e3f2fd;
        }
        
        .student-item h4 {
            margin: 0 0 0.25rem 0;
            color: #2c3e50;
        }
        
        .student-item p {
            margin: 0;
            font-size: 0.85rem;
            color: #7f8c8d;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: #3498db;
            color: white;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
        
        .subject-checkbox {
            display: block;
            padding: 1rem;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .subject-checkbox:hover {
            border-color: #3498db;
            background: #f8f9fa;
        }
        
        .subject-checkbox input[type="checkbox"] {
            margin-right: 0.75rem;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .subject-checkbox.checked {
            border-color: #27ae60;
            background: #e8f5e9;
        }
        
        .subject-checkbox label {
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .subject-checkbox h4 {
            margin: 0;
            color: #2c3e50;
        }
        
        .subject-checkbox p {
            margin: 0.25rem 0 0 0;
            font-size: 0.85rem;
            color: #7f8c8d;
        }
        
        .no-selection {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }
        
        .no-selection i {
            font-size: 3rem;
            display: block;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 968px) {
            .container-grid {
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
    <h1>📝 Vpis učencev v predmete</h1>
    
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

    <div class="container-grid">
        <div class="students-list">
            <h2>Izberite učenca</h2>
            <p style="color: #7f8c8d; margin-bottom: 1rem;">Kliknite na učenca za urejanje vpisa</p>
            
            <?php if (empty($students)): ?>
                <p style="text-align: center; color: #e74c3c; padding: 2rem;">
                    Ni učencev! Dodajte učence v <a href="admin_students.php">upravljanju učencev</a>.
                </p>
            <?php else: ?>
                <?php foreach ($students as $student): ?>
                    <div class="student-item <?php echo ($selected_student && $selected_student['id'] == $student['id']) ? 'selected' : ''; ?>" 
                         onclick="window.location.href='?student_id=<?php echo $student['id']; ?>'">
                        <h4><?php echo htmlspecialchars($student['name']); ?>
                            <span class="badge"><?php echo $student['subject_count']; ?> predmetov</span>
                        </h4>
                        <p><?php echo htmlspecialchars($student['email']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="subjects-selection">
            <?php if (!$selected_student): ?>
                <div class="no-selection">
                    <i style="font-style: normal;">👈</i>
                    <h3>Izberite učenca</h3>
                    <p>Kliknite na učenca na levi strani za urejanje vpisa v predmete</p>
                </div>
            <?php elseif (empty($subjects)): ?>
                <p style="text-align: center; color: #e74c3c; padding: 2rem;">
                    Ni predmetov! Dodajte predmete v <a href="admin_subjects.php">upravljanju predmetov</a>.
                </p>
            <?php else: ?>
                <h2>Predmeti za: <?php echo htmlspecialchars($selected_student['name']); ?></h2>
                <p style="color: #7f8c8d; margin-bottom: 1.5rem;">Izberite predmete, v katere želite vpisati učenca</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="student_id" value="<?php echo $selected_student['id']; ?>">
                    
                    <?php foreach ($subjects as $subject): ?>
                        <?php $is_enrolled = in_array($subject['id'], $enrolled_subjects); ?>
                        <div class="subject-checkbox <?php echo $is_enrolled ? 'checked' : ''; ?>">
                            <label>
                                <input type="checkbox" 
                                       name="subject_ids[]" 
                                       value="<?php echo $subject['id']; ?>"
                                       <?php echo $is_enrolled ? 'checked' : ''; ?>
                                       onchange="this.closest('.subject-checkbox').classList.toggle('checked', this.checked)">
                                <div>
                                    <h4><?php echo htmlspecialchars($subject['name']); ?></h4>
                                    <?php if ($subject['description']): ?>
                                        <p><?php echo htmlspecialchars($subject['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    
                    <button type="submit" class="btn-primary" style="margin-top: 1.5rem; width: 100%;">
                        Shrani vpis
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
