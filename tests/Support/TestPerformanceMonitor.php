<?php
/**
 * Test Performance Monitor
 * Tracks test execution time and memory usage
 */

class TestPerformanceMonitor
{
    private static array $metrics = [];
    private static float $startTime = 0;
    private static int $startMemory = 0;

    /**
     * Start monitoring
     */
    public static function start(string $testName): void
    {
        self::$startTime = microtime(true);
        self::$startMemory = memory_get_usage(true);
    }

    /**
     * Stop monitoring and record metrics
     */
    public static function stop(string $testName): array
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $duration = round(($endTime - self::$startTime) * 1000, 2); // milliseconds
        $memoryUsed = round(($endMemory - self::$startMemory) / 1024 / 1024, 2); // MB
        $peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 2); // MB

        $metrics = [
            'test' => $testName,
            'duration_ms' => $duration,
            'memory_used_mb' => $memoryUsed,
            'peak_memory_mb' => $peakMemory,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        self::$metrics[$testName] = $metrics;
        return $metrics;
    }

    /**
     * Get all metrics
     */
    public static function getMetrics(): array
    {
        return self::$metrics;
    }

    /**
     * Get slow tests (above threshold)
     */
    public static function getSlowTests(float $thresholdMs = 1000): array
    {
        return array_filter(self::$metrics, function($metric) use ($thresholdMs) {
            return $metric['duration_ms'] > $thresholdMs;
        });
    }

    /**
     * Get memory-intensive tests (above threshold)
     */
    public static function getMemoryIntensiveTests(float $thresholdMB = 50): array
    {
        return array_filter(self::$metrics, function($metric) use ($thresholdMB) {
            return $metric['peak_memory_mb'] > $thresholdMB;
        });
    }

    /**
     * Generate performance report
     */
    public static function generateReport(): string
    {
        if (empty(self::$metrics)) {
            return "No performance data collected.\n";
        }

        $report = "========================================\n";
        $report .= "Test Performance Report\n";
        $report .= "========================================\n\n";

        $totalDuration = array_sum(array_column(self::$metrics, 'duration_ms'));
        $avgDuration = $totalDuration / count(self::$metrics);
        $maxDuration = max(array_column(self::$metrics, 'duration_ms'));
        $maxMemory = max(array_column(self::$metrics, 'peak_memory_mb'));

        $report .= "Total Tests: " . count(self::$metrics) . "\n";
        $report .= "Total Duration: " . round($totalDuration / 1000, 2) . " seconds\n";
        $report .= "Average Duration: " . round($avgDuration, 2) . " ms\n";
        $report .= "Max Duration: " . round($maxDuration, 2) . " ms\n";
        $report .= "Max Memory: " . round($maxMemory, 2) . " MB\n\n";

        // Slow tests
        $slowTests = self::getSlowTests();
        if (!empty($slowTests)) {
            $report .= "Slow Tests (>1000ms):\n";
            foreach ($slowTests as $test => $metric) {
                $report .= "  - {$test}: " . round($metric['duration_ms'], 2) . " ms\n";
            }
            $report .= "\n";
        }

        // Memory-intensive tests
        $memoryTests = self::getMemoryIntensiveTests();
        if (!empty($memoryTests)) {
            $report .= "Memory-Intensive Tests (>50MB):\n";
            foreach ($memoryTests as $test => $metric) {
                $report .= "  - {$test}: " . round($metric['peak_memory_mb'], 2) . " MB\n";
            }
            $report .= "\n";
        }

        return $report;
    }

    /**
     * Save report to file
     */
    public static function saveReport(string $filePath): void
    {
        $report = self::generateReport();
        file_put_contents($filePath, $report);
    }

    /**
     * Clear all metrics
     */
    public static function clear(): void
    {
        self::$metrics = [];
    }
}

