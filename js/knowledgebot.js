/**
 * KnowledgeBot Chatbot Frontend-Funktionalität
 */
document.addEventListener('DOMContentLoaded', function() {
    // Chatbot nur initialisieren, wenn wir uns nicht auf einer Login-Seite befinden
    if (typeof CFG_GLPI !== 'undefined' && typeof CFG_GLPI.root_doc !== 'undefined') {
        new KnowledgeBotChatUI();
    }
});

class KnowledgeBotChatUI {
    constructor() {
        this.chatContainer = null;
        this.messageList = null;
        this.messageInput = null;
        this.sendButton = null;
        this.conversationId = null;
        this.sessionId = this.generateSessionId();
        this.userId = (typeof CFG_GLPI !== 'undefined' && CFG_GLPI.user_id) ? CFG_GLPI.user_id : 0;
        
        this.init();
    }
    
    /**
     * Initialisiert den Chatbot
     */
    init() {
        // Chat-UI zur Seite hinzufügen
        this.createChatUI();
        
        // Event-Listener hinzufügen
        this.addEventListeners();
        
        // Konversation starten/fortsetzen
        this.startConversation();
    }
    
    /**
     * Erstellt die Chat-Benutzeroberfläche
     */
    createChatUI() {
        // Hauptcontainer erstellen
        this.chatContainer = document.createElement('div');
        this.chatContainer.className = 'knowledgebot-chat-container';
        this.chatContainer.innerHTML = `
            <div class="chat-header">
                <h3>KnowledgeBot</h3>
                <button class="minimize-btn">_</button>
            </div>
            <div class="chat-messages-container">
                <ul class="chat-messages"></ul>
            </div>
            <div class="chat-input-container">
                <input type="text" class="chat-input" placeholder="Stellen Sie eine Frage...">
                <button class="chat-send-btn">Senden</button>
            </div>
        `;
        
        // Referenzen zu wichtigen Elementen speichern
        this.messageList = this.chatContainer.querySelector('.chat-messages');
        this.messageInput = this.chatContainer.querySelector('.chat-input');
        this.sendButton = this.chatContainer.querySelector('.chat-send-btn');
        this.minimizeButton = this.chatContainer.querySelector('.minimize-btn');
        
        // Chat-Container zum Body hinzufügen
        document.body.appendChild(this.chatContainer);
        
        // Initial minimiert anzeigen
        this.chatContainer.classList.add('minimized');
    }
    
