<?php
declare(strict_types=1);

/**
 * Memory Cleanup Helper
 * 
 * Phase 3.3: Memory Leak Prevention - Centralized cleanup operations
 * 
 * Provides methods to clean up sessions, cache, and other resources to prevent
 * memory leaks. All cleanup operations respect execution time limits to avoid
 * blocking the main application flow.
 * 
 * @package App\Lib
 * @since Phase 3.3
 */
class MemoryCleanupHelper
{
    /**
     * Clean up expired cache entries
     * 
     * Removes expired cache files from the cache directory. Respects execution time
     * limits to prevent blocking. Returns statistics about the cleanup operation.
     * 
     * @param int $maxExecutionTime Maximum execution time in seconds (default: 5)
     * @return array Associative array with keys: 'cleaned' (int), 'errors' (int), 'execution_time' (float)
     * 
     * @example
     * $result = MemoryCleanupHelper::cleanupExpiredCache(5);
     * // Returns: ['cleaned' => 10, 'errors' => 0, 'execution_time' => 0.5]
     */
    public static function cleanupExpiredCache(int $maxExecutionTime = 5): array
    {
        $startTime = microtime(true);
        $cleaned = 0;
        $errors = 0;
        
        try {
            $cacheDir = __DIR__ . '/../../cache/';
            if (!is_dir($cacheDir)) {
                return ['cleaned' => 0, 'errors' => 0, 'execution_time' => 0];
            }
            
            $files = glob($cacheDir . '*');
            if (!$files) {
                return ['cleaned' => 0, 'errors' => 0, 'execution_time' => microtime(true) - $startTime];
            }
            
            foreach ($files as $file) {
                // Check execution time limit
                if ((microtime(true) - $startTime) > $maxExecutionTime) {
                    break;
                }
                
                if (!is_file($file)) {
                    continue;
                }
                
                try {
                    $content = @file_get_contents($file);
                    if ($content === false) {
                        continue;
                    }
                    
                    $data = @unserialize($content);
                    if (!is_array($data) || !isset($data['expires'])) {
                        // Invalid cache file, delete it
                        @unlink($file);
                        $cleaned++;
                        continue;
                    }
                    
                    // Check if expired
                    if (time() > $data['expires']) {
                        @unlink($file);
                        $cleaned++;
                    }
                } catch (Exception $e) {
                    $errors++;
                    if (class_exists('Logger')) {
                        Logger::warning("MemoryCleanupHelper::cleanupExpiredCache() error: " . $e->getMessage());
                    } elseif (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("MemoryCleanupHelper::cleanupExpiredCache() error: " . $e->getMessage());
                    }
                }
            }
        } catch (Exception $e) {
            $errors++;
            if (class_exists('Logger')) {
                Logger::error("MemoryCleanupHelper::cleanupExpiredCache() fatal error: " . $e->getMessage());
            } elseif (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("MemoryCleanupHelper::cleanupExpiredCache() fatal error: " . $e->getMessage());
            }
        }
        
        return [
            'cleaned' => $cleaned,
            'errors' => $errors,
            'execution_time' => microtime(true) - $startTime
        ];
    }
    
