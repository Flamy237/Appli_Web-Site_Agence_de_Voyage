<?php
// train-page.php

// DOIT être la toute première chose exécutée sur la page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion des configurations du modele.
require_once __DIR__ . '/../models/Database.php';  //connexion a la BD
require_once __DIR__ . '/../models/TrajetModel.php';  //Page de recuperations des trajets dispo

// Initialisation du modele de recuperation des donnees.
$trajetModel = new TrajetModel();
$type_transport = 'Train';

// Récupérer dynamiquement l'id_transport correspondant au type
$id_transport_value = 0;
try {
    $pdoTmp = getPDOConnection();
    $st = $pdoTmp->prepare('SELECT id_transport FROM moyen_transport WHERE LOWER(type) = ? LIMIT 1');
    $st->execute([strtolower($type_transport)]);
    $r = $st->fetch();
    if ($r) $id_transport_value = intval($r['id_transport']);
} catch (Exception $e) {
    error_log('Impossible de récupérer id_transport pour train: ' . $e->getMessage());
}

// Variables par défaut pour éviter undefined
$villes_depart = [];
$villes_arrivee = [];
$classes = ['Standard', 'VIP'];
$load_error = null;
$all_trajets_debug = []; // Pour debug

try {
    // 1. Villes de Départ
    $villes_depart = $trajetModel->getDistinctDepartCities($type_transport);
    
    // 2. Classes
    $classes = $trajetModel->getAllClasses();
    
    // 3. Villes d'Arrivée
    $villes_arrivee = $trajetModel->getDistinctArrivalCities($type_transport);
    
    // Si aucune ville trouvée, afficher tous les trajets pour debug
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

// Pré-remplissage des champs Nom / Prénom si l'utilisateur est connecté en session
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

    <title>Flex Travel | Réservation de Train</title>
    <style>
        /* ========================================= Variables et Réinitialisation ========================================= */
        :root {
            /* Palette Flex Travel (Cohérente avec Bus/Avion) */
            --couleur-primaire: #1E293B;
            /* Bleu foncé/Ardoise (Fond) */
            --couleur-action: #06B6D4;
            /* Cyan/Turquoise (Boutons, Accent) */
            --couleur-action-survol: #0891B2;
            --couleur-neutre-claire: #F8FAFC;
            /* Blanc cassé */
            --couleur-accent-2: #FACC15;
            /* Jaune or (Accents secondaires) */

            /* GLASSMORPHISM */
            --couleur-glass-bg: rgba(255, 255, 255, 0.1);
            --couleur-form-glass-bg: rgba(255, 255, 255, 0.8);
            /* Opaque pour le formulaire */
            --couleur-glass-border: rgba(255, 255, 255, 0.4);
            --couleur-shadow-dark: rgba(0, 0, 0, 0.4);

            /* Texte */
            --texte-principal: #334155;
            --texte-inverse: #F8FAFC;
        }

        * {
            font-family: 'Poppins', sans-serif;
            /* Utilisation de Poppins */
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
            /* Espace pour la barre de navigation fixed */
            overflow-x: hidden;
            position: relative;


        }

        /* ========================================= Navigation (Glassmorphism) ========================================= */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
        }

        .train-nav {
            width: 100%;
            height: 10vh;
            display: flex;
            justify-content: space-around;
            align-items: center;
            color: var(--texte-inverse);

            /* GLASSMORPHISM pour la NAV */
            background-color: var(--couleur-glass-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--couleur-glass-border);
            box-shadow: 0 4px 30px var(--couleur-shadow-dark);
        }

        .train-logo h1 {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .train-logo span {
            color: var(--couleur-action);
            /* Utilisation de Cyan/Turquoise */
        }

        .train-links {
            display: flex;
        }

        .train-link a {
            position: relative;
            color: var(--texte-inverse);
            text-decoration: none;
            font-size: 1rem;
            margin: 0 15px;
            font-weight: 500;
            transition: 0.3s linear;
        }

        .train-link a::before {
            content: "";
            position: absolute;
            left: 0;
            bottom: -4px;
            background-color: var(--couleur-accent-2);
            /* Utilisation de Jaune Or */
            width: 0;
            border-radius: 10px;
            transition: 0.3s linear;
            height: 3px;
        }

        .train-link a:hover::before,
        .train-link a:hover,
        .train-active a {
            color: var(--couleur-accent-2);
            width: 100%;
        }

        .train-active a::before {
            width: 100%;
        }

        .train-button-group {
            width: 250px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .train-btn-login,
        .train-btn-signup {
            padding: 10px 15px;
            border: none;
            outline: none;
            font-size: 1rem;
            color: var(--texte-inverse);
            font-weight: 600;
            border-radius: 8px;
            transition: 0.3s ease;
            cursor: pointer;
        }

        .train-btn-login {
            background-color: transparent;
            border: 2px solid var(--couleur-action);
        }

        .train-btn-signup {
            background-color: var(--couleur-action);
        }

        .train-btn-login:hover {
            background-color: var(--couleur-action);
            color: var(--texte-inverse);
        }

        .train-btn-signup:hover {
            background-color: var(--couleur-action-survol);
            transform: translateY(-1px);
        }

        .train-menu-icon {
            font-size: 24px;
            cursor: pointer;
            color: var(--couleur-action);
            display: none;
            /* Caché par défaut */
        }

        /* ========================================= Contenu Principal ========================================= */

        .train-main-container {
            display: flex;
            justify-content: space-between;
            padding: 5vh 5vw 0 5vw;
            align-items: center;
            min-height: 90vh;
        }

        /* Partie gauche (Contenu et Formulaire) */
        .train-gauche {
            width: 50%;
            padding-right: 30px;
        }

        .train-content {
            color: var(--texte-inverse);
            margin-bottom: 40px;
        }

        .train-content h1,
        .train-content h2 {
            text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            font-size: 3.5rem;
            font-weight: 900;
        }

        .train-content h2 span {
            color: var(--couleur-accent-2);
            text-shadow: 2px 2px 3px rgba(0, 0, 0, 0.5);
        }

        .train-content p {
            margin: 15px 0 25px 0;
            font-size: 1.2rem;
            font-weight: 300;
        }

        /* ========================================= Formulaire (Glassmorphism & Espacement Corrigé) ========================================= */
        .train-form-wrapper {
            /* GLASSMORPHISM APPLIQUÉ */
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

        .train-form {
            display: flex;
            flex-wrap: wrap;
            flex-direction: column;
            gap: 20px;
            /* ESPACEMENT VERTICAL entre lignes */
        }

        .train-input-line {
            display: flex;
            gap: 25px;
            /* ESPACEMENT HORIZONTAL entre champs */
            width: 100%;
        }

        .train-form-group {
            flex: 1;
            min-width: 200px;
            display: flex;
            flex-direction: column;
        }

        .train-form-group label {
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--texte-principal);
        }

        .train-form-group input,
        .train-form-group [list] {
            border: 1px solid #ccc;
            padding: 12px;
            border-radius: 8px;
            font-size: 1rem;
            background-color: var(--couleur-neutre-claire);
            color: var(--texte-principal);
            transition: border 0.3s;
        }

        .train-form-group input:focus,
        .train-form-group [list]:focus {
            border: 2px solid var(--couleur-action);
            outline: none;
            box-shadow: 0 0 5px rgba(6, 182, 212, 0.5);
        }

        .train-form-button {
            width: 100%;
            margin-top: 10px;
        }

        .train-buy-btn {
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

        .train-buy-btn:hover {
            background-color: var(--couleur-action-survol);
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.3);
        }

        /* ========================================= Partie Droite (Image et Animation) ========================================= */
        .train-droite {
            width: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        #train-img {
            width: 90%;
            max-width: 500px;
            border-radius: 15px;
            box-shadow: 0 10px 30px var(--couleur-shadow-dark);
            /* Animation de mouvement subtile pour l'image du train */
            animation: train-rock 10s ease-in-out infinite;
        }

        @keyframes train-rock {

            0%,
            100% {
                transform: translateY(0) rotate(-2deg);
            }

            50% {
                transform: translateY(-15px) rotate(2deg);
            }
        }

        /* ========================================= Media Queries ========================================= */

        @media (max-width: 1024px) {
            .train-main-container {
                flex-direction: column;
                padding: 20px;
            }

            .train-gauche {
                width: 100%;
                padding-right: 0;
                order: 2;
                /* Le contenu passe après l'image sur tablette */
            }

            .train-droite {
                width: 100%;
                order: 1;
                margin-bottom: 40px;
                height: 350px;
            }

            .train-content {
                text-align: center;
                margin-bottom: 30px;
            }

            .train-form-wrapper {
                width: 90%;
                margin: 0 auto;
            }

            /* Le formulaire reste sur deux colonnes par ligne */
            .train-input-line {
                gap: 20px;
            }
        }

        @media (max-width: 768px) {

            /* Menu mobile */
            .train-nav {
                justify-content: space-between;
                padding: 0 20px;
                height: 8vh;
            }

            .train-links,
            .train-button-group {
                display: none;
                flex-direction: column;
                position: fixed;
                /* Remplacer absolute par fixed si le body a un padding-top */
                top: 8vh;
                left: 0;
                width: 100%;
                background-color: var(--couleur-primaire);
                border: 1px solid var(--couleur-glass-border);
                padding: 15px 0;
                z-index: 999;
                transition: all 0.3s ease-in-out;
            }

            .train-links.active,
            .train-button-group.active {
                display: flex;
            }

            .train-links.active {
                /* Calcule la position si les boutons suivent */
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
            }

            .train-button-group.active {
                top: calc(8vh + 160px);
                /* Ajuster selon la hauteur des liens */
                padding: 20px 0;
            }

            .train-link {
                margin: 10px 0;
                text-align: center;
            }

            .train-link a {
                font-size: 1.1rem;
            }

            .train-button-group button {
                width: 80%;
                margin: 5px auto;
            }

            .train-menu-icon {
                display: block;
            }

            .train-content h1,
            .train-content h2 {
                font-size: 2.5rem;
            }

            .train-droite {
                height: 250px;
            }

            /* Une seule colonne sur mobile pour les champs de formulaire */
            .train-input-line {
                flex-direction: column;
                gap: 15px;
                /* Espacement réduit sur mobile */
            }

            .train-form-wrapper {
                padding: 20px;
            }
        }




        /* Cloud */
        .sky {
            position: relative;
        }

        .cloud {
            margin: 5% 0 0 100%;
            position: relative;
        }

        .cloud::before,
        .cloud::after {
            position: absolute;
            content: "";
        }

        .cloud,
        .cloud::before,
        .cloud::after {
            background: #fff;
            border-radius: 50%;
            border: 25px solid #fff;
            width: 0.5%;
            overflow: visible;
        }

        .cloud::before {
            top: -35px;
            right: -60px;
            border-width: 30px;
        }

        .cloud::after {
            bottom: -25px;
            right: -95px;
            border-width: 28px;
        }

        .sky .cloud:nth-child(1) {
            animation: wind 80s infinite;
        }

        .sky .cloud:nth-child(2) {
            animation: wind 120s infinite;
        }

        .stage::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            border-top: 3px solid #205c6d;
            border-bottom: 3px dashed #205c6d;
        }

        /* Train */
        .train {
            position: absolute;
            bottom: 10px;
            left: -400px;
            /* start off-screen */
            width: 310px;
            z-index: 1;
            will-change: transform;
            animation: moveTrain 12s linear infinite;
        }

        .wagon,
        .cabin,
        .chinney {
            border-radius: 4px 4px 0 0;
            border-top: 4px solid #ffc928;
        }

        .wagon,
        .motor {
            border-bottom: 2px solid #ffc928;
        }

        .wagon,
        .locomotive {
            display: inline-block;
            width: 70px;
            height: 55px;
            background-color: #764fe0;
            position: relative;
        }

        /* WHEELS */
        .wagon:before,
        .wagon:after,
        .motor:before,
        .motor:after,
        .cabin:after {
            content: "";
            color: #fff;
            padding: 6px;
            position: absolute;
            border-radius: 46%;
            border: 6px solid #333;
            bottom: -10px;
            background-color: #666;
            animation: spin 4s linear infinite;
        }

        .wagon::before {
            left: 6px;
        }

        .wagon::after {
            right: 6px;
        }

        .motor::before {
            left: 32px;
        }

        .motor:after {
            right: 4px;
        }

        .cabin::after {
            bottom: -50px;
            padding: 8px;
            left: 3px;
        }

        /* LOCOMOTIVE */
        .locomotive {
            background-color: transparent;
        }

        .locomotive .cabin {
            width: 58px;
            height: 35px;
            background-color: #653fcd;
            position: relative;
            z-index: 1;
            border-radius: 8px 8px 0 0;
        }

        /* LOCOMOTIVE WINDOWS */
        .locomotive .cabin::before {
            content: "";
            width: 25px;
            height: 18px;
            position: absolute;
            top: 10px;
            left: 15px;
            border-radius: 3px;
            border: 3px solid pink;
            background-color: #f1f1f1;
        }

        .locomotive .motor {
            display: inline-block;
            width: 85px;
            height: 40px;
            background-color: #764fe0;
            position: relative;
            border-radius: 0 8px 0px 0px;
        }

        .locomotive .chinney {
            position: absolute;
            width: 15px;
            height: 15px;
            background-color: #967be2;
            bottom: 10px;
            -webkit-animation: puf 4s 2;
            animation: puf 4s 2;
        }

        /* small smoke puff element inside the chimney */
        .locomotive .chinney .smoke {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 100%;
            width: 10px;
            height: 10px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            filter: blur(0.6px);
            animation: smoke 2s linear infinite;
            pointer-events: none;
        }

        .locomotive .light {
            content: "";
            position: absolute;
            right: -10px;
            bottom: 0;
            border-radius: 50%;
            border: 3px solid pink;
        }

        /* Animation */
        @keyframes dayTime {
            0% {
                background-color: #cce6fd;
            }

            50% {
                background-color: #252645;
            }

            100% {
                background-color: #cce6fd;
            }
        }

        @keyframes puf {
            0% {
                bottom: 0px;
            }

            25% {
                bottom: -1px;
            }

            100% {
                bottom: 5px;
            }
        }

        @keyframes wind {
            0% {
                right: 0;
            }

            100% {
                right: 110%;
            }
        }

        @keyframes spin {
            100% {
                -webkit-transform: rotate(360deg);
                transform: rotate(380deg);
            }
        }

        /* move the whole train from left off-screen to right off-screen */
        @keyframes moveTrain {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(calc(100vw + 800px));
            }
        }

        /* smoke puff float */
        @keyframes smoke {
            0% {
                transform: translateY(0) scale(0.6);
                opacity: 0.9;
            }

            60% {
                transform: translateY(-40px) scale(1.2);
                opacity: 0.45;
            }

            100% {
                transform: translateY(-80px) scale(1.8);
                opacity: 0;
            }
        }

        /* Styles de la Modale (Modal) */
        .modal-overlay {
            /* Style pour masquer par défaut */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--couleur-glass-bg);
            /* Fond semi-transparent */
            backdrop-filter: blur(5px);
            /* Effet Glassmorphism sur le fond */
            display: none;
            /* Masqué par défaut */
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
            /* Afficher lorsque la classe active est ajoutée par JS */
        }

        .modal-content {
            background-color: var(--couleur-form-glass-bg);
            /* Fond moins transparent pour la modale */
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 8px 32px var(--couleur-glass-shadow-dark);
            /* Ombre externe */
            border: 1px solid var(--couleur-glass-border);
            /* Bordure légère */
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
            /* Jaune or pour titre */
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
            /* Rouge pour le bouton de fermeture */
            transition: color 0.2s;
        }

        .close-btn:hover {
            color: var(--couleur-accent-1-survol);
        }

        /* Style du Formulaire dans la Modale */
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
            /* Inclusion du padding dans la largeur */
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
            /* Le bouton prend toute la largeur disponible */
        }

        /* Responsive pour les petits écrans */
        @media (max-width: 600px) {
            .train-input-line {
                flex-direction: column;
                gap: 15px;
            }

            .train-form-group {
                width: 100%;
            }

            .train-form {
                padding: 20px;
            }

            .modal-content {
                padding: 15px;
            }
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            // 1. Définition des éléments du DOM
            const $departInput = $('#train-depart');
            const $arriveeInput = $('#train-arrive');
            const $arriveeDatalist = $('#train-arrive-list');
            const $dateDatalist = $('#train-dateD-list');
            const $heureDatalist = $('#train-heureD-list');
            // NOUVEAU : Référence à la datalist de la compagnie
            const $compagnieDatalist = $('#train-compagnie-list');

            const typeTransport = 'Train'; // Constante pour le type de transport

            // FONCTION D'AIDE : Formate la date SQL (AAAA-MM-JJ) en format affichable (JJ/MM/AAAA)
            function formatDisplayDate(dateString) {
                const parts = dateString.split('-');
                if (parts.length === 3) {
                    return `${parts[2]}/${parts[1]}/${parts[0]}`;
                }
                return dateString;
            }

            // -----------------------------------------------------------------
            // FONCTION 1 : Mise à jour de la liste des VILLES D'ARRIVÉE (Déclencheur : Changement de DÉPART)
            // Ajout de logs de debug et calcul dynamique du basePath pour éviter les 404
            // -----------------------------------------------------------------
            const _projBase = (function(){
                // tente de détecter le nom du dossier du projet dans le pathname (fallback explicite)
                const match = window.location.pathname.match(/^.*?(\/projet-agence-de-voyage)/i);
                if (match && match[1]) return window.location.origin + match[1];
                // fallback: si le projet est servi depuis la racine localhost
                return window.location.origin + '/projet-agence-de-voyage';
            })();
            function updateArrivalCities() {
                const depart = $departInput.val();

                // 1. Réinitialiser l'arrivée, la date, l'heure et la compagnie (et vider leurs listes)
                $arriveeDatalist.empty();
                $dateDatalist.empty();
                $heureDatalist.empty();
                $compagnieDatalist.empty(); // Vider la liste de la compagnie aussi
                $arriveeInput.val(''); // Très important pour forcer une nouvelle sélection

                if (depart) {
                    const urlArrivals = _projBase + '/app/api/trajet_arrivals.php';
                    console.log('[train] AJAX Arrivals ->', urlArrivals, { depart: depart, typeTransport: typeTransport });
                    $.ajax({
                        // Point d'API pour filtrer les arrivées en fonction du départ
                        url: urlArrivals,
                        method: 'GET',
                        data: {
                            depart: depart,
                            typeTransport: typeTransport
                        },
                        dataType: 'json',
                        success: function (response) {
                            if (response.success && response.arrivals.length > 0) {
                                // Remplir la datalist des arrivées filtrées
                                response.arrivals.forEach(function (city) {
                                    $arriveeDatalist.append(`<option value="${city}">${city}</option>`);
                                });
                            } else {
                                // Afficher un message si aucune destination trouvée pour ce départ
                                $arriveeDatalist.append('<option value="">Aucune destination disponible</option>');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('AJAX Erreur Arrivées:', status, error, xhr.responseText);
                            $arriveeDatalist.empty();
                            $arriveeDatalist.append('<option value="">Erreur de chargement</option>');
                        }
                    });
                }
            }

            // -----------------------------------------------------------------
            // FONCTION 2 : Mise à jour des listes DATE, HEURE, et COMPAGNIE (Déclencheur : Changement d'ARRIVÉE)
            // -----------------------------------------------------------------
            function updateDateAndHeureLists() {
                const depart = $departInput.val();
                const arrivee = $arriveeInput.val();

                // Réinitialiser la date, l'heure et la compagnie
                $dateDatalist.empty();
                $heureDatalist.empty();
                $compagnieDatalist.empty(); // Réinitialisation de la compagnie

                // Lancer la requête uniquement si Départ ET Arrivée sont sélectionnés
                if (depart && arrivee) {
                    // --- A. MISE À JOUR DES DATES ET HEURES ---
                    const urlDates = _projBase + '/app/api/trajet_dates.php';
                    console.log('[train] AJAX Dates ->', urlDates, { depart: depart, arrivee: arrivee });
                    $.ajax({
                        // Point d'API pour filtrer les dates et heures
                        url: urlDates,
                        method: 'GET',
                        data: {
                            depart: depart,
                            arrivee: arrivee,
                            typeTransport: typeTransport
                        },
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {

                                // 1. Remplir la liste des dates
                                response.dates.forEach(function (date) {
                                    const displayDate = formatDisplayDate(date);
                                    // Valeur technique (value) est en format SQL (AAAA-MM-JJ) pour la recherche
                                    $dateDatalist.append(`<option value="${date}">${displayDate}</option>`);
                                });

                                // 2. Remplir la liste des heures
                                response.heures.forEach(function (heure) {
                                    // Affichage et valeur technique en HH:MM
                                    const formattedHeure = heure.substring(0, 5);
                                    $heureDatalist.append(`<option value="${formattedHeure}">${formattedHeure}</option>`);
                                });
                            } else {
                                console.error('AJAX Erreur Dates/Heures:', response.error);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('AJAX Erreur Dates/Heures:', status, error);
                        }
                    });

                    // --- B. MISE À JOUR DES COMPAGNIES ---
                    updateCompanyList(depart, arrivee);
                }
            }

            // -----------------------------------------------------------------
            // FONCTION 3 : Mise à jour de la liste des COMPAGNIES (Appelée par Fonction 2)
            // -----------------------------------------------------------------
            function updateCompanyList(depart, arrivee) {
                $compagnieDatalist.empty(); // Assurez-vous qu'elle est vide avant de charger

                const urlCompanies = _projBase + '/app/api/trajet_compagnies.php';
                console.log('[train] AJAX Companies ->', urlCompanies, { depart: depart, arrivee: arrivee });
                $.ajax({
                    // Point d'API pour filtrer les compagnies
                    url: urlCompanies,
                    method: 'GET',
                    data: {
                        depart: depart,
                        arrivee: arrivee,
                        typeTransport: typeTransport
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success && response.companies.length > 0) {
                            response.companies.forEach(function (company) {
                                $compagnieDatalist.append(`<option value="${company}">${company}</option>`);
                            });
                        } else if (response.success) {
                            // Option par défaut si aucune compagnie trouvée (ne devrait pas arriver si dates/heures sont trouvées)
                            $compagnieDatalist.append('<option value="">Toutes Compagnies</option>');
                        } else {
                            console.error('AJAX Erreur Compagnies:', response.error);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX Erreur Compagnies:', status, error, xhr.responseText);
                    }
                });
            }


            // -----------------------------------------------------------------
            // GESTION DES ÉVÉNEMENTS
            // -----------------------------------------------------------------

            // 1. Filtrer les villes d'arrivée dès que le départ change
            $departInput.on('change', updateArrivalCities);

            // 2. Filtrer les dates, heures, ET COMPAGNIES dès que l'arrivée change
            $arriveeInput.on('change', updateDateAndHeureLists);
        });
    </script>


