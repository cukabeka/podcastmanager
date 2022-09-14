<?php


$content = '';
$buttons = '';

if (!rex_addon::get('yrewrite')->isAvailable()) {
    $baseurl = rex::getServer();
} else {
    $baseurl = rex_yrewrite::getCurrentDomain()->getUrl();
}


$func = rex_request('func', 'string');


// https://stackoverflow.com/questions/8820607/parsing-webalizer-or-and-awstats-html-file
//Path to Webalizer or AWStats file
$xpath = PodcastManagerGetStatsWebalizer('07','2022','podcast.gfk-trainer.de','/usage/podcast_gfk-trainer_de');
dump($xpath);

// Get the first bandwidth record in the table
$query = "//tr[7]/td[7]/font/text()";
$bandwidth1 = $xpath->query($query);
dump($bandwidth1);

function PodcastManagerGetStatsWebalizer($month,$year,$domain,$path) {
    $doc = new DOMDocument;
    $filename = rex_path::frontend().'../..'.$path.'/usage_'.$year.$month.'.html';# /usage/podcast_gfk-trainer_de/usage_202207.html - war: $path.'awstats'.$month.$year.'.'.$domain.'.txt';
    dump($filename);
    $doc->Load($filename);
    $xpath = new DOMXPath($doc);
    return $xpath;
}

//https://geekthis.net/post/parsing-awstats-with-php/
/* Sample Setup to test the code */

/* AWStats - leider nur mit Textdateien, auf dem Server hier nicht verfügbar

$p = new WebalizeMe('07','2022','podcast.gfk-trainer.de','/usage/podcast_gfk-trainer_de');
dump($p,$p->data);

class WebalizeMe {
private $fh = false;
public $lastError = false;
public $data = array();

    function __construct($month,$year,$domain,$path) {
        $filename = rex_path::frontend().'../..'.$path.'/usage_'.$year.$month.'.html';# /usage/podcast_gfk-trainer_de/usage_202207.html - war: $path.'awstats'.$month.$year.'.'.$domain.'.txt';
        dump($filename,rex_path::frontend()); 
        # /usage/podcast_gfk-trainer_de/usage_202207.html
        #"/usage/podcast_gfk-trainer_de/usage_202207.html"
        if(!file_exists($filename)) {
$this->lastError = 'File does not exist.';
return false;
}

$this->fh = fopen($filename,'r');
if($this->fh === false) {
$this->lastError = 'File cannot be opened.';
return false;
}

$this->parse();
}

//rest im Link https://geekthis.net/post/parsing-awstats-with-php/