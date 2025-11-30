<?php
// /api/trajet_arrivals.php

header('Content-Type: application/json');
require_once __DIR__ . '/../models/TrajetModel.php'; 

$type_transport = isset($_GET['typeTransport']) ? filter_input(INPUT_GET, 'typeTransport', FILTER_SANITIZE_STRING) : 'Train';

if (isset($_GET['depart'])) {
    
    $depart = filter_input(INPUT_GET, 'depart', FILTER_SANITIZE_STRING);

    // Simple server-side logging for debugging
    try {
        $logLine = sprintf("[%s] arrivée request: %s\n", date('Y-m-d H:i:s'), json_encode(['depart'=>$depart,'type'=>$type_transport]));
        @file_put_contents(__DIR__ . '/../logs/trajet_api.log', $logLine, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) { /* ignore logging errors */ }

    try {
        $trajetModel = new TrajetModel();
        
        // Appel de la nouvelle méthode du Modèle
        $arrivals = $trajetModel->getAvailableArrivals($depart, $type_transport);

        // If nothing found (likely because dates are all in the past), fallback to no-date variant to help the UI populate
        if (empty($arrivals)) {
            @file_put_contents(__DIR__ . '/../logs/trajet_api.log', sprintf("[%s] arrivée empty -> fallback no-date for depart=%s\n", date('Y-m-d H:i:s'), $depart), FILE_APPEND | LOCK_EX);
            $arrivals = $trajetModel->getAvailableArrivalsNoDate($depart, $type_transport);
        }

        $out = ['success' => true, 'arrivals' => $arrivals];
        // log response
        @file_put_contents(__DIR__ . '/../logs/trajet_api.log', sprintf("[%s] arrivée response: %s\n", date('Y-m-d H:i:s'), json_encode($out)), FILE_APPEND | LOCK_EX);
        echo json_encode($out);

    } catch (Exception $e) { 
        error_log("API Error in trajet_arrivals.php: " . $e->getMessage());
        @file_put_contents(__DIR__ . '/../logs/trajet_api.log', sprintf("[%s] arrivée exception: %s\n", date('Y-m-d H:i:s'), $e->getMessage()), FILE_APPEND | LOCK_EX);
        echo json_encode(['success' => false, 'error' => 'Erreur du serveur lors de la recherche des arrivées.', 'exception' => $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'error' => 'Paramètre de départ manquant.']);
}
?>