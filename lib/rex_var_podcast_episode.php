<?php
/**
* Erstellt die Variable REX_PODCAST_EPISODE[]. Mit ihr kann im Fließtext ein Link zu einer Episode ausgegeben werden.
*
* Syntax Beispiel von https://redaxo.org/doku/master/redaxo-variablen
*     REX_WEBSITE_TITLE[] // Gibt den Titel aus
*     REX_WEBSITE_TITLE[case=lower] // Gibt den Titel in Kleinbuchstaben aus
*     REX_WEBSITE_TITLE[case=upper] // Gibt den Titel in Großbuchstaben aus
*
* Syntax:
*     //REX_PODCAST_EPISODE[] // Gibt Link zur aktuellen Episode aus
*     REX_PODCAST_EPISODE[number=1] // Gibt Link zur aktuellen Episode aus
*     //REX_PODCAST_EPISODE[url=1] // Gibt nur URL aus
*/
dump("rex_var_podcast_episode");
class rex_var_podcast_episode extends rex_var
{
    protected function getOutput()
    {
        // Episoden-url holen
        // $url = podcastmanager::getLatestShowUrl();

        // Prüfen, ob der Parameter 'number' vorhanden ist.
        // Durch ihn kann die Ausgabe manipuliert werden.
        if ($this->hasArg('number') && $this->getArg('number')) {

           // SQL
            $pod_items = rex_sql::factory()->getArray('SELECT * FROM rex_podcastmanager
                                          WHERE (`status` = 1)
                                          `number`='.$this->getArg('number').'
                                          ORDER BY STR_TO_DATE(publishdate, "%d.%m.%Y") DESC LIMIT 1');
            $item = podcastmanager::prepare($pod_items[0], podcastmanager::getBaseUrl());
            $url = podcastmanager::getShowUrl($this->getArg('number'));
            $link = '<a href="'.$item['episode_url'].'>'.$item['title'].'</a>';
        }

        // Reine Textausgaben müssen mit 'self::quote()' als String maskiert werden.
        return self::quote($link);
    }
}
