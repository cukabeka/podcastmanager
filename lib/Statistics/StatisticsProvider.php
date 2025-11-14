<?php

namespace FriendsOfRedaxo\Podcastmanager\Statistics;

/**
 * Abstract Statistics Provider
 * 
 * Base class for all statistics data sources.
 * Supports multiple analysis tools: Webalizer, AWStats, Analog, etc.
 */
abstract class StatisticsProvider {
    
    protected $basePath;
    protected $domain;
    protected $config = [];
    
    /**
     * Constructor
     * 
     * @param string $basePath Base path where statistics files are stored
     * @param string $domain Domain name for statistics
     * @param array $config Provider-specific configuration
     */
    public function __construct($basePath, $domain, array $config = []) {
        $this->basePath = rtrim($basePath, '/');
        $this->domain = $domain;
        $this->config = $config;
        
        if (!$this->validatePath()) {
            throw new \Exception("Statistics path does not exist or is not readable: {$basePath}");
        }
    }
    
    /**
     * Validate that the base path exists and is readable
     */
    protected function validatePath() {
        return is_dir($this->basePath) && is_readable($this->basePath);
    }
    
    /**
     * Get statistics for a specific month/year
     * 
     * @param string $month Month (MM format)
     * @param string $year Year (YYYY format)
     * @return StatisticsData Statistics data object
     */
    abstract public function getStatistics($month, $year);
    
    /**
     * Get available months for statistics
     * 
     * @return array Array of available months ['2024-01', '2024-02', ...]
     */
    abstract public function getAvailableMonths();
    
    /**
     * Get provider name for display
     * 
     * @return string Provider name
     */
    abstract public function getProviderName();
    
    /**
     * Check if provider is available/configured
     * 
     * @return bool True if provider can be used
     */
    public function isAvailable() {
        return $this->validatePath();
    }
}
