<?php
/**
 * Performance Baseline Measurement Tool
 * 
 * Measures and reports key performance metrics:
 * - OPcache hit rate
 * - Response time
 * - Memory usage
 * - Database query performance
 * 
 * Self-Audit Fix: P2.2 - Performance measurement
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';

class PerformanceBaseline
{
    private $results = [];
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Run all performance measurements
     */
    public function measure()
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║         PERFORMANCE BASELINE MEASUREMENT TOOL                  ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "Self-Audit Fix: Measuring actual performance metrics\n";
        echo "Started: " . date('Y-m-d H:i:s') . "\n";
        echo "\n";
        echo "══════════════════════════════════════════════════════════════════\n\n";
        
        $this->measureOPcache();
        $this->measureMemoryUsage();
        $this->measureResponseTime();
        $this->measureDatabasePerformance();
        
        $this->generateReport();
        
        return true;
    }
    
    /**
     * Measure OPcache performance
     */
    private function measureOPcache()
    {
        echo "1. OPcache Metrics\n";
        echo "   ────────────────────\n";
        
        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status();
            
            if ($status === false) {
                echo "   ⚠️  OPcache is disabled\n";
                echo "   Recommendation: Enable OPcache in php.ini\n\n";
                
                $this->results['opcache'] = [
                    'enabled' => false,
                    'hit_rate' => 0,
                    'memory_usage' => 0,
                    'status' => 'disabled'
                ];
                return;
            }
            
            $hits = $status['opcache_statistics']['hits'];
            $misses = $status['opcache_statistics']['misses'];
            $total = $hits + $misses;
            $hitRate = $total > 0 ? ($hits / $total) * 100 : 0;
            
            $memoryUsed = $status['memory_usage']['used_memory'];
            $memoryFree = $status['memory_usage']['free_memory'];
            $memoryTotal = $memoryUsed + $memoryFree;
            $memoryUsagePercent = $memoryTotal > 0 ? ($memoryUsed / $memoryTotal) * 100 : 0;
            
            echo "   ✅ OPcache Status: ENABLED\n";
            echo "   Hit Rate: " . round($hitRate, 2) . "%\n";
            echo "   Hits: " . number_format($hits) . "\n";
            echo "   Misses: " . number_format($misses) . "\n";
            echo "   Memory Used: " . $this->formatBytes($memoryUsed) . " (" . round($memoryUsagePercent, 2) . "%)\n";
            echo "   Cached Scripts: " . $status['opcache_statistics']['num_cached_scripts'] . "\n";
            
            // Performance evaluation
            if ($hitRate >= 95) {
                echo "   🎯 Excellent hit rate!\n";
            } elseif ($hitRate >= 80) {
                echo "   ✅ Good hit rate\n";
            } else {
                echo "   ⚠️  Low hit rate - consider optimization\n";
            }
            
            $this->results['opcache'] = [
                'enabled' => true,
                'hit_rate' => $hitRate,
                'hits' => $hits,
                'misses' => $misses,
                'memory_used' => $memoryUsed,
                'memory_usage_percent' => $memoryUsagePercent,
                'cached_scripts' => $status['opcache_statistics']['num_cached_scripts'],
                'status' => $hitRate >= 80 ? 'good' : 'needs_optimization'
            ];
            
        } else {
            echo "   ⚠️  OPcache functions not available\n";
            echo "   Check PHP configuration\n";
            
            $this->results['opcache'] = [
                'enabled' => false,
                'hit_rate' => 0,
                'status' => 'not_available'
            ];
        }
        
        echo "\n";
    }
    
    /**
     * Measure memory usage
     */
    private function measureMemoryUsage()
    {
        echo "2. Memory Usage\n";
        echo "   ─────────────────\n";
        
        $memoryUsage = memory_get_usage();
        $memoryPeakUsage = memory_get_peak_usage();
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        $memoryUsagePercent = $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) * 100 : 0;
        
        echo "   Current: " . $this->formatBytes($memoryUsage) . "\n";
        echo "   Peak: " . $this->formatBytes($memoryPeakUsage) . "\n";
        echo "   Limit: " . $this->formatBytes($memoryLimit) . "\n";
        echo "   Usage: " . round($memoryUsagePercent, 2) . "% of limit\n";
        
        if ($memoryUsagePercent < 50) {
            echo "   ✅ Memory usage is healthy\n";
        } elseif ($memoryUsagePercent < 80) {
            echo "   ⚠️  Memory usage is moderate\n";
        } else {
            echo "   🔴 Memory usage is high - optimize!\n";
        }
        
        $this->results['memory'] = [
            'current' => $memoryUsage,
            'peak' => $memoryPeakUsage,
            'limit' => $memoryLimit,
            'usage_percent' => $memoryUsagePercent,
            'status' => $memoryUsagePercent < 50 ? 'healthy' : ($memoryUsagePercent < 80 ? 'moderate' : 'high')
        ];
        
        echo "\n";
    }
    
    /**
     * Measure response time
     */
    private function measureResponseTime()
    {
        echo "3. Response Time Benchmark\n";
        echo "   ─────────────────────────────\n";
        
        $iterations = 10;
        $times = [];
        
        echo "   Running {$iterations} iterations...\n";
        
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            
            // Simulate typical application operations
            $this->simulateTypicalRequest();
            
            $end = microtime(true);
            $time = ($end - $start) * 1000; // Convert to ms
            $times[] = $time;
            
            echo "   Iteration " . ($i + 1) . ": " . round($time, 2) . " ms\n";
        }
        
        $avgTime = array_sum($times) / count($times);
        $minTime = min($times);
        $maxTime = max($times);
        
        echo "\n";
        echo "   Average: " . round($avgTime, 2) . " ms\n";
        echo "   Min: " . round($minTime, 2) . " ms\n";
        echo "   Max: " . round($maxTime, 2) . " ms\n";
        
        if ($avgTime < 100) {
            echo "   🚀 Excellent response time!\n";
        } elseif ($avgTime < 250) {
            echo "   ✅ Good response time\n";
        } elseif ($avgTime < 500) {
            echo "   ⚠️  Moderate response time\n";
        } else {
            echo "   🔴 Slow response time - needs optimization\n";
        }
        
        $this->results['response_time'] = [
            'average_ms' => $avgTime,
            'min_ms' => $minTime,
            'max_ms' => $maxTime,
            'iterations' => $iterations,
            'status' => $avgTime < 250 ? 'good' : ($avgTime < 500 ? 'moderate' : 'slow')
        ];
        
        echo "\n";
    }
    
    /**
     * Measure database performance
     */
    private function measureDatabasePerformance()
    {
        echo "4. Database Performance\n";
        echo "   ──────────────────────────\n";
        
        $queries = [
            'Simple SELECT' => "SELECT COUNT(*) as c FROM users",
            'JOIN query' => "SELECT j.*, c.name FROM jobs j LEFT JOIN customers c ON j.customer_id = c.id LIMIT 10",
            'Aggregate query' => "SELECT COUNT(*) as total, status FROM jobs GROUP BY status"
        ];
        
        $queryTimes = [];
        
        foreach ($queries as $name => $sql) {
            $start = microtime(true);
            
            try {
                $this->db->fetchAll($sql);
                $end = microtime(true);
                $time = ($end - $start) * 1000; // Convert to ms
                
                echo "   {$name}: " . round($time, 2) . " ms\n";
                $queryTimes[$name] = $time;
                
            } catch (Exception $e) {
                echo "   {$name}: ERROR - " . $e->getMessage() . "\n";
                $queryTimes[$name] = -1;
            }
        }
        
        $avgQueryTime = array_sum(array_filter($queryTimes, fn($t) => $t >= 0)) / count(array_filter($queryTimes, fn($t) => $t >= 0));
        
        echo "\n";
        echo "   Average Query Time: " . round($avgQueryTime, 2) . " ms\n";
        
        if ($avgQueryTime < 10) {
            echo "   🚀 Excellent database performance!\n";
        } elseif ($avgQueryTime < 50) {
            echo "   ✅ Good database performance\n";
        } else {
            echo "   ⚠️  Consider database optimization\n";
        }
        
        $this->results['database'] = [
            'queries' => $queryTimes,
            'average_ms' => $avgQueryTime,
            'status' => $avgQueryTime < 50 ? 'good' : 'needs_optimization'
        ];
        
        echo "\n";
    }
    
    /**
     * Simulate typical request
     */
    private function simulateTypicalRequest()
    {
        // Simulate typical application operations
        $this->db->fetch("SELECT COUNT(*) as c FROM users");
        $this->db->fetch("SELECT COUNT(*) as c FROM jobs");
        
        // Simulate some processing
        $data = [];
        for ($i = 0; $i < 100; $i++) {
            $data[] = md5($i);
        }
    }
    
    /**
     * Generate comprehensive report
     */
    private function generateReport()
    {
        echo "══════════════════════════════════════════════════════════════════\n\n";
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║                    PERFORMANCE REPORT                          ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        
        // Overall score
        $scores = [
            'opcache' => $this->getScore($this->results['opcache']['status']),
            'memory' => $this->getScore($this->results['memory']['status']),
            'response_time' => $this->getScore($this->results['response_time']['status']),
            'database' => $this->getScore($this->results['database']['status'])
        ];
        
        $overallScore = round(array_sum($scores) / count($scores), 1);
        
        echo "Overall Performance Score: {$overallScore}/10\n\n";
        
        if ($overallScore >= 8) {
            echo "✅ System performance is EXCELLENT\n";
        } elseif ($overallScore >= 6) {
            echo "✅ System performance is GOOD\n";
        } elseif ($overallScore >= 4) {
            echo "⚠️  System performance is MODERATE - consider optimizations\n";
        } else {
            echo "🔴 System performance needs IMMEDIATE optimization\n";
        }
        
        echo "\n";
        echo "Individual Scores:\n";
        echo "  OPcache: {$scores['opcache']}/10 ({$this->results['opcache']['status']})\n";
        echo "  Memory: {$scores['memory']}/10 ({$this->results['memory']['status']})\n";
        echo "  Response Time: {$scores['response_time']}/10 ({$this->results['response_time']['status']})\n";
        echo "  Database: {$scores['database']}/10 ({$this->results['database']['status']})\n";
        
        echo "\n";
        
        // Save JSON report
        $this->saveJsonReport($overallScore, $scores);
    }
    
    /**
     * Get numeric score from status
     */
    private function getScore($status)
    {
        $scoreMap = [
            'excellent' => 10,
            'good' => 8,
            'healthy' => 8,
            'moderate' => 6,
            'needs_optimization' => 4,
            'high' => 3,
            'slow' => 2,
            'disabled' => 0,
            'not_available' => 0
        ];
        
        return $scoreMap[$status] ?? 5;
    }
    
    /**
     * Save JSON report
     */
    private function saveJsonReport($overallScore, $scores)
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'overall_score' => $overallScore,
            'individual_scores' => $scores,
            'metrics' => $this->results
        ];
        
        $jsonPath = __DIR__ . '/../../performance_baseline_report.json';
        file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT));
        
        echo "JSON Report: {$jsonPath}\n";
        echo "\n";
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Parse memory limit string
     */
    private function parseMemoryLimit($limit)
    {
        if ($limit == -1) {
            return PHP_INT_MAX;
        }
        
        $value = (int)$limit;
        $unit = strtoupper(substr($limit, -1));
        
        switch ($unit) {
            case 'G':
                $value *= 1024;
            case 'M':
                $value *= 1024;
            case 'K':
                $value *= 1024;
        }
        
        return $value;
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli') {
    $baseline = new PerformanceBaseline();
    $baseline->measure();
}

