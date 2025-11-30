<?php
session_start();

// Prévenir l'affichage d'erreurs HTML imprévues et logger les erreurs
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');
error_reporting(E_ALL);

// Bufferiser toute sortie accidentelle pour garder le JSON propre
if (!ini_get('output_buffering')) {
    ob_start();
} else {
    // s'assurer qu'on a un buffer actif
    if (ob_get_level() === 0) ob_start();
}

// Charger la librairie (chemin corrigé)
$chatLibPath = __DIR__ . '/../../app/lib/ChatbotLibrary.php';
if (file_exists($chatLibPath)) {
    require_once $chatLibPath;
} else {
    error_log("ChatbotLibrary not found at: $chatLibPath");
    // Ne pas exposer les détails en production — renvoyer une réponse JSON propre
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        if (ob_get_length()) ob_clean();
        echo json_encode(['error' => 'Internal server error', 'success' => false]);
        exit;
    }
}

// Configuration des APIs
$providers = [
    'mistral' => getenv('MISTRAL_API_KEY') ?? $_ENV['MISTRAL_API_KEY'] ?? '',
    'openai' => getenv('OPENAI_API_KEY') ?? $_ENV['OPENAI_API_KEY'] ?? '',
    'hugging_face' => getenv('HUGGING_FACE_TOKEN') ?? $_ENV['HUGGING_FACE_TOKEN'] ?? ''
];

// Récupérer le fournisseur préféré (ou utiliser le fallback)
$preferredProvider = $_GET['provider'] ?? 'mistral';

// Gestion des requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'chat') {
    header('Content-Type: application/json');
    // Nettoyer tout contenu déjà envoyé (warnings, espaces, BOM...) afin
    // de garantir que la réponse est un JSON valide.
    if (ob_get_length()) {
        ob_clean();
    }
    
    $userMessage = trim($_POST['message'] ?? '');
    
    if (empty($userMessage)) {
        echo json_encode(['error' => 'Message vide', 'success' => false]);
        exit;
    }
    
    // Récupérer l'historique de la conversation
    $conversationHistory = $_SESSION['conversation_history'] ?? [];
    
    // Limiter l'historique à 20 messages pour économiser les tokens
    if (count($conversationHistory) > 40) {
        $conversationHistory = array_slice($conversationHistory, -40);
    }
    
    // Créer le chatbot avec fallback
    $manager = new ChatbotManager($preferredProvider, $providers[$preferredProvider] ?? '');
    
    // Options de requête
    $options = [
        'system_prompt' => "Tu es un assistant client enthousiaste pour une agence de voyage. "
            . "Tu aides les clients à réserver des trajets en bus, train ou avion. "
            . "Sois amical, concis et utile. Propose des alternatives si nécessaire.",
        'temperature' => 0.7,
        'max_tokens' => 512
    ];
    
    try {
        // Essayer avec le fournisseur préféré, sinon utiliser le fallback
        $result = $manager->chat($userMessage, $conversationHistory, $options);
        
        // Si le fournisseur préféré échoue, utiliser le fallback
        if (!$result['success'] && count($providers) > 1) {
            $result = $manager->chatWithFallback($userMessage, array_filter($providers));
        }
        
        if ($result['success']) {
            // Ajouter à l'historique
            $_SESSION['conversation_history'][] = ['role' => 'user', 'content' => $userMessage];
            $_SESSION['conversation_history'][] = ['role' => 'assistant', 'content' => $result['response']];
            
            // Sauvegarder dans la base de données (optionnel)
            saveConversationToDatabase($userMessage, $result['response'], $result['model'] ?? 'unknown');
            
            if (ob_get_length()) ob_clean();
            echo json_encode([
                'success' => true,
                'response' => $result['response'],
                'provider' => $result['provider_used'] ?? $preferredProvider,
                'tokens' => $result['total_tokens'] ?? $result['tokens_used'] ?? null,
                'cost' => $result['cost_usd'] ?? null
            ]);
        } else {
            if (ob_get_length()) ob_clean();
            echo json_encode([
                'error' => $result['error'] ?? 'Erreur API',
                'success' => false
            ]);
        }
    } catch (Exception $e) {
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'error' => $e->getMessage(),
            'success' => false
        ]);
    }
    
    exit;
}

// Réinitialiser la conversation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset') {
    $_SESSION['conversation_history'] = [];
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true]);
    exit;
}

/**
 * Sauvegarder la conversation dans la base de données
 */
