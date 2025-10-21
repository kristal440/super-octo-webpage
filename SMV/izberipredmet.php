<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Izberite predmete</title>
    <link rel="stylesheet" href="style (1).css">
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

        <section class="stats">
            <div class="stat-card subject-card" data-subject="Angleščina">
                <i>🇬🇧</i>
                <h3>Angleščina</h3>
            </div>

            <div class="stat-card subject-card" data-subject="Slovenščina">
                <i>📖</i>
                <h3>Slovenščina</h3>
            </div>

            <div class="stat-card subject-card" data-subject="Matematika">
                <i>🧮</i>
                <h3>Matematika</h3>
            </div>

            <div class="stat-card subject-card" data-subject="Sociologija">
                <i>👥</i>
                <h3>Sociologija</h3>
            </div>

            <div class="stat-card subject-card" data-subject="SMV">
                <i>💬</i>
                <h3>SMV</h3>
            </div>

            <div class="stat-card subject-card" data-subject="NUP">
                <i>📘</i>
                <h3>NUP</h3>
            </div>

            <div class="stat-card subject-card" data-subject="NRP">
                <i>📗</i>
                <h3>NRP</h3>
            </div>

            <div class="stat-card subject-card" data-subject="RPR">
                <i>📙</i>
                <h3>RPR</h3>
            </div>

            <div class="stat-card subject-card" data-subject="ŠVZ">
                <i>🏃‍♂️</i>
                <h3>ŠVZ</h3>
            </div>
        </section>

        <div style="text-align:center; margin-top:2rem;">
            <button class="btn-primary" onclick="saveSelection()">Shrani izbiro</button>
        </div>
    </main>

    <script>
        const subjectCards = document.querySelectorAll('.subject-card');
        const selected = new Set();

        subjectCards.forEach(card => {
            card.addEventListener('click', () => {
                const subject = card.dataset.subject;
                if (selected.has(subject)) {
                    selected.delete(subject);
                    card.classList.remove('selected');
                } else {
                    selected.add(subject);
                    card.classList.add('selected');
                }
            });
        });

        function saveSelection() {
            if (selected.size === 0) {
                alert("Izberite vsaj en predmet.");
                return;
            }
            alert("Izbrani predmeti: " + Array.from(selected).join(", "));
        }
    </script>

    <style>
        .subject-card {
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .subject-card i {
            font-size: 2rem;
        }

        .subject-card.selected {
            background-color: #3498db;
            color: white;
            transform: translateY(-5px);
        }

        .subject-card.selected i {
            color: white;
        }

        .subject-card:hover {
            transform: translateY(-5px);
            background-color: #ecf5ff;
        }
    </style>
</body>
</html>


