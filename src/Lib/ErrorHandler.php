<?php
/**
 * Global Error Handler
 */

class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $error = [
            'type' => 'Error',
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::logError($error);
        
        if (APP_DEBUG) {
            self::displayError($error);
        } else {
            self::displayUserFriendlyError();
        }
        
        return true;
    }
    
    public static function handleException(Throwable $exception): void
    {
        $error = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::logError($error);
        
        // Track error if ErrorTracker is available
        if (class_exists('ErrorTracker')) {
            try {
                ErrorTracker::trackException($exception);
            } catch (Exception $e) {
                // Silently fail - don't break error handling
            }
        }
        
        if (APP_DEBUG) {
            self::displayException($exception);
        } else {
            self::displayUserFriendlyError();
        }
    }
    
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorData = [
                'type' => 'Fatal Error',
                'severity' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            self::logError($errorData);
            
            if (APP_DEBUG) {
                self::displayError($errorData);
            } else {
                self::displayUserFriendlyError();
            }
        }
    }
    
    private static function logError(array $error): void
    {
        // Sanitize file path for security (don't expose full server paths in production)
        $filePath = $error['file'];
        if (!APP_DEBUG) {
            $filePath = basename($error['file']);
        }
        
        // Sanitize error message to prevent information leakage
        $message = $error['message'];
        if (!APP_DEBUG) {
            // Remove sensitive information patterns
            $message = preg_replace('/password[=:]\s*[^\s]+/i', 'password=[HIDDEN]', $message);
            $message = preg_replace('/token[=:]\s*[^\s]+/i', 'token=[HIDDEN]', $message);
        }
        
        $logMessage = sprintf(
            "[%s] %s: %s in %s on line %d\n",
            $error['timestamp'],
            $error['type'],
            $message,
            $filePath,
            $error['line']
        );
        
        // ===== IMPROVEMENT: Use APP_ROOT for absolute path =====
        $logPath = defined('APP_ROOT') ? APP_ROOT . '/logs/error.log' : __DIR__ . '/../../logs/error.log';
        error_log($logMessage, 3, $logPath);
        
        // Log to advanced logging system if available
        if (class_exists('Logger')) {
            try {
                $isError = (strpos($error['type'], 'Error') !== false || strpos($error['type'], 'Fatal') !== false);
                
                $context = [
                    'type' => $error['type'],
                    'file' => $error['file'],
                    'line' => $error['line']
                ];
                
                if (isset($error['trace'])) {
                    $context['trace'] = $error['trace'];
                }
                
                if (isset($error['severity'])) {
                    $context['severity'] = $error['severity'];
                }
                
                if ($isError) {
                    Logger::error($error['message'], $context);
                } else {
                    Logger::warning($error['message'], $context);
                }
            } catch (Exception $e) {
                // Silently fail - don't break error handling
                error_log("Logger error: " . $e->getMessage());
            }
        }
        
        // Also log to activity log if user is authenticated
        if (class_exists('ActivityLogger')) {
            try {
                if (method_exists('Auth', 'check') && Auth::check()) {
                    ActivityLogger::log('error', 'system', [
                        'message' => $error['message'],
                        'file' => $error['file'],
                        'line' => $error['line'],
                        'type' => $error['type']
                    ]);
                }
            } catch (Exception $e) {
                // Silently fail - don't break error handling
            }
        }
    }
    
    private static function displayError(array $error): void
    {
        // ===== ERR-021 FIX: Sanitize error messages in production =====
        $message = self::sanitizeErrorMessage($error['message']);
        $file = APP_DEBUG ? $error['file'] : basename($error['file']);
        // ===== ERR-021 FIX: End =====
        
        if (php_sapi_name() === 'cli') {
            echo "Error: {$message} in {$file} on line {$error['line']}\n";
            return;
        }
        
        http_response_code(500);
        echo "<h1>Application Error</h1>";
        echo "<p><strong>Type:</strong> {$error['type']}</p>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . "</p>";
        echo "<p><strong>Line:</strong> {$error['line']}</p>";
        echo "<p><strong>Time:</strong> {$error['timestamp']}</p>";
        
        if (isset($error['trace']) && APP_DEBUG) {
            echo "<h2>Stack Trace:</h2>";
            echo "<pre>" . htmlspecialchars($error['trace'], ENT_QUOTES, 'UTF-8') . "</pre>";
        }
    }
    
    private static function displayException(Throwable $exception): void
    {
        // ===== ERR-021 FIX: Sanitize exception messages in production =====
        $message = self::sanitizeErrorMessage($exception->getMessage());
        $file = APP_DEBUG ? $exception->getFile() : basename($exception->getFile());
        $trace = APP_DEBUG ? $exception->getTraceAsString() : null;
        // ===== ERR-021 FIX: End =====
        
        if (php_sapi_name() === 'cli') {
            echo "Exception: {$message}\n";
            if ($trace) {
                echo "Stack trace:\n{$trace}\n";
            }
            return;
        }
        
        http_response_code(500);
        echo "<h1>Application Exception</h1>";
        echo "<p><strong>Type:</strong> " . htmlspecialchars(get_class($exception), ENT_QUOTES, 'UTF-8') . "</p>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . "</p>";
        echo "<p><strong>Line:</strong> {$exception->getLine()}</p>";
        
        if ($trace) {
            echo "<h2>Stack Trace:</h2>";
            echo "<pre>" . htmlspecialchars($trace, ENT_QUOTES, 'UTF-8') . "</pre>";
        }
    }
    
    private static function displayUserFriendlyError(): void
    {
        if (php_sapi_name() === 'cli') {
            echo "An error occurred. Please check the logs.\n";
            return;
        }
        
        http_response_code(500);
        
        if (Auth::check()) {
            include __DIR__ . '/../Views/errors/error.php';
        } else {
            redirect(base_url('/login'));
        }
    }
    
    // ===== ERR-021 FIX: Sanitize error messages to prevent information disclosure =====
    /**
     * Sanitize error messages to remove sensitive information
     */
    private static function sanitizeErrorMessage(string $message): string
    {
        if (APP_DEBUG) {
            // In debug mode, show full messages but still escape HTML
            return $message;
        }
        
        // Remove sensitive patterns
        $patterns = [
            // Database credentials
            '/password[=:]\s*[^\s]+/i' => 'password=[HIDDEN]',
            '/pwd[=:]\s*[^\s]+/i' => 'pwd=[HIDDEN]',
            '/pass[=:]\s*[^\s]+/i' => 'pass=[HIDDEN]',
            '/user[=:]\s*[^\s]+/i' => 'user=[HIDDEN]',
            '/username[=:]\s*[^\s]+/i' => 'username=[HIDDEN]',
            '/host[=:]\s*[^\s]+/i' => 'host=[HIDDEN]',
            '/database[=:]\s*[^\s]+/i' => 'database=[HIDDEN]',
            '/dbname[=:]\s*[^\s]+/i' => 'dbname=[HIDDEN]',
            
            // API keys and tokens
            '/token[=:]\s*[^\s]+/i' => 'token=[HIDDEN]',
            '/api[_-]?key[=:]\s*[^\s]+/i' => 'api_key=[HIDDEN]',
            '/secret[=:]\s*[^\s]+/i' => 'secret=[HIDDEN]',
            '/apikey[=:]\s*[^\s]+/i' => 'apikey=[HIDDEN]',
            
            // File paths (full paths)
            '/\/[a-z0-9\/\._-]+\.(php|sql|log|env|ini)/i' => '[FILE_PATH]',
            '/[a-z]:\\\\[a-z0-9\\\\\._-]+/i' => '[FILE_PATH]',
            
            // SQL queries (partial)
            '/SELECT\s+.*\s+FROM/i' => 'SELECT ... FROM',
            '/INSERT\s+INTO\s+.*\s+VALUES/i' => 'INSERT INTO ... VALUES',
            '/UPDATE\s+.*\s+SET/i' => 'UPDATE ... SET',
            '/DELETE\s+FROM\s+.*/i' => 'DELETE FROM ...',
            
            // Email addresses
            '/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i' => '[EMAIL]',
            
            // IP addresses
            '/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/' => '[IP_ADDRESS]',
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $message = preg_replace($pattern, $replacement, $message);
        }
        
        // Generic error message if message contains too much detail
        if (strlen($message) > 200) {
            return 'An error occurred. Please contact support if the problem persists.';
        }
        
        return $message;
    }
    // ===== ERR-021 FIX: End =====
}
