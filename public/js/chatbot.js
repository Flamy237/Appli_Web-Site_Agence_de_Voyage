/**
 * chatbot.js - Client JavaScript pour le Chatbot
 * Intègre le chatbot dans votre site
 */

class ChatbotClient {
    constructor(options = {}) {
        this.apiUrl = options.apiUrl || '/app/api/chat-api.php';
        this.provider = options.provider || 'mistral';
        this.conversationId = options.conversationId || this.generateId();
        this.containerSelector = options.containerSelector || '#chatbot';
        this.messageInputSelector = options.messageInputSelector || '#message-input';
        this.sendButtonSelector = options.sendButtonSelector || '#send-button';
        this.messagesSelector = options.messagesSelector || '.messages';
        
        this.systemPrompt = options.systemPrompt || 
            'Tu es un assistant client pour une agence de voyage.';
        
        this.isLoading = false;
        this.conversationHistory = [];
        
        this.init();
    }
    
    /**
     * Initialiser le chatbot
     */
    init() {
        this.attachEventListeners();
        this.logInfo('Chatbot initialized', {
            apiUrl: this.apiUrl,
            provider: this.provider,
            conversationId: this.conversationId
        });
    }
    
    /**
     * Attacher les écouteurs d'événements
     */
    attachEventListeners() {
        const input = document.querySelector(this.messageInputSelector);
        const button = document.querySelector(this.sendButtonSelector);
        
        if (input) {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }
        
        if (button) {
            button.addEventListener('click', () => this.sendMessage());
        }
    }
    
    /**
     * Envoyer un message
     */
    async sendMessage() {
        if (this.isLoading) return;
        
        const input = document.querySelector(this.messageInputSelector);
        const message = input?.value?.trim();
        
        if (!message) return;
        
        // Afficher le message utilisateur
        this.addMessageToUI(message, 'user');
        input.value = '';
        
        this.isLoading = true;
        this.showTypingIndicator();
        
        try {
            const response = await this.callApi(message);
            
            if (response.success) {
                // Afficher la réponse du bot
                this.addMessageToUI(response.response, 'assistant', {
                    provider: response.provider,
                    tokens: response.metadata?.tokens,
                    latency: response.metadata?.latency_ms,
                    cost: response.metadata?.cost_usd
                });
                
                // Ajouter à l'historique
                this.conversationHistory.push({
                    role: 'user',
                    content: message
                });
                this.conversationHistory.push({
                    role: 'assistant',
                    content: response.response
                });
                
                // Callback de succès
                if (this.onSuccess) {
                    this.onSuccess(response);
                }
            } else {
                this.addMessageToUI(
                    '❌ Erreur: ' + (response.error || 'Erreur inconnue'),
                    'error'
                );
                
                if (this.onError) {
                    this.onError(response.error);
                }
            }
        } catch (error) {
            this.addMessageToUI(
                '❌ Erreur de connexion: ' + error.message,
                'error'
            );
            
            if (this.onError) {
                this.onError(error.message);
            }
        } finally {
            this.removeTypingIndicator();
            this.isLoading = false;
            
            const input = document.querySelector(this.messageInputSelector);
            if (input) input.focus();
        }
    }
    
    /**
     * Appeler l'API
     */
    async callApi(message) {
        const payload = {
            message: message,
            provider: this.provider,
            conversation_id: this.conversationId,
            options: {
                temperature: 0.7,
                max_tokens: 512
            }
        };
        
        const response = await fetch(this.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        });
        
        if (!response.ok) {
            throw new Error(`API Error: ${response.status}`);
        }
        
