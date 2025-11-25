<?php
/**
 * Sysadmin Crawl Runner
 * 
 * PATH_CRAWL_SYSADMIN_WEB_V1: Programmatic API for sysadmin crawl
 * PATH_CRAWL_SYSADMIN_DEEPCLICK_V1: Recursive link-based crawl
 * 
 * Can be used by both CLI script and web controller
 */

require_once __DIR__ . '/BaseCrawlRunner.php';
require_once __DIR__ . '/../../src/Config/CrawlConfig.php';

class SysadminCrawlRunner extends BaseCrawlRunner
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
        return 'crawl_sysadmin';
    }
    
    /**
     * Get special seed URLs (for reference)
     * 
     * PATH_CRAWL_SYSADMIN_DEEPCLICK_V1: Returns special seed URLs
     * 
     * @return array
     */
    public static function getSpecialSeedUrls(): array
    {
        return self::$specialSeedUrls;
    }
}
