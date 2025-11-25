<?php
/**
 * Error Tracker
 * Centralized error tracking and monitoring
 */
class ErrorTracker
{
    private static $errors = [];
    
    /**
     * Track error
     */
    public static function track(string $message, array $context = [], string $level = 'error'): void
    {
        $userId = null;
        if (class_exists('Auth') && method_exists('Auth', 'check')) {
            try {
                $userId = Auth::check() ? Auth::id() : null;
            } catch (Exception $e) {
                // Silently fail - auth is not critical for error tracking
            }
        }
        
        $error = [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'timestamp' => date('Y-m-d H:i:s'),
            'file' => debug_backtrace()[0]['file'] ?? null,
            'line' => debug_backtrace()[0]['line'] ?? null,
            'user_id' => $userId,
            'url' => $_SERVER['REQUEST_URI'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ];
        
        self::$errors[] = $error;
        
        // Log to file
        if (class_exists('Logger')) {
            Logger::error($message, $context);
        }
        
        // Send to monitoring service if configured
        self::sendToMonitoring($error);
    }
    
    /**
     * Track exception
     */
    public static function trackException(Throwable $e, array $context = []): void
    {
        self::track($e->getMessage(), array_merge($context, [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]), 'critical');
    }
    
    /**
     * Get recent errors
     */
    public static function getRecent(int $limit = 50): array
    {
        return array_slice(self::$errors, -$limit);
    }
    
    /**
     * Get error statistics
     */
    public static function getStats(): array
    {
        $stats = [
            'total' => count(self::$errors),
            'by_level' => [],
            'by_hour' => []
        ];
        
        foreach (self::$errors as $error) {
            $level = $error['level'];
            $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
            
            $hour = date('H', strtotime($error['timestamp']));
            $stats['by_hour'][$hour] = ($stats['by_hour'][$hour] ?? 0) + 1;
        }
        
        return $stats;
    }
    
    /**
     * Send to monitoring service (if configured)
     */
    private static function sendToMonitoring(array $error): void
    {
        // Only send critical errors in production
        if (!APP_DEBUG && $error['level'] === 'critical') {
            // Implement integration with monitoring services (Sentry, Bugsnag, etc.)
            // For now, just log to error log
            error_log("CRITICAL ERROR: " . json_encode($error));
        }
    }
    
    /**
     * Clear tracked errors
     */
    public static function clear(): void
    {
        self::$errors = [];
    }
}
