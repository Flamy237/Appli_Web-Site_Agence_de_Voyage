<?php
// /api/trajet_compagnies.php

header('Content-Type: application/json');
require_once __DIR__ . '/../models/TrajetModel.php'; 

$type_transport = isset($_GET['typeTransport']) ? filter_input(INPUT_GET, 'typeTransport', FILTER_SANITIZE_STRING) : 'Train';

if (isset($_GET['depart']) && isset($_GET['arrivee'])) {
    
    $depart = filter_input(INPUT_GET, 'depart', FILTER_SANITIZE_STRING);
    $arrivee = filter_input(INPUT_GET, 'arrivee', FILTER_SANITIZE_STRING);

    @file_put_contents(__DIR__ . '/../logs/trajet_api.log', sprintf("[%s] companies request: %s\n", date('Y-m-d H:i:s'), json_encode(['depart'=>$depart,'arrivee'=>$arrivee,'type'=>$type_transport])), FILE_APPEND | LOCK_EX);

    try {
        $trajetModel = new TrajetModel();
        
        $companies = $trajetModel->getAvailableCompanies($depart, $arrivee, $type_transport);

        if (empty($companies)) {
            @file_put_contents(__DIR__ . '/../logs/trajet_api.log', sprintf("[%s] companies empty -> fallback no-date for %s->%s\n", date('Y-m-d H:i:s'), $depart, $arrivee), FILE_APPEND | LOCK_EX);
            $companies = $trajetModel->getAvailableCompaniesNoDate($depart, $arrivee, $type_transport);
        }

        $out = ['success' => true, 'companies' => $companies];
        @file_put_contents(__DIR__ . '/../logs/trajet_api.log', sprintf("[%s] companies response: %s\n", date('Y-m-d H:i:s'), json_encode($out)), FILE_APPEND | LOCK_EX);
        echo json_encode($out);

    } catch (Exception $e) { 
        error_log("API Error in trajet_compagnies.php: " . $e->getMessage());
        @file_put_contents(__DIR__ . '/../logs/trajet_api.log', sprintf("[%s] companies exception: %s\n", date('Y-m-d H:i:s'), $e->getMessage()), FILE_APPEND | LOCK_EX);
        echo json_encode(['success' => false, 'error' => 'Erreur du serveur.', 'exception' => $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants.']);
}
?>