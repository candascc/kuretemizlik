<?php

/**
 * Performance Controller
 */
class PerformanceController
{
    private $cacheManager;
    private $queryOptimizer;

    public function __construct()
    {
        $this->cacheManager = CacheManager::getInstance();
        $this->queryOptimizer = new QueryOptimizer();
    }

    /**
     * Performance dashboard
     */
    public function index()
    {
        try {
            if (class_exists('Logger')) { Logger::info('PerformanceController@index start'); }

            Auth::require();
            Auth::requireAdmin();

            $cacheStats = [
                'total_files' => 0,
                'valid_files' => 0,
                'expired_files' => 0,
                'total_size' => 0,
                'total_size_mb' => 0
            ];
            $slowQueries = [];
            $metrics = [
                'cache_hit_ratio' => 0,
                'avg_query_time' => 0,
                'memory_usage' => ['current' => '0 B', 'peak' => '0 B', 'current_mb' => 0, 'peak_mb' => 0],
                'disk_usage' => ['total' => '0 B', 'used' => '0 B', 'free' => '0 B', 'percentage' => 0]
            ];

            // Populate data defensively
            try {
                $cacheStats = $this->cacheManager->getStats();
            } catch (Throwable $e) {
                if (class_exists('Logger')) { Logger::warning('Perf cacheStats error', ['error' => $e->getMessage()]); }
            }
            try {
                $slowQueries = $this->getSlowQueriesFromDatabase();
            } catch (Throwable $e) {
                if (class_exists('Logger')) { Logger::warning('Perf slowQueries error', ['error' => $e->getMessage()]); }
            }
            try {
                $metrics = [
                    'cache_hit_ratio' => $this->calculateCacheHitRatio(),
                    'avg_query_time' => $this->calculateAvgQueryTime(),
                    'memory_usage' => $this->getMemoryUsage(),
                    'disk_usage' => $this->getDiskUsage()
                ];
            } catch (Throwable $e) {
                if (class_exists('Logger')) { Logger::warning('Perf metrics error', ['error' => $e->getMessage()]); }
            }

            if (class_exists('Logger')) {
                Logger::info('PerformanceController@index data prepared', [
                    'cache_stats' => $cacheStats,
                    'metrics' => $metrics,
                    'slow_queries_count' => is_array($slowQueries) ? count($slowQueries) : 0,
                ]);
            }

            echo View::renderWithLayout('performance/index', [
                'title' => 'Performans İzleme',
                'cache_stats' => $cacheStats,
                'slow_queries' => $slowQueries,
                'metrics' => $metrics
            ]);

            if (class_exists('Logger')) { Logger::info('PerformanceController@index end'); }
        } catch (Throwable $e) {
            // Ensure we never return a raw 500 without a styled error page
            if (class_exists('Logger')) {
                Logger::exception($e, ['controller' => 'PerformanceController@index']);
            } else {
                error_log('PerformanceController@index fatal: ' . $e->getMessage());
            }
            View::error('Performans sayfası yüklenemedi', 500, $e->getMessage());
        }
    }
    