function saveConversationToDatabase($userMessage, $response, $model) {
    try {
        $db = new PDO('mysql:host=localhost;dbname=voyage', 'root', '');
        
        $stmt = $db->prepare("
            INSERT INTO chat_conversations (user_id, user_message, bot_response, model, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $userId = $_SESSION['user_id'] ?? null;
        $stmt->execute([$userId, $userMessage, $response, $model]);
    } catch (Exception $e) {
        // Silencieusement échouer - ne pas interrompre le chatbot
        error_log("Database save failed: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Agent de Voyage</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .chatbot-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 600px;
            display: flex;
            flex-direction: column;
            height: 90vh;
            max-height: 800px;
        }
        
        .chatbot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chatbot-header h1 {
            font-size: 1.5em;
            margin: 0;
        }
        
        .provider-selector {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .provider-selector select {
            padding: 8px 12px;
            border-radius: 5px;
            border: none;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            cursor: pointer;
        }
        
        .provider-selector select option {
            color: #333;
        }
        
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .message {
            display: flex;
            margin-bottom: 10px;
            animation: slideIn 0.3s ease-in-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message.user {
            justify-content: flex-end;
        }
        
        .message.bot {
            justify-content: flex-start;
        }
        
        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 12px;
            word-wrap: break-word;
        }
        
        .message.user .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message.bot .message-content {
            background: #f0f0f0;
            color: #333;
            border-bottom-left-radius: 4px;
        }
        
        .message-meta {
            font-size: 0.75em;
            color: #999;
            margin-top: 4px;
            text-align: right;
        }
        
        .message.user .message-meta {
            text-align: right;
        }
        
        .message.bot .message-meta {
            text-align: left;
        }
        
        .typing-indicator {
            display: flex;
            gap: 4px;
            align-items: center;
            padding: 12px 16px;
            background: #f0f0f0;
            border-radius: 12px;
            width: fit-content;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            background: #999;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }
        
        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
            }
            30% {
                transform: translateY(-10px);
            }
        }
        
        .chatbot-footer {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 10px;
        }
        
        .input-group {
            flex: 1;
            display: flex;
            gap: 10px;
        }
        
        .input-group input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .input-group button,
        .reset-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .input-group button:hover,
        .reset-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .input-group button:active,
        .reset-btn:active {
            transform: translateY(0);
        }
        
        .input-group button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .reset-btn {
            background: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px 16px;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        .info-badge {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75em;
            margin-top: 4px;
        }
        
        @media (max-width: 600px) {
            .chatbot-container {
                max-height: none;
                height: 100vh;
            }
            
            .message-content {
                max-width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="chatbot-container">
        <div class="chatbot-header">
            <h1>🚌 Assistant Voyage</h1>
            <div class="provider-selector">
                <select id="provider" onchange="changeProvider(this.value)">
                    <option value="mistral">Mistral (Rapide)</option>
                    <option value="openai">OpenAI (Pro)</option>
                    <option value="hugging_face">Hugging Face (Gratuit)</option>
                </select>
                <button class="reset-btn" onclick="resetConversation()">Réinitialiser</button>
            </div>
        </div>
        
        <div class="messages-container" id="messagesContainer">
            <div class="message bot">
                <div>
                    <div class="message-content">
                        Bonjour! 👋 Je suis votre assistant de voyage. Comment puis-je vous aider à réserver un trajet?
                    </div>
                </div>
            </div>
        </div>
        
        <div class="chatbot-footer">
            <div class="input-group">
                <input type="text" id="messageInput" placeholder="Votre message..." onkeypress="handleKeyPress(event)">
                <button onclick="sendMessage()" id="sendBtn">Envoyer</button>
            </div>
        </div>
    </div>
    
    <script>
        const messagesContainer = document.getElementById('messagesContainer');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        let isLoading = false;
        
        function sendMessage() {
            if (isLoading) return;
            
            const message = messageInput.value.trim();
            if (!message) return;
            
            // Ajouter le message utilisateur
            addMessage(message, 'user');
            messageInput.value = '';
            
            // Montrer l'indicateur de saisie
            showTypingIndicator();
            isLoading = true;
            sendBtn.disabled = true;
            
            // Envoyer la requête
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=chat&message=' + encodeURIComponent(message) + 
                      '&provider=' + encodeURIComponent(document.getElementById('provider').value)
            })
            .then(response => response.json())
            .then(data => {
                removeTypingIndicator();
                
                if (data.success) {
                    addMessage(data.response, 'bot', {
                        provider: data.provider,
                        tokens: data.tokens,
                        cost: data.cost
                    });
                } else {
                    addMessage('❌ Erreur: ' + (data.error || 'Erreur inconnue'), 'bot');
                }
            })
            .catch(error => {
                removeTypingIndicator();
                addMessage('❌ Erreur de connexion: ' + error.message, 'bot');
            })
            .finally(() => {
                isLoading = false;
                sendBtn.disabled = false;
                messageInput.focus();
            });
        }
        
        function addMessage(text, sender, meta = {}) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message ' + sender;
            
            let metaHtml = '';
            if (meta.provider) {
                metaHtml += `<span class="info-badge">${meta.provider}</span> `;
            }
            if (meta.tokens) {
                metaHtml += `<span class="info-badge">${meta.tokens} tokens</span> `;
            }
            if (meta.cost) {
                metaHtml += `<span class="info-badge">$${meta.cost}</span>`;
            }
            
            messageDiv.innerHTML = `
                <div>
                    <div class="message-content">${escapeHtml(text)}</div>
                    ${metaHtml ? '<div class="message-meta">' + metaHtml + '</div>' : ''}
                </div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        function showTypingIndicator() {
            const typeDiv = document.createElement('div');
            typeDiv.id = 'typingIndicator';
            typeDiv.className = 'message bot';
            typeDiv.innerHTML = '<div class="typing-indicator"><span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span></div>';
            messagesContainer.appendChild(typeDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        function removeTypingIndicator() {
            const typing = document.getElementById('typingIndicator');
            if (typing) typing.remove();
        }
        
        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }
        
        function resetConversation() {
            if (confirm('Êtes-vous sûr de vouloir réinitialiser la conversation?')) {
                fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=reset'
                })
                .then(() => {
                    messagesContainer.innerHTML = `
                        <div class="message bot">
                            <div>
                                <div class="message-content">
                                    Conversation réinitialisée. Comment puis-je vous aider?
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
        }
        
        function changeProvider(provider) {
            // Optionnel: vous pouvez ajouter un indicateur de changement
            console.log('Switched to provider:', provider);
        }
        
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
        
        // Focus sur l'input au chargement
        messageInput.focus();
    </script>
</body>
</html>
