# Server Statistics Integration - Admin Panel Setup Guide

## Übersicht

Die neuen Server-Statistik-Einstellungen im Podcastmanager ermöglichen es dir, Statistiken von Webalizer oder AWStats im Backend anzuzeigen.

## Admin-Panel Konfiguration

Du findest die neuen Einstellungen unter **Podcastmanager > Einstellungen** im neuen Bereich **"Server-Statistiken (Webalizer / AWStats)"**.

### Einstellungsfelder

#### 1. **Statistiken aktivieren** (Checkbox)
- **Standard:** Deaktiviert
- **Funktion:** Schaltet die gesamte Statistik-Anzeige ein/aus
- **Hinweis:** Ohne Aktivierung sind die anderen Einstellungen wirkungslos

#### 2. **Statistik-Tool** (Select)
- **Optionen:**
  - Webalizer (HTML Reports)
  - AWStats (Text Reports)
- **Standard:** Webalizer
- **Hinweis:** Wähle das Tool, das auf deinem Server verfügbar ist

#### 3. **Pfad zu Statistik-Dateien** (Text Input)
- **Erforderlich:** Ja (wenn Statistiken aktiviert sind)
- **Format:** Relativ oder absolut
- **Beispiele:**
  ```
  /usage/podcast_domain_de
  ../../usage/podcast_domain_de
  /home/user/public_html/usage/podcast_domain_de
  /var/lib/awstats
  ```

### Hilfetext & Hinweise

Im Admin-Panel ist eine Box mit folgendem Info-Text integriert:

```
Verfügbare Statistik-Tools:

- Webalizer: HTML-Reports im Format usage_YYYYMM.html. 
  Auf vielen Hostern unter /usage verfügbar.

- AWStats: Text-basierte Reports im Format awstats.YYYYMM.domain.txt.
  Typischerweise unter /var/lib/awstats oder im Nutzerhome.

Die Pfade können je nach Hoster unterschiedlich sein. 
Kontaktiere deinen Support, falls du nicht weißt, wo die Dateien liegen.
```

## Pfad-Finder für häufige Hoster

### Strato
```
Webalizer: /usage/podcast_domain_de
```

### Ionos (ehemals 1&1)
```
Webalizer: /usage/podcast_domain_de
AWStats: /var/www/virtual/user/awstats
```

### netcup
```
Webalizer: ../../usage/podcast_domain_de
AWStats: /var/lib/awstats
```

### All-Inkl
```
Webalizer: /www/htdocs/username/domains/podcast.de/usage
AWStats: /www/ftpusers/username/webs/awstats
```

### Hetzner
```
Webalizer: /var/www/virtual/username/public_html/usage
AWStats: /var/lib/awstats
```

### Eigener Server / VPS
```
Webalizer: /var/www/html/usage
AWStats: /var/lib/awstats
```

## Wie du den richtigen Pfad findest

### 1. Kontakt zum Support
- Das ist die zuverlässigste Methode
- Suche nach "Webalizer" oder "AWStats" im Hoster-FAQ

### 2. über SSH / Terminal
```bash
# Webalizer suchen
find / -name "usage_*.html" 2>/dev/null | head -5

# AWStats suchen  
find / -name "awstats.*.txt" 2>/dev/null | head -5

# Oder direkt schauen
ls -la /usage/
ls -la /var/lib/awstats/
```

### 3. über FTP/File Manager
- Navigiere zum Root-Verzeichnis
- Suche nach `usage` Ordner (für Webalizer)
- Suche nach `awstats` Ordner oder `awstats.*` Dateien

## Konfiguration testen

Nach dem Speichern der Einstellungen:

1. Gehe zu **Podcastmanager > Statistiken**
2. Sollte folgende Meldungen zeigen:
   - ✅ **Statistiken verfügbar:** Pfad ist korrekt und erreichbar
   - ⚠️ **Pfad nicht verfügbar:** Überprüfe den Pfad
   - ❌ **Provider Fehler:** Dateien nicht im erwarteten Format

## Unterstützte Dateiformate

### Webalizer
```
/path/to/stats/usage_202407.html
/path/to/stats/usage_202406.html
/path/to/stats/usage_202405.html
```

### AWStats
```
/path/to/stats/awstats.202407.domain.txt
/path/to/stats/awstats.202407.domain_de.txt
/path/to/stats/awstats.202407.txt
```

## Troubleshooting

### Problem: "Statistik-Pfad nicht verfügbar"
- ✅ Überprüfe Schreibrechte
- ✅ Stelle sicher, dass der Pfad existiert
- ✅ Versuche mit einem anderen Pfad-Format
- ✅ Wende dich an deinen Hoster

### Problem: "Keine Monate verfügbar"
- ✅ Webalizer/AWStats wurde noch nicht ausgeführt
- ✅ Die Dateien sind älter als 1 Monat
- ✅ Falscher Provider ausgewählt
- ✅ Überprüfe auf Unterschied zwischen Domain-Namen (mit/ohne -)

### Problem: "Fehlerhafte Anfragen" oder "0" bei allen Werten
- ✅ HTML/Text-Struktur hat sich geändert
- ✅ Report von älterem/neueren Tool generiert
- ✅ Kontaktiere den Support des Hosters

## Code-Integration (für Entwickler)

Die Statistiken können auch im Code verwendet werden:

```php
use FriendsOfRedaxo\Podcastmanager\Statistics\StatisticsManager;

$stats_path = rex_addon::get('podcastmanager')->getConfig('stats_path');
$stats_provider = rex_addon::get('podcastmanager')->getConfig('stats_provider');
$stats_enabled = rex_addon::get('podcastmanager')->getConfig('stats_enabled');

if ($stats_enabled && $stats_path) {
    $provider = StatisticsManager::createProvider(
        $stats_provider,
        $stats_path,
        rex_yrewrite::getCurrentDomain()->getName()
    );
    
    $months = $provider->getAvailableMonths();
    $stats = $provider->getStatistics('07', '2024');
    
    echo "Besuche: " . $stats->getVisits();
}
```

Siehe auch: `redaxo/src/addons/podcastmanager/lib/Statistics/README.md`