    /**
     * Get slow queries from database
     */
    private function getSlowQueriesFromDatabase(): array
    {
        try {
            $db = Database::getInstance();
            $pdo = $db->getPdo();
            
            // Get recent slow queries from slow_queries table
            $slowQueries = $pdo->query("
                SELECT query, params, duration_ms, rows, occurred_at, path, method, ip 
                FROM slow_queries 
                ORDER BY occurred_at DESC 
                LIMIT 50
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            return $slowQueries ?: [];
        } catch (Exception $e) {
            error_log("Failed to fetch slow queries: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cache management
     */
    public function cache()
    {
        Auth::require();
        Auth::requireAdmin();

        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'clear':
                $this->cacheManager->clear();
                Utils::flash('success', 'Cache cleared successfully');
                break;
                
            case 'clean_expired':
                $cleaned = $this->cacheManager->cleanExpired();
                Utils::flash('success', "Cleaned $cleaned expired cache files");
                break;
                
            case 'optimize_indexes':
                QueryOptimizer::optimizeIndexes();
                Utils::flash('success', 'Database indexes optimized');
                break;
        }

        redirect(base_url('/performance'));
    }

    /**
     * Database optimization
     */
    public function optimize()
    {
        Auth::require();
        Auth::requireAdmin();

        try {
            // Optimize indexes
            QueryOptimizer::optimizeIndexes();
            
            // Clean expired cache
            $cleaned = $this->cacheManager->cleanExpired();
            
            // Analyze database
            $this->analyzeDatabase();
            
            Utils::flash('success', 'Database optimization completed successfully');
        } catch (Exception $e) {
            Utils::flash('error', 'Optimization failed: ' . Utils::safeExceptionMessage($e));
        }

        redirect(base_url('/performance'));
    }

    /**
     * Get performance metrics API
     * ROUND 18: Public endpoint for frontend status bar (no auth required)
     * ROUND 49: Hardened with JSON-only guarantee and safe defaults
     */
    public function metrics()
    {
        // ===== LOGIN_500_PATHC: Log API start =====
        if (class_exists('PathCLogger')) {
            PathCLogger::log('API_METRICS_START', ['path' => '/performance/metrics']);
        }
        // ===== LOGIN_500_PATHC END =====
        
        // ROUND 49: Log start
        $log_file = __DIR__ . '/../../logs/performance_r49.log';
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        $request_id = uniqid('req_', true);
        @file_put_contents($log_file, "[{$timestamp}] [{$request_id}] METRICS_START\n", FILE_APPEND | LOCK_EX);
        
        $isInternalRequest = defined('KUREAPP_INTERNAL_REQUEST') && KUREAPP_INTERNAL_REQUEST;
        $initialObLevel = ob_get_level();
        if (!$isInternalRequest) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
        ob_start();
        
        try {
            // ROUND 49: Safe defaults
            $metrics = [
                'cache' => [
                    'hit_ratio' => 0.85,
                    'cache_hit_ratio' => 0.85
                ],
                'queries' => [
                    'slow_queries' => [] // Don't return slow queries for public endpoint (performance & security)
                ],
                'system' => [
                    'memory_usage' => [
                        'current' => '0 B',
                        'peak' => '0 B',
                        'current_mb' => 0,
                        'peak_mb' => 0
                    ],
                    'disk_usage' => [
                        'total' => '0 B',
                        'used' => '0 B',
                        'free' => '0 B',
                        'percentage' => 0
                    ]
                ]
            ];
            
            // ROUND 49: Try to get real metrics with safe fallbacks
            try {
                $cacheHitRatio = $this->calculateCacheHitRatio();
                $metrics['cache']['hit_ratio'] = $cacheHitRatio;
                $metrics['cache']['cache_hit_ratio'] = $cacheHitRatio;
            } catch (Throwable $e) {
                @file_put_contents($log_file, "[{$timestamp}] [{$request_id}] CACHE_HIT_RATIO_ERROR: {$e->getMessage()}, file={$e->getFile()}, line={$e->getLine()}\n", FILE_APPEND | LOCK_EX);
            }
            
            try {
                $memoryUsage = $this->getMemoryUsage();
                if (is_array($memoryUsage)) {
                    $metrics['system']['memory_usage'] = $memoryUsage;
                }
            } catch (Throwable $e) {
                @file_put_contents($log_file, "[{$timestamp}] [{$request_id}] MEMORY_USAGE_ERROR: {$e->getMessage()}, file={$e->getFile()}, line={$e->getLine()}\n", FILE_APPEND | LOCK_EX);
            }
            
            try {
                $diskUsage = $this->getDiskUsage();
                if (is_array($diskUsage)) {
                    $metrics['system']['disk_usage'] = $diskUsage;
                }
            } catch (Throwable $e) {
                @file_put_contents($log_file, "[{$timestamp}] [{$request_id}] DISK_USAGE_ERROR: {$e->getMessage()}, file={$e->getFile()}, line={$e->getLine()}\n", FILE_APPEND | LOCK_EX);
            }
            
            // ROUND 49: Success response (JSON-only)
            while (ob_get_level() > $initialObLevel) {
                ob_end_clean();
            }
            if (!$isInternalRequest && !headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(200);
            }
            // ===== LOGIN_500_PATHC: Log API success =====
            if (class_exists('PathCLogger')) {
                PathCLogger::log('API_METRICS_SUCCESS', ['path' => '/performance/metrics']);
            }
            // ===== LOGIN_500_PATHC END =====
            
            // Only output JSON for external requests
            if (!$isInternalRequest) {
                echo json_encode([
                    'success' => true,
                    'metrics' => $metrics,
                    'error' => null
                ], JSON_UNESCAPED_SLASHES);
            }
            @file_put_contents($log_file, "[{$timestamp}] [{$request_id}] METRICS_SUCCESS\n", FILE_APPEND | LOCK_EX);
            if ($isInternalRequest) {
                return;
            }
            exit;
            
        } catch (Throwable $e) {
            // ===== LOGIN_500_PATHC: Log exception =====
            if (class_exists('PathCLogger')) {
                PathCLogger::logException('API_METRICS_EXCEPTION', $e, ['path' => '/performance/metrics']);
            }
            // ===== LOGIN_500_PATHC END =====
            
            // ROUND 49: Log full exception
            $error_msg = $e->getMessage();
            $error_file = $e->getFile();
            $error_line = $e->getLine();
            $error_trace = substr($e->getTraceAsString(), 0, 1000);
            @file_put_contents($log_file, "[{$timestamp}] [{$request_id}] METRICS_EXCEPTION: message={$error_msg}, file={$error_file}, line={$error_line}, trace={$error_trace}\n", FILE_APPEND | LOCK_EX);
            
            // ROUND 49: Error response (JSON-only, 200 status)
            while (ob_get_level() > $initialObLevel) {
                ob_end_clean();
            }
            if (!$isInternalRequest && !headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(200); // 500 yerine 200 (JSON error)
            }
            // Only output JSON for external requests
            if (!$isInternalRequest) {
                echo json_encode([
                    'success' => false,
                    'metrics' => null,
                    'error' => 'internal_error'
                ], JSON_UNESCAPED_SLASHES);
            }
            if ($isInternalRequest) {
                return;
            }
            exit;
        }
    }

    /**
     * Calculate cache hit ratio
     */
    private function calculateCacheHitRatio()
    {
        // This would need to be implemented with query tracking
        // For now, return a placeholder
        return 0.85; // 85% hit ratio
    }

    /**
     * Calculate average query time
     */
    private function calculateAvgQueryTime()
    {
        $slowQueries = $this->getSlowQueriesFromDatabase();
        if (empty($slowQueries)) {
            return 0.05; // 50ms default
        }

        $totalTime = array_sum(array_column($slowQueries, 'duration_ms'));
        return ($totalTime / count($slowQueries)) / 1000; // Convert ms to seconds
    }

    /**
     * Get memory usage
     * ROUND 49: Hardened with safe defaults
     */
    private function getMemoryUsage()
    {
        try {
            $memory = memory_get_usage(true);
            $peak = memory_get_peak_usage(true);
            
            // ROUND 49: Safe defaults
            $memory = ($memory !== false && $memory !== null && is_numeric($memory)) ? (int)$memory : 0;
            $peak = ($peak !== false && $peak !== null && is_numeric($peak)) ? (int)$peak : 0;
            
            return [
                'current' => $this->formatBytes($memory),
                'peak' => $this->formatBytes($peak),
                'current_mb' => round($memory / 1024 / 1024, 2),
                'peak_mb' => round($peak / 1024 / 1024, 2)
            ];
        } catch (Throwable $e) {
            // ROUND 49: Safe fallback
            return [
                'current' => '0 B',
                'peak' => '0 B',
                'current_mb' => 0,
                'peak_mb' => 0
            ];
        }
    }

    /**
     * Get disk usage
     * ROUND 49: Hardened with safe defaults and division-by-zero protection
     */
    private function getDiskUsage()
    {
        try {
            $total = @disk_total_space('.');
            $free = @disk_free_space('.');
            
            // ROUND 49: Safe defaults
            $total = ($total !== false && $total !== null && is_numeric($total)) ? (float)$total : 0;
            $free = ($free !== false && $free !== null && is_numeric($free)) ? (float)$free : 0;
            $used = max(0, $total - $free);

            // ROUND 49: Division-by-zero protection
            $percentage = ($total > 0)
                ? round(($used / $total) * 100, 2)
                : 0;
            
            return [
                'total' => $this->formatBytes($total),
                'used' => $this->formatBytes($used),
                'free' => $this->formatBytes($free),
                'percentage' => $percentage
            ];
        } catch (Throwable $e) {
            // ROUND 49: Safe fallback
            return [
                'total' => '0 B',
                'used' => '0 B',
                'free' => '0 B',
                'percentage' => 0
            ];
        }
    }

    /**
     * Get load average
     */
    private function getLoadAverage()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => $load[0],
                '5min' => $load[1],
                '15min' => $load[2]
            ];
        }
        
        return ['1min' => 0, '5min' => 0, '15min' => 0];
    }

    /**
     * Get total queries count
     */
    private function getTotalQueries()
    {
        // This would need to be implemented with query tracking
        return 0;
    }

    /**
     * Analyze database
     */
    private function analyzeDatabase()
    {
        $db = Database::getInstance();
        
        // Analyze all tables
        $tables = ['jobs', 'customers', 'services', 'money_entries', 'addresses', 'activity_logs'];
        
        foreach ($tables as $table) {
            try {
                $db->execute("ANALYZE $table");
            } catch (Exception $e) {
                error_log("Failed to analyze table $table: " . $e->getMessage());
            }
        }
    }

    /**
     * Format bytes to human readable
     * ROUND 49: Hardened with null/false/type checks
     */
    private function formatBytes($bytes, $precision = 2)
    {
        // ROUND 49: Null/false check
        if ($bytes === null || $bytes === false) {
            $bytes = 0;
        }
        
        // ROUND 49: Type check
        if (!is_numeric($bytes)) {
            $bytes = 0;
        }
        
        $bytes = (float)$bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        // ROUND 49: Safe loop
        while ($bytes > 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        // ROUND 49: Array bounds check
        if ($i >= count($units)) {
            $i = count($units) - 1;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
