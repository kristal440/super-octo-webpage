<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'database.php';

$user_id = $_SESSION['user_id'];
$subject_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($subject_id === 0) {
    header('Location: Homepage.php');
    exit;
}

// Verify user is enrolled in this subject
$stmt = $pdo->prepare('
    SELECT COUNT(*) as count 
    FROM user_subjects 
    WHERE user_id = :user_id AND subject_id = :subject_id
');
$stmt->execute(['user_id' => $user_id, 'subject_id' => $subject_id]);
$is_enrolled = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

if (!$is_enrolled) {
    header('Location: Homepage.php');
    exit;
}

// Fetch subject details
$stmt = $pdo->prepare('
    SELECT s.*, u.name as teacher_name 
    FROM subjects s 
    LEFT JOIN users u ON s.teacher_id = u.id 
    WHERE s.id = :id
');
$stmt->execute(['id' => $subject_id]);
$subject = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subject) {
    header('Location: Homepage.php');
    exit;
}

$error = '';
$success = '';

// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['submission_file'])) {
    $assignment_id = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
    $file = $_FILES['submission_file'];
    
    if ($assignment_id === 0) {
        $error = "Neveljavna naloga!";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Napaka pri nalaganju datoteke!";
    } else {
        // Check if user already submitted this assignment
        $stmt = $pdo->prepare('
            SELECT id FROM submissions 
            WHERE assignment_id = :assignment_id AND user_id = :user_id
        ');
        $stmt->execute(['assignment_id' => $assignment_id, 'user_id' => $user_id]);
        $existing_submission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/submissions/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = 'submission_' . $assignment_id . '_' . $user_id . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $unique_filename;
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            try {
                if ($existing_submission) {
                    // Update existing submission
                    $stmt = $pdo->prepare("
                        UPDATE submissions 
                        SET file_path = :file_path, submitted_at = CURRENT_TIMESTAMP, grade = NULL, feedback = NULL
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'file_path' => $file_path,
                        'id' => $existing_submission['id']
                    ]);
                    $success = "Oddaja uspešno posodobljena!";
                } else {
                    // Insert new submission
                    $stmt = $pdo->prepare("
                        INSERT INTO submissions (assignment_id, user_id, file_path) 
                        VALUES (:assignment_id, :user_id, :file_path)
                    ");
                    $stmt->execute([
                        'assignment_id' => $assignment_id,
                        'user_id' => $user_id,
                        'file_path' => $file_path
                    ]);
                    $success = "Naloga uspešno oddana!";
                }
            } catch (PDOException $e) {
                unlink($file_path); // Delete file if DB insert fails
                $error = "Napaka pri shranjevanju: " . $e->getMessage();
            }
        } else {
            $error = "Napaka pri premikanju datoteke!";
        }
    }
}

