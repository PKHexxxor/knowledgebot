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

// AJAX-Handler für den KnowledgeBot Chatbot

include('../../../inc/includes.php');

// Prüfen, ob der Benutzer eingeloggt ist
Session::checkLoginUser();

// CSRF-Schutz
if (isset($_POST['_glpi_csrf_token'])) {
    Session::checkCSRF($_POST);
}

// Chatbot-Instanz laden
$chatbot = new PluginKnowledgebotChatbot();

// Aktionsparameter auslesen
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

// Antwort basierend auf der Aktion generieren
switch ($action) {
    case 'startConversation':
        $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : (isset($_SESSION['glpiID']) ? $_SESSION['glpiID'] : 0);
        $sessionId = isset($_POST['session_id']) ? $_POST['session_id'] : null;
        
        $conversationId = $chatbot->startConversation($userId, $sessionId);
        
        echo json_encode([
            'success' => true,
            'conversation_id' => $conversationId
        ]);
        break;
        
    case 'sendMessage':
        if (!isset($_POST['conversation_id']) || !isset($_POST['message'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Fehlende Parameter'
            ]);
            exit;
        }
        
        $conversationId = intval($_POST['conversation_id']);
        $message = trim($_POST['message']);
        
        // Nachricht an den Chatbot senden
        $response = $chatbot->sendMessage($conversationId, $message);
        
        echo json_encode([
            'success' => true,
            'response' => $response
        ]);
        break;
        
    case 'getHistory':
        if (!isset($_REQUEST['conversation_id'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Konversations-ID fehlt'
            ]);
            exit;
        }
        
        $conversationId = intval($_REQUEST['conversation_id']);
        $limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 10;
        
        // Nachrichtenverlauf abrufen
        $messages = $chatbot->getConversationHistory($conversationId, $limit);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Unbekannte Aktion'
        ]);
}
