<?php
/**
 * Logger
 * Centralized logging system with multiple log levels
 */
class Logger
{
    private static $logDir = __DIR__ . '/../../logs';
    private static $levels = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'];
    
    /**
     * Ensure log directory exists
     */
    private static function ensureLogDir(): void
    {
        if (!is_dir(self::$logDir)) {
            try {
                if (!mkdir(self::$logDir, 0755, true)) {
                    // Directory creation failed, will fall back to PHP error_log
                    return;
                }
            } catch (Exception $e) {
                // Directory creation failed, will fall back to PHP error_log
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("Failed to create log directory: " . $e->getMessage());
                }
                return;
            }
        }
        
        // Check if directory is writable, if not, silently fail (will fall back to PHP error_log)
        if (!is_writable(self::$logDir)) {
            // Directory exists but is not writable - logging will fall back to PHP error_log
            return;
        }
    }
    
    /**
     * Configure logger (for compatibility with existing code)
     */
    public static function configure(array $config = []): void
    {
        // This method exists for compatibility, but our simple logger doesn't need complex configuration
        // The configuration is done via APP_DEBUG constant
        if (isset($config['log_dir'])) {
            self::$logDir = $config['log_dir'];
        }
    }
    
    /**
     * Write log entry
     * OPS HARDENING ROUND 1: Add request ID for log correlation
     */
    private static function write(string $level, string $message, array $context = []): void
    {
        $isDebug = defined('APP_DEBUG') ? APP_DEBUG : true;
        if (!$isDebug && $level === 'DEBUG') {
            return;
        }
        
        self::ensureLogDir();
        
        // OPS HARDENING: Get request ID for correlation
        $requestId = null;
        if (class_exists('AppErrorHandler')) {
            $requestId = AppErrorHandler::getRequestId();
        } elseif (isset($_SERVER['HTTP_X_REQUEST_ID'])) {
            $requestId = $_SERVER['HTTP_X_REQUEST_ID'];
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $requestIdStr = $requestId ? " [req:{$requestId}]" : '';
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logEntry = "[{$timestamp}] [{$level}]{$requestIdStr} {$message}{$contextStr}\n";
        
        // Only try to write to file if directory is writable
        if (is_dir(self::$logDir) && is_writable(self::$logDir)) {
            // Write to daily log file
            $logFile = self::$logDir . '/app_' . date('Y-m-d') . '.log';
            try {
                file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
            } catch (Exception $e) {
                // File write failed, will fall back to PHP error_log
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("Failed to write to log file: " . $e->getMessage());
                }
            }
            
            // Also write to level-specific file for critical errors
            if ($level === 'ERROR' || $level === 'CRITICAL') {
                $errorLogFile = self::$logDir . '/errors_' . date('Y-m-d') . '.log';
                try {
                    file_put_contents($errorLogFile, $logEntry, FILE_APPEND | LOCK_EX);
                } catch (Exception $e) {
                    // File write failed, will fall back to PHP error_log
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Failed to write to error log file: " . $e->getMessage());
                    }
                }
            }
        }
        
        // Always log to PHP error log for production monitoring (works even if file write fails)
        if (!$isDebug && ($level === 'ERROR' || $level === 'CRITICAL')) {
            error_log("[{$level}] {$message}");
        }
    }
    
    /**
     * Log debug message
     */
    public static function debug(string $message, array $context = []): void
    {
        self::write('DEBUG', $message, $context);
    }
    
    /**
     * Log info message
     */
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }
    
    /**
     * Log warning message
     */
    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }
    
    /**
     * Log error message
     */
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }
    
    /**
     * Log critical message
     */
    public static function critical(string $message, array $context = []): void
    {
        self::write('CRITICAL', $message, $context);
    }
    
    /**
     * Log exception (compat helper expected by tests)
     */
    public static function exception(\Throwable $e, array $context = []): void
    {
        $data = array_merge([
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], $context);
        self::write('ERROR', 'Exception', $data);
    }
    
    /**
     * Log query execution
     */
    public static function query(string $sql, array $params = [], float $executionTime = 0, int $rowCount = 0): void
    {
        $isDebug = defined('APP_DEBUG') ? APP_DEBUG : true;
        if (!$isDebug) {
            return;
        }
        
        $message = "Query executed in " . round($executionTime * 1000, 2) . "ms";
        $context = [
            'sql' => substr($sql, 0, 200),
            'params' => $params,
            'rows' => $rowCount
        ];
        
        self::write('DEBUG', $message, $context);
    }
    
    /**
     * Get recent log entries
     */
    public static function getRecent(int $limit = 100, string $level = null): array
    {
        self::ensureLogDir();
        $logFile = self::$logDir . '/app_' . date('Y-m-d') . '.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        try {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                return [];
            }
            
            $lines = array_reverse($lines);
            
            $entries = [];
            foreach ($lines as $line) {
                if (preg_match('/\[([^\]]+)\] \[([^\]]+)\] (.+)/', $line, $matches)) {
                    $entryLevel = $matches[2];
                    if ($level === null || $entryLevel === $level) {
                        $entries[] = [
                            'timestamp' => $matches[1],
                            'level' => $entryLevel,
                            'message' => $matches[3]
                        ];
                        
                        if (count($entries) >= $limit) {
                            break;
                        }
                    }
                }
            }
            
            return $entries;
        } catch (Exception $e) {
            // Silently fail if log file cannot be read
            return [];
        }
    }
    
    /**
     * Clear old log files (keep last N days)
     */
    public static function cleanupOldLogs(int $daysToKeep = 30): int
    {
        self::ensureLogDir();
        $files = glob(self::$logDir . '/app_*.log');
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        $deleted = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                try {
                    unlink($file);
                    $deleted++;
                } catch (Exception $e) {
                    if (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("Failed to delete old log file: " . $e->getMessage());
                    }
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Get logging statistics
     */
    public static function getStatistics(): array
    {
        self::ensureLogDir();
        
        $stats = [
            'total_logs' => 0,
            'by_level' => [],
            'today_count' => 0,
            'log_files' => 0,
            'total_size' => 0
        ];
        
        // Get today's log file
        $todayFile = self::$logDir . '/app_' . date('Y-m-d') . '.log';
        if (file_exists($todayFile)) {
            $lines = file($todayFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $stats['today_count'] = count($lines);
            $stats['total_size'] += filesize($todayFile);
            
            // Count by level
            foreach ($lines as $line) {
                if (preg_match('/\[([^\]]+)\] \[([^\]]+)\]/', $line, $matches)) {
                    $level = $matches[2];
                    $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
                    $stats['total_logs']++;
                }
            }
        }
        
        // Count log files
        $files = glob(self::$logDir . '/app_*.log');
        $stats['log_files'] = count($files ?? []);
        
        return $stats;
    }
    
    /**
     * Get recent log entries (alias for getRecent)
     */
    public static function getRecentLogs(int $limit = 100, string $level = null): array
    {
        return self::getRecent($limit, $level);
    }
}
