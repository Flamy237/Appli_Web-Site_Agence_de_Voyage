<?php
require_once __DIR__ . '/../app/models/Database.php';

$pdo = null;
try {
    $pdo = getPDOConnection();
} catch (Exception $e) {
    $error = "Impossible de se connecter à la base de données: " . $e->getMessage();
}

// Fetch transport types and appareils
$transports = [];
$appareils = [];
if ($pdo) {
    $stmt = $pdo->query('SELECT id_transport, type, nom_compagnie FROM moyen_transport ORDER BY type, nom_compagnie');
    $transports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt2 = $pdo->query('SELECT id_appareil, id_transport, numero_identification, capacite_totale FROM appareil ORDER BY numero_identification');
    $appareils = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}

$errors = [];
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect & sanitize
    $id_appareil = isset($_POST['id_appareil']) ? (int)$_POST['id_appareil'] : 0;
    $ville_depart = trim($_POST['ville_depart'] ?? '');
    $ville_arrivee = trim($_POST['ville_arrivee'] ?? '');
    $date_depart = trim($_POST['date_depart'] ?? '');
    $heure_depart = trim($_POST['heure_depart'] ?? '');
    $heure_arrivee = trim($_POST['heure_arrivee'] ?? '');


    $duree = null;
    if (!empty($heure_depart) && !empty($heure_arrivee)) {
        try {
            // Utiliser DateTime pour un calcul précis
            $depart = new DateTime($date_depart . ' ' . $heure_depart);
            $arrivee = new DateTime($date_depart . ' ' . $heure_arrivee);
            
            // Si l'heure d'arrivée est antérieure à l'heure de départ, on suppose un trajet passant minuit.
            if ($arrivee < $depart) {
                $arrivee->modify('+1 day');
            }
            
            $interval = $depart->diff($arrivee);
            // Calcul de la durée totale en minutes : (heures * 60) + minutes
            $duree = ($interval->h * 60) + $interval->i + ($interval->days * 24 * 60); 

        } catch (Exception $e) {
            // Si les formats d'heure/date sont vraiment mauvais (bien que la validation suive), on met une erreur.
            $errors[] = 'Erreur lors du calcul de la durée.';
        }
    }


    $prix_base = isset($_POST['prix_base']) ? number_format((float)$_POST['prix_base'], 2, '.', '') : null;

    // Validation
    if ($id_appareil <= 0) $errors[] = 'Veuillez choisir un appareil valide.';
    if ($ville_depart === '') $errors[] = 'La ville de départ est requise.';
    if ($ville_arrivee === '') $errors[] = 'La ville d\'arrivée est requise.';
    if ($date_depart === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_depart)) $errors[] = 'Date de départ invalide.';
    if ($heure_depart === '' || !preg_match('/^\d{2}:\d{2}$/', $heure_depart)) $errors[] = 'Heure de départ invalide.';
    if ($heure_arrivee === '' || !preg_match('/^\d{2}:\d{2}$/', $heure_arrivee)) $errors[] = 'Heure d\'arrivée invalide.';
    if ($prix_base === null || $prix_base === '' || $prix_base <= 0) $errors[] = 'Le prix de base doit être supérieur à 0.';

    // Date must be today or future
    if ($date_depart !== '') {
        $today = new DateTime('today');
        $d = DateTime::createFromFormat('Y-m-d', $date_depart);
        if (!$d) {
            $errors[] = 'Date de départ invalide.';
        } else if ($d < $today) {
            $errors[] = 'La date de départ doit être aujourd\'hui ou ultérieure.';
        }
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO trajet (id_appareil, ville_depart, ville_arrivee, date_depart, heure_depart, heure_arrivee, duree, prix_base) VALUES (:id_appareil, :ville_depart, :ville_arrivee, :date_depart, :heure_depart, :heure_arrivee, :duree, :prix_base)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_appareil', $id_appareil, PDO::PARAM_INT);
            $stmt->bindParam(':ville_depart', $ville_depart);
            $stmt->bindParam(':ville_arrivee', $ville_arrivee);
            $stmt->bindParam(':date_depart', $date_depart);
            $stmt->bindParam(':heure_depart', $heure_depart);
            $stmt->bindParam(':heure_arrivee', $heure_arrivee);
            $stmt->bindValue(':duree', $duree, PDO::PARAM_INT);
            $stmt->bindParam(':prix_base', $prix_base);
            $stmt->execute();
            $success = 'Trajet ajouté avec succès.';

            // refresh appareils & transports (in case of DB side changes)
            $stmt = $pdo->query('SELECT id_transport, type, nom_compagnie FROM moyen_transport ORDER BY type, nom_compagnie');
            $transports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt2 = $pdo->query('SELECT id_appareil, id_transport, numero_identification, capacite_totale FROM appareil ORDER BY numero_identification');
            $appareils = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'Un trajet pour cet appareil à la même date et heure existe déjà.';
            } else {
                $errors[] = 'Erreur lors de l\'enregistrement : ' . $e->getMessage();
            }
        }
    }
}

