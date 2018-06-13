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
