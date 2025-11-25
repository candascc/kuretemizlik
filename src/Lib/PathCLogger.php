<?php
/**
 * Path C Logger - /app first-load path logging
 * 
 * Provides centralized logging for /app first-load path with correlation ID
 * All logs go to logs/app_firstload_pathc.log
 */

class PathCLogger
{
    private static $logFile = null;
    private static $requestId = null;
    
    /**
     * Get log file path
     */
    private static function getLogFile(): string
    {
        if (self::$logFile === null) {
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            self::$logFile = $logDir . '/app_firstload_pathc.log';
        }
        return self::$logFile;
    }
    
    /**
     * Get or generate request ID
     */
    private static function getRequestId(): string
    {
        if (self::$requestId !== null) {
            return self::$requestId;
        }
        
        // Try to get from session
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['app_pathc_request_id'])) {
            self::$requestId = $_SESSION['app_pathc_request_id'];
            return self::$requestId;
        }
        
        // Generate new request ID
        try {
            self::$requestId = bin2hex(random_bytes(8));
        } catch (Exception $e) {
            self::$requestId = uniqid('app_', true);
        }
        
        // Store in session if session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['app_pathc_request_id'] = self::$requestId;
        }
        
        return self::$requestId;
    }
    
    /**
     * Set request ID (for correlation across requests)
     */
    public static function setRequestId(string $requestId): void
    {
        self::$requestId = $requestId;
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['app_pathc_request_id'] = $requestId;
        }
    }
    
    /**
     * Get current request ID (public accessor - returns cached value or null)
     */
    public static function getCurrentRequestId(): ?string
    {
        if (self::$requestId !== null) {
            return self::$requestId;
        }
        
        // Try to get from session
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['app_pathc_request_id'])) {
            self::$requestId = $_SESSION['app_pathc_request_id'];
            return self::$requestId;
        }
        
        return null;
    }
    
    /**
     * Build context array with user info
     */
    private static function buildContext(array $context = []): array
    {
        $defaultContext = [];
        
        // Add user info if available
        if (class_exists('Auth') && Auth::check()) {
            try {
                $user = Auth::user();
                $defaultContext['username'] = $user['username'] ?? null;
                $defaultContext['session_role'] = $_SESSION['role'] ?? null;
                $defaultContext['db_role'] = $user['role'] ?? null;
                
                // Normalize roles
                if ($defaultContext['session_role']) {
                    $defaultContext['session_role'] = strtoupper(trim($defaultContext['session_role']));
                }
                if ($defaultContext['db_role']) {
                    $defaultContext['db_role'] = strtoupper(trim($defaultContext['db_role']));
                }
                
                // is_admin_like
                $role = $defaultContext['session_role'] ?? $defaultContext['db_role'];
                $defaultContext['is_admin_like'] = $role ? in_array($role, ['ADMIN', 'SUPERADMIN'], true) : false;
            } catch (Throwable $e) {
                // Ignore auth errors in logging
            }
        } else {
            $defaultContext['username'] = null;
            $defaultContext['session_role'] = null;
            $defaultContext['db_role'] = null;
            $defaultContext['is_admin_like'] = false;
        }
        
        // Add path
        $defaultContext['path'] = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Merge with provided context
        return array_merge($defaultContext, $context);
    }
    
    /**
     * Format log entry
     */
    private static function formatLogEntry(string $step, array $context, ?string $status = null, ?string $exceptionClass = null, ?string $exceptionMessage = null, ?string $file = null, ?int $line = null, ?string $traceHash = null): string
    {
        $requestId = self::getRequestId();
        $datetime = date('Y-m-d H:i:s');
        
        $parts = [
            'datetime=' . $datetime,
            'request_id=' . $requestId,
            'step=' . $step,
        ];
        
        // Add context fields
        foreach ($context as $key => $value) {
            if ($value === null) {
                $value = 'null';
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif (is_array($value)) {
                $value = json_encode($value);
            } else {
                $value = str_replace(["\n", "\r", "\t"], [' ', ' ', ' '], (string)$value);
            }
            $parts[] = $key . '=' . $value;
        }
        
        // Add status
        if ($status !== null) {
            $parts[] = 'status=' . $status;
        }
        
        // Add exception info if provided
        if ($exceptionClass !== null) {
            $parts[] = 'exception_class=' . $exceptionClass;
        }
        if ($exceptionMessage !== null) {
            $exceptionMessage = str_replace(["\n", "\r", "\t"], [' ', ' ', ' '], $exceptionMessage);
            $parts[] = 'exception_message=' . $exceptionMessage;
        }
        if ($file !== null) {
            $parts[] = 'file=' . $file;
        }
        if ($line !== null) {
            $parts[] = 'line=' . $line;
        }
        if ($traceHash !== null) {
            $parts[] = 'trace_hash=' . $traceHash;
        }
        
        return implode(' ', $parts) . "\n";
    }
    
    /**
     * Write log entry
     */
    private static function writeLog(string $entry): void
    {
        $logFile = self::getLogFile();
        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log a step
     * 
     * @param string $step Step name (e.g., 'APP_HTML_START', 'VIEW_RENDER_START')
     * @param array $context Additional context data
     */
    public static function log(string $step, array $context = []): void
    {
        $context = self::buildContext($context);
        $entry = self::formatLogEntry($step, $context, 'success');
        self::writeLog($entry);
    }
    
    /**
     * Log an exception
     * 
     * @param string $step Step name (e.g., 'APP_HTML_EXCEPTION')
     * @param \Throwable $e Exception object
     * @param array $context Additional context data
     */
    public static function logException(string $step, \Throwable $e, array $context = []): void
    {
        $context = self::buildContext($context);
        
        // Generate trace hash (first 16 chars of trace string hash)
        $traceString = $e->getTraceAsString();
        $traceHash = substr(md5($traceString), 0, 16);
        
        $entry = self::formatLogEntry(
            $step,
            $context,
            'exception',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $traceHash
        );
        
        self::writeLog($entry);
    }
}

