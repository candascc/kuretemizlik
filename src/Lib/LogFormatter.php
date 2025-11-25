<?php
/**
 * Log Formatter
 * Formats log messages in various formats (JSON, text, etc.)
 */

class LogFormatter
{
    /**
     * Format log entry as JSON
     */
    public static function toJson(string $level, string $message, array $context = []): string
    {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'request_id' => self::getRequestId(),
            'user_id' => self::getUserId(),
            'ip' => self::getIpAddress(),
            'url' => self::getCurrentUrl(),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A'
        ];

        return json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }

    /**
     * Format log entry as text
     */
    public static function toText(string $level, string $message, array $context = []): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        return "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
    }

    /**
     * Format log entry for display
     */
    public static function toHtml(string $level, string $message, array $context = []): string
    {
        $levelColors = [
            LogLevel::DEBUG => '#6c757d',
            LogLevel::INFO => '#0dcaf0',
            LogLevel::WARNING => '#ffc107',
            LogLevel::ERROR => '#dc3545',
            LogLevel::CRITICAL => '#8b0000'
        ];

        $color = $levelColors[$level] ?? '#000';
        $timestamp = date('Y-m-d H:i:s');
        $contextHtml = !empty($context) ? '<pre>' . json_encode($context, JSON_PRETTY_PRINT) . '</pre>' : '';

        return "<div style='color: $color; margin: 5px 0;'>
            <strong>[$timestamp] [$level]</strong> $message
            $contextHtml
        </div>";
    }

    /**
     * Get or generate request ID
     */
    private static function getRequestId(): string
    {
        static $requestId = null;
        
        if ($requestId === null) {
            $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? uniqid('req_', true);
        }
        
        return $requestId;
    }

    /**
     * Get current user ID
     */
    private static function getUserId(): ?int
    {
        if (class_exists('Auth')) {
            return Auth::id();
        }
        return null;
    }

    /**
     * Get client IP address
     */
    private static function getIpAddress(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get current URL
     */
    private static function getCurrentUrl(): string
    {
        if (php_sapi_name() === 'cli') {
            return 'CLI: ' . implode(' ', $_SERVER['argv'] ?? []);
        }

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return "$protocol://$host$uri";
    }

    /**
     * Sanitize context data
     */
    public static function sanitizeContext(array $context): array
    {
        $sensitive = ['password', 'token', 'secret', 'api_key', 'access_token', 'refresh_token'];
        
        array_walk_recursive($context, function(&$value, $key) use ($sensitive) {
            if (is_string($key) && in_array(strtolower($key), $sensitive)) {
                $value = '***REDACTED***';
            }
        });

        return $context;
    }
}