    /**
     * Event-Listener hinzufügen
     */
    addEventListeners() {
        // Senden-Button
        this.sendButton.addEventListener('click', () => {
            this.sendMessage();
        });
        
        // Enter-Taste im Eingabefeld
        this.messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });
        
        // Minimieren/Maximieren-Button
        this.minimizeButton.addEventListener('click', () => {
            this.chatContainer.classList.toggle('minimized');
            
            if (this.chatContainer.classList.contains('minimized')) {
                this.minimizeButton.textContent = '+';
            } else {
                this.minimizeButton.textContent = '_';
                this.messageInput.focus();
            }
        });
    }
    
    /**
     * Generiert eine eindeutige Session-ID
     */
    generateSessionId() {
        return 'kb_' + Math.random().toString(36).substring(2, 15);
    }
    
    /**
     * Startet oder setzt eine Konversation fort
     */
    startConversation() {
        // AJAX-Anfrage zum Starten einer Konversation
        $.ajax({
            url: CFG_GLPI.root_doc + '/plugins/knowledgebot/ajax/chat_api.php',
            type: 'POST',
            data: {
                action: 'startConversation',
                user_id: this.userId,
                session_id: this.sessionId
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.conversationId = response.conversation_id;
                    
                    // Konversationsverlauf laden
                    this.loadConversationHistory();
                    
                    // Willkommensnachricht anzeigen
                    this.addBotMessage("Hallo! Ich bin der KnowledgeBot. Wie kann ich Ihnen heute helfen?");
                } else {
                    console.error('Fehler beim Starten der Konversation:', response.error);
                }
            },
            error: (xhr, status, error) => {
                console.error('AJAX-Fehler:', error);
            }
        });
    }
    
    /**
     * Lädt den Konversationsverlauf
     */
    loadConversationHistory() {
        if (!this.conversationId) {
            return;
        }
        
        $.ajax({
            url: CFG_GLPI.root_doc + '/plugins/knowledgebot/ajax/chat_api.php',
            type: 'GET',
            data: {
                action: 'getHistory',
                conversation_id: this.conversationId
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    // Nachrichtenverlauf anzeigen
                    this.messageList.innerHTML = '';
                    
                    response.messages.forEach((message) => {
                        if (message.is_bot) {
                            this.addBotMessage(message.message, false);
                        } else {
                            this.addUserMessage(message.message, false);
                        }
                    });
                    
                    // Scrollen
                    this.scrollToBottom();
                }
            }
        });
    }
    
    /**
     * Sendet eine Nachricht an den Chatbot
     */
    sendMessage() {
        const message = this.messageInput.value.trim();
        
        if (!message || !this.conversationId) {
            return;
        }
        
        // Benutzernachricht zur UI hinzufügen
        this.addUserMessage(message);
        
        // Eingabefeld leeren
        this.messageInput.value = '';
        
        // Lade-Indikator anzeigen
        this.addBotMessage('<em>Denkt nach...</em>', false, 'loading-message');
        
        // AJAX-Anfrage zum Senden der Nachricht
        $.ajax({
            url: CFG_GLPI.root_doc + '/plugins/knowledgebot/ajax/chat_api.php',
            type: 'POST',
            data: {
                action: 'sendMessage',
                conversation_id: this.conversationId,
                message: message
            },
            dataType: 'json',
            success: (response) => {
                // Lade-Indikator entfernen
                this.removeLoadingMessage();
                
                if (response.success) {
                    // Bot-Antwort zur UI hinzufügen
                    this.addBotMessage(response.response.message);
                    
                    // Suchergebnisse anzeigen, wenn vorhanden
                    if (response.response.search_results && response.response.search_results.length > 0) {
                        this.displaySearchResults(response.response.search_results);
                    }
                } else {
                    this.addBotMessage("Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.");
                }
            },
            error: (xhr, status, error) => {
                // Lade-Indikator entfernen
                this.removeLoadingMessage();
                
                this.addBotMessage("Es ist ein Fehler bei der Kommunikation aufgetreten. Bitte versuchen Sie es später erneut.");
                console.error('AJAX-Fehler:', error);
            }
        });
    }
    
    /**
     * Fügt eine Benutzernachricht zur Chat-UI hinzu
     */
    addUserMessage(message, scroll = true) {
        const messageItem = document.createElement('li');
        messageItem.className = 'user-message';
        messageItem.innerHTML = `<div class="message-content">${this.escapeHtml(message)}</div>`;
        
        this.messageList.appendChild(messageItem);
        
        if (scroll) {
            this.scrollToBottom();
        }
    }
    
    /**
     * Fügt eine Bot-Nachricht zur Chat-UI hinzu
     */
    addBotMessage(message, scroll = true, className = '') {
        const messageItem = document.createElement('li');
        messageItem.className = 'bot-message ' + className;
        messageItem.innerHTML = `<div class="message-content">${message}</div>`;
        
        this.messageList.appendChild(messageItem);
        
        if (scroll) {
            this.scrollToBottom();
        }
    }
    
    /**
     * Entfernt die Lade-Nachricht
     */
    removeLoadingMessage() {
        const loadingMessage = this.messageList.querySelector('.loading-message');
        if (loadingMessage) {
            loadingMessage.remove();
        }
    }
    
    /**
     * Zeigt Suchergebnisse an
     */
    displaySearchResults(results) {
        const messageItem = document.createElement('li');
        messageItem.className = 'search-results';
        
        let html = '<div class="search-results-container">';
        html += '<h4>Gefundene Lösungen:</h4>';
        html += '<ul>';
        
        results.forEach((result) => {
            html += `<li>
                <a href="${CFG_GLPI.root_doc}/front/ticket.form.php?id=${result.ticket_id}" target="_blank">
                    ${this.escapeHtml(result.title)} (Ticket #${result.ticket_id})
                </a>
            </li>`;
        });
        
        html += '</ul></div>';
        messageItem.innerHTML = html;
        
        this.messageList.appendChild(messageItem);
        this.scrollToBottom();
    }
    
    /**
     * Scrollt zum Ende des Chat-Fensters
     */
    scrollToBottom() {
        const container = this.chatContainer.querySelector('.chat-messages-container');
        container.scrollTop = container.scrollHeight;
    }
    
    /**
     * Hilfsfunktion zum Escapen von HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}