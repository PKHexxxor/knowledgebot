<?php
/**
 * -------------------------------------------------------------------------
 * KnowledgeBot plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of KnowledgeBot.
 *
 * KnowledgeBot is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * KnowledgeBot is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with KnowledgeBot. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2025 by KnowledgeBot plugin team.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/PKHexxxor/knowledgebot
 * -------------------------------------------------------------------------
 */

class PluginKnowledgebotChatbot {
    private $db;
    private $ollamaEnabled;
    private $ollamaUrl;
    private $ollamaModel;
    private $systemPrompt;
    
    /**
     * Konstruktor - lädt die Konfiguration
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
        
        // Ollama-Konfiguration laden
        $this->loadOllamaConfig();
    }
    
    /**
     * Lädt die Ollama-Konfiguration aus der Datenbank
     */
    private function loadOllamaConfig() {
        $config = $this->db->request([
            'FROM' => 'glpi_plugin_knowledgebot_configs',
            'WHERE' => [
                'key' => ['ollama_enabled', 'ollama_url', 'ollama_model', 'system_prompt']
            ]
        ]);
        
        $configValues = [];
        foreach ($config as $item) {
            $configValues[$item['key']] = $item['value'];
        }
        
        $this->ollamaEnabled = isset($configValues['ollama_enabled']) ? 
            filter_var($configValues['ollama_enabled'], FILTER_VALIDATE_BOOLEAN) : false;
        
        $this->ollamaUrl = $configValues['ollama_url'] ?? 'http://localhost:11434';
        $this->ollamaModel = $configValues['ollama_model'] ?? 'llama2';
        
        $this->systemPrompt = $configValues['system_prompt'] ?? 
            "Du bist ein hilfreicher IT-Support-Assistent für ein GLPI-Ticketsystem. Deine Aufgabe ist es, Benutzeranfragen basierend auf gelösten Tickets zu beantworten. Sei höflich und präzise. Wenn du eine Antwort nicht kennst, verweise den Benutzer freundlich an das Supportteam.";
    }
    
