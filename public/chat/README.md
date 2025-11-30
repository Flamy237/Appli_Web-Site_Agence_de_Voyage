# 💬 Système de Chat Intelligent

## 📋 Vue d'ensemble

Votre système de chat interactif est maintenant complètement fonctionnel avec:
- **Réponses intelligentes** basées sur la BD, FAQ, et APIs externes
- **Apprentissage des conversations** pour améliorer les réponses au fil du temps
- **Support multilingue** (FR/EN)
- **Dashboard administrateur** pour analyser les conversations
- **Interface moderne** avec animations et design professionnel

---

## 🏗️ Architecture

```
public/chat/
├── index.html              # Interface du chat
├── script.js               # Logique client (fetch API, historique)
├── style.css               # Styles avec animations
├── admin-dashboard.html    # Dashboard d'analyse
└── api/                    # (symlink vers app/api)

app/api/
├── chat.php                # API principale (traite les questions)
├── ChatLearningManager.php # Gestion de l'apprentissage
└── chat-admin.php          # API pour le dashboard

logs/
├── chat_conversations.json # Historique des conversations
└── chat_knowledge.json     # Base de connaissances
```

---

## 🚀 Fonctionnalités

### 1. **Traitement des Questions**

Le bot traite les questions dans cet ordre de priorité:

#### 1️⃣ FAQ (Base de connaissances locale)
- Mots-clés: `booking`, `refund`, `baggage`, `payment`, `cancel`, `modify`
- Réponses rapides prédéfinies

```javascript
// Exemple: "Quelles sont les règles de bagages?"
→ Réponse FAQ prédéfinie
```

#### 2️⃣ Base de Données
- Trajets disponibles
- Réservations de l'utilisateur
- Prix et tarifs

```javascript
// Exemple: "Je cherche un bus pour Paris"
→ Requête BD: SELECT * FROM trajets WHERE ...
```

#### 3️⃣ APIs Externes
- Météo (OpenWeatherMap)
- Taux de change
- Infos de voyage

```javascript
// Exemple: "Quel temps fait-il à Paris?"
→ Appel OpenWeatherMap API
```

#### 4️⃣ Fallback
- Suggestions d'amélioration
- Demande de clarification

---

## 📚 Apprentissage des Conversations

### Comment ça marche?

1. **Enregistrement**: Chaque Q&R est sauvegardée dans `logs/chat_conversations.json`
2. **Scoring**: Le système compare les nouvelles questions avec l'historique (Jaro-Winkler)
3. **Réutilisation**: Si une réponse similaire existe → la proposer
4. **Feedback**: Les votes utilisateur améliorent le scoring

### Fichiers d'apprentissage

```json
// logs/chat_conversations.json
[
  {
    "timestamp": "2025-11-27 14:30:45",
    "userId": 123,
    "question": "Comment modifier ma réservation?",
    "response": "Pour modifier votre réservation...",
    "source": "faq",
    "lang": "fr",
    "rating": 1  // 1=utile, -1=pas utile, 0=pas noté
  }
]
```

### API d'apprentissage

```php
// ChatLearningManager.php - Méthodes disponibles

$manager = new ChatLearningManager($pdo);

// Récupérer la meilleure réponse de l'historique
$bestResponse = $manager->getBestResponseFromHistory($question, 'fr');

// Générer des suggestions
$suggestions = $manager->generateSuggestions($question, 3);

// Analyser les patterns
$patterns = $manager->analyzePatterns();
// Retourne: {
//   "total_conversations": 1234,
//   "avg_response_length": 156,
//   "most_common_sources": {"faq": 600, "database": 400, ...},
//   "language_distribution": {"fr": 800, "en": 434},
//   "avg_rating": 0.85
// }
```

---

## 🛠️ API Backend (chat.php)

### Endpoint

```
POST /app/api/chat.php

Paramètres:
- question (string): La question de l'utilisateur
- lang (string): 'fr' ou 'en'

Réponse:
{
  "response": "Texte de réponse",
  "source": "faq|database|external|fallback",
  "data": {...}  // Optional, données additionnelles
}
```

### Exemple d'utilisation

```javascript
// Frontend (script.js)
const formData = new FormData();
formData.append('question', 'Quels trajets pour Paris?');
formData.append('lang', 'fr');

const response = await fetch('/app/api/chat.php', {
  method: 'POST',
  body: formData
});

const data = await response.json();
console.log(data.response);  // Affiche la réponse du bot
```

---

## 📊 Dashboard Administrateur

### Accès

```
http://localhost/public/chat/admin-dashboard.html
```

### Fonctionnalités

