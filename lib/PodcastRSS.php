<?php
/**
 * PodcastRSS Class
 * 
 * Handles RSS feed generation for podcasts
 * Centralizes logic from RSS template into addon
 */
class PodcastRSS
{
    /**
     * Configuration settings
     */
    private $config = [];
    
    /**
     * Base URL for the current domain
     */
    private $baseurl = '';
    
    /**
     * Tracking URL for statistics
     */
    private $track_url = '';
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->baseurl = $this->getBaseUrl();
        $this->track_url = $this->getTrackingUrl();
        $this->loadConfiguration();
    }
    
    /**
     * Get base URL (with yrewrite support)
     */
    private function getBaseUrl()
    {
        if (!rex_addon::get('yrewrite')->isAvailable()) {
            $baseurl = rex::getServer();
        } else {
            $baseurl = rex_yrewrite::getCurrentDomain()->getUrl();
        }
        
        $baseurl = rtrim($baseurl, "/");
        
        // Convert to HTTP for RSS compliance
        $parsedUrl = parse_url($baseurl);
        if (($parsedUrl['scheme'] ?? '') !== 'http') {
            $baseurl = substr_replace($baseurl, 'http', 0, strlen($parsedUrl['scheme'] ?? ''));
        }
        
        return $baseurl;
    }
    
    /**
     * Get tracking URL for statistics
     */
    private function getTrackingUrl()
    {
        $url = $this->baseurl;
        $parsedUrl = parse_url($this->baseurl);
        $track_base = $parsedUrl['host'] ?? '';
        
        if (rex_config::get('podcastmanager', 'stats_rss_active') === 'active') {
            $prefix = rex_config::get('podcastmanager', 'stats_prefix');
            $url = $prefix . "/" . $track_base;
        }
        
        return $url;
    }
    
    /**
     * Load all configuration from podcastmanager config
     */
    private function loadConfiguration()
    {
        $this->config = [
            'feed_title' => rex_config::get('podcastmanager', 'feed_title'),
            'feed_link' => htmlspecialchars(rex_config::get('podcastmanager', 'feed_link')),
            'feed_description' => html_entity_decode(htmlspecialchars(strip_tags(rex_config::get('podcastmanager', 'feed_description')))),
            'feed_item_additions' => html_entity_decode(htmlspecialchars(strip_tags(rex_config::get('podcastmanager', 'feed_item_additions')))),
            'feed_license' => rex_config::get('podcastmanager', 'feed_license'),
            'feed_lang' => rex_config::get('podcastmanager', 'feed_lang') ?: 'de_DE',
            'feed_author' => rex_config::get('podcastmanager', 'feed_author'),
            'feed_owner' => rex_config::get('podcastmanager', 'feed_owner'),
            'feed_email' => rex_config::get('podcastmanager', 'feed_email'),
            'feed_explicit' => rex_config::get('podcastmanager', 'feed_explicit'),
            'feed_category' => rex_config::get('podcastmanager', 'feed_category'),
            'feed_subcategory' => rex_config::get('podcastmanager', 'feed_subcategory'),
            'feed_keywords' => rex_config::get('podcastmanager', 'feed_keywords'),
            'feed_limit' => rex_config::get('podcastmanager', 'setting_limit') ? 'LIMIT ' . rex_config::get('podcastmanager', 'setting_limit') : '',
        ];
        
        // Process subtitle
        $subtitle = strip_tags(rex_config::get('podcastmanager', 'feed_subtitle'));
        $this->config['feed_subtitle'] = strlen($subtitle) > 252 ? substr($subtitle, 0, 252) . '...' : $subtitle;
        
        // Process feed image
        $feed_image = rex_config::get('podcastmanager', 'feed_image');
        if (rex_addon::exists("media_manager_autorewrite")) {
            $this->config['feed_image'] = htmlspecialchars($this->baseurl . mm_auto::rewrite($feed_image, 'podcastmanager_main_image'));
        } else {
            $this->config['feed_image'] = htmlspecialchars($this->baseurl . "index.php?rex_media_type=podcastmanager_main_image&rex_media_file=" . $feed_image);
        }
    }
    
    /**
     * Generate complete RSS feed
     * 
     * @param string $format 'rss2' for classic RSS, 'itunes' for iTunes Podcast
     * @return string RSS XML
     */
    public function generate($format = 'itunes')
    {
        if ($format === 'itunes') {
            return $this->generateITunesFeed();
        } else {
            return $this->generateClassicRssFeed();
        }
    }
    
    /**
     * Generate iTunes-compatible podcast feed
     */
    private function generateITunesFeed()
    {
        $feed = new \Lukaswhite\FeedWriter\Itunes();
        $channel = $feed->addChannel();
        
        $channel->title($this->config['feed_title'])
            ->subtitle($this->config['feed_subtitle'])
            ->description($this->config['feed_description'])
            ->summary($this->config['feed_description'])
            ->newFeedUrl(rex_getUrl(rex_config::get('podcastmanager', 'rss_feed_id')))
            ->link($this->config['feed_link'])
            ->image($this->config['feed_image'])
            ->language("de")
            ->author($this->config['feed_author'], $this->config['feed_email'])
            ->owner($this->config['feed_author'], $this->config['feed_email'])
            ->explicit($this->config['feed_explicit'])
            ->copyright($this->config['feed_license'])
            ->generator('Redaxo RSS Podcast Generator /2.0')
            ->ttl(60)
            ->lastBuildDate(new \DateTime('now'));
        
        // Add categories
        $category = $channel->addCategory()->text("Education");
        $category->addCategory()->text("Self-Improvement");
        
        $category = $channel->addCategory()->text("Business");
        $category->addCategory()->text("Careers");
        
        $category = $channel->addCategory()->text("Society &amp; Culture");
        $category->addCategory()->text("Relationships");
        
        // Add episodes
        $this->addEpisodesToFeed($channel, 'itunes');
        
        return $feed;
    }
    
    /**
     * Generate classic RSS 2.0 feed with Apple Podcasts and Spotify optimization
     */
    private function generateClassicRssFeed()
    {
        $xml = '<?xml version="1.0" encoding="utf-8" ?>' . PHP_EOL;
        $xml .= '<?xml-stylesheet href="/xsl/rss.xsl" type="text/xsl"?>' . PHP_EOL;
        $xml .= '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:podcast="https://podcastindex.org/namespace/1.0" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:atom="http://www.w3.org/2005/Atom">' . PHP_EOL;
        $xml .= '<channel>' . PHP_EOL;
        $xml .= '<title>' . htmlspecialchars($this->config['feed_title']) . '</title>' . PHP_EOL;
        $xml .= '<link>' . $this->config['feed_link'] . '</link>' . PHP_EOL;
        $xml .= '<generator>REDAXO Podcast Manager</generator>' . PHP_EOL;
        $xml .= '<lastBuildDate>' . date(DateTime::RFC2822) . '</lastBuildDate>' . PHP_EOL;
        
        // iTunes metadata (required for Apple Podcasts)
        $xml .= '<itunes:author>' . htmlspecialchars($this->config['feed_author']) . '</itunes:author>' . PHP_EOL;
        $xml .= '<itunes:owner>' . PHP_EOL;
        $xml .= '<itunes:name>' . htmlspecialchars($this->config['feed_author']) . '</itunes:name>' . PHP_EOL;
        $xml .= '<itunes:email>' . htmlspecialchars($this->config['feed_email']) . '</itunes:email>' . PHP_EOL;
        $xml .= '</itunes:owner>' . PHP_EOL;
        $xml .= '<itunes:image href="' . $this->config['feed_image'] . '" />' . PHP_EOL;
        $xml .= '<itunes:explicit>' . htmlspecialchars($this->config['feed_explicit']) . '</itunes:explicit>' . PHP_EOL;
        $xml .= '<itunes:type>episodic</itunes:type>' . PHP_EOL; // Apple Podcasts 2025 requirement
        $xml .= '<itunes:category text="' . htmlspecialchars($this->config['feed_category']) . '">' . PHP_EOL;
        $xml .= '<itunes:category text="' . htmlspecialchars($this->config['feed_subcategory']) . '" />' . PHP_EOL;
        $xml .= '</itunes:category>' . PHP_EOL;
        $xml .= '<itunes:summary><![CDATA[' . $this->config['feed_description'] . ']]></itunes:summary>' . PHP_EOL;
        $xml .= '<itunes:keywords>' . htmlspecialchars($this->config['feed_keywords']) . '</itunes:keywords>' . PHP_EOL;
        $xml .= '<itunes:subtitle><![CDATA[' . $this->config['feed_subtitle'] . ']]></itunes:subtitle>' . PHP_EOL;
        
        // Standard RSS metadata
        $xml .= '<category>Music</category>' . PHP_EOL;
        $xml .= '<description><![CDATA[' . $this->config['feed_description'] . ']]></description>' . PHP_EOL;
        $xml .= '<language>' . htmlspecialchars($this->config['feed_lang']) . '</language>' . PHP_EOL;
        $xml .= '<copyright>' . htmlspecialchars($this->config['feed_license']) . '</copyright>' . PHP_EOL;
        $xml .= '<ttl>86400</ttl>' . PHP_EOL;
        
        // Atom self-link (required for validation)
        $feedUrl = $this->baseurl . rex_getUrl(rex_config::get('podcastmanager', 'rss_feed_id'));
        $xml .= '<atom:link href="' . htmlspecialchars($feedUrl) . '" rel="self" type="application/rss+xml"/>' . PHP_EOL;
        
        // Add episodes
        $xml .= $this->generateEpisodeXml();
        
        $xml .= '</channel>' . PHP_EOL;
        $xml .= '</rss>';
        
        return $xml;
    }
    
    /**
     * Add episodes to iTunes feed
     */
    private function addEpisodesToFeed($channel, $format)
    {
        $episodes = $this->getEpisodes();
        
        foreach ($episodes as $item) {
            $item = podcastmanager::prepare($item, $this->track_url);
            
            $episode_title = htmlspecialchars($item['title']);
            $episode_number = "#" . $item['number'] . " - ";
            
            if (empty($item['subtitle'])) {
                $item['subtitle'] = preg_replace("/[^a-zA-Z0-9äöüßÄÖÜ,.\?\s]/", "", html_entity_decode(strip_tags(html_entity_decode($item['description']))));
            }
            
            $episode_subtitle = strip_tags(htmlspecialchars($item['subtitle']));
            if (strlen($episode_subtitle) > 252) {
                $episode_subtitle = substr($episode_subtitle, 0, 252) . '...';
            }
            
            $episode_url = podcastmanager::getShowUrl($item, $this->baseurl);
            $file_url = $this->track_url . $item['file_url'];
            $file_duration = $item['runtime'];
            $file_description = podcastmanager::urlFeedConvert($item['description']) . " \n\n\n" . $this->config['feed_item_additions'];
            $file_date = date(DateTime::RFC2822, strtotime($item['date_rfc']));
            
            if (is_object(rex_media::get($item['audiofiles']))) {
                $file_size = rex_media::get($item['audiofiles'])->getSize();
            }
            
            $channel->addItem()
                ->title($episode_number . $episode_title)
                ->author($this->config['feed_email'] . " (" . $this->config['feed_author'] . ")")
                ->subtitle(html_entity_decode(strip_tags($episode_subtitle)))
                ->duration(gmdate("H:i:s", intval($file_duration)))
                ->summary($episode_subtitle)
                ->description(nl2br(html_entity_decode(strip_tags(htmlspecialchars_decode($episode_subtitle . PHP_EOL . $file_description)))))
                ->pubDate(new \DateTime($file_date))
                ->guid(trim($episode_url))
                ->explicit($this->config['feed_explicit'])
                ->link(trim($episode_url))
                ->image($this->config['feed_image'])
                ->addEnclosure()
                ->url(trim($file_url))
                ->length($file_size)
                ->type('audio/mpeg');
        }
    }
    
    /**
     * Generate XML for episodes (classic RSS) with Apple Podcasts and Spotify optimization
     */
    private function generateEpisodeXml()
    {
        $xml = '';
        $episodes = $this->getEpisodes();
        
        foreach ($episodes as $item) {
            $item = podcastmanager::prepare($item, $this->track_url);
            
            $episode_title = htmlspecialchars($item['title']);
            $episode_number = "#" . $item['number'] . " - ";
            
            if (empty($item['subtitle'])) {
                $item['subtitle'] = $item['description'];
            }
            
            $episode_subtitle = htmlspecialchars($item['subtitle']);
            if (strlen($episode_subtitle) > 252) {
                $episode_subtitle = substr($episode_subtitle, 0, 252) . '...';
            }
            
            $episode_url = podcastmanager::getShowUrl($item, $this->baseurl);
            $file_url = $this->track_url . $item['file_url'];
            $file_duration = $item['runtime'];
            
            // Improved description with proper HTML/link handling
            $file_description_text = podcastmanager::urlFeedConvert($item['description']) . " \n\n\n" . $this->config['feed_item_additions'];
            $file_description = "<![CDATA[" . nl2br(htmlspecialchars($file_description_text)) . "]]>";
            
            $file_date = date(DateTime::RFC2822, strtotime($item['date_rfc']));
            
            $file_size = 0;
            if (is_object(rex_media::get($item['audiofiles']))) {
                $file_size = rex_media::get($item['audiofiles'])->getSize();
            }
            
            // Format duration for iTunes (HH:MM:SS)
            $formatted_duration = $file_duration;
            if (is_numeric($file_duration)) {
                $seconds = (int)$file_duration;
                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                $secs = $seconds % 60;
                $formatted_duration = sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
            }
            
            // Get episode image if available, otherwise use podcast image
            $episode_image = $this->config['feed_image'];
            if (!empty($item['images'])) {
                $images = explode(',', $item['images']);
                if (!empty($images[0])) {
                    $episode_image = $this->baseurl . rex_url::media($images[0]);
                }
            }
            
            $xml .= '<item>' . PHP_EOL;
            $xml .= '<title>' . $episode_number . $episode_title . '</title>' . PHP_EOL;
            $xml .= '<description>' . $file_description . '</description>' . PHP_EOL;
            $xml .= '<guid isPermaLink="true">' . htmlspecialchars($episode_url) . '</guid>' . PHP_EOL;
            $xml .= '<comments>' . htmlspecialchars($episode_url) . '</comments>' . PHP_EOL;
            $xml .= '<pubDate>' . $file_date . '</pubDate>' . PHP_EOL;
            $xml .= '<dcterms:created>' . date("Y-m-d", strtotime($item['date_rfc'])) . '</dcterms:created>' . PHP_EOL;
            $xml .= '<link>' . htmlspecialchars($episode_url) . '</link>' . PHP_EOL;
            $xml .= '<enclosure url="' . htmlspecialchars($file_url) . '" length="' . $file_size . '" type="audio/mpeg" />' . PHP_EOL;
            
            // iTunes-specific tags (required for Apple Podcasts)
            $xml .= '<itunes:duration>' . htmlspecialchars($formatted_duration) . '</itunes:duration>' . PHP_EOL;
            $xml .= '<itunes:episodeType>full</itunes:episodeType>' . PHP_EOL; // Apple Podcasts 2025 requirement
            if (is_numeric($item['number'])) {
                $xml .= '<itunes:episode>' . (int)$item['number'] . '</itunes:episode>' . PHP_EOL;
            }
            $xml .= '<itunes:image href="' . htmlspecialchars($episode_image) . '"></itunes:image>' . PHP_EOL;
            $xml .= '<itunes:explicit>' . htmlspecialchars($this->config['feed_explicit']) . '</itunes:explicit>' . PHP_EOL;
            $xml .= '<itunes:summary>' . $file_description . '</itunes:summary>' . PHP_EOL;
            $xml .= '<itunes:subtitle>' . $episode_subtitle . '</itunes:subtitle>' . PHP_EOL;
            $xml .= '<itunes:author>' . htmlspecialchars($this->config['feed_author']) . '</itunes:author>' . PHP_EOL;
            $xml .= '<author>' . htmlspecialchars($this->config['feed_email']) . ' (' . htmlspecialchars($this->config['feed_owner']) . ')</author>' . PHP_EOL;
            $xml .= '<category>Music</category>' . PHP_EOL;
            $xml .= '</item>' . PHP_EOL;
        }
        
        return $xml;
    }
    
    /**
     * Get episodes from database
     */
    private function getEpisodes()
    {
        $limit = $this->config['feed_limit'];
        
        // Publication date filter: Only show episodes with publish date in the past or today
        $today = date('d.m.Y');
        $dateFilter = 'AND (
            publishdate = "" OR 
            publishdate IS NULL OR 
            STR_TO_DATE(publishdate, "%d.%m.%Y") <= STR_TO_DATE("' . $today . '", "%d.%m.%Y")
        )';
        
        $sql = 'SELECT * FROM ' . rex::getTable('podcastmanager') . ' 
                WHERE (`status` = 1) 
                ' . $dateFilter . '
                ORDER BY STR_TO_DATE(publishdate, "%d.%m.%Y") DESC ' . $limit;
        
        return rex_sql::factory()->getArray($sql);
    }
}
