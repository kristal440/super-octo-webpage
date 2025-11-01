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

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['material_file'])) {
    $title = trim($_POST['title']);
    $file = $_FILES['material_file'];
    
    if (empty($title)) {
        $error = "Naslov je obvezen!";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Napaka pri nalaganju datoteke!";
    } else {
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/materials/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safe_filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
        $unique_filename = $safe_filename . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $unique_filename;
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO materials (subject_id, title, file_path) VALUES (:subject_id, :title, :file_path)");
                $stmt->execute([
                    'subject_id' => $subject_id,
                    'title' => $title,
                    'file_path' => $file_path
                ]);
                $success = "Gradivo uspešno naloženo!";
            } catch (PDOException $e) {
                unlink($file_path); // Delete file if DB insert fails
                $error = "Napaka pri shranjevanju: " . $e->getMessage();
            }
        } else {
            $error = "Napaka pri premikanju datoteke!";
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    try {
        // Get file path
        $stmt = $pdo->prepare("SELECT file_path FROM materials WHERE id = :id AND subject_id = :subject_id");
        $stmt->execute(['id' => $_GET['delete'], 'subject_id' => $subject_id]);
        $material = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($material) {
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM materials WHERE id = :id AND subject_id = :subject_id");
            $stmt->execute(['id' => $_GET['delete'], 'subject_id' => $subject_id]);
            
            // Delete file
            if (file_exists($material['file_path'])) {
                unlink($material['file_path']);
            }
            
            $success = "Gradivo uspešno izbrisano!";
        }
    } catch (PDOException $e) {
        $error = "Napaka pri brisanju: " . $e->getMessage();
    }
}

// Fetch all materials for this subject
$stmt = $pdo->prepare('SELECT * FROM materials WHERE subject_id = :subject_id ORDER BY uploaded_at DESC');
$stmt->execute(['subject_id' => $subject_id]);
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gradiva - <?php echo htmlspecialchars($subject['name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .upload-box {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .materials-list {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .material-item {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 1.5rem;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #ecf0f1;
            transition: background 0.3s ease;
        }
        
        .material-item:last-child {
            border-bottom: none;
        }
        
        .material-item:hover {
            background: #f8f9fa;
        }
        
        .material-icon {
            font-size: 2.5rem;
        }
        
        .material-info h3 {
            margin: 0 0 0.25rem 0;
            color: #2c3e50;
        }
        
        .material-info p {
            margin: 0;
            font-size: 0.85rem;
            color: #7f8c8d;
        }
        
        .material-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-download, .btn-delete {
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
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c0392b;
        }
        
        .input-group {
            margin-bottom: 1rem;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .input-group input[type="text"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type="file"] {
            width: 100%;
            padding: 0.8rem;
            border: 2px dashed #ddd;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .file-input-wrapper input[type="file"]:hover {
            border-color: #3498db;
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
    <h1>📁 Gradiva: <?php echo htmlspecialchars($subject['name']); ?></h1>
    <p>Nalaganje in upravljanje učnih gradiv</p>
    
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

    <div class="upload-box">
        <h2>📤 Naloži novo gradivo</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="input-group">
                <label for="title">Naslov gradiva *</label>
                <input type="text" id="title" name="title" required placeholder="npr. Uvod v matematiko">
            </div>
            
            <div class="input-group">
                <label for="material_file">Datoteka *</label>
                <div class="file-input-wrapper">
                    <input type="file" id="material_file" name="material_file" required>
                </div>
                <p style="font-size: 0.85rem; color: #7f8c8d; margin-top: 0.5rem;">
                    Podprti formati: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, ZIP
                </p>
            </div>
            
            <button type="submit" class="btn-primary">Naloži gradivo</button>
        </form>
    </div>

    <div class="materials-list">
        <h2>Naložena gradiva (<?php echo count($materials); ?>)</h2>
        
        <?php if (empty($materials)): ?>
            <p style="text-align: center; color: #7f8c8d; padding: 2rem;">
                Še ni naloženih gradiv. Naložite prvo gradivo zgoraj.
            </p>
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
                    <div class="material-icon"><?php echo $icon; ?></div>
                    <div class="material-info">
                        <h3><?php echo htmlspecialchars($material['title']); ?></h3>
                        <p>
                            Naloženo: <?php echo date('d.m.Y H:i', strtotime($material['uploaded_at'])); ?> | 
                            Velikost: <?php echo $file_size_mb; ?> MB | 
                            Format: <?php echo strtoupper($extension); ?>
                        </p>
                    </div>
                    <div class="material-actions">
                        <a href="<?php echo htmlspecialchars($material['file_path']); ?>" 
                           class="btn-download" 
                           download>
                            ⬇️ Prenesi
                        </a>
                        <a href="?subject_id=<?php echo $subject_id; ?>&delete=<?php echo $material['id']; ?>" 
                           class="btn-delete"
                           onclick="return confirm('Ali ste prepričani, da želite izbrisati to gradivo?')">
                            🗑️ Izbriši
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
</body>
</html>