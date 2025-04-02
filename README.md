# KnowledgeBot Plugin für GLPI

Ein GLPI-Plugin, das das Ticketsystem als Knowledge Base nutzt und einen KI-gestützten Chatbot integriert.

## Funktionen

- Automatisches Indexieren von geschlossenen Tickets für die Knowledge Base
- KI-gestützter Chatbot mit Ollama-Integration
- Direkte Suche in der Ticket-Datenbank
- Bequemer Zugriff auf relevante Lösungen früherer Tickets
- Intuitives Dashboard mit Statistiken zur Nutzung

## Anforderungen

- GLPI 10.0.0 oder höher
- PHP 7.4.0 oder höher
- MySQL/MariaDB
- Ollama (optional, für KI-Funktionen)

## Installation

### 1. Plugin-Dateien installieren

1. Laden Sie das Plugin herunter und entpacken Sie es im `plugins`-Verzeichnis Ihrer GLPI-Installation.
2. Benennen Sie das entpackte Verzeichnis in `knowledgebot` um, falls nötig.
3. Öffnen Sie GLPI und gehen Sie zu "Setup > Plugins".
4. Suchen Sie "KnowledgeBot" in der Pluginliste und klicken Sie auf "Installieren".
5. Aktivieren Sie das Plugin nach der Installation.

### 2. Ollama für KI-Funktionen einrichten (optional)

1. Installieren Sie Ollama (siehe [ollama.ai](https://ollama.ai) für Installationsanleitungen).
2. Laden Sie ein unterstütztes Modell herunter, z.B.: `ollama pull llama2`
3. Starten Sie den Ollama-Server: `ollama serve`
4. Konfigurieren Sie die Ollama-Integration im KnowledgeBot-Plugin über "Setup > Plugins > KnowledgeBot > Konfiguration"

## Konfiguration

### Plugin-Konfiguration

Gehen Sie zu "Setup > Plugins > KnowledgeBot > Konfiguration" um folgende Einstellungen vorzunehmen:

- **Automatische Indizierung**: Aktiviert/deaktiviert die automatische Indizierung von geschlossenen Tickets

### Ollama-Integration konfigurieren

- **Ollama aktivieren**: Aktiviert/deaktiviert die KI-Funktionen
- **Ollama URL**: Die URL des Ollama-Servers (Standard: http://localhost:11434)
- **Ollama Modell**: Das zu verwendende LLM-Modell (z.B. llama2, mistral, gemma)
- **System Prompt**: Anweisungen für das KI-Modell, um die Antworten anzupassen

## Nutzung

### Knowledge Base

- Tickets werden automatisch zur Knowledge Base hinzugefügt, wenn sie geschlossen werden (falls aktiviert).
- Sie können Tickets auch manuell zur Knowledge Base hinzufügen, indem Sie den Tab "KnowledgeBot" in der Ticket-Ansicht verwenden.

### Chatbot

- Der Chatbot erscheint als Symbol in der unteren rechten Ecke der GLPI-Benutzeroberfläche.
- Benutzer können Fragen an den Chatbot stellen, der relevante Informationen aus früheren Tickets findet.
- Wenn Ollama aktiviert ist, generiert der Chatbot kontextbezogene Antworten basierend auf den gefundenen Tickets.

### Dashboard

- Gehen Sie zu "Tools > KnowledgeBot" um das Dashboard anzuzeigen.
- Das Dashboard zeigt Statistiken zur Nutzung des Chatbots und der Knowledge Base.

## Lizenz

Dieses Plugin ist unter der GPL-2.0+ Lizenz verfügbar.
