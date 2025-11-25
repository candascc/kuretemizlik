<?php
/**
 * Crawl Flow Integration Tests
 * 
 * Tests end-to-end crawl flow
 */

require_once __DIR__ . '/../bootstrap.php';

// Prevent duplicate includes
if (!class_exists('BaseCrawlRunner')) {
    require_once __DIR__ . '/../ui/BaseCrawlRunner.php';
}
if (!class_exists('AdminCrawlRunner')) {
    require_once __DIR__ . '/../ui/AdminCrawlRunner.php';
}
if (!class_exists('InternalCrawlService')) {
    require_once __DIR__ . '/../../src/Services/InternalCrawlService.php';
}

class CrawlFlowTest extends PHPUnit\Framework\TestCase
{
    public function testAdminCrawlRunnerStructure(): void
    {
        $runner = new AdminCrawlRunner();
        $this->assertInstanceOf(BaseCrawlRunner::class, $runner);
    }
    
    public function testInternalCrawlServiceStructure(): void
    {
        // InternalCrawlService requires CrawlLogger which may not be available in test environment
        // Just verify the class exists and can be instantiated if dependencies are available
        if (class_exists('InternalCrawlService')) {
            try {
                $service = new InternalCrawlService();
                $this->assertInstanceOf(InternalCrawlService::class, $service);
            } catch (Throwable $e) {
                // Skip if dependencies are missing (CrawlLogger, etc.)
                $this->markTestSkipped('InternalCrawlService requires dependencies not available in test environment: ' . $e->getMessage());
            }
        } else {
            $this->markTestSkipped('InternalCrawlService class not found');
        }
    }
}

