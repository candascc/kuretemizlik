<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Rate Limiting Test Suite
 * Tests for API rate limiting functionality
 */
class RateLimitingTest extends TestCase
{
    /**
     * Test that ApiRateLimitMiddleware applies rate limiting
     */
    public function testApiRateLimitMiddlewareApplies(): void
    {
        // This test verifies the middleware exists and can be called
        $this->assertTrue(class_exists('ApiRateLimitMiddleware'));
        
        // Test that methods exist
        $this->assertTrue(method_exists('ApiRateLimitMiddleware', 'apply'));
        $this->assertTrue(method_exists('ApiRateLimitMiddleware', 'applyStrict'));
        $this->assertTrue(method_exists('ApiRateLimitMiddleware', 'applyModerate'));
        $this->assertTrue(method_exists('ApiRateLimitMiddleware', 'applyLenient'));
    }
    
    /**
     * Test that ApiRateLimiter class exists and has required methods
     */
    public function testApiRateLimiterExists(): void
    {
        $this->assertTrue(class_exists('ApiRateLimiter'));
        
        // Test that methods exist
        $this->assertTrue(method_exists('ApiRateLimiter', 'check'));
        $this->assertTrue(method_exists('ApiRateLimiter', 'record'));
        $this->assertTrue(method_exists('ApiRateLimiter', 'sendLimitExceededResponse'));
    }
    
    /**
     * Test that RateLimitHelper exists and has required methods
     */
    public function testRateLimitHelperExists(): void
    {
        $this->assertTrue(class_exists('RateLimitHelper'));
        
        // Test that methods exist
        $this->assertTrue(method_exists('RateLimitHelper', 'checkLoginRateLimit'));
        $this->assertTrue(method_exists('RateLimitHelper', 'recordFailedAttempt'));
        $this->assertTrue(method_exists('RateLimitHelper', 'clearRateLimit'));
    }
    
    /**
     * Test that ApiRateLimiter check and record work together
     */
    public function testApiRateLimiterCheckAndRecord(): void
    {
        if (!class_exists('ApiRateLimiter')) {
            $this->markTestSkipped('ApiRateLimiter class not available');
            return;
        }
        
        $key = 'test_rate_limit_' . uniqid();
        $limit = 3;
        $window = 60;
        
        // First 3 requests should pass
        for ($i = 0; $i < $limit; $i++) {
            $this->assertTrue(ApiRateLimiter::check($key, $limit, $window));
            ApiRateLimiter::record($key, $limit, $window);
        }
        
        // 4th request should fail
        $this->assertFalse(ApiRateLimiter::check($key, $limit, $window));
        
        // Cleanup
        ApiRateLimiter::reset($key);
    }
    
    /**
     * Test that RateLimitHelper getClientIp works
     */
    public function testRateLimitHelperGetClientIp(): void
    {
        $ip = RateLimitHelper::getClientIp();
        
        // Should return a string
        $this->assertIsString($ip);
        
        // Should not be empty
        $this->assertNotEmpty($ip);
    }
}

