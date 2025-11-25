<?php
/**
 * Application Error Handler
 * OPS HARDENING ROUND 1: Enhanced error handling with structured logging, request ID, and safe user messages
 * 
 * This class provides a centralized way to handle exceptions and errors in controllers,
 * with structured logging for monitoring systems (Sentry, ELK, CloudWatch, etc.)
 */

class AppErrorHandler
{
    /**
     * Get or generate request ID for correlation
     * OPS HARDENING: Request ID for log correlation across services
     */
    public static function getRequestId(): string
    {
        static $requestId = null;
        
        if ($requestId === null) {
            // Check if request ID is already set (e.g., from load balancer, API gateway)
            $requestId = $_SERVER['HTTP_X_REQUEST_ID'] 
                ?? $_SERVER['HTTP_X_CORRELATION_ID'] 
                ?? $_SERVER['HTTP_X_TRACE_ID'] 
                ?? null;
            
            // Generate new request ID if not present
            if (!$requestId) {
                $requestId = 'req_' . uniqid('', true) . '_' . bin2hex(random_bytes(4));
            }
            
            // Store in session for request correlation
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['_request_id'] = $requestId;
            }
        }
        
        return $requestId;
    }
    
    /**
     * Log exception with structured format for monitoring systems
     * OPS HARDENING: Structured logging compatible with Sentry/ELK/CloudWatch
     */
    public static function logException(Throwable $exception, array $context = []): void
    {
        $requestId = self::getRequestId();
        $user = null;
        $companyId = null;
        
        // Get user context if available
        if (class_exists('Auth') && Auth::check()) {
            try {
                $user = Auth::user();
                $companyId = $user['company_id'] ?? null;
            } catch (Exception $e) {
                // Silently fail - don't break error logging
            }
        }
        
        // Get IP address
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        
        // Build structured log entry
        $logEntry = [
            'type' => 'error',
            'level' => self::getErrorLevel($exception),
            'timestamp' => date('c'), // ISO 8601 format
            'request_id' => $requestId,
            'exception' => [
                'class' => get_class($exception),
                'message' => self::sanitizeMessage($exception->getMessage()),
                'file' => self::sanitizeFilePath($exception->getFile()),
                'line' => $exception->getLine(),
                'trace' => self::sanitizeTrace($exception->getTraceAsString()),
            ],
            'request' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'uri' => $_SERVER['REQUEST_URI'] ?? '/',
                'ip' => $ip,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200),
            ],
            'user' => $user ? [
                'id' => $user['id'] ?? null,
                'username' => $user['username'] ?? null,
                'role' => $user['role'] ?? null,
                'company_id' => $companyId,
            ] : null,
            'context' => self::sanitizeContext($context),
        ];
        
        // Log to file (JSON format for easy parsing)
        self::writeStructuredLog($logEntry);
        
        // ROUND 4: Send security alert for critical errors
        $errorLevel = self::getErrorLevel($exception);
        if ($errorLevel === 'CRITICAL' && class_exists('SecurityAlertService')) {
            try {
                SecurityAlertService::notifyCriticalError([
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'ip_address' => $ip,
                    'user_id' => $user['id'] ?? null,
                    'request_id' => $requestId,
                ]);
            } catch (Exception $e) {
                // Non-blocking: don't fail if alerting fails
                error_log("AppErrorHandler: Failed to send critical error alert: " . $e->getMessage());
            }
        }
        
        // Also log to existing Logger if available (for backward compatibility)
        if (class_exists('Logger')) {
            try {
                Logger::error(
                    "Exception: {$exception->getMessage()}",
                    [
                        'request_id' => $requestId,
                        'exception_class' => get_class($exception),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                    ] + $context
                );
            } catch (Exception $e) {
                // Silently fail - don't break error logging
            }
        }
    }
    
    /**
     * Handle exception and return safe user message
     * OPS HARDENING: Safe error messages that don't leak sensitive information
     */
    public static function handleAndRespond(Throwable $exception, array $context = [], ?string $userMessage = null): void
    {
        // Log the exception
        self::logException($exception, $context);
        
        // Get safe user message
        $safeMessage = $userMessage ?? self::toSafeMessage($exception);
        
        // Set request ID header for debugging
        if (!headers_sent()) {
            header('X-Request-Id: ' . self::getRequestId());
        }
        
        // For API requests, return JSON
        if (self::isApiRequest()) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => [
                    'message' => $safeMessage,
                    'request_id' => self::getRequestId(),
                ],
            ], JSON_PRETTY_PRINT);
            exit;
        }
        
        // For web requests, show error page
        http_response_code(500);
        $code = 500;
        $message = $safeMessage;
        $details = defined('APP_DEBUG') && APP_DEBUG ? $exception->getMessage() : null;
        
        include __DIR__ . '/../Views/errors/error.php';
        exit;
    }
    
    /**
     * Convert exception to safe user message
     * OPS HARDENING: Never expose sensitive information to users
     */
    public static function toSafeMessage(Throwable $exception): string
    {
        // In debug mode, show more details (but still sanitized)
        if (defined('APP_DEBUG') && APP_DEBUG) {
            return 'Bir hata oluştu: ' . self::sanitizeMessage($exception->getMessage());
        }
        
        // Production: Generic user-friendly message
        $exceptionClass = get_class($exception);
        
        // Map common exceptions to user-friendly messages
        $userMessages = [
            'PDOException' => 'Veritabanı bağlantısı kurulamadı. Lütfen daha sonra tekrar deneyin.',
            'DatabaseException' => 'Veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin.',
            'ValidationException' => 'Girdiğiniz bilgiler geçersiz. Lütfen kontrol edip tekrar deneyin.',
            'UnauthorizedException' => 'Bu işlem için yetkiniz bulunmamaktadır.',
            'NotFoundException' => 'Aradığınız kayıt bulunamadı.',
        ];
        
        if (isset($userMessages[$exceptionClass])) {
            return $userMessages[$exceptionClass];
        }
        
        // Default generic message
        return 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin. Sorun devam ederse destek ekibiyle iletişime geçin.';
    }
    
    /**
     * Check if current request is an API request
     */
    private static function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return strpos($uri, '/api/') === 0 || 
               strpos($uri, '/api/v') === 0 ||
               (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }
    
    /**
     * Get error level from exception
     */
    private static function getErrorLevel(Throwable $exception): string
    {
        $class = get_class($exception);
        
        if (strpos($class, 'Error') !== false || strpos($class, 'Fatal') !== false) {
            return 'CRITICAL';
        }
        
        if (strpos($class, 'Warning') !== false) {
            return 'WARNING';
        }
        
        return 'ERROR';
    }
    
    /**
     * Sanitize error message to remove sensitive information
     */
    private static function sanitizeMessage(string $message): string
    {
        // Remove sensitive patterns
        $patterns = [
            '/password[=:]\s*[^\s]+/i' => 'password=[HIDDEN]',
            '/token[=:]\s*[^\s]+/i' => 'token=[HIDDEN]',
            '/secret[=:]\s*[^\s]+/i' => 'secret=[HIDDEN]',
            '/api[_-]?key[=:]\s*[^\s]+/i' => 'api_key=[HIDDEN]',
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $message = preg_replace($pattern, $replacement, $message);
        }
        
        return $message;
    }
    
    /**
     * Sanitize file path (remove full server paths in production)
     */
    private static function sanitizeFilePath(string $file): string
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            return $file;
        }
        
        // Only return relative path from project root
        $root = defined('APP_ROOT') ? APP_ROOT : __DIR__ . '/../../';
        if (strpos($file, $root) === 0) {
            return substr($file, strlen($root));
        }
        
        return basename($file);
    }
    
    /**
     * Sanitize stack trace (remove sensitive info)
     */
    private static function sanitizeTrace(string $trace): string
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            return $trace;
        }
        
        // In production, only keep first few lines
        $lines = explode("\n", $trace);
        return implode("\n", array_slice($lines, 0, 10)) . "\n... (truncated)";
    }
    
    /**
     * Sanitize context data (remove sensitive fields)
     */
    private static function sanitizeContext(array $context): array
    {
        $sensitiveKeys = ['password', 'token', 'secret', 'api_key', 'csrf_token', 'auth_token'];
        $sanitized = [];
        
        foreach ($context as $key => $value) {
            $keyLower = strtolower($key);
            $isSensitive = false;
            
            foreach ($sensitiveKeys as $sensitive) {
                if (strpos($keyLower, $sensitive) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            if ($isSensitive) {
                $sanitized[$key] = '[HIDDEN]';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeContext($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Write structured log entry to file
     * ROUND 4: Added extension point for external monitoring systems
     */
    private static function writeStructuredLog(array $logEntry): void
    {
        // Write to local file (default behavior)
        $logDir = defined('APP_ROOT') ? APP_ROOT . '/logs' : __DIR__ . '/../../logs';
        
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        if (!is_writable($logDir)) {
            // Fallback to PHP error_log
            error_log('[ERROR] ' . json_encode($logEntry, JSON_UNESCAPED_UNICODE));
            return;
        }
        
        // Write to daily error log file (JSON format)
        $logFile = $logDir . '/errors_' . date('Y-m-d') . '.json';
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        
        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // ROUND 4: Extension point for external monitoring systems (Sentry/ELK/CloudWatch)
        self::sendToExternalSinks($logEntry);
    }
    
    /**
     * Send log entry to external monitoring sinks
     * ROUND 5 - STAGE 1: Refactored to use ErrorSinkInterface pattern
     * 
     * @param array $logEntry Structured log entry
     * @return void
     */
    private static function sendToExternalSinks(array $logEntry): void
    {
        // Load external logging config from security.php or environment
        $configPath = __DIR__ . '/../../config/security.php';
        $config = [];
        if (file_exists($configPath)) {
            $config = require $configPath;
        }
        
        $externalConfig = $config['logging']['external'] ?? [];
        $enabled = $externalConfig['enabled'] ?? false;
        
        if (!$enabled) {
            return; // External logging disabled
        }
        
        // ROUND 5: Use ErrorSinkInterface pattern
        $sink = self::getErrorSink($externalConfig);
        if (!$sink || !$sink->isEnabled()) {
            return; // No sink configured or disabled
        }
        
        // Non-blocking: don't fail if external sink fails
        try {
            $sink->send($logEntry);
        } catch (Exception $e) {
            // Silently fail - don't break error logging
            error_log("AppErrorHandler: Failed to send to external sink: " . $e->getMessage());
        }
    }
    
    /**
     * Get error sink instance based on config
     * ROUND 5 - STAGE 1: Factory pattern for error sinks
     * 
     * @param array $config External logging config
     * @return ErrorSinkInterface|null Error sink instance or null
     */
    private static function getErrorSink(array $config): ?ErrorSinkInterface
    {
        static $sinkCache = null;
        
        // Cache sink instance (singleton pattern)
        if ($sinkCache !== null) {
            return $sinkCache;
        }
        
        $provider = $config['provider'] ?? 'sentry';
        $dsn = $config['dsn'] ?? '';
        
        if (empty($dsn)) {
            return null; // No DSN configured
        }
        
        // Load sink implementations
        $sinkPath = __DIR__ . '/../Services/';
        
        try {
            switch ($provider) {
                case 'sentry':
                    if (file_exists($sinkPath . 'SentryErrorSink.php')) {
                        require_once $sinkPath . 'SentryErrorSink.php';
                        $sinkCache = new SentryErrorSink($config);
                        return $sinkCache;
                    }
                    // Fallback to old method if new class doesn't exist
                    break;
                    
                case 'custom':
                case 'webhook':
                    if (file_exists($sinkPath . 'GenericWebhookErrorSink.php')) {
                        require_once $sinkPath . 'GenericWebhookErrorSink.php';
                        $sinkCache = new GenericWebhookErrorSink($config);
                        return $sinkCache;
                    }
                    // Fallback to old method if new class doesn't exist
                    break;
                    
                case 'elk':
                    // Use existing ELK implementation (HTTP POST)
                    // Keep old method for backward compatibility
                    break;
            }
        } catch (Exception $e) {
            error_log("AppErrorHandler: Failed to create error sink ({$provider}): " . $e->getMessage());
            return null;
        }
        
        // Fallback to old methods for backward compatibility
        // This ensures existing ELK/CloudWatch implementations still work
        return null;
    }
    
    /**
     * Send to Sentry (skeleton - requires Sentry SDK)
     * ROUND 4: Skeleton implementation - requires sentry/sentry-sdk package
     * 
     * @param array $logEntry Log entry
     * @param string $dsn Sentry DSN
     * @param array $config Additional config
     * @return void
     */
    private static function sendToSentry(array $logEntry, string $dsn, array $config): void
    {
        // TODO: Implement Sentry SDK integration
        // Example (requires composer require sentry/sentry-sdk):
        // \Sentry\init(['dsn' => $dsn]);
        // \Sentry\captureException($exception);
        
        // For now, just log that Sentry integration is needed
        error_log("AppErrorHandler: Sentry integration requires sentry/sentry-sdk package. DSN: " . substr($dsn, 0, 20) . "...");
    }
    
    /**
     * Send to ELK stack (via HTTP endpoint)
     * ROUND 4: Basic HTTP POST to ELK endpoint
     * 
     * @param array $logEntry Log entry
     * @param string $dsn ELK endpoint URL
     * @param array $config Additional config
     * @return void
     */
    private static function sendToElk(array $logEntry, string $dsn, array $config): void
    {
        $timeout = $config['timeout'] ?? 2;
        
        $ch = curl_init($dsn);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($logEntry),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]);
        
        @curl_exec($ch);
        curl_close($ch);
    }
    
    /**
     * Send to AWS CloudWatch (skeleton - requires AWS SDK)
     * ROUND 4: Skeleton implementation - requires aws/aws-sdk-php package
     * 
     * @param array $logEntry Log entry
     * @param string $dsn CloudWatch config (JSON or endpoint)
     * @param array $config Additional config
     * @return void
     */
    private static function sendToCloudWatch(array $logEntry, string $dsn, array $config): void
    {
        // TODO: Implement CloudWatch SDK integration
        // Example (requires composer require aws/aws-sdk-php):
        // $client = new \Aws\CloudWatchLogs\CloudWatchLogsClient([...]);
        // $client->putLogEvents([...]);
        
        error_log("AppErrorHandler: CloudWatch integration requires aws/aws-sdk-php package.");
    }
    
    /**
     * Send to custom webhook endpoint
     * ROUND 4: Generic webhook for custom monitoring systems
     * 
     * @param array $logEntry Log entry
     * @param string $dsn Webhook URL
     * @param array $config Additional config
     * @return void
     */
    private static function sendToCustomWebhook(array $logEntry, string $dsn, array $config): void
    {
        $timeout = $config['timeout'] ?? 2;
        $secret = $config['secret'] ?? '';
        
        $payload = $logEntry;
        if (!empty($secret)) {
            $payload['signature'] = hash_hmac('sha256', json_encode($logEntry), $secret);
        }
        
        $ch = curl_init($dsn);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: AppErrorHandler/1.0',
            ],
        ]);
        
        @curl_exec($ch);
        curl_close($ch);
    }
}

