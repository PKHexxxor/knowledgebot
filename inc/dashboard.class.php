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

class PluginKnowledgebotDashboard extends CommonGLPI {
    
    static $rightname = 'config'; // Berechtigungseinstellungen
    
    /**
     * Get name of this type by language of the user connected
     *
     * @param integer $nb number of elements
     * @return string name of this type
     */
    static function getTypeName($nb = 0) {
        return __('KnowledgeBot Dashboard', 'knowledgebot');
    }
    
    /**
     * Get the menu name for this item
     *
     * @return string
     */
    static function getMenuName() {
        return __('KnowledgeBot', 'knowledgebot');
    }
    
    /**
     * Check right on an item
     *
     * @return boolean
     */
    static function canView() {
        return Session::haveRight(self::$rightname, READ);
    }
    
    /**
     * Check right on an item
     *
     * @return boolean
     */
    static function canCreate() {
        return Session::haveRight(self::$rightname, UPDATE);
    }
    
    /**
     * Display the dashboard content
     *
     * @return void
     */
    function showDashboard() {
        global $DB;
        
        echo "<div class='knowledgebot-dashboard'>";
        echo "<div class='card'>";
        echo "<div class='card-header'><h3>" . __('Knowledge Base Statistiken', 'knowledgebot') . "</h3></div>";
        
        // Statistiken abrufen
        $kbCount = $DB->request([
            'COUNT' => 'id',
            'FROM' => 'glpi_plugin_knowledgebot_kb_entries'
        ])->current()['COUNT'];
        
        $messageCount = $DB->request([
            'COUNT' => 'id',
            'FROM' => 'glpi_plugin_knowledgebot_messages'
        ])->current()['COUNT'];
        
        $conversationCount = $DB->request([
            'COUNT' => 'id',
            'FROM' => 'glpi_plugin_knowledgebot_conversations'
        ])->current()['COUNT'];
        
        // Anzeige der Statistiken
        echo "<div class='kb-stats-grid'>";
        
        // Knowledge Base Einträge
        echo "<div class='stat-card'>";
        echo "<div class='stat-label'>" . __('Knowledge Base Einträge', 'knowledgebot') . "</div>";
        echo "<div class='stat-value'>$kbCount</div>";
        echo "</div>";
        
        // Chatbot-Nachrichten
        echo "<div class='stat-card'>";
        echo "<div class='stat-label'>" . __('Chatbot-Nachrichten', 'knowledgebot') . "</div>";
        echo "<div class='stat-value'>$messageCount</div>";
        echo "</div>";
        
        // Konversationen
        echo "<div class='stat-card'>";
        echo "<div class='stat-label'>" . __('Konversationen', 'knowledgebot') . "</div>";
        echo "<div class='stat-value'>$conversationCount</div>";
        echo "</div>";
        
        echo "</div>"; // End kb-stats-grid
        echo "</div>"; // End card
        
        // Neueste Knowledge Base Einträge
        echo "<div class='card'>";
        echo "<div class='card-header'><h3>" . __('Neueste Knowledge Base Einträge', 'knowledgebot') . "</h3></div>";
        
        $entries = $DB->request([
            'FROM' => 'glpi_plugin_knowledgebot_kb_entries',
            'ORDER' => ['created_at DESC'],
            'LIMIT' => 10
        ]);
        
        if (count($entries) > 0) {
            echo "<table class='kb-entries-table'>";
            echo "<tr>";
            echo "<th>" . __('Ticket-ID', 'knowledgebot') . "</th>";
            echo "<th>" . __('Titel', 'knowledgebot') . "</th>";
            echo "<th>" . __('Erstellt am', 'knowledgebot') . "</th>";
            echo "<th>" . __('Aktionen', 'knowledgebot') . "</th>";
            echo "</tr>";
            
            foreach ($entries as $entry) {
                echo "<tr>";
                echo "<td><a href='" . $CFG_GLPI['root_doc'] . "/front/ticket.form.php?id=" . $entry['ticket_id'] . "' target='_blank'>" . $entry['ticket_id'] . "</a></td>";
                echo "<td>" . $entry['title'] . "</td>";
                echo "<td>" . Html::convDateTime($entry['created_at']) . "</td>";
                echo "<td>";
                echo "<a href='" . $CFG_GLPI['root_doc'] . "/front/ticket.form.php?id=" . $entry['ticket_id'] . "' class='kb-action-btn' target='_blank'>" . __('Ticket anzeigen', 'knowledgebot') . "</a>";
                echo "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>" . __('Keine Knowledge Base Einträge vorhanden.', 'knowledgebot') . "</p>";
        }
        
        echo "</div>"; // End card
        echo "</div>"; // End knowledgebot-dashboard
    }
    
    /**
     * Display the main dashboard page
     *
     * @return void
     */
    static function displayDashboard() {
        global $CFG_GLPI;
        
        $dashboard = new self();
        
        Html::header(
            self::getTypeName(),
            $_SERVER['PHP_SELF'],
            'tools',
            'PluginKnowledgebotDashboard'
        );
        
        $dashboard->showDashboard();
        
        Html::footer();
    }
}