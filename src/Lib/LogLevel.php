<?php
/**
 * Log Level Constants
 * Defines standard logging levels for the application
 */

class LogLevel
{
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';

    /**
     * Get numeric priority for log level
     */
    public static function getPriority(string $level): int
    {
        $priorities = [
            self::DEBUG => 0,
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3,
            self::CRITICAL => 4
        ];

        return $priorities[$level] ?? 0;
    }

    /**
     * Check if level is valid
     */
    public static function isValid(string $level): bool
    {
        return in_array($level, [
            self::DEBUG,
            self::INFO,
            self::WARNING,
            self::ERROR,
            self::CRITICAL
        ]);
    }

    /**
     * Get all log levels
     */
    public static function all(): array
    {
        return [
            self::DEBUG,
            self::INFO,
            self::WARNING,
            self::ERROR,
            self::CRITICAL
        ];
    }
}

