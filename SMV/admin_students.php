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
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND status = 'Student'");
        $stmt->execute(['id' => $_GET['delete']]);
        $success = "Učenec uspešno izbrisan!";
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
                    $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email, password = :password WHERE id = :id AND status = 'Student'");
                    $stmt->execute(['name' => $name, 'email' => $email, 'password' => $hashed, 'id' => $_POST['id']]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id AND status = 'Student'");
                    $stmt->execute(['name' => $name, 'email' => $email, 'id' => $_POST['id']]);
                }
                $success = "Učenec uspešno posodobljen!";
            } else {
                // INSERT
                if (empty($password)) {
                    $error = "Geslo je obvezno za novega učenca!";
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, status) VALUES (:name, :email, :password, 'Student')");
                    $stmt->execute(['name' => $name, 'email' => $email, 'password' => $hashed]);
                    $success = "Učenec uspešno dodan!";
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

// Get student for editing
$edit_student = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND status = 'Student'");
    $stmt->execute(['id' => $_GET['edit']]);
    $edit_student = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Count total students
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'Student'");
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
$total_pages = ceil($total_students / $per_page);

// Fetch students with subject count
$stmt = $pdo->prepare("
    SELECT u.*, COUNT(DISTINCT us.subject_id) as subject_count 
    FROM users u 
    LEFT JOIN user_subjects us ON u.id = us.user_id 
    WHERE u.status = 'Student' 
    GROUP BY u.id 
    ORDER BY u.name
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upravljanje učencev</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .students-table {
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
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        
        .pagination a, .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #2c3e50;
        }
        
        .pagination a:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .pagination .active {
            background: #3498db;
            color: white;
            border-color: #3498db;
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
    <h1>👨‍🎓 Upravljanje učencev</h1>
    
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
        <h2><?php echo $edit_student ? 'Uredi učenca' : 'Dodaj novega učenca'; ?></h2>
        <form method="POST" action="">
            <?php if ($edit_student): ?>
                <input type="hidden" name="id" value="<?php echo $edit_student['id']; ?>">
            <?php endif; ?>
            
            <div class="input-group">
                <label for="name">Ime in priimek *</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo $edit_student ? htmlspecialchars($edit_student['name']) : ''; ?>">
            </div>
            
            <div class="input-group">
                <label for="email">E-pošta *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo $edit_student ? htmlspecialchars($edit_student['email']) : ''; ?>">
            </div>
            
            <div class="input-group">
                <label for="password">Geslo <?php echo $edit_student ? '(pustite prazno za ohranitev)' : '*'; ?></label>
                <input type="password" id="password" name="password" <?php echo $edit_student ? '' : 'required'; ?>>
            </div>
            
            <button type="submit" class="btn-primary">
                <?php echo $edit_student ? 'Posodobi učenca' : 'Dodaj učenca'; ?>
            </button>
            
            <?php if ($edit_student): ?>
                <button type="button" class="secondary" onclick="window.location.href='admin_students.php'">
                    Prekliči
                </button>
            <?php endif; ?>
        </form>
    </div>

    <div class="students-table">
        <h2>Seznam učencev (<?php echo $total_students; ?>)</h2>
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
                <?php if (empty($students)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #7f8c8d;">
                        Ni učencev. Dodajte prvega učenca zgoraj.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo $student['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td>
                            <?php if ($student['subject_count'] > 0): ?>
                                <span class="badge"><?php echo $student['subject_count']; ?> predmetov</span>
                            <?php else: ?>
                                <span style="color: #95a5a6;">Brez predmetov</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="?edit=<?php echo $student['id']; ?>" class="btn-edit">Uredi</a>
                                <a href="?delete=<?php echo $student['id']; ?>" 
                                   class="btn-delete" 
                                   onclick="return confirm('Ali ste prepričani, da želite izbrisati tega učenca?')">
                                    Izbriši
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>">« Prejšnja</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>">Naslednja »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
