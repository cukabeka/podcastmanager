<?php
/**
 * PodcastStats Class
 * 
 * Comprehensive podcast download/stream tracking
 * IAB-compliant statistics for monetization
 * GDPR-compliant with IP anonymization
 */
class PodcastStats
{
    /**
     * Track a podcast download/stream
     * 
     * @param array $episode Episode data
     * @param string $downloadType Type: 'stream', 'download', 'rss', 'embed'
     * @param array $options Additional tracking options
     * @return bool Success
     */
    public static function track($episode, $downloadType = 'stream', $options = [])
    {
        // Check if tracking is enabled
        if (!rex_config::get('podcastmanager', 'tracking_enabled')) {
            return false;
        }
        
        // Get tracking data
        $trackingData = self::getTrackingData($episode, $downloadType, $options);
        
        // Filter bots if enabled
        if (rex_config::get('podcastmanager', 'tracking_bot_filter') && $trackingData['is_bot']) {
            return false; // Don't track bots
        }
        
        // Insert into database
        try {
            $sql = rex_sql::factory();
            $sql->setTable(rex::getTable('podcastmanager_stats'));
            
            foreach ($trackingData as $key => $value) {
                $sql->setValue($key, $value);
            }
            
            $sql->insert();
            
            return true;
        } catch (Exception $e) {
            // Log error but don't break the application
            rex_logger::logException($e);
            return false;
        }
    }
    
    /**
     * Collect tracking data
     * 
     * @param array $episode Episode data
     * @param string $downloadType Download type
     * @param array $options Additional options
     * @return array Tracking data
     */
    private static function getTrackingData($episode, $downloadType, $options = [])
    {
        $data = [
            'episode_id' => (int)$episode['id'],
            'episode_number' => $episode['number'] ?? '',
            'download_type' => $downloadType,
            'timestamp' => time(),
            'date' => date('Y-m-d'),
            'createdate' => date('Y-m-d H:i:s'),
        ];
        
        // Session ID for deduplication
        $data['session_id'] = self::getSessionId();
        
        // IP Address (anonymized if configured)
        if (rex_config::get('podcastmanager', 'tracking_ip_anonymize')) {
            $data['ip_hash'] = self::getAnonymizedIpHash();
        } else {
            $data['ip_hash'] = self::getIpHash();
        }
        
        // User Agent
        if (rex_config::get('podcastmanager', 'tracking_user_agent')) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $data['user_agent'] = substr($userAgent, 0, 500);
            
            // Detect platform and app
            $platformData = self::detectPlatform($userAgent);
            $data['platform'] = $platformData['platform'];
            $data['app_name'] = $platformData['app'];
            
            // Bot detection
            $data['is_bot'] = self::isBot($userAgent);
        }
        
