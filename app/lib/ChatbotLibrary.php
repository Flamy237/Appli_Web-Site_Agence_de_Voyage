<?php
/**
 * Librairie complète d'intégration NLP/IA pour Chatbot PHP
 * Support de: OpenAI, Mistral, Hugging Face, Together, Cohere, Google
 */

// ============================================================================
// 1. HUGGING FACE INFERENCE API
// ============================================================================

class HuggingFaceChatbot {
    private $apiKey;
    private $endpoint = "https://router.huggingface.co/v1/chat/completions";
    private $model;
    
    public function __construct($apiKey, $model = "meta-llama/Llama-3-70b-chat-hf") {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }
    
    public function chat($userMessage, $conversationHistory = [], $options = []) {
        $messages = $conversationHistory;
        $messages[] = ["role" => "user", "content" => $userMessage];
        
        $payload = [
            "model" => $options['model'] ?? $this->model,
            "messages" => $messages,
            "temperature" => $options['temperature'] ?? 0.7,
            "max_tokens" => $options['max_tokens'] ?? 512,
            "top_p" => $options['top_p'] ?? 0.95,
            "stream" => false
        ];
        
        return $this->makeRequest($payload);
    }
    
    private function makeRequest($payload) {
        $ch = curl_init($this->endpoint);
        
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->apiKey}",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            return ["error" => "cURL Error: $curlError", "success" => false];
        }
        
        if ($httpCode !== 200) {
            return ["error" => "API Error ($httpCode): $response", "success" => false];
        }
        
        $decoded = json_decode($response, true);
        
        return [
            "success" => true,
            "response" => $decoded["choices"][0]["message"]["content"] ?? "",
            "tokens_used" => $decoded["usage"]["total_tokens"] ?? 0,
            "model" => $this->model
        ];
    }
}


// ============================================================================
// 2. OPENAI CHAT API
// ============================================================================

class OpenAIChatbot {
    private $apiKey;
    private $endpoint = "https://api.openai.com/v1/chat/completions";
    private $model = "gpt-4o-mini";
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    public function chat($userMessage, $systemPrompt = null, $conversationHistory = [], $options = []) {
        $messages = [];
        
        if ($systemPrompt) {
            $messages[] = ["role" => "system", "content" => $systemPrompt];
        }
        
        $messages = array_merge($messages, $conversationHistory);
        $messages[] = ["role" => "user", "content" => $userMessage];
        
        $payload = [
            "model" => $options['model'] ?? $this->model,
            "messages" => $messages,
            "temperature" => $options['temperature'] ?? 0.7,
            "max_tokens" => $options['max_tokens'] ?? 512,
            "top_p" => $options['top_p'] ?? 0.95
        ];
        
        return $this->makeRequest($payload);
    }
    
    private function makeRequest($payload) {
        $ch = curl_init($this->endpoint);
        
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->apiKey}",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            return ["error" => "cURL Error: $curlError", "success" => false];
        }
        
        if ($httpCode !== 200) {
            return ["error" => "API Error ($httpCode): $response", "success" => false];
        }
        
        $decoded = json_decode($response, true);
        
        return [
            "success" => true,
            "response" => $decoded["choices"][0]["message"]["content"] ?? "",
            "tokens_input" => $decoded["usage"]["prompt_tokens"] ?? 0,
            "tokens_output" => $decoded["usage"]["completion_tokens"] ?? 0,
            "total_tokens" => $decoded["usage"]["total_tokens"] ?? 0,
            "cost_usd" => $this->calculateCost(
                $decoded["usage"]["prompt_tokens"] ?? 0,
                $decoded["usage"]["completion_tokens"] ?? 0
            )
        ];
    }
    
    private function calculateCost($inputTokens, $outputTokens) {
        // GPT-4o mini: $0.15/M input, $0.6/M output
        $inputCost = ($inputTokens / 1000000) * 0.15;
        $outputCost = ($outputTokens / 1000000) * 0.6;
        return round($inputCost + $outputCost, 6);
    }
}


// ============================================================================
// 3. MISTRAL AI API
// ============================================================================

class MistralAIChatbot {
    private $apiKey;
    private $endpoint = "https://api.mistral.ai/v1/chat/completions";
    private $model = "mistral-small";
    
    public function __construct($apiKey, $model = "mistral-small") {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }
    
    public function chat($userMessage, $conversationHistory = [], $options = []) {
        $messages = $conversationHistory;
        $messages[] = ["role" => "user", "content" => $userMessage];
        
        $payload = [
            "model" => $options['model'] ?? $this->model,
            "messages" => $messages,
            "temperature" => $options['temperature'] ?? 0.7,
            "top_p" => $options['top_p'] ?? 1.0,
            "max_tokens" => $options['max_tokens'] ?? 1024,
            "safe_prompt" => false
        ];
        
        return $this->makeRequest($payload);
    }
    
