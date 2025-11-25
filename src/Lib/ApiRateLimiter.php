<?php
/**
 * API Rate Limiter
 * 
 * Provides rate limiting functionality for API endpoints.
 * Uses in-memory cache for rate limit tracking.
 * 
 * @package App\Lib
 * @author System
 * @version 1.0
 */

require_once __DIR__ . '/../Constants/AppConstants.php';

class ApiRateLimiter
{
    /** @var array $cache In-memory cache for rate limit tracking */
    private static $cache = [];
    
    // ===== ERR-024 FIX: Magic numbers replaced with constants =====
    /** @var int $defaultLimit Default rate limit (requests per window) */
    private static $defaultLimit = AppConstants::RATE_LIMIT_API_REQUESTS;
    
    /** @var int $defaultWindow Default time window in seconds */
    private static $defaultWindow = AppConstants::RATE_LIMIT_API_WINDOW;

    /**
     * Check if request is within rate limit
     */
    public static function check($key, $limit = null, $window = null)
    {
        $limit = $limit ?? self::$defaultLimit;
        $window = $window ?? self::$defaultWindow;
        
        $userKey = self::getUserKey($key);
        $now = time();
        
        // Clean old entries
        self::cleanExpiredEntries($userKey, $now - $window);
        
        // Count current requests
        $count = count(self::$cache[$userKey] ?? []);
        
        return $count < $limit;
    }

    /**
     * Record a request for rate limiting
     * 
     * @param string $key Rate limit key
     * @param int|null $limit Maximum number of requests allowed
     * @param int|null $window Time window in seconds
     * @return void
     */
    public static function record($key, $limit = null, $window = null): void
    {
        $limit = $limit ?? self::$defaultLimit;
        $window = $window ?? self::$defaultWindow;
        
        $userKey = self::getUserKey($key);
        $now = time();
        
        if (!isset(self::$cache[$userKey])) {
            self::$cache[$userKey] = [];
        }
        
        self::$cache[$userKey][] = $now;
        
        // Keep only recent entries
        self::cleanExpiredEntries($userKey, $now - $window);
    }

    /**
     * Send rate limit exceeded response
     */
    /**
     * Send rate limit exceeded response
     * 
     * @param string $key Rate limit key
     * @param int $retryAfter Retry after time in seconds
     * @return void
     */
    public static function sendLimitExceededResponse($key, $retryAfter = AppConstants::RATE_LIMIT_API_WINDOW)
    {
        header('HTTP/1.1 429 Too Many Requests');
        header('Content-Type: application/json');
        header("Retry-After: $retryAfter");
        
        echo json_encode([
            'error' => 'Rate limit exceeded',
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter
        ]);
        
        exit;
    }

    /**
     * Get user-specific key
     */
    private static function getUserKey($key)
    {
        $userId = Auth::id() ?? 'anonymous';
        return $key . '_' . $userId;
    }

    /**
     * Clean expired entries from cache
     * 
     * @param string $userKey User-specific rate limit key
     * @param int $cutoff Timestamp cutoff (entries before this are expired)
     * @return void
     */
    private static function cleanExpiredEntries($userKey, $cutoff): void
    {
        if (!isset(self::$cache[$userKey])) {
            return;
        }
        
        self::$cache[$userKey] = array_filter(
            self::$cache[$userKey],
            function($timestamp) use ($cutoff) {
                return $timestamp > $cutoff;
            }
        );
    }

    /**
     * Get current usage count for a rate limit key
     * 
     * @param string $key Rate limit key
     * @return int Current usage count
     */
    public static function getUsage($key): int
    {
        $userKey = self::getUserKey($key);
        return count(self::$cache[$userKey] ?? []);
    }

    /**
     * Reset rate limit for a user
     * 
     * @param string $key Rate limit key
     * @return void
     */
    public static function reset($key): void
    {
        $userKey = self::getUserKey($key);
        unset(self::$cache[$userKey]);
    }
}