<?php
require_once __DIR__ . '/../app/models/Database.php';

$pdo = null;
try {
    $pdo = getPDOConnection();
} catch (Exception $e) {
    $error = "Impossible de se connecter à la base de données: " . $e->getMessage();
}

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = trim($_POST['type'] ?? '');
    $nom_compagnie = trim($_POST['nom_compagnie'] ?? '');
    $numero = trim($_POST['numero'] ?? '');

    // Basic validation
    if ($type === '' || !in_array($type, ['Train','Bus','Avion'])) $errors[] = 'Veuillez choisir un type valide.';
    if ($nom_compagnie === '') $errors[] = 'Le nom de la compagnie est requis.';
    if ($numero === '') $errors[] = 'Le numéro (identifiant) est requis.';

    if (empty($errors) && $pdo) {
        try {
            // Insert
            $sql = "INSERT INTO moyen_transport (type, nom_compagnie, numero) VALUES (:type, :nom_compagnie, :numero)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':nom_compagnie', $nom_compagnie);
            $stmt->bindParam(':numero', $numero);
            $stmt->execute();
            $success = 'Moyen de transport ajouté avec succès.';
        } catch (PDOException $e) {
            // Duplicate numero handling (unique)
            if ($e->getCode() === '23000') {
                $errors[] = 'Un moyen de transport avec ce numéro existe déjà.';
            } else {
                $errors[] = 'Erreur lors de l\'enregistrement : ' . $e->getMessage();
            }
        }
    }
}

