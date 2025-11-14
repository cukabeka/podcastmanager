# RSS Feed Format Examples

## Description Format Options

Das PodcastManager Addon unterstützt jetzt drei verschiedene Formate für die Episode-Beschreibungen im RSS-Feed:

### 1. Text Format (Standard)
Konvertiert HTML zu lesbarem Plain-Text. Links werden als "Text (URL)" dargestellt.

```php
$rss = new PodcastRSS('text');
echo $rss->generate('rss2');
```

**Beispiel Ausgabe:**
```
Willkommen zur neuen Episode!

In dieser Episode sprechen wir über:
- REDAXO CMS
- Podcast-Optimierung
- SEO Best Practices

Mehr Infos auf unserer Website (https://example.com)
```

### 2. Markdown Format (NEU!)
Konvertiert HTML zu Markdown - sowohl menschen- als auch maschinenlesbar!

```php
$rss = new PodcastRSS('markdown');
echo $rss->generate('rss2');
```

**Beispiel Ausgabe:**
```markdown
# Willkommen zur neuen Episode!

In dieser Episode sprechen wir über:

* REDAXO CMS
* Podcast-Optimierung  
* SEO Best Practices

[Mehr Infos auf unserer Website](https://example.com)
```

**Vorteile von Markdown:**
- ✅ Menschenlesbar im Rohformat
- ✅ Maschinenlesbar für Parser/Bots
- ✅ Kann einfach zu HTML konvertiert werden
- ✅ Behält Formatierung bei (Listen, Links, Überschriften)
- ✅ Perfekt für moderne Podcast-Apps
- ✅ GitHub/Gitlab/Markdown-Editoren kompatibel

### 3. HTML Format
Behält sauberes HTML (nur sichere Tags).

```php
$rss = new PodcastRSS('html');
echo $rss->generate('rss2');
```

**Beispiel Ausgabe:**
```html
<h1>Willkommen zur neuen Episode!</h1>

<p>In dieser Episode sprechen wir über:</p>
<ul>
  <li>REDAXO CMS</li>
  <li>Podcast-Optimierung</li>
  <li>SEO Best Practices</li>
</ul>

<p><a href="https://example.com">Mehr Infos auf unserer Website</a></p>
```

## Template Beispiel

```php
<?php
// RSS Feed Template mit Markdown-Support

// Content-Type setzen
rex_response::sendContentType('application/xml; charset=utf-8');

// Format auswählen
$format = rex_get('format', 'string', 'markdown');

// Validiere Format
$allowedFormats = ['text', 'markdown', 'html'];
if (!in_array($format, $allowedFormats)) {
    $format = 'text';
}

// RSS generieren
$rss = new PodcastRSS($format);
echo $rss->generate('rss2');

exit;
```

**Aufruf:**
- `https://example.com/podcast-feed` → Standard (text)
- `https://example.com/podcast-feed?format=markdown` → Markdown
- `https://example.com/podcast-feed?format=html` → HTML

## Module Beispiel mit Format-Auswahl

```php
<?php
if (rex::isBackend()) {
    // Backend: Format-Auswahl anzeigen
    echo '<div class="form-group">';
    echo '<label>RSS Description Format:</label>';
    echo '<select class="form-control" name="REX_INPUT_VALUE[5]">';
    echo '<option value="text"' . ('REX_VALUE[5]' == 'text' ? ' selected' : '') . '>Text (Standard)</option>';
    echo '<option value="markdown"' . ('REX_VALUE[5]' == 'markdown' ? ' selected' : '') . '>Markdown</option>';
    echo '<option value="html"' . ('REX_VALUE[5]' == 'html' ? ' selected' : '') . '>HTML</option>';
    echo '</select>';
    echo '</div>';
} else {
    // Frontend: RSS generieren
    $format = 'REX_VALUE[5]' ?: 'text';
    
    rex_response::sendContentType('application/xml; charset=utf-8');
    
    $rss = new PodcastRSS($format);
    echo $rss->generate('rss2');
    
    exit;
}
```

