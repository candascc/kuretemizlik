<?php
/**
 * API Rate Limit Middleware
 * Standardized rate limiting for all API endpoints
 * 
 * Usage:
 *   ApiRateLimitMiddleware::apply('api.endpoint_name', 100, 300);
 *   // 100 requests per 300 seconds (5 minutes)
 */
class ApiRateLimitMiddleware
{
    /**
     * Apply rate limiting to an API endpoint
     * 
     * @param string $endpoint Endpoint identifier (e.g., 'api.jobs', 'api.customers')
     * @param int|null $limit Maximum requests per window (default: 100)
     * @param int|null $window Time window in seconds (default: 300 = 5 minutes)
     * @return void Exits with 429 response if rate limit exceeded
     */
    public static function apply(string $endpoint, ?int $limit = null, ?int $window = null): void
    {
        require_once __DIR__ . '/ApiRateLimiter.php';
        
        $limit = $limit ?? 100; // Default: 100 requests
        $window = $window ?? 300; // Default: 5 minutes
        
        if (!ApiRateLimiter::check($endpoint, $limit, $window)) {
            ApiRateLimiter::sendLimitExceededResponse($endpoint, $window);
        }
        
        ApiRateLimiter::record($endpoint, $limit, $window);
    }
    
    /**
     * Apply strict rate limiting (lower limits for sensitive endpoints)
     * 
     * @param string $endpoint Endpoint identifier
     * @return void
     */
    public static function applyStrict(string $endpoint): void
    {
        self::apply($endpoint, 20, 300); // 20 requests per 5 minutes
    }
    
    /**
     * Apply moderate rate limiting (default for most endpoints)
     * 
     * @param string $endpoint Endpoint identifier
     * @return void
     */
    public static function applyModerate(string $endpoint): void
    {
        self::apply($endpoint, 100, 300); // 100 requests per 5 minutes
    }
    
    /**
     * Apply lenient rate limiting (for read-only endpoints)
     * 
     * @param string $endpoint Endpoint identifier
     * @return void
     */
    public static function applyLenient(string $endpoint): void
    {
        self::apply($endpoint, 200, 300); // 200 requests per 5 minutes
    }
}