        // Referrer
        if (rex_config::get('podcastmanager', 'tracking_referrer')) {
            $data['referrer'] = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500);
        }
        
        // Bytes sent (from options)
        if (isset($options['bytes_sent'])) {
            $data['bytes_sent'] = (int)$options['bytes_sent'];
        }
        
        // Duration (from options)
        if (isset($options['duration_seconds'])) {
            $data['duration_seconds'] = (int)$options['duration_seconds'];
        }
        
        // Completed (from options)
        if (isset($options['completed'])) {
            $data['completed'] = (bool)$options['completed'] ? 1 : 0;
        }
        
        // Country (from options or IP geolocation if available)
        if (isset($options['country'])) {
            $data['country'] = substr($options['country'], 0, 2);
        }
        
        return $data;
    }
    
    /**
     * Get or create session ID
     */
    private static function getSessionId()
    {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['podcast_session_id'])) {
            return $_SESSION['podcast_session_id'];
        }
        
        // Generate session ID from IP + User Agent + Date
        $sessionString = ($_SERVER['REMOTE_ADDR'] ?? '') . 
                        ($_SERVER['HTTP_USER_AGENT'] ?? '') . 
                        date('Y-m-d');
        
        return hash('sha256', $sessionString);
    }
    
    /**
     * Get anonymized IP hash (GDPR-compliant)
     */
    private static function getAnonymizedIpHash()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Anonymize IP (remove last octet for IPv4, last 80 bits for IPv6)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            $ip = implode('.', $parts);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $parts = array_slice($parts, 0, 4);
            $ip = implode(':', $parts) . '::';
        }
        
        return hash('sha256', $ip);
    }
    
    /**
     * Get IP hash (full IP)
     */
    private static function getIpHash()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return hash('sha256', $ip);
    }
    
    /**
     * Detect platform and app from User Agent
     */
    private static function detectPlatform($userAgent)
    {
        $platform = 'unknown';
        $app = null;
        
        // Podcast apps
        $apps = [
            'Apple Podcasts' => '/Apple.*Podcasts|iTunes/',
            'Spotify' => '/Spotify/',
            'Overcast' => '/Overcast/',
            'Pocket Casts' => '/Pocket Casts/',
            'Castro' => '/Castro/',
            'Downcast' => '/Downcast/',
            'Podcast Addict' => '/Podcast Addict/',
            'Google Podcasts' => '/Google.*Podcasts/',
            'Stitcher' => '/Stitcher/',
            'TuneIn' => '/TuneIn/',
        ];
        
        foreach ($apps as $appName => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                $app = $appName;
                break;
            }
        }
        
        // Platform detection
        if (preg_match('/iPhone|iPad|iPod/', $userAgent)) {
            $platform = 'iOS';
        } elseif (preg_match('/Android/', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/Windows/', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS X/', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/Linux/', $userAgent)) {
            $platform = 'Linux';
        }
        
        return [
            'platform' => $platform,
            'app' => $app,
        ];
    }
    
    /**
     * Check if User Agent is a bot
     */
    private static function isBot($userAgent)
    {
        $botPatterns = [
            'bot', 'crawl', 'spider', 'slurp', 'scraper', 
            'feed', 'validator', 'monitor', 'check',
        ];
        
        $userAgentLower = strtolower($userAgent);
        
        foreach ($botPatterns as $pattern) {
            if (strpos($userAgentLower, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get statistics for an episode
     * 
     * @param int $episodeId Episode ID
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Statistics
     */
    public static function getEpisodeStats($episodeId, $startDate = null, $endDate = null)
    {
        $sql = rex_sql::factory();
        
        $where = 'WHERE episode_id = ' . (int)$episodeId . ' AND is_bot = 0';
        
        if ($startDate) {
            $where .= ' AND date >= ' . $sql->escape($startDate);
        }
        if ($endDate) {
            $where .= ' AND date <= ' . $sql->escape($endDate);
        }
        
        // Total downloads
        $sql->setQuery('SELECT COUNT(*) as total FROM ' . rex::getTable('podcastmanager_stats') . ' ' . $where);
        $total = $sql->getValue('total');
        
        // Unique listeners (by session_id)
        $sql->setQuery('SELECT COUNT(DISTINCT session_id) as unique_listeners FROM ' . rex::getTable('podcastmanager_stats') . ' ' . $where);
        $uniqueListeners = $sql->getValue('unique_listeners');
        
        // By platform
        $sql->setQuery('SELECT platform, COUNT(*) as count FROM ' . rex::getTable('podcastmanager_stats') . ' ' . $where . ' GROUP BY platform ORDER BY count DESC');
        $platforms = $sql->getArray();
        
        // By app
        $sql->setQuery('SELECT app_name, COUNT(*) as count FROM ' . rex::getTable('podcastmanager_stats') . ' ' . $where . ' AND app_name IS NOT NULL GROUP BY app_name ORDER BY count DESC');
        $apps = $sql->getArray();
        
        // By day
        $sql->setQuery('SELECT date, COUNT(*) as count FROM ' . rex::getTable('podcastmanager_stats') . ' ' . $where . ' GROUP BY date ORDER BY date ASC');
        $byDay = $sql->getArray();
        
        return [
            'total_downloads' => $total,
            'unique_listeners' => $uniqueListeners,
            'platforms' => $platforms,
            'apps' => $apps,
            'by_day' => $byDay,
        ];
    }
    
    /**
     * Get overall podcast statistics
     * 
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Statistics
     */
    public static function getOverallStats($startDate = null, $endDate = null)
    {
        $sql = rex_sql::factory();
        
        $where = 'WHERE is_bot = 0';
        
        if ($startDate) {
            $where .= ' AND date >= ' . $sql->escape($startDate);
        }
        if ($endDate) {
            $where .= ' AND date <= ' . $sql->escape($endDate);
        }
        
        // Total downloads
        $sql->setQuery('SELECT COUNT(*) as total FROM ' . rex::getTable('podcastmanager_stats') . ' ' . $where);
        $total = $sql->getValue('total');
        
        // Unique listeners
        $sql->setQuery('SELECT COUNT(DISTINCT session_id) as unique_listeners FROM ' . rex::getTable('podcastmanager_stats') . ' ' . $where);
        $uniqueListeners = $sql->getValue('unique_listeners');
        
        // Top episodes
        $sql->setQuery('SELECT episode_id, episode_number, COUNT(*) as count FROM ' . rex::getTable('podcastmanager_stats') . ' ' . $where . ' GROUP BY episode_id ORDER BY count DESC LIMIT 10');
        $topEpisodes = $sql->getArray();
        
        // Growth (last 30 days vs previous 30 days)
        $last30 = date('Y-m-d', strtotime('-30 days'));
        $previous30 = date('Y-m-d', strtotime('-60 days'));
        
        $sql->setQuery('SELECT COUNT(*) as count FROM ' . rex::getTable('podcastmanager_stats') . ' WHERE is_bot = 0 AND date >= ' . $sql->escape($last30));
        $last30Count = $sql->getValue('count');
        
        $sql->setQuery('SELECT COUNT(*) as count FROM ' . rex::getTable('podcastmanager_stats') . ' WHERE is_bot = 0 AND date >= ' . $sql->escape($previous30) . ' AND date < ' . $sql->escape($last30));
        $previous30Count = $sql->getValue('count');
        
        $growth = 0;
        if ($previous30Count > 0) {
            $growth = (($last30Count - $previous30Count) / $previous30Count) * 100;
        }
        
        return [
            'total_downloads' => $total,
            'unique_listeners' => $uniqueListeners,
            'top_episodes' => $topEpisodes,
            'last_30_days' => $last30Count,
            'previous_30_days' => $previous30Count,
            'growth_percentage' => round($growth, 2),
        ];
    }
    
    /**
     * Export statistics for IAB compliance (CSV format)
     * 
     * @param int $episodeId Episode ID (optional, null for all)
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return string CSV content
     */
    public static function exportIABCompliant($episodeId = null, $startDate = null, $endDate = null)
    {
        $sql = rex_sql::factory();
        
        $where = 'WHERE is_bot = 0';
        
        if ($episodeId) {
            $where .= ' AND episode_id = ' . (int)$episodeId;
        }
        if ($startDate) {
            $where .= ' AND date >= ' . $sql->escape($startDate);
        }
        if ($endDate) {
            $where .= ' AND date <= ' . $sql->escape($endDate);
        }
        
        $sql->setQuery('SELECT * FROM ' . rex::getTable('podcastmanager_stats') . ' ' . $where . ' ORDER BY date DESC, timestamp DESC');
        
        $csv = "Date,Episode ID,Episode Number,Platform,App,Download Type,Duration Seconds,Completed\n";
        
        while ($sql->hasNext()) {
            $row = [
                $sql->getValue('date'),
                $sql->getValue('episode_id'),
                $sql->getValue('episode_number'),
                $sql->getValue('platform'),
                $sql->getValue('app_name'),
                $sql->getValue('download_type'),
                $sql->getValue('duration_seconds'),
                $sql->getValue('completed') ? 'Yes' : 'No',
            ];
            
            $csv .= implode(',', array_map(function($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, $row)) . "\n";
            
            $sql->next();
        }
        
        return $csv;
    }
}
