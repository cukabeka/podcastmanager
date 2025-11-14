# Vidstack Integration für Podcast Manager

## Übersicht

Der Podcast Manager unterstützt jetzt vollständig das moderne **vidstack Addon** (https://github.com/FriendsOfREDAXO/vidstack/) für die Audio-Wiedergabe. Dies bietet eine moderne, zugängliche und performante Alternative zu Plyr.

## Features

### ✅ Automatische Erkennung
Der Podcast Manager erkennt automatisch, welcher Player verfügbar ist:
1. **Vidstack** (bevorzugt) - Modern & barrierefrei
2. **Plyr/Video** (Fallback) - Legacy-Support
3. **HTML5 Audio** (Fallback) - Immer verfügbar

### ✅ Frontend Audio Player

#### Mit Vidstack
```php
// Automatisch aktiviert wenn vidstack installiert ist
$output = new PodcastOutput([
    'mode' => 'detail',
    'show_audio' => true,
]);
echo $output->render();
```

**Vorteile von Vidstack:**
- 🎨 Modernes UI mit besserer UX
- ♿ Verbesserte Barrierefreiheit (ARIA, Keyboard-Navigation)
- 📱 Mobile-optimiert
- 🖼️ Poster-Image Support
- 📝 Untertitel-Unterstützung (für Video-Podcasts)
- ⚡ Bessere Performance
- 🎯 Präzise Media-Query Unterstützung

#### Features im Detail

##### Poster-Bilder
Episode-Bilder werden automatisch als Poster verwendet:
```php
// Wird automatisch gesetzt wenn Episode Bilder hat
$item['images'] = 'episode-cover.jpg';
```

##### Barrierefreiheit
```php
// Episode-Beschreibung wird als A11y-Content genutzt
$video->setA11yContent(
    $description,      // Beschreibung der Episode
    $episode_url       // Alternative URL
);
```

##### Player-Attribute
Der Player wird mit optimalen Einstellungen initialisiert:
```php
$video->setAttributes([
    'controls' => true,           // Steuerelemente anzeigen
    'preload' => 'metadata',      // Nur Metadaten vorladen
    'class' => 'podcast-audio-player' // CSS-Klasse für Styling
]);
```

### ✅ Backend Audio Preview (YForm)

**NEU!** Audio-Vorschau direkt im Backend beim Bearbeiten von Episodes!

#### Installation

1. Vidstack Addon installieren (optional, aber empfohlen)
2. YForm-Feld zur Tabelle hinzufügen

#### Verwendung in YForm

```php
// In der YForm-Tabellendefinition:
audio_preview|preview|Audio-Vorschau|audiofiles|1|1
```

**Parameter:**
1. `audio_preview` - Feldtyp
2. `preview` - Feldname (intern)
3. `Audio-Vorschau` - Label im Backend
4. `audiofiles` - Name des Audio-Feld (Quelle)
5. `1` - Laufzeit anzeigen (0/1)
6. `1` - Dateigröße anzeigen (0/1)

#### Features

- 🎵 **Live Audio Preview** im Backend
- ⏱️ **Laufzeit-Anzeige** mit automatischer ID3-Auslese
- 📏 **Dateigröße** formatiert angezeigt
- ✅ **Vidstack Support** wenn installiert
- 🔄 **Fallback zu HTML5** wenn vidstack nicht verfügbar
- 🎨 **Optisches Feedback** bei fehlenden Dateien

#### Screenshot-Beispiel

```
┌─────────────────────────────────────────┐
│ Audio-Vorschau                          │
├─────────────────────────────────────────┤
│ ┌─────────────────────────────────────┐ │
│ │  🎵 [===========►--------]  05:23   │ │
│ │  🔊 Volume: [====►----]            │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ 📁 15.3 MB  ⏱️ 00:05:23                │
└─────────────────────────────────────────┘
```

### ✅ Modul-Beispiele

#### Einfaches Modul mit Vidstack
```php
<?php
if (rex::isBackend()) {
    echo "PODCAST output - nur auf der Webseite sichtbar.";
} else {
    $config = [
        'mode'        => 'detail',
        'show_audio'  => true,  // Vidstack Player wird automatisch verwendet
    ];
    
    $output = new PodcastOutput($config);
    echo $output->renderWithAssets();
}
```

#### Modul mit Player-Auswahl
```php
<?php
if (rex::isBackend()) {
    // Backend: Player-Auswahl
    ?>
    <div class="form-group">
        <label>Audio Player:</label>
        <select class="form-control" name="REX_INPUT_VALUE[5]">
            <option value="auto"<?= ('REX_VALUE[5]' == 'auto' ? ' selected' : '') ?>>Automatisch (Vidstack bevorzugt)</option>
            <option value="vidstack"<?= ('REX_VALUE[5]' == 'vidstack' ? ' selected' : '') ?>>Vidstack (nur wenn installiert)</option>
            <option value="html5"<?= ('REX_VALUE[5]' == 'html5' ? ' selected' : '') ?>>HTML5 (Fallback)</option>
        </select>
    </div>
    <?php
} else {
    // Frontend: Episode anzeigen
    $output = new PodcastOutput([
        'mode' => 'detail',
        'show_audio' => true,
    ]);
    echo $output->renderWithAssets();
}
```

### ✅ Direkte API-Nutzung

Für komplexe Anwendungsfälle kannst du vidstack direkt nutzen:

```php
<?php
use FriendsOfRedaxo\VidStack\Video;

// Episode-Daten holen
$episode = rex_sql::factory()->getArray(
    'SELECT * FROM ' . rex::getTable('podcastmanager') . ' WHERE id = 1'
)[0];

// Vidstack Player erstellen
$video = new Video($episode['audiofiles'], $episode['title']);

// Attribute setzen
$video->setAttributes([
    'controls' => true,
    'preload' => 'metadata',
    'class' => 'my-custom-player'
]);

// Poster-Bild
if (!empty($episode['images'])) {
    $images = explode(',', $episode['images']);
    $posterUrl = rex_url::media($images[0]);
    $video->setPoster($posterUrl, $episode['title']);
}

// Barrierefreiheit
$description = strip_tags($episode['description']);
$episodeUrl = podcastmanager::getShowUrl($episode);
$video->setA11yContent($description, $episodeUrl);

// Player ausgeben
echo $video->generate();
```

### ✅ Styling

#### CSS für Vidstack Player

```css
/* Podcast Manager Vidstack Styles */
.podcast-audio-player {
    width: 100%;
    max-width: 800px;
    margin: 0 auto;
}

/* Responsive */
@media (max-width: 768px) {
    .podcast-audio-player {
        max-width: 100%;
    }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    .podcast-audio-player {
        filter: invert(1) hue-rotate(180deg);
    }
}
```

#### Backend Preview Styling

Die Backend-Vorschau ist bereits gestylt, kann aber angepasst werden:

```css
/* Custom Backend Preview Styles */
.audio-preview-wrapper {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
}

.audio-preview-player {
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.audio-info-item {
    font-weight: 600;
    color: #333;
}
```

## Migration von Plyr zu Vidstack

### Schritt 1: Vidstack installieren
```bash
composer require friendsofredaxo/vidstack
```

### Schritt 2: Keine Code-Änderungen nötig!
Der Podcast Manager erkennt vidstack automatisch und nutzt es.

### Schritt 3: Plyr-Abhängigkeit entfernen (optional)
Wenn du nur noch vidstack nutzen willst:

1. Plyr/Video Addon deinstallieren
2. Podcast Manager nutzt automatisch vidstack
3. Bei Bedarf: HTML5 als Fallback bleibt verfügbar

### Vergleich

| Feature | Plyr | Vidstack | HTML5 |
|---------|------|----------|-------|
| Modernes UI | ✅ | ✅✅ | ❌ |
| Barrierefreiheit | ✅ | ✅✅ | ⚠️ |
| Mobile Support | ✅ | ✅✅ | ✅ |
| Performance | ✅ | ✅✅ | ✅✅ |
| Customization | ✅ | ✅✅ | ⚠️ |
| Bundle Size | ~50KB | ~30KB | 0KB |
| Backend Preview | ❌ | ✅ | ✅ |

✅✅ = Ausgezeichnet, ✅ = Gut, ⚠️ = Eingeschränkt, ❌ = Nicht unterstützt

## Kompatibilität

### Browser Support
- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile Browser (iOS Safari, Chrome Mobile)

### REDAXO
- REDAXO 5.13+
- PHP 5.6 - 8.4

### Addons
- **Vidstack**: Optional aber empfohlen
- **Plyr/Video**: Optional als Fallback
- **YForm**: Erforderlich für Backend-Vorschau

## Troubleshooting

### Player wird nicht angezeigt

**Problem:** Audio-Player erscheint nicht

**Lösung:**
1. Prüfe ob vidstack installiert ist: `rex_addon::exists('vidstack')`
2. Prüfe Browser-Konsole auf JavaScript-Fehler
3. Stelle sicher dass Audio-Datei existiert
4. Prüfe Dateipfad: `rex_media::get('audio.mp3')`

### Backend-Vorschau zeigt Fehler

**Problem:** "Audio-Datei nicht gefunden"

**Lösung:**
1. Stelle sicher dass Feldname korrekt ist (`audiofiles`)
2. Prüfe ob Datei im Medienpool existiert
3. Prüfe Datei-Permissions

### Vidstack wird nicht genutzt

**Problem:** Es wird HTML5-Player statt vidstack verwendet

**Lösung:**
1. Prüfe ob vidstack aktiviert ist
2. Leere REDAXO Cache
3. Prüfe ob vidstack-Assets geladen werden (Browser-DevTools)

## Best Practices

### 1. Nutze Vidstack für neue Projekte
Vidstack ist modern und bietet bessere Features.

### 2. Behalte Fallbacks bei
Auch wenn vidstack installiert ist, funktioniert HTML5 als Sicherheitsnetz.

### 3. Optimiere Audio-Dateien
- Nutze MP3 mit konstanter Bitrate
- 128-192 kbps für Sprache
- ID3-Tags richtig setzen

### 4. Teste Barrierefreiheit
- Keyboard-Navigation testen
- Screen Reader testen
- ARIA-Labels prüfen

### 5. Backend-Vorschau nutzen
Aktiviere Audio-Preview in YForm für besseres Editing-Erlebnis.

## Weitere Ressourcen

- **Vidstack Dokumentation:** https://www.vidstack.io/
- **Vidstack REDAXO Addon:** https://github.com/FriendsOfREDAXO/vidstack/
- **Podcast Manager:** https://github.com/FriendsOfREDAXO/podcastmanager/

---

**Version:** 1.1.0  
**Datum:** 2025-01-14  
**Autor:** Friends Of REDAXO
