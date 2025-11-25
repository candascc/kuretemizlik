<?php
/**
 * Crawl Logger
 * 
 * Centralized logging for crawl operations with log level control
 * Reduces excessive logging in production while maintaining debug capability
 */

class CrawlLogger
{
    /**
     * Log levels
     */
    private const LEVEL_DEBUG = 'DEBUG';
    private const LEVEL_INFO = 'INFO';
    private const LEVEL_WARNING = 'WARNING';
    private const LEVEL_ERROR = 'ERROR';
    
    /**
     * Current log level
     */
    private string $logLevel;
    
    /**
     * Whether logging is enabled
     */
    private bool $enabled;
    
    /**
     * Log file path
     */
    private ?string $logFile;
    
    /**
     * Request ID for correlation
     */
    private string $requestId;
    
    /**
     * Batch log entries (for batch writing)
     */
    private array $batchEntries = [];
    
    /**
     * Constructor
     * 
     * @param string|null $logFile Optional log file path
     * @param string $logLevel Log level (DEBUG, INFO, WARNING, ERROR)
     */
    public function __construct(?string $logFile = null, string $logLevel = 'INFO')
    {
        $this->logFile = $logFile;
        $this->logLevel = strtoupper($logLevel);
        $this->requestId = bin2hex(random_bytes(8));
        
        // Enable error_log output if DEBUG mode is on or log level is DEBUG
        // File logging is always enabled (controlled by shouldLog)
        $this->enabled = (defined('APP_DEBUG') && APP_DEBUG) || 
                        getenv('CRAWL_DEBUG') === 'true' ||
                        $this->logLevel === self::LEVEL_DEBUG;
        
        if ($this->logFile) {
            $this->ensureLogDir();
        }
    }
    
    /**
     * Ensure log directory exists
     */
    private function ensureLogDir(): void
    {
        if ($this->logFile) {
            $logDir = dirname($this->logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
        }
    }
    
    /**
     * Check if log level should be logged
     * 
     * @param string $level
     * @return bool
     */
    private function shouldLog(string $level): bool
    {
        // Always allow logging to file, but control error_log output
        $levels = [self::LEVEL_DEBUG => 0, self::LEVEL_INFO => 1, self::LEVEL_WARNING => 2, self::LEVEL_ERROR => 3];
        $currentLevel = $levels[$this->logLevel] ?? 1;
        $messageLevel = $levels[$level] ?? 1;
        
        return $messageLevel >= $currentLevel;
    }
    
    /**
     * Write log entry
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     */
    private function writeLog(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logEntry = "[{$timestamp}] [{$this->requestId}] [{$level}] {$message}{$contextStr}\n";
        
        if ($this->logFile) {
            @file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
        
        // Only log to error_log if DEBUG mode or ERROR level
        if ($this->enabled && ($level === self::LEVEL_ERROR || $this->logLevel === self::LEVEL_DEBUG)) {
            error_log("CrawlLogger [{$level}]: {$message}" . $contextStr);
        }
    }
    
    /**
     * Log debug message
     * 
     * @param string $message
     * @param array $context
     */
    public function debug(string $message, array $context = []): void
    {
        $this->writeLog(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Log info message
     * 
     * @param string $message
     * @param array $context
     */
    public function log(string $message, array $context = []): void
    {
        $this->writeLog(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log warning message
     * 
     * @param string $message
     * @param array $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->writeLog(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log error message
     * 
     * @param string $message
     * @param array $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->writeLog(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Add log entry to batch
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public function batch(string $level, string $message, array $context = []): void
    {
        if ($this->shouldLog($level)) {
            $this->batchEntries[] = [
                'level' => $level,
                'message' => $message,
                'context' => $context,
                'timestamp' => time(),
            ];
        }
    }
    
    /**
     * Flush batch entries to log file
     */
    public function flushBatch(): void
    {
        if (empty($this->batchEntries) || !$this->logFile) {
            return;
        }
        
        $logContent = '';
        foreach ($this->batchEntries as $entry) {
            $timestamp = date('Y-m-d H:i:s', $entry['timestamp']);
            $contextStr = !empty($entry['context']) ? ' | Context: ' . json_encode($entry['context'], JSON_UNESCAPED_UNICODE) : '';
            $logContent .= "[{$timestamp}] [{$this->requestId}] [{$entry['level']}] {$entry['message']}{$contextStr}\n";
        }
        
        @file_put_contents($this->logFile, $logContent, FILE_APPEND | LOCK_EX);
        $this->batchEntries = [];
    }
    
    /**
     * Get request ID
     * 
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }
}

