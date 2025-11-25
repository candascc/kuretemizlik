<?php
/**
 * Minimal Performance Monitor used by tests
 */
class PerformanceMonitor
{
    private static $timers = [];
    private static $counters = [];

    public static function startTimer(string $name): void
    {
        self::$timers[$name] = microtime(true);
    }

    public static function stopTimer(string $name): float
    {
        if (!isset(self::$timers[$name])) {
            return 0.0;
        }
        $elapsed = microtime(true) - self::$timers[$name];
        unset(self::$timers[$name]);
        return $elapsed;
    }

    public static function measure(callable $fn)
    {
        $start = microtime(true);
        $result = $fn();
        $elapsed = microtime(true) - $start;
        return [$result, $elapsed];
    }

    public static function incrementCounter(string $name, int $by = 1): int
    {
        self::$counters[$name] = (self::$counters[$name] ?? 0) + $by;
        return self::$counters[$name];
    }

    public static function recordMemoryUsage(string $name): int
    {
        $bytes = memory_get_usage(true);
        self::$counters['mem:' . $name] = $bytes;
        return $bytes;
    }

    public static function getSnapshot(): array
    {
        return [
            'timers_active' => array_keys(self::$timers),
            'counters' => self::$counters,
        ];
    }

    public static function convertToBytes(string $val): int
    {
        $val = trim($val);
        $last = strtolower(substr($val, -1));
        $num = (int)$val;
        switch ($last) {
            case 'g': return $num * 1024 * 1024 * 1024;
            case 'm': return $num * 1024 * 1024;
            case 'k': return $num * 1024;
            default: return (int)$val;
        }
    }
}