## XOPF + Markdown Beispiel

Mit xoutputfilter Addon werden Platzhalter ZUERST ersetzt, DANN in Markdown konvertiert:

**Input (in REDAXO Backend):**
```html
<p>Hier ist ein tolles Buch:</p>
[[AMAZON_LINK produkt=B08XYZ|Buchtitel=Das beste Buch ever]]

<h2>Weitere Empfehlungen</h2>
<ul>
  <li>Empfehlung 1</li>
  <li>Empfehlung 2</li>
</ul>
```

**Output (Markdown im RSS Feed):**
```markdown
Hier ist ein tolles Buch:

[Das beste Buch ever](https://amazon.de/dp/B08XYZ?tag=dein-affiliate-id)

## Weitere Empfehlungen

* Empfehlung 1
* Empfehlung 2
```

## Direkte Nutzung der Helper-Funktion

```php
<?php
// Beispiel Episode
$episode = rex_sql::factory()->getArray(
    'SELECT * FROM ' . rex::getTable('podcastmanager') . ' WHERE id = 1'
)[0];

// Text Format
$text = podcastmanager::urlFeedConvert($episode['description'], 'text');
echo $text;

// Markdown Format  
$markdown = podcastmanager::urlFeedConvert($episode['description'], 'markdown');
echo $markdown;

// HTML Format
$html = podcastmanager::urlFeedConvert($episode['description'], 'html');
echo $html;
```

## Verwendung in anderen Addons

Das Markdown-Format kann auch außerhalb des RSS-Feeds genutzt werden:

```php
<?php
// In einem Blog-Addon
$podcastEpisode = /* ... episode data ... */;
$markdownDescription = podcastmanager::urlFeedConvert(
    $podcastEpisode['description'], 
    'markdown'
);

// Markdown zu HTML konvertieren (falls benötigt)
// Nutze z.B. Parsedown oder Markdown Parser
$html = (new Parsedown())->text($markdownDescription);
echo $html;
```

## API Endpunkt mit Format-Auswahl

```php
<?php
// api.php?episode=123&format=markdown

$episodeId = rex_get('episode', 'int', 0);
$format = rex_get('format', 'string', 'markdown');

if ($episodeId > 0) {
    $episode = rex_sql::factory()->getArray(
        'SELECT * FROM ' . rex::getTable('podcastmanager') . ' WHERE id = ' . $episodeId
    );
    
    if (!empty($episode)) {
        $item = podcastmanager::prepare($episode[0]);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'title' => $item['title'],
            'number' => $item['number'],
            'description' => podcastmanager::urlFeedConvert($item['description'], $format),
            'format' => $format
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
```

## Warum Markdown?

### Für Podcast-Hörer:
- Bessere Lesbarkeit in Apps
- Funktioniert mit allen Markdown-Readern
- Copy-Paste freundlich

### Für Entwickler:
- Einfach zu parsen
- Kann zu HTML/PDF/etc. konvertiert werden
- Versionierbar (Git-friendly)

### Für SEO:
- Strukturierte Inhalte
- Bessere Indexierung
- Klare Hierarchie

## Migration von bestehenden Feeds

Bestehende Feeds bleiben unverändert (Text-Format ist Standard).

Um auf Markdown umzustellen:

```php
// Alt:
$rss = new PodcastRSS();

// Neu:
$rss = new PodcastRSS('markdown');
```

**Keine Breaking Changes!** Backward-kompatibel.

## Podcast-App Kompatibilität

| App | Text | Markdown | HTML |
|-----|------|----------|------|
| Apple Podcasts | ✅ | ✅ | ⚠️ |
| Spotify | ✅ | ✅ | ⚠️ |
| Overcast | ✅ | ✅ | ✅ |
| Pocket Casts | ✅ | ✅ | ⚠️ |
| Castro | ✅ | ✅ | ✅ |

✅ = Vollständig unterstützt  
⚠️ = Teilweise unterstützt (HTML wird oft gefiltert)

**Empfehlung:** Nutze Markdown für beste Kompatibilität und Lesbarkeit!
