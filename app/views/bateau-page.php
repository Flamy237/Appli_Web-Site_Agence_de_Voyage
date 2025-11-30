<?php
// bateau-page.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/TrajetModel.php';
$trajetModel = new TrajetModel();
$type_transport = 'Bateau';
// Récupérer dynamiquement l'id_transport correspondant au type
$id_transport_value = 0;
try {
    $pdoTmp = getPDOConnection();
    $st = $pdoTmp->prepare('SELECT id_transport FROM moyen_transport WHERE LOWER(type) = ? LIMIT 1');
    $st->execute([strtolower($type_transport)]);
    $r = $st->fetch();
    if ($r) $id_transport_value = intval($r['id_transport']);
} catch (Exception $e) {
    error_log('Impossible de récupérer id_transport pour bateaux: ' . $e->getMessage());
}
$villes_depart = [];
$villes_arrivee = [];
$classes = ['Standard', 'VIP'];
$load_error = null;
$all_trajets_debug = [];
try {
    $villes_depart = $trajetModel->getDistinctDepartCities($type_transport);
    $classes = $trajetModel->getAllClasses();
    $villes_arrivee = $trajetModel->getDistinctArrivalCities($type_transport);
    if (empty($villes_depart)) {
        $all_trajets_debug = $trajetModel->getAllTrajets();
    }
} catch (PDOException $e) {
    error_log("Erreur de BDD au chargement du formulaire: " . $e->getMessage());
    $load_error = "Erreur BDD: " . $e->getMessage();
} catch (Exception $e) {
    error_log("Erreur au chargement du formulaire: " . $e->getMessage());
    $load_error = "Erreur: " . $e->getMessage();
}
$prefill_nom = '';
$prefill_prenom = '';
if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
    $prefill_nom = !empty($_SESSION['user']['nom']) ? $_SESSION['user']['nom'] : '';
    $prefill_prenom = !empty($_SESSION['user']['prenom']) ? $_SESSION['user']['prenom'] : '';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <title>Flex Travel | Réservation de Bateau</title>
    <style>
        :root {
            --couleur-primaire: #1E293B;
            --couleur-action: #06B6D4;
            --couleur-action-survol: #0891B2;
            --couleur-neutre-claire: #F8FAFC;
            --couleur-accent-2: #FACC15;
            --couleur-glass-bg: rgba(255, 255, 255, 0.1);
            --couleur-form-glass-bg: rgba(255, 255, 255, 0.8);
            --couleur-glass-border: rgba(255, 255, 255, 0.4);
            --couleur-shadow-dark: rgba(0, 0, 0, 0.4);
            --texte-principal: #334155;
            --texte-inverse: #F8FAFC;
        }

        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            list-style: none;
            text-decoration: none;
        }

        body {
            background-color: var(--couleur-primaire);
            min-height: 100vh;
            padding-top: 10vh;
            overflow-x: hidden;
            position: relative;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
        }

        .bateau-main-container {
            display: flex;
            justify-content: space-between;
            padding: 5vh 5vw 0 5vw;
            align-items: center;
            min-height: 90vh;
        }

        .bateau-gauche {
            width: 50%;
            padding-right: 30px;
        }

        .bateau-content {
            color: var(--texte-inverse);
            margin-bottom: 40px;
        }

        .bateau-content h1,
        .bateau-content h2 {
            text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            font-size: 3.5rem;
            font-weight: 900;
        }

        .bateau-content h2 span {
            color: var(--couleur-accent-2);
            text-shadow: 2px 2px 3px rgba(0, 0, 0, 0.5);
        }

        .bateau-content p {
            margin: 15px 0 25px 0;
            font-size: 1.2rem;
            font-weight: 300;
        }

        .bateau-form-wrapper {
            background: var(--couleur-form-glass-bg);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--couleur-glass-border);
            border-radius: 15px;
            box-shadow: 0 10px 30px var(--couleur-shadow-dark);
            padding: 30px;
            width: 100%;
            max-width: 650px;
            animation: dayTime 40s linear infinite;
        }

        .bateau-form {
            display: flex;
            flex-wrap: wrap;
            flex-direction: column;
            gap: 20px;
        }

        .bateau-input-line {
            display: flex;
            gap: 25px;
            width: 100%;
        }

        .bateau-form-group {
            flex: 1;
            min-width: 200px;
            display: flex;
            flex-direction: column;
        }

        .bateau-form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--texte-principal);
        }

        .bateau-form-group input,
        .bateau-form-group [list] {
            border: 1px solid #ccc;
            padding: 12px;
            border-radius: 8px;
            font-size: 1rem;
            background-color: var(--couleur-neutre-claire);
            color: var(--texte-principal);
            transition: border 0.3s;
        }

        .bateau-form-group input:focus,
        .bateau-form-group [list]:focus {
            border: 2px solid var(--couleur-action);
            outline: none;
            box-shadow: 0 0 5px rgba(6, 182, 212, 0.5);
        }

        .bateau-form-button {
            width: 100%;
            margin-top: 10px;
        }

        .bateau-buy-btn {
            width: 100%;
            padding: 15px;
            background-color: var(--couleur-action);
            color: var(--texte-inverse);
            font-size: 1.2rem;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }

        .bateau-buy-btn:hover {
            background-color: var(--couleur-action-survol);
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.3);
        }

        .bateau-droite {
            width: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        #bateau-img {
            width: 90%;
            max-width: 500px;
            border-radius: 15px;
            box-shadow: 0 10px 30px var(--couleur-shadow-dark);
            animation: bateau-rock 10s ease-in-out infinite;
        }

        @keyframes bateau-rock {

            0%,
            100% {
                transform: translateY(0) rotate(-2deg);
            }

            50% {
                transform: translateY(-15px) rotate(2deg);
            }
        }

        @media (max-width: 1024px) {
            .bateau-main-container {
                flex-direction: column;
                padding: 20px;
            }

            .bateau-gauche {
                width: 100%;
                padding-right: 0;
                order: 2;
            }

            .bateau-droite {
                width: 100%;
                order: 1;
                margin-bottom: 40px;
                height: 350px;
            }

            .bateau-content {
                text-align: center;
                margin-bottom: 30px;
            }

            .bateau-form-wrapper {
                width: 90%;
                margin: 0 auto;
            }

            .bateau-input-line {
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .bateau-main-container {
                flex-direction: column;
            }

            .bateau-droite {
                height: 250px;
            }

            .bateau-input-line {
                flex-direction: column;
                gap: 15px;
            }

            .bateau-form-wrapper {
                padding: 20px;
            }
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--couleur-glass-bg);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background-color: var(--couleur-form-glass-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 8px 32px var(--couleur-glass-shadow-dark);
            border: 1px solid var(--couleur-glass-border);
            width: 90%;
            max-width: 450px;
            transform: scale(0.9);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .modal-overlay.active .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--couleur-neutre-alternative);
            margin-bottom: 20px;
        }

        .modal-title {
            color: var(--couleur-accent-2);
            font-size: 1.5em;
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 2em;
            line-height: 1;
            cursor: pointer;
            color: var(--couleur-acccent-1);
            transition: color 0.2s;
        }

        .close-btn:hover {
            color: var(--couleur-accent-1-survol);
        }

        .auth-form .form-group {
            margin-bottom: 15px;
        }

        .auth-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: var(--couleur-primaire);
            font-size: 0.9em;
        }

        .auth-form input[type="text"],
        .auth-form select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--couleur-neutre-alternative);
            border-radius: 8px;
            background-color: white;
            font-size: 1em;
            box-sizing: border-box;
            color: var(--couleur-primaire);
        }

        .auth-form input[type="text"]:focus,
        .auth-form select:focus {
            outline: none;
            border-color: var(--couleur-action);
            box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.3);
        }

        .modal-footer {
            padding-top: 15px;
            text-align: right;
        }

        .auth-submit-btn {
            max-width: none;
        }

        @media (max-width: 600px) {
            .bateau-input-line {
                flex-direction: column;
                gap: 15px;
            }

            .bateau-form-group {
                width: 100%;
            }

            .bateau-form {
                padding: 20px;
            }

            .modal-content {
                padding: 15px;
            }
        }

        /* Cyber neon circular + bar loader (photo-inspired) */
        :root {
            --loader-accent: #00f5ff;
            --loader-accent-2: #00d0c7;
            --loader-bg: rgba(2, 6, 23, 0.95);
        }

        #page-loader {
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse at center, rgba(2, 6, 23, 0.95) 0%, rgba(0, 0, 0, 0.92) 60%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            transition: opacity 0.6s ease, visibility 0.6s ease;
            opacity: 1;
            visibility: visible;
        }

        #page-loader.loaded {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .loader-inner {
            display: flex;
            gap: 28px;
            align-items: center;
            color: var(--loader-accent);
        }

        /* Left: circular progress */
        .loader-left {
            width: 140px;
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .loader-svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .ring {
            fill: none;
            stroke-linecap: round;
            filter: drop-shadow(0 0 10px rgba(0, 245, 255, 0.15));
        }

        .ring-outer {
            stroke: var(--loader-accent);
            stroke-width: 4;
            stroke-opacity: 0.98;
        }

        .ring-inner {
            stroke: var(--loader-accent-2);
            stroke-width: 3;
            stroke-opacity: 0.9;
        }

        .loader-perc {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 800;
            color: var(--loader-accent);
            text-shadow: 0 0 14px rgba(0, 245, 255, 0.35);
        }

        /* Right: progress bar */
        .loader-right {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-start;
        }

        .bar-track {
            width: 420px;
            max-width: 60vw;
            height: 46px;
            border-radius: 8px;
            background: linear-gradient(90deg, rgba(0, 0, 0, 0.12), rgba(255, 255, 255, 0.02));
            border: 2px solid rgba(0, 245, 255, 0.12);
            box-shadow: 0 8px 36px rgba(0, 245, 255, 0.04);
            position: relative;
            overflow: hidden;
        }

        .bar-fill {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, rgba(0, 245, 255, 0.98), rgba(0, 170, 255, 0.95));
            box-shadow: 0 0 20px rgba(0, 245, 255, 0.18) inset;
            transition: width 160ms linear;
        }

        .bar-stripes {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(90deg, rgba(255, 255, 255, 0.08) 0 12%, transparent 12% 24%);
            background-size: 48px 100%;
            animation: stripes-move 900ms linear infinite;
            mix-blend-mode: overlay;
            pointer-events: none;
        }

        @keyframes stripes-move {
            from {
                background-position: 0 0
            }

            to {
                background-position: 48px 0
            }
        }

        .bar-meta {
            font-size: 13px;
            color: var(--loader-accent);
            opacity: 0.95;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        @media (max-width:800px) {
            .loader-inner {
                gap: 14px;
            }

            .bar-track {
                width: 260px
            }

            .loader-left {
                width: 110px;
                height: 110px
            }
        }

        /* Page loader — HUD-style neon segmented ring + vertical-line bar */
        :root {
            --neon: #00f5ff;
            --neon-2: #00d0c7;
            --bg-dark: #000612;
            --seg-off: rgba(255, 255, 255, 0.06);
        }

        #page-loader {
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.96) 0%, rgba(0, 0, 0, 0.99) 70%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            transition: opacity 0.6s ease, visibility 0.6s ease;
        }

        #page-loader.loaded {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .loader-inner {
            display: flex;
            gap: 28px;
            align-items: center;
            color: var(--neon);
        }

        /* Left: segmented circular indicator */
        .loader-left {
            width: 160px;
            height: 160px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center
        }

        #ring-svg {
            width: 100%;
            height: 100%;
            display: block
        }

        #ring-svg .seg {
            fill: var(--seg-off);
            transition: fill 160ms ease, opacity 160ms ease;
        }

        #ring-svg .seg.on {
            fill: var(--neon);
            filter: drop-shadow(0 0 10px var(--neon));
        }

        .loader-perc {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 22px;
            color: #eaffff;
            text-shadow: 0 0 10px rgba(0, 245, 255, 0.25);
            font-family: 'Poppins', monospace;
        }

        /* Right: vertical-line progress bar */
        .loader-right {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-start
        }

        .bar-meta {
            font-family: 'Poppins', monospace;
            font-weight: 700;
            color: var(--neon);
            letter-spacing: 2px;
            font-size: 12px;
            text-transform: uppercase;
            text-shadow: 0 0 8px rgba(0, 245, 255, 0.12);
        }

        .bar-track {
            display: flex;
            align-items: end;
            gap: 6px;
            padding: 8px;
            width: 520px;
            max-width: 66vw;
            height: 56px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(0, 245, 255, 0.06);
            box-shadow: 0 12px 40px rgba(0, 245, 255, 0.03) inset
        }

        .bar-seg {
            flex: 0 0 8px;
            height: 40%;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 3px;
            transition: all 180ms ease;
            opacity: 0.95
        }

        .bar-seg.active {
            height: 100%;
            background: linear-gradient(180deg, var(--neon), var(--neon-2));
            box-shadow: 0 0 18px var(--neon);
            transform: translateY(0);
            animation: bar-pulse 1100ms ease-in-out infinite
        }

        @keyframes bar-pulse {
            0% {
                transform: translateY(0) scaleY(1);
            }

            50% {
                transform: translateY(-6px) scaleY(1.03);
            }

            100% {
                transform: translateY(0) scaleY(1);
            }
        }

        /* Loading text shimmer */
        .loading-text {
            font-family: 'Poppins', monospace;
            color: var(--neon);
            font-weight: 700;
            letter-spacing: 1.5px;
            font-size: 13px;
            margin-top: 6px;
        }

        .loading-text {
            text-shadow: 0 0 6px rgba(0, 245, 255, 0.12);
            animation: text-glow 1.8s linear infinite
        }

        @keyframes text-glow {
            0% {
                text-shadow: 0 0 6px rgba(0, 245, 255, 0.06)
            }

            50% {
                text-shadow: 0 0 18px rgba(0, 245, 255, 0.6)
            }

            100% {
                text-shadow: 0 0 6px rgba(0, 245, 255, 0.06)
            }
        }

        @media (max-width:900px) {
            .loader-inner {
                gap: 16px
            }

            .bar-track {
                width: 320px
            }

            .loader-left {
                width: 120px;
                height: 120px
            }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            const $departInput = $('#bateau-depart');
            const $arriveeInput = $('#bateau-arrive');
            const $arriveeDatalist = $('#bateau-arrive-list');
            const $dateDatalist = $('#bateau-dateD-list');
            const $heureDatalist = $('#bateau-heureD-list');
            const $compagnieDatalist = $('#bateau-compagnie-list');
            const typeTransport = 'Bateau';
            function formatDisplayDate(dateString) {
                const parts = dateString.split('-');
                if (parts.length === 3) {
                    return `${parts[2]}/${parts[1]}/${parts[0]}`;
                }
                return dateString;
            }
            const _projBase = (function () {
                const match = window.location.pathname.match(/^.*?(\/projet-agence-de-voyage)/i);
                if (match && match[1]) return window.location.origin + match[1];
                return window.location.origin + '/projet-agence-de-voyage';
            })();
            function updateArrivalCities() {
                const depart = $departInput.val();
                $arriveeDatalist.empty();
                $dateDatalist.empty();
                $heureDatalist.empty();
                $compagnieDatalist.empty();
                $arriveeInput.val('');
                if (depart) {
                    const urlArrivals = _projBase + '/app/api/trajet_arrivals.php';
                    $.ajax({
                        url: urlArrivals,
                        method: 'GET',
                        data: { depart: depart, typeTransport: typeTransport },
                        dataType: 'json',
                        success: function (response) {
                            if (response.success && response.arrivals.length > 0) {
                                response.arrivals.forEach(function (city) {
                                    $arriveeDatalist.append(`<option value="${city}">${city}</option>`);
                                });
                            } else {
                                $arriveeDatalist.append('<option value="">Aucune destination disponible</option>');
                            }
                        },
                        error: function () {
                            $arriveeDatalist.empty();
                            $arriveeDatalist.append('<option value="">Erreur de chargement</option>');
                        }
                    });
                }
            }
            function updateDateAndHeureLists() {
                const depart = $departInput.val();
                const arrivee = $arriveeInput.val();
                $dateDatalist.empty();
                $heureDatalist.empty();
                $compagnieDatalist.empty();
                if (depart && arrivee) {
                    const urlDates = _projBase + '/app/api/trajet_dates.php';
                    $.ajax({
                        url: urlDates,
                        method: 'GET',
                        data: { depart: depart, arrivee: arrivee, typeTransport: typeTransport },
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                response.dates.forEach(function (date) {
                                    const displayDate = formatDisplayDate(date);
                                    $dateDatalist.append(`<option value="${date}">${displayDate}</option>`);
                                });
                                response.heures.forEach(function (heure) {
                                    const formattedHeure = heure.substring(0, 5);
                                    $heureDatalist.append(`<option value="${formattedHeure}">${formattedHeure}</option>`);
                                });
                            }
                        }
                    });
                    updateCompanyList(depart, arrivee);
                }
            }
            function updateCompanyList(depart, arrivee) {
                $compagnieDatalist.empty();
                const urlCompanies = _projBase + '/app/api/trajet_compagnies.php';
                $.ajax({
                    url: urlCompanies,
                    method: 'GET',
                    data: { depart: depart, arrivee: arrivee, typeTransport: typeTransport },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success && response.companies.length > 0) {
                            response.companies.forEach(function (company) {
                                $compagnieDatalist.append(`<option value="${company}">${company}</option>`);
                            });
                        } else if (response.success) {
                            $compagnieDatalist.append('<option value="">Toutes Compagnies</option>');
                        }
                    }
                });
            }
            $departInput.on('change', updateArrivalCities);
            $arriveeInput.on('change', updateDateAndHeureLists);
        });
    </script>
