# Podcastmanager Standalone-Architektur

## Überblick

Die gesamte Logik für die Ausgabe von Podcast-Episoden wurde aus den Modulen und Templates ins Addon überführt. Dies ermöglicht:

- **Standalone-Nutzung**: Das Addon kann unabhängig von spezifischen Modulen/Templates verwendet werden
- **Wiederverwendbarkeit**: Die Logik kann in verschiedenen Kontexten (Module, Templates, APIs, CLIs) genutzt werden
- **Wartbarkeit**: Zentrale Logik-Verwaltung statt verstreut über multiple Dateien
- **Testing**: Leichter zu testen durch klare, isolierte Klassen
- **Erweiterbarkeit**: Neue Output-Formate können einfach hinzugefügt werden

## Architektur

### Addon-Klassen

#### `PodcastOutput` - Frontend Output
**Location**: `redaxo/src/addons/podcastmanager/lib/PodcastOutput.php`

Behandelt die Rendering-Logik für Frontend-Ausgaben:

```php
// Nutzung im Modul
$output = new PodcastOutput([
    'mode'        => 'overview',      // 'start', 'overview', 'detail'
    'show_teaser' => true,             // Show description
    'limit'       => 10,               // Episode limit
    'show_audio'  => true,             // Show player
    'width'       => 12,               // Bootstrap column width
]);

echo $output->render();              // Gibt HTML aus
echo $output->renderWithAssets();    // Mit CSS/JS
```

**Verfügbare Modi:**

- **`overview`** - Listet alle Episoden als Teasers auf
- **`start`** - Zeigt erste Episode mit Player + Teaser-Liste
- **`detail`** - Einzelne Episode mit vollem Player und Info

**Methoden:**

- `render()` - Gibt HTML des aktuellen Modus aus
- `renderWithAssets()` - Inkludiert CSS/JS Assets
- `renderItem($item)` - Einzelne Episode als Teaser
- `renderHeader($item)` - Episode mit Player
- `renderPlayer($item)` - Audio Player
- `renderDetail()` - Detail-Ansicht
- `renderOverview()` - Übersichts-Ansicht
- `renderStart()` - Start-Ansicht

#### `PodcastRSS` - RSS Feed Generation
**Location**: `redaxo/src/addons/podcastmanager/lib/PodcastRSS.php`

Generiert RSS-Feeds in verschiedenen Formaten:

```php
// RSS Generator
$rss = new PodcastRSS();

// iTunes Podcast Feed (Standard)
echo $rss->generate('itunes');

// Klassischer RSS 2.0
echo $rss->generate('rss2');
```

**Unterstützte Formate:**

- **`itunes`** - iTunes-kompatibles Podcast-Format
- **`rss2`** - Klassisches RSS 2.0 Format

**Methoden:**

- `generate($format)` - Generiert vollständigen RSS-Feed
- `generateITunesFeed()` - iTunes Podcast Feed
- `generateClassicRssFeed()` - RSS 2.0 Feed

### Module und Templates (vereinfacht)

#### Modul Output
**Location**: `redaxo/data/addons/developer/modules/Podcast Manager Output/output.php`

```php
<?php
if (rex::isBackend()) {
    echo "PODCAST output - nur auf der Webseite sichtbar.";
} else {
    // Konfiguration zusammenstellen
    $config = [
        'mode'        => 'REX_VALUE[1]',
        'show_teaser' => (bool)"REX_VALUE[2]",
        'limit'       => ((int)"REX_VALUE[3]" != 0) ? "REX_VALUE[3]" : '',
        'show_audio'  => (bool)"REX_VALUE[4]",
        'width'       => ((int)"REX_VALUE[6]" != 0) ? "REX_VALUE[6]" : 12,
    ];
    
    // Output über Addon-Klasse rendern
    $output = new PodcastOutput($config);
    echo $output->renderWithAssets();
}
```

**REX_VALUE Definitionen:**
- `REX_VALUE[1]` - Modus ('start', 'overview', 'detail')
- `REX_VALUE[2]` - Teaser mit/ohne Description (0/1)
- `REX_VALUE[3]` - Limit für Episoden
- `REX_VALUE[4]` - Player an/aus (0/1)
- `REX_VALUE[5]` - reserviert
- `REX_VALUE[6]` - Column width Bootstrap (12)

#### RSS Template
**Location**: `redaxo/data/addons/developer/templates/PODCAST MANAGER RSS/template.php`

```php
<?php
if("REX_ARTICLE_ID" != "234") 
    rex_response::sendContentType('application/xml; charset=utf-8');

// Determine feed format (classic RSS or iTunes)
$feed_format = rex_get("feed_version") !== "classic" ? 'itunes' : 'rss2';

// Create RSS generator and output
$rss = new PodcastRSS();
echo $rss->generate($feed_format);

exit;
```

## HTML Output bleibt identisch

Das HTML des Output ist **100% identisch** geblieben. Nur die **Verwaltung der Logik** hat sich geändert.

