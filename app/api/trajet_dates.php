<?php
// /api/trajet_dates.php

header('Content-Type: application/json');
// Le chemin d'accès à votre modèle doit être correct
require_once __DIR__ . '/../models/TrajetModel.php'; 

$type_transport = isset($_GET['typeTransport']) ? filter_input(INPUT_GET, 'typeTransport', FILTER_SANITIZE_STRING) : 'Train';

if (isset($_GET['depart']) && isset($_GET['arrivee'])) {
    
    $depart = filter_input(INPUT_GET, 'depart', FILTER_SANITIZE_STRING);
    $arrivee = filter_input(INPUT_GET, 'arrivee', FILTER_SANITIZE_STRING);

    // log incoming request
    @file_put_contents(__DIR__ . '/../logs/trajet_api.log', sprintf("[%s] dates request: %s\n", date('Y-m-d H:i:s'), json_encode(['depart'=>$depart,'arrivee'=>$arrivee,'type'=>$type_transport])), FILE_APPEND | LOCK_EX);

    try {
        $trajetModel = new TrajetModel();
        
        // CORRECTION CLÉ : Appeler la méthode du Modèle
        $results = $trajetModel->getDatesAndTimes($depart, $arrivee, $type_transport);

        // If no dates found (all dates might be in the past), fallback to no-date variant so UI can show historic dates for debug
        if (empty($results['dates']) && empty($results['heures'])) {
            @file_put_contents(__DIR__ . '/../logs/trajet_api.log', sprintf("[%s] dates empty -> fallback no-date for %s->%s\n", date('Y-m-d H:i:s'), $depart, $arrivee), FILE_APPEND | LOCK_EX);
            $results = $trajetModel->getDatesAndTimesNoDate($depart, $arrivee, $type_transport);
        }

        // La réponse JSON devrait contenir les tableaux 'dates' et 'heures'
        $out = ['success' => true, 'dates' => $results['dates'], 'heures' => $results['heures']];
        @file_put_contents(__DIR__ . '/../logs/trajet_api.log', sprintf("[%s] dates response: %s\n", date('Y-m-d H:i:s'), json_encode($out)), FILE_APPEND | LOCK_EX);
        echo json_encode($out);

    } catch (Exception $e) { 
        // En cas d'erreur (ex: problème de BDD ou modèle non trouvé)
        error_log("API Error in trajet_dates.php: " . $e->getMessage());
        @file_put_contents(__DIR__ . '/../logs/trajet_api.log', sprintf("[%s] dates exception: %s\n", date('Y-m-d H:i:s'), $e->getMessage()), FILE_APPEND | LOCK_EX);
        echo json_encode(['success' => false, 'error' => 'Erreur du serveur: ' . $e->getMessage(), 'exception' => $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'error' => 'Paramètres de départ ou d\'arrivée manquants.']);
}
?>