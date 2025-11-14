Podcast-Manager
============

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/podcastmanager/assets/screenshot.png)

Dieses Addon stellt eine einfache Podcastverwaltung bereit. Dabei werden die Beiträge in einer eigenen Tabelle abgelegt.

## Beschreibung

Die Kernfunktion ist die Verwaltung von Episoden. Man kann Einstellungen festlegen und die Episoden dann einer oder mehreren Kategorien zuordenen.

Man kann die Ausgabe über Datenbankabfragen realisieren, dazu sind Beispielmodul und -Template mitgeliefert.

Alle zukünftige Funktionen werden über Plugins eingebunden. Das erste Plugin realisiert eine Kommentarfunktioalität.

**Derzeitige Funktionen:**

* Kategorien
* RSS Feed
* Einfache Downlaod-Statistik

**To be done**
* Kommentare (via Plugin)

### Installation

Einfach das Addon nach /redaxo/src/addons/ kopieren und im Addons Bereich installieren.

**Das Addon benötigt folgende Addons:**

* url Addon (für "sprechende" URLs)
* redactor2 Addon (optional, macht aber Sinn wenn man Richtext im Artikel verwenden will).

Das Addon enthält eine Einstellungsseite. Hier sollten Sie die alle notwendigen Angaben angeben, in welcher im Startartikel die Artikelliste und die Artikelansicht ausgegben wird.
Beim Klick auf "Einstellungen speichern" wird (falls vorhanden) ein Profil für das redactor2 Addon sowie die Einstellungen für das url Addon angelegt.

### Template anpassen

Man kann natürlich einfach eine entsprechende Datenbank Abfrage machen und sich selbst um die Ausgabe kümmern. Wie das geht, kann man in der REDAXO Doku nachlesen bzw im mitgelieferten Template.

**Kategorie Menü**

```php
echo $podcastmanager->printCategoryMenu();
```

Den Quellcode für die Ausgabe kann man auch anpassen.
Es gibt dafür sog. Views, also HTML/PHP Schnipsel die in /redaxo/data/addons/podcastmanager/views/ bzw. für die Kommentare
unter /redaxo/data/addons/podcastmanager/views/comments/views/ abgelegt sind.



## 📦 Neue Addon-Klassen

### 1. **PodcastOutput** - Frontend Ausgabe
```php
$output = new PodcastOutput([
    'mode' => 'overview',      // 'start', 'overview', 'detail'
    'show_teaser' => true,     // Description anzeigen
    'limit' => 10,             // Anzahl der Episodes
    'show_audio' => true,      // Player anzeigen
    'width' => 12,             // Bootstrap Breite
]);

echo $output->render();              // Nur HTML
echo $output->renderWithAssets();    // Mit CSS/JS
```

### 2. **PodcastRSS** - RSS Feed Generation
```php
$rss = new PodcastRSS();

// iTunes Podcast Feed
echo $rss->generate('itunes');

// Klassisches RSS 2.0
echo $rss->generate('rss2');
```

## 🎯 Die 3 Output-Modi

### Overview (Episodenliste)
```php
$output = new PodcastOutput(['mode' => 'overview']);
echo $output->render();
```
→ Listet alle Episoden als Teasers auf

### Start (Featured + Teaser)
```php
$output = new PodcastOutput(['mode' => 'start']);
echo $output->render();
```
→ Erste Episode groß mit Player + Rest als Teaser

### Detail (Einzelne Episode)
```php
$output = new PodcastOutput(['mode' => 'detail']);
echo $output->render();
```
→ Volle Episode mit Player und kompletten Infos

## 📝 Modul - Jetzt super einfach!

**Alte Struktur** (~260 Zeilen):
```php
// Viel Inline-Code, SQL, Helper Functions...
```

**Neue Struktur** (~30 Zeilen):
```php
<?php
if (rex::isBackend()) {
    echo "PODCAST output - nur auf der Webseite sichtbar.";
} else {
    $config = [
        'mode'        => 'REX_VALUE[1]',
        'show_teaser' => (bool)"REX_VALUE[2]",
        'limit'       => ((int)"REX_VALUE[3]" != 0) ? "REX_VALUE[3]" : '',
        'show_audio'  => (bool)"REX_VALUE[4]",
        'width'       => ((int)"REX_VALUE[6]" != 0) ? "REX_VALUE[6]" : 12,
    ];
    
    $output = new PodcastOutput($config);
    echo $output->renderWithAssets();
}
```

## 📡 Template - Auch super einfach!

**Alte Struktur** (~450 Zeilen):
```php
// Komplexe RSS-Generierung, viel Code...
```