    /**
     * Clean up old session files
     * 
     * Removes session files older than the specified maximum age. This helps prevent
     * disk space issues and ensures old sessions are properly cleaned up. Respects
     * execution time limits to avoid blocking.
     * 
     * @param int $maxAge Maximum age of session files in seconds (default: 86400 = 24 hours)
     * @param int $maxExecutionTime Maximum execution time in seconds (default: 5)
     * @return array Associative array with keys: 'cleaned' (int), 'errors' (int), 'execution_time' (float)
     * 
     * @example
     * $result = MemoryCleanupHelper::cleanupOldSessions(86400, 5);
     * // Removes sessions older than 24 hours
     */
    public static function cleanupOldSessions(int $maxAge = 86400, int $maxExecutionTime = 5): array
    {
        $startTime = microtime(true);
        $cleaned = 0;
        $errors = 0;
        
        try {
            $sessionPath = session_save_path();
            if (empty($sessionPath) || !is_dir($sessionPath)) {
                // Use default session save path
                $sessionPath = sys_get_temp_dir();
            }
            
            $files = glob($sessionPath . '/sess_*');
            if (!$files) {
                return ['cleaned' => 0, 'errors' => 0, 'execution_time' => microtime(true) - $startTime];
            }
            
            $cutoffTime = time() - $maxAge;
            
            foreach ($files as $file) {
                // Check execution time limit
                if ((microtime(true) - $startTime) > $maxExecutionTime) {
                    break;
                }
                
                if (!is_file($file)) {
                    continue;
                }
                
                try {
                    $fileTime = @filemtime($file);
                    if ($fileTime === false) {
                        continue;
                    }
                    
                    // Delete old session files
                    if ($fileTime < $cutoffTime) {
                        @unlink($file);
                        $cleaned++;
                    }
                } catch (Exception $e) {
                    $errors++;
                    if (class_exists('Logger')) {
                        Logger::warning("MemoryCleanupHelper::cleanupOldSessions() error: " . $e->getMessage());
                    } elseif (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("MemoryCleanupHelper::cleanupOldSessions() error: " . $e->getMessage());
                    }
                }
            }
        } catch (Exception $e) {
            $errors++;
            if (class_exists('Logger')) {
                Logger::error("MemoryCleanupHelper::cleanupOldSessions() fatal error: " . $e->getMessage());
            } elseif (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("MemoryCleanupHelper::cleanupOldSessions() fatal error: " . $e->getMessage());
            }
        }
        
        return [
            'cleaned' => $cleaned,
            'errors' => $errors,
            'execution_time' => microtime(true) - $startTime
        ];
    }
    
    /**
     * Clean up temporary files
     * 
     * Removes temporary files older than the specified maximum age from the given
     * directory. This prevents accumulation of temporary files that could fill up
     * disk space. Respects execution time limits.
     * 
     * @param string $tempDir Temporary directory path to clean
     * @param int $maxAge Maximum age of files in seconds (default: 3600 = 1 hour)
     * @param int $maxExecutionTime Maximum execution time in seconds (default: 5)
     * @return array Associative array with keys: 'cleaned' (int), 'errors' (int), 'execution_time' (float)
     * 
     * @example
     * $result = MemoryCleanupHelper::cleanupTempFiles('/tmp/app_temp', 3600, 5);
     * // Removes temp files older than 1 hour
     */
    public static function cleanupTempFiles(string $tempDir, int $maxAge = 3600, int $maxExecutionTime = 5): array
    {
        $startTime = microtime(true);
        $cleaned = 0;
        $errors = 0;
        
        try {
            if (!is_dir($tempDir)) {
                return ['cleaned' => 0, 'errors' => 0, 'execution_time' => 0];
            }
            
            $files = glob($tempDir . '/*');
            if (!$files) {
                return ['cleaned' => 0, 'errors' => 0, 'execution_time' => microtime(true) - $startTime];
            }
            
            $cutoffTime = time() - $maxAge;
            
            foreach ($files as $file) {
                // Check execution time limit
                if ((microtime(true) - $startTime) > $maxExecutionTime) {
                    break;
                }
                
                if (!is_file($file)) {
                    continue;
                }
                
                try {
                    $fileTime = @filemtime($file);
                    if ($fileTime === false) {
                        continue;
                    }
                    
                    // Delete old temp files
                    if ($fileTime < $cutoffTime) {
                        @unlink($file);
                        $cleaned++;
                    }
                } catch (Exception $e) {
                    $errors++;
                    if (class_exists('Logger')) {
                        Logger::warning("MemoryCleanupHelper::cleanupTempFiles() error: " . $e->getMessage());
                    } elseif (defined('APP_DEBUG') && APP_DEBUG) {
                        error_log("MemoryCleanupHelper::cleanupTempFiles() error: " . $e->getMessage());
                    }
                }
            }
        } catch (Exception $e) {
            $errors++;
            if (class_exists('Logger')) {
                Logger::error("MemoryCleanupHelper::cleanupTempFiles() fatal error: " . $e->getMessage());
            } elseif (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("MemoryCleanupHelper::cleanupTempFiles() fatal error: " . $e->getMessage());
            }
        }
        
        return [
            'cleaned' => $cleaned,
            'errors' => $errors,
            'execution_time' => microtime(true) - $startTime
        ];
    }
    
