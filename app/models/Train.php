<?php
// /process/search_train.php

session_start();
// Inclure le modèle Trajet
require_once __DIR__ . '..\TrajetModel.php'; 

// Définir le type de transport à rechercher (pour cette page, c'est 'Train')
$type_transport = 'Train'; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // 1. Récupération et Nettoyage des données du formulaire
    $depart = filter_input(INPUT_POST, 'departD', FILTER_SANITIZE_STRING);
    $arrivee = filter_input(INPUT_POST, 'arriveA', FILTER_SANITIZE_STRING);
    $dateDepart = filter_input(INPUT_POST, 'dateD', FILTER_SANITIZE_STRING);
    // Validation spécifique pour s'assurer que c'est un entier
    $passagers = filter_input(INPUT_POST, 'nbpassage', FILTER_VALIDATE_INT);
    
    // Le champ 'classe' du formulaire n'est pas utilisé pour la recherche initiale, 
    // car on veut montrer toutes les classes disponibles pour les trajets trouvés.
    
    // 2. Validation basique
    if (empty($depart) || empty($arrivee) || empty($dateDepart) || $passagers === false || $passagers < 1) {
        $_SESSION['error_message'] = "Veuillez remplir tous les champs correctement.";
        header("Location: /train.php"); // Rediriger vers la page du formulaire
        exit;
    }

    // 3. Exécution de la recherche via le Modèle
    try {
        $trajetModel = new TrajetModel();
        
        // La méthode renvoie les trajets disponibles, combinés avec toutes les classes
        $resultats = $trajetModel->searchTrajets($depart, $arrivee, $dateDepart, $passagers, $type_transport);

        // 4. Gérer les résultats
        if (!empty($resultats)) {
            // Succès: Stocker les résultats en session et rediriger vers la page d'affichage
            $_SESSION['search_results'] = $resultats;
            $_SESSION['search_criteria'] = [
                'depart' => $depart, 
                'arrivee' => $arrivee, 
                'date' => $dateDepart, 
                'passagers' => $passagers
            ];
            header("Location: /train_results.php"); 
            exit;
        } else {
            // Aucun résultat trouvé
            $_SESSION['message'] = "Aucun trajet de " . $type_transport . " trouvé pour vos critères.";
            header("Location: /train.php");
            exit;
        }

    } catch (PDOException $e) {
        // En cas d'erreur de base de données (ex: erreur de syntaxe SQL)
        error_log("Erreur de recherche BDD: " . $e->getMessage());
        $_SESSION['error_message'] = "Une erreur serveur est survenue lors de la recherche.";
        header("Location: /train.php");
        exit;
    }

} else {
    // Empêcher l'accès direct au script de traitement
    header("Location: /train.php");
    exit;
}