// Fetch materials for this subject
$stmt = $pdo->prepare('
    SELECT * FROM materials 
    WHERE subject_id = :subject_id 
    ORDER BY uploaded_at DESC
');
$stmt->execute(['subject_id' => $subject_id]);
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch assignments for this subject with user's submissions
$stmt = $pdo->prepare('
    SELECT a.*, 
           s.id as submission_id,
           s.file_path as submission_file,
           s.submitted_at,
           s.grade,
           s.feedback
    FROM assignments a
    LEFT JOIN submissions s ON a.id = s.assignment_id AND s.user_id = :user_id
    WHERE a.subject_id = :subject_id
    ORDER BY a.due_date DESC
');
$stmt->execute(['subject_id' => $subject_id, 'user_id' => $user_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($subject['name']); ?> - Učenčev portal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .subject-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .subject-header h1 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .subject-header p {
            color: #7f8c8d;
            margin: 0.25rem 0;
        }
        
        .section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
        }
        
        .material-item, .assignment-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1.5rem;
            align-items: center;
            padding: 1.5rem;
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .material-item:hover, .assignment-item:hover {
            background: #f8f9fa;
            border-color: #3498db;
        }
        
        .item-icon {
            font-size: 2.5rem;
        }
        
        .item-info h3 {
            margin: 0 0 0.25rem 0;
            color: #2c3e50;
        }
        
        .item-info p {
            margin: 0;
            font-size: 0.85rem;
            color: #7f8c8d;
        }
        
        .btn-download, .btn-submit {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-download {
            background: #3498db;
            color: white;
        }
        
        .btn-download:hover {
            background: #2980b9;
        }
        
        .btn-submit {
            background: #27ae60;
            color: white;
        }
        
        .btn-submit:hover {
            background: #229954;
        }
        
        .submission-status {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
        }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-submitted {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-graded {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-pending {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .grade-display {
            font-size: 1.5rem;
            font-weight: bold;
            color: #27ae60;
        }
        
        .feedback-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
            border-left: 4px solid #3498db;
        }
        
        .feedback-box strong {
            color: #2c3e50;
        }
        
        .feedback-box p {
            margin: 0.5rem 0 0 0;
            color: #555;
        }
        
        .upload-form {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .upload-form.active {
            display: block;
        }
        
        .file-input-wrapper {
            margin-bottom: 1rem;
        }
        
        .file-input-wrapper input[type="file"] {
            width: 100%;
            padding: 0.8rem;
            border: 2px dashed #ddd;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .empty-message {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }
        
        .empty-message i {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }
    </style>
</head>
<body>
<header>
    <div class="logo">
        <a href="Homepage.php">🎓 Šolski sistem na daljavo</a>
    </div>
    <div class="user-info">
        <button onclick="window.location.href='Homepage.php'">Nazaj</button>
        <button onclick="window.location.href='logout.php'">Odjava</button>
    </div>
</header>

<main>
    <?php if (!empty($error)): ?>
        <div style="color: #b00020; background: #ffebee; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div style="color: #2e7d32; background: #e8f5e9; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="subject-header">
        <h1>📚 <?php echo htmlspecialchars($subject['name']); ?></h1>
        <p><strong>Predavatelj:</strong> <?php echo htmlspecialchars($subject['teacher_name'] ?? 'Ni določen'); ?></p>
        <?php if (!empty($subject['description'])): ?>
            <p><?php echo htmlspecialchars($subject['description']); ?></p>
        <?php endif; ?>
    </div>

    <!-- Materials Section -->
    <div class="section">
        <h2>📄 Gradiva</h2>
        
        <?php if (empty($materials)): ?>
            <div class="empty-message">
                <i style="font-style: normal;">📭</i>
                <p>Še ni naloženih gradiv.</p>
            </div>
        <?php else: ?>
            <?php foreach ($materials as $material): ?>
                <?php
                $extension = pathinfo($material['file_path'], PATHINFO_EXTENSION);
                $icon_map = [
                    'pdf' => '📕',
                    'doc' => '📘',
                    'docx' => '📘',
                    'ppt' => '📙',
                    'pptx' => '📙',
                    'xls' => '📗',
                    'xlsx' => '📗',
                    'zip' => '📦'
                ];
                $icon = $icon_map[strtolower($extension)] ?? '📄';
                $file_size = file_exists($material['file_path']) ? filesize($material['file_path']) : 0;
                $file_size_mb = round($file_size / 1024 / 1024, 2);
                ?>
                <div class="material-item">
                    <div class="item-icon"><?php echo $icon; ?></div>
                    <div class="item-info">
                        <h3><?php echo htmlspecialchars($material['title']); ?></h3>
                        <p>
                            Naloženo: <?php echo date('d.m.Y H:i', strtotime($material['uploaded_at'])); ?> | 
                            Velikost: <?php echo $file_size_mb; ?> MB | 
                            Format: <?php echo strtoupper($extension); ?>
                        </p>
                    </div>
                    <a href="<?php echo htmlspecialchars($material['file_path']); ?>" 
                       class="btn-download" 
                       download>
                        ⬇️ Prenesi
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Assignments Section -->
    <div class="section">
        <h2>📝 Naloge</h2>
        
        <?php if (empty($assignments)): ?>
            <div class="empty-message">
                <i style="font-style: normal;">📭</i>
                <p>Še ni nalog.</p>
            </div>
        <?php else: ?>
            <?php foreach ($assignments as $assignment): ?>
                <div class="assignment-item" style="grid-template-columns: auto 1fr; align-items: start;">
                    <div class="item-icon">📋</div>
                    <div style="width: 100%;">
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: start;">
                            <div class="item-info">
                                <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                <?php if (!empty($assignment['description'])): ?>
                                    <p style="margin-top: 0.5rem; color: #555;">
                                        <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                                    </p>
                                <?php endif; ?>
                                <p style="margin-top: 0.5rem;">
                                    📅 Rok oddaje: <?php echo $assignment['due_date'] ? date('d.m.Y', strtotime($assignment['due_date'])) : 'Ni določen'; ?>
                                </p>
                            </div>
                            
                            <div class="submission-status">
                                <?php if ($assignment['submission_id']): ?>
                                    <?php if ($assignment['grade']): ?>
                                        <span class="status-badge status-graded">Ocenjeno</span>
                                        <div class="grade-display"><?php echo htmlspecialchars($assignment['grade']); ?></div>
                                    <?php else: ?>
                                        <span class="status-badge status-submitted">Oddano</span>
                                        <p style="font-size: 0.85rem; margin: 0;">
                                            <?php echo date('d.m.Y H:i', strtotime($assignment['submitted_at'])); ?>
                                        </p>
                                    <?php endif; ?>
                                    <a href="<?php echo htmlspecialchars($assignment['submission_file']); ?>" 
                                       class="btn-download" 
                                       download 
                                       style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">
                                        Prenesi oddajo
                                    </a>
                                    <button class="btn-submit" 
                                            style="font-size: 0.85rem; padding: 0.4rem 0.8rem;"
                                            onclick="toggleUploadForm(<?php echo $assignment['id']; ?>)">
                                        Ponovno oddaj
                                    </button>
                                <?php else: ?>
                                    <span class="status-badge status-pending">Neodano</span>
                                    <button class="btn-submit" 
                                            onclick="toggleUploadForm(<?php echo $assignment['id']; ?>)">
                                        Oddaj nalogo
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($assignment['feedback']): ?>
                            <div class="feedback-box">
                                <strong>Komentar učitelja:</strong>
                                <p><?php echo nl2br(htmlspecialchars($assignment['feedback'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Upload Form -->
                        <form method="POST" 
                              action="" 
                              enctype="multipart/form-data" 
                              class="upload-form" 
                              id="upload-form-<?php echo $assignment['id']; ?>">
                            <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                            <div class="file-input-wrapper">
                                <input type="file" name="submission_file" required>
                            </div>
                            <button type="submit" class="btn-submit">📤 Naloži datoteko</button>
                            <button type="button" 
                                    class="btn-download" 
                                    style="background: #95a5a6; margin-left: 0.5rem;"
                                    onclick="toggleUploadForm(<?php echo $assignment['id']; ?>)">
                                Prekliči
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script>
function toggleUploadForm(assignmentId) {
    const form = document.getElementById('upload-form-' + assignmentId);
    form.classList.toggle('active');
}
</script>
</body>
</html>