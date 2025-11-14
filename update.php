<?php

/** @var rex_addon $this */

// Diese Datei wird beim Update eines Addons über den Installer aufgerufen

// Hier können zum Beispiel DB-Tabellen angepasst werden

// $this->getVersion() liefert die noch aktuell installierte Version

if (rex_string::versionCompare($this->getVersion(), '1.1', '<')) {

}

if (rex_string::versionCompare($this->getVersion(), '1.2', '<')) {
    // Änderungen für Nutzer die von Versionen kleiner 1.2 kommen
}

// DB-Anpassungen für Version 1.1.0
if (rex_string::versionCompare($this->getVersion(), '1.1.0', '<')) {
    // Tabelle rex_podcastmanager anpassen
    rex_sql_table::get(rex::getTable('podcastmanager'))
        ->ensureColumn(new rex_sql_column('description', 'text', true))
        ->ensureColumn(new rex_sql_column('publishdate', 'varchar(255)', false, ''))
        ->ensureIndex(new rex_sql_index('status_publishdate', ['status', 'publishdate']))
        ->alter();
    
    // Upgrade auf utf8mb4 für bessere Unicode-Unterstützung
    try {
        $sql = rex_sql::factory();
        $sql->setQuery('ALTER TABLE ' . rex::getTable('podcastmanager') . ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $sql->setQuery('ALTER TABLE ' . rex::getTable('podcastmanager_categories') . ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    } catch (Exception $e) {
        // Fehler bei utf8mb4 Konvertierung ignorieren (für ältere MySQL Versionen)
    }
    
    // Migriere richtext zu description wenn description leer ist (Rückwärtskompatibilität)
    $sql = rex_sql::factory();
    $sql->setQuery('UPDATE ' . rex::getTable('podcastmanager') . ' SET description = richtext WHERE (description IS NULL OR description = "") AND richtext IS NOT NULL AND richtext != ""');
}

// Update kann abgebrochen werden:
// throw new rex_functional_exception('Fehlermeldung');
