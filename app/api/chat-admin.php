<?php
/**
 * Chat Admin API - Gestion du dashboard administrateur
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

class ChatAdmin {
    private $conversationLog = array();
    private $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/../../logs/chat_conversations.json';
        $this->loadConversationHistory();
    }
    
    /**
     * Charger l'historique
     */
    private function loadConversationHistory() {
        if (file_exists($this->logFile)) {
            $data = file_get_contents($this->logFile);
            $this->conversationLog = json_decode($data, true) ?: array();
        }
    }
    
    /**
     * Obtenir les statistiques
     */
    public function getStats() {
        $stats = array(
            'total_conversations' => count($this->conversationLog),
            'avg_response_length' => 0,
            'avg_rating' => 0,
            'most_common_sources' => array(),
            'languages' => array(),
            'conversations' => $this->conversationLog
        );
        
        if (count($this->conversationLog) > 0) {
            $totalLength = 0;
            $sources = array();
            $languages = array();
            
            foreach ($this->conversationLog as $conv) {
                $totalLength += strlen($conv['response'] ?? '');
                
                $source = $conv['source'] ?? 'unknown';
                $sources[$source] = (isset($sources[$source]) ? $sources[$source] : 0) + 1;
                
                $lang = $conv['lang'] ?? 'fr';
                $languages[$lang] = (isset($languages[$lang]) ? $languages[$lang] : 0) + 1;
            }
            
            $stats['avg_response_length'] = intval($totalLength / count($this->conversationLog));
            $stats['most_common_sources'] = $sources;
            $stats['languages'] = $languages;
        }
        
        return $stats;
    }
}

try {
    $admin = new ChatAdmin();
    $action = isset($_GET['action']) ? $_GET['action'] : 'get_data';

    if ($action === 'get_data') {
        $stats = $admin->getStats();
        
        echo json_encode(array(
            'success' => true,
            'patterns' => array(
                'total_conversations' => $stats['total_conversations'],
                'avg_response_length' => $stats['avg_response_length'],
                'avg_rating' => $stats['avg_rating'],
                'most_common_sources' => $stats['most_common_sources'],
                'languages' => $stats['languages']
            ),
            'conversations' => $stats['conversations']
        ));
    } else {
        http_response_code(400);
        echo json_encode(array(
            'success' => false,
            'error' => 'Action inconnue'
        ));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
