<?php
/**
 * API Load Test
 * Tests concurrent API requests
 */

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;
use Tests\Support\FactoryRegistry;

class ApiLoadTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        FactoryRegistry::setDatabase($this->db);
        
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        parent::tearDown();
    }

    /**
     * Test concurrent API requests (simulated)
     */
    public function testConcurrentApiRequests(): void
    {
        // Create test data
        $customerId = FactoryRegistry::customer()->create(['company_id' => 1]);
        $jobId = FactoryRegistry::job()->create(['customer_id' => $customerId, 'company_id' => 1]);

        $concurrentRequests = 50;
        $successful = 0;
        $failed = 0;
        $startTime = microtime(true);

        // Simulate concurrent requests
        for ($i = 0; $i < $concurrentRequests; $i++) {
            try {
                // Simulate API call - fetch job
                $result = $this->db->fetch("SELECT * FROM jobs WHERE id = ?", [$jobId]);
                if ($result) {
                    $successful++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                $failed++;
            }
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertEquals($concurrentRequests, $successful, 'All requests should succeed');
        $this->assertEquals(0, $failed, 'No requests should fail');
        $this->assertLessThan(1000, $duration, '50 concurrent requests should complete in < 1 second');
    }

    /**
     * Test API rate limiting under load
     */
    public function testApiRateLimitingUnderLoad(): void
    {
        if (!class_exists('ApiRateLimiter')) {
            $this->markTestSkipped('ApiRateLimiter class not available');
            return;
        }

        $key = 'load_test_' . uniqid();
        $limit = 100;
        $window = 60;

        $requests = 200; // More than limit
        $passed = 0;
        $blocked = 0;
        $startTime = microtime(true);

        for ($i = 0; $i < $requests; $i++) {
            if (ApiRateLimiter::check($key, $limit, $window)) {
                ApiRateLimiter::record($key, $limit, $window);
                $passed++;
            } else {
                $blocked++;
            }
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertEquals($limit, $passed, 'Should allow exactly limit requests');
        $this->assertEquals($requests - $limit, $blocked, 'Should block excess requests');
        $this->assertLessThan(500, $duration, 'Rate limiting should be fast');

        ApiRateLimiter::reset($key);
    }
}