    /**
     * Erstellt oder setzt eine Konversation fort
     *
     * @param int $userId Die Benutzer-ID
     * @param string|null $sessionId Die Session-ID
     * @return int Die Konversations-ID
     */
    public function startConversation($userId, $sessionId = null) {
        if (!$sessionId) {
            $sessionId = md5(uniqid(rand(), true));
        }
        
        // Prüfen, ob die Konversation bereits existiert
        $existingConversation = $this->db->request([
            'FROM' => 'glpi_plugin_knowledgebot_conversations',
            'WHERE' => [
                'user_id' => $userId,
                'session_id' => $sessionId
            ]
        ])->current();
        
        if ($existingConversation) {
            return $existingConversation['id'];
        }
        
        // Neue Konversation erstellen
        $this->db->insert('glpi_plugin_knowledgebot_conversations', [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->db->insertId();
    }
    
    /**
     * Nachricht an den Chatbot senden
     *
     * @param int $conversationId Die Konversations-ID
     * @param string $message Die Nachricht
     * @return array Die Antwort
     */
    public function sendMessage($conversationId, $message) {
        if (empty($message)) {
            return null;
        }
        
        // Benutzernachricht speichern
        $this->db->insert('glpi_plugin_knowledgebot_messages', [
            'conversation_id' => $conversationId,
            'is_bot' => 0,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Knowledge Base nach relevanten Informationen durchsuchen
        $searchResults = $this->searchKnowledgeBase($message);
        
        // Antwort generieren
        $response = "";
        
        if ($this->ollamaEnabled) {
            // Ollama für KI-gestützte Antwort verwenden
            $response = $this->getAIResponse($message, $conversationId, $searchResults);
        } else {
            // Standard-Antwortgenerierung ohne KI
            if (empty($searchResults)) {
                $response = "Leider konnte ich keine passenden Informationen zu Ihrer Anfrage finden. Können Sie Ihre Frage anders formulieren?";
            } else {
                $response = "Ich habe folgende relevante Informationen gefunden:\n\n";
                
                foreach ($searchResults as $index => $result) {
                    $response .= ($index + 1) . ". " . $result['title'] . " (Ticket #" . $result['ticket_id'] . ")\n";
                }
                
                $response .= "\nMöchten Sie weitere Details zu einem dieser Einträge?";
            }
        }
        
        // Bot-Antwort speichern
        $this->db->insert('glpi_plugin_knowledgebot_messages', [
            'conversation_id' => $conversationId,
            'is_bot' => 1,
            'message' => $response,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Konversation aktualisieren
        $this->db->update('glpi_plugin_knowledgebot_conversations', [
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'id' => $conversationId
        ]);
        
        return [
            'message' => $response,
            'search_results' => $searchResults
        ];
    }
    
    /**
     * Generiert eine KI-basierte Antwort mit Ollama
     * 
     * @param string $message Die Benutzernachricht
     * @param int $conversationId Die ID der aktuellen Konversation
     * @param array $searchResults Suchergebnisse aus der Knowledge Base
     * @return string Die generierte Antwort
     */
    private function getAIResponse($message, $conversationId, $searchResults) {
        try {
            // Konversationshistorie abrufen (begrenzt auf die letzten 5 Nachrichten)
            $history = $this->getConversationHistory($conversationId, 5);
            
            // Konversationsverlauf für den Prompt formatieren
            $conversationContext = [];
            foreach ($history as $entry) {
                $role = $entry['is_bot'] ? 'assistant' : 'user';
                $conversationContext[] = [
                    'role' => $role,
                    'content' => $entry['message']
                ];
            }
            
            // Aktuelle Frage zum Kontext hinzufügen
            $conversationContext[] = [
                'role' => 'user',
                'content' => $message
            ];
            
            // Kontext aus den Suchergebnissen erstellen
            $knowledgeContext = "";
            if (!empty($searchResults)) {
                $knowledgeContext = "Hier sind relevante Informationen aus früheren Tickets:\n\n";
                
                foreach ($searchResults as $index => $result) {
                    // Ticketdetails abrufen
                    $ticketDetails = $this->getTicketData($result['ticket_id']);
                    
                    if ($ticketDetails) {
                        $knowledgeContext .= "Ticket #" . $result['ticket_id'] . ": " . $result['title'] . "\n";
                        $knowledgeContext .= "Problem: " . substr($ticketDetails['problem'], 0, 200) . "...\n";
                        
                        if (isset($ticketDetails['solution']) && !empty($ticketDetails['solution'])) {
                            $knowledgeContext .= "Lösung: " . $ticketDetails['solution'] . "\n";
                        }
                        
                        $knowledgeContext .= "\n";
                    }
                }
            } else {
                $knowledgeContext = "Keine relevanten Informationen in der Knowledge Base gefunden.";
            }
            
            // Vollständigen Prompt für Ollama erstellen
            $messages = [
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt . "\n\nKnowledge Base Kontext:\n" . $knowledgeContext
                ]
            ];
            
            // Konversationshistorie hinzufügen (max. 5 Nachrichten)
            foreach ($conversationContext as $msg) {
                $messages[] = $msg;
            }
            
            // API-Anfrage an Ollama senden
            $response = $this->callOllamaAPI($messages);
            
            // Fallback, wenn die API-Anfrage fehlschlägt
            if ($response === null) {
                return "Entschuldigung, ich konnte keine Antwort generieren. Hier sind jedoch relevante Informationen:\n\n" . 
                    $this->formatSearchResults($searchResults);
            }
            
            return $response;
        } catch (Exception $e) {
            error_log("Fehler bei der Ollama-Anfrage: " . $e->getMessage());
            
            // Fallback-Antwort bei Fehlern
            return "Entschuldigung, es gab ein technisches Problem mit meiner KI-Komponente. Hier sind jedoch relevante Informationen:\n\n" . 
                $this->formatSearchResults($searchResults);
        }
    }
    
    /**
     * Sendet eine Anfrage an die Ollama API
     * 
     * @param array $messages Die Nachrichten im Chat-Format
     * @return string|null Die generierte Antwort oder null bei Fehlern
     */
    private function callOllamaAPI($messages) {
        $url = $this->ollamaUrl . '/api/chat';
        
        $data = [
            'model' => $this->ollamaModel,
            'messages' => $messages,
            'stream' => false,
            'options' => [
                'temperature' => 0.7,
                'top_p' => 0.9,
                'max_tokens' => 800
            ]
        ];
        
        // cURL-Anfrage initialisieren
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Antwort erhalten
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Antwort verarbeiten
        if ($status === 200 && $response) {
            $responseData = json_decode($response, true);
            if (isset($responseData['message']['content'])) {
                return $responseData['message']['content'];
            }
        }
        
        return null;
    }
    
    /**
     * Formatiert Suchergebnisse als Text
     * 
     * @param array $searchResults Die Suchergebnisse
     * @return string Formatierter Text
     */
    private function formatSearchResults($searchResults) {
        if (empty($searchResults)) {
            return "Keine relevanten Informationen gefunden.";
        }
        
        $formatted = "";
        foreach ($searchResults as $index => $result) {
            $formatted .= ($index + 1) . ". " . $result['title'] . " (Ticket #" . $result['ticket_id'] . ")\n";
        }
        
        return $formatted;
    }
    
    /**
     * Ruft die Details eines Tickets ab
     *
     * @param int $ticketId Ticket-ID
     * @return array|null Ticket-Details oder null, wenn nicht gefunden
     */
    private function getTicketData($ticketId) {
        global $DB;
        
        $query = "SELECT t.id, t.name, t.content as problem, t.date, t.closedate, t.status,
                    s.content as solution
                 FROM glpi_tickets t
                 LEFT JOIN glpi_itilsolutions s ON s.items_id = t.id AND s.itemtype = 'Ticket'
                 WHERE t.id = $ticketId";
        
        $result = $DB->query($query);
        if ($result && $DB->numrows($result) > 0) {
            return $DB->fetchAssoc($result);
        }
        
        return null;
    }
    
    /**
     * Durchsucht die Knowledge Base nach relevanten Informationen
     *
     * @param string $query Suchanfrage
     * @return array Suchergebnisse
     */
    public function searchKnowledgeBase($query) {
        global $DB;
        
        // Einfache Suchimplementierung
        $searchTerms = explode(' ', $query);
        $searchConditions = [];
        
        foreach ($searchTerms as $term) {
            if (strlen($term) > 2) {
                $escapedTerm = $DB->escape("%$term%");
                $searchConditions[] = "title LIKE '$escapedTerm' OR content LIKE '$escapedTerm' OR keywords LIKE '$escapedTerm'";
            }
        }
        
        if (empty($searchConditions)) {
            return [];
        }
        
        $searchQuery = "SELECT id, ticket_id, title FROM glpi_plugin_knowledgebot_kb_entries 
                       WHERE " . implode(' OR ', $searchConditions) . " 
                       ORDER BY updated_at DESC LIMIT 10";
        
        $results = $DB->query($searchQuery);
        $entries = [];
        
        while ($row = $DB->fetchAssoc($results)) {
            $entries[] = $row;
        }
        
        return $entries;
    }
    
    /**
     * Ruft den Konversationsverlauf ab
     *
     * @param int $conversationId Konversations-ID
     * @param int $limit Maximale Anzahl von Nachrichten
     * @return array Konversationsverlauf
     */
    public function getConversationHistory($conversationId, $limit = 10) {
        $messages = $this->db->request([
            'FROM' => 'glpi_plugin_knowledgebot_messages',
            'WHERE' => ['conversation_id' => $conversationId],
            'ORDER' => ['created_at DESC'],
            'LIMIT' => $limit
        ]);
        
        $history = [];
        foreach ($messages as $message) {
            $history[] = $message;
        }
        
        // Umkehren, damit die ältesten Nachrichten zuerst kommen
        return array_reverse($history);
    }
}