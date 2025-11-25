<?php
/**
 * System Health Check
 * Monitors system health and performance
 */
class SystemHealth
{
    /**
     * Get comprehensive system health status
     * OPS HARDENING ROUND 1: Enhanced with app version and request ID
     */
    public static function check(): array
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('c'), // ISO 8601 format
            'app_version' => self::getAppVersion(),
            'request_id' => self::getRequestId(),
            'checks' => [],
            'metrics' => []
        ];
        
        // Database check
        $health['checks']['database'] = self::checkDatabase();
        
        // Cache check
        $health['checks']['cache'] = self::checkCache();
        
        // Disk space check
        $health['checks']['disk'] = self::checkDiskSpace();
        
        // Memory check
        $health['checks']['memory'] = self::checkMemory();
        
        // PHP configuration
        $health['checks']['php'] = self::checkPhp();
        
        // Performance metrics
        $health['metrics'] = self::getMetrics();
        
        // Overall status
        $hasIssues = false;
        foreach ($health['checks'] as $check) {
            if (isset($check['status']) && $check['status'] !== 'ok') {
                $hasIssues = true;
                break;
            }
        }
        
        $health['status'] = $hasIssues ? 'degraded' : 'healthy';
        
        return $health;
    }
    
    /**
     * Check database connection
     */
    private static function checkDatabase(): array
    {
        try {
            $db = Database::getInstance();
            $start = microtime(true);
            $db->fetch("SELECT 1");
            $time = round((microtime(true) - $start) * 1000, 2);
            
            return [
                'status' => 'ok',
                'response_time_ms' => $time,
                'message' => 'Database connection successful'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check cache system
     */
    private static function checkCache(): array
    {
        try {
            $key = '__health_check__';
            $value = 'test_' . time();
            
            Cache::set($key, $value, 10);
            $retrieved = Cache::get($key);
            Cache::delete($key);
            
            if ($retrieved === $value) {
                return [
                    'status' => 'ok',
                    'message' => 'Cache system operational'
                ];
            }
            
            return [
                'status' => 'warning',
                'message' => 'Cache read/write mismatch'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache system error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check disk space
     */
    private static function checkDiskSpace(): array
    {
        $free = disk_free_space(__DIR__);
        $total = disk_total_space(__DIR__);
        $used = $total - $free;
        $percentage = ($used / $total) * 100;
        
        $status = 'ok';
        if ($percentage > 90) {
            $status = 'critical';
        } elseif ($percentage > 80) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'free_bytes' => $free,
            'free_formatted' => self::formatBytes($free),
            'used_percentage' => round($percentage, 2),
            'message' => $percentage > 90 ? 'Low disk space' : 'Disk space adequate'
        ];
    }
    
    /**
     * Check memory usage
     */
    private static function checkMemory(): array
    {
        $used = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = ini_get('memory_limit');
        
        $limitBytes = self::parseMemoryLimit($limit);
        $percentage = $limitBytes > 0 ? ($peak / $limitBytes) * 100 : 0;
        
        $status = 'ok';
        if ($percentage > 90) {
            $status = 'critical';
        } elseif ($percentage > 75) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'current_bytes' => $used,
            'current_formatted' => self::formatBytes($used),
            'peak_bytes' => $peak,
            'peak_formatted' => self::formatBytes($peak),
            'limit' => $limit,
            'usage_percentage' => round($percentage, 2)
        ];
    }
    
    /**
     * Check PHP configuration
     */
    private static function checkPhp(): array
    {
        $issues = [];
        
        // Check required extensions
        $required = ['pdo', 'pdo_sqlite', 'json', 'mbstring'];
        $missing = [];
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        if (!empty($missing)) {
            $issues[] = 'Missing extensions: ' . implode(', ', $missing);
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            $issues[] = 'PHP version < 7.4 (recommended: 8.0+)';
        }
        
        return [
            'status' => empty($issues) ? 'ok' : 'warning',
            'php_version' => PHP_VERSION,
            'issues' => $issues
        ];
    }
    
    /**
     * Get performance metrics
     */
    private static function getMetrics(): array
    {
        if (class_exists('PerformanceMonitor')) {
            return PerformanceMonitor::getMetrics();
        }
        
        return [
            'execution_time_ms' => 0,
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'queries' => 0
        ];
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private static function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int)$limit;
        
        switch ($last) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }
        
        return $value;
    }
    
    /**
     * Format bytes to human readable
     */
    private static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Get app version (from config or constant)
     * OPS HARDENING ROUND 1: App version for healthcheck
     */
    private static function getAppVersion(): string
    {
        if (defined('APP_VERSION')) {
            return APP_VERSION;
        }
        
        // Try to read from config
        $configPath = __DIR__ . '/../../config/config.php';
        if (file_exists($configPath)) {
            $config = require $configPath;
            return $config['app_version'] ?? 'unknown';
        }
        
        return 'unknown';
    }
    
    /**
     * Get request ID for correlation
     * OPS HARDENING ROUND 1: Request ID for healthcheck correlation
     */
    private static function getRequestId(): ?string
    {
        if (class_exists('AppErrorHandler')) {
            return AppErrorHandler::getRequestId();
        }
        
        return $_SERVER['HTTP_X_REQUEST_ID'] ?? null;
    }
    
    /**
     * Quick health check (lightweight, for load balancers)
     * OPS HARDENING ROUND 1: Lightweight healthcheck for LB/uptime robots
     */
    public static function quick(): array
    {
        try {
            // Only check database (most critical)
            $db = Database::getInstance();
            $start = microtime(true);
            $db->fetch("SELECT 1");
            $time = round((microtime(true) - $start) * 1000, 2);
            
            return [
                'status' => 'ok',
                'timestamp' => date('c'),
                'db_response_time_ms' => $time
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'timestamp' => date('c'),
                'message' => 'Database check failed'
            ];
        }
    }
}

