<?php
/**
 * chat-api.php - API REST pour le chatbot
 * Endpoint pour intégrer le chatbot dans votre application existante
 * 
 * Utilisation:
 * POST /chat-api.php
 * {
 *   "message": "Bonjour!",
 *   "provider": "mistral",
 *   "conversation_id": "user_123"
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

// Charger la configuration et librairie
require_once '../app/lib/ChatbotLibrary.php';

if (file_exists('../.env')) {
    $env = parse_ini_file('../.env');
    $_ENV = array_merge($_ENV, $env);
}

// Gérer les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Seulement POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Parser les données
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input && isset($_POST)) {
        $input = $_POST;
    }
    
    $message = $input['message'] ?? '';
    $provider = $input['provider'] ?? $_ENV['CHATBOT_DEFAULT_PROVIDER'] ?? 'mistral';
    $conversationId = $input['conversation_id'] ?? null;
    $model = $input['model'] ?? null;
    $options = $input['options'] ?? [];
    
    // Validation
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message required']);
        exit;
    }
    
    if (strlen($message) > 2000) {
        http_response_code(400);
        echo json_encode(['error' => 'Message too long (max 2000 chars)']);
        exit;
    }
    
    // Récupérer l'historique si conversation_id fourni
    $conversationHistory = [];
    if ($conversationId) {
        $conversationHistory = getConversationHistory($conversationId);
    }
    
    // Sélectionner le provider
    $apiKey = getApiKeyForProvider($provider);
    
    if (empty($apiKey)) {
        http_response_code(503);
        echo json_encode([
            'error' => "Provider '$provider' not configured",
            'available_providers' => getAvailableProviders()
        ]);
        exit;
    }
    
    // Créer le chatbot
    $chatbot = new ChatbotManager($provider, $apiKey);
    
    // Paramètres par défaut
    $chatOptions = array_merge([
        'system_prompt' => $_ENV['CHATBOT_SYSTEM_PROMPT'] ?? 
            "Tu es un assistant client pour une agence de voyage. Aide les clients à réserver des trajets.",
        'temperature' => floatval($_ENV['CHATBOT_TEMPERATURE'] ?? 0.7),
        'max_tokens' => intval($_ENV['CHATBOT_MAX_TOKENS'] ?? 512),
        'top_p' => floatval($_ENV['CHATBOT_TOP_P'] ?? 0.95)
    ], $options);
    
    if ($model) {
        $chatOptions['model'] = $model;
    }
    
    // Faire la requête
    $startTime = microtime(true);
    
    $result = $chatbot->chat($message, $conversationHistory, $chatOptions);
    
    $latency = round((microtime(true) - $startTime) * 1000); // en ms
    
    // Si échec et fallback activé
    if (!$result['success'] && ($_ENV['ENABLE_FALLBACK'] ?? false)) {
        $providers = array_filter([
            'mistral' => getApiKeyForProvider('mistral'),
            'openai' => getApiKeyForProvider('openai'),
            'hugging_face' => getApiKeyForProvider('hugging_face')
        ]);
        
        $result = $chatbot->chatWithFallback($message, $providers);
    }
    
    // Sauvegarder si réussi
    if ($result['success'] && $conversationId) {
        saveConversationMessage(
            $conversationId,
            $message,
            $result['response'],
            $provider,
            $result['total_tokens'] ?? $result['tokens_used'] ?? 0,
            $result['cost_usd'] ?? null
        );
    }
    
    // Préparer la réponse
    $response = [
        'success' => $result['success'],
        'response' => $result['response'] ?? null,
        'error' => $result['error'] ?? null,
        'provider' => $result['provider_used'] ?? $provider,
        'model' => $result['model'] ?? null,
        'metadata' => [
            'latency_ms' => $latency,
            'tokens' => $result['total_tokens'] ?? $result['tokens_used'] ?? null,
            'cost_usd' => $result['cost_usd'] ?? null,
            'timestamp' => date('c')
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    http_response_code($result['success'] ? 200 : 500);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'success' => false
    ]);
}

// ============================================================================
// Fonctions Utilitaires
// ============================================================================

/**
 * Récupérer la clé API d'un provider
 */
function getApiKeyForProvider($provider) {
    $keys = [
        'openai' => $_ENV['OPENAI_API_KEY'] ?? null,
        'mistral' => $_ENV['MISTRAL_API_KEY'] ?? null,
        'hugging_face' => $_ENV['HUGGING_FACE_TOKEN'] ?? null,
        'together' => $_ENV['TOGETHER_API_KEY'] ?? null,
        'cohere' => $_ENV['COHERE_API_KEY'] ?? null,
        'google' => $_ENV['GOOGLE_API_KEY'] ?? null
    ];
    
    return $keys[$provider] ?? null;
}

/**
 * Lister les providers disponibles
 */
function getAvailableProviders() {
    $providers = [];
    
    if (!empty($_ENV['OPENAI_API_KEY'])) $providers[] = 'openai';
    if (!empty($_ENV['MISTRAL_API_KEY'])) $providers[] = 'mistral';
    if (!empty($_ENV['HUGGING_FACE_TOKEN'])) $providers[] = 'hugging_face';
    if (!empty($_ENV['TOGETHER_API_KEY'])) $providers[] = 'together';
    if (!empty($_ENV['COHERE_API_KEY'])) $providers[] = 'cohere';
    if (!empty($_ENV['GOOGLE_API_KEY'])) $providers[] = 'google';
    
    return $providers;
}

/**
 * Récupérer l'historique de conversation
 */
function getConversationHistory($conversationId) {
    try {
        $db = new PDO(
            'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . 
            ';dbname=' . ($_ENV['DB_NAME'] ?? 'voyage'),
            $_ENV['DB_USER'] ?? 'root',
            $_ENV['DB_PASSWORD'] ?? ''
        );
        
        $stmt = $db->prepare("
            SELECT user_message, bot_response 
            FROM chat_conversations 
            WHERE conversation_id = ? 
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        $stmt->execute([$conversationId]);
        
        $messages = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Inverser l'ordre pour avoir les messages les plus anciens d'abord
            array_unshift($messages, 
                ['role' => 'user', 'content' => $row['user_message']],
                ['role' => 'assistant', 'content' => $row['bot_response']]
            );
        }
        
        return array_slice($messages, 0, 40); // Max 20 échanges
        
    } catch (Exception $e) {
        error_log("Conversation history error: " . $e->getMessage());
        return [];
    }
}

/**
 * Sauvegarder un message de conversation
 */
function saveConversationMessage($conversationId, $userMessage, $botResponse, $provider, $tokensUsed, $cost) {
    try {
        $db = new PDO(
            'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . 
            ';dbname=' . ($_ENV['DB_NAME'] ?? 'voyage'),
            $_ENV['DB_USER'] ?? 'root',
            $_ENV['DB_PASSWORD'] ?? ''
        );
        
        $stmt = $db->prepare("
            INSERT INTO chat_conversations 
            (conversation_id, user_message, bot_response, provider, tokens_used, cost_usd, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $conversationId,
            $userMessage,
            $botResponse,
            $provider,
            $tokensUsed,
            $cost
        ]);
        
    } catch (Exception $e) {
        error_log("Failed to save conversation: " . $e->getMessage());
    }
}

// GET endpoint pour les stats
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'providers':
            echo json_encode(['providers' => getAvailableProviders()]);
            break;
        
        case 'health':
            $providers = getAvailableProviders();
            echo json_encode([
                'status' => 'ok',
                'providers' => $providers,
                'count' => count($providers)
            ]);
            break;
        
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action not found']);
    }
    exit;
}
?>
