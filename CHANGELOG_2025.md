# Podcast Manager - Verbesserungen 2025

## Neue Features & Verbesserungen

### 1. Veröffentlichungsdatum-Filterung ✅
**Problem:** Episodes wurden angezeigt, auch wenn das Veröffentlichungsdatum in der Zukunft lag.

**Lösung:** 
- Automatische Filterung nach Veröffentlichungsdatum
- Episodes werden nur angezeigt wenn `publishdate` in der Vergangenheit oder heute ist
- Gilt für Frontend-Ausgabe UND RSS-Feed
- Backward-kompatibel: Leere Datumswerte werden weiterhin angezeigt

```php
// Beispiel: Episode mit Datum 31.12.2025 wird erst ab diesem Datum angezeigt
```

### 2. Automatische ID3-Tag Auslese ✅
**Problem:** Runtime musste manuell in Sekunden eingegeben werden (unbenutzerfreundlich).

**Lösung:**
- Automatisches Auslesen der Laufzeit aus MP3-Dateien via getID3
- Nur wenn Runtime-Feld leer ist
- Backward-kompatibel: Manuelle Eingaben bleiben erhalten
- Anzeige im benutzerfreundlichen Format HH:MM:SS

```php
// Automatisch: 3665 Sekunden → 01:01:05
$item['runtime_formatted'] // HH:MM:SS Format
$item['runtime']          // Original (Sekunden, backward-kompatibel)
```

### 3. RSS Feed Optimierungen ✅

#### Apple Podcasts 2025 Compliance
- `<itunes:type>episodic</itunes:type>` - Erforderlich für neue Podcasts
- `<itunes:episodeType>full</itunes:episodeType>` - Episode-Typ
- `<itunes:episode>123</itunes:episode>` - Episode-Nummer
- `<podcast>` Namespace für erweiterte Features

#### Spotify Kompatibilität
- Korrekte XML-Struktur
- Vollständige Metadaten
- Validierte Enclosure-Tags
- Atom-Self-Link für Feed-Discovery

#### Verbesserte Beschreibungen
- HTML-Links werden korrekt formatiert: `Text (URL)`
- Zeilenumbrüche werden beibehalten
- XOPF-Affiliate-Links werden verarbeitet
- Saubere Text-Ausgabe ohne HTML-Reste

### 4. XOPF Integration ✅
**Problem:** XOPF-Replacements (z.B. `[[AMAZON_LINK]]`) wurden nicht im RSS-Feed gerendert.

**Lösung:**
- Automatische Verarbeitung wenn xoutputfilter Addon aktiv ist
- Graceful fallback wenn Addon nicht verfügbar
- Affiliate-Links funktionieren in RSS-Feeds

```php
// Vorher: [[AMAZON_LINK produkt=123|Buchtitel]]
// Nachher: Buchtitel (https://amazon.de/...)
```

### 5. Kategorie-Filterung ✅
**Problem:** Kategorien existierten, konnten aber nicht gefiltert werden.

**Lösung:**
```php
// Modul/Template Beispiel:
$output = new PodcastOutput([
    'mode' => 'overview',
    'category' => 5, // Nur Episodes aus Kategorie 5
]);
echo $output->render();
```

### 6. Sicherheitsverbesserungen ✅

#### Input Validation
- Alle IDs werden zu Integer konvertiert
- Status-Check in SQL-Queries
- Validierung von Media-Objekten

#### Path Traversal Protection
```php
// Verhindert: ../../../etc/passwd
// Nur Dateien aus Media-Verzeichnis erlaubt
if (strpos(realpath($server_path), realpath(rex_path::media())) !== 0) {
    return; // Blocked!
}
```

#### XSS Prevention
- Alle Ausgaben werden mit `htmlspecialchars()` escaped
- CDATA-Sections in RSS für sichere HTML-Inhalte
- Validierte URL-Ausgaben

### 7. Datenbank-Verbesserungen ✅

#### Neue Felder
```sql
`description` text           -- Dediziertes Feld für Beschreibung
`publishdate` varchar(255)   -- Veröffentlichungsdatum
```

#### Index für Performance
```sql
KEY `status_publishdate` (`status`, `publishdate`)
-- Beschleunigt Episode-Abfragen
```

#### UTF8MB4 Support
- Bessere Unicode-Unterstützung
- Emojis in Titeln/Beschreibungen möglich 🎙️
- Automatische Migration bei Update

### 8. Backward-Kompatibilität ✅

#### Daten-Migration
```php
// Automatisch bei Update:
// - richtext → description (wenn description leer)
// - Bestehende Daten bleiben erhalten
// - Keine Breaking Changes
```

#### API-Kompatibilität
- Alle bestehenden Funktionen funktionieren weiter
- Neue Parameter sind optional
- Graceful Degradation bei fehlenden Features