</head>

<body>
        <?php include __DIR__ . '/layout/header.php'; ?>
    <!-- <header>
        <nav class="train-nav">
            <div class="train-logo">
                <h1>FLEX <span>TRAVEL</span></h1>
            </div>
            <div class="train-links">
                <div class="train-link"><a href="#">HOME</a></div>
                <div class="train-link"><a href="#">BUS</a></div>
                <div class="train-link"><a href="#">AVION</a></div>
                <div class="train-link train-active"><a href="#">TRAIN</a></div>
            </div>
            <div class="train-button-group">
                <button class="train-btn-login">LOG IN</button>
                <button class="train-btn-signup">SIGN UP</button>
            </div>
            <div class="train-menu-icon" id="menu-Icon">
                <i class="fa-solid fa-bars"></i>
            </div>
        </nav>
    </header> -->

    <div class="train-main-container">
        <section class="train-gauche">
            <div class="train-content">
                <h1 data-aos="fade-right" data-aos-duration="2000" data-aos-delay="500">Welcome</h1>
                <h2 data-aos="fade-left" data-aos-duration="2000" data-aos-delay="1000">To <span>FLEX TRAVEL</span></h2>
                <p data-aos="fade-up" data-aos-duration="2000" data-aos-delay="500">
                    Réservez votre billet de Train en ligne,<br> Facilement et en toute Sécurité.
                </p>

                <div class="train-form-wrapper" data-aos="fade-up" data-aos-duration="1500" data-aos-delay="800">
                    <?php if ($load_error): ?>
                    <div style="background-color: #fee; color: #c00; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <strong>⚠️ Erreur de chargement :</strong> <?php echo htmlspecialchars($load_error); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (empty($villes_depart)): ?>
                    <div style="background-color: #fef3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <strong>⚠️ Aucune ville de départ :</strong> Veuillez vérifier que des trajets Train existent en base de données.
                        <?php if (!empty($all_trajets_debug)): ?>
                        <br><small><strong>Debug:</strong> Trajets trouvés en base (tous types) : <?php echo count($all_trajets_debug); ?> (filtrés : <?php echo count($villes_depart); ?>)</small>
                        <br><small>Types : <?php echo implode(', ', array_unique(array_column($all_trajets_debug, 'type'))); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <form action="#" class="train-form" id="trainForm">
                        <!-- Hidden transport identifiers (populated from DB) -->
                        <input type="hidden" name="id_transport" id="train-id-transport" value="<?php echo $id_transport_value; ?>">
                        <input type="hidden" name="type_transport" id="train-type-transport" value="Train">

                        <div class="train-input-line">
                            <div class="train-form-group">
                                <label for="train-depart">Départ</label>
                                <input list="train-depart-list" name="departD" id="train-depart"
                                    placeholder="Lieu de Départ" require>
                                <datalist id="train-depart-list">
                                    <?php foreach ($villes_depart as $ville): ?>
                                    <option value="<?php echo htmlspecialchars($ville); ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="train-form-group">
                                <label for="train-arrive">Arrivée</label>
                                <input list="train-arrive-list" name="arriveA" id="train-arrive"
                                    placeholder="Lieu D'Arrivée">
                                <datalist id="train-arrive-list">
                                </datalist>
                            </div>
                        </div>

                        <div class="train-input-line">
                            <div class="train-form-group">
                                <label for="train-dateD">Date Départ</label>
                                <input list="train-dateD-list" name="dateD" id="train-dateD" placeholder="jj/mm/aaaa">
                                <datalist id="train-dateD-list">
                                </datalist>
                            </div>
                            <div class="train-form-group">
                                <label for="train-heureD">Heure Départ</label>
                                <input list="train-heureD-list" name="heureD" id="train-heureD"
                                    placeholder="Heure Départ">
                                <datalist id="train-heureD-list">
                                </datalist>
                            </div>
                        </div>

                        <div class="train-input-line">
                            <div class="train-form-group">
                                <label for="train-compagnie">Compagnie</label>
                                <input list="train-compagnie-list" name="compagnie" id="train-compagnie"
                                    placeholder="Nom de la Compagnie">

                                <datalist id="train-compagnie-list">
                                    <?php 
      
                                       if (isset($compagnies) && is_array($compagnies)): 
                                    ?>
                                    <?php foreach ($compagnies as $company_name): ?>
                                    <option value="<?php echo htmlspecialchars($company_name); ?>"></option>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </datalist>
                            </div>
                            <div class="train-form-group">
                                <label for="train-classe">Classe</label>
                                <input list="train-classe-list" name="classe" id="train-classe"
                                    placeholder="Votre Classe de Siège">
                                <datalist id="train-classe-list">
                                    <?php foreach ($classes as $class_name): ?>
                                    <option value="<?php echo htmlspecialchars($class_name); ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>

                        <div class="train-form-button">
                            <button type="submit" class="train-buy-btn">RECHERCHER BILLETS</button>
                        </div>
                    </form>
                </div>
                <div id="authModal" class="modal-overlay">
                    <div class="modal-content">
                        <header class="modal-header">
                            <!-- <h3 class="modal-title">Veuillez Compléter Vos Informations</h3> -->
                            <button class="close-btn">&times;</button>
                        </header>
                        <form action="#" class="auth-form" id="authForm">
                            <div class="form-group">
                                <label for="auth-nom">Nom</label>
                                <input type="text" id="auth-nom" name="nom" required value="<?php echo htmlspecialchars($prefill_nom); ?>">
                            </div>
                            <div class="form-group">
                                <label for="auth-prenom">Prénom</label>
                                <input type="text" id="auth-prenom" name="prenom" required value="<?php echo htmlspecialchars($prefill_prenom); ?>">
                            </div>
                            <div class="form-group">
                                <label for="auth-type-piece">Type de Pièce d'Identité</label>
                                <select id="auth-type-piece" name="typePiece" required>
                                    <option value="" disabled selected>Choisir un type</option>
                                    <option value="Passeport">Passeport</option>
                                    <option value="CNI">CNI (Carte Nationale d'Identité)</option>
                                    <option value="Permis">Permis de Conduire</option>
                                    <!-- <option value="Autres">Autres</option> -->
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
        </section>

        <section id="train-img" class="train-droite" data-aos="fade-down-left" data-aos-duration="1000"
            data-aos-delay="800">
            <section class="stage">
                <div class="sky">
                    <div class="cloud"></div>
                    <div class="cloud"></div>
                </div>
                <div class="train">
                    <div class="wagon"></div>
                    <div class="wagon"></div>
                    <div class="wagon"></div>
                    <div class="locomotive">
                        <div class="cabin"></div>
                        <div class="motor"></div>
                        <div class="chinney">
                            <div class="smoke"></div>
                        </div>
                        <div class="light"></div>
                    </div>
                </div>
            </section>
            <!-- <img src="/projet-agence-de-voyage/public/img/tr1.png" alt="Image d'un train moderne en mouvement" id="train-img"> -->
        </section>


    </div>

    <!-- <script src="train.js"></script> -->
    <style>
        /* Page loader CSS (HUD-style neon segmented ring + vertical-line bar) */
        :root { --neon: #00f5ff; --neon-2: #00d0c7; --bg-dark: #000612; --seg-off: rgba(255,255,255,0.06); }
        #page-loader { position: fixed; inset: 0; background: radial-gradient(ellipse at center, rgba(0,0,0,0.96) 0%, rgba(0,0,0,0.99) 70%); display:flex; align-items:center; justify-content:center; z-index:99999; transition: opacity 0.6s ease, visibility 0.6s ease; }
        #page-loader.loaded { opacity:0; visibility:hidden; pointer-events:none; }
        .loader-inner { display:flex; gap:28px; align-items:center; color:var(--neon); }
        .loader-left{ width:160px; height:160px; position:relative; display:flex; align-items:center; justify-content:center }
        #ring-svg{ width:100%; height:100%; display:block }
        #ring-svg .seg{ fill: var(--seg-off); transition: fill 160ms ease, opacity 160ms ease; }
        #ring-svg .seg.on{ fill: var(--neon); filter: drop-shadow(0 0 10px var(--neon)); }
        .loader-perc{ position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:22px; color:#eaffff; text-shadow:0 0 10px rgba(0,245,255,0.25); font-family: 'Poppins', monospace; }
        .loader-right{ display:flex; flex-direction:column; gap:10px; align-items:flex-start }
        .bar-meta{ font-family: 'Poppins', monospace; font-weight:700; color:var(--neon); letter-spacing:2px; font-size:12px; text-transform:uppercase; text-shadow:0 0 8px rgba(0,245,255,0.12); }
        .bar-track{ display:flex; align-items:end; gap:6px; padding:8px; width:520px; max-width:66vw; height:56px; border-radius:8px; background: rgba(255,255,255,0.02); border:1px solid rgba(0,245,255,0.06); box-shadow:0 12px 40px rgba(0,245,255,0.03) inset }
        .bar-seg{ flex:0 0 8px; height:40%; background: rgba(255,255,255,0.06); border-radius:3px; transition: all 180ms ease; opacity:0.95 }
        .bar-seg.active{ height:100%; background: linear-gradient(180deg, var(--neon), var(--neon-2)); box-shadow:0 0 18px var(--neon); transform: translateY(0); animation: bar-pulse 1100ms ease-in-out infinite }
        @keyframes bar-pulse{ 0%{ transform: translateY(0) scaleY(1); } 50%{ transform: translateY(-6px) scaleY(1.03); } 100%{ transform: translateY(0) scaleY(1); } }
        .loading-text{ font-family: 'Poppins', monospace; color:var(--neon); font-weight:700; letter-spacing:1.5px; font-size:13px; margin-top:6px; }
        .loading-text{ text-shadow:0 0 6px rgba(0,245,255,0.12); animation: text-glow 1.8s linear infinite }
        @keyframes text-glow{ 0%{ text-shadow:0 0 6px rgba(0,245,255,0.06) } 50%{ text-shadow:0 0 18px rgba(0,245,255,0.6) } 100%{ text-shadow:0 0 6px rgba(0,245,255,0.06) } }
        @media (max-width:900px){ .loader-inner{ gap:16px } .bar-track{ width:320px } .loader-left{ width:120px;height:120px } }
    </style>

    <!-- Page Loader (HUD segmented ring + bar) -->
    <div id="page-loader" role="status" aria-live="polite">
        <div class="loader-inner">
            <div class="loader-left">
                <svg id="ring-svg" viewBox="0 0 100 100" aria-hidden="true"></svg>
                <div class="loader-perc" aria-hidden="false">0%</div>
            </div>
            <div class="loader-right">
                <div class="bar-meta">SYSTEM</div>
                <div id="bar-track" class="bar-track" aria-hidden="true"></div>
                <div class="loading-text">LOADING...</div>
            </div>
        </div>
    </div>
    <script>
        (function(){
            const loader = document.getElementById('page-loader');
            const ringSvg = document.getElementById('ring-svg');
            const barTrack = document.getElementById('bar-track');
            const percEl = document.querySelector('.loader-perc');
            if(!loader || !ringSvg || !barTrack || !percEl) return;

            const NUM_SEGS = 36;
            const NUM_BARS = 40;
            const minDisplay = 1500; // ms minimal visible time

            const segs = [];
            const cx = 50, cy = 50, r = 36, w = 4, h = 10;
            for(let i=0;i<NUM_SEGS;i++){
                const g = document.createElementNS('http://www.w3.org/2000/svg','rect');
                const a = (i/NUM_SEGS)*Math.PI*2 - Math.PI/2;
                const x = cx + Math.cos(a)*(r);
                const y = cy + Math.sin(a)*(r);
                g.setAttribute('x', x - w/2);
                g.setAttribute('y', y - h/2);
                g.setAttribute('width', w);
                g.setAttribute('height', h);
                g.setAttribute('rx', 2);
                g.setAttribute('ry', 2);
                g.setAttribute('transform', `rotate(${a*180/Math.PI} ${x} ${y})`);
                g.classList.add('seg');
                ringSvg.appendChild(g);
                segs.push(g);
            }

            const bars = [];
            for(let i=0;i<NUM_BARS;i++){
                const d = document.createElement('div');
                d.className = 'bar-seg';
                barTrack.appendChild(d);
                bars.push(d);
            }

            function render(p){
                const segOn = Math.round((p/100)*(NUM_SEGS-1));
                segs.forEach((s, idx)=> s.classList.toggle('on', idx<=segOn));
                const barOn = Math.round((p/100)*(NUM_BARS-1));
                bars.forEach((b, idx)=> b.classList.toggle('active', idx<=barOn));
                percEl.textContent = Math.min(100, Math.round(p)) + '%';
            }

            // fake progress until load
            let pct = 0; render(0);
            let lastTick = Date.now();
            const incInterval = setInterval(()=>{
                const now = Date.now();
                const dt = now - lastTick;
                lastTick = now;
                if(pct < 90) pct += Math.random()*0.8 + 0.4; // slow climb
                render(pct);
                if(pct >= 90) { clearInterval(incInterval); }
            }, 80);

            let finalized = false;
            function finalizeAndHide(){
                if(finalized) return; finalized = true;
                const t0 = Date.now();
                const finish = ()=>{
                    render(100);
                    const elapsed = Date.now() - t0;
                    const wait = Math.max(0, minDisplay - elapsed);
                    setTimeout(()=>{ loader.classList.add('loaded'); setTimeout(()=> loader.remove(), 650); }, wait);
                };
                // ramp to 100
                let ramp = 0;
                const rampI = setInterval(()=>{ ramp += 6 + Math.random()*6; render(Math.min(100, pct + ramp)); if(pct + ramp >= 100){ clearInterval(rampI); finish(); } }, 36);
            }

            window.addEventListener('load', ()=> finalizeAndHide());
            // safety fallback
            setTimeout(()=> finalizeAndHide(), 6000);
        })();
    </script>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();

        // Simple script pour le menu mobile 
        const menuIcon = document.getElementById('menu-Icon');
        const navLinks = document.querySelector('.train-links');
        const navButtons = document.querySelector('.train-button-group');

        menuIcon.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            navButtons.classList.toggle('active');
            menuIcon.querySelector('i').classList.toggle('fa-bars');
            menuIcon.querySelector('i').classList.toggle('fa-xmark'); // Changer l'icône en X
        });


        //Script de la modal

        document.addEventListener('DOMContentLoaded', () => {

            const trainForm = document.getElementById('trainForm');
            const authModal = document.getElementById('authModal');
            const closeBtn = authModal.querySelector('.close-btn');

            // Fonction pour afficher la modale
            function showModal(event) {
                event.preventDefault(); // Empêche la soumission normale du formulaire
                authModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            // Fonction pour masquer la modale
            function hideModal() {
                authModal.classList.remove('active');
                document.body.style.overflow = '';
            }

            // GESTIONNAIRES D'ÉVÉNEMENTS

            // 1. Événement de soumission du formulaire
            if (trainForm) {
                trainForm.addEventListener('submit', showModal);
            }

            // 2. Bouton de fermeture (X) de la modale
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

            // Gerer la soumission du formulaire d'authentification
            const authForm = document.getElementById('authForm');
            if (authForm) {
                // Calculer la base du projet (comme plus haut) pour former l'URL de redirection
                const _projBaseModal = (function(){
                    const match = window.location.pathname.match(/^.*?(\/projet-agence-de-voyage)/i);
                    if (match && match[1]) return window.location.origin + match[1];
                    return window.location.origin + '/projet-agence-de-voyage';
                })();

                authForm.addEventListener('submit', (event) => {
                    event.preventDefault();

                    // Récupérer les valeurs de la modale (nom/prénom etc.)
                    const nom = document.getElementById('auth-nom').value.trim();
                    const prenom = document.getElementById('auth-prenom').value.trim();
                    const typePiece = document.getElementById('auth-type-piece').value;
                    const numPiece = document.getElementById('auth-num-piece').value.trim();

                    // Validation simple côté client
                    if (!nom || !prenom || !typePiece || !numPiece) {
                        alert('Veuillez remplir tous les champs du passager avant de continuer.');
                        return;
                    }

                    // Optionnel: fermer la modale (pour UX) puis rediriger vers la page de paiement
                    hideModal();

                    // Construire une description du produit depuis les champs sélectionnés (départ/arrivée/date/heure)
                    const departField = document.getElementById('train-depart').value || '';
                    const arriveField = document.getElementById('train-arrive').value || '';
                    const dateField = document.getElementById('train-dateD').value || '';
                    const heureField = document.getElementById('train-heureD').value || '';
                    const compagnieField = document.getElementById('train-compagnie').value || '';
                    const classeField = document.getElementById('train-classe').value || '';

                    const productName = `Billet Train ${departField}→${arriveField} ${dateField} ${heureField} (${classeField})`;
                    // Montant de démonstration (en FCFA) — vous pouvez remplacer par un calcul réel
                    const demoAmount = 5000;

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
                        type_transport: typeTransport,
                        id_transport: document.getElementById('train-id-transport').value || ''
                    });

                    // Rediriger vers la passerelle de paiement interne (app/api)
                    const paymentUrl = _projBaseModal + '/app/api/Payment_Gateway.html' + '?' + params.toString();
                    window.location.href = paymentUrl;
                });
            }
        });
    </script>
    </script>
</body>

</html>