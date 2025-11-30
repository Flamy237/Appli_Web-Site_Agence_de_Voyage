<?php
session_start();
$base = '/projet-agence-de-voyage/public/';
// Clear session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}
session_destroy();
// Redirect to home
header('Location: ' . $base . 'Home.php');
exit;
 