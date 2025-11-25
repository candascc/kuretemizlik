<?php
/**
 * Rate Limiting Stress Test
 * Tests rate limiting with 1000+ and 10000+ requests
 */

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

class RateLimitingStressTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        
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
     * Test rate limiting with 1000 requests
     */
    public function testRateLimitingWith1000Requests(): void
    {
        if (!class_exists('ApiRateLimiter')) {
            $this->markTestSkipped('ApiRateLimiter class not available');
            return;
        }

        $key = 'stress_test_1000_' . uniqid();
        $limit = 100;
        $window = 600; // 10 minutes

        $passed = 0;
        $blocked = 0;
        $startTime = microtime(true);

        for ($i = 0; $i < 1000; $i++) {
            if (ApiRateLimiter::check($key, $limit, $window)) {
                ApiRateLimiter::record($key, $limit, $window);
                $passed++;
            } else {
                $blocked++;
            }
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertEquals(100, $passed, 'Should allow exactly 100 requests');
        $this->assertEquals(900, $blocked, 'Should block 900 requests');
        $this->assertLessThan(5000, $duration, 'Should complete in less than 5 seconds');

        ApiRateLimiter::reset($key);
    }

    /**
     * Test rate limiting with 10000 requests
     */
    public function testRateLimitingWith10000Requests(): void
    {
        if (!class_exists('ApiRateLimiter')) {
            $this->markTestSkipped('ApiRateLimiter class not available');
            return;
        }

        $key = 'stress_test_10000_' . uniqid();
        $limit = 1000;
        $window = 3600; // 1 hour

        $passed = 0;
        $blocked = 0;
        $startTime = microtime(true);

        for ($i = 0; $i < 10000; $i++) {
            if (ApiRateLimiter::check($key, $limit, $window)) {
                ApiRateLimiter::record($key, $limit, $window);
                $passed++;
            } else {
                $blocked++;
            }
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertEquals(1000, $passed, 'Should allow exactly 1000 requests');
        $this->assertEquals(9000, $blocked, 'Should block 9000 requests');
        $this->assertLessThan(30000, $duration, 'Should complete in less than 30 seconds');

        ApiRateLimiter::reset($key);
    }

    /**
     * Test concurrent rate limiting (simulated)
     */
    public function testConcurrentRateLimiting(): void
    {
        if (!class_exists('ApiRateLimiter')) {
            $this->markTestSkipped('ApiRateLimiter class not available');
            return;
        }

        $keys = [];
        for ($i = 0; $i < 10; $i++) {
            $keys[] = 'concurrent_test_' . $i . '_' . uniqid();
        }

        $limit = 10;
        $window = 60;

        $totalPassed = 0;
        $totalBlocked = 0;

        foreach ($keys as $key) {
            for ($i = 0; $i < 20; $i++) {
                if (ApiRateLimiter::check($key, $limit, $window)) {
                    ApiRateLimiter::record($key, $limit, $window);
                    $totalPassed++;
                } else {
                    $totalBlocked++;
                }
            }
        }

        $this->assertEquals(100, $totalPassed, 'Should allow 10 requests per key (10 keys * 10)');
        $this->assertEquals(100, $totalBlocked, 'Should block 10 requests per key (10 keys * 10)');

        foreach ($keys as $key) {
            ApiRateLimiter::reset($key);
        }
    }
}

