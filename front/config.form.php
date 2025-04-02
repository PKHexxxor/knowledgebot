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

include('../../../inc/includes.php');

Session::checkRight('config', UPDATE);

// Konfigurationsaktualisierungen verarbeiten
if (isset($_POST['update'])) {
    PluginKnowledgebotConfig::updateConfig($_POST);
    Html::back();
}

// Header anzeigen
Html::header(PluginKnowledgebotConfig::getTypeName(), $_SERVER['PHP_SELF'], "config", "plugins");

// Konfigurationsformular anzeigen
PluginKnowledgebotConfig::showConfigForm();

Html::footer();
