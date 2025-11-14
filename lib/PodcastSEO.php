<?php
/**
 * PodcastSEO Class
 * 
 * Provides SEO enhancements for podcast episodes
 * - JSON-LD Structured Data
 * - OpenGraph Meta Tags
 * - Twitter Cards
 */
class PodcastSEO
{
    /**
     * Generate JSON-LD structured data for a podcast episode
     * 
     * @param array $episode Episode data from database
     * @param array $podcast Podcast/Channel configuration
     * @return string JSON-LD script tag
     */
    public static function generateStructuredData($episode, $podcast = [])
    {
        // Prepare episode data
        $item = podcastmanager::prepare($episode, '');
        
        // Get podcast configuration
        if (empty($podcast)) {
            $podcast = [
                'name' => rex_config::get('podcastmanager', 'feed_title'),
                'description' => rex_config::get('podcastmanager', 'feed_description'),
                'author' => rex_config::get('podcastmanager', 'feed_author'),
                'image' => rex_config::get('podcastmanager', 'feed_image'),
                'url' => rex_config::get('podcastmanager', 'feed_link'),
            ];
        }
        
        // Build structured data
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'PodcastEpisode',
            'name' => $item['title'],
            'description' => strip_tags($item['description']),
            'episodeNumber' => $item['number'],
            'url' => $item['episode_url'],
            'datePublished' => date('Y-m-d', strtotime($item['date_rfc'])),
            'associatedMedia' => [
                '@type' => 'MediaObject',
                'contentUrl' => $item['file_url'],
                'encodingFormat' => 'audio/mpeg',
            ],
            'partOfSeries' => [
                '@type' => 'PodcastSeries',
                'name' => $podcast['name'],
                'description' => $podcast['description'],
                'url' => $podcast['url'],
            ],
        ];
        
        // Add optional fields
        if (!empty($item['subtitle'])) {
            $structuredData['description'] = strip_tags($item['subtitle']);
        }
        
        if (!empty($item['runtime']) && is_numeric($item['runtime'])) {
            $structuredData['timeRequired'] = 'PT' . (int)$item['runtime'] . 'S';
        }
        
        if (!empty($item['images'])) {
            $images = explode(',', $item['images']);
            if (!empty($images[0])) {
                $structuredData['image'] = rex_url::media($images[0]);
            }
        } elseif (!empty($podcast['image'])) {
            $structuredData['image'] = $podcast['image'];
        }
        
        if (!empty($item['author'])) {
            $structuredData['author'] = [
                '@type' => 'Person',
                'name' => $item['author'],
            ];
        }
        