**Neue Struktur** (~15 Zeilen):
```php
<?php
if("REX_ARTICLE_ID" != "234") 
    rex_response::sendContentType('application/xml; charset=utf-8');

$feed_format = rex_get("feed_version") !== "classic" ? 'itunes' : 'rss2';
$rss = new PodcastRSS();
echo $rss->generate($feed_format);

exit;
```

## 💡 Verwendungsbeispiele

### In einem Custom Snippet
```php
// Übersicht aller Episoden
$output = new PodcastOutput(['mode' => 'overview']);
echo $output->render();
```

### In einer API
```php
// JSON für Frontend
$output = new PodcastOutput(['mode' => 'overview', 'limit' => 50]);
$episodes = json_encode($output->render());
```

### Im Fragment
```php
// fragment/podcast.php
$output = new PodcastOutput([
    'mode' => rex_var::get('mode', 'overview'),
    'limit' => rex_var::get('limit', 10),
]);
return $output->render();
```

### Mit Caching
```php
$cache_key = 'podcast_episodes';

if ($cached = rex_cache::get($cache_key)) {
    echo $cached;
} else {
    $output = new PodcastOutput(['mode' => 'overview']);
    $html = $output->render();
    rex_cache::set($cache_key, $html, 3600);
    echo $html;
}
```

## 🔧 Konfigurationsoptionen

| Option | Wert | Beschreibung |
|--------|------|-------------|
| `mode` | 'start', 'overview', 'detail' | Output-Modus |
| `show_teaser` | true/false | Teaser Text anzeigen |
| `limit` | Zahl | Anzahl Episodes |
| `show_audio` | true/false | Audio Player anzeigen |
| `width` | 1-12 | Bootstrap Spalten Breite |
| `detail_id` | Zahl | Detail Artikel ID |
| `order` | 'ASC'/'DESC' | Sortierreihenfolge |

## ✨ Vorteile

✅ **Standalone-Nutzbar** - Nicht an Module/Templates gebunden  
✅ **Wiederverwendbar** - Überall im Projekt einsetzbar  
✅ **Wartbar** - Zentrale Logik an einer Stelle  
✅ **Testbar** - Klassen können isoliert getestet werden  
✅ **Erweiterbar** - Neue Modi leicht hinzufügbar  
✅ **Performant** - Keine redundante Logik  
✅ **Konsistent** - Ein Standard Output für alle Kontexte  

## 📚 Dokumentation

- **STANDALONE_ARCHITECTURE.md** - Detaillierte Architektur-Dokumentation
- **USAGE_EXAMPLES.php** - 15+ Praktische Beispiele
- **PodcastOutput.php** - Ausführliche Kommentare im Code
- **PodcastRSS.php** - Ausführliche Kommentare im Code

## 🔍 Debugging

```php
// So prüfst du, ob alles funktioniert

// 1. Klasse verfügbar?
if (class_exists('PodcastOutput')) {
    echo "✓ PodcastOutput gefunden";
}

// 2. Output generieren
$output = new PodcastOutput(['mode' => 'overview']);
$html = $output->render();

// 3. HTML vorhanden?
if (!empty($html)) {
    echo "✓ HTML generiert";
    echo $html;
} else {
    echo "✗ Kein HTML generiert";
}
```

## 🚀 Nächste Schritte

1. ✅ Die neuen Klassen sind bereits erstellt
2. ✅ Modul und Template sind bereits vereinfacht
3. 📝 Optional: Weitere Modi hinzufügen (z.B. 'featured', 'latest')
4. 📝 Optional: Custom RSS Formate hinzufügen
5. 📝 Optional: Output in Fragments verwenden

## ❓ FAQ

**Q: Funktioniert das alte Modul noch?**  
A: Ja, das alte Modul wird durch das neue ersetzt, aber die **Ausgabe ist identisch**.

**Q: Muss ich meine Seiten neu speichern?**  
A: Nein, alles funktioniert sofort. Die Module/Templates müssen nicht angepasst werden.

**Q: Kann ich die Addon-Klassen überall verwenden?**  
A: Ja! In Snippets, Fragmenten, APIs, CLIs - überall wo PHP läuft.

**Q: Wie füge ich einen neuen Modus hinzu?**  
A: In `PodcastOutput.php` eine neue Methode `renderCustomMode()` und in `render()` eine neue `case` hinzufügen.

**Q: Wie teste ich die Klassen?**  
A: Siehe `USAGE_EXAMPLES.php` für 15+ Beispiele.

## 💬 Support

Alle 80+ Zeilen Code sind ausführlich kommentiert. Die Klassen-Struktur ist selbserklärend.

---

**Happy Podcasting! 🎙️**
