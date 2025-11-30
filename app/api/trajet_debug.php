<?php
// /api/trajet_debug.php
// Debug endpoint: retourne les trajets bruts pour une ville de départ (sans filtrage de date/places)
header('Content-Type: application/json');
require_once __DIR__ . '/../models/Database.php';

if (!isset($_GET['depart'])) {
    echo json_encode(['success' => false, 'error' => 'Paramètre depart manquant']);
    exit;
}

$depart = trim(filter_input(INPUT_GET, 'depart', FILTER_SANITIZE_STRING));
$type = isset($_GET['type']) ? trim($_GET['type']) : null;

try {
    $pdo = getPDOConnection();
    $sql = "SELECT t.id_trajet, t.ville_depart, t.ville_arrivee, t.date_depart, t.heure_depart, a.capacite_totale, mt.nom_compagnie, mt.type
            FROM trajet t
            JOIN appareil a ON t.id_appareil = a.id_appareil
            JOIN moyen_transport mt ON a.id_transport = mt.id_transport
            WHERE LOWER(t.ville_depart) = LOWER(:depart)";
    if ($type) {
        $sql .= " AND mt.type = :type";
    }
    $sql .= " ORDER BY t.date_depart DESC, t.heure_depart DESC LIMIT 200";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':depart', $depart);
    if ($type) $stmt->bindParam(':type', $type);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // log
    @file_put_contents(__DIR__ . '/../logs/trajet_api.log', sprintf("[%s] debug request: %s -> %d rows\n", date('Y-m-d H:i:s'), json_encode(['depart'=>$depart,'type'=>$type]), count($rows)), FILE_APPEND | LOCK_EX);

    echo json_encode(['success' => true, 'count' => count($rows), 'rows' => $rows]);
} catch (Exception $e) {
    @file_put_contents(__DIR__ . '/../logs/trajet_api.log', sprintf("[%s] debug exception: %s\n", date('Y-m-d H:i:s'), $e->getMessage()), FILE_APPEND | LOCK_EX);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
