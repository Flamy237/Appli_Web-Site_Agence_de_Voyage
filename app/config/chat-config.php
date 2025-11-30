<?php
/**
 * Chat Configuration
 * Personnalisez le comportement du chatbot
 */

return [
    // Comportement du chat
    'chat' => [
        'default_language' => 'fr',
        'response_timeout' => 5000, // ms
        'max_response_length' => 1000,
        'typing_delay' => 500, // ms
    ],

    // Apprentissage
    'learning' => [
        'enabled' => true,
        'similarity_threshold' => 0.6,
        'max_conversations' => 5000,
        'auto_cleanup_interval' => 86400, // 24h en secondes
    ],

    // Sources de données
    'sources' => [
        'faq' => [
            'enabled' => true,
            'keywords' => ['booking', 'refund', 'baggage', 'payment', 'cancel', 'modify']
        ],
        'database' => [
            'enabled' => true,
            'tables' => ['trajets', 'reservations', 'utilisateur']
        ],
        'external_api' => [
            'weather' => [
                'enabled' => false, // Passer à true après configurer la clé API
                'provider' => 'openweathermap',
                'api_key' => 'YOUR_API_KEY_HERE'
            ],
            'exchange_rates' => [
                'enabled' => false,
                'provider' => 'exchangerate-api',
                'api_key' => 'YOUR_API_KEY_HERE'
            ]
        ]
    ],

    // Personnalisation UI
    'ui' => [
        'position' => 'bottom-right', // bottom-right, bottom-left, top-right, top-left
        'theme' => 'light', // light, dark
        'button_text' => 'Chat',
        'button_icon' => '💬',
        'welcome_message_fr' => 'Bienvenue! 👋 Je suis votre assistant de voyage. Posez-moi vos questions sur les réservations, trajets, ou voyages.',
        'welcome_message_en' => 'Welcome! 👋 I\'m your travel assistant. Ask me about bookings, routes, or trips.',
    ],

    // Logging
    'logs' => [
        'enabled' => true,
        'log_file' => 'logs/chat_conversations.json',
        'knowledge_file' => 'logs/chat_knowledge.json',
        'max_log_size' => 5000, // Nombre de conversations à garder
    ],

    // Modération
    'moderation' => [
        'enabled' => false, // Passer à true pour filtrer les mauvaises questions
        'blocked_keywords' => [], // Mots interdits
        'max_rapid_messages' => 10, // Messages par minute
    ],

    // Intégration Admin
    'admin' => [
        'dashboard_enabled' => true,
        'dashboard_url' => '/public/chat/admin-dashboard.html',
        'require_authentication' => false, // Changer à true si vous ajoutez une auth
    ]
];
?>
