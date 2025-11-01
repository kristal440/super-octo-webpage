<?php
session_start();

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_status'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

require_once 'database.php';

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as count FROM subjects");
$total_subjects = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'Teacher'");
$total_teachers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'Student'");
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM materials");
$total_materials = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM assignments");
$total_assignments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM submissions");
$total_submissions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .admin-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .admin-card h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .admin-card p {
            color: #7f8c8d;
            margin-bottom: 1.5rem;
        }
        
        .admin-card .btn-primary {
            width: 100%;
            padding: 0.8rem;
            font-size: 1rem;
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-box h4 {
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }
        
        .stat-box .number {
            font-size: 2.5rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
<header>
    <div class="logo">
        <a href="admin_dashboard.php" class="reload">🎓 Admin Panel</a>
    </div>
    <div class="user-info">
        <span>👤 <?php echo htmlspecialchars($_SESSION['user_email']); ?> (Admin)</span>
        <button onclick="window.location.href='Homepage.php'">Portal</button>
        <button onclick="window.location.href='logout.php'">Odjava</button>
    </div>
</header>

<main>
    <h1>Admin Dashboard</h1>
    <p>Upravljanje sistema šolskega dela na daljavo</p>

    <div class="stats-overview">
        <div class="stat-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <h4>Predmeti</h4>
            <div class="number"><?php echo $total_subjects; ?></div>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <h4>Učitelji</h4>
            <div class="number"><?php echo $total_teachers; ?></div>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <h4>Učenci</h4>
            <div class="number"><?php echo $total_students; ?></div>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <h4>Gradiva</h4>
            <div class="number"><?php echo $total_materials; ?></div>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
            <h4>Naloge</h4>
            <div class="number"><?php echo $total_assignments; ?></div>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">
            <h4>Oddaje</h4>
            <div class="number"><?php echo $total_submissions; ?></div>
        </div>
    </div>

    <h2>Upravljanje</h2>
    <div class="admin-grid">
        <div class="admin-card">
            <h3>📚 Predmeti</h3>
            <p>Dodajanje, urejanje in brisanje predmetov</p>
            <button class="btn-primary" onclick="window.location.href='admin_subjects.php'">
                Upravljaj predmete
            </button>
        </div>

        <div class="admin-card">
            <h3>👨‍🏫 Učitelji</h3>
            <p>Dodajanje, urejanje in brisanje učiteljev</p>
            <button class="btn-primary" onclick="window.location.href='admin_teachers.php'">
                Upravljaj učitelje
            </button>
        </div>

        <div class="admin-card">
            <h3>👨‍🎓 Učenci</h3>
            <p>Dodajanje, urejanje in brisanje učencev</p>
            <button class="btn-primary" onclick="window.location.href='admin_students.php'">
                Upravljaj učence
            </button>
        </div>

        <div class="admin-card">
            <h3>🔗 Dodelitev učiteljev</h3>
            <p>Dodeljevanje učiteljev k predmetom</p>
            <button class="btn-primary" onclick="window.location.href='admin_assign_teachers.php'">
                Dodeli učitelje
            </button>
        </div>

        <div class="admin-card">
            <h3>📝 Vpis učencev</h3>
            <p>Vpis učencev v predmete</p>
            <button class="btn-primary" onclick="window.location.href='admin_assign_students.php'">
                Vpiši učence
            </button>
        </div>

        <div class="admin-card">
            <h3>🗄️ Generiranje podatkov</h3>
            <p>Avtomatično generiranje testnih podatkov</p>
            <button class="btn-primary" onclick="window.location.href='admin_generate_data.php'">
                Generiraj podatke
            </button>
        </div>
    </div>
</main>
</body>
</html>