        return await response.json();
    }
    
    /**
     * Ajouter un message à l'interface
     */
    addMessageToUI(text, role, metadata = {}) {
        const container = document.querySelector(this.messagesSelector);
        if (!container) return;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `message message-${role}`;
        
        let html = `<div class="message-content">${this.escapeHtml(text)}</div>`;
        
        if (metadata.provider || metadata.tokens || metadata.latency || metadata.cost) {
            html += '<div class="message-meta">';
            if (metadata.provider) {
                html += `<span class="badge">${metadata.provider}</span>`;
            }
            if (metadata.tokens) {
                html += `<span class="badge">${metadata.tokens} tokens</span>`;
            }
            if (metadata.latency) {
                html += `<span class="badge">${metadata.latency}ms</span>`;
            }
            if (metadata.cost) {
                html += `<span class="badge">$${metadata.cost}</span>`;
            }
            html += '</div>';
        }
        
        messageDiv.innerHTML = html;
        container.appendChild(messageDiv);
        container.scrollTop = container.scrollHeight;
    }
    
    /**
     * Afficher l'indicateur de saisie
     */
    showTypingIndicator() {
        const container = document.querySelector(this.messagesSelector);
        if (!container) return;
        
        const typing = document.createElement('div');
        typing.id = 'typing-indicator';
        typing.className = 'typing-indicator';
        typing.innerHTML = '<span></span><span></span><span></span>';
        container.appendChild(typing);
        container.scrollTop = container.scrollHeight;
    }
    
    /**
     * Retirer l'indicateur de saisie
     */
    removeTypingIndicator() {
        const typing = document.getElementById('typing-indicator');
        if (typing) typing.remove();
    }
    
    /**
     * Changer le provider
     */
    setProvider(provider) {
        this.provider = provider;
        this.logInfo('Provider changed to: ' + provider);
    }
    
    /**
     * Réinitialiser la conversation
     */
    reset() {
        this.conversationHistory = [];
        this.conversationId = this.generateId();
        
        const container = document.querySelector(this.messagesSelector);
        if (container) {
            container.innerHTML = '<div class="message message-assistant">' +
                '<div class="message-content">Conversation réinitialisée. Comment puis-je vous aider?</div>' +
                '</div>';
        }
        
        this.logInfo('Conversation reset');
    }
    
    /**
     * Exporter la conversation
     */
    exportConversation() {
        const data = {
            conversationId: this.conversationId,
            provider: this.provider,
            timestamp: new Date().toISOString(),
            messages: this.conversationHistory
        };
        
        return JSON.stringify(data, null, 2);
    }
    
    /**
     * Télécharger la conversation en JSON
     */
    downloadConversation() {
        const data = this.exportConversation();
        const blob = new Blob([data], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `conversation-${this.conversationId}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    /**
     * Utilitaires
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    generateId() {
        return 'chat_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
    }
    
    logInfo(message, data = {}) {
        if (this.debug) {
            console.log('[ChatBot]', message, data);
        }
    }
}

// ============================================================================
// Utilisation Simple
// ============================================================================

/*
// Dans votre HTML
<div id="chatbot">
    <div class="messages"></div>
    <input type="text" id="message-input" placeholder="Votre message">
    <button id="send-button">Envoyer</button>
</div>

// Dans votre JavaScript
const chatbot = new ChatbotClient({
    apiUrl: '/app/api/chat-api.php',
    provider: 'mistral',
    containerSelector: '#chatbot',
    messageInputSelector: '#message-input',
    sendButtonSelector: '#send-button',
    messagesSelector: '.messages'
});

// Callbacks optionnels
chatbot.onSuccess = (response) => {
    console.log('Message sent successfully', response);
};

chatbot.onError = (error) => {
    console.error('Error:', error);
};

// Changer de provider
chatbot.setProvider('openai');

// Exporter la conversation
const json = chatbot.exportConversation();

// Télécharger la conversation
chatbot.downloadConversation();

// Réinitialiser
chatbot.reset();
*/

// ============================================================================
// Intégration Vue.js (optionnel)
// ============================================================================

if (typeof Vue !== 'undefined') {
    Vue.component('chatbot', {
        template: `
            <div class="chatbot-wrapper">
                <div class="chatbot-header">
                    <h3>Assistant Voyage</h3>
                    <select v-model="provider" @change="changeProvider">
                        <option value="mistral">Mistral</option>
                        <option value="openai">OpenAI</option>
                        <option value="hugging_face">Hugging Face</option>
                    </select>
                </div>
                <div class="messages">
                    <div v-for="msg in messages" :key="msg.id" :class="'message message-' + msg.role">
                        <div class="message-content">{{ msg.content }}</div>
                        <div v-if="msg.metadata" class="message-meta">
                            <span v-if="msg.metadata.provider" class="badge">{{ msg.metadata.provider }}</span>
                            <span v-if="msg.metadata.tokens" class="badge">{{ msg.metadata.tokens }} tokens</span>
                        </div>
                    </div>
                    <div v-if="isLoading" class="typing-indicator">
                        <span></span><span></span><span></span>
                    </div>
                </div>
                <div class="input-group">
                    <input 
                        v-model="input" 
                        @keyup.enter="sendMessage"
                        placeholder="Votre message..."
                        :disabled="isLoading"
                    >
                    <button @click="sendMessage" :disabled="isLoading">Envoyer</button>
                </div>
            </div>
        `,
        data() {
            return {
                provider: 'mistral',
                messages: [],
                input: '',
                isLoading: false,
                apiUrl: '/app/api/chat-api.php'
            };
        },
        methods: {
            async sendMessage() {
                if (this.isLoading || !this.input.trim()) return;
                
                this.messages.push({
                    id: Date.now(),
                    role: 'user',
                    content: this.input
                });
                
                const message = this.input;
                this.input = '';
                this.isLoading = true;
                
                try {
                    const response = await fetch(this.apiUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message: message,
                            provider: this.provider
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.messages.push({
                            id: Date.now() + 1,
                            role: 'assistant',
                            content: data.response,
                            metadata: data.metadata
                        });
                    }
                } catch (error) {
                    this.messages.push({
                        id: Date.now() + 1,
                        role: 'error',
                        content: 'Erreur: ' + error.message
                    });
                } finally {
                    this.isLoading = false;
                }
            },
            changeProvider() {
                // Optionnel: réinitialiser la conversation
            }
        }
    });
}

// ============================================================================
// Intégration React (optionnel)
// ============================================================================

/*
// hooks/useChatbot.js
import { useState, useCallback } from 'react';

export function useChatbot(apiUrl = '/app/api/chat-api.php') {
    const [messages, setMessages] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [provider, setProvider] = useState('mistral');
    
    const sendMessage = useCallback(async (message) => {
        if (isLoading) return;
        
        setIsLoading(true);
        
        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message, provider })
            });
            
            const data = await response.json();
            
            if (data.success) {
                setMessages(prev => [...prev, 
                    { role: 'user', content: message },
                    { role: 'assistant', content: data.response }
                ]);
            }
        } finally {
            setIsLoading(false);
        }
    }, [isLoading, apiUrl, provider]);
    
    return { messages, isLoading, provider, setProvider, sendMessage };
}
*/
