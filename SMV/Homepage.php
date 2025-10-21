[file name]: homepage.php
[file content begin]
<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];
$user_status = $_SESSION['user_status'];

// Get user's name from database
$stmt = $pdo->prepare('SELECT name FROM users WHERE id = :id');
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_name = $user['name'];

// Get statistics for the dashboard
// Total courses count
$stmt_courses = $pdo->prepare('SELECT COUNT(*) as total_courses FROM courses');
$stmt_courses->execute();
$total_courses = $stmt_courses->fetch(PDO::FETCH_ASSOC)['total_courses'];

// User's enrolled courses count
$stmt_my_courses = $pdo->prepare('SELECT COUNT(*) as my_courses FROM enrollments WHERE user_id = :user_id');
$stmt_my_courses->execute(['user_id' => $user_id]);
$my_courses = $stmt_my_courses->fetch(PDO::FETCH_ASSOC)['my_courses'];

// Total assignments count
$stmt_assignments = $pdo->prepare('SELECT COUNT(*) as total_assignments FROM assignments');
$stmt_assignments->execute();
$total_assignments = $stmt_assignments->fetch(PDO::FETCH_ASSOC)['total_assignments'];

// Get user's enrolled courses with details
$stmt_enrolled = $pdo->prepare('
    SELECT c.id, c.name, c.description, c.teacher_name 
    FROM courses c 
    INNER JOIN enrollments e ON c.id = e.course_id 
    WHERE e.user_id = :user_id 
    ORDER BY c.name
');
$stmt_enrolled->execute(['user_id' => $user_id]);
$enrolled_courses = $stmt_enrolled->fetchAll(PDO::FETCH_ASSOC);

// Get available courses (not enrolled yet)
$stmt_available = $pdo->prepare('
    SELECT c.id, c.name, c.description, c.teacher_name 
    FROM courses c 
    WHERE c.id NOT IN (
        SELECT course_id FROM enrollments WHERE user_id = :user_id
    )
    ORDER BY c.name
');
$stmt_available->execute(['user_id' => $user_id]);
$available_courses = $stmt_available->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8" />
    <title>Šolski sistem na daljavo - Domov</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body class="dashboard-page">
    <header>
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span>Šolski sistem na daljavo</span>
        </div>
        <div class="user-info">
            <span>Pozdravljen, <strong><?php echo htmlspecialchars($user_name); ?></strong> (<?php echo htmlspecialchars($user_status); ?>)</span>
            <a href="logout.php" class="btn-primary">Odjava</a>
        </div>
    </header>

    <main>
        <h1>Nadzorna plošča</h1>
        <p>Pregled vaših predmetov in šolskih aktivnosti</p>

        <!-- Statistics Cards -->
        <div class="stats">
            <div class="stat-card">
                <h3>Vsi predmeti</h3>
                <p><?php echo $total_courses; ?></p>
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-card">
                <h3>Moji predmeti</h3>
                <p><?php echo $my_courses; ?></p>
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-card">
                <h3>Naloge</h3>
                <p><?php echo $total_assignments; ?></p>
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-card">
                <h3>Status</h3>
                <p><?php echo htmlspecialchars($user_status); ?></p>
                <i class="fas fa-chart-line"></i>
            </div>
        </div>

        <!-- My Courses Section -->
        <div class="content">
            <h2>Moji predmeti</h2>
            
            <?php if (count($enrolled_courses) > 0): ?>
                <ul class="subject-list">
                    <?php foreach ($enrolled_courses as $course): ?>
                        <li class="subject-item">
                            <h3><?php echo htmlspecialchars($course['name']); ?></h3>
                            <p><?php echo htmlspecialchars($course['description']); ?></p>
                            <p><strong>Učitelj:</strong> <?php echo htmlspecialchars($course['teacher_name']); ?></p>
                            <a href="course.php?id=<?php echo $course['id']; ?>">Pojdi na predmet →</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>Nimate še nobenega predmeta</h3>
                    <p>Pridružite se prvemu predmetu in začnite z učenjem!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Available Courses Section -->
        <?php if (count($available_courses) > 0): ?>
        <div class="content">
            <h2>Razpoložljivi predmeti</h2>
            <ul class="subject-list">
                <?php foreach ($available_courses as $course): ?>
                    <li class="subject-item">
                        <h3><?php echo htmlspecialchars($course['name']); ?></h3>
                        <p><?php echo htmlspecialchars($course['description']); ?></p>
                        <p><strong>Učitelj:</strong> <?php echo htmlspecialchars($course['teacher_name']); ?></p>
                        <form method="POST" action="enroll_course.php" style="display: inline;">
                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                            <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Pridruži se predmetu</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
[file content end]
