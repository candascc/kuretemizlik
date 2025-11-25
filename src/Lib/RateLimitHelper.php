<?php
/**
 * Rate Limit Helper
 * STAGE 4.2: Centralized rate limiting helper for consistent usage across all login endpoints
 * 
 * This helper standardizes rate limiting usage while preserving existing behavior.
 * Uses the existing RateLimit class (SQLite-backed) for persistent rate limiting.
 */

class RateLimitHelper
{
    // STAGE 4.2: Standard rate limit configurations
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_BLOCK_DURATION = 300; // 5 minutes
    
    private const PASSWORD_RESET_MAX_ATTEMPTS = 3;
    private const PASSWORD_RESET_BLOCK_DURATION = 600; // 10 minutes
    
    private const OTP_MAX_ATTEMPTS = 5;
    private const OTP_BLOCK_DURATION = 300; // 5 minutes
    
    /**
     * Check and enforce rate limit for login attempts
     * 
     * @param string $identifier User identifier (username, phone, email, etc.)
     * @param string $type Rate limit type: 'login', 'password_reset', 'otp'
     * @return array ['allowed' => bool, 'remaining_seconds' => int|null, 'message' => string|null]
     */
    public static function checkLoginRateLimit(string $identifier, string $type = 'login'): array
    {
        $ipAddress = self::getClientIp();
        
        // Build rate limit key based on type
        $keyPrefix = match($type) {
            'login' => 'login',
            'password_reset' => 'password_reset',
            'otp' => 'otp',
            default => 'login'
        };
        
        $rateLimitKey = "{$keyPrefix}_{$ipAddress}_" . md5($identifier);
        
        // Get limits based on type
        [$maxAttempts, $blockDuration] = match($type) {
            'login' => [self::LOGIN_MAX_ATTEMPTS, self::LOGIN_BLOCK_DURATION],
            'password_reset' => [self::PASSWORD_RESET_MAX_ATTEMPTS, self::PASSWORD_RESET_BLOCK_DURATION],
            'otp' => [self::OTP_MAX_ATTEMPTS, self::OTP_BLOCK_DURATION],
            default => [self::LOGIN_MAX_ATTEMPTS, self::LOGIN_BLOCK_DURATION]
        };
        
        // Check if rate limit is exceeded
        if (!RateLimit::check($rateLimitKey, $maxAttempts, $blockDuration)) {
            $remaining = RateLimit::getBlockTimeRemaining($rateLimitKey);
            return [
                'allowed' => false,
                'remaining_seconds' => $remaining,
                'message' => "Çok fazla başarısız deneme. $remaining saniye sonra tekrar deneyin."
            ];
        }
        
        return [
            'allowed' => true,
            'remaining_seconds' => null,
            'message' => null,
            'rate_limit_key' => $rateLimitKey,
            'max_attempts' => $maxAttempts,
            'block_duration' => $blockDuration
        ];
    }
    
    /**
     * Record a failed login attempt
     * 
     * @param string $rateLimitKey Rate limit key from checkLoginRateLimit()
     * @param int|null $maxAttempts Maximum attempts (uses default if null)
     * @param int|null $blockDuration Block duration in seconds (uses default if null)
     * @return bool True if still allowed, false if blocked
     */
    public static function recordFailedAttempt(string $rateLimitKey, ?int $maxAttempts = null, ?int $blockDuration = null): bool
    {
        $maxAttempts = $maxAttempts ?? self::LOGIN_MAX_ATTEMPTS;
        $blockDuration = $blockDuration ?? self::LOGIN_BLOCK_DURATION;
        
        return RateLimit::recordAttempt($rateLimitKey, $maxAttempts, $blockDuration);
    }
    
    /**
     * Clear rate limit (e.g., on successful login)
     * 
     * @param string $rateLimitKey Rate limit key from checkLoginRateLimit()
     * @return void
     */
    public static function clearRateLimit(string $rateLimitKey): void
    {
        RateLimit::clear($rateLimitKey);
    }
    
    /**
     * Get remaining attempts for a rate limit key
     * 
     * @param string $rateLimitKey Rate limit key
     * @param int $maxAttempts Maximum attempts
     * @return int Remaining attempts
     */
    public static function getRemainingAttempts(string $rateLimitKey, int $maxAttempts = self::LOGIN_MAX_ATTEMPTS): int
    {
        return RateLimit::getRemainingAttempts($rateLimitKey, $maxAttempts);
    }
    
    /**
     * Get client IP address (handles proxies and load balancers)
     * STAGE 2 ROUND 2: Made public for use in audit logging
     * 
     * @return string Client IP address
     */
    public static function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',      // Cloudflare
            'HTTP_X_FORWARDED_FOR',       // Proxy/Load balancer
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