### 9. Bilderverwaltung ✅

#### Episode-Spezifische Bilder
- Jede Episode kann eigenes Bild haben
- Falls nicht vorhanden: Podcast-Hauptbild
- Automatische Ausgabe in RSS-Feed

```xml
<itunes:image href="https://example.com/episode-123.jpg" />
```

## Verwendung

### Modul mit Kategorie-Filter
```php
<?php
if (rex::isBackend()) {
    echo "PODCAST output - nur auf der Webseite sichtbar.";
} else {
    $category_id = rex_get('category_id', 'int', 0);
    
    $config = [
        'mode'        => 'overview',
        'show_teaser' => true,
        'limit'       => 10,
        'show_audio'  => true,
        'category'    => $category_id, // NEU!
    ];
    
    $output = new PodcastOutput($config);
    echo $output->renderWithAssets();
}
```

### RSS Feed mit Datum-Filterung
```php
// Automatisch aktiv - keine Änderungen nötig!
$rss = new PodcastRSS();
echo $rss->generate('rss2');
// Zeigt nur vergangene/heutige Episodes
```

### Runtime Anzeige
```php
// Backend: Weiterhin Sekunden-Eingabe (backward-kompatibel)
// Frontend: Automatisch formatiert als HH:MM:SS
// RSS: Automatisch als HH:MM:SS für iTunes

<?php
$item = podcastmanager::prepare($episode);
echo $item['runtime_formatted']; // "01:23:45"
echo $item['runtime'];           // "5025" (Sekunden)
?>
```

## SEO Verbesserungen

### RSS Feed
- ✅ Vollständige Dublin Core Metadaten
- ✅ Atom Self-Link für Discovery
- ✅ Korrekte Permalink-GUIDs
- ✅ lastBuildDate für Aktualität
- ✅ Strukturierte Episode-Nummerierung

### Frontend (für zukünftige Erweiterung)
- [ ] JSON-LD Structured Data
- [ ] OpenGraph Meta Tags
- [ ] Twitter Cards
- [ ] Sitemap Integration

## Testing Checkliste

### Veröffentlichungsdatum
- [ ] Episode mit zukünftigem Datum ist unsichtbar
- [ ] Episode mit heutigem Datum ist sichtbar
- [ ] Episode mit vergangenem Datum ist sichtbar
- [ ] Leeres Datum funktioniert (Backward-Compat)

### ID3 Tags
- [ ] Neue Episode ohne Runtime: Automatisch ausgelesen
- [ ] Bestehende Episode mit Runtime: Bleibt unverändert
- [ ] HH:MM:SS Anzeige funktioniert
- [ ] RSS zeigt korrekte Duration

### RSS Feed
- [ ] Apple Podcasts Validator: ✅
- [ ] Spotify Validator: ✅
- [ ] Affiliate Links werden aufgelöst
- [ ] HTML wird korrekt formatiert
- [ ] Episode-Bilder werden angezeigt

### Sicherheit
- [ ] Download mit ungültiger ID: Blocked
- [ ] Download mit Status=0: Blocked
- [ ] Path Traversal Versuch: Blocked
- [ ] XSS in Titel: Escaped

### Backward-Kompatibilität
- [ ] Bestehende Episodes sichtbar
- [ ] Alte Module funktionieren
- [ ] Migration läuft ohne Fehler
- [ ] Keine Datenverluste

## Bekannte Einschränkungen

1. **getID3 Library**: Muss für ID3-Auslese verfügbar sein (ist included)
2. **Datumformat**: Weiterhin DD.MM.YYYY (REDAXO Standard)
3. **xoutputfilter**: Optional - funktioniert auch ohne

## Zukünftige Erweiterungen

### Quick Wins
- [ ] JSON-LD Structured Data für SEO
- [ ] OpenGraph/Twitter Cards für Social Sharing
- [ ] Sitemap-Integration für bessere Indexierung
- [ ] Admin-UI für Runtime-Eingabe (HH:MM:SS Picker)

### Mittelfristig
- [ ] Chapter Markers Support
- [ ] Transkript-Integration
- [ ] Podcast Index Namespace
- [ ] WebVTT/Untertitel Support

### Langfristig
- [ ] Multi-Track Audio
- [ ] Video Podcast Support
- [ ] Analytics Dashboard
- [ ] Auto-Publishing via Scheduler

## Support & Feedback

Bei Fragen oder Problemen:
- GitHub Issues: https://github.com/FriendsOfREDAXO/podcastmanager/issues
- REDAXO Slack: #addons Channel
- Forum: https://www.redaxo.org/de/forum/

---

**Version:** 1.1.0  
**Datum:** 2025-01-14  
**REDAXO Kompatibilität:** 5.13+  
**PHP Anforderung:** 5.6+
