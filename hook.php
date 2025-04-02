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

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_knowledgebot_install() {
   global $DB;

   $migration = new Migration(PLUGIN_KNOWLEDGEBOT_VERSION);
   
   $default_charset = DBConnection::getDefaultCharset();
   $default_collation = DBConnection::getDefaultCollation();
   $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();
   
   // Tabelle für Chatbot-Konversationen erstellen
   if (!$DB->tableExists('glpi_plugin_knowledgebot_conversations')) {
      $query = "CREATE TABLE `glpi_plugin_knowledgebot_conversations` (
                 `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
                 `user_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                 `session_id` varchar(255) NOT NULL,
                 `created_at` timestamp NULL DEFAULT NULL,
                 `updated_at` timestamp NULL DEFAULT NULL,
                 PRIMARY KEY (`id`),
                 KEY `user_id` (`user_id`),
                 KEY `session_id` (`session_id`)
              ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
      $DB->query($query) or die("Fehler beim Erstellen der Tabelle 'glpi_plugin_knowledgebot_conversations': " . $DB->error());
   }
   
   // Tabelle für Chatbot-Nachrichten erstellen
   if (!$DB->tableExists('glpi_plugin_knowledgebot_messages')) {
      $query = "CREATE TABLE `glpi_plugin_knowledgebot_messages` (
                 `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
                 `conversation_id` int {$default_key_sign} NOT NULL,
                 `is_bot` tinyint NOT NULL DEFAULT '0',
                 `message` text,
                 `created_at` timestamp NULL DEFAULT NULL,
                 PRIMARY KEY (`id`),
                 KEY `conversation_id` (`conversation_id`)
              ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
      $DB->query($query) or die("Fehler beim Erstellen der Tabelle 'glpi_plugin_knowledgebot_messages': " . $DB->error());
   }
   
   // Tabelle für Knowledge Base Einträge erstellen
   if (!$DB->tableExists('glpi_plugin_knowledgebot_kb_entries')) {
      $query = "CREATE TABLE `glpi_plugin_knowledgebot_kb_entries` (
                 `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
                 `ticket_id` int {$default_key_sign} NOT NULL,
                 `title` varchar(255) NOT NULL,
                 `content` text,
                 `keywords` text,
                 `created_at` timestamp NULL DEFAULT NULL,
                 `updated_at` timestamp NULL DEFAULT NULL,
                 PRIMARY KEY (`id`),
                 KEY `ticket_id` (`ticket_id`)
              ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
      $DB->query($query) or die("Fehler beim Erstellen der Tabelle 'glpi_plugin_knowledgebot_kb_entries': " . $DB->error());
   }
   
   // Konfigurationsdaten einfügen
   if (!$DB->tableExists('glpi_plugin_knowledgebot_configs')) {
      $query = "CREATE TABLE `glpi_plugin_knowledgebot_configs` (
                 `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
                 `key` varchar(255) NOT NULL,
                 `value` text,
                 PRIMARY KEY (`id`),
                 UNIQUE KEY `key` (`key`)
              ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
      $DB->query($query) or die("Fehler beim Erstellen der Tabelle 'glpi_plugin_knowledgebot_configs': " . $DB->error());
      
      // Standardkonfigurationen einfügen
      $DB->insert('glpi_plugin_knowledgebot_configs', [
         'key' => 'auto_index_tickets',
         'value' => '1'
      ]);
      
      // Ollama-Konfigurationen hinzufügen
      $DB->insert('glpi_plugin_knowledgebot_configs', [
         'key' => 'ollama_enabled',
         'value' => '1'
      ]);
      
      $DB->insert('glpi_plugin_knowledgebot_configs', [
         'key' => 'ollama_url',
         'value' => 'http://localhost:11434'
      ]);
      
      $DB->insert('glpi_plugin_knowledgebot_configs', [
         'key' => 'ollama_model',
         'value' => 'llama2'
      ]);
      
      $DB->insert('glpi_plugin_knowledgebot_configs', [
         'key' => 'system_prompt',
         'value' => "Du bist ein hilfreicher IT-Support-Assistent für ein GLPI-Ticketsystem. Deine Aufgabe ist es, Benutzeranfragen basierend auf gelösten Tickets zu beantworten. Sei höflich und präzise. Wenn du eine Antwort nicht kennst, verweise den Benutzer freundlich an das Supportteam."
      ]);
   }

   return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_knowledgebot_uninstall() {
   global $DB;

   // Alle Plugin-Tabellen entfernen
   $tables = [
      'glpi_plugin_knowledgebot_conversations',
      'glpi_plugin_knowledgebot_messages',
      'glpi_plugin_knowledgebot_kb_entries',
      'glpi_plugin_knowledgebot_configs'
   ];
   
   foreach ($tables as $table) {
      if ($DB->tableExists($table)) {
         $DB->query("DROP TABLE `$table`");
      }
   }
   
   return true;
}