    // Avec function calling
    public function chatWithTools($userMessage, $tools = [], $conversationHistory = [], $options = []) {
        $messages = $conversationHistory;
        $messages[] = ["role" => "user", "content" => $userMessage];
        
        $payload = [
            "model" => $options['model'] ?? $this->model,
            "messages" => $messages,
            "tools" => $tools,
            "tool_choice" => "auto",
            "temperature" => $options['temperature'] ?? 0.7,
            "max_tokens" => $options['max_tokens'] ?? 1024
        ];
        
        return $this->makeRequest($payload);
    }
    
    private function makeRequest($payload) {
        $ch = curl_init($this->endpoint);
        
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->apiKey}",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            return ["error" => "cURL Error: $curlError", "success" => false];
        }
        
        if ($httpCode !== 200) {
            return ["error" => "API Error ($httpCode): $response", "success" => false];
        }
        
        $decoded = json_decode($response, true);
        
        return [
            "success" => true,
            "response" => $decoded["choices"][0]["message"]["content"] ?? "",
            "tokens_prompt" => $decoded["usage"]["prompt_tokens"] ?? 0,
            "tokens_completion" => $decoded["usage"]["completion_tokens"] ?? 0,
            "total_tokens" => $decoded["usage"]["total_tokens"] ?? 0,
            "model" => $decoded["model"] ?? ""
        ];
    }
}


// ============================================================================
// 4. TOGETHER AI API
// ============================================================================

class TogetherAIChatbot {
    private $apiKey;
    private $endpoint = "https://api.together.xyz/v1/chat/completions";
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    public function chat($message, $model = "meta-llama/Llama-3-8b-chat-hf", 
                        $conversationHistory = [], $options = []) {
        $messages = $conversationHistory;
        $messages[] = ["role" => "user", "content" => $message];
        
        $payload = [
            "model" => $options['model'] ?? $model,
            "messages" => $messages,
            "temperature" => $options['temperature'] ?? 0.7,
            "max_tokens" => $options['max_tokens'] ?? 512,
            "top_p" => $options['top_p'] ?? 0.95,
            "top_k" => $options['top_k'] ?? 50,
            "repetition_penalty" => $options['repetition_penalty'] ?? 1.0,
            "stop" => ["\n\n"]
        ];
        
        return $this->makeRequest($payload);
    }
    
    private function makeRequest($payload) {
        $ch = curl_init($this->endpoint);
        
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->apiKey}",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            return ["error" => "cURL Error: $curlError", "success" => false];
        }
        
        if ($httpCode !== 200) {
            return ["error" => "API Error ($httpCode): $response", "success" => false];
        }
        
        $decoded = json_decode($response, true);
        
        return [
            "success" => true,
            "response" => $decoded["choices"][0]["message"]["content"] ?? "",
            "tokens_used" => $decoded["usage"]["total_tokens"] ?? 0,
            "model" => $decoded["model"] ?? ""
        ];
    }
}


// ============================================================================
// 5. COHERE API
// ============================================================================

class CohereChatbot {
    private $apiKey;
    private $endpoint = "https://api.cohere.com/v1/chat";
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    public function chat($message, $conversationHistory = []) {
        $payload = [
            "model" => "command-r-plus",
            "message" => $message,
            "chat_history" => $conversationHistory,
            "temperature" => 0.8,
            "max_tokens" => 512
        ];
        
        return $this->makeRequest($payload);
    }
    
    // RAG: Réponse basée sur des documents
    public function chatWithDocuments($message, $documents = []) {
        $payload = [
            "model" => "command-r-plus",
            "message" => $message,
            "documents" => $documents,
            "temperature" => 0.3,
            "max_tokens" => 512
        ];
        
        return $this->makeRequest($payload);
    }
    
    private function makeRequest($payload) {
        $ch = curl_init($this->endpoint);
        
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->apiKey}",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            return ["error" => "cURL Error: $curlError", "success" => false];
        }
        
        if ($httpCode !== 200) {
            return ["error" => "API Error ($httpCode): $response", "success" => false];
        }
        
        $decoded = json_decode($response, true);
        
        return [
            "success" => true,
            "response" => $decoded["text"] ?? "",
            "token_count" => $decoded["token_count"] ?? 0
        ];
    }
}


// ============================================================================
// 6. GOOGLE CLOUD NATURAL LANGUAGE
// ============================================================================

class GoogleNLPAnalyzer {
    private $apiKey;
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    // Analyse le sentiment d'un texte
    public function analyzeSentiment($text) {
        $endpoint = "https://language.googleapis.com/v1/documents:analyzeSentiment";
        
        $payload = [
            "document" => [
                "type" => "PLAIN_TEXT",
                "content" => $text,
                "language" => "fr"
            ],
            "encodingType" => "UTF8"
        ];
        
        $response = $this->makeRequest($endpoint, $payload);
        
        if (!isset($response["documentSentiment"])) {
            return ["error" => "Invalid response", "success" => false];
        }
        
        $sentiment = $response["documentSentiment"];
        
        return [
            "success" => true,
            "score" => $sentiment["score"] ?? 0,
            "magnitude" => $sentiment["magnitude"] ?? 0,
            "sentiment_level" => $this->getSentimentLevel($sentiment["score"] ?? 0),
            "sentences" => $response["sentences"] ?? []
        ];
    }
    
    // Reconnaissance d'entités nommées
    public function analyzeEntities($text) {
        $endpoint = "https://language.googleapis.com/v1/documents:analyzeEntities";
        
        $payload = [
            "document" => [
                "type" => "PLAIN_TEXT",
                "content" => $text,
                "language" => "fr"
            ],
            "encodingType" => "UTF8"
        ];
        
        return $this->makeRequest($endpoint, $payload);
    }
    
    private function makeRequest($endpoint, $payload) {
        $url = $endpoint . "?key=" . $this->apiKey;
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            return ["error" => "cURL Error: $curlError", "success" => false];
        }
        
        if ($httpCode !== 200) {
            return ["error" => "Google NLP Error: $response", "success" => false];
        }
        
        return json_decode($response, true);
    }
    
    private function getSentimentLevel($score) {
        if ($score >= 0.5) return "Très positif";
        if ($score >= 0.2) return "Positif";
        if ($score >= -0.2) return "Neutre";
        if ($score >= -0.5) return "Négatif";
        return "Très négatif";
    }
}


// ============================================================================
// 7. GESTIONNAIRE MULTI-PROVIDER
// ============================================================================

class ChatbotManager {
    private $provider;
    private $apiKey;
    private $config;
    
    public function __construct($provider, $apiKey, $config = []) {
        $this->provider = $provider;
        $this->apiKey = $apiKey;
        $this->config = $config;
    }
    
    public function chat($message, $conversationHistory = [], $options = []) {
        switch ($this->provider) {
            case 'openai':
                $chatbot = new OpenAIChatbot($this->apiKey);
                return $chatbot->chat($message, $options['system_prompt'] ?? null, $conversationHistory, $options);
            
            case 'mistral':
                $chatbot = new MistralAIChatbot($this->apiKey, $options['model'] ?? "mistral-small");
                return $chatbot->chat($message, $conversationHistory, $options);
            
            case 'hugging_face':
                $chatbot = new HuggingFaceChatbot($this->apiKey, $options['model'] ?? "meta-llama/Llama-3-70b-chat-hf");
                return $chatbot->chat($message, $conversationHistory, $options);
            
            case 'together':
                $chatbot = new TogetherAIChatbot($this->apiKey);
                return $chatbot->chat($message, $options['model'] ?? "meta-llama/Llama-3-8b-chat-hf", $conversationHistory, $options);
            
            case 'cohere':
                $chatbot = new CohereChatbot($this->apiKey);
                return $chatbot->chat($message, $conversationHistory);
            
            case 'google':
                $analyzer = new GoogleNLPAnalyzer($this->apiKey);
                return $analyzer->analyzeSentiment($message);
            
            default:
                return ["error" => "Provider not supported: $this->provider", "success" => false];
        }
    }
    
    // Fallback multi-provider
    public function chatWithFallback($message, $providers = []) {
        foreach ($providers as $provider => $apiKey) {
            try {
                $manager = new ChatbotManager($provider, $apiKey);
                $result = $manager->chat($message);
                if ($result['success']) {
                    return array_merge($result, ['provider_used' => $provider]);
                }
            } catch (Exception $e) {
                error_log("Provider $provider failed: " . $e->getMessage());
                continue;
            }
        }
        return ["error" => "All providers failed", "success" => false];
    }
}


// ============================================================================
// EXEMPLE D'UTILISATION
// ============================================================================

if (php_sapi_name() === 'cli') {
    // Test depuis CLI
    
    echo "=== Test des APIs NLP ===\n\n";
    
    // Test Mistral (recommandé - moins cher)
    if (getenv('MISTRAL_API_KEY')) {
        echo "1. Testing Mistral AI...\n";
        $mistral = new MistralAIChatbot(getenv('MISTRAL_API_KEY'));
        $result = $mistral->chat("Bonjour! Je veux réserver un bus pour demain.");
        echo "Réponse: " . ($result['response'] ?? $result['error']) . "\n";
        echo "Tokens: " . ($result['total_tokens'] ?? 'N/A') . "\n\n";
    }
    
    // Test OpenAI (production)
    if (getenv('OPENAI_API_KEY')) {
        echo "2. Testing OpenAI...\n";
        $openai = new OpenAIChatbot(getenv('OPENAI_API_KEY'));
        $result = $openai->chat("Je veux aller à Paris en bus");
        echo "Réponse: " . ($result['response'] ?? $result['error']) . "\n";
        echo "Coût: $" . ($result['cost_usd'] ?? 'N/A') . "\n\n";
    }
    
    // Test Hugging Face (gratuit)
    if (getenv('HUGGING_FACE_TOKEN')) {
        echo "3. Testing Hugging Face...\n";
        $hf = new HuggingFaceChatbot(getenv('HUGGING_FACE_TOKEN'));
        $result = $hf->chat("Comment réserver un billet?");
        echo "Réponse: " . ($result['response'] ?? $result['error']) . "\n\n";
    }
}
