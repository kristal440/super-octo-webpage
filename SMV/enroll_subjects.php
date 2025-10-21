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
        .subject-card.selected i {
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
        <div class="button-container">
            <button class="btn-primary" href="Homepage.php" onclick="saveSelection()">Shrani izbiro</button>
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
            // Here you can add code to send the selection to the server
            // For example: window.location.href = 'save_subjects.php?subjects=' + Array.from(selected).join(',');
        }
    </script>
</body>
</html>