        // Generate script tag
        $json = json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        return '<script type="application/ld+json">' . PHP_EOL . $json . PHP_EOL . '</script>';
    }
    
    /**
     * Generate OpenGraph meta tags for a podcast episode
     * 
     * @param array $episode Episode data from database
     * @return string Meta tags HTML
     */
    public static function generateOpenGraphTags($episode)
    {
        $item = podcastmanager::prepare($episode, '');
        
        $tags = [];
        $tags[] = '<meta property="og:type" content="music.song">';
        $tags[] = '<meta property="og:title" content="' . htmlspecialchars($item['title']) . '">';
        $tags[] = '<meta property="og:url" content="' . htmlspecialchars($item['episode_url']) . '">';
        
        // Description
        $description = !empty($item['subtitle']) ? $item['subtitle'] : strip_tags($item['description']);
        $description = substr($description, 0, 200);
        $tags[] = '<meta property="og:description" content="' . htmlspecialchars($description) . '">';
        
        // Image
        if (!empty($item['images'])) {
            $images = explode(',', $item['images']);
            if (!empty($images[0])) {
                $imageUrl = rex_url::base(rex_url::media($images[0]));
                $tags[] = '<meta property="og:image" content="' . htmlspecialchars($imageUrl) . '">';
            }
        } else {
            $podcastImage = rex_config::get('podcastmanager', 'feed_image');
            if ($podcastImage) {
                $tags[] = '<meta property="og:image" content="' . htmlspecialchars($podcastImage) . '">';
            }
        }
        
        // Audio
        $tags[] = '<meta property="og:audio" content="' . htmlspecialchars($item['file_url']) . '">';
        $tags[] = '<meta property="og:audio:type" content="audio/mpeg">';
        
        // Site name
        $podcastTitle = rex_config::get('podcastmanager', 'feed_title');
        if ($podcastTitle) {
            $tags[] = '<meta property="og:site_name" content="' . htmlspecialchars($podcastTitle) . '">';
        }
        
        return implode(PHP_EOL, $tags);
    }
    
    /**
     * Generate Twitter Card meta tags for a podcast episode
     * 
     * @param array $episode Episode data from database
     * @return string Meta tags HTML
     */
    public static function generateTwitterCardTags($episode)
    {
        $item = podcastmanager::prepare($episode, '');
        
        $tags = [];
        $tags[] = '<meta name="twitter:card" content="summary_large_image">';
        $tags[] = '<meta name="twitter:title" content="' . htmlspecialchars($item['title']) . '">';
        
        // Description
        $description = !empty($item['subtitle']) ? $item['subtitle'] : strip_tags($item['description']);
        $description = substr($description, 0, 200);
        $tags[] = '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">';
        
        // Image
        if (!empty($item['images'])) {
            $images = explode(',', $item['images']);
            if (!empty($images[0])) {
                $imageUrl = rex_url::base(rex_url::media($images[0]));
                $tags[] = '<meta name="twitter:image" content="' . htmlspecialchars($imageUrl) . '">';
            }
        } else {
            $podcastImage = rex_config::get('podcastmanager', 'feed_image');
            if ($podcastImage) {
                $tags[] = '<meta name="twitter:image" content="' . htmlspecialchars($podcastImage) . '">';
            }
        }
        
        // Player card for audio
        $tags[] = '<meta name="twitter:player" content="' . htmlspecialchars($item['episode_url']) . '">';
        $tags[] = '<meta name="twitter:player:width" content="480">';
        $tags[] = '<meta name="twitter:player:height" content="80">';
        
        return implode(PHP_EOL, $tags);
    }
    
    /**
     * Generate all SEO tags for a podcast episode
     * 
     * @param array $episode Episode data from database
     * @param bool $includeStructuredData Include JSON-LD (default: true)
     * @param bool $includeOpenGraph Include OpenGraph tags (default: true)
     * @param bool $includeTwitterCard Include Twitter Card tags (default: true)
     * @return string All SEO tags HTML
     */
    public static function generateAllTags($episode, $includeStructuredData = true, $includeOpenGraph = true, $includeTwitterCard = true)
    {
        $output = [];
        
        if ($includeStructuredData) {
            $output[] = self::generateStructuredData($episode);
        }
        
        if ($includeOpenGraph) {
            $output[] = self::generateOpenGraphTags($episode);
        }
        
        if ($includeTwitterCard) {
            $output[] = self::generateTwitterCardTags($episode);
        }
        
        return implode(PHP_EOL . PHP_EOL, $output);
    }
    
    /**
     * Generate sitemap entries for all published podcast episodes
     * 
     * @return string XML sitemap entries
     */
    public static function generateSitemapEntries()
    {
        // Get all published episodes
        $today = date('d.m.Y');
        $sql = 'SELECT * FROM ' . rex::getTable('podcastmanager') . '
                WHERE (`status` = 1)
                AND (
                    `publishdate` = "" OR 
                    `publishdate` IS NULL OR 
                    STR_TO_DATE(`publishdate`, "%d.%m.%Y") <= STR_TO_DATE("' . $today . '", "%d.%m.%Y")
                )
                ORDER BY STR_TO_DATE(publishdate, "%d.%m.%Y") DESC';
        
        $episodes = rex_sql::factory()->getArray($sql);
        
        $entries = [];
        foreach ($episodes as $episode) {
            $item = podcastmanager::prepare($episode, '');
            
            // Build sitemap entry
            $entry = '<url>';
            $entry .= '<loc>' . htmlspecialchars($item['episode_url']) . '</loc>';
            
            // Last modification date
            if (!empty($item['updatedate'])) {
                $entry .= '<lastmod>' . date('Y-m-d', $item['updatedate']) . '</lastmod>';
            }
            
            // Priority and change frequency
            $entry .= '<changefreq>weekly</changefreq>';
            $entry .= '<priority>0.8</priority>';
            
            $entry .= '</url>';
            $entries[] = $entry;
        }
        
        return implode(PHP_EOL, $entries);
    }
}
