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

use Glpi\Plugin\Hooks;

define('PLUGIN_KNOWLEDGEBOT_VERSION', '0.0.1');

// Minimal GLPI version, inclusive
define('PLUGIN_KNOWLEDGEBOT_MIN_GLPI', '10.0.0');
// Maximum GLPI version, exclusive
define('PLUGIN_KNOWLEDGEBOT_MAX_GLPI', '10.0.99');

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_knowledgebot() {
   global $PLUGIN_HOOKS, $CFG_GLPI;

   // CSRF compliance
   $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['knowledgebot'] = true;

   // Add CSS and JavaScript 
   $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['knowledgebot'] = 'js/knowledgebot.js';
   $PLUGIN_HOOKS[Hooks::ADD_CSS]['knowledgebot'] = 'css/knowledgebot.css';

   // Add ticket hooks for knowledge base
   $PLUGIN_HOOKS[Hooks::ITEM_UPDATE]['knowledgebot'] = [
      'Ticket' => 'plugin_knowledgebot_ticket_update'
   ];

   // Config page
   if (Session::haveRight('config', UPDATE)) {
      $PLUGIN_HOOKS['config_page']['knowledgebot'] = 'front/config.form.php';
   }

   // Add menu entry in Setup > Plugins
   if (Session::haveRight('config', READ)) {
      $PLUGIN_HOOKS['menu_toadd']['knowledgebot'] = ['tools' => 'PluginKnowledgebotDashboard'];
   }

   // Add tab to ticket
   Plugin::registerClass('PluginKnowledgebotKnowledgeBase', [
      'addtabon' => ['Ticket']
   ]);
}

/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_knowledgebot() {
   return [
      'name'           => 'KnowledgeBot',
      'version'        => PLUGIN_KNOWLEDGEBOT_VERSION,
      'author'         => 'KnowledgeBot Team',
      'license'        => 'GPLv2+',
      'homepage'       => 'https://github.com/PKHexxxor/knowledgebot',
      'requirements'   => [
         'glpi' => [
            'min' => PLUGIN_KNOWLEDGEBOT_MIN_GLPI,
            'max' => PLUGIN_KNOWLEDGEBOT_MAX_GLPI,
         ],
         'php' => [
            'min' => '7.4.0'
         ]
      ]
   ];
}

/**
 * Check pre-requisites before install
 * OPTIONAL, but recommended
 *
 * @return boolean
 */
function plugin_knowledgebot_check_prerequisites() {
   // Check PHP extensions
   if (!extension_loaded('mysqli')) {
      echo "Die mysqli-Erweiterung ist erforderlich.";
      return false;
   }
   
   if (!extension_loaded('json')) {
      echo "Die json-Erweiterung ist erforderlich.";
      return false;
   }

   return true;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_knowledgebot_check_config($verbose = false) {
   if (true) { // Your configuration check
      return true;
   }

   if ($verbose) {
      echo __('Installed / not configured', 'knowledgebot');
   }
   return false;
}

/**
 * Hook for ticket update to process knowledge base indexing
 *
 * @param Ticket $ticket The ticket object
 * @return void
 */
function plugin_knowledgebot_ticket_update(Ticket $ticket) {
   // Automatische Indizierung bei geschlossenen Tickets
   if ($ticket->fields['status'] == CommonITILObject::CLOSED) {
      // Lazy load the service
      $knowledgebase = new PluginKnowledgebotKnowledgeBase();
      $knowledgebase->indexTicket($ticket->getID());
   }
}