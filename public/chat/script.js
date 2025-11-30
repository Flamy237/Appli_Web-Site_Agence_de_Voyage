// Chatbot avancé avec apprentissage des conversations
(function () {
  // Config
  const DEFAULT_LANG = 'fr';
  const API_ENDPOINT = '/projet-agence-de-voyage/app/api/chat.php'; // Chemin complet XAMPP
  const CHAT_HISTORY_KEY = 'chat_history';
  const MAX_HISTORY = 100;

  // DOM refs
  const toggle = document.getElementById('chat-toggle');
  const windowEl = document.getElementById('chat-window');
  const closeBtn = document.getElementById('chat-close');
  const messages = document.getElementById('messages');
  const input = document.getElementById('chat-input');
  const sendBtn = document.getElementById('send-btn');
  const langButtons = document.querySelectorAll('.lang-btn');

  let currentLang = DEFAULT_LANG;
  let chatHistory = JSON.parse(localStorage.getItem(CHAT_HISTORY_KEY) || '[]');
  let isLoading = false;

  // ========== Toggle & Basic UI ==========
  toggle?.addEventListener('click', () => {
    const hidden = windowEl.getAttribute('aria-hidden') === 'true';
    windowEl.setAttribute('aria-hidden', !hidden);
    if (!hidden && messages.children.length === 0) {
      showWelcomeMessage();
    }
  });

  closeBtn?.addEventListener('click', () => {
    windowEl.setAttribute('aria-hidden', 'true');
  });

  // ========== Language Switching ==========
  langButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const newLang = btn.dataset.lang;
      setLanguage(newLang);
      langButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });

  function setLanguage(lang) {
    currentLang = lang;
    localStorage.setItem('current_lang', lang);
    // Mettre à jour placeholder
    input.placeholder = lang === 'fr' 
      ? "Posez votre question..." 
      : "Ask a question...";
  }

  // Récupérer langue sauvegardée
  const savedLang = localStorage.getItem('current_lang') || DEFAULT_LANG;
  setLanguage(savedLang);
  document.querySelector(`.lang-btn[data-lang="${savedLang}"]`)?.classList.add('active');

  // ========== Message Rendering ==========
  function renderMessage(text, who = 'bot', metadata = {}) {
    const el = document.createElement('div');
    el.className = `message message-${who}`;
    
    // Escape HTML mais garde les balises simples
    const safeText = escapeHtml(text)
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/__(.+?)__/g, '<em>$1</em>')
      .replace(/🌤️|🚗|🚌|✈️|⚡|💰/g, m => m); // Emojis

    el.innerHTML = safeText;

    // Ajouter métadonnées (feedback, source, etc)
    if (Object.keys(metadata).length > 0) {
      const meta = document.createElement('div');
      meta.className = 'message-meta';
      
      if (metadata.source) {
        const sourceEl = document.createElement('small');
        sourceEl.textContent = `Source: ${metadata.source}`;
        sourceEl.className = 'message-source';
        meta.appendChild(sourceEl);
      }

      if (who === 'bot') {
        // Boutons de feedback
        const feedbackDiv = document.createElement('div');
        feedbackDiv.className = 'feedback-controls';

        const helpfulBtn = document.createElement('button');
        helpfulBtn.className = 'feedback-btn feedback-helpful';
        helpfulBtn.textContent = currentLang === 'fr' ? '👍 Utile' : '👍 Helpful';
        helpfulBtn.addEventListener('click', () => recordFeedback(text, 1, helpfulBtn));

        const unhelpfulBtn = document.createElement('button');
        unhelpfulBtn.className = 'feedback-btn feedback-unhelpful';
        unhelpfulBtn.textContent = currentLang === 'fr' ? '👎 Pas utile' : '👎 Not helpful';
        unhelpfulBtn.addEventListener('click', () => recordFeedback(text, -1, unhelpfulBtn));

        feedbackDiv.appendChild(helpfulBtn);
        feedbackDiv.appendChild(unhelpfulBtn);
        meta.appendChild(feedbackDiv);
      }

      el.appendChild(meta);
    }

    messages.appendChild(el);
    messages.scrollTop = messages.scrollHeight;
    
    return el;
  }

  function showWelcomeMessage() {
    const welcome = currentLang === 'fr'
      ? "Bienvenue! 👋 Je suis votre assistant de voyage. Posez-moi vos questions sur les réservations, trajets, ou voyages."
      : "Welcome! 👋 I'm your travel assistant. Ask me about bookings, routes, or trips.";
    
    renderMessage(welcome, 'bot', { source: 'system' });
  }

  function showLoadingIndicator() {
    const loader = document.createElement('div');
    loader.className = 'message-loading';
    loader.id = 'chat-loader';
    loader.innerHTML = '<span></span><span></span><span></span>';
    messages.appendChild(loader);
    messages.scrollTop = messages.scrollHeight;
  }

  function hideLoadingIndicator() {
    const loader = document.getElementById('chat-loader');
    if (loader) loader.remove();
  }

  // ========== Send Message & API Call ==========
  function sendMessage() {
    const text = input.value.trim();
    if (!text || isLoading) return;

    input.value = '';
    isLoading = true;

    // Afficher le message utilisateur
    renderMessage(text, 'user');
    showLoadingIndicator();

    // Appeler l'API backend
    callChatAPI(text);
  }

  async function callChatAPI(question) {
    try {
      const formData = new FormData();
      formData.append('question', question);
      formData.append('lang', currentLang);

      const response = await fetch(API_ENDPOINT, {
        method: 'POST',
        body: formData
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const data = await response.json();
      hideLoadingIndicator();

      if (data.error) {
        renderMessage(
          currentLang === 'fr' ? "Désolé, une erreur s'est produite." : "Sorry, an error occurred.",
          'bot',
          { source: 'error' }
        );
      } else {
        renderMessage(data.response, 'bot', { source: data.source });
        
        // Sauvegarder dans l'historique
        saveChatEntry(question, data.response, data.source);
      }
    } catch (error) {
      hideLoadingIndicator();
      const errorMsg = currentLang === 'fr'
        ? `Erreur: ${error.message}`
        : `Error: ${error.message}`;
      renderMessage(errorMsg, 'bot', { source: 'error' });
    } finally {
      isLoading = false;
    }
  }

  // ========== Chat History & Learning ==========
  function saveChatEntry(question, response, source) {
    const entry = {
      timestamp: new Date().toISOString(),
      question: question,
      response: response,
      source: source,
      lang: currentLang,
      rating: 0 // À remplir si feedback
    };

    chatHistory.push(entry);

    // Garder seulement MAX_HISTORY entrées
    if (chatHistory.length > MAX_HISTORY) {
      chatHistory = chatHistory.slice(-MAX_HISTORY);
    }

    localStorage.setItem(CHAT_HISTORY_KEY, JSON.stringify(chatHistory));
  }

  function recordFeedback(response, rating, button) {
    // Marquer comme votée
    button.disabled = true;
    button.style.opacity = '0.5';

    // Sauvegarder le feedback dans l'historique
    const lastEntry = chatHistory[chatHistory.length - 1];
    if (lastEntry) {
      lastEntry.rating = rating;
      localStorage.setItem(CHAT_HISTORY_KEY, JSON.stringify(chatHistory));
    }

    // Feedback visuel
    const msg = rating > 0
      ? (currentLang === 'fr' ? 'Merci!' : 'Thanks!')
      : (currentLang === 'fr' ? 'Nous nous améliorerons' : 'We will improve');
    
    const feedback = document.createElement('small');
    feedback.className = 'feedback-thank-you';
    feedback.textContent = msg;
    button.parentElement.appendChild(feedback);
  }

  // ========== Keyboard Shortcut ==========
  input?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      sendMessage();
    }
  });

  sendBtn?.addEventListener('click', sendMessage);

  // ========== Export chat history pour backend learning ==========
  window.exportChatHistory = function() {
    return JSON.stringify(chatHistory, null, 2);
  };

  // ========== Utils ==========
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

  // ========== Initialisation ==========
  console.log('[Chat] Initialized with language:', currentLang);
  console.log('[Chat] History entries:', chatHistory.length);
  showWelcomeMessage();
})();