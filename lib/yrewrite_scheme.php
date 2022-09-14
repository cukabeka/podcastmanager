<?php

\rex_extension::register('YREWRITE_PREPARE', function (\rex_extension_point $ep) {
    // aufgerufene Url holen und auswerten
    // wenn übergebene params enthalten sind, diese in $_GET und $_REQUEST speichern
    // diese stehen dann in deinen Skripten über rex_get() bzw. rex_request() zur Verfügung
    dump($ep);
    $articleId = null;
    $clangId = null;

    return ['article_id' => $articleId, 'clang' => $clangId];
}, \rex_extension::EARLY);

rex_extension::register('PACKAGES_INCLUDED', function (\rex_extension_point $epPackagesIncluded) {
    rex_extension::register('URL_REWRITE', function (\rex_extension_point $ep) {
        // params auswerten
        // und deine Url /article/param-key/param-value/ zurückgeben
        dump($ep);
        $url = '';

        return $url;
    }, rex_extension::EARLY);
}, rex_extension::EARLY);

// konvertiert params zu GET/REQUEST Variablen
 if($this->use_params_rewrite)
 {
   if(strstr($path,'/+/'))
   {
     $tmp = explode('/+/',$path);
     $path = $tmp[0].'/';
     $vars = explode('/',$tmp[1]);
     for($c=0;$c<count($vars);$c+=2)
     {
       if($vars[$c]!='')
       {
         $_GET[$vars[$c]] = $vars[$c+1];
         $_REQUEST[$vars[$c]] = $vars[$c+1];
       }
     }
   }
 }
