<?php
/**
 * PodcastOutput Class
 * 
 * Handles all output rendering for podcast episodes
 * This class centralizes all output logic from modules and templates
 * to make podcastmanager a standalone addon
 */
class PodcastOutput
{
    /**
     * Configuration for output rendering
     */
    private $config = [];
    
    /**
     * Base URL for the current domain
     */
    private $baseurl = '';
    
    /**
     * Current episode data (for detail view)
     */
    private $currentEpisode = null;
    
    /**
     * Constructor
     * 
     * @param array $config Configuration array
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->baseurl = $this->getBaseUrl();
    }
    
    /**
     * Get default configuration
     * 
     * @return array
     */
    private function getDefaultConfig()
    {
        return [
            'mode' => 'overview',           // 'start', 'overview', 'detail'
            'show_teaser' => true,          // Show description in overview
            'limit' => '',                  // SQL limit
            'show_audio' => true,           // Show audio player
            'width' => 12,                  // Column width (Bootstrap)
            'detail_id' => rex_config::get('podcastmanager', 'detail_id'),
            'order' => 'DESC',
            'category' => '',               // Filter by category ID
            'seo_enabled' => true,          // Enable SEO tags in detail view
        ];
    }
    
    /**
     * Get base URL (with yrewrite support)
     * 
     * @return string
     */
    private function getBaseUrl()
    {
        if (!rex_addon::get('yrewrite')->isAvailable()) {
            $baseurl = rex::getServer();
        } else {
            $baseurl = rex_yrewrite::getCurrentDomain()->getUrl();
        }
        return rtrim($baseurl, "/");
    }
    
