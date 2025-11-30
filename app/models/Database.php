<?php

// Définir les informations de connexion (À adapter à votre environnement)
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'agences');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

/**
 * Retourne l'objet PDO pour la connexion à la base de données.
 * @return PDO L'objet PDO.
 * @throws PDOException si la connexion échoue.
 */
function getPDOConnection(): PDO {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // En cas d'échec de connexion, on arrête l'exécution et on affiche l'erreur
        // (À personnaliser en production pour des messages d'erreur moins détaillés)
        throw new PDOException("Erreur de connexion à la BDD: " . $e->getMessage(), (int)$e->getCode());
    }
}