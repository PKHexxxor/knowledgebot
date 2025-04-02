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

class PluginKnowledgebotKnowledgeBase extends CommonGLPI {

   static $rightname = 'plugin_knowledgebot_knowledgebase';
   
   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb = 0) {
      return __('Knowledge Base', 'knowledgebot');
   }

   /**
    * Get the menu name for this item
    *
    * @return string
    */
   static function getMenuName() {
      return __('Knowledge Base', 'knowledgebot');
   }

   /**
    * Get the tab name used for item
    *
    * @param object $item the item object
    * @param integer $withtemplate 1 if is a template form
    * @return string|array name of the tab
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (!$withtemplate) {
         if ($item->getType() == 'Ticket' && $item->fields['status'] == CommonITILObject::CLOSED) {
            return __('Knowledge Base', 'knowledgebot');
         }
      }
      return '';
   }

   /**
    * Display content of the tab
    *
    * @param object $item
    * @param integer $tabnum number of the tab to display
    * @param integer $withtemplate 1 if is a template form
    * @return boolean True if content displayed
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Ticket') {
         self::showKnowledgeBotTab($item);
         return true;
      }
      return false;
   }

   /**
    * Display the KnowledgeBot information for the ticket
    *
    * @param Ticket $ticket The ticket object
    * @return void
    */
   static function showKnowledgeBotTab(Ticket $ticket) {
      echo "<div class='knowledgebot-tab'>";
      echo "<h3>" . __('Ticket als Knowledge Base Eintrag verwalten', 'knowledgebot') . "</h3>";
      
      global $DB;
      
      // Prüfen, ob das Ticket bereits in der Knowledge Base ist
      $exists = $DB->request([
          'COUNT' => 'id',
          'FROM' => 'glpi_plugin_knowledgebot_kb_entries',
          'WHERE' => ['ticket_id' => $ticket->getID()]
      ])->current()['COUNT'];
      
      $addToKBUrl = Plugin::getWebDir('knowledgebot') . '/ajax/index_ticket.php';
      
      if ($exists) {
          echo "<p>" . __('Dieses Ticket ist bereits in der Knowledge Base indiziert.', 'knowledgebot') . "</p>";
          echo "<button class='update-kb-btn' data-ticket-id='" . $ticket->getID() . "' data-url='$addToKBUrl'>" . __('Knowledge Base Eintrag aktualisieren', 'knowledgebot') . "</button>";
      } else {
          echo "<p>" . __('Dieses Ticket ist noch nicht in der Knowledge Base indiziert.', 'knowledgebot') . "</p>";
          echo "<button class='add-to-kb-btn' data-ticket-id='" . $ticket->getID() . "' data-url='$addToKBUrl'>" . __('Zur Knowledge Base hinzufügen', 'knowledgebot') . "</button>";
      }
      
      echo "</div>";
      
      // JavaScript für die AJAX-Funktionalität
      echo "<script>
          $(document).ready(function() {
              $('.add-to-kb-btn, .update-kb-btn').on('click', function() {
                  var ticketId = $(this).data('ticket-id');
                  var url = $(this).data('url');
                  
                  $.ajax({
                      url: url,
                      type: 'POST',
                      data: {
                          ticket_id: ticketId
                      },
                      success: function(response) {
                          alert('Knowledge Base wurde erfolgreich aktualisiert.');
                          location.reload();
                      },
                      error: function() {
                          alert('Es ist ein Fehler aufgetreten.');
                      }
                  });
              });
          });
      </script>";
   }
   
   /**
    * Index a ticket in the knowledge base
    *
    * @param int $ticketId The ticket ID to index
    * @return boolean True if successful
    */
   public function indexTicket($ticketId) {
      global $DB;
      
      $ticket = new Ticket();
      if (!$ticket->getFromDB($ticketId)) {
         return false;
      }
      
      // Prüfen, ob das Ticket bereits einen Lösungseintrag hat
      $itilSolution = new ITILSolution();
      $solutions = $itilSolution->find([
         'items_id' => $ticketId,
         'itemtype' => 'Ticket'
      ]);
      
      if (count($solutions) == 0) {
         // Kein Lösungseintrag vorhanden
         return false;
      }
      
      // Die erste Lösung verwenden
      $solution = reset($solutions);
      
      // Prüfen, ob der Eintrag bereits existiert
      $exists = $DB->request([
         'COUNT' => 'id',
         'FROM' => 'glpi_plugin_knowledgebot_kb_entries',
         'WHERE' => ['ticket_id' => $ticketId]
      ])->current()['COUNT'];
      
      $keywords = $this->extractKeywords($ticket->fields['name'] . ' ' . $ticket->fields['content'] . ' ' . $solution['content']);
      
      if ($exists) {
         // Eintrag aktualisieren
         $DB->update('glpi_plugin_knowledgebot_kb_entries', [
            'title' => $ticket->fields['name'],
            'content' => $ticket->fields['content'] . "\n\n" . $solution['content'],
            'keywords' => $keywords,
            'updated_at' => date('Y-m-d H:i:s')
         ], [
            'ticket_id' => $ticketId
         ]);
      } else {
         // Neuen Eintrag erstellen
         $DB->insert('glpi_plugin_knowledgebot_kb_entries', [
            'ticket_id' => $ticketId,
            'title' => $ticket->fields['name'],
            'content' => $ticket->fields['content'] . "\n\n" . $solution['content'],
            'keywords' => $keywords,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
         ]);
      }
      
      return true;
   }
   
   /**
    * Extract keywords from text
    *
    * @param string $text The text to extract keywords from
    * @return string Comma-separated keywords
    */
   private function extractKeywords($text) {
      // Einfache Implementierung - könnte durch NLP-Verfahren erweitert werden
      $text = strtolower($text);
      
      // Stoppwörter entfernen
      $stopwords = ['der', 'die', 'das', 'ein', 'eine', 'und', 'oder', 'aber', 'wenn', 'dann', 'ist', 'sind', 'war', 'wurden'];
      foreach ($stopwords as $word) {
          $text = str_replace(' ' . $word . ' ', ' ', $text);
      }
      
      // Sonderzeichen entfernen
      $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
      
      // Wörter nach Häufigkeit zählen
      $words = explode(' ', $text);
      $words = array_filter($words, function($word) {
          return strlen($word) > 3; // Nur Wörter mit mindestens 4 Buchstaben
      });
      
      $wordCounts = array_count_values($words);
      arsort($wordCounts);
      
      // Die 10 häufigsten Wörter auswählen
      $keywords = array_slice(array_keys($wordCounts), 0, 10);
      
      return implode(', ', $keywords);
   }

   /**
    * Search the knowledge base
    *
    * @param string $query The search query
    * @return array Search results
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
}