<?php
// Returns JSON about the current session user (if any).
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
    echo json_encode(['logged' => true, 'user' => $_SESSION['user']]);
} else {
    echo json_encode(['logged' => false]);
}