<?php

namespace FriendsOfRedaxo\Podcastmanager\Statistics;

/**
 * Statistics Manager
 * 
 * Central factory and manager for statistics providers.
 * Handles provider selection, configuration, and fallbacks.
 */
class StatisticsManager {
    
    private static $providers = [];
    private static $activeProvider = null;
    
    /**
     * Initialize statistics provider
     * 
     * @param string $type Provider type ('webalizer', 'awstats')
     * @param string $path Path to statistics files
     * @param string $domain Domain name
     * @param array $config Additional configuration
     * @return StatisticsProvider Provider instance
     */
    public static function createProvider($type, $path, $domain, array $config = []) {
        $type = strtolower($type);
        
        switch ($type) {
            case 'webalizer':
                return new WebalizerProvider($path, $domain, $config);
            
            case 'awstats':
                return new AwstatsProvider($path, $domain, $config);
            
            default:
                throw new \Exception("Unknown statistics provider type: {$type}");
        }
    }
    
    /**
     * Auto-detect available provider
     * 
     * Tries providers in order until one is found to be available
     * 
     * @param string $path Path to statistics files
     * @param string $domain Domain name
     * @param array $providerOrder Provider order to try ['webalizer', 'awstats']
     * @return StatisticsProvider|null Available provider or null
     */
    public static function autoDetectProvider($path, $domain, $providerOrder = ['webalizer', 'awstats']) {
        foreach ($providerOrder as $type) {
            try {
                $provider = self::createProvider($type, $path, $domain);
                if ($provider->isAvailable()) {
                    return $provider;
                }
            } catch (\Exception $e) {
                // Provider not available, try next
                continue;
            }
        }
        
        return null;
    }
    
    /**
     * Get available providers
     * 
     * @return array ['type' => 'Provider Name', ...]
     */
    public static function getAvailableProviders() {
        return [
            'webalizer' => 'Webalizer',
            'awstats' => 'AWStats',
        ];
    }
    
    /**
     * Get provider information
     * 
     * @param string $type Provider type
     * @return array Provider info with description and requirements
     */
    public static function getProviderInfo($type) {
        $info = [
            'webalizer' => [
                'name' => 'Webalizer',
                'description' => 'Popular web server log analyzer. Generates HTML reports.',
                'file_format' => 'usage_YYYYMM.html',
                'requirements' => 'Webalizer installed on server',
                'pros' => ['HTML reports', 'Wide hosting support', 'Low resource usage'],
                'cons' => ['Deprecated in some environments'],
            ],
            'awstats' => [
                'name' => 'AWStats',
                'description' => 'Advanced log analyzer. Can generate text or HTML reports.',
                'file_format' => 'awstats.YYYYMM.domain.txt',
                'requirements' => 'AWStats installed on server',
                'pros' => ['Very detailed', 'Still actively maintained', 'Text-based logs'],
                'cons' => ['Larger file sizes', 'More complex setup'],
            ],
        ];
        
        return $info[$type] ?? [];
    }
}
