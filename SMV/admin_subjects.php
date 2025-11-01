<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_status'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

require_once 'database.php';

$error = '';
$success = '';

// Handle DELETE
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = :id");
        $stmt->execute(['id' => $_GET['delete']]);
        $success = "Predmet uspešno izbrisan!";
    } catch (PDOException $e) {
        $error = "Napaka pri brisanju: " . $e->getMessage();
    }
}

// Handle ADD/EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $access_key = trim($_POST['access_key']);
    
    if (empty($name)) {
        $error = "Ime predmeta je obvezno!";
    } else {
        try {
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // UPDATE
                $stmt = $pdo->prepare("UPDATE subjects SET name = :name, description = :description, access_key = :access_key WHERE id = :id");
                $stmt->execute([
                    'name' => $name,
                    'description' => $description,
                    'access_key' => $access_key ?: null,
                    'id' => $_POST['id']
                ]);
                $success = "Predmet uspešno posodobljen!";
            } else {
                // INSERT
                $stmt = $pdo->prepare("INSERT INTO subjects (name, description, access_key) VALUES (:name, :description, :access_key)");
                $stmt->execute([
                    'name' => $name,
                    'description' => $description,
                    'access_key' => $access_key ?: null
                ]);
                $success = "Predmet uspešno dodan!";
            }
        } catch (PDOException $e) {
            $error = "Napaka: " . $e->getMessage();
        }
    }
}

// Get subject for editing
$edit_subject = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $edit_subject = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all subjects
$stmt = $pdo->query("SELECT s.*, u.name as teacher_name FROM subjects s LEFT JOIN users u ON s.teacher_id = u.id ORDER BY s.name");
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upravljanje predmetov</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .subjects-table {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-edit, .btn-delete {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
        }
        
        .btn-edit:hover {
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
        
        .input-group input, .input-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .input-group textarea {
            resize: vertical;
            min-height: 100px;
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
    <h1>📚 Upravljanje predmetov</h1>
    
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

    <div class="form-container">
        <h2><?php echo $edit_subject ? 'Uredi predmet' : 'Dodaj nov predmet'; ?></h2>
        <form method="POST" action="">
            <?php if ($edit_subject): ?>
                <input type="hidden" name="id" value="<?php echo $edit_subject['id']; ?>">
            <?php endif; ?>
            
            <div class="input-group">
                <label for="name">Ime predmeta *</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo $edit_subject ? htmlspecialchars($edit_subject['name']) : ''; ?>">
            </div>
            
            <div class="input-group">
                <label for="description">Opis</label>
                <textarea id="description" name="description"><?php echo $edit_subject ? htmlspecialchars($edit_subject['description']) : ''; ?></textarea>
            </div>
            
            <div class="input-group">
                <label for="access_key">Prijavna koda (6 znakov)</label>
                <input type="text" id="access_key" name="access_key" maxlength="6" 
                       value="<?php echo $edit_subject ? htmlspecialchars($edit_subject['access_key']) : ''; ?>"
                       placeholder="Opcijsko">
            </div>
            
            <button type="submit" class="btn-primary">
                <?php echo $edit_subject ? 'Posodobi predmet' : 'Dodaj predmet'; ?>
            </button>
            
            <?php if ($edit_subject): ?>
                <button type="button" class="secondary" onclick="window.location.href='admin_subjects.php'">
                    Prekliči
                </button>
            <?php endif; ?>
        </form>
    </div>

    <div class="subjects-table">
        <h2>Seznam predmetov (<?php echo count($subjects); ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ime</th>
                    <th>Opis</th>
                    <th>Učitelj</th>
                    <th>Prijavna koda</th>
                    <th>Dejanja</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $subject): ?>
                <tr>
                    <td><?php echo $subject['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($subject['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars(substr($subject['description'], 0, 50)) . (strlen($subject['description']) > 50 ? '...' : ''); ?></td>
                    <td><?php echo htmlspecialchars($subject['teacher_name'] ?? 'Ni dodeljen'); ?></td>
                    <td><?php echo htmlspecialchars($subject['access_key'] ?? '-'); ?></td>
                    <td>
                        <div class="actions">
                            <a href="?edit=<?php echo $subject['id']; ?>" class="btn-edit">Uredi</a>
                            <a href="?delete=<?php echo $subject['id']; ?>" 
                               class="btn-delete" 
                               onclick="return confirm('Ali ste prepričani, da želite izbrisati ta predmet?')">
                                Izbriši
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
