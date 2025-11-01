<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_status'] !== 'Teacher') {
    header('Location: login.php');
    exit;
}

require_once 'database.php';

$user_id = $_SESSION['user_id'];
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

// Verify teacher owns this subject
$stmt = $pdo->prepare('SELECT * FROM subjects WHERE id = :id AND teacher_id = :teacher_id');
$stmt->execute(['id' => $subject_id, 'teacher_id' => $user_id]);
$subject = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subject) {
    header('Location: teacher_dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle grading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'])) {
    $submission_id = $_POST['submission_id'];
    $grade = trim($_POST['grade']);
    $feedback = trim($_POST['feedback']);
    
    try {
        $stmt = $pdo->prepare("UPDATE submissions SET grade = :grade, feedback = :feedback WHERE id = :id");
        $stmt->execute([
            'grade' => $grade ?: null,
            'feedback' => $feedback ?: null,
            'id' => $submission_id
        ]);
        $success = "Ocena uspešno shranjena!";
    } catch (PDOException $e) {
        $error = "Napaka pri shranjevanju: " . $e->getMessage();
    }
}

// Fetch all assignments for this subject with submissions
$stmt = $pdo->prepare('
    SELECT a.*, 
           COUNT(DISTINCT s.id) as submission_count,
           COUNT(DISTINCT CASE WHEN s.grade IS NULL THEN s.id END) as pending_count
    FROM assignments a
    LEFT JOIN submissions s ON a.id = s.assignment_id
    WHERE a.subject_id = :subject_id
    GROUP BY a.id
    ORDER BY a.due_date DESC
');
$stmt->execute(['subject_id' => $subject_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected assignment
$selected_assignment = null;
$submissions = [];
if (isset($_GET['assignment_id'])) {
    $assignment_id = (int)$_GET['assignment_id'];
    
    // Get assignment details
    $stmt = $pdo->prepare('SELECT * FROM assignments WHERE id = :id AND subject_id = :subject_id');
    $stmt->execute(['id' => $assignment_id, 'subject_id' => $subject_id]);
    $selected_assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_assignment) {
        // Get all submissions for this assignment
        $stmt = $pdo->prepare('
            SELECT s.*, u.name as student_name, u.email as student_email
            FROM submissions s
            INNER JOIN users u ON s.user_id = u.id
            WHERE s.assignment_id = :assignment_id
            ORDER BY s.submitted_at DESC
        ');
        $stmt->execute(['assignment_id' => $assignment_id]);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oddaje - <?php echo htmlspecialchars($subject['name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .assignments-list, .submissions-view {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .assignment-item {
            padding: 1.5rem;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .assignment-item:hover {
            border-color: #3498db;
            background: #f8f9fa;
        }
        
        .assignment-item.selected {
            border-color: #3498db;
            background: #e3f2fd;
        }
        
        .assignment-item h3 {
            margin: 0 0 0.5rem 0;
            color: #2c3e50;
        }
        
        .assignment-item p {
            margin: 0.25rem 0;
            font-size: 0.85rem;
            color: #7f8c8d;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
        
        .badge-pending {
            background: #e74c3c;
            color: white;
        }
        
        .badge-graded {
            background: #27ae60;
            color: white;
        }
        
        .submission-card {
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .student-info h4 {
            margin: 0 0 0.25rem 0;
            color: #2c3e50;
        }
        
        .student-info p {
            margin: 0;
            font-size: 0.85rem;
            color: #7f8c8d;
        }
        
        .submission-status {
            text-align: right;
        }
        
        .grade-form {
            margin-top: 1rem;
        }
        
        .grade-form input, .grade-form textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            margin-bottom: 0.75rem;
        }
        
        .grade-form textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn-download {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .btn-download:hover {
            background: #2980b9;
        }
        
        .no-selection {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
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
        <a href="teacher_dashboard.php">👨‍🏫 Učiteljski portal</a>
    </div>
    <div class="user-info">
        <button onclick="window.location.href='teacher_dashboard.php'">Nazaj</button>
        <button onclick="window.location.href='logout.php'">Odjava</button>
    </div>
</header>

<main>
    <h1>📝 Oddaje: <?php echo htmlspecialchars($subject['name']); ?></h1>
    <p>Pregled in ocenjevanje oddanih nalog</p>
    
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
        <div class="assignments-list">
            <h2>Naloge</h2>
            
            <?php if (empty($assignments)): ?>
                <p style="text-align: center; color: #7f8c8d; padding: 2rem;">
                    Še ni nalog. Administrator mora dodati naloge.
                </p>
            <?php else: ?>
                <?php foreach ($assignments as $assignment): ?>
                    <div class="assignment-item <?php echo ($selected_assignment && $selected_assignment['id'] == $assignment['id']) ? 'selected' : ''; ?>"
                         onclick="window.location.href='?subject_id=<?php echo $subject_id; ?>&assignment_id=<?php echo $assignment['id']; ?>'">
                        <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                        <p>📅 Rok: <?php echo $assignment['due_date'] ? date('d.m.Y', strtotime($assignment['due_date'])) : 'Ni določen'; ?></p>
                        <p>
                            📊 Oddaj: <?php echo $assignment['submission_count']; ?>
                            <?php if ($assignment['pending_count'] > 0): ?>
                                <span class="badge badge-pending"><?php echo $assignment['pending_count']; ?> neocenjenih</span>
                            <?php else: ?>
                                <span class="badge badge-graded">Vse ocenjene</span>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="submissions-view">
            <?php if (!$selected_assignment): ?>
                <div class="no-selection">
                    <i style="font-style: normal; font-size: 3rem; display: block; margin-bottom: 1rem;">👈</i>
                    <h3>Izberite nalogo</h3>
                    <p>Kliknite na nalogo na levi strani za pregled oddaj</p>
                </div>
            <?php else: ?>
                <h2><?php echo htmlspecialchars($selected_assignment['title']); ?></h2>
                <p style="color: #7f8c8d; margin-bottom: 2rem;">
                    <?php echo htmlspecialchars($selected_assignment['description'] ?? 'Brez opisa'); ?>
                </p>
                
                <?php if (empty($submissions)): ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 2rem;">
                        Še ni oddaj za to nalogo.
                    </p>
                <?php else: ?>
                    <?php foreach ($submissions as $submission): ?>
                        <div class="submission-card">
                            <div class="submission-header">
                                <div class="student-info">
                                    <h4>👤 <?php echo htmlspecialchars($submission['student_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($submission['student_email']); ?></p>
                                    <p>📅 Oddano: <?php echo date('d.m.Y H:i', strtotime($submission['submitted_at'])); ?></p>
                                </div>
                                <div class="submission-status">
                                    <?php if ($submission['grade']): ?>
                                        <div style="font-size: 2rem; color: #27ae60; font-weight: bold;">
                                            <?php echo htmlspecialchars($submission['grade']); ?>
                                        </div>
                                        <p style="color: #27ae60; margin: 0;">Ocenjeno</p>
                                    <?php else: ?>
                                        <div style="font-size: 1.5rem; color: #e74c3c;">⏳</div>
                                        <p style="color: #e74c3c; margin: 0;">Neocenjeno</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" 
                                   class="btn-download" 
                                   download>
                                    ⬇️ Prenesi oddajo
                                </a>
                            </div>
                            
                            <?php if ($submission['feedback']): ?>
                                <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                                    <strong>Komentar:</strong>
                                    <p style="margin: 0.5rem 0 0 0;"><?php echo nl2br(htmlspecialchars($submission['feedback'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" class="grade-form">
                                <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                <input type="text" 
                                       name="grade" 
                                       placeholder="Ocena (npr. 5, A, 85%, ...)" 
                                       value="<?php echo htmlspecialchars($submission['grade'] ?? ''); ?>">
                                <textarea name="feedback" 
                                          placeholder="Komentar za učenca (opcijsko)"><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                                <button type="submit" class="btn-primary">Shrani oceno</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>