// Helper: map appareils to JS
$appareils_js = json_encode($appareils);
$transports_js = json_encode($transports);

?>
<!doctype html>
<html lang="fr" data-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title> Ajouter un trajet | Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
        integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="/public/css/style.css">
    <style>
        /* --- DÉBUT DES STYLES AMÉLIORÉS (Design et Thèmes) --- */

        /* Variables de Thème (Sombres par défaut) */
        :root {
            /* Dark Mode */
            --font-family: 'Poppins', sans-serif;
            --bg-body: #0f172a;
            /* Bleu foncé */
            --bg-gradient: linear-gradient(135deg, var(--bg-body), #0b1220);
            --color-text: #e2e8f0;
            --color-muted: #94a3b8;
            --color-card-bg: rgba(255, 255, 255, 0.04);
            --color-card-shadow: 0 6px 30px rgba(2, 6, 23, 0.6);
            --color-input-border: rgba(255, 255, 255, 0.1);
            --color-input-bg: rgba(255, 255, 255, 0.02);
            --color-input-focus-shadow: 0 6px 18px rgba(124, 58, 237, 0.12);
            --color-input-focus-border: #7c3aed;
            --color-success-bg: #10372d;
            --color-success-text: #c8f7df;
            --color-error-bg: #4c1f1f;
            --color-error-text: #ffdede;
            --color-chip-bg: rgba(255, 255, 255, 0.05);
            --color-chip-text: #dbeafe;
            --color-preview-bg: rgba(124, 58, 237, 0.05);
            /* Preview léger */

            --accent: #7c3aed;
            /* Violet */
            --accent-2: #06b6d4;
            /* Cyan */
        }

        /* Light Mode */
        [data-theme="light"] {
            --bg-body: #f1f5f9;
            /* Gris très clair */
            --bg-gradient: linear-gradient(135deg, var(--bg-body), #e2e8f0);
            --color-text: #1e293b;
            --color-muted: #475569;
            --color-card-bg: #ffffff;
            --color-card-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --color-input-border: #cbd5e1;
            --color-input-bg: #ffffff;
            --color-input-focus-shadow: 0 6px 18px rgba(124, 58, 237, 0.1);
            --color-input-focus-border: #7c3aed;
            --color-success-bg: #dcfce7;
            --color-success-text: #059669;
            --color-error-bg: #fee2e2;
            --color-error-text: #ef4444;
            --color-chip-bg: #e2e8f0;
            --color-chip-text: #334155;
            --color-preview-bg: #f3e8ff;
            /* Preview léger */
        }

        body {
            font-family: var(--font-family);
            background: var(--bg-gradient);
            color: var(--color-text);
            margin: 0;
            padding: 40px;
            min-height: 100vh;
            transition: background 0.3s, color 0.3s;
        }

        .wrap {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            align-items: start;
        }

        .card {
            background: var(--color-card-bg);
            backdrop-filter: blur(5px);
            padding: 28px;
            border-radius: 18px;
            /* Plus arrondi */
            box-shadow: var(--color-card-shadow);
            border: 1px solid var(--color-input-border);
            /* Bordure subtile */
            transition: background 0.3s, box-shadow 0.3s, border 0.3s;
        }

        /* Header Amélioré */
        header.site {
            grid-column: 1/-1;
            margin-bottom: 10px;
        }

        header.site h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* --- DÉBUT MODIFICATION DE LA GRILLE DU FORMULAIRE --- */
        .form-grid {
            /* Nous passons à une grille de 4 colonnes sur les grands écrans pour mieux grouper */
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .form-group-1-2 {
            /* Les champs 'transport' et 'appareil' prennent 2 colonnes sur 4 */
            grid-column: span 2;
        }

        .form-group-full {
            /* Les champs 'ville' et 'aperçu' prennent 4 colonnes */
            grid-column: span 4;
        }

        .form-group-1-4 {
            /* Les champs petits (Heure/Durée/Prix) prennent 1 colonne */
            grid-column: span 1;
        }

        /* Ajustements pour les petits écrans */
        @media (max-width: 900px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
                /* Revenir à 2 colonnes */
            }

            .form-group-1-2,
            .form-group-1-4 {
                grid-column: span 1;
            }

            .form-group-full {
                grid-column: span 2;
            }

            .wrap {
                grid-template-columns: 1fr;
                /* Panneau latéral en bas */
            }

            .right-panel .card {
                position: static;
            }
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
                /* Une seule colonne */
            }

            .form-group-1-2,
            .form-group-1-4,
            .form-group-full {
                grid-column: span 1;
            }
        }

        /* --- FIN MODIFICATION DE LA GRILLE DU FORMULAIRE --- */

        /* Labels et Inputs */
        label {
            font-size: 14px;
            font-weight: 500;
            color: var(--color-text);
            display: block;
            margin-bottom: 8px;
            opacity: 0.9;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-muted);
            font-size: 16px;
            pointer-events: none;
        }

        .input-with-icon {
            padding-left: 40px !important;
        }

        input[type=text] {
            width: 84% !important;
        }


        input[type=date] {
            width: 90% !important;
        }

        input[type=time] {
            width: 80% !important;
        }

        input[type=number] {
            width: 78% !important;
        }

        input[type=text],
        input[type=date],
        input[type=time],
        input[type=number],
        select {
            width: 100%;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid var(--color-input-border);
            background: var(--color-input-bg);
            color: inherit;
            outline: none;
            font-size: 15px;
            font-weight: 400;
            appearance: none;
            /* Pour un meilleur contrôle du style */
            transition: all 0.2s;
        }

        select {
            /* Icone de flèche personnalisée pour les selects */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath fill='%23<?php echo (strpos($_SERVER['HTTP_HOST'], 'dark') !== false ? '94a3b8' : '475569'); ?>' d='M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 12px;
            padding-right: 36px;
        }

        input:focus,
        select:focus {
            box-shadow: var(--color-input-focus-shadow);
            border-color: var(--color-input-focus-border);
        }

        /* Groupes d'heures et de villes */
        .field-row {
            display: flex;
            gap: 15px;
        }

        .field-row>* {
            flex: 1;
        }

        /* Bouton */
        .btn {
            display: inline-flex;
            gap: 10px;
            align-items: center;
            padding: 14px 20px;
            border-radius: 12px;
            background: linear-gradient(90deg, var(--accent), var(--accent-2));
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4);
            transition: all 0.2s;
        }

        .btn:hover {
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.6);
            transform: translateY(-1px);
        }

        .btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(124, 58, 237, 0.4);
        }

        /* Messages */
        .error {
            background: var(--color-error-bg);
            color: var(--color-error-text);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid var(--color-error-text);
            font-weight: 500;
        }

        .success {
            background: var(--color-success-bg);
            color: var(--color-success-text);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid var(--color-success-text);
            font-weight: 500;
        }

        .error ul {
            margin-top: 5px;
            padding-left: 20px;
        }

        /* Aperçu (Preview) */
        #previewText {
            font-weight: 600;
            padding: 15px;
            border-radius: 10px;
            background: var(--color-preview-bg);
            color: var(--color-text);
            border: 1px dashed var(--color-muted);
            min-height: 40px;
            display: flex;
            align-items: center;
        }

        .footer-hint {
            font-size: 12px;
            color: var(--color-muted);
            margin-top: 10px;
        }

        /* Sidebar/Right Panel */
        .right-panel .card {
            position: sticky;
            top: 40px;
        }

        .right-panel h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 10px;
            border-bottom: 1px solid var(--color-input-border);
            padding-bottom: 8px;
        }

        .right-panel ul {
            list-style: none;
            padding-left: 0;
        }

        .right-panel li {
            margin-bottom: 4px;
            padding-left: 18px;
            position: relative;
            font-size: 14px;
            color: var(--color-muted);
        }

        .right-panel li::before {
            content: "\f05a";
            /* icône d'info */
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            left: 0;
            color: var(--accent);
        }

        /* Mode Switch */
        #theme-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--color-card-bg);
            color: var(--color-text);
            border: 1px solid var(--color-input-border);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 18px;
        }

        #theme-toggle:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .header-meta {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--color-chip-bg);
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            color: var(--color-chip-text);
            font-weight: 500;
        }

        .chip .fa-database,
        .chip .fa-table {
            color: var(--accent-2);
        }


        /* Rendre l'Appareil plus clair */
        .capacity {
            font-weight: 600;
            color: var(--accent-2);
        }

        /* Champ entier */
        .full {
            grid-column: 1 / -1;
        }

        /* Amélioration de l'esthétique des selects */
        select option {
            background: var(--bg-body);
            /* Assure un bon fond pour les options */
            color: var(--color-text);
        }

        /* Assurer que la date de départ prenne au moins 2 colonnes sur les grands écrans */
        .date-group {
            grid-column: span 2;
        }

        /* --- FIN DES STYLES AMÉLIORÉS --- */
    </style>
