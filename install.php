<?php

/** @var rex_addon $this */

// Datenverzeichnis kopieren
if (!is_dir($this->getDataPath())) {
    rex_dir::copy($this->getPath('data'), $this->getDataPath());
}

// Standard-Konfigurationswerte setzen
if (!$this->hasConfig()) {
    // Feed Grundeinstellungen
    $this->setConfig('feed_title', 'Mein Podcast');
    $this->setConfig('feed_subtitle', 'Beschreibung meines Podcasts');
    $this->setConfig('feed_description', 'Ausführliche Beschreibung des Podcast-Inhalts');
    $this->setConfig('feed_link', rex::getServer());
    $this->setConfig('feed_author', 'Podcast Autor');
    $this->setConfig('feed_email', 'podcast@example.com');
    $this->setConfig('feed_owner', 'Podcast Besitzer');
    $this->setConfig('feed_image', '');
    $this->setConfig('feed_explicit', 'no');
    $this->setConfig('feed_lang', 'de-DE');
    $this->setConfig('feed_license', 'All rights reserved');
    $this->setConfig('feed_category', 'Technology');
    $this->setConfig('feed_subcategory', 'Podcasting');
    $this->setConfig('feed_keywords', 'podcast, audio, episodes');
    $this->setConfig('feed_item_additions', '');
    
    // Artikel IDs
    $this->setConfig('detail_id', 0);
    $this->setConfig('rss_feed_id', 0);
    
    // Statistik
    $this->setConfig('stats_rss_active', 'inactive');
    $this->setConfig('stats_prefix', '/podcast-download');
    $this->setConfig('setting_limit', 50);
    
    // Tracking Optionen
    $this->setConfig('tracking_enabled', true);
    $this->setConfig('tracking_ip_anonymize', true);
    $this->setConfig('tracking_user_agent', true);
    $this->setConfig('tracking_referrer', true);
    $this->setConfig('tracking_bot_filter', true);
    
    echo rex_view::success('Podcast Manager erfolgreich installiert! Standard-Konfiguration wurde angelegt.');
}

// Prüfe erforderliche Addons
$requiredAddons = [
    'yform' => 'YForm wird für die Datenverwaltung benötigt',
    'yrewrite' => 'YRewrite wird für SEO-freundliche URLs benötigt',
];

$missingAddons = [];
foreach ($requiredAddons as $addon => $description) {
    if (!rex_addon::exists($addon) || !rex_addon::get($addon)->isAvailable()) {
        $missingAddons[] = $addon . ' - ' . $description;
    }
}

if (!empty($missingAddons)) {
    echo rex_view::warning('<strong>Hinweis:</strong> Folgende Addons werden empfohlen:<ul><li>' . implode('</li><li>', $missingAddons) . '</li></ul>');
}

// Prüfe optionale Addons
$optionalAddons = [
    'vidstack' => 'Moderner Audio-Player mit besserer UX',
    'xoutputfilter' => 'Für Affiliate-Links und spezielle Replacements',
    'redactor2' => 'Rich-Text-Editor für Episode-Beschreibungen',
];

$availableOptional = [];
foreach ($optionalAddons as $addon => $description) {
    if (rex_addon::exists($addon) && rex_addon::get($addon)->isAvailable()) {
        $availableOptional[] = $addon . ' - ' . $description;
    }
}

if (!empty($availableOptional)) {
    echo rex_view::success('<strong>Gefundene optionale Addons:</strong><ul><li>' . implode('</li><li>', $availableOptional) . '</li></ul>');
}

// Media Manager Typen prüfen/erstellen
if (rex_addon::exists('media_manager') && rex_addon::get('media_manager')->isAvailable()) {
    $types = [
        'podcastmanager_main_image' => [
            'description' => 'Podcast Haupt-Bild (1400x1400px)',
            'effects' => [
                ['resize', ['width' => 1400, 'height' => 1400, 'style' => 'fit']],
            ],
        ],
        'pod_detail_header' => [
            'description' => 'Podcast Detail Header (1920x500px)',
            'effects' => [
                ['resize', ['width' => 1920, 'height' => 500, 'style' => 'fit']],
            ],
        ],
    ];
    
    foreach ($types as $typeName => $config) {
        $type = rex_sql::factory();
        $type->setQuery('SELECT id FROM ' . rex::getTable('media_manager_type') . ' WHERE name = ?', [$typeName]);
        
        if ($type->getRows() == 0) {
            // Type erstellen
            $type->setTable(rex::getTable('media_manager_type'));
            $type->setValue('name', $typeName);
            $type->setValue('description', $config['description']);
            $type->insert();
            
            echo rex_view::info('Media Manager Typ "' . $typeName . '" wurde erstellt.');
        }
    }
}

// Prüfe getID3 Library
if (class_exists('getID3')) {
    echo rex_view::success('getID3 Library gefunden - Automatische Runtime-Erkennung aktiviert!');
} else {
    echo rex_view::info('getID3 Library nicht gefunden - Runtime muss manuell eingegeben werden.');
}

// Prüfe Markdownify
if (class_exists('Markdownify\Converter')) {
    echo rex_view::success('Markdownify Library gefunden - Markdown RSS-Format verfügbar!');
}

// Cache leeren
rex_delete_cache();

echo rex_view::success('<strong>Installation abgeschlossen!</strong><br><br>Nächste Schritte:<ol><li>Gehe zu "Einstellungen" und konfiguriere deinen Podcast</li><li>Erstelle Kategorien unter "Kategorien"</li><li>Füge deine erste Episode unter "Podcast Manager" hinzu</li><li>Erstelle ein Modul mit PodcastOutput für die Ausgabe</li><li>Erstelle ein Template mit PodcastRSS für den RSS-Feed</li></ol><p>📚 Siehe README.md für ausführliche Dokumentation!</p>');