// Fetch current transports for display
$transports = [];
if ($pdo) {
    $stmt = $pdo->query('SELECT id_transport, type, nom_compagnie, numero FROM moyen_transport ORDER BY type, nom_compagnie');
    $transports = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!doctype html>
<html lang="fr" data-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title> Ajouter un moyen de transport | Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/public/css/style.css">
    <style>
        :root {
            --font-family: 'Poppins', sans-serif;
        }

        body {
            font-family: var(--font-family);
            background: #0f172a;
            color: #e2e8f0;
            margin: 0;
            padding: 40px;
        }

        .wrap {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 24px;
            align-items: start;
        }

        .card {
            background: rgba(255, 255, 255, 0.03);
            padding: 22px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        header.site {
            grid-column: 1 / -1;
        }

        header.site h1 {
            margin: 0;
            font-size: 20px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        input[type=text]{
            width: 96% !important;
        }


        input[type=text],
        select {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(255, 255, 255, 0.02);
            color: inherit;
        }

        .btn {
            background: linear-gradient(90deg, #7c3aed, #06b6d4);
            color: #fff;
            padding: 12px 16px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
        }

        .error {
            background: #4c1f1f;
            color: #ffdede;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .success {
            background: #10372d;
            color: #c8f7df;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .list-transport {
            margin-top: 10px;
        }

        .transport-item {
            padding: 10px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.02);
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 8px;
        }

        .small {
            font-size: 13px;
            color: #94a3b8;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255,255,255,0.03);
            color: #cbd5e1;
            font-size: 13px;
            border: 1px solid rgba(255,255,255,0.04);
        }

        /* Light theme overrides */
        html[data-theme="light"] body {
            background: #f8fafc;
            color: #0f172a;
        }

        html[data-theme="light"] .card {
            background: #ffffff;
            border-color: rgba(15, 23, 42, 0.04);
            color: inherit;
        }

        html[data-theme="light"] input[type=text],
        html[data-theme="light"] select {
            background: #ffffff;
            color: inherit;
            border: 1px solid rgba(15,23,42,0.06);
        }

        html[data-theme="light"] .badge {
            background: rgba(15,23,42,0.04);
            color: #0f172a;
            border: 1px solid rgba(15,23,42,0.06);
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/inc/sidebar.php'; ?>
    <div class="wrap">
        <header class="site">
            <div class="card" style="display:flex;justify-content:space-between;align-items:center">
                <div>
                    <h1><i class="fas fa-bus" style="color:#7c3aed;"></i> Ajouter un moyen de transport</h1>
                    <div class="small">Créer un nouveau type/appareil de transport (Bus, Train, Avion etc...).</div>
                </div>
                <div style="display:flex;gap:10px;align-items:center">
                    <div style="display:flex;gap:8px;align-items:center">
                        <span class="badge badge--table" title="Table: moyen_transport">
                            <i class="fas fa-table" aria-hidden="true"></i>
                            <span class="badge__meta"><span class="badge__label">Table</span>
                                <strong class="badge__value">moyen_transport</strong>
                            </span>
                        </span>

                        <span class="badge badge--db" title="DB: <?php echo htmlentities(DB_NAME); ?>">
                            <i class="fas fa-database" aria-hidden="true"></i>
                            <span class="badge__meta"><span class="badge__label">DB</span>
                                <strong class="badge__value"><?php echo htmlentities(DB_NAME); ?></strong>
                            </span>
                        </span>
                    </div>
                    <button id="theme-toggle" title="Changer de thème"
                        style="width:44px;height:44px;border-radius:999px;border:1px solid rgba(255,255,255,0.06);background:transparent;color:inherit"><i
                            class="fas fa-moon"></i></button>
                </div>
            </div>
        </header>

        <main class="card">
            <?php if (!empty($error)): ?>
            <div class="error">
                <?php echo htmlentities($error); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="error">
                <strong>Erreurs :</strong>
                <ul>
                    <?php foreach ($errors as $e): ?>
                    <li>
                        <?php echo htmlentities($e); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="success">
                <?php echo htmlentities($success); ?>
            </div>
            <?php endif; ?>

            <form method="post" id="addTransportForm">
                <div style="display:grid;gap:14px;">
                    <div>
                        <label for="type">Type</label>
                        <select id="type" name="type" required>
                            <option value="">— Choisir un type —</option>
                            <option value="Train">Train</option>
                            <option value="Bus">Bus</option>
                            <option value="Avion">Avion</option>
                        </select>
                    </div>

                    <div>
                        <label for="nom_compagnie">Nom de la compagnie</label>
                        <input type="text" id="nom_compagnie" name="nom_compagnie" placeholder="Ex: Camrail Express"
                            required />
                    </div>

                    <div>
                        <label for="numero">Numéro (identifiant)</label>
                        <input type="text" id="numero" name="numero" placeholder="Ex: BUS-G-45" required />
                        <div class="small">Ce numéro doit être unique et sert d'identifiant.</div>
                    </div>

                    <div style="display:flex;justify-content:flex-end;margin-top:6px">
                        <button class="btn" type="submit"><i class="fas fa-plus-circle"></i> Enregistrer</button>
                    </div>
                </div>
            </form>
        </main>

        <aside>
            <div class="card">
                <h3 style="margin-top:0">Moyens de transport existants</h3>
                <div class="list-transport">
                    <?php if (empty($transports)): ?>
                    <div class="small">Aucun enregistrement trouvé.</div>
                    <?php else: ?>
                    <?php foreach ($transports as $t): ?>
                    <div class="transport-item">
                        <div>
                            <div style="font-weight:700">
                                <?php echo htmlentities($t['type'] . ' — ' . $t['nom_compagnie']); ?>
                            </div>
                            <div class="small">#
                                <?php echo htmlentities($t['numero']); ?>
                            </div>
                        </div>
                        <div class="small">ID:
                            <?php echo (int)$t['id_transport']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </aside>

    </div>

    <script>
        const themeToggle = document.getElementById('theme-toggle');
        function updateTheme(theme) { document.documentElement.setAttribute('data-theme', theme); themeToggle.innerHTML = `<i class="fas ${theme === 'dark' ? 'fa-moon' : 'fa-sun'}"></i>`; localStorage.setItem('theme', theme); }
        const savedTheme = localStorage.getItem('theme') || 'dark'; updateTheme(savedTheme);
        themeToggle.addEventListener('click', () => { const cur = document.documentElement.getAttribute('data-theme'); updateTheme(cur === 'dark' ? 'light' : 'dark'); });
    </script>
</body>

</html>