### Alte Architektur
```
Modul/Template 
  ├─ SQL Queries
  ├─ Helper Functions
  ├─ HTML Rendering
  └─ Output
```

### Neue Architektur
```
Modul/Template (einfach, nur Konfiguration)
  ↓
Addon-Klasse (PodcastOutput/PodcastRSS)
  ├─ SQL Queries
  ├─ Datenverarbeitung
  ├─ HTML Rendering
  └─ Output
```

## Nutzungsbeispiele

### Beispiel 1: Im Modul
```php
$output = new PodcastOutput([
    'mode' => 'overview',
    'limit' => 20,
    'show_teaser' => true,
]);
echo $output->renderWithAssets();
```

### Beispiel 2: Im Template
```php
$rss = new PodcastRSS();
echo $rss->generate('itunes');
```

### Beispiel 3: In eigenen Skripten
```php
// Custom snippet
$output = new PodcastOutput([
    'mode' => 'start',
    'width' => 6,
]);
$html = $output->render();
// Weitere Verarbeitung...
```

### Beispiel 4: Als API
```php
// Custom API Endpoint
class PodcastAPI {
    public function getEpisodes() {
        $output = new PodcastOutput(['mode' => 'overview']);
        return $output->render();
    }
}
```

## Konfiguration

### PodcastOutput Optionen
```php
[
    'mode'        => 'overview',    // Render-Modus
    'show_teaser' => true,          // Teaser anzeigen
    'limit'       => '',            // Anzahl Episodes
    'show_audio'  => true,          // Player anzeigen
    'width'       => 12,            // Bootstrap width (1-12)
    'detail_id'   => 93,            // Detail Artikel ID
    'order'       => 'DESC',        // Sortierung
]
```

### PodcastRSS Konfiguration
Verwendet automatisch alle `rex_config::get('podcastmanager', ...)` Einstellungen:

- `feed_title` - Podcast Titel
- `feed_link` - Website URL
- `feed_description` - Beschreibung
- `feed_author` - Autor Name
- `feed_email` - Kontakt Email
- `feed_image` - Cover Image
- `feed_explicit` - Explicit Content (yes/no/clean)
- `feed_category` - iTunes Kategorie
- `feed_subcategory` - iTunes Unterkategorie

## Vorteile dieser Architektur

✅ **Modularität** - Logik ist nicht an Module/Templates gebunden
✅ **Wiederverwendbarkeit** - Kann überall im Projekt genutzt werden  
✅ **Testing** - Klassen können isoliert getestet werden
✅ **Wartbarkeit** - Zentrale Logik-Verwaltung
✅ **Erweiterbarkeit** - Neue Modi/Formate leicht hinzufügbar
✅ **Performance** - Keine redundante Logik
✅ **Konsistenz** - Ein Output-Standard für alle Kontexte

## Migration von altem Code

### Alte Modul Struktur
```php
// Viel Inline-Code, Helper Functions, direkte SQL Queries
$pod_items = rex_sql::factory()->getArray('SELECT...');
foreach ($pod_items as $item) {
    $item = podcastmanager::prepare($item, $track_url);
    // HTML Rendering hier...
}
```

### Neue Modul Struktur
```php
// Nur noch Konfiguration und Rendering
$output = new PodcastOutput(['mode' => 'overview']);
echo $output->render();
```

## Erweiterungen

### Neuen Output-Modus hinzufügen

```php
// In PodcastOutput Klasse
private function renderCustomMode() {
    // Deine Custom Logik
    return $html;
}

// In render() Method
case 'custom':
    return $this->renderCustomMode();
```

### Neues RSS-Format hinzufügen

```php
// In PodcastRSS Klasse
public function generate($format = 'itunes') {
    if ($format === 'custom') {
        return $this->generateCustomFeed();
    }
    // ...
}

private function generateCustomFeed() {
    // Custom Feed Generation
}
```

## Debugging

```php
// Konfiguration prüfen
$output = new PodcastOutput(['mode' => 'overview']);
echo $output->render();

// Mit Debug-Infos
$config = [
    'mode' => 'overview',
    'limit' => 5,
    'show_teaser' => true,
];
$output = new PodcastOutput($config);
// HTML wird mit aktuellen Einstellungen gerendert
```

## Performance

- **Lazy Loading** - Assets nur laden wenn nötig
- **Single Queries** - Optimierte Datenbankabfragen
- **HTML Caching** - Output kann gecacht werden
- **Keine redundanten Operationen** - Zentrale Logik

## Zusammenfassung

Die neue Architektur ermöglicht es dem podcastmanager Addon, **komplett unabhängig** zu funktionieren. Module und Templates dienen jetzt nur noch als **dünne Wrapper** um die zentrale Addon-Logik. Dies macht das Addon:

- 🎯 **Standalone-Nutzbar**
- 🔧 **Leicht wartbar**
- 📦 **Wiederverwendbar**
- 🚀 **Leicht erweiterbar**
