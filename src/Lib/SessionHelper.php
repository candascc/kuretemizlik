<?php
declare(strict_types=1);

/**
 * Session Management Helper
 * Centralized session management to prevent multiple session_start() calls
 * and ensure consistent session configuration
 */
class SessionHelper
{
    private static $initialized = false;
    
    /**
     * Ensure session is started with proper configuration
     * This method is safe to call multiple times - it will only start session once
     * 
     * @return bool True if session is active, false otherwise
     */
    public static function ensureStarted(): bool
    {
        // If session is already active, return true
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
        
        // In CLI/phpdbg mode, sessions may not be available
        $isCliLike = in_array(PHP_SAPI, ['cli', 'phpdbg'], true);
        if ($isCliLike) {
            // Try to start session anyway (for testing purposes)
            // In CLI, we may need to set a custom session save path
            if (session_status() === PHP_SESSION_DISABLED) {
                return false;
            }
        } else {
            // If headers are already sent, we cannot start session (web mode only)
            if (headers_sent()) {
                if (class_exists('Logger')) {
                    Logger::warning("SessionHelper::ensureStarted: Headers already sent, cannot start session");
                } elseif (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("SessionHelper::ensureStarted: Headers already sent, cannot start session");
                }
                return false;
            }
        }
        
        // Configure session cookie parameters before starting (only if not in CLI or headers not sent)
        if (!self::$initialized) {
            if (!$isCliLike || !headers_sent()) {
                self::configureSession();
            }
            self::$initialized = true;
        }
        
        // Start session with error handling
        try {
            // In CLI, use a temporary session save path if needed
            if ($isCliLike) {
                $savePath = ini_get('session.save_path');
                if (empty($savePath) || $savePath === session_save_path()) {
                    $tempPath = sys_get_temp_dir() . '/php_sessions_' . getmypid();
                    if (!is_dir($tempPath)) {
                        @mkdir($tempPath, 0700, true);
                    }
                    if (!headers_sent()) {
                        ini_set('session.save_path', $tempPath);
                    }
                }
            }
            
            session_start();
            
            // Verify session is actually active
            if (session_status() !== PHP_SESSION_ACTIVE) {
                if (class_exists('Logger')) {
                    Logger::warning("SessionHelper::ensureStarted: Session start failed - status is not active");
                } elseif (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("SessionHelper::ensureStarted: Session start failed - status is not active");
                }
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::error("SessionHelper::ensureStarted: Exception - " . $e->getMessage());
            } elseif (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("SessionHelper::ensureStarted: Exception - " . $e->getMessage());
            }
            return false;
        } catch (Throwable $e) {
            if (class_exists('Logger')) {
                Logger::error("SessionHelper::ensureStarted: Throwable - " . $e->getMessage());
            } elseif (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("SessionHelper::ensureStarted: Throwable - " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Configure session cookie parameters
     * Uses APP_BASE from config if available, otherwise defaults to /app
     */
    private static function configureSession(): void
    {
        $isCliLike = in_array(PHP_SAPI, ['cli', 'phpdbg'], true);
        
        if ($isCliLike) {
            // CLI/phpdbg does not need cookies or cache headers but we still configure defaults
            ini_set('session.use_cookies', '0');
            ini_set('session.use_only_cookies', '0');
            ini_set('session.cache_limiter', '');
            session_cache_limiter('');
        }
        
        // Get cookie path from APP_BASE if defined, otherwise use default
        $cookiePath = defined('APP_BASE') && APP_BASE !== '' ? APP_BASE : '/app';
        
        // Determine if HTTPS is being used
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        
        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime'  => 0,
            'path'      => $cookiePath,
            'domain'    => null,
            'secure'    => $isHttps ? 1 : 0,
            'httponly'  => true,
            'samesite'  => 'Lax',
        ]);
        
        // Also set via ini_set for compatibility
        ini_set('session.cookie_path', $cookiePath);
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $isHttps ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');
    }
    
    /**
     * Check if session is active
     * 
     * @return bool
     */
    public static function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }
    
    /**
     * Get session status
     * 
     * @return int PHP_SESSION_DISABLED, PHP_SESSION_NONE, or PHP_SESSION_ACTIVE
     */
    public static function getStatus(): int
    {
        return session_status();
    }
}