</head>

<body>
    <!-- Page loader: visible on every page open, hides after resources load -->
    <div id="page-loader" aria-hidden="false">
        <div class="loader-inner" role="status" aria-label="Chargement de la page">
            <div class="loader-left">
                <svg id="ring-svg" viewBox="0 0 120 120" aria-hidden="true"></svg>
                <div class="loader-perc" aria-live="polite">0%</div>
            </div>
            <div class="loader-right">
                <div class="bar-meta">LOADING...</div>
                <div class="bar-track" id="bar-track" aria-hidden="true"></div>
                <div class="loading-text" aria-hidden="true">LOADING...</div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/layout/header.php'; ?>
    <div class="bateau-main-container">
        <section class="bateau-gauche">
            <div class="bateau-content">
                <h1 data-aos="fade-right" data-aos-duration="2000" data-aos-delay="500">Bienvenue</h1>
                <h2 data-aos="fade-left" data-aos-duration="2000" data-aos-delay="1000">Sur <span>FLEX TRAVEL</span>
                </h2>
                <p data-aos="fade-up" data-aos-duration="2000" data-aos-delay="500">
                    Réservez votre billet de Bateau en ligne,<br> Facilement et en toute Sécurité.
                </p>
                <div class="bateau-form-wrapper" data-aos="fade-up" data-aos-duration="1500" data-aos-delay="800">
                    <?php if ($load_error): ?>
                    <div
                        style="background-color: #fee; color: #c00; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <strong>⚠️ Erreur de chargement :</strong>
                        <?php echo htmlspecialchars($load_error); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (empty($villes_depart)): ?>
                    <div
                        style="background-color: #fef3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <strong>⚠️ Aucune ville de départ :</strong> Veuillez vérifier que des trajets Bateau existent
                        en base de données.
                        <?php if (!empty($all_trajets_debug)): ?>
                        <br><small><strong>Debug:</strong> Trajets trouvés en base (tous types) :
                            <?php echo count($all_trajets_debug); ?> (filtrés :
                            <?php echo count($villes_depart); ?>)
                        </small>
                        <br><small>Types :
                            <?php echo implode(', ', array_unique(array_column($all_trajets_debug, 'type'))); ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <form action="#" class="bateau-form" id="bateauForm">
                        <!-- Hidden transport identifiers: populated from DB -->
                        <input type="hidden" name="id_transport" id="bateau-id-transport"
                            value="<?php echo $id_transport_value; ?>">
                        <input type="hidden" name="type_transport" id="bateau-type-transport" value="Bateau">
                        <div class="bateau-input-line">
                            <div class="bateau-form-group">
                                <label for="bateau-depart">Départ</label>
                                <input list="bateau-depart-list" name="departD" id="bateau-depart"
                                    placeholder="Lieu de Départ" require>
                                <datalist id="bateau-depart-list">
                                    <?php foreach ($villes_depart as $ville): ?>
                                    <option value="<?php echo htmlspecialchars($ville); ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="bateau-form-group">
                                <label for="bateau-arrive">Arrivée</label>
                                <input list="bateau-arrive-list" name="arriveA" id="bateau-arrive"
                                    placeholder="Lieu D'Arrivée">
                                <datalist id="bateau-arrive-list"></datalist>
                            </div>
                        </div>
                        <div class="bateau-input-line">
                            <div class="bateau-form-group">
                                <label for="bateau-dateD">Date Départ</label>
                                <input list="bateau-dateD-list" name="dateD" id="bateau-dateD" placeholder="jj/mm/aaaa">
                                <datalist id="bateau-dateD-list"></datalist>
                            </div>
                            <div class="bateau-form-group">
                                <label for="bateau-heureD">Heure Départ</label>
                                <input list="bateau-heureD-list" name="heureD" id="bateau-heureD"
                                    placeholder="Heure Départ">
                                <datalist id="bateau-heureD-list"></datalist>
                            </div>
                        </div>
                        <div class="bateau-input-line">
                            <div class="bateau-form-group">
                                <label for="bateau-compagnie">Compagnie</label>
                                <input list="bateau-compagnie-list" name="compagnie" id="bateau-compagnie"
                                    placeholder="Nom de la Compagnie">
                                <datalist id="bateau-compagnie-list"></datalist>
                            </div>
                            <div class="bateau-form-group">
                                <label for="bateau-classe">Classe</label>
                                <input list="bateau-classe-list" name="classe" id="bateau-classe"
                                    placeholder="Votre Classe de Siège">
                                <datalist id="bateau-classe-list">
                                    <?php foreach ($classes as $class_name): ?>
                                    <option value="<?php echo htmlspecialchars($class_name); ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        <div class="bateau-form-button">
                            <button type="submit" class="bateau-buy-btn">RECHERCHER BILLETS</button>
                        </div>
                    </form>
                </div>
                <div id="authModal" class="modal-overlay">
                    <div class="modal-content">
                        <header class="modal-header">
                            <button class="close-btn">&times;</button>
                        </header>
                        <form action="#" class="auth-form" id="authForm">
                            <div class="form-group">
                                <label for="auth-nom">Nom</label>
                                <input type="text" id="auth-nom" name="nom" required
                                    value="<?php echo htmlspecialchars($prefill_nom); ?>">
                            </div>
                            <div class="form-group">
                                <label for="auth-prenom">Prénom</label>
                                <input type="text" id="auth-prenom" name="prenom" required
                                    value="<?php echo htmlspecialchars($prefill_prenom); ?>">
                            </div>
                            <div class="form-group">
                                <label for="auth-type-piece">Type de Pièce d'Identité</label>
                                <select id="auth-type-piece" name="typePiece" required>
                                    <option value="" disabled selected>Choisir un type</option>
                                    <option value="Passeport">Passeport</option>
                                    <option value="CNI">CNI (Carte Nationale d'Identité)</option>
                                    <option value="Permis">Permis de Conduire</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="auth-num-piece">Numéro de Pièce d'Identité</label>
                                <input type="text" id="auth-num-piece" name="numPiece" required>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="auth-submit-btn">VALIDER ET CONTINUER</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        <section id="bateau-img" class="bateau-droite" data-aos="fade-down-left" data-aos-duration="1000"
            data-aos-delay="800">
            <img src="/projet-agence-de-voyage/public/img/voyage-bateau.jpg" alt="Image d'un bateau moderne"
                id="bateau-img-real"
                style="width:90%;max-width:500px;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,0.4);">
        </section>
    </div>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Loader with segmented ring and vertical-line bar (generates segments dynamically)
        (function () {
            const loader = document.getElementById('page-loader');
            if (!loader) return;
            const ringSvg = document.getElementById('ring-svg');
            const barTrack = document.getElementById('bar-track');
            const percEl = loader.querySelector('.loader-perc');
            const minDisplay = 1500; // ms minimal visible time
            const start = Date.now();

            // configuration
            const NUM_SEGS = 36; // segments around circle
            const NUM_BARS = 40; // vertical lines in bar

            // build ring segments (SVG rects placed radially)
            const cx = 60, cy = 60, r = 42;
            for (let i = 0; i < NUM_SEGS; i++) {
                const angle = i * (360 / NUM_SEGS);
                const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                const w = 6, h = 14;
                // place rect above center then rotate
                rect.setAttribute('x', String(cx - w / 2));
                rect.setAttribute('y', String(cy - r - h / 2 + 6));
                rect.setAttribute('width', String(w));
                rect.setAttribute('height', String(h));
                rect.setAttribute('rx', '2');
                rect.setAttribute('class', 'seg');
                rect.setAttribute('transform', `rotate(${angle} ${cx} ${cy})`);
                rect.dataset.index = i;
                ringSvg.appendChild(rect);
            }
            const segs = Array.from(ringSvg.querySelectorAll('.seg'));

            // build bar segments
            for (let i = 0; i < NUM_BARS; i++) {
                const s = document.createElement('div');
                s.className = 'bar-seg';
                s.dataset.index = i;
                // staggered heights for visual variance
                s.style.height = (40 + Math.round(Math.sin(i / 4) * 18)) + '%';
                barTrack.appendChild(s);
            }
            const bars = Array.from(barTrack.querySelectorAll('.bar-seg'));

            // render function: lights up first N segments/bars according to percent
            let percent = 0;
            function render(p) {
                p = Math.max(0, Math.min(100, Math.round(p)));
                if (percEl) percEl.textContent = p + '%';
                const segOn = Math.round(p / 100 * NUM_SEGS);
                segs.forEach((el, idx) => el.classList.toggle('on', idx < segOn));
                const barOn = Math.round(p / 100 * NUM_BARS);
                bars.forEach((el, idx) => el.classList.toggle('active', idx < barOn));
                // stagger active animation delays
                bars.forEach((el, idx) => { if (el.classList.contains('active')) el.style.animationDelay = (idx * 18) % 500 + 'ms'; else el.style.animationDelay = '0ms'; });
            }

            // auto-increment while page loads (avoid jumping to 100 too early)
            const BEFORE_TARGET = 90;
            let autoInterval = setInterval(() => {
                if (percent < BEFORE_TARGET) { percent += 0.6 + Math.random() * 1.6; if (percent > BEFORE_TARGET) percent = BEFORE_TARGET; render(percent); }
            }, 80);

            function finalizeAndHide() {
                render(100);
                const waited = Date.now() - start;
                const left = Math.max(0, minDisplay - waited);
                setTimeout(() => { loader.classList.add('loaded'); setTimeout(() => { if (loader && loader.parentNode) loader.parentNode.removeChild(loader); }, 700); }, left + 300);
            }

            window.addEventListener('load', function () {
                clearInterval(autoInterval);
                // quick ramp to 100
                const ramp = setInterval(() => { if (percent < 100) { percent += 6; render(percent); } else { clearInterval(ramp); finalizeAndHide(); } }, 36);
            });

            // fallback: finish after 6s
            setTimeout(() => { if (document.readyState === 'complete') return; clearInterval(autoInterval); finalizeAndHide(); }, 6000);

            // initial paint
            render(0);
        })();

        AOS.init();
        const bateauForm = document.getElementById('bateauForm');
        const authModal = document.getElementById('authModal');
        const closeBtn = authModal.querySelector('.close-btn');
        function showModal(event) {
            event.preventDefault();
            authModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function hideModal() {
            authModal.classList.remove('active');
            document.body.style.overflow = '';
        }
        if (bateauForm) {
            bateauForm.addEventListener('submit', showModal);
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', hideModal);
        }
        if (authModal) {
            authModal.addEventListener('click', (e) => {
                if (e.target === authModal) {
                    hideModal();
                }
            });
        }
        const authForm = document.getElementById('authForm');
        if (authForm) {
            const _projBaseModal = (function () {
                const match = window.location.pathname.match(/^.*?(\/projet-agence-de-voyage)/i);
                if (match && match[1]) return window.location.origin + match[1];
                return window.location.origin + '/projet-agence-de-voyage';
            })();
            authForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const nom = document.getElementById('auth-nom').value.trim();
                const prenom = document.getElementById('auth-prenom').value.trim();
                const typePiece = document.getElementById('auth-type-piece').value;
                const numPiece = document.getElementById('auth-num-piece').value.trim();
                if (!nom || !prenom || !typePiece || !numPiece) {
                    alert('Veuillez remplir tous les champs du passager avant de continuer.');
                    return;
                }
                hideModal();
                const departField = document.getElementById('bateau-depart').value || '';
                const arriveField = document.getElementById('bateau-arrive').value || '';
                const dateField = document.getElementById('bateau-dateD').value || '';
                const heureField = document.getElementById('bateau-heureD').value || '';
                const compagnieField = document.getElementById('bateau-compagnie').value || '';
                const classeField = document.getElementById('bateau-classe').value || '';
                const productName = `Billet Bateau ${departField}→${arriveField} ${dateField} ${heureField} (${classeField})`;
                const demoAmount = 285000;
                const params = new URLSearchParams({
                    nom: nom,
                    prenom: prenom,
                    productName: productName,
                    amount: demoAmount,
                    depart: departField,
                    arrivee: arriveField,
                    date: dateField,
                    heure: heureField,
                    compagnie: compagnieField,
                    classe: classeField,
                    type_transport: 'Bateau',
                    id_transport: document.getElementById('bateau-id-transport').value || ''
                });
                const paymentUrl = _projBaseModal + '/app/api/Payment_Gateway.html' + '?' + params.toString();
                window.location.href = paymentUrl;
            });
        }
    </script>
</body>

</html>