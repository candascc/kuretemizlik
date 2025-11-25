<?php
/**
 * Metrics Collection System
 * Collects and stores application metrics for monitoring and analysis
 */

class Metrics
{
    private static $instance = null;
    private $metricsDir;
    private $metrics = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->metricsDir = __DIR__ . '/../../metrics';
        
        if (!is_dir($this->metricsDir)) {
            mkdir($this->metricsDir, 0755, true);
        }
    }

    /**
     * Record a metric
     */
    public static function record(string $name, $value, array $tags = []): void
    {
        $instance = self::getInstance();
        
        $metric = [
            'name' => $name,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => microtime(true)
        ];

        $instance->metrics[] = $metric;
        $instance->persistMetric($metric);
    }

    /**
     * Record timing metric
     */
    public static function timing(string $name, float $duration, array $tags = []): void
    {
        self::record($name, $duration, array_merge($tags, ['type' => 'timing', 'unit' => 'ms']));
    }

    /**
     * Record counter metric
     */
    public static function increment(string $name, int $value = 1, array $tags = []): void
    {
        self::record($name, $value, array_merge($tags, ['type' => 'counter']));
    }

    /**
     * Record gauge metric
     */
    public static function gauge(string $name, $value, array $tags = []): void
    {
        self::record($name, $value, array_merge($tags, ['type' => 'gauge']));
    }

    /**
     * Persist metric to file
     */
    private function persistMetric(array $metric): void
    {
        $date = date('Y-m-d');
        $file = $this->metricsDir . "/metrics-$date.json";
        
        $line = json_encode($metric) . PHP_EOL;
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get metrics for a date range
     */
    public static function get(string $name = null, string $dateFrom = null, string $dateTo = null): array
    {
        $instance = self::getInstance();
        $dateFrom = $dateFrom ?? date('Y-m-d');
        $dateTo = $dateTo ?? date('Y-m-d');
        
        $metrics = [];
        $current = strtotime($dateFrom);
        $end = strtotime($dateTo);

        while ($current <= $end) {
            $date = date('Y-m-d', $current);
            $file = $instance->metricsDir . "/metrics-$date.json";
            
            if (file_exists($file)) {
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                
                foreach ($lines as $line) {
                    $metric = json_decode($line, true);
                    
                    if ($metric && ($name === null || $metric['name'] === $name)) {
                        $metrics[] = $metric;
                    }
                }
            }
            
            $current = strtotime('+1 day', $current);
        }

        return $metrics;
    }

    /**
     * Get aggregated statistics for a metric
     */
    public static function getStats(string $name, string $dateFrom = null, string $dateTo = null): array
    {
        $metrics = self::get($name, $dateFrom, $dateTo);
        
        if (empty($metrics)) {
            return [
                'count' => 0,
                'sum' => 0,
                'avg' => 0,
                'min' => 0,
                'max' => 0,
                'p50' => 0,
                'p95' => 0,
                'p99' => 0
            ];
        }

        $values = array_column($metrics, 'value');
        sort($values);
        
        return [
            'count' => count($values),
            'sum' => array_sum($values),
            'avg' => array_sum($values) / count($values),
            'min' => min($values),
            'max' => max($values),
            'p50' => self::percentile($values, 50),
            'p95' => self::percentile($values, 95),
            'p99' => self::percentile($values, 99)
        ];
    }

    /**
     * Calculate percentile
     */
    private static function percentile(array $values, float $percentile): float
    {
        $count = count($values);
        if ($count === 0) return 0;
        
        $index = ($percentile / 100) * ($count - 1);
        $lower = floor($index);
        $upper = ceil($index);
        
        if ($lower === $upper) {
            return $values[$lower];
        }
        
        $fraction = $index - $lower;
        return $values[$lower] + ($values[$upper] - $values[$lower]) * $fraction;
    }

    /**
     * Clear old metrics
     */
    public static function clearOldMetrics(int $days = 90): int
    {
        $instance = self::getInstance();
        $cutoff = time() - ($days * 86400);
        $deleted = 0;

        $files = glob($instance->metricsDir . '/*');
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}