    /**
     * Perform comprehensive cleanup (cache, sessions, temp files)
     * 
     * Executes all cleanup operations (cache, sessions, temp files) within the
     * specified time limit. Time is distributed proportionally: 30% for cache,
     * 30% for sessions, and remaining time for temp files.
     * 
     * @param int $maxExecutionTime Maximum total execution time in seconds (default: 10)
     * @return array Summary array with keys: 'cache', 'sessions', 'temp_files' (each containing cleanup stats), 'total_execution_time' (float)
     * 
     * @example
     * $result = MemoryCleanupHelper::performComprehensiveCleanup(10);
     * // Performs all cleanup operations within 10 seconds
     */
    public static function performComprehensiveCleanup(int $maxExecutionTime = 10): array
    {
        $startTime = microtime(true);
        $results = [
            'cache' => ['cleaned' => 0, 'errors' => 0],
            'sessions' => ['cleaned' => 0, 'errors' => 0],
            'temp_files' => ['cleaned' => 0, 'errors' => 0],
            'total_execution_time' => 0
        ];
        
        // Cleanup cache (use 30% of max time)
        $cacheTime = $maxExecutionTime * 0.3;
        $results['cache'] = self::cleanupExpiredCache((int)$cacheTime);
        
        // Check if we have time left
        $elapsed = microtime(true) - $startTime;
        if ($elapsed < $maxExecutionTime) {
            // Cleanup sessions (use 30% of remaining time)
            $remainingTime = $maxExecutionTime - $elapsed;
            $sessionTime = $remainingTime * 0.3;
            $results['sessions'] = self::cleanupOldSessions(86400, (int)$sessionTime);
        }
        
        // Check if we have time left
        $elapsed = microtime(true) - $startTime;
        if ($elapsed < $maxExecutionTime) {
            // Cleanup temp files (use remaining time)
            $remainingTime = $maxExecutionTime - $elapsed;
            $tempDir = sys_get_temp_dir() . '/app_temp';
            if (is_dir($tempDir)) {
                $results['temp_files'] = self::cleanupTempFiles($tempDir, 3600, (int)$remainingTime);
            }
        }
        
        $results['total_execution_time'] = microtime(true) - $startTime;
        
        return $results;
    }
    
    /**
     * Get memory usage statistics
     * 
     * Retrieves current and peak memory usage information, along with memory limit
     * and available memory. Useful for monitoring and debugging memory issues.
     * 
     * @return array Associative array with keys: 'current', 'current_mb', 'peak', 'peak_mb',
     *               'limit', 'limit_bytes', 'usage_percent', 'available', 'available_mb'
     * 
     * @example
     * $stats = MemoryCleanupHelper::getMemoryStats();
     * // Returns: ['current' => 12345678, 'current_mb' => 11.77, 'peak' => ...]
     */
    public static function getMemoryStats(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        // Convert memory limit to bytes
        $memoryLimitBytes = self::convertToBytes($memoryLimit);
        $memoryUsagePercent = $memoryLimitBytes > 0 
            ? ($memoryUsage / $memoryLimitBytes) * 100 
            : 0;
        
        return [
            'current' => $memoryUsage,
            'current_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak' => $memoryPeak,
            'peak_mb' => round($memoryPeak / 1024 / 1024, 2),
            'limit' => $memoryLimit,
            'limit_bytes' => $memoryLimitBytes,
            'usage_percent' => round($memoryUsagePercent, 2),
            'available' => $memoryLimitBytes - $memoryUsage,
            'available_mb' => round(($memoryLimitBytes - $memoryUsage) / 1024 / 1024, 2)
        ];
    }
    
    /**
     * Convert memory limit string to bytes
     * 
     * @param string $value Memory limit string (e.g., "128M", "2G")
     * @return int Bytes
     */
    private static function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int)$value;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
                // fall through
            case 'm':
                $value *= 1024;
                // fall through
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
}

