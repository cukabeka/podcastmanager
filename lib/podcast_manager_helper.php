<?php
/**
 * podcastmanager Class
 *
 */
class podcastmanager
{
    protected $tpl;
    public $news_id_parameter;
    public $category_id_parameter;
    private $rex_get_categoryId;

    /**
     * tracks a download
     *
     * @return $baseurl id of the newsarticle
     */
    public static function track($item, $origin, $origin_article)
    {

/*
 *          $s = new rex_sql;
            $s->setTable("rex_0_stats_files");
            $s->setValue("kennung",$file_name);
            $s->setValue("year",date("Y"));
            $s->setValue("month",date("m"));
            $s->setValue("day",date("d"));
            $s->setValue("ip",$_SERVER["REMOTE_ADDR"]);
            $s->insert();
            */

        $db_table = "rex_podcastmanager_stats";

        $db = rex_sql::factory();
        //$db->debugsql = 0;
        $db->setTable($db_table);

        $db->setValue("media_id", $item["id"]);
        $db->setValue("show_id", $item["number"]);
        //$db->setValue("url", $url);
        $db->setValue("origin", $origin);
        $db->setValue("origin_article", $origin_article);
        $db->setValue("timestamp", time());

        $db->insert();

        return ;
    }


    /**
     * Returns url of an episode as a string
     * @param string $baseurl Tracking Server URL
     * @param string $mediaurl Media Server URL (can be different if mirroring to CDN is active)
     * @param strong $delivery_article Redaxo Article for providing the media
     * @return string
     */
    public static function getTrackingUrl($item, $delivery_article, $baseurl = "", $origin = false, $origin_article = false, $mediaurl = "")
    {
        $url = "";
        if ($baseurl!="") {
            $url = $baseurl;
        }
        $delivery_article = rex_config::get('podcastmanager', 'detail_id');
        //$url = $baseurl.rex_url::media().$item['audiofiles'];
        $url .= rex_getUrl($delivery_article, 'REX_CLANG_ID', array("deliver"=>$item['id'],"o"=>$origin,"a"=>$origin_article,"f"=>$item['audiofiles']));
        return $url;
    }


    public static function prepare($item, $baseurl = "")
    {
        /**
        * Returns array of normalized values
        * @param string $baseurl Server URL
        * @param array $item podcast content
        * @return string
        */
        // Set Variables - available:
        // $item['title'];
        // $item['number']." ";
        // $item['subtitle'];
        // $item['audiofiles'];
        // $item['runtime'];
        if (is_object(rex_media::get($item['audiofiles']))) {
            $item['filesize'] = rex_media::get($item['audiofiles'])->getSize();
        }
        $item['file_url'] = $baseurl.rex_url::media().$item['audiofiles']; //
        $item['file_link'] = podcastmanager::getTrackingUrl($item, rex_config::get('podcastmanager', 'detail_id'), $baseurl);
        $item['description'] = htmlspecialchars($item['description']);
        $item['date_rfc'] = date(DateTime::RFC2822, strtotime($item['publishdate']));
        $item['publishdate'] = strftime("%d %m %y", strtotime($item['publishdate']));
        $item['updatedate'] = (strtotime($item['date']));
        if ($item['number']!="") {
            $item['number'] = str_pad($item['number'], 3, "0", STR_PAD_LEFT);
        }

        $url_title = podcastmanager::normalize($item['title']);

        $item['episode_url'] = podcastmanager::getShowUrl($item, $baseurl);

        return $item;
    }

    /**
     * Returns url of an episode on website
     * @param string $baseurl Server URL
     * @param strong $delivery_article Redaxo Article for providing the media
     * @return string
     */
    public static function getShowUrl($item, $baseurl = "", $detail_article = '')
    {
        $url = "";
        if ($baseurl!="") {
            $url = $baseurl;
        }

        if ($detail_article=="") {
            $detail_article = rex_config::get('podcastmanager', 'detail_id');
        }
        if (!isset($item)) {
            $item=1;
        } //TBD: letzte episode aus DB holen
        //$url = $baseurl.rex_url::media().$item['audiofiles'];
        $url .= rex_getUrl($detail_article, rex_clang::getCurrentId(), array("episode"=>$item['id']));
        return $url;
    }

    /**
     * Returns baseurl of the project, according to yrewrite
     * @return $baseurl Server URL
     */
    public static function getBaseUrl()
    {
        if (!rex_addon::get('yrewrite')->isAvailable()) {
            $baseurl = rex::getServer();
        } else {
            $baseurl = rex_yrewrite::getCurrentDomain()->getUrl();
        }
        $baseurl = rtrim($baseurl, "/");
    }

    /**
     * Returns normalized string of an url
     * @param string $inputString input string
     * @return string
     */
    public static function normalize($inputString)
    {
        //$inputString = "Á,Â,Ã,Ä,Å,Æ,Ç,È,É,Ê,Ë,Ì,Í,Î,Ï,Ð,Ñ,Ò,Ó,Ô,Õ,Ö,×,Ù,Ú,Û,Ü,Ý,Þ,ß,à,á,â,ã,ä,å,æ,ç,è,é,ê,ë,ì,í,î,ï,ð,ñ,ò,ó,ô,õ,ö,ù,ú,û,ü,ý,þ,ÿ";
        $extraCharsToRemove = array("\"","'","`","^","~"," ",",","!",":","?");
        return str_replace($extraCharsToRemove, "_", iconv("utf-8", "ASCII//TRANSLIT", $inputString));
    }


