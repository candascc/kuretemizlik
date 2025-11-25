<?php
/**
 * System Health Check
 * Monitors system health and provides status checks
 */

class HealthCheck
{
    private $checks = [];
    private $results = [];

    /**
     * Register a health check
     */
    public function registerCheck(string $name, callable $check, string $critical = 'WARNING'): void
    {
        $this->checks[$name] = [
            'callable' => $check,
            'critical' => $critical
        ];
    }

    /**
     * Run all health checks
     */
    public function runAll(): array
    {
        $this->results = [];
        $overallStatus = 'HEALTHY';

        foreach ($this->checks as $name => $check) {
            $result = $this->runCheck($name, $check['callable'], $check['critical']);
            $this->results[$name] = $result;

            if ($result['status'] === 'CRITICAL') {
                $overallStatus = 'CRITICAL';
            } elseif ($result['status'] === 'WARNING' && $overallStatus !== 'CRITICAL') {
                $overallStatus = 'WARNING';
            }
        }

        return [
            'status' => $overallStatus,
            'checks' => $this->results,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Run a single health check
     */
    private function runCheck(string $name, callable $check, string $criticalLevel): array
    {
        $startTime = microtime(true);
        
        try {
            $result = call_user_func($check);
            $duration = (microtime(true) - $startTime) * 1000;

            if ($result === true || (is_array($result) && ($result['status'] ?? false))) {
                return [
                    'status' => 'OK',
                    'message' => is_array($result) ? ($result['message'] ?? 'Check passed') : 'Check passed',
                    'duration_ms' => $duration,
                    'data' => is_array($result) ? $result : null
                ];
            } else {
                return [
                    'status' => $criticalLevel,
                    'message' => is_array($result) ? ($result['message'] ?? 'Check failed') : 'Check failed',
                    'duration_ms' => $duration,
                    'data' => is_array($result) ? $result : null
                ];
            }
        } catch (Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            return [
                'status' => 'CRITICAL',
                'message' => 'Check threw exception: ' . $e->getMessage(),
                'duration_ms' => $duration,
                'exception' => get_class($e)
            ];
        }
    }

    /**
     * Get standard health checks
     */
    public static function getStandardChecks(): self
    {
        $instance = new self();

        // Database check
        $instance->registerCheck('database', function() {
            try {
                $db = Database::getInstance();
                $result = $db->fetch("SELECT 1 as test");
                return $result['test'] === 1;
            } catch (Exception $e) {
                return ['status' => false, 'message' => 'Database connection failed: ' . $e->getMessage()];
            }
        }, 'CRITICAL');

        // Disk space check
        $instance->registerCheck('disk_space', function() {
            $free = disk_free_space(__DIR__);
            $total = disk_total_space(__DIR__);
            $usedPercent = (($total - $free) / $total) * 100;

            if ($usedPercent > 90) {
                return ['status' => false, 'message' => 'Disk usage critical: ' . round($usedPercent, 1) . '%'];
            } elseif ($usedPercent > 80) {
                return ['status' => false, 'message' => 'Disk usage warning: ' . round($usedPercent, 1) . '%'];
            }

            return ['status' => true, 'message' => 'Disk usage OK: ' . round($usedPercent, 1) . '%', 'free_gb' => round($free / 1073741824, 2)];
        }, 'WARNING');

        // Memory check
        $instance->registerCheck('memory', function() {
            $current = memory_get_usage(true);
            $limit = ini_get('memory_limit');
            $limitBytes = PerformanceMonitor::convertToBytes($limit);
            $usedPercent = ($current / $limitBytes) * 100;

            if ($usedPercent > 90) {
                return ['status' => false, 'message' => 'Memory usage critical: ' . round($usedPercent, 1) . '%'];
            } elseif ($usedPercent > 80) {
                return ['status' => false, 'message' => 'Memory usage warning: ' . round($usedPercent, 1) . '%'];
            }

            return ['status' => true, 'message' => 'Memory usage OK: ' . round($usedPercent, 1) . '%'];
        }, 'WARNING');

        // Cache directory check
        $instance->registerCheck('cache_directory', function() {
            $cacheDir = __DIR__ . '/../../cache';
            
            if (!is_dir($cacheDir)) {
                return ['status' => false, 'message' => 'Cache directory does not exist'];
            }
            
            if (!is_writable($cacheDir)) {
                return ['status' => false, 'message' => 'Cache directory is not writable'];
            }

            return ['status' => true, 'message' => 'Cache directory OK'];
        }, 'WARNING');

        // Logs directory check
        $instance->registerCheck('logs_directory', function() {
            $logsDir = __DIR__ . '/../../logs';
            
            if (!is_dir($logsDir)) {
                return ['status' => false, 'message' => 'Logs directory does not exist'];
            }
            
            if (!is_writable($logsDir)) {
                return ['status' => false, 'message' => 'Logs directory is not writable'];
            }

            return ['status' => true, 'message' => 'Logs directory OK'];
        }, 'WARNING');

        // PHP version check
        $instance->registerCheck('php_version', function() {
            $version = phpversion();
            $minVersion = '8.0.0';
            
            if (version_compare($version, $minVersion, '<')) {
                return ['status' => false, 'message' => "PHP version $version is below minimum $minVersion"];
            }

            return ['status' => true, 'message' => "PHP version OK: $version"];
        }, 'CRITICAL');

        // Required extensions check
        $instance->registerCheck('php_extensions', function() {
            $required = ['pdo', 'pdo_sqlite', 'json', 'mbstring'];
            $missing = [];

            foreach ($required as $ext) {
                if (!extension_loaded($ext)) {
                    $missing[] = $ext;
                }
            }

            if (!empty($missing)) {
                return ['status' => false, 'message' => 'Missing extensions: ' . implode(', ', $missing)];
            }

            return ['status' => true, 'message' => 'All required extensions loaded'];
        }, 'CRITICAL');

        return $instance;
    }

    /**
     * Get results
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Quick health check
     */
    public static function quick(): array
    {
        $checks = self::getStandardChecks();
        return $checks->runAll();
    }
}

