<?php
// Simple debug viewer for auth log - remove this file in production
$log_file = __DIR__ . '/../includes/auth_debug.log';
if (file_exists($log_file)) {
    echo '<pre style="background:#f0f0f0;padding:10px;font-family:monospace;">';
    echo htmlspecialchars(file_get_contents($log_file));
    echo '</pre>';
    echo '<p><a href="?clear=1">Clear Log</a></p>';
    if (!empty($_GET['clear'])) {
        file_put_contents($log_file, '');
        echo 'Log cleared.';
    }
} else {
    echo 'No debug log found yet.';
}
?>
