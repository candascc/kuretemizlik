<?php
declare(strict_types=1);

/**
 * Exception Handler
 * Centralized exception handling and logging
 */
class ExceptionHandler
{
    private static $registered = false;
    
    /**
     * Register global exception handler
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
        
        self::$registered = true;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public static function handleException(Throwable $exception): void
    {
        self::logException($exception);
        
        // In production, show generic error
        if (!defined('APP_DEBUG') || !APP_DEBUG) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Internal server error',
                'message' => 'An error occurred while processing your request.'
            ]);
            exit;
        }
        
        // In debug mode, show detailed error
        http_response_code(500);
        echo json_encode([
            'error' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        exit;
    }
    
    /**
     * Handle PHP errors
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        // Convert error to exception if it's a fatal error
        if (!(error_reporting() & $severity)) {
            return false; // Error was suppressed with @
        }
        
        $exception = new ErrorException($message, 0, $severity, $file, $line);
        self::logException($exception);
        
        // In production, don't display errors
        if (!defined('APP_DEBUG') || !APP_DEBUG) {
            return true; // Suppress error display
        }
        
        // In debug mode, throw exception
        throw $exception;
    }
    
    /**
     * Handle fatal errors
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $exception = new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
            self::logException($exception);
        }
    }
    
    /**
     * Log exception to error log and activity log
     */
    private static function logException(Throwable $exception): void
    {
        $logMessage = sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        // Phase 3.4: Use Logger if available, otherwise fallback to error_log
        if (class_exists('Logger')) {
            Logger::error($logMessage);
        } else {
            error_log($logMessage);
        }
        
        // Log to activity log if available
        if (class_exists('ActivityLogger')) {
            try {
                ActivityLogger::log('exception', [
                    'type' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString()
                ]);
            } catch (Exception $e) {
                // Don't throw exception while logging exception
                if (class_exists('Logger')) {
                    Logger::error("Failed to log exception to ActivityLogger: " . $e->getMessage());
                } else {
                    error_log("Failed to log exception to ActivityLogger: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Format exception for display
     */
    public static function formatException(Throwable $exception): string
    {
        return sprintf(
            "%s: %s in %s:%d",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
    }
}