#### 📈 Statistiques en temps réel
- Total de conversations
- Longueur moyenne des réponses
- Note/Rating moyen
- Source la plus utilisée

#### 💬 Historique des conversations
- Affichage des 20 dernières conversations
- Source de chaque réponse
- Timestamp exact
- Ratings utilisateurs

#### 🔍 Recherche en temps réel
- Filtrer les conversations
- Chercher des mots-clés

#### 📥 Export de données
- Télécharger l'historique en JSON
- Analyse hors-ligne

---

## 🔧 Configuration

### Intégration API Externe - Météo

Pour utiliser la vraie API météo, modifier `chat.php`:

```php
private function getWeatherInfo($query) {
    // Avant: simulation
    // Après: vraie API
    
    $apiKey = 'YOUR_OPENWEATHERMAP_KEY';
    $city = 'Paris'; // Extraire de $query
    
    $url = "https://api.openweathermap.org/data/2.5/weather?q=$city&appid=$apiKey&lang=fr";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    return [
        'type' => 'weather',
        'location' => $data['name'],
        'temp' => $data['main']['temp'] . '°C',
        'condition' => $data['weather'][0]['main']
    ];
}
```

### Intégration API Taux de Change

```php
private function getExchangeRates($query) {
    $url = 'https://exchangerate-api.com/rates/EUR';
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    return [
        'type' => 'exchange',
        'rates' => $data['rates']
    ];
}
```

---

## 💾 Historique Local (LocalStorage)

### Chat History

```javascript
// Sauvegardé dans le navigateur
localStorage.getItem('chat_history')

// Format
[
  {
    "timestamp": "2025-11-27T14:30:45.123Z",
    "question": "Quels trajets?",
    "response": "J'ai trouvé 5 trajets...",
    "source": "database",
    "lang": "fr",
    "rating": 0
  }
]
```

### Accès depuis console

```javascript
// Afficher l'historique
console.log(JSON.parse(localStorage.getItem('chat_history')))

// Exporter
copy(JSON.stringify(JSON.parse(localStorage.getItem('chat_history')), null, 2))

// Effacer
localStorage.removeItem('chat_history')
```

---

## 📱 Intégration sur les pages

### 1. Copier les fichiers

```bash
cp -r public/chat /your/website/path/
```

### 2. Ajouter au HTML

```html
<!-- En bas du body -->
<link rel="stylesheet" href="/chat/style.css">
<div id="chatbot-root">
  <button id="chat-toggle">Chat</button>
  <aside id="chat-window" role="dialog" aria-hidden="true">
    <!-- ... structure ... -->
  </aside>
</div>
<script src="/chat/script.js"></script>
```

### 3. C'est tout! 🎉

Le chat est maintenant opérationnel sur votre page.

---

## 🎯 Améliorations Futures

### Phase 1 (À court terme)
- [ ] Support des images dans le chat
- [ ] Typing indicator ("L'assistant écrit...")
- [ ] Historique chiffré
- [ ] Notifications push

### Phase 2 (Moyen terme)
- [ ] NLP avec TensorFlow.js pour meilleure compréhension
- [ ] Chatbot avec mémoire (contexte conversationnel)
- [ ] Support des emojis réactifs
- [ ] Traduction en temps réel

### Phase 3 (Long terme)
- [ ] IA générative (ChatGPT API)
- [ ] Voice chat (Web Speech API)
- [ ] Transfert à agent humain
- [ ] Analytics avancées

---

## 🐛 Dépannage

### Le chat ne répond pas

**Vérifier:**
1. La BD est accessible: `app/models/Database.php`
2. Les logs sont créés: `logs/` existent et sont accessibles en écriture
3. L'API retourne une erreur: Ouvrir DevTools → Network → Voir la réponse chat.php

**Solutions:**
```bash
# Créer les logs s'ils n'existent pas
mkdir -p logs
chmod 777 logs

# Vérifier la connexion BD
php -r "require 'includes/config.php'; echo 'BD OK';"
```

### Erreur CORS

Si les requêtes fetch échouent:

```php
// Ajouter à app/api/chat.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
```

### Chat lent

- Nettoyer `logs/chat_conversations.json` (garder les 1000 dernières)
- Optimiser requêtes BD (ajouter INDEX)
- Réduire timeout API externe

---

## 📞 Support

Pour une aide supplémentaire:
1. Consulter les logs: `logs/chat_conversations.json`
2. Ouvrir DevTools (F12) → Console
3. Vérifier les appels réseau (Network tab)

---

**Créé le**: 27 Novembre 2025
**Version**: 1.0.0
**Status**: Production-Ready ✅
