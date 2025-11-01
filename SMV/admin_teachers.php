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
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND status = 'Teacher'");
        $stmt->execute(['id' => $_GET['delete']]);
        $success = "Učitelj uspešno izbrisan!";
    } catch (PDOException $e) {
        $error = "Napaka pri brisanju: " . $e->getMessage();
    }
}

// Handle ADD/EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($name) || empty($email)) {
        $error = "Ime in e-pošta sta obvezna!";
    } else {
        try {
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // UPDATE
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email, password = :password WHERE id = :id AND status = 'Teacher'");
                    $stmt->execute(['name' => $name, 'email' => $email, 'password' => $hashed, 'id' => $_POST['id']]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id AND status = 'Teacher'");
                    $stmt->execute(['name' => $name, 'email' => $email, 'id' => $_POST['id']]);
                }
                $success = "Učitelj uspešno posodobljen!";
            } else {
                // INSERT
                if (empty($password)) {
                    $error = "Geslo je obvezno za novega učitelja!";
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, status) VALUES (:name, :email, :password, 'Teacher')");
                    $stmt->execute(['name' => $name, 'email' => $email, 'password' => $hashed]);
                    $success = "Učitelj uspešno dodan!";
                }
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'unique') !== false) {
                $error = "Ta e-poštni naslov je že uporabljen!";
            } else {
                $error = "Napaka: " . $e->getMessage();
            }
        }
    }
}

// Get teacher for editing
$edit_teacher = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND status = 'Teacher'");
    $stmt->execute(['id' => $_GET['edit']]);
    $edit_teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all teachers with subject count
$stmt = $pdo->query("
    SELECT u.*, COUNT(DISTINCT s.id) as subject_count 
    FROM users u 
    LEFT JOIN subjects s ON u.id = s.teacher_id 
    WHERE u.status = 'Teacher' 
    GROUP BY u.id 
    ORDER BY u.name
");
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upravljanje učiteljev</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .teachers-table {
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
        
        .input-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #3498db;
            color: white;
            border-radius: 12px;
            font-size: 0.85rem;
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
    <h1>👨‍🏫 Upravljanje učiteljev</h1>
    
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
        <h2><?php echo $edit_teacher ? 'Uredi učitelja' : 'Dodaj novega učitelja'; ?></h2>
        <form method="POST" action="">
            <?php if ($edit_teacher): ?>
                <input type="hidden" name="id" value="<?php echo $edit_teacher['id']; ?>">
            <?php endif; ?>
            
            <div class="input-group">
                <label for="name">Ime in priimek *</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo $edit_teacher ? htmlspecialchars($edit_teacher['name']) : ''; ?>">
            </div>
            
            <div class="input-group">
                <label for="email">E-pošta *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo $edit_teacher ? htmlspecialchars($edit_teacher['email']) : ''; ?>">
            </div>
            
            <div class="input-group">
                <label for="password">Geslo <?php echo $edit_teacher ? '(pustite prazno za ohranitev)' : '*'; ?></label>
                <input type="password" id="password" name="password" <?php echo $edit_teacher ? '' : 'required'; ?>>
            </div>
            
            <button type="submit" class="btn-primary">
                <?php echo $edit_teacher ? 'Posodobi učitelja' : 'Dodaj učitelja'; ?>
            </button>
            
            <?php if ($edit_teacher): ?>
                <button type="button" class="secondary" onclick="window.location.href='admin_teachers.php'">
                    Prekliči
                </button>
            <?php endif; ?>
        </form>
    </div>

    <div class="teachers-table">
        <h2>Seznam učiteljev (<?php echo count($teachers); ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ime</th>
                    <th>E-pošta</th>
                    <th>Število predmetov</th>
                    <th>Dejanja</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($teachers)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #7f8c8d;">
                        Ni učiteljev. Dodajte prvega učitelja zgoraj.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($teachers as $teacher): ?>
                    <tr>
                        <td><?php echo $teacher['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($teacher['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                        <td>
                            <?php if ($teacher['subject_count'] > 0): ?>
                                <span class="badge"><?php echo $teacher['subject_count']; ?> predmetov</span>
                            <?php else: ?>
                                <span style="color: #95a5a6;">Brez predmetov</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="?edit=<?php echo $teacher['id']; ?>" class="btn-edit">Uredi</a>
                                <a href="?delete=<?php echo $teacher['id']; ?>" 
                                   class="btn-delete" 
                                   onclick="return confirm('Ali ste prepričani, da želite izbrisati tega učitelja?')">
                                    Izbriši
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
