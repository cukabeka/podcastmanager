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
     * @param array $item Episode data
     * @param int $origin Origin type (1=RSS, 2=Website, 3=Direct)
     * @param int $origin_article Origin article ID
     * @return void
     */
    public static function track($item, $origin, $origin_article)
    {
        // Use new PodcastStats class if available
        if (class_exists('PodcastStats')) {
            $downloadType = 'stream';
            if ($origin == 1) {
                $downloadType = 'rss';
            } elseif ($origin == 3) {
                $downloadType = 'download';
            }
            
            PodcastStats::track($item, $downloadType, [
                'origin' => $origin,
                'origin_article' => $origin_article,
            ]);
        }
        
        // Legacy tracking for backward compatibility
        $db_table = rex::getTable("podcastmanager_stats");

        $db = rex_sql::factory();
        $db->setTable($db_table);

        $db->setValue("episode_id", $item["id"]);
        $db->setValue("episode_number", $item["number"]);
        $db->setValue("timestamp", time());
        $db->setValue("createdate", date('Y-m-d H:i:s'));

        try {
            $db->insert();
        } catch (Exception $e) {
            // Silently fail - don't break the download
        }

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
        if ((rex_config::get('podcastmanager', 'stats_rss_active') != "active")) {
            $url = "";
        }
        $delivery_article = rex_config::get('podcastmanager', 'detail_id');
        //$url = $baseurl.rex_url::media().$item['audiofiles'];
        #$url .= rex_getUrl($delivery_article, 'REX_CLANG_ID', array("deliver"=>$item['id'],"o"=>$origin,"a"=>$origin_article,"f"=>$item['audiofiles']));
        $url .= rex_url::base('index.php?rex_media_type=log_statistics&rex_media_file='.$item['audiofiles']);
            #rex_media_manager::getUrl('log_statistics',$item['audiofiles']);
        return $url;
    }

    public static function urlFeedConvert($str, $format = 'text')
    {
        /**
         * Convert HTML description to RSS-friendly format
         * 
         * @param string $str HTML content
         * @param string $format Output format: 'text', 'markdown', 'html'
         * @return string Converted content
         */
        
        // Process xoutputfilter replacements if addon is available
        if (rex_addon::exists('xoutputfilter') && rex_addon::get('xoutputfilter')->isAvailable()) {
            try {
                $str = xoutputfilter::replace($str, rex_clang::getCurrentId());
            } catch (Exception $e) {
                // If xoutputfilter fails, continue without it
            }
        }

        // Return based on format
        switch ($format) {
            case 'markdown':
                // Convert HTML to Markdown using markdownify library
                try {
                    $converter = new Markdownify\Converter();
                    $markdown = $converter->parseString($str);
                    return trim($markdown);
                } catch (Exception $e) {
                    // Fallback to text if markdownify fails
                    return self::urlFeedConvert($str, 'text');
                }
                break;
                
            case 'html':
                // Return cleaned HTML (remove dangerous tags but keep structure)
                $str = strip_tags($str, '<a><p><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6>');
                return trim($str);
                break;
                
            case 'text':
            default:
                // Convert HTML to plain text (original behavior)
                // Replace <br> and <p> tags with line breaks
                $str = str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $str);
                $str = str_replace('<p>', "\n\n", $str);
                
                // Convert links to readable format: Link text (URL)
                $str = preg_replace('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>([^<]+)<\/a>/i', '$2 ($1)', $str);
                
                // Remove remaining HTML tags but keep the content
                $str = strip_tags($str);
                
                // Clean up extra whitespace
                $str = preg_replace('/\n{3,}/', "\n\n", $str);
                $str = trim($str);
                
                return $str;
        }
    }
    /**
 * Function: sanitize
 * Returns a sanitized string, typically for URLs.
 *
 * Parameters:
 *     $string - The string to sanitize.
 *     $force_lowercase - Force the string to lowercase?
 *     $anal - If set to *true*, will remove all non-alphanumeric characters.
 */
    public static function sanitize($string, $force_lowercase = true, $anal = false) {
        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
                       "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
                       "â€”", "â€“", ",", "<", ".", ">", "/", "?");
        $clean = trim(str_replace($strip, "", strip_tags($string)));
        $clean = preg_replace('/\s+/', "-", $clean);
        $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
        return ($force_lowercase) ?
            (function_exists('mb_strtolower')) ?
            mb_strtolower($clean, 'UTF-8') :
        strtolower($clean) :
        $clean;
    }

    public static function prepare($item, $baseurl = "")
    {
        /**
        * Returns array of normalized values
        * @param string $baseurl Server URL
        * @param array $item podcast content
        * @return array
        */
        // Set Variables - available:
        // $item['title'];
        // $item['number']." ";
        // $item['subtitle'];
        // $item['audiofiles'];
        // $item['runtime'];
        if (rex_config::get('podcastmanager', 'stats_rss_active') != 'active') $baseurl="";

        // Backward compatibility: Use richtext if description is empty
        if (empty($item['description']) && !empty($item['richtext'])) {
            $item['description'] = $item['richtext'];
        }
        
        // Process xoutputfilter if available
        if (rex_addon::exists('xoutputfilter') && rex_addon::get('xoutputfilter')->isAvailable()) {
            try {
                $item['description'] = xoutputfilter::replace($item['description'], rex_clang::getCurrentId());
            } catch (Exception $e) {
                // Continue without xoutputfilter if it fails
            }
        }
        
        $item['description'] = htmlspecialchars($item['description']);
        
        $item['file_url'] = $baseurl.rex_url::media().$item['audiofiles']; //
        $item['file_link'] = podcastmanager::getTrackingUrl($item, rex_config::get('podcastmanager', 'detail_id'), $baseurl);
        $item['audiofiles'] = trim($item['audiofiles']);
        
        // Handle publish date - PHP 8.4 compatible (replaced strftime)
        if (!empty($item['publishdate'])) {
            $timestamp = strtotime($item['publishdate']);
            $item['date_rfc'] = date(DateTime::RFC2822, $timestamp);
            // Format: dd.mm.yy (e.g., 14.01.25) - PHP 8.4 compatible
            $item['publishdate'] = date('d.m.y', $timestamp);
        } else {
            // Fallback to creation date if no publish date
            $timestamp = strtotime($item['createdate']);
            $item['date_rfc'] = date(DateTime::RFC2822, $timestamp);
            $item['publishdate'] = date('d.m.y', $timestamp);
        }
        
        $item['updatedate'] = (strtotime($item['updatedate']));
        
        if ($item['number']!="") {
            $item['number'] = str_pad($item['number'], 3, "0", STR_PAD_LEFT); #passt Zahlen auf das Format 00X an
        }
        
        // Get filesize
        if (empty($item['filesize']) AND !empty($item['audiofiles'])) {
            $media = rex_media::get($item['audiofiles']);
            if ($media) {
                $item['filesize'] = $media->getSize();
            }
        }

        $item['episode_url'] = podcastmanager::getShowUrl($item, $baseurl);
        
        // Extract ID3 tags from MP3 file if getID3 is available and runtime is empty
        if (class_exists('getID3') && !empty($item['audiofiles']) && empty($item['runtime'])) {
            try {
                $getID3 = new getID3;
                $media_path = rex_path::media() . $item['audiofiles'];
                
                if (file_exists($media_path)) {
                    $fileInfo = $getID3->analyze($media_path);
                    
                    // Extract playtime in seconds
                    if (isset($fileInfo['playtime_seconds'])) {
                        $item['runtime'] = (int)$fileInfo['playtime_seconds'];
                    }
                    
                    // Store additional info if needed
                    if (isset($fileInfo['playtime_string'])) {
                        $item['runtime_formatted'] = $fileInfo['playtime_string'];
                    }
                }
            } catch (Exception $e) {
                // Silently ignore getID3 errors
            }
        }
        
        // Format runtime for display (convert seconds to HH:MM:SS if numeric)
        if (!empty($item['runtime']) && is_numeric($item['runtime'])) {
            $seconds = (int)$item['runtime'];
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $secs = $seconds % 60;
            $item['runtime_formatted'] = sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }
        
        return $item;
    }

    /**
     * Returns url of an episode on website
     * @param string $baseurl Server URL
     * @param strong $delivery_article Redaxo Article for providing the media
     * @return string
     */
    public static function getShowUrl($item, $baseurl = "", $detail_article = '', $params = "")
    {
        if (rex_config::get('podcastmanager', 'stats_rss_active') != 'active') $baseurl="";
        $url = "";
        if ($baseurl!="") {
            $url = $baseurl;
        }

        if ($detail_article=="") {
            $detail_article = rex_config::get('podcastmanager', 'detail_id');
        }
        if (!isset($item)) {
            $item=1;
        }

        //TBD: letzte episode aus DB holen
        //$url = $baseurl.rex_url::media().$item['audiofiles'];
        $url .= rex_getUrl($detail_article, rex_clang::getCurrentId(), array("episode"=>$item['number'],"id"=>$item['id'],"thema"=>$item['title']));
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
     * http://www.ebrueggeman.com/blog/abbreviate-text-without-cutting-words-in-half
     * trims text to a space then adds ellipses if desired
     * @param string $input text to trim
     * @param int $length in characters to trim to
     * @param bool $ellipses if ellipses (...) are to be added
     * @param bool $strip_html if html tags are to be stripped
     * @return string
     */
    public static function trim_text($input, $length, $ellipses = true, $strip_html = true) {
        //strip tags, if desired
        if ($strip_html) {
            $input = strip_tags($input);
        }

        //no need to trim, already shorter than trim length
        if (strlen($input) <= $length) {
            return $input;
        }

        //find last space within length
        $last_space = strrpos(substr($input, 0, $length), ' ');
        $trimmed_text = substr($input, 0, $last_space);

        //add ellipses (...)
        if ($ellipses) {
            $trimmed_text .= '...';
        }

        return $trimmed_text;
    }


    /**
     * Returns stream of an episode
     * @param int $item_id Episode ID
     * @param int $origin Origin identifier
     * @param int $origin_article Origin article ID
     * @return stream
     */
    public static function download($item_id, $origin, $origin_article)
    {
        // Security: Validate and sanitize inputs
        $item_id = (int)$item_id;
        $origin = (int)$origin;
        $origin_article = (int)$origin_article;
        
        if ($item_id <= 0) {
            return;
        }
        
        $pod_items = rex_sql::factory()->getArray('SELECT * FROM ' . rex::getTable('podcastmanager') . ' WHERE (`id` = ' . $item_id . ') AND (`status` = 1) LIMIT 1');

        if (!is_array($pod_items) || empty($pod_items)) {
            return;
        }
        
        $item = $pod_items[0];

        podcastmanager::track($item, $origin, $origin_article);

        $file_name = trim($item['audiofiles']);
        
        // Security: Validate media file exists
        $media = rex_media::get($file_name);
        if (!$media) {
            return;
        }
        
        $contentType = $media->getType();
        $server_path = rex_path::media() . $file_name;
        
        // Security: Verify file exists and is within media directory
        if (!file_exists($server_path) || strpos(realpath($server_path), realpath(rex_path::media())) !== 0) {
            return;
        }
        
        rex_response::sendFile($server_path, $contentType, $contentDisposition = 'inline', $file_name);
    }

    /**
     * Returns stream of an episode as a redirect
     * @param int $item_id Episode ID
     * @param int $origin Origin identifier
     * @param int $origin_article Origin article ID
     * @return initiates download
     */
    public static function deliver($item_id, $origin, $origin_article)
    {
        // Security: Validate and sanitize inputs
        $item_id = (int)$item_id;
        $origin = (int)$origin;
        $origin_article = (int)$origin_article;
        
        if ($item_id <= 0) {
            return "<div class=warning>Download fehlgeschlagen</div>";
        }
        
        $pod_items = rex_sql::factory()->getArray('SELECT * FROM ' . rex::getTable('podcastmanager') . ' WHERE (`id` = ' . $item_id . ') AND (`status` = 1) LIMIT 1');

        if (!is_array($pod_items) || empty($pod_items)) {
            return "<div class=warning>Download fehlgeschlagen</div>";
        }
        
        $item = $pod_items[0];

        podcastmanager::track($item, $origin, $origin_article);

        $file_name = trim($item['audiofiles']);
        
        // Security: Validate media file exists
        $media = rex_media::get($file_name);
        if (!$media) {
            return "<div class=warning>Download fehlgeschlagen</div>";
        }
        
        $contentType = $media->getType();
        $file_path = rex_url::media() . $file_name;
        $server_path = rex_path::media() . $file_name;
        
        // Security: Verify file exists and is within media directory
        if (!file_exists($server_path) || strpos(realpath($server_path), realpath(rex_path::media())) !== 0) {
            return "<div class=warning>Download fehlgeschlagen</div>";
        }

        $file_url = rtrim(rex_yrewrite::getCurrentDomain()->getUrl(), "/") . $file_path;

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
                header("Location: " . $file_url);
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