</head>

<body>
    <?php include __DIR__ . '/inc/sidebar.php'; ?>
    <div class="wrap">
        <header class="site">
            <div class="card" style="display:flex;justify-content:space-between;align-items:center">
                <div>
                    <h1><i class="fas fa-route" style="color: var(--accent);"></i> Ajouter un trajet</h1>
                    <div class="muted">Créez et enregistré rapidement un trajet.</div>
                </div>
                <div class="header-meta">
                    <div class="meta">
                        <div class="chip"><i class="fas fa-table"></i> Table: <span class="small">`trajet`</span></div>
                        <div class="chip"><i class="fas fa-database"></i> DB: <span class="small">
                                <?php echo defined('DB_NAME') ? DB_NAME : 'unknown'; ?>
                            </span></div>
                    </div>
                    <button id="theme-toggle" title="Changer de thème">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
        </header>

        <main class="card">
            <?php if (!empty($error)): ?>
            <div class="error"><i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlentities($error); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="error">
                <i class="fas fa-times-circle"></i> <strong>Erreurs de validation :</strong>
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
            <div class="success"><i class="fas fa-check-circle"></i>
                <?php echo htmlentities($success); ?>
            </div>
            <?php endif; ?>

            <form method="post" id="addTrajetForm">
                <div class="form-grid">

                    <div class="input-group form-group-1-2">
                        <label for="transport_type"><i class="fas fa-truck-moving"></i> Type de transport</label>
                        <select id="transport_type" aria-label="Type de transport">
                            <option value="">— Filtrer par type —</option>
                            <?php foreach ($transports as $t): ?>
                            <option data-company="<?php echo htmlentities($t['nom_compagnie']); ?>"
                                value="<?php echo (int)$t['id_transport']; ?>">
                                <?php echo htmlentities($t['type'] . ' — ' . $t['nom_compagnie']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-group form-group-1-2">
                        <label for="id_appareil"><i class="fas fa-plane"></i> Appareil <span
                                class="text-danger">*</span></label>
                        <select name="id_appareil" id="id_appareil" required>
                            <option value="">— Choisir un appareil —</option>
                            <?php foreach ($appareils as $a): ?>
                            <option data-transport="<?php echo (int)$a['id_transport']; ?>"
                                data-cap="<?php echo (int)$a['capacite_totale']; ?>"
                                value="<?php echo (int)$a['id_appareil']; ?>">
                                <?php echo htmlentities($a['numero_identification'] . ' — ' . $a['capacite_totale'] . ' places'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field-row form-group-full">
                        <div class="input-group">
                            <i class="fas fa-map-marker-alt input-icon"></i>
                            <label for="ville_depart">Ville de départ <span class="text-danger">*</span></label>
                            <input type="text" id="ville_depart" name="ville_depart" placeholder="Ex: DOUALA" required
                                class="input-with-icon">
                        </div>
                        <div class="input-group">
                            <i class="fas fa-location-arrow input-icon"></i>
                            <label for="ville_arrivee">Ville d'arrivée <span class="text-danger">*</span></label>
                            <input type="text" id="ville_arrivee" name="ville_arrivee" placeholder="Ex: YAOUNDE"
                                required class="input-with-icon">
                        </div>
                    </div>


                    <div class="input-group date-group">
                        <label for="date_depart"><i class="fas fa-calendar-alt"></i> Date de départ <span
                                class="text-danger">*</span></label>
                        <input type="date" id="date_depart" name="date_depart" min="<?php echo date('Y-m-d'); ?>"
                            required>
                    </div>

                    <div class="input-group form-group-1-4">
                        <label for="heure_depart"><i class="fas fa-clock"></i> Heure départ <span
                                class="text-danger">*</span></label>
                        <input type="time" id="heure_depart" name="heure_depart" required>
                    </div>

                    <div class="input-group form-group-1-4">
                        <label for="heure_arrivee"><i class="fas fa-clock"></i> Heure arrivée <span
                                class="text-danger">*</span></label>
                        <input type="time" id="heure_arrivee" name="heure_arrivee" required>
                    </div>

                    <div class="input-group form-group-1-4">
                        <label for="duree"><i class="fas fa-hourglass-half"></i> Durée (min)</label>
                        <input type="number" id="duree" name="duree" readonly>
                    </div>

                    <div class="input-group form-group-1-4">
                        <label for="prix_base"><i class="fas fa-money-bill-wave"></i> Prix de base (FCFA) <span
                                class="text-danger">*</span></label>
                        <input type="number" id="prix_base" name="prix_base" step="0.01" min="0" placeholder="15000.00"
                            required>
                    </div>

                    <div class="form-group-full">
                        <label class="small"><i class="fas fa-eye"></i> Aperçu du trajet</label>
                        <div id="previewText">Remplissez le formulaire pour voir un aperçu ici.</div>
                        <div class="footer-hint">
                            Le système vérifie l'unicité par appareil + date + heure (contrainte unique).
                        </div>
                    </div>

                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:25px">
                    <div class="muted"><i class="fas fa-asterisk"
                            style="font-size:10px;vertical-align:top;color: var(--color-error-text);"></i> Les champs
                        marqués d'une étoile sont requis.</div>
                    <button class="btn" type="submit">
                        <i class="fas fa-plus-circle"></i> Enregistrer le trajet
                    </button>
                </div>
            </form>
        </main>

        <aside class="right-panel">
            <div class="card">
                <h3><i class="fas fa-info-circle"></i> Aide & informations</h3>
                <p class="small" style="color: var(--color-muted);">Sélectionnez un **type de transport** pour filtrer
                    la liste des appareils associés automatiquement. Si aucun filtre, tous les appareils sont affichés.
                </p>

                <div style="margin-top:15px">
                    <div class="small" style="font-weight:600; color: var(--color-text);">Appareils disponibles</div>
                    <?php if (empty($appareils)): ?>
                    <div class="muted" style="margin-top:8px;">Aucun appareil trouvé. Créez des appareils dans la table
                        <code>appareil</code>.</div>
                    <?php else: ?>
                    <ul style="margin:8px 0;">
                        <?php foreach ($appareils as $a): ?>
                        <li>
                            <span class="capacity">
                                <?php echo htmlentities($a['numero_identification']); ?>
                            </span> (Capacité: <span class="capacity">
                                <?php echo (int)$a['capacite_totale']; ?>
                            </span> places)
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>

                <div style="margin-top:15px">
                    <div class="small" style="font-weight:600; color: var(--color-text);">Types de transport enregistrés
                    </div>
                    <ul style="margin:8px 0;">
                        <?php foreach ($transports as $t): ?>
                        <li>
                            <?php echo htmlentities($t['type']); ?> par **
                            <?php echo htmlentities($t['nom_compagnie']); ?>**
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </aside>

    </div>

    <script>
        const appareils = <?php echo $appareils_js ?? '[]'; ?>;
        const transports = <?php echo $transports_js ?? '[]'; ?>;

        const transportSelect = document.getElementById('transport_type');
        const appareilSelect = document.getElementById('id_appareil');
        const previewText = document.getElementById('previewText');
        const form = document.getElementById('addTrajetForm');
        const themeToggle = document.getElementById('theme-toggle');

        /* --- Logique de Filtrage (Inchangée) --- */
        function filterAppareilsByTransport(idTransport) {
            // Clear and re-add default
            appareilSelect.innerHTML = '<option value="">— Choisir un appareil —</option>';
            const list = appareils.filter(a => String(a.id_transport) === String(idTransport));

            // Determine the list to use (filtered or all as fallback/initial)
            const listToUse = list.length > 0 ? list : appareils;

            listToUse.forEach(a => {
                const opt = document.createElement('option');
                opt.value = a.id_appareil;
                opt.dataset.transport = a.id_transport;
                opt.dataset.cap = a.capacite_totale;
                opt.textContent = `${a.numero_identification} — ${a.capacite_totale} places`;

                // Si on a un filtre mais la liste est vide, on laisse le message par défaut.
                // Sinon, on affiche les options.
                if (list.length > 0 || !idTransport) {
                    appareilSelect.appendChild(opt);
                }
            });
        }

        transportSelect.addEventListener('change', (e) => {
            const id = e.target.value;
            if (!id) {
                // restore full list (fait dans filterAppareilsByTransport si list.length === 0, mais appelons pour être sûr)
                filterAppareilsByTransport(null);
            } else {
                filterAppareilsByTransport(id);
            }
        });

        /* --- Logique de Preview (Inchangée) --- */
        function updatePreview() {
            const vdep = document.getElementById('ville_depart').value;
            const varr = document.getElementById('ville_arrivee').value;
            const date = document.getElementById('date_depart').value;
            const hd = document.getElementById('heure_depart').value;
            const ha = document.getElementById('heure_arrivee').value;
            const prix = document.getElementById('prix_base').value;
            const appText = appareilSelect.options[appareilSelect.selectedIndex]?.text || '— Appareil non choisi —';

            const formatPrice = (p) => {
                if (!p) return '';
                // Simple format sans librairie
                const num = parseFloat(p).toFixed(2);
                return num + ' FCFA';
            }

            const formatCity = (city) => {
                return city.toUpperCase() || '—';
            }

            previewText.textContent = `${formatCity(vdep)} ➡️ ${formatCity(varr)} | ${date || '—/—/—'} ${hd || ''}–${ha || ''} | ✈️ ${appText.split(' — ')[0]} | ${formatPrice(prix)}`;
        }

        ['ville_depart', 'ville_arrivee', 'date_depart', 'heure_depart', 'heure_arrivee', 'prix_base', 'id_appareil'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', updatePreview);
        });
        appareilSelect.addEventListener('change', updatePreview);

        /* Fonction de calcul de la durée en minutes pour le champ "duree" */
        function calculateDuration() {
            const hdInput = document.getElementById('heure_depart');
            const haInput = document.getElementById('heure_arrivee');
            const dureeInput = document.getElementById('duree');

            const hd = hdInput.value;
            const ha = haInput.value;

            if (hd && ha) {
                // Convertir les heures "HH:MM" en minutes totales depuis minuit
                const timeToMinutes = (time) => {
                    const [h, m] = time.split(':').map(Number);
                    return (h * 60) + m;
                };

                let minutesDepart = timeToMinutes(hd);
                let minutesArrivee = timeToMinutes(ha);
                let durationMinutes;

                if (minutesArrivee >= minutesDepart) {
                    // Cas simple: arrivée le même jour
                    durationMinutes = minutesArrivee - minutesDepart;
                } else {
                    // Cas du passage à minuit: Arrivée le jour suivant
                    // (1440 minutes = 24 heures)
                    durationMinutes = (1440 - minutesDepart) + minutesArrivee;
                }

                dureeInput.value = durationMinutes;
            } else {
                dureeInput.value = ''; // Réinitialiser si les heures sont manquantes
            }
        }


        /* --- Lier le calcul aux événements d'input --- */

        // Lier toutes les entrées qui affectent le calcul ou l'aperçu
        ['ville_depart', 'ville_arrivee', 'date_depart', 'heure_depart', 'heure_arrivee', 'prix_base', 'id_appareil'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', () => {
                calculateDuration(); // Calculer la durée
                updatePreview();     // Mettre à jour l'aperçu
            });
        });
        appareilSelect.addEventListener('change', () => {
            calculateDuration(); // Calculer la durée
            updatePreview();     // Mettre à jour l'aperçu
        });



        // Simple client validation to enhance UX
        form.addEventListener('submit', (ev) => {
            // minimal check to keep UX smooth
            const idApp = document.getElementById('id_appareil').value;
            const date = document.getElementById('date_depart').value;
            if (!idApp) { ev.preventDefault(); alert('Veuillez choisir un appareil.'); return; }
            if (!date) { ev.preventDefault(); alert('Veuillez choisir une date de départ.'); return; }
        });

        // Initialisation de la preview
        (function init() {
            // Reconstruit la liste initiale des appareils si elle a été filtrée ou vidée
            filterAppareilsByTransport(transportSelect.value || null);
            updatePreview();
        })();


        /* --- Logique de Theme Toggle (Ajoutée) --- */

        function updateTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            const icon = theme === 'dark' ? 'fa-moon' : 'fa-sun';
            themeToggle.innerHTML = `<i class="fas ${icon}"></i>`;
            localStorage.setItem('theme', theme);

        }

        // Charger le thème depuis localStorage ou utiliser le thème par défaut 'dark'
        const savedTheme = localStorage.getItem('theme') || 'dark';
        updateTheme(savedTheme);

        // Événement de bascule
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            updateTheme(newTheme);
        });
    </script>

    
</body>

</html>