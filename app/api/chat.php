<?php
/**
 * Chat API - Intelligence conversationnelle avec IA MISTRAL
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Charger la configuration
@require_once __DIR__ . '/../../config.php';

class ChatIA {
    private $conversationLog = array();
    private $logFile;
    private $pdo;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/../../logs/chat_conversations.json';
        $this->loadConversationHistory();
        $this->connectDB();
    }
    
    /**
     * Connecter à la base de données
     */
    private function connectDB() {
        try {
            require_once __DIR__ . '/../models/Database.php';
            $this->pdo = getPDOConnection();
        } catch (Exception $e) {
            error_log("Chat DB Error: " . $e->getMessage());
            $this->pdo = null;
        }
    }
    
    public function respond($question, $lang) {
        $greeting = $this->extractGreeting($question, $lang);
        $cleanQuestion = trim($question);
        $questionLower = strtolower($cleanQuestion);
        $keywords = $this->extractKeywords($questionLower);
        
        // PRIORITÉ 1: BD d'abord (données réelles!)
        $dbResponse = $this->getResponseFromDB($keywords, $questionLower, $lang);
        if ($dbResponse) {
            $response = $greeting . $dbResponse;
            $this->saveConversation($cleanQuestion, $response, 'database', $lang);
            return array(
                'response' => $response,
                'source' => 'database',
                'confidence' => 0.95
            );
        }
        
        // PRIORITÉ 2: Historique d'apprentissage
        $similarResponse = $this->findSimilarResponse($keywords, $lang);
        if ($similarResponse) {
            $response = $greeting . $similarResponse;
            return array(
                'response' => $response,
                'source' => 'learned',
                'confidence' => 0.9
            );
        }
        
        // PRIORITÉ 3: Patterns + IA Mistral
        $response = $this->generateResponse($keywords, $lang, $greeting, $questionLower);
        $this->saveConversation($cleanQuestion, $response['response'], $response['source'], $lang);
        return $response;
    }
    
    /**
     * Extraire les salutations
     */
    private function extractGreeting($text, $lang) {
        $greetings_fr = array(
            'bonjour' => 'Bonjour! 👋 ',
            'bonsoir' => 'Bonsoir! 🌙 ',
            'salut' => 'Salut! 😊 ',
            'coucou' => 'Coucou! 👋 ',
        );
        
        $greetings_en = array(
            'hello' => 'Hello! 👋 ',
            'hi' => 'Hi! 😊 ',
            'hey' => 'Hey! 👋 ',
        );
        
        $textLower = strtolower($text);
        $greetings = ($lang === 'fr') ? $greetings_fr : $greetings_en;
        
        foreach ($greetings as $word => $resp) {
            if (strpos($textLower, $word) !== false) {
                return $resp;
            }
        }
        return '';
    }
    
    /**
     * Extraire les mots-clés
     */
    private function extractKeywords($text) {
        $stopWords = array('le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'et', 'ou', 'mais', 'donc', 'car',
                     'je', 'tu', 'il', 'elle', 'nous', 'vous', 'ils', 'elles', 'me', 'te', 'se', 'moi', 'toi', 'soi',
                     'pour', 'avec', 'sans', 'en', 'a', 'by', 'on', 'in', 'is', 'it', 'the', 'an', 'and', 'or', 'but',
                     'bonjour', 'bonsoir', 'salut', 'hello', 'hi', 'coucou', 'donne', 'moi', 'que', 'quel', 'comment');
        
        $text = str_replace(array('-', '.', ',', '?', '!'), ' ', $text);
        $words = preg_split('/[\s]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        $keywords = array();
        foreach ($words as $word) {
            $word = strtolower(trim($word));
            if (strlen($word) > 1 && !in_array($word, $stopWords)) {
                $keywords[] = $word;
            }
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Calculer similarité Jaccard
     */
    private function calculateSimilarity($keywords1, $keywords2) {
        if (empty($keywords1) || empty($keywords2)) return 0;
        $common = count(array_intersect($keywords1, $keywords2));
        $total = count(array_unique(array_merge($keywords1, $keywords2)));
        return ($total > 0) ? ($common / $total) : 0;
    }
    
    /**
     * Chercher dans l'historique
     */
    private function findSimilarResponse($keywords, $lang) {
        if (empty($this->conversationLog) || empty($keywords)) return null;
        
        $bestMatch = null;
        $bestScore = 0.65;
        
        foreach ($this->conversationLog as $entry) {
            if ($entry['source'] === 'fallback') continue;
            
            $entryKeywords = $this->extractKeywords($entry['question']);
            $similarity = $this->calculateSimilarity($keywords, $entryKeywords);
            
            if ($similarity > $bestScore) {
                $bestScore = $similarity;
                $bestMatch = $entry['response'];
            }
        }
        
        return $bestMatch;
    }
    
    /**
     * ★★★ RÉCUPÉRER LES DONNÉES DE LA BD ★★★
     */
    private function getResponseFromDB($keywords, $questionLower, $lang) {
        if (!$this->pdo) return null;
        
        try {
            // ========================
            // LISTE DES TRAJETS
            // ========================
            if (in_array('liste', $keywords) || in_array('trajets', $keywords) || 
                in_array('trajet', $keywords) || in_array('voyages', $keywords) ||
                strpos($questionLower, 'liste') !== false || strpos($questionLower, 'donne') !== false) {
                
                $stmt = $this->pdo->query("
                    SELECT t.id_trajet, t.ville_depart, t.ville_arrivee, t.date_depart, 
                           t.heure_depart, t.prix, mt.type
                    FROM trajet t
                    JOIN appareil a ON t.id_appareil = a.id_appareil
                    JOIN moyen_transport mt ON a.id_transport = mt.id_transport
                    ORDER BY t.date_depart DESC
                    LIMIT 20
                ");
                
                $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($trajets)) {
                    $response = ($lang === 'fr') 
                        ? '<strong>' . count($trajets) . ' trajets disponibles:</strong><br>'
                        : '<strong>' . count($trajets) . ' available trips:</strong><br>';
                    
                    $response .= '<ul style="margin: 10px 0; padding-left: 20px;">';
                    
                    foreach ($trajets as $t) {
                        $response .= '<li>';
                        $response .= '<strong>' . htmlspecialchars($t['ville_depart']) . '</strong> ➜ ';
                        $response .= '<strong>' . htmlspecialchars($t['ville_arrivee']) . '</strong> | ';
                        $response .= $t['date_depart'] . ' ' . $t['heure_depart'] . ' | ';
                        $response .= '<strong>€' . number_format($t['prix'], 2) . '</strong>';
                        $response .= '</li>';
                    }
                    
                    $response .= '</ul>';
                    return $response;
                }
            }
            
            // ========================
            // TRAJETS POUR UNE VILLE
            // ========================
            foreach ($keywords as $keyword) {
                if (strlen($keyword) > 2) {
                    $stmt = $this->pdo->prepare("
                        SELECT t.id_trajet, t.ville_depart, t.ville_arrivee, t.date_depart, 
                               t.heure_depart, t.prix, mt.type
                        FROM trajet t
                        JOIN appareil a ON t.id_appareil = a.id_appareil
                        JOIN moyen_transport mt ON a.id_transport = mt.id_transport
                        WHERE (LOWER(t.ville_depart) LIKE :search OR LOWER(t.ville_arrivee) LIKE :search)
                        ORDER BY t.date_depart
                        LIMIT 15
                    ");
                    
                    $stmt->execute(array(':search' => '%' . strtolower($keyword) . '%'));
                    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($trajets)) {
                        $response = ($lang === 'fr')
                            ? '<strong>Trajets pour ' . htmlspecialchars($keyword) . ':</strong><br><ul style="padding-left: 20px;">'
                            : '<strong>Trips for ' . htmlspecialchars($keyword) . ':</strong><br><ul style="padding-left: 20px;">';
                        
                        foreach ($trajets as $t) {
                            $response .= '<li>' . $t['ville_depart'] . ' → ' . $t['ville_arrivee'] . 
                                       ' | ' . $t['date_depart'] . ' | €' . number_format($t['prix'], 2) . '</li>';
                        }
                        
                        $response .= '</ul>';
                        return $response;
                    }
                }
            }
            
            // ========================
            // COMPAGNIES
            // ========================
            if (in_array('compagnie', $keywords) || in_array('transporteur', $keywords)) {
                $stmt = $this->pdo->query("
                    SELECT DISTINCT mt.nom_compagnie, mt.type, COUNT(t.id_trajet) as nombre_trajets
                    FROM moyen_transport mt
                    LEFT JOIN appareil a ON mt.id_transport = a.id_transport
                    LEFT JOIN trajet t ON a.id_appareil = t.id_appareil
                    WHERE mt.nom_compagnie IS NOT NULL
                    GROUP BY mt.id_transport
                    ORDER BY nombre_trajets DESC
                ");
                
                $compagnies = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($compagnies)) {
                    $response = ($lang === 'fr')
                        ? '<strong>Nos compagnies partenaires:</strong><br><ul style="padding-left: 20px;">'
                        : '<strong>Our partner companies:</strong><br><ul style="padding-left: 20px;">';
                    
                    foreach ($compagnies as $c) {
                        $response .= '<li><strong>' . htmlspecialchars($c['nom_compagnie']) . '</strong> (' . 
                                    $c['type'] . ') - ' . $c['nombre_trajets'] . 
                                    ($lang === 'fr' ? ' trajets' : ' trips') . '</li>';
                    }
                    
                    $response .= '</ul>';
                    return $response;
                }
            }
            
            // ========================
            // TARIFS
            // ========================
            if (in_array('prix', $keywords) || in_array('tarif', $keywords) || 
                in_array('price', $keywords) || in_array('cout', $keywords)) {
                
                $stmt = $this->pdo->query("
                    SELECT MIN(prix) as min_prix, MAX(prix) as max_prix, AVG(prix) as avg_prix
                    FROM trajet
                ");
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && $result['max_prix'] > 0) {
                    return ($lang === 'fr')
                        ? '<strong>Nos tarifs:</strong><br>Min: €' . number_format($result['min_prix'], 2) . 
                          '<br>Max: €' . number_format($result['max_prix'], 2) .
                          '<br>Moyen: €' . number_format($result['avg_prix'], 2)
                        : '<strong>Our fares:</strong><br>Min: €' . number_format($result['min_prix'], 2) . 
                          '<br>Max: €' . number_format($result['max_prix'], 2) .
                          '<br>Average: €' . number_format($result['avg_prix'], 2);
                }
            }
            
        } catch (Exception $e) {
            error_log("Chat BD Query Error: " . $e->getMessage());
            return null;
        }
        
        return null;
    }
    
    /**
     * Générer une réponse avec IA Mistral (API externe intelligente)
     */
    private function generateResponse($keywords, $lang, $greeting = '', $originalQuestion = '') {
        // Essayer Mistral IA en premier
        $aiResponse = $this->getMistralResponse($originalQuestion, $lang);
        if ($aiResponse) {
            return array(
                'response' => $greeting . $aiResponse,
                'source' => 'mistral_ai',
                'confidence' => 0.9
            );
        }
        
        // Fallback: patterns simples
        $patterns = array(
            'merci|thanks' => array('fr' => 'De rien! 😊', 'en' => 'You\'re welcome! 😊'),
            'comment ca va|how are' => array('fr' => 'Très bien! 😊', 'en' => 'Great! 😊'),
            'reservation|reserver|book' => array('fr' => 'Utilisez notre moteur de recherche pour réserver.', 'en' => 'Use our search engine to book.'),
            'modifier|changer|modify' => array('fr' => 'Allez à "Mes Réservations" pour modifier.', 'en' => 'Go to "My Bookings" to modify.'),
            'annuler|cancel' => array('fr' => 'Allez à "Mes Réservations" pour annuler.', 'en' => 'Go to "My Bookings" to cancel.'),
            'aide|help|support' => array('fr' => 'Notre support est dispo 24/7.', 'en' => 'Our support is available 24/7.'),
        );
        
        $keywordStr = implode(' ', $keywords);
        
        foreach ($patterns as $pattern => $responses) {
            foreach (explode('|', $pattern) as $keyword) {
                $keyword = trim($keyword);
                if (in_array($keyword, $keywords) || strpos($keywordStr, $keyword) !== false) {
                    $response = isset($responses[$lang]) ? $responses[$lang] : $responses['fr'];
                    return array('response' => $greeting . $response, 'source' => 'pattern', 'confidence' => 0.8);
                }
            }
        }
        
        $fallback = ($lang === 'fr') 
            ? 'Je ne comprends pas bien. Pouvez-vous reformuler?'
            : 'I don\'t understand. Can you rephrase?';
        
        return array('response' => $greeting . $fallback, 'source' => 'fallback', 'confidence' => 0.5);
    }
    
    /**
     * Appeler l'API Mistral pour une vraie IA
     */
    private function getMistralResponse($question, $lang) {
        // Récupérer la clé API
        $apiKey = null;
        
        // 1. Vérifier si la constante est définie
        if (defined('MISTRAL_API_KEY')) {
            $apiKey = MISTRAL_API_KEY;
        }
        
        // 2. Vérifier les variables d'environnement
        if (!$apiKey) {
            $apiKey = getenv('MISTRAL_API_KEY') ?: ($_ENV['MISTRAL_API_KEY'] ?? null);
        }
        
        // 3. Vérifier dans $_SERVER
        if (!$apiKey) {
            $apiKey = $_SERVER['MISTRAL_API_KEY'] ?? null;
        }
        
        // Clé par défaut ou invalide
        if (!$apiKey || $apiKey === 'remplace_par_ta_cle_api_mistral') {
            error_log("⚠️ Mistral API key not configured. Configure in /config.php");
            return null;
        }
        
        // Vérifier curl
        if (!function_exists('curl_init')) {
            error_log("⚠️ curl extension not enabled");
            return null;
        }
        
        $systemPrompt = ($lang === 'fr')
            ? "Tu es un assistant intelligent d'une agence de voyage française. Sois courtois, professionnel et utile. Réponds toujours en français. Les réponses doivent être courtes et directes (2-3 phrases max). Aide les clients avec leurs questions sur les trajets, réservations, tarifs, etc."
            : "You are an intelligent assistant for a travel agency. Be courteous, professional and helpful. Always respond in English. Keep answers short and direct (2-3 sentences max). Help customers with questions about trips, bookings, fares, etc.";
        
        $payload = array(
            'model' => 'mistral-small',
            'messages' => array(
                array('role' => 'system', 'content' => $systemPrompt),
                array('role' => 'user', 'content' => $question)
            ),
            'temperature' => 0.7,
            'max_tokens' => 300
        );
        
        try {
            $ch = curl_init('https://api.mistral.ai/v1/chat/completions');
            curl_setopt_array($ch, array(
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey
                ),
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                error_log("Mistral curl error: $curlError");
                return null;
            }
            
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if (isset($result['choices'][0]['message']['content'])) {
                    $aiText = trim($result['choices'][0]['message']['content']);
                    error_log("✅ Mistral AI success: " . substr($aiText, 0, 50) . "...");
                    return $aiText;
                }
            }
            
            error_log("Mistral HTTP $httpCode: " . substr($response, 0, 200));
            return null;
            
        } catch (Exception $e) {
            error_log("Mistral Exception: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Charger l'historique
     */
    private function loadConversationHistory() {
        if (file_exists($this->logFile)) {
            $this->conversationLog = json_decode(file_get_contents($this->logFile), true) ?: array();
        }
    }
    
    /**
     * Sauvegarder une conversation
     */
    private function saveConversation($question, $response, $source, $lang) {
        $this->conversationLog[] = array(
            'timestamp' => date('Y-m-d H:i:s'),
            'question' => $question,
            'response' => $response,
            'source' => $source,
            'lang' => $lang
        );
        
        if (count($this->conversationLog) > 3000) {
            $this->conversationLog = array_slice($this->conversationLog, -3000);
        }
        
        @mkdir(dirname($this->logFile), 0777, true);
        @file_put_contents($this->logFile, json_encode($this->conversationLog, JSON_PRETTY_PRINT));
    }
}

// ========================
// TRAITER LA REQUÊTE
// ========================
try {
    $question = isset($_POST['question']) ? $_POST['question'] : '';
    $lang = isset($_POST['lang']) ? $_POST['lang'] : 'fr';

    if (empty($question)) {
        http_response_code(400);
        echo json_encode(array('error' => 'Question vide'));
        exit;
    }

    $chat = new ChatIA();
    $result = $chat->respond($question, $lang);

    echo json_encode(array(
        'success' => true,
        'response' => $result['response'],
        'source' => $result['source'],
        'confidence' => isset($result['confidence']) ? $result['confidence'] : 0
    ));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
