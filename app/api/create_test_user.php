<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../models/Database.php';

try {
    $pdo = getPDOConnection();

    // Utilisateur de test fixe
    $email = 'test.user+auto@example.com';
    $nom = 'Test';
    $prenom = 'User';
    $password = 'password';

    // Vérifier si l'utilisateur existe
    $stmt = $pdo->prepare('SELECT id_utilisateur FROM utilisateur WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if ($row && !empty($row['id_utilisateur'])) {
        echo json_encode(['success' => true, 'id_utilisateur' => (int)$row['id_utilisateur'], 'existing' => true]);
        exit;
    }

    // Insérer un utilisateur de test
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare('INSERT INTO utilisateur (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)');
    $ins->execute([$nom, $prenom, $email, $hash]);
    $id = $pdo->lastInsertId();

    echo json_encode(['success' => true, 'id_utilisateur' => (int)$id, 'existing' => false]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