    /**
     * Set configuration
     * 
     * @param array $config
     * @return self
     */
    public function setConfig($config)
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }
    
    /**
     * Render complete output based on configuration
     * 
     * @return string HTML output
     */
    public function render()
    {
        $mode = $this->config['mode'];
        
        switch ($mode) {
            case 'detail':
                return $this->renderDetail();
            case 'start':
                return $this->renderStart();
            case 'overview':
            default:
                return $this->renderOverview();
        }
    }
    
    /**
     * Get episodes from database
     * 
     * @param bool $single Get only one episode (for detail view)
     * @param string $category Filter by category ID (optional)
     * @return array
     */
    private function getEpisodes($single = false, $category = '')
    {
        $limit = '';
        if ($single) {
            $limit = 'LIMIT 1';
        } elseif ($this->config['limit'] !== '') {
            $limit = 'LIMIT ' . (int)$this->config['limit'];
        }
        
        $condition = '';
        $episode_id = (int)rex_get("id");
        
        if ($single && $episode_id > 0) {
            $condition = 'AND `id`=' . $episode_id;
        }
        
        // Category filter
        if (!empty($category)) {
            $condition .= ' AND FIND_IN_SET(' . (int)$category . ', `podcastmanager_category_id`)';
        }
        
        // Publication date filter: Only show episodes with publish date in the past or today
        $today = date('d.m.Y');
        $condition .= ' AND (
            `publishdate` = "" OR 
            `publishdate` IS NULL OR 
            STR_TO_DATE(`publishdate`, "%d.%m.%Y") <= STR_TO_DATE("' . $today . '", "%d.%m.%Y")
        )';
        
        $sql = 'SELECT * FROM ' . rex::getTable('podcastmanager') . '
                WHERE (`status` = 1)
                ' . $condition . '
                ORDER BY STR_TO_DATE(publishdate, "%d.%m.%Y") ' . $this->config['order'] . ' ' . $limit;
        
        return rex_sql::factory()->getArray($sql);
    }
    
    /**
     * Render detail view (single episode with full player)
     * 
     * @return string HTML
     */
    private function renderDetail()
    {
        $episodes = $this->getEpisodes(true, $this->config['category']);
        
        if (empty($episodes)) {
            return $this->renderNoEpisodes();
        }
        
        $item = podcastmanager::prepare($episodes[0], $this->getTrackingUrl());
        
        return $this->renderHeader($item);
    }
    
    /**
     * Render overview (list of episodes as teasers)
     * 
     * @return string HTML
     */
    private function renderOverview()
    {
        $episodes = $this->getEpisodes(false, $this->config['category']);
        
        if (empty($episodes)) {
            return $this->renderNoEpisodes();
        }
        
        $html = '<section class="modul modul-podfeed rxid-' . rex_request('REX_SLICE_ID', 'string', '') . ' podfeed-overview">';
        $html .= '<div class="container wrapper">';
        $html .= '<div class="row modul-podfeed-list">';
        
        foreach ($episodes as $item) {
            $item = podcastmanager::prepare($item, $this->getTrackingUrl());
            $html .= $this->renderItem($item);
        }
        
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</section>';
        
        return $html;
    }
    
    /**
     * Render start view (featured episode with player + teaser list)
     * 
     * @return string HTML
     */
    private function renderStart()
    {
        $episodes = $this->getEpisodes(false, $this->config['category']);
        
        if (empty($episodes)) {
            return $this->renderNoEpisodes();
        }
        
        $html = '';
        $firstItem = true;
        
        foreach ($episodes as $episode) {
            $item = podcastmanager::prepare($episode, $this->getTrackingUrl());
            
            if ($firstItem) {
                // Show first episode as featured with player
                $html .= $this->renderHeader($item);
                $firstItem = false;
            } else {
                // Show rest as compact list
                $html .= $this->renderItem($item);
            }
        }
        
        return $html;
    }
    
    /**
     * Render single episode item (teaser)
     * 
     * @param array $item Episode data
     * @return string HTML
     */
    private function renderItem($item)
    {
        $width = (int)$this->config['width'] ?: 12;
        
        $html = '<div class="col-md-12 col-lg-' . $width . '">';
        $html .= '<div class="thumbnail">';
        $html .= '<div class="caption">';
        $html .= '<div class="col-xs-10">';
        $html .= '<span class="item-date"><strong><b>Episode ' . $item['number'] . '</b></strong></span>';
        $html .= '&nbsp;<span>' . $item['publishdate'] . '</span>';
        $html .= '</div>';
        $html .= '<div class="col-xs-10">';
        $html .= '<a href="' . $item['episode_url'] . '" title="' . htmlspecialchars($item['title']) . '">';
        $html .= '<h3>' . htmlspecialchars($item['title']) . '</h3>';
        $html .= '</a>';
        
        if ($this->config['show_teaser']) {
            $html .= '<div class="subtitle">';
            $html .= podcastmanager::trim_text($item['subtitle'] ?: $item['description'], 200, true, true);
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '<div class="col-xs-2">';
        $html .= '<a href="' . $item['episode_url'] . '" class="play"></a>';
        $html .= '</div>';
        $html .= '</div></div></div>';
        
        return $html;
    }
    
    /**
     * Render detail header (episode with player and full info)
     * 
     * @param array $item Episode data
     * @return string HTML
     */
    private function renderHeader($item)
    {
        $img = rex_url::base('index.php?rex_media_type=pod_detail_header&rex_media_file=' . rex_config::get('podcastmanager', 'feed_image'));
        
        $html = '<section class="section section-numbers-2 modul-podfeed rxid-' . rex_request('REX_SLICE_ID', 'string', '') . '">';
        $html .= '<div class="parallax pattern-image">';
        $html .= '<img alt="' . htmlspecialchars($item['title']) . '" src="' . $img . '">';
        $html .= '</div>';
        $html .= '<div class="container">';
        $html .= '<div class="text-area">';
        $html .= '<div class="title">';
        $html .= '<strong class="h5">Episode ' . $item['number'] . '</strong>';
        $html .= '<h1 class="h2">' . htmlspecialchars($item['title']) . '</h1>';
        $html .= '<strong class="h4">' . htmlspecialchars($item['subtitle']) . '</strong>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="row podfeed-player">';
        $html .= '<div class="col-sm-12">';
        $html .= $this->renderPlayer($item);
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</section>';
        
        $html .= '<section class="section podfeed-details-content">';
        $html .= '<div class="container wrapper">';
        $html .= '<div class="col-sm-12 col-md-8">';
        $html .= '<p class="text-gray">' . $item['publishdate'] . '</p>';
        $html .= '<div class="shownotes">' . html_entity_decode($item['description']) . '</div>';
        $html .= '</div>';
        $html .= '<div class="col-sm-12 col-md-4">';
        $html .= '<h4>Download</h4>';
        $html .= '<a href="' . podcastmanager::getTrackingUrl($item, rex_config::get('podcastmanager', 'detail_id'), $this->baseurl, '3', rex_request('REX_ARTICLE_ID', 'string', '')) . '" class="downloadlink">';
        $html .= 'MP3 Datei direkt herunterladen (' . rex_formatter::bytes($item['filesize']) . ')</a>';
        $html .= '<h4>Link zur Episode</h4>';
        $html .= '<a href="' . $item['episode_url'] . '" class="showlink">';
        $html .= 'Details und Shownotes zu Episode ' . $item['number'] . '</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</section>';
        
        return $html;
    }
    
    /**
     * Render audio player
     * 
     * @param array $item Episode data
     * @return string HTML
     */
    private function renderPlayer($item)
    {
        if (!$this->config['show_audio']) {
            return '';
        }
        
        // Check if vidstack addon is available (preferred)
        if (rex_addon::exists('vidstack') && rex_addon::get('vidstack')->isAvailable()) {
            return $this->renderVidstackPlayer($item);
        }
        
        // Fallback to plyr/video addon
        if (class_exists('rex_video')) {
            return $this->renderPlyrPlayer($item);
        }
        
        // Final fallback: HTML5 audio element
        return $this->renderHtml5Player($item);
    }
    
    /**
     * Render player using vidstack addon
     * 
     * @param array $item Episode data
     * @return string HTML
     */
    private function renderVidstackPlayer($item)
    {
        try {
            $video = new \FriendsOfRedaxo\VidStack\Video($item['audiofiles'], $item['title']);
            
            // Set audio-specific attributes
            $video->setAttributes([
                'controls' => true,
                'preload' => 'metadata',
                'class' => 'podcast-audio-player'
            ]);
            
            // Add accessibility content
            if (!empty($item['description'])) {
                $description = strip_tags($item['description']);
                $description = substr($description, 0, 200);
                $video->setA11yContent($description, $item['episode_url']);
            }
            
            // Add poster image if available
            if (!empty($item['images'])) {
                $images = explode(',', $item['images']);
                if (!empty($images[0])) {
                    $posterUrl = rex_url::base(rex_url::media($images[0]));
                    $video->setPoster($posterUrl, $item['title']);
                }
            }
            
            return '<section class="player">' . $video->generate() . '</section>';
        } catch (Exception $e) {
            // Fallback to HTML5 if vidstack fails
            return $this->renderHtml5Player($item);
        }
    }
    
    /**
     * Render player using plyr/video addon (legacy)
     * 
     * @param array $item Episode data
     * @return string HTML
     */
    private function renderPlyrPlayer($item)
    {
        $plyr = new rex_video();
        $autoplayStandard = rex_config::get('video', 'autoplay_plyr');
        $hideControls = rex_config::get('video', 'controls_plyr');
        
        $link = $plyr->getVideoType($item['audiofiles']);
        
        if ($plyr->checkAudio($item['audiofiles']) !== false) {
            $html = '<section class="player">';
            $html .= '<audio preload="metadata" class="rex_video" ' . $autoplayStandard . '>';
            $html .= '<source src="' . $this->baseurl . $link . '" type="audio/mpeg">';
            $html .= '</audio>';
            $html .= '</section>';
            
            return $html;
        }
        
        return '';
    }
    
    /**
     * Render HTML5 audio player (fallback)
     * 
     * @param array $item Episode data
     * @return string HTML
     */
    private function renderHtml5Player($item)
    {
        if (empty($item['audiofiles'])) {
            return '';
        }
        
        $audioUrl = $this->baseurl . rex_url::media() . $item['audiofiles'];
        
        $html = '<section class="player">';
        $html .= '<audio controls preload="metadata" class="podcast-audio-player">';
        $html .= '<source src="' . htmlspecialchars($audioUrl) . '" type="audio/mpeg">';
        $html .= 'Your browser does not support the audio element.';
        $html .= '</audio>';
        $html .= '</section>';
        
        return $html;
    }
    
    /**
     * Render "no episodes" message
     * 
     * @return string HTML
     */
    private function renderNoEpisodes()
    {
        return '<div class="alert alert-info">';
        $html .= '<strong>Oh!</strong> Leider gibts gerade keine Podcast-Folgen.';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get tracking URL for statistics
     * 
     * @return string Base URL with tracking prefix if enabled
     */
    private function getTrackingUrl()
    {
        $url = $this->baseurl;
        
        if (rex_config::get('podcastmanager', 'stats_rss_active') != 'active') {
            return '';
        }
        
        $prefix = rex_config::get('podcastmanager', 'stats_prefix');
        $parsedUrl = parse_url($this->baseurl);
        $track_base = $parsedUrl['host'] ?? '';
        
        return $prefix . '/' . $track_base;
    }
    
    /**
     * Get complete HTML with required scripts and styles
     * 
     * @return string Complete HTML with assets
     */
    public function renderWithAssets()
    {
        $html = '';
        
        if ($this->config['show_audio']) {
            $html .= '<link rel="stylesheet" href="' . rex_url::base('assets/addons/video/Plyr/plyr.css') . '">';
            $html .= '<script type="text/javascript" src="' . rex_url::base('assets/addons/video/Plyr/plyr.min.js') . '"></script>';
            $html .= '<script type="text/javascript" src="' . rex_url::base('assets/addons/video/js/plyr_video.js') . '"></script>';
        }
        
        $html .= $this->render();
        
        return $html;
    }
}
