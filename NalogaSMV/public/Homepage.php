<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Učenčev portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="logo"><a href="Homepage.html"class="reload">🎓 Šolski sistem na daljavo</a></div>
    <div class="user-info">
        <span>👤 Ziga Gracner (Učenec)</span>
        <button onclick="window.location.href='logout.php'">Odjava</button>
    </div>
</header>

<main>
    <h1>Učenčev portal</h1>
    <p>Dostop do gradiv in oddaja nalog</p>

    <div class="stats">
        <div class="stat-card">
            <h3>Moji predmeti</h3>
            <p><strong></strong></p>
            <i class="icon-book"></i>
        </div>
        <div class="stat-card">
            <h3>Dostopna gradiva</h3>
            <p><strong>0</strong></p>
            <i class="icon-file"></i>
        </div>
        <div class="stat-card">
            <h3>Oddane naloge</h3>
            <p><strong>0</strong></p>
            <i class="icon-upload"></i>
        </div>
    </div>

    <section class="content">
        <h2>Moji predmeti</h2>
        <?php if (empty($subjects)): ?>
            <div class="empty-state">
                <i class="icon-book-large"></i>
                <h3>Nimate izbranih predmetov</h3>
                <p>Za dostop do gradiv in oddajo nalog morate izbrati predmete.</p>
                <button class="btn-primary">Izbirate predmete</button>
            </div>
        <?php else: ?>
            <ul class="subject-list">
                <?php foreach ($subjects as $subject): ?>
                    <li class="subject-item">
                        <h3><?php echo htmlspecialchars($subject['name']); ?></h3>
                        <p><strong>Predavatelj:</strong> <?php echo htmlspecialchars($subject['teacher']); ?></p>
                        <p><small><?php echo htmlspecialchars($subject['description']); ?></small></p>
                        <a href="subject.php?id=<?php echo $subject['id']; ?>">Odpri predmet</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>

</body>
</html>