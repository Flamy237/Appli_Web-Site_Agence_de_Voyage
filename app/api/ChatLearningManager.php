<?php
/**
 * Chat Learning Manager - Apprentissage des conversations
 * Analyse les conversations passées pour améliorer les réponses
 */

class ChatLearningManager {
    private $conversationLogFile;
    private $knowledgeBaseFile;
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->conversationLogFile = __DIR__ . '/../../logs/chat_conversations.json';
        $this->knowledgeBaseFile = __DIR__ . '/../../logs/chat_knowledge.json';

        // Créer les fichiers s'ils n'existent pas
        if (!file_exists($this->conversationLogFile)) {
            file_put_contents($this->conversationLogFile, json_encode([], JSON_PRETTY_PRINT));
        }
        if (!file_exists($this->knowledgeBaseFile)) {
            file_put_contents($this->knowledgeBaseFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Récupérer les meilleures réponses basées sur l'historique
     */
    public function getBestResponseFromHistory($question, $lang = 'fr') {
        $conversations = json_decode(file_get_contents($this->conversationLogFile), true) ?? [];
        
        if (empty($conversations)) {
            return null;
        }

        // Scoring basé sur la similarité
        $scores = [];
        foreach ($conversations as $entry) {
            if ($entry['role'] === 'user' && isset($entry['question'])) {
                $score = $this->calculateSimilarity($question, $entry['question']);
                if ($score > 0.6) { // Au-dessus du seuil
                    $scores[] = ['score' => $score, 'entry' => $entry];
                }
            }
        }

        // Retourner la meilleure correspondance
        if (!empty($scores)) {
            usort($scores, fn($a, $b) => $b['score'] - $a['score']);
            $bestMatch = $scores[0]['entry'];
            
            // Récupérer la réponse correspondante
            $responseIndex = array_key_last($conversations);
            for ($i = array_search($bestMatch, $conversations); $i <= $responseIndex; $i++) {
                if ($conversations[$i]['role'] === 'bot') {
                    return $conversations[$i]['message'];
                }
            }
        }

        return null;
    }

    /**
     * Calculer la similarité entre deux textes (Jaro-Winkler)
     */
    private function calculateSimilarity($str1, $str2) {
        $str1 = strtolower($str1);
        $str2 = strtolower($str2);

        // Tokenisation simple
        $tokens1 = array_filter(explode(' ', preg_replace('/[^\w\s]/', '', $str1)));
        $tokens2 = array_filter(explode(' ', preg_replace('/[^\w\s]/', '', $str2)));

        if (empty($tokens1) || empty($tokens2)) {
            return 0;
        }

        // Intersection des tokens
        $common = count(array_intersect($tokens1, $tokens2));
        $total = count(array_unique(array_merge($tokens1, $tokens2)));

        return $common / max($total, 1);
    }

    /**
     * Enregistrer une conversation réussie
     */
    public function recordSuccessfulConversation($question, $response, $source, $userId = null) {
        $conversations = json_decode(file_get_contents($this->conversationLogFile), true) ?? [];
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'userId' => $userId,
            'question' => $question,
            'response' => $response,
            'source' => $source,
            'rating' => 0 // À remplir avec feedback utilisateur
        ];

        $conversations[] = $entry;

        // Garder les 5000 dernières conversations
        if (count($conversations) > 5000) {
            $conversations = array_slice($conversations, -5000);
        }

        file_put_contents($this->conversationLogFile, json_encode($conversations, JSON_PRETTY_PRINT));
    }

    /**
     * Mettre à jour le feedback d'une conversation
     */
    public function updateConversationFeedback($conversationIndex, $rating) {
        $conversations = json_decode(file_get_contents($this->conversationLogFile), true) ?? [];
        
        if (isset($conversations[$conversationIndex])) {
            $conversations[$conversationIndex]['rating'] = $rating;
            file_put_contents($this->conversationLogFile, json_encode($conversations, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Générer des suggestions basées sur les conversations passées
     */
    public function generateSuggestions($question, $limit = 3) {
        $conversations = json_decode(file_get_contents($this->conversationLogFile), true) ?? [];
        
        $suggestions = [];
        foreach ($conversations as $entry) {
            if ($entry['role'] === 'user' && isset($entry['question'])) {
                $score = $this->calculateSimilarity($question, $entry['question']);
                if ($score > 0.3 && $score < 1.0) {
                    $suggestions[] = ['question' => $entry['question'], 'score' => $score];
                }
            }
        }

        // Trier par score décroissant et limiter
        usort($suggestions, fn($a, $b) => $b['score'] - $a['score']);
        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Analyzer les patterns de conversation pour amélioration
     */
    public function analyzePatterns() {
        $conversations = json_decode(file_get_contents($this->conversationLogFile), true) ?? [];
        
        $patterns = [
            'total_conversations' => count($conversations),
            'avg_response_length' => 0,
            'most_common_sources' => [],
            'language_distribution' => [],
            'avg_rating' => 0
        ];

        $totalLength = 0;
        $sources = [];
        $languages = [];
        $ratings = [];

        foreach ($conversations as $entry) {
            if ($entry['role'] === 'bot') {
                $totalLength += strlen($entry['message'] ?? '');
                $sources[] = $entry['source'] ?? 'unknown';
                $languages[] = $entry['lang'] ?? 'fr';
                if (isset($entry['rating']) && $entry['rating'] !== 0) {
                    $ratings[] = $entry['rating'];
                }
            }
        }

        $patterns['avg_response_length'] = $totalLength > 0 ? intval($totalLength / max(count($conversations) / 2, 1)) : 0;
        $patterns['most_common_sources'] = array_count_values($sources);
        $patterns['language_distribution'] = array_count_values($languages);
        $patterns['avg_rating'] = !empty($ratings) ? array_sum($ratings) / count($ratings) : 0;

        return $patterns;
    }

    /**
     * Exporter les données d'apprentissage pour analyse
     */
    public function exportLearningData() {
        return [
            'conversations' => json_decode(file_get_contents($this->conversationLogFile), true) ?? [],
            'patterns' => $this->analyzePatterns()
        ];
    }
}

?>
