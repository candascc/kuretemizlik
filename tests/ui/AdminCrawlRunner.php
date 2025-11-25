<?php
/**
 * Admin Crawl Runner
 * 
 * PATH_CRAWL_ADMIN_V1: Recursive link-based crawl for normal admin role (test_admin)
 * 
 * Can be used by both CLI script and web controller
 */

require_once __DIR__ . '/BaseCrawlRunner.php';
require_once __DIR__ . '/../../src/Config/CrawlConfig.php';

class AdminCrawlRunner extends BaseCrawlRunner
{
    /**
     * Special seed URLs (important endpoints that may not be linked from dashboard)
     * 
     * These URLs are added to the seed list in addition to /app/ dashboard
     */
    private static array $specialSeedUrls = [
        '/app/performance/metrics',
        '/app/health',
    ];
    
    /**
     * Get special seed URLs
     * 
     * @return array
     */
    protected function getSpecialSeedUrls(): array
    {
        return self::$specialSeedUrls;
    }
    
    /**
     * Get maximum number of URLs to crawl
     * 
     * @return int
     */
    protected function getMaxUrls(): int
    {
        return CrawlConfig::getMaxUrls();
    }
    
    /**
     * Get maximum crawl depth
     * 
     * @return int
     */
    protected function getMaxDepth(): int
    {
        return CrawlConfig::getMaxDepth();
    }
    
    /**
     * Get maximum execution time in seconds
     * 
     * @return int
     */
    protected function getMaxExecutionTime(): int
    {
        return CrawlConfig::getMaxExecutionTime();
    }
    
    /**
     * Get default log file prefix
     * 
     * @return string
     */
    protected function getLogFilePrefix(): string
    {
        return 'crawl_admin';
    }
    
    /**
     * Get special seed URLs (for reference) - static accessor
     * 
     * PATH_CRAWL_ADMIN_V1: Returns special seed URLs
     * 
     * @return array
     */
    public static function getSpecialSeedUrlsStatic(): array
    {
        return self::$specialSeedUrls;
    }
}
