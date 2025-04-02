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

class PluginKnowledgebotConfig extends CommonGLPI {
    
    static $rightname = 'config';
    
    /**
     * Get name of this type by language of the user connected
     *
     * @param integer $nb number of elements
     * @return string name of this type
     */
    static function getTypeName($nb = 0) {
        return __('KnowledgeBot Konfiguration', 'knowledgebot');
    }
    
    /**
     * Gibt den Tab-Namen für das Item zurück
     *
     * @param CommonGLPI $item das Item-Objekt
     * @param integer $withtemplate 1 wenn es sich um ein Vorlagenformular handelt
     * @return string|array Name des Tabs
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item::getType() == 'Config' && Session::haveRight('config', UPDATE)) {
            return self::getTypeName();
        }
        return '';
    }
    
    /**
     * Zeigt den Inhalt des Tabs an
     *
     * @param CommonGLPI $item das Item-Objekt
     * @param integer $tabnum Nummer des anzuzeigenden Tabs
     * @param integer $withtemplate 1 wenn es sich um ein Vorlagenformular handelt
     * @return boolean True, wenn Inhalt angezeigt wurde
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item::getType() == 'Config') {
            self::showConfigForm();
            return true;
        }
        return false;
    }
    
    /**
     * Zeigt das Konfigurationsformular an
     *
     * @return void
     */
    static function showConfigForm() {
        global $DB;
        
        // Aktuelle Konfiguration laden
        $config = [];
        $result = $DB->request([
            'FROM' => 'glpi_plugin_knowledgebot_configs'
        ]);
        
        foreach ($result as $item) {
            $config[$item['key']] = $item['value'];
        }
        
        // Standardwerte setzen, falls nicht vorhanden
        $config['auto_index_tickets'] = $config['auto_index_tickets'] ?? '1';
        $config['ollama_enabled'] = $config['ollama_enabled'] ?? '0';
        $config['ollama_url'] = $config['ollama_url'] ?? 'http://localhost:11434';
        $config['ollama_model'] = $config['ollama_model'] ?? 'llama2';
        $config['system_prompt'] = $config['system_prompt'] ?? '';
        
        echo "<div class='center'>";
        echo "<form method='post' action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
        
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Allgemeine Einstellungen', 'knowledgebot') . "</th></tr>";
        
        // Automatische Ticket-Indizierung
        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Tickets automatisch indizieren', 'knowledgebot') . "</td>";
        echo "<td colspan='3'>";
        echo "<select name='auto_index_tickets'>";
        echo "<option value='1'" . ($config['auto_index_tickets'] == '1' ? " selected" : "") . ">" . __('Ja', 'knowledgebot') . "</option>";
        echo "<option value='0'" . ($config['auto_index_tickets'] == '0' ? " selected" : "") . ">" . __('Nein', 'knowledgebot') . "</option>";
        echo "</select>";
        echo "</td></tr>";
        
        // Ollama-Einstellungen
        echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Ollama KI-Integration', 'knowledgebot') . "</th></tr>";
        
        // Ollama aktivieren
        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Ollama KI aktivieren', 'knowledgebot') . "</td>";
        echo "<td colspan='3'>";
        echo "<select name='ollama_enabled' id='ollama_enabled'>";
        echo "<option value='1'" . ($config['ollama_enabled'] == '1' ? " selected" : "") . ">" . __('Ja', 'knowledgebot') . "</option>";
        echo "<option value='0'" . ($config['ollama_enabled'] == '0' ? " selected" : "") . ">" . __('Nein', 'knowledgebot') . "</option>";
        echo "</select>";
        echo "</td></tr>";
        
        // Ollama URL
        echo "<tr class='tab_bg_2 ollama_config'>";
        echo "<td>" . __('Ollama URL', 'knowledgebot') . "</td>";
        echo "<td colspan='3'>";
        echo "<input type='text' name='ollama_url' id='ollama_url' size='50' value='" . $config['ollama_url'] . "'>";
        echo "</td></tr>";
        
        // Ollama Modell
        echo "<tr class='tab_bg_2 ollama_config'>";
        echo "<td>" . __('Ollama Modell', 'knowledgebot') . "</td>";
        echo "<td colspan='3'>";
        echo "<input type='text' name='ollama_model' id='ollama_model' size='50' value='" . $config['ollama_model'] . "'>";
        echo "<div class='comment'>" . __('Beispiele: llama2, mistral, mixtral, gemma', 'knowledgebot') . "</div>";
        echo "</td></tr>";
        
        // System Prompt
        echo "<tr class='tab_bg_2 ollama_config'>";
        echo "<td>" . __('System Prompt', 'knowledgebot') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea name='system_prompt' id='system_prompt' rows='5' cols='80'>" . $config['system_prompt'] . "</textarea>";
        echo "<div class='comment'>" . __('Anweisung für das KI-Modell, wie es reagieren soll', 'knowledgebot') . "</div>";
        echo "</td></tr>";
        
        // Formular-Buttons
        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='4' class='center'>";
        echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
        echo "<input type='submit' name='update' value=\"" . _sx('button', 'Speichern') . "\" class='submit'>";
        echo "</td></tr>";
        
        echo "</table>";
        echo "</form>";
        echo "</div>";
        
        // JavaScript für dynamische Formularelemente
        echo "<script>
        function toggleOllamaConfig() {
            var enabled = document.getElementById('ollama_enabled').value === '1';
            var elements = document.getElementsByClassName('ollama_config');
            
            for (var i = 0; i < elements.length; i++) {
                elements[i].style.display = enabled ? 'table-row' : 'none';
            }
        }
        
        // Bei Seitenladung ausführen
        document.addEventListener('DOMContentLoaded', function() {
            toggleOllamaConfig();
            
            // Event-Listener für Änderungen
            document.getElementById('ollama_enabled').addEventListener('change', toggleOllamaConfig);
        });
        </script>";
    }
    
    /**
     * Formularverarbeitung für die Konfigurationsseite
     *
     * @param array $post POST-Daten des Formulars
     * @return void
     */
    static function updateConfig($post) {
        global $DB;
        
        // CSRF-Schutz
        Session::checkCSRF($post);
        
        // Konfigurationswerte aktualisieren
        foreach (['auto_index_tickets', 'ollama_enabled', 'ollama_url', 'ollama_model', 'system_prompt'] as $key) {
            $value = $post[$key] ?? '';
            
            // Vorhandenen Eintrag aktualisieren oder neuen hinzufügen
            $exists = $DB->request([
                'COUNT' => 'id',
                'FROM' => 'glpi_plugin_knowledgebot_configs',
                'WHERE' => ['key' => $key]
            ])->current()['COUNT'];
            
            if ($exists) {
                $DB->update('glpi_plugin_knowledgebot_configs', [
                    'value' => $value
                ], [
                    'key' => $key
                ]);
            } else {
                $DB->insert('glpi_plugin_knowledgebot_configs', [
                    'key' => $key,
                    'value' => $value
                ]);
            }
        }
        
        Session::addMessageAfterRedirect(__('Konfiguration erfolgreich aktualisiert', 'knowledgebot'), true, INFO);
    }
}