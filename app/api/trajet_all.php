<?php
// /api/trajet_all.php - Debug endpoint: retourne TOUS les trajets sans filtrage
header('Content-Type: application/json');
require_once __DIR__ . '/../models/Database.php';

try {
    $pdo = getPDOConnection();
    
    // Requête simple : tous les trajets avec détails
    $sql = "SELECT t.id_trajet, t.ville_depart, t.ville_arrivee, t.date_depart, t.heure_depart, 
                   a.capacite_totale, mt.nom_compagnie, mt.type,
                   COALESCE(COUNT(b.id_billet), 0) as places_vendues
            FROM trajet t
            JOIN appareil a ON t.id_appareil = a.id_appareil
            JOIN moyen_transport mt ON a.id_transport = mt.id_transport
            LEFT JOIN billet b ON t.id_trajet = b.id_trajet
            GROUP BY t.id_trajet
            ORDER BY t.date_depart DESC, t.heure_depart DESC 
            LIMIT 100";
    
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log
    @file_put_contents(__DIR__ . '/../logs/trajet_api.log', sprintf("[%s] all trajets: %d rows\n", date('Y-m-d H:i:s'), count($rows)), FILE_APPEND | LOCK_EX);
    
    echo json_encode(['success' => true, 'count' => count($rows), 'rows' => $rows]);
    
} catch (Exception $e) {
    @file_put_contents(__DIR__ . '/../logs/trajet_api.log', sprintf("[%s] all trajets exception: %s\n", date('Y-m-d H:i:s'), $e->getMessage()), FILE_APPEND | LOCK_EX);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
