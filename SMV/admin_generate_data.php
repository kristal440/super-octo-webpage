<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_status'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

require_once 'database.php';

$error = '';
$success = '';
$log = [];

// Handle generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    try {
        $pdo->beginTransaction();
        
        // Slovenian names for realistic data
        $first_names = ['Luka', 'Maja', 'Nik', 'Eva', 'Jan', 'Ana', 'Mark', 'Sara', 'Tim', 'Nina', 
                        'Žan', 'Ema', 'Matej', 'Teja', 'Gal', 'Lara', 'Jakob', 'Zala', 'Vid', 'Nika'];
        $last_names = ['Novak', 'Horvat', 'Kovač', 'Kralj', 'Mlakar', 'Potočnik', 'Golob', 'Zupan',
                       'Hribar', 'Kozina', 'Kos', 'Bizjak', 'Korošec', 'Vidmar', 'Lesjak', 'Ferlan'];
        
        // Generate additional subject if needed (to reach 10)
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM subjects");
        $subject_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($subject_count < 10) {
            $new_subjects = [
                ['Geografija', 'Študij geografskih značilnosti in prostorov'],
                ['Zgodovina', 'Preučevanje zgodovinskih dogodkov'],
                ['Biologija', 'Študij živih organizmov'],
                ['Kemija', 'Preučevanje snovi in njihovih reakcij'],
                ['Fizika', 'Študij narave in materije']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO subjects (name, description) VALUES (:name, :description)");
            $added = 0;
            foreach ($new_subjects as $subj) {
                if ($subject_count + $added >= 10) break;
                try {
                    $stmt->execute(['name' => $subj[0], 'description' => $subj[1]]);
                    $added++;
                    $log[] = "✓ Dodan predmet: {$subj[0]}";
                } catch (PDOException $e) {
                    // Skip if already exists
                }
            }
            $subject_count += $added;
        }
        
        // Generate 20+ teachers
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'Teacher'");
        $teacher_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $teachers_to_add = max(0, 20 - $teacher_count);
        
        if ($teachers_to_add > 0) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, status) VALUES (:name, :email, :password, 'Teacher')");
            $added = 0;
            
            for ($i = 1; $i <= $teachers_to_add + 5; $i++) {
                if ($added >= $teachers_to_add) break;
                
                $first = $first_names[array_rand($first_names)];
                $last = $last_names[array_rand($last_names)];
                $name = "$first $last";
                $email = strtolower($first . '.' . $last . $i . '@teacher.com');
                $password = password_hash('teacher123', PASSWORD_DEFAULT);
                
                try {
                    $stmt->execute(['name' => $name, 'email' => $email, 'password' => $password]);
                    $added++;
                    $log[] = "✓ Dodan učitelj: $name ($email)";
                } catch (PDOException $e) {
                    // Skip duplicates
                }
            }
        }
        
        // Generate 100+ students
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'Student'");
        $student_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $students_to_add = max(0, 100 - $student_count);
        
        if ($students_to_add > 0) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, status) VALUES (:name, :email, :password, 'Student')");
            $added = 0;
            
            for ($i = 1; $i <= $students_to_add + 20; $i++) {
                if ($added >= $students_to_add) break;
                
                $first = $first_names[array_rand($first_names)];
                $last = $last_names[array_rand($last_names)];
                $name = "$first $last";
                $email = strtolower($first . '.' . $last . $i . '@student.com');
                $password = password_hash('student123', PASSWORD_DEFAULT);
                
                try {
                    $stmt->execute(['name' => $name, 'email' => $email, 'password' => $password]);
                    $added++;
                    if ($added % 20 == 0) {
                        $log[] = "✓ Dodanih $added učencev...";
                    }
                } catch (PDOException $e) {
                    // Skip duplicates
                }
            }
            $log[] = "✓ Skupaj dodanih učencev: $added";
        }
        
        // Assign teachers to subjects (multiple teachers can teach same subject)
        $stmt = $pdo->query("SELECT id FROM subjects");
        $all_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $stmt = $pdo->query("SELECT id FROM users WHERE status = 'Teacher'");
        $all_teachers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($all_teachers) && !empty($all_subjects)) {
            // Assign primary teacher to each subject
            $stmt = $pdo->prepare("UPDATE subjects SET teacher_id = :teacher_id WHERE id = :subject_id");
            foreach ($all_subjects as $subject_id) {
                $teacher_id = $all_teachers[array_rand($all_teachers)];
                $stmt->execute(['teacher_id' => $teacher_id, 'subject_id' => $subject_id]);
            }
            $log[] = "✓ Učitelji dodeljeni predmetom";
        }
        
        // Enroll all students in multiple subjects (2-6 subjects each)
        $stmt = $pdo->query("SELECT id FROM users WHERE status = 'Student'");
        $all_students = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($all_students) && !empty($all_subjects)) {
            $stmt_check = $pdo->prepare("SELECT COUNT(*) as count FROM user_subjects WHERE user_id = :user_id AND subject_id = :subject_id");
            $stmt_insert = $pdo->prepare("INSERT INTO user_subjects (user_id, subject_id) VALUES (:user_id, :subject_id)");
            
            $enrolled_count = 0;
            foreach ($all_students as $student_id) {
                // Each student gets 2-6 random subjects
                $num_subjects = rand(2, 6);
                $student_subjects = array_rand(array_flip($all_subjects), min($num_subjects, count($all_subjects)));
                if (!is_array($student_subjects)) $student_subjects = [$student_subjects];
                
                foreach ($student_subjects as $subject_id) {
                    try {
                        // Check if already enrolled
                        $stmt_check->execute(['user_id' => $student_id, 'subject_id' => $subject_id]);
                        $exists = $stmt_check->fetch(PDO::FETCH_ASSOC)['count'];
                        
                        if ($exists == 0) {
                            $stmt_insert->execute(['user_id' => $student_id, 'subject_id' => $subject_id]);
                            $enrolled_count++;
                        }
                    } catch (PDOException $e) {
                        // Skip duplicates
                    }
                }
            }
            $log[] = "✓ Učenci vpisani v predmete ($enrolled_count vpisov)";
        }
        
        $pdo->commit();
        $success = "Podatki uspešno generirani!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Napaka pri generiranju: " . $e->getMessage();
    }
}

// Get current statistics
$stmt = $pdo->query("SELECT COUNT(*) as count FROM subjects");
$current_subjects = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'Teacher'");
$current_teachers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'Student'");
$current_students = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM user_subjects");
$current_enrollments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generiranje podatkov</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-card .status {
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }
        
        .status.ok {
            color: #27ae60;
        }
        
        .status.warning {
            color: #f39c12;
        }
        
        .status.error {
            color: #e74c3c;
        }
        
        .info-box {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .info-box h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .info-box ul {
            list-style: none;
            padding: 0;
        }
        
        .info-box li {
            padding: 0.5rem 0;
            color: #555;
        }
        
        .info-box li::before {
            content: "✓ ";
            color: #27ae60;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
        .generate-box {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .log-box {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1rem;
            max-height: 400px;
            overflow-y: auto;
            text-align: left;
        }
        
        .log-box p {
            margin: 0.25rem 0;
            font-family: monospace;
            font-size: 0.9rem;
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
    <h1>🗄️ Generiranje testnih podatkov</h1>
    
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

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Predmeti</h3>
            <div class="number"><?php echo $current_subjects; ?></div>
            <div class="status <?php echo $current_subjects >= 10 ? 'ok' : 'error'; ?>">
                Zahteva: 10+ (<?php echo $current_subjects >= 10 ? '✓' : '✗'; ?>)
            </div>
        </div>
        
        <div class="stat-card">
            <h3>Učitelji</h3>
            <div class="number"><?php echo $current_teachers; ?></div>
            <div class="status <?php echo $current_teachers >= 20 ? 'ok' : 'error'; ?>">
                Zahteva: 20+ (<?php echo $current_teachers >= 20 ? '✓' : '✗'; ?>)
            </div>
        </div>
        
        <div class="stat-card">
            <h3>Učenci</h3>
            <div class="number"><?php echo $current_students; ?></div>
            <div class="status <?php echo $current_students >= 100 ? 'ok' : 'error'; ?>">
                Zahteva: 100+ (<?php echo $current_students >= 100 ? '✓' : '✗'; ?>)
            </div>
        </div>
        
        <div class="stat-card">
            <h3>Vpisi</h3>
            <div class="number"><?php echo $current_enrollments; ?></div>
            <div class="status ok">
                Število vpisov učencev
            </div>
        </div>
    </div>

    <div class="info-box">
        <h2>Kaj bo generirano?</h2>
        <ul>
            <li>Predmeti: Dopolnjeno do vsaj 10 predmetov</li>
            <li>Učitelji: Generiranih bo vsaj 20 učiteljev z realnimi slovenskimi imeni</li>
            <li>Učenci: Generiranih bo vsaj 100 učencev z realnimi slovenskimi imeni</li>
            <li>Učitelji bodo samodejno dodeljeni predmetom (lahko več učiteljev na predmet)</li>
            <li>Vsi učenci bodo vpisani v 2-6 naključnih predmetov</li>
            <li>Prijavni podatki: učitelji (teacher123), učenci (student123)</li>
        </ul>
    </div>

    <div class="generate-box">
        <h2>Generiranje podatkov</h2>
        <p style="color: #7f8c8d; margin-bottom: 1.5rem;">
            Kliknite spodnji gumb za samodejno generiranje manjkajočih podatkov.
            Obstoječi podatki bodo ohranjeni.
        </p>
        
        <form method="POST" action="">
            <button type="submit" name="generate" class="btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">
                🚀 Generiraj podatke
            </button>
        </form>
        
        <?php if (!empty($log)): ?>
        <div class="log-box">
            <h3 style="margin-top: 0;">Dnevnik generiranja:</h3>
            <?php foreach ($log as $entry): ?>
                <p><?php echo htmlspecialchars($entry); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