    /**
     * Returns stream of an episode
     * @param array $file complete item array
     * @return stream
     */
    public static function download($item_id, $origin, $origin_article)
    {
        $pod_items = rex_sql::factory()->getArray('SELECT * FROM rex_podcastmanager WHERE (`id` = '.(int)$item_id.') LIMIT 1');

        if (is_array($pod_items)) {
            $item=$pod_items[0];
        }

        podcastmanager::track($item, $origin, $origin_article);

        $file_name = trim($item['audiofiles']);
        $contentType = rex_media::get($item['audiofiles'])->getType();
        $file_path = rex_url::media().$file_name;
        $server_path = rex_path::media().$file_name;
        rex_response::sendFile($server_path, $contentType, $contentDisposition = 'inline', $file_name);
    }

    /**
     * Returns stream of an episode as a redirect
     * @param array $file complete item array
     * @return initiates download
     */
    public static function deliver($item_id, $origin, $origin_article)
    {
        $pod_items = rex_sql::factory()->getArray('SELECT * FROM rex_podcastmanager WHERE (`id` = '.(int)$item_id.') LIMIT 1');

        if (is_array($pod_items)) {
            $item=$pod_items[0];
        }

        podcastmanager::track($item, $origin, $origin_article);

        $file_name = trim($item['audiofiles']);
        $contentType = rex_media::get($item['audiofiles'])->getType();
        $file_path = rex_url::media().$file_name;
        $server_path = rex_path::media().$file_name;

        $file_url = rtrim(rex_yrewrite::getCurrentDomain()->getUrl(), "/").$file_path;

        #dump($file_url,$server_path);

        #podcastmanager::rangeDownload($server_path);

        if ($file_path != "") {

            //rss: wegen byte range nur direktlink
            if ($origin == 1) {
            } else {

            /*
            https://stackoverflow.com/questions/8517049/itunes-podcast-rss-php-tracking-not-downloading?rq=1

            header("Expires: Mon, 01 Jan 2000 01:01:01 GMT"); // Date in the past
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");// always modified
            header("Content-Type: $file_type; name=\"$file_name\"");
            header("Content-Disposition: attachment; filename=\"$file_name\"");

            fpassthru ($fp);
            fclose($fp);
            */

                //rex_response::sendRedirect($url);

                header("Expires: Mon, 01 Jan 2000 01:01:01 GMT"); // Date in the past
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");// always modified
            header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
            header("Cache-Control: post-check=0, pre-check=0", false);
                header("Cache-Control: private");
                header("Pragma: no-cache");
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: ".$file_url);
            }

            return true;
        } else {
            return "<div class=warning>Download fehlgeschlagen</div>";
        }
    }


    // https://github.com/horst-n/LocalAudioFiles/blob/master/site-default/templates/local-audio-files_stream.php#L250-L357
    /////////// FUNCTIONS ////////////////////////////////////////////////////////
    /*******************************************************************************
    Thomas Thomassen has done a great job on his PHP Resumable Download Server,
    providing working PHP code which supports byte-range downloads.
    The following sample code is the complete version for the one from the
    first part of the article, but including byte-range support by using the
    rangeDownload() function when the $_SERVER['HTTP_RANGE'] header is present
    on the device’s HTTP request.
    The rangeDownload() function is an exact copy&paste from Thomas Thomassen’s code
    (only the relevant part).
    */
    public static function rangeDownload($file)
    {
        $fp = @fopen($file, 'rb');
        $size   = filesize($file); // File size
    $length = $size;           // Content length
    $start  = 0;               // Start byte
    $end    = $size - 1;       // End byte
    // Now that we've gotten so far without errors we send the accept range header
    /* At the moment we only support single ranges.
     * Multiple ranges requires some more work to ensure it works correctly
     * and comply with the spesifications: http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
     *
     * Multirange support annouces itself with:
     * header('Accept-Ranges: bytes');
     *
     * Multirange content must be sent with multipart/byteranges mediatype,
     * (mediatype = mimetype)
     * as well as a boundry header to indicate the various chunks of data.
     */
        header("Accept-Ranges: 0-$length");
        // header('Accept-Ranges: bytes');
        // multipart/byteranges
        // http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
        if (isset($_SERVER['HTTP_RANGE'])) {
            $c_start = $start;
            $c_end   = $end;
            // Extract the range string
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            // Make sure the client hasn't sent us a multibyte range
            if (strpos($range, ',') !== false) {
                // (?) Shoud this be issued here, or should the first
                // range be used? Or should the header be ignored and
                // we output the whole content?
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                // (?) Echo some info to the client?
                exit;
            }
            // If the range starts with an '-' we start from the beginning
            // If not, we forward the file pointer
            // And make sure to get the end byte if spesified
            if ($range0 == '-') {
                // The n-number of the last bytes is requested
                $c_start = $size - substr($range, 1);
            } else {
                $range  = explode('-', $range);
                $c_start = $range[0];
                $c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
            }
            /* Check the range and make sure it's treated according to the specs.
             * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
             */
            // End bytes can not be larger than $end.
            $c_end = ($c_end > $end) ? $end : $c_end;
            // Validate the requested range and return an error if it's not correct.
            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                // (?) Echo some info to the client?
                exit;
            }
            $start  = $c_start;
            $end    = $c_end;
            $length = $end - $start + 1; // Calculate new content length
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
        }
        // Notify the client the byte range we'll be outputting
        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: $length");
        // Start buffered download
        $buffer = 1024 * 8;
        while (!feof($fp) && ($p = ftell($fp)) <= $end) {
            if ($p + $buffer > $end) {
                // In case we're only outputtin a chunk, make sure we don't
                // read past the length
                $buffer = $end - $p + 1;
            }
            set_time_limit(0); // Reset time limit for big files
            echo fread($fp, $buffer);
            flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
        }
        fclose($fp);
    }
}
