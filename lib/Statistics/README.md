# Podcastmanager Statistics Module

Abstrakte, konfigurierbare Statistik-Integration für verschiedene Server-Analyse-Tools.

## Features

- **Mehrere Provider** - Unterstützung für Webalizer und AWStats
- **Domain-agnostisch** - Keine hardcoded Domains
- **Konfigurierbar** - Pfade und Provider im Admin-Panel einstellbar
- **Auto-Detection** - Automatische Provider-Erkennung
- **Standardisierte Daten** - Einheitliche Datenstruktur unabhängig vom Tool
- **Erweiterbar** - Einfaches Hinzufügen neuer Provider

## Architektur

```
Statistics/
├── StatisticsProvider.php      # Abstract base class
├── StatisticsData.php          # Standardisierte Datenklasse
├── StatisticsManager.php       # Factory & Manager
├── WebalizerProvider.php       # Webalizer-Implementierung
└── AwstatsProvider.php         # AWStats-Implementierung
```

## Provider

### Webalizer

**Dateiformat:** `usage_YYYYMM.html`

```php
$provider = StatisticsManager::createProvider(
    'webalizer',
    '/usage/podcast_domain_de',  // Pfad zu Dateien
    'podcast.example.com'
);

$stats = $provider->getStatistics('07', '2024');
echo $stats->getVisits();    // 5000
echo $stats->getHits();      // 25000
echo $stats->getBandwidth(); // 1073741824 (1 GB in Bytes)
```

### AWStats

**Dateiformat:** `awstats.YYYYMM.domain.txt`

```php
$provider = StatisticsManager::createProvider(
    'awstats',
    '/var/lib/awstats',
    'podcast.example.com'
);

$stats = $provider->getStatistics('07', '2024');
```

## Verwendung im Code

### Basic Setup

```php
use FriendsOfRedaxo\Podcastmanager\Statistics\StatisticsManager;

// Provider erstellen
$provider = StatisticsManager::createProvider(
    'webalizer',
    '/usage/podcast_domain_de',
    rex_yrewrite::getCurrentDomain()->getName()
);

// Verfügbare Monate abrufen
$months = $provider->getAvailableMonths();
// ['2024-07', '2024-06', '2024-05', ...]

// Statistiken für spezifischen Monat laden
$stats = $provider->getStatistics('07', '2024');

// Daten auslesen
$visits = $stats->getVisits();
$bandwidth = $stats->getBandwidth();
$formatted = StatisticsData::formatBandwidth($bandwidth); // "2.5 GB"
```

### Auto-Detection

```php
// Versucht nacheinander Webalizer und AWStats
$provider = StatisticsManager::autoDetectProvider(
    '/usage/podcast_domain_de',
    'podcast.example.com',
    ['webalizer', 'awstats']  // Reihenfolge
);

if ($provider) {
    // Provider gefunden und verfügbar
    $stats = $provider->getStatistics('07', '2024');
}
```

### Provider-Information

```php
$info = StatisticsManager::getProviderInfo('webalizer');
// [
//   'name' => 'Webalizer',
//   'description' => '...',
//   'file_format' => 'usage_YYYYMM.html',
//   'requirements' => 'Webalizer installed on server',
//   'pros' => [...],
//   'cons' => [...]
// ]
```

## Admin-Panel Konfiguration

Im Backend unter "Podcastmanager > Statistiken":

1. **Statistik-Tool** - Wähle Webalizer oder AWStats
2. **Pfad zu Statistik-Dateien** - Relativer oder absoluter Pfad
3. **Aktivieren** - Toggle für Statistik-Anzeige

## Pfad-Beispiele

### Webalizer auf typischem Hoster

```
/usage/podcast_domain_de         # Relativ von Server-Root
../../usage/podcast_domain_de    # Relativ von REDAXO-Root
/home/user/public_html/usage/podcast_domain_de  # Absolut
```

### AWStats auf typischem Hoster

```
/var/lib/awstats               # System-Standard
/home/user/awstats            # Nutzerspezifisch
../../awstats                 # Relativ von REDAXO-Root
```

## Neue Provider hinzufügen

### 1. Provider-Klasse erstellen

```php
<?php
namespace FriendsOfRedaxo\Podcastmanager\Statistics;

class CustomProvider extends StatisticsProvider {
    
    public function getStatistics($month, $year) {
        $data = new StatisticsData($month, $year, $this->getProviderName());
        
        // Daten laden und parsen
        $parsed = $this->parseData();
        
        $data->setVisits($parsed['visits']);
        $data->setHits($parsed['hits']);
        // ... weitere Standard-Felder
        
        return $data;
    }
    
    public function getAvailableMonths() {
        // Verfügbare Monate zurückgeben
        return ['2024-07', '2024-06'];
    }
    
    public function getProviderName() {
        return 'Custom Provider';
    }
    
    protected function parseData() {
        // Custom Parsing-Logik
        return [];
    }
}
```

### 2. In StatisticsManager registrieren

```php
// In createProvider() Methode:
case 'custom':
    return new CustomProvider($path, $domain, $config);
```

### 3. In getAvailableProviders() hinzufügen

```php
public static function getAvailableProviders() {
    return [
        'webalizer' => 'Webalizer',
        'awstats' => 'AWStats',
        'custom' => 'Custom Provider',  // Neu
    ];
}
```

## StandardFields (StatisticsData)

Diese Felder werden von allen Providern unterstützt:

- `visits` - Anzahl Besuche
- `hits` - Anzahl Hits
- `pages` - Anzahl Seiten
- `bandwidth` - Bandbreite in Bytes
- `bots` - Anzahl Bot-Zugriffe
- `failed_requests` - Fehlerhafte Anfragen

### Zusätzliche Felder

Beliebige weitere Felder möglich:

```php
$data->set('custom_field', 'value');
$value = $data->get('custom_field', 'default');
```

## Error Handling

```php
try {
    $provider = StatisticsManager::createProvider(
        'webalizer',
        '/nonexistent/path',
        'example.com'
    );
} catch (\Exception $e) {
    echo "Fehler: " . $e->getMessage();
    // "Statistik Pfad existiert nicht oder ist nicht lesbar"
}

try {
    $stats = $provider->getStatistics('07', '2024');
} catch (\Exception $e) {
    echo "Fehler: " . $e->getMessage();
    // "Webalizer report not found: ..."
}
```

## Zusammenfassung

Die Statistik-Integration ist:
- ✅ Domain-agnostisch
- ✅ Flexibel erweiterbar
- ✅ Benutzerfreundlich konfigurierbar
- ✅ Fehlerresistent
- ✅ Standardisiert über alle Provider
