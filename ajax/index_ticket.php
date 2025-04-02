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

// AJAX-Handler zum Indizieren eines Tickets in der Knowledge Base

include('../../../inc/includes.php');

// Prüfen, ob der Benutzer eingeloggt ist
Session::checkLoginUser();

// CSRF-Schutz
if (isset($_POST['_glpi_csrf_token'])) {
    Session::checkCSRF($_POST);
}

// Ticket-ID aus der Anfrage extrahieren
$ticketId = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;

if ($ticketId <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Ungültige Ticket-ID'
    ]);
    exit;
}

// Prüfen, ob das Ticket existiert und der Benutzer die Rechte hat
$ticket = new Ticket();
if (!$ticket->getFromDB($ticketId) || !$ticket->canView()) {
    echo json_encode([
        'success' => false,
        'error' => 'Ticket nicht gefunden oder unzureichende Rechte'
    ]);
    exit;
}

// KnowledgeBase-Instanz erstellen
$knowledgebase = new PluginKnowledgebotKnowledgeBase();

// Ticket in der Knowledge Base indizieren
$result = $knowledgebase->indexTicket($ticketId);

// Ergebnis zurückgeben
if ($result) {
    $message = 'Ticket erfolgreich zur Knowledge Base hinzugefügt';
    
    // Prüfen, ob das Ticket bereits in der KB war
    global $DB;
    $exists = $DB->request([
        'COUNT' => 'id',
        'FROM' => 'glpi_plugin_knowledgebot_kb_entries',
        'WHERE' => ['ticket_id' => $ticketId]
    ])->current()['COUNT'];
    
    if ($exists > 0) {
        $message = 'Knowledge Base Eintrag erfolgreich aktualisiert';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Fehler beim Indizieren des Tickets. Möglicherweise fehlt eine Lösung.'
    ]);
}
