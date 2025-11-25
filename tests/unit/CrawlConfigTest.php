<?php
/**
 * CrawlConfig Unit Tests
 */

require_once __DIR__ . '/../../src/Config/CrawlConfig.php';

class CrawlConfigTest extends PHPUnit\Framework\TestCase
{
    public function testGetMaxUrls(): void
    {
        $value = CrawlConfig::getMaxUrls();
        $this->assertIsInt($value);
        $this->assertGreaterThan(0, $value);
    }
    
    public function testGetMaxDepth(): void
    {
        $value = CrawlConfig::getMaxDepth();
        $this->assertIsInt($value);
        $this->assertGreaterThan(0, $value);
    }
    
    public function testGetMaxExecutionTime(): void
    {
        $value = CrawlConfig::getMaxExecutionTime();
        $this->assertIsInt($value);
        $this->assertGreaterThan(0, $value);
    }
}

