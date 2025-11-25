<?php
/**
 * Crawl Configuration
 * 
 * Centralized configuration management for crawl operations
 * Reads from environment variables with sensible defaults
 */

class CrawlConfig
{
    /**
     * Get maximum number of URLs to crawl
     * 
     * @return int
     */
    public static function getMaxUrls(): int
    {
        $value = getenv('CRAWL_MAX_URLS');
        if ($value !== false && is_numeric($value)) {
            return (int) $value;
        }
        return 100; // Default
    }
    
    /**
     * Get maximum crawl depth
     * 
     * @return int
     */
    public static function getMaxDepth(): int
    {
        $value = getenv('CRAWL_MAX_DEPTH');
        if ($value !== false && is_numeric($value)) {
            return (int) $value;
        }
        return 5; // Default
    }
    
    /**
     * Get maximum execution time in seconds
     * 
     * @return int
     */
    public static function getMaxExecutionTime(): int
    {
        $value = getenv('CRAWL_MAX_EXECUTION_TIME');
        if ($value !== false && is_numeric($value)) {
            return (int) $value;
        }
        return 60; // Default: 1 minute
    }
    
    /**
     * Get display item limit for views
     * 
     * @return int
     */
    public static function getDisplayItemLimit(): int
    {
        $value = getenv('CRAWL_DISPLAY_ITEM_LIMIT');
        if ($value !== false && is_numeric($value)) {
            return (int) $value;
        }
        return 500; // Default
    }
    
    /**
     * Get items per page for pagination
     * 
     * @return int
     */
    public static function getItemsPerPage(): int
    {
        $value = getenv('CRAWL_ITEMS_PER_PAGE');
        if ($value !== false && is_numeric($value)) {
            return (int) $value;
        }
        return 100; // Default
    }
    
    /**
     * Get base delay in microseconds
     * 
     * @return int
     */
    public static function getBaseDelay(): int
    {
        $value = getenv('CRAWL_BASE_DELAY');
        if ($value !== false && is_numeric($value)) {
            return (int) $value;
        }
        return 100000; // Default: 100ms
    }
    
    /**
     * Get maximum time per page in seconds
     * If a page takes longer than this, it will be skipped
     * 
     * @return int
     */
    public static function getMaxTimePerPage(): int
    {
        $value = getenv('CRAWL_MAX_TIME_PER_PAGE');
        if ($value !== false && is_numeric($value)) {
            return (int) $value;
        }
        return 30; // Default: 30 seconds per page
    }
}

