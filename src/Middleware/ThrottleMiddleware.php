<?php
/**
 * Throttle Middleware
 * Rate limiting for routes
 */
class ThrottleMiddleware
{
    private static $limit = 60; // requests per minute
    private static $window = 60; // seconds
    
    /**
     * Check if request should be throttled
     */
    public static function check(string $key, int $limit = null, int $window = null): bool
    {
        $limit = $limit ?? self::$limit;
        $window = $window ?? self::$window;
        
        $rateLimiter = new ApiRateLimiter();
        return $rateLimiter->check($key, $limit, $window);
    }
    
    /**
     * Record request
     */
    public static function record(string $key): void
    {
        $rateLimiter = new ApiRateLimiter();
        $rateLimiter->record($key);
    }
}

