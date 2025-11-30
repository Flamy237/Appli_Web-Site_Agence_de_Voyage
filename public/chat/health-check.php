<?php
/**
 * Chat System - Health Check
 * Vérifier que tous les composants sont en place
 */

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Chat System Health Check</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }";
echo ".container { max-width: 800px; margin: 0 auto; }";
echo ".header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }";
echo ".check { padding: 15px; margin: 10px 0; border-left: 4px solid #ccc; background: white; border-radius: 4px; }";
echo ".check.ok { border-left-color: #48bb78; }";
echo ".check.warning { border-left-color: #ed8936; }";
echo ".check.error { border-left-color: #f56565; }";
echo ".status { font-weight: bold; margin-left: 10px; }";
echo ".ok .status { color: #48bb78; }";
echo ".warning .status { color: #ed8936; }";
echo ".error .status { color: #f56565; }";
echo ".section { margin-top: 30px; }";
echo ".section h2 { color: #2d3748; border-bottom: 2px solid #667eea; padding-bottom: 10px; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>✅ Chat System Health Check</h1>";
echo "<p>Vérification de l'installation du système de chat</p>";
echo "</div>";

$checks = [];

// 1. Fichiers Frontend
echo "<div class='section'>";
echo "<h2>📁 Fichiers Frontend</h2>";

$files = [
    'public/chat/index.html' => 'Page du chat',
    'public/chat/script.js' => 'Logique client',
    'public/chat/style.css' => 'Styles CSS',
    'public/chat/README.md' => 'Documentation',
    'public/chat/admin-dashboard.html' => 'Dashboard admin'
];

foreach ($files as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    $class = $exists ? 'ok' : 'error';
    $status = $exists ? '✅ OK' : '❌ MANQUANT';
    echo "<div class='check $class'>";
    echo "<strong>$desc</strong> <span class='status'>$status</span>";
    echo "<br><small>$file</small>";
    echo "</div>";
    $checks[] = $exists;
}

echo "</div>";

// 2. Fichiers Backend
echo "<div class='section'>";
echo "<h2>🔧 Fichiers Backend</h2>";

$files = [
    'app/api/chat.php' => 'API principale',
    'app/api/ChatLearningManager.php' => 'Gestionnaire apprentissage',
    'app/api/chat-admin.php' => 'API admin',
    'app/config/chat-config.php' => 'Configuration'
];

foreach ($files as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    $class = $exists ? 'ok' : 'error';
    $status = $exists ? '✅ OK' : '❌ MANQUANT';
    echo "<div class='check $class'>";
    echo "<strong>$desc</strong> <span class='status'>$status</span>";
    echo "<br><small>$file</small>";
    echo "</div>";
    $checks[] = $exists;
}

echo "</div>";

// 3. Logs & Data
echo "<div class='section'>";
echo "<h2>📊 Logs & Données</h2>";

$logsDir = __DIR__ . '/logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
    echo "<div class='check ok'>";
    echo "<strong>Répertoire logs</strong> <span class='status'>✅ CRÉÉ</span>";
    echo "</div>";
    $checks[] = true;
} else {
    echo "<div class='check ok'>";
    echo "<strong>Répertoire logs</strong> <span class='status'>✅ EXISTE</span>";
    echo "</div>";
    $checks[] = true;
}

$conversationFile = $logsDir . '/chat_conversations.json';
if (file_exists($conversationFile)) {
    $size = filesize($conversationFile);
    echo "<div class='check ok'>";
    echo "<strong>Historique conversations</strong> <span class='status'>✅ OK</span>";
    echo "<br><small>$size bytes</small>";
    echo "</div>";
    $checks[] = true;
} else {
    echo "<div class='check warning'>";
    echo "<strong>Historique conversations</strong> <span class='status'>⚠️ VIERGE</span>";
    echo "<br><small>Sera créé à la première conversation</small>";
    echo "</div>";
}

echo "</div>";

// 4. Configuration
echo "<div class='section'>";
echo "<h2>⚙️ Configuration</h2>";

$configFile = __DIR__ . '/app/config/chat-config.php';
if (file_exists($configFile)) {
    $config = require $configFile;
    echo "<div class='check ok'>";
    echo "<strong>Configuration chargée</strong> <span class='status'>✅ OK</span>";
    echo "<br><small>";
    echo "Langue défaut: " . ($config['chat']['default_language'] ?? 'N/A');
    echo " | Logging: " . ($config['logs']['enabled'] ? 'Activé' : 'Désactivé');
    echo " | Dashboard: " . ($config['admin']['dashboard_enabled'] ? 'Actif' : 'Inactif');
    echo "</small>";
    echo "</div>";
    $checks[] = true;
} else {
    echo "<div class='check error'>";
    echo "<strong>Configuration</strong> <span class='status'>❌ MANQUANTE</span>";
    echo "</div>";
    $checks[] = false;
}

echo "</div>";

// 5. API Test
echo "<div class='section'>";
echo "<h2>🔗 Test API</h2>";

if (file_exists($configFile) && file_exists(__DIR__ . '/app/api/chat.php')) {
    echo "<div class='check ok'>";
    echo "<strong>API Backend</strong> <span class='status'>✅ PRÊTE</span>";
    echo "<br><small>Endpoint: /app/api/chat.php</small>";
    echo "</div>";
    $checks[] = true;
} else {
    echo "<div class='check error'>";
    echo "<strong>API Backend</strong> <span class='status'>❌ INCOMPLÈTE</span>";
    echo "</div>";
    $checks[] = false;
}

echo "</div>";

// 6. Résumé
echo "<div class='section'>";
echo "<h2>📋 Résumé</h2>";

$total = count($checks);
$passed = array_sum($checks);
$percentage = ($passed / $total) * 100;

echo "<div class='check " . ($percentage === 100 ? 'ok' : ($percentage >= 75 ? 'warning' : 'error')) . "'>";
echo "<strong>Status Global</strong> <span class='status'>" . intval($percentage) . "% (" . $passed . "/" . $total . ")</span>";
echo "</div>";

if ($percentage === 100) {
    echo "<div style='background: #c6f6d5; border: 1px solid #9ae6b4; padding: 15px; border-radius: 8px; margin-top: 15px; color: #22543d;'>";
    echo "<strong>✅ Système complètement installé!</strong>";
    echo "<br>Accédez au chat: <a href='/public/chat/index.html'>/public/chat/index.html</a>";
    echo "<br>Dashboard admin: <a href='/public/chat/admin-dashboard.html'>/public/chat/admin-dashboard.html</a>";
    echo "</div>";
} else if ($percentage >= 75) {
    echo "<div style='background: #feebc8; border: 1px solid #fbd38d; padding: 15px; border-radius: 8px; margin-top: 15px; color: #7c2d12;'>";
    echo "<strong>⚠️ Presque prêt!</strong>";
    echo "<br>Créez les fichiers manquants listés ci-dessus.";
    echo "</div>";
} else {
    echo "<div style='background: #fed7d7; border: 1px solid #fc8181; padding: 15px; border-radius: 8px; margin-top: 15px; color: #742a2a;'>";
    echo "<strong>❌ Installation incomplète</strong>";
    echo "<br>Créez les fichiers manquants.";
    echo "</div>";
}

echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
