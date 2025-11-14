<?php

namespace FriendsOfRedaxo\Podcastmanager\Statistics;

/**
 * Statistics Data Container
 * 
 * Holds parsed statistics data in a standardized format,
 * independent of the source tool (Webalizer, AWStats, etc.)
 */
class StatisticsData {
    
    private $month;
    private $year;
    private $provider;
    private $data = [];
    
    /**
     * Constructor
     * 
     * @param string $month Month (MM format)
     * @param string $year Year (YYYY format)
     * @param string $provider Provider name
     */
    public function __construct($month, $year, $provider) {
        $this->month = $month;
        $this->year = $year;
        $this->provider = $provider;
    }
    
    /**
     * Set a statistic value
     * 
     * @param string $key Key name
     * @param mixed $value Value
     */
    public function set($key, $value) {
        $this->data[$key] = $value;
    }
    
    /**
     * Get a statistic value
     * 
     * @param string $key Key name
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Value or default
     */
    public function get($key, $default = null) {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }
    
    /**
     * Get all data as array
     * 
     * @return array All statistics data
     */
    public function getAll() {
        return array_merge([
            'month' => $this->month,
            'year' => $this->year,
            'provider' => $this->provider,
        ], $this->data);
    }
    
    /**
     * Common statistics keys (standardized across providers)
     */
    public function setVisits($count) {
        $this->data['visits'] = (int)$count;
    }
    
    public function getVisits() {
        return $this->get('visits', 0);
    }
    
    public function setHits($count) {
        $this->data['hits'] = (int)$count;
    }
    
    public function getHits() {
        return $this->get('hits', 0);
    }
    
    public function setBandwidth($bytes) {
        $this->data['bandwidth'] = (int)$bytes;
    }
    
    public function getBandwidth() {
        return $this->get('bandwidth', 0);
    }
    
    public function setPages($count) {
        $this->data['pages'] = (int)$count;
    }
    
    public function getPages() {
        return $this->get('pages', 0);
    }
    
    public function setBots($count) {
        $this->data['bots'] = (int)$count;
    }
    
    public function getBots() {
        return $this->get('bots', 0);
    }
    
    public function setFailedRequests($count) {
        $this->data['failed_requests'] = (int)$count;
    }
    
    public function getFailedRequests() {
        return $this->get('failed_requests', 0);
    }
    
    /**
     * Format bandwidth for display
     * 
     * @param int $bytes Number of bytes
     * @return string Formatted bandwidth (e.g., "2.5 GB")
     */
    public static function formatBandwidth($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
