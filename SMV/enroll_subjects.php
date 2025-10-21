<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'database.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_subjects'])) {
    try {
        $pdo->beginTransaction();
        
        // Delete existing enrollments for this user
        $stmt_delete = $pdo->prepare('DELETE FROM user_subjects WHERE user_id = :user_id');
        $stmt_delete->execute(['user_id' => $user_id]);
        
        // Insert new enrollments
        $stmt_insert = $pdo->prepare('INSERT INTO user_subjects (user_id, subject_id) VALUES (:user_id, :subject_id)');
        
        foreach ($_POST['selected_subjects'] as $subject_id) {
            $stmt_insert->execute([
                'user_id' => $user_id,
                'subject_id' => intval($subject_id)
            ]);
        }
        
        $pdo->commit();
        $success = 'Predmeti so bili uspešno shranjeni!';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Napaka pri shranjevanju predmetov: ' . $e->getMessage();
    }
}

// Fetch all available subjects
$stmt = $pdo->prepare('
    SELECT s.id, s.name, s.description, u.name as teacher_name 
    FROM subjects s 
    LEFT JOIN users u ON s.teacher_id = u.id 
    ORDER BY s.name
');
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's currently enrolled subjects
$stmt_enrolled = $pdo->prepare('
    SELECT subject_id 
    FROM user_subjects 
    WHERE user_id = :user_id
');
$stmt_enrolled->execute(['user_id' => $user_id]);
$enrolled_subjects = $stmt_enrolled->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Izberite predmete</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Additional styles for subject selection page */
        .subject-card {
            text-align: center;
            cursor: pointer;
            position: relative;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .subject-card i {
            font-size: 3rem;
            font-style: normal;
            display: block;
            margin-bottom: 0.5rem;
            transition: transform 0.3s ease;
        }

        .subject-card h3 {
            font-size: 1.1rem;
            color: #2c3e50;
            margin: 0;
        }

        .subject-card p {
            font-size: 0.8rem;
            color: #7f8c8d;
            margin: 0.3rem 0 0 0;
            text-align: center;
        }

        .subject-card:hover {
            transform: translateY(-5px);
            border-color: #3498db;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
        }

        .subject-card:hover i {
            transform: scale(1.1);
        }

        .subject-card.selected {
            background-color: #3498db;
            border-color: #2980b9;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
        }

        .subject-card.selected h3,
        .subject-card.selected i,
        .subject-card.selected p {
            color: white;
        }

        .button-container {
            text-align: center;
            margin-top: 2rem;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .error-message {
            color: #b00020;
            background: #ffebee;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .success-message {
            color: #2e7d32;
            background: #e8f5e9;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 1rem;
            text-align: center;
        }

        @media (max-width: 768px) {
            .stats {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }
        }
    </style>
</head>
<body class="dashboard-page">
    <header>
        <div class="logo">
            <span>📘</span>
            <span>Izberite predmete</span>
        </div>
        <div class="user-info">
            <button class="secondary" onclick="window.location.href='Homepage.php'">Nazaj</button>
        </div>
    </header>
    <main>
        <h1>Izberite svoje predmete</h1>
        <p>Kliknite na predmete, ki jih želite dodati.</p>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <section class="stats">
                <?php foreach ($subjects as $subject): ?>
                    <?php
                    $isSelected = in_array($subject['id'], $enrolled_subjects);
                    $icon = getSubjectIcon($subject['name']);
                    ?>
                    <div class="stat-card subject-card <?php echo $isSelected ? 'selected' : ''; ?>" 
                         data-subject-id="<?php echo $subject['id']; ?>">
                        <i><?php echo $icon; ?></i>
                        <h3><?php echo htmlspecialchars($subject['name']); ?></h3>
                        <?php if (!empty($subject['teacher_name'])): ?>
                            <p><strong>Učitelj:</strong> <?php echo htmlspecialchars($subject['teacher_name']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($subject['description'])): ?>
                            <p><?php echo htmlspecialchars($subject['description']); ?></p>
                        <?php endif; ?>
                        <input type="checkbox" 
                               name="selected_subjects[]" 
                               value="<?php echo $subject['id']; ?>" 
                               <?php echo $isSelected ? 'checked' : ''; ?> 
                               style="display: none;">
                    </div>
                <?php endforeach; ?>
            </section>
            <div class="button-container">
                <button type="submit" class="btn-primary">Shrani izbiro</button>
            </div>
        </form>
    </main>
    <script>
        const subjectCards = document.querySelectorAll('.subject-card');
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');

        subjectCards.forEach(card => {
            card.addEventListener('click', () => {
                const checkbox = card.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
                
                if (checkbox.checked) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            });
        });

        // Prevent form submission if no subjects selected
        document.querySelector('form').addEventListener('submit', function(e) {
            const selected = document.querySelectorAll('input[type="checkbox"]:checked');
            if (selected.length === 0) {
                e.preventDefault();
                alert("Izberite vsaj en predmet.");
            }
        });
    </script>
</body>
</html>

<?php
// Helper function to get appropriate icon for each subject
function getSubjectIcon($subjectName) {
    $icons = [
        'Angleščina' => '🇬🇧',
        'Slovenščina' => '📖',
        'Matematika' => '🧮',
        'Sociologija' => '👥',
        'SMV' => '💬',
        'NUP' => '📘',
        'NRP' => '📗',
        'RPR' => '📙',
        'ŠVZ' => '🏃‍♂️'
    ];
    
    return $icons[$subjectName] ?? '📚';
}
?>