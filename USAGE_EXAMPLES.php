<?php
/**
 * Beispiel: Podcastmanager Standalone Nutzung
 * 
 * Dieses Snippet zeigt, wie man die podcastmanager Addon-Klassen
 * unabhängig von Modulen/Templates verwenden kann.
 * 
 * Diese Beispiele können als Redaxo Console Snippets verwendet werden:
 * php bin/console project:run-snippet "$(cat path/to/this/file.php)"
 */

// ============================================
// Beispiel 1: Frontend Output rendern
// ============================================

// Overview aller Episoden
$output = new PodcastOutput([
    'mode' => 'overview',
    'limit' => 10,
    'show_teaser' => true,
    'show_audio' => true,
]);
echo $output->renderWithAssets();


// ============================================
// Beispiel 2: Start-Seite (Featured + Teaser)
// ============================================

$output = new PodcastOutput([
    'mode' => 'start',
    'show_teaser' => true,
    'show_audio' => true,
]);
echo $output->render();


// ============================================
// Beispiel 3: Einzelne Episode detailliert
// ============================================

$_GET['id'] = 5; // Episode mit ID 5

$output = new PodcastOutput([
    'mode' => 'detail',
    'show_audio' => true,
]);
echo $output->render();


// ============================================
// Beispiel 4: RSS Feed generieren
// ============================================

// iTunes Podcast Feed
$rss = new PodcastRSS();
$feed = $rss->generate('itunes');
// $feed exportieren oder speichern

// oder klassisches RSS 2.0
$feed_rss2 = $rss->generate('rss2');


// ============================================
// Beispiel 5: Nur HTML (ohne Assets)
// ============================================

$output = new PodcastOutput([
    'mode' => 'overview',
    'limit' => 5,
]);
$html = $output->render(); // Nur das HTML


// ============================================
// Beispiel 6: Mit Custom Konfiguration
// ============================================

$config = [
    'mode'        => 'overview',
    'show_teaser' => false,        // Ohne Teaser Text
    'limit'       => 15,            // 15 Episodes
    'show_audio'  => false,         // Kein Player
    'width'       => 6,             // 50% Breite
];

$output = new PodcastOutput($config);
echo $output->render();


// ============================================
// Beispiel 7: Dynamische Konfiguration
// ============================================

// Je nach Seite different Einstellungen
$page = rex_request('page', 'string', 'overview');

$config = [
    'mode' => $page === 'featured' ? 'start' : 'overview',
    'limit' => $page === 'featured' ? 3 : 20,
    'show_teaser' => $page !== 'featured',
    'show_audio' => $page !== 'featured',
];

$output = new PodcastOutput($config);
echo $output->render();


// ============================================
// Beispiel 8: Episoden für API ausgeben
// ============================================

// JSON API für Frontend Framework
$output = new PodcastOutput([
    'mode' => 'overview',
    'limit' => 50,
]);
$html = $output->render();

// Alternativ: Rohe Episoden auslesen
$episodes = rex_sql::factory()->getArray(
    'SELECT * FROM rex_podcastmanager 
     WHERE status = 1 
     ORDER BY STR_TO_DATE(publishdate, "%d.%m.%Y") DESC 
     LIMIT 50'
);

foreach ($episodes as &$item) {
    $item = podcastmanager::prepare($item);
}

echo json_encode($episodes);


// ============================================
// Beispiel 9: Multiple Ausgaben auf einer Seite
// ============================================

// Featured Episodes
$featured = new PodcastOutput([
    'mode' => 'start',
    'show_audio' => true,
]);

// Recent Episodes
$recent = new PodcastOutput([
    'mode' => 'overview',
    'limit' => 5,
    'show_teaser' => false,
    'show_audio' => false,
]);

echo $featured->render();
echo $recent->render();


// ============================================
// Beispiel 10: Mit Custom HTML Wrapper
// ============================================

$output = new PodcastOutput([
    'mode' => 'overview',
    'limit' => 10,
]);

$html = '
<div class="custom-podcast-wrapper">
    <h2>Unsere Podcast Episoden</h2>
    ' . $output->render() . '
</div>
';

echo $html;


// ============================================
// Beispiel 11: Fehlerbehandlung
// ============================================

try {
    $output = new PodcastOutput([
        'mode' => 'overview',
    ]);
    $html = $output->render();
    
    if (empty($html)) {
        echo '<p>Keine Episoden gefunden.</p>';
    } else {
        echo $html;
    }
} catch (Exception $e) {
    echo '<p>Fehler beim Laden der Episoden: ' . htmlspecialchars($e->getMessage()) . '</p>';
}


// ============================================
// Beispiel 12: Caching
// ============================================

$cache_key = 'podcast_episodes_overview_10';

// Aus Cache laden wenn vorhanden
if ($cached = rex_cache::get($cache_key)) {
    echo $cached;
} else {
    // Neu generieren wenn nicht im Cache
    $output = new PodcastOutput([
        'mode' => 'overview',
        'limit' => 10,
    ]);
    $html = $output->renderWithAssets();
    
    // 1 Stunde cachen (3600 Sekunden)
    rex_cache::set($cache_key, $html, 3600);
    
    echo $html;
}


// ============================================
// Beispiel 13: In Fragment verwenden
// ============================================

// In einem Fragment podcast.php
$output = new PodcastOutput([
    'mode' => rex_var::get('mode', 'overview'),
    'limit' => rex_var::get('limit', ''),
    'show_teaser' => (bool)rex_var::get('show_teaser', true),
    'show_audio' => (bool)rex_var::get('show_audio', true),
]);

return $output->render();


// ============================================
// Beispiel 14: Debug Informationen
// ============================================

$output = new PodcastOutput([
    'mode' => 'overview',
]);

// Config auslesen
$config = $output->getConfig();  // Falls diese Method existiert
echo '<pre>Konfiguration: ' . print_r($config, true) . '</pre>';

// HTML ausgeben
echo $output->render();


// ============================================
// Beispiel 15: Extension Integration
// ============================================

// Als Extension Hook
rex_extension::register('POD_OUTPUT_RENDER', function(rex_extension_point $ep) {
    $config = $ep->getParam('config');
    $output = new PodcastOutput($config);
    return $output->render();
});

// Später im Code:
$html = rex_extension::registerPoint(new rex_extension_point(
    'POD_OUTPUT_RENDER',
    '',
    ['config' => ['mode' => 'overview']]
));
