<?php

/**
 * Cache Manager
 */
class CacheManager
{
    private static $instance = null;
    private $cacheDir;
    private $defaultTTL = 3600; // 1 hour
    // ===== ERR-019 FIX: Memory leak prevention =====
    private $maxFileSize = 10 * 1024 * 1024; // 10MB max file size
    private $maxMemoryUsage = 0.8; // 80% of memory limit
    // ===== ERR-019 FIX: End =====

    private function __construct()
    {
        $this->cacheDir = __DIR__ . '/../../storage/cache/';
        if (!is_dir($this->cacheDir)) {
            try {
                if (!mkdir($this->cacheDir, 0755, true)) {
                    if (class_exists('Logger')) {
                        Logger::warning("Failed to create cache directory: " . $this->cacheDir);
                    }
                }
            } catch (Exception $e) {
                if (class_exists('Logger')) {
                    Logger::error("Error creating cache directory: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Check if cache directory is writable
     */
    private function isWritable(): bool
    {
        return is_dir($this->cacheDir) && is_writable($this->cacheDir);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Clear all cache (compat helper expected by tests)
     */
    public static function clearAll(): bool
    {
        return self::getInstance()->clear();
    }

    /**
     * Get cached data
     */
    public function get($key)
    {
        if (!$this->isWritable()) {
            return null;
        }
        
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }

        // ===== ERR-019 FIX: Check file size before loading into memory =====
        try {
            $fileSize = filesize($file);
            if ($fileSize === false || $fileSize > $this->maxFileSize) {
                if (class_exists('Logger')) {
                    Logger::warning("CacheManager get failed: File too large ({$fileSize} bytes) for key: " . $key);
                }
                // Delete oversized cache file
                $this->delete($key);
                return null;
            }
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning("Failed to check cache file size: " . $e->getMessage());
            }
            return null;
        }
        // ===== ERR-019 FIX: End =====

        // ===== ERR-020 FIX: Use file locking for read operations to prevent race conditions =====
        try {
            $fp = fopen($file, 'rb');
            if ($fp === false) {
                return null;
            }
            
            // Acquire shared lock for reading
            if (!flock($fp, LOCK_SH)) {
                fclose($fp);
                return null;
            }
            
            $content = '';
            while (!feof($fp)) {
                $content .= fread($fp, 8192); // Read in chunks
            }
            
            flock($fp, LOCK_UN);
            fclose($fp);
            
            if ($content === '') {
                return null;
            }
        } catch (Exception $e) {
            // ===== ERR-022 FIX: Replace error suppression with proper error handling =====
            if (isset($fp) && is_resource($fp)) {
                try {
                    flock($fp, LOCK_UN);
                } catch (Exception $e) {
                    // Ignore unlock errors
                }
                try {
                    fclose($fp);
                } catch (Exception $e) {
                    // Ignore close errors
                }
            }
            // ===== ERR-022 FIX: End =====
            if (class_exists('Logger')) {
                Logger::warning("Failed to read cache file: " . $e->getMessage());
            }
            return null;
        }
        // ===== ERR-020 FIX: End =====
        
        // ROUND 50: Hardened unserialize with Throwable catch and false check
        try {
            $data = unserialize($content);
            
            // ROUND 50: Check for false return (corrupted data, not just 'b:0;')
            if ($data === false && $content !== 'b:0;' && $content !== serialize(false)) {
                // Corrupted data detected
                $logFile = __DIR__ . '/../../logs/cache_unserialize_fail.log';
                $logDir = dirname($logFile);
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                @file_put_contents($logFile, date('Y-m-d H:i:s') . " [CACHE_UNSERIALIZE_FAIL] CacheManager unserialize() returned false for key={$key}, content_length=" . strlen($content) . "\n", FILE_APPEND | LOCK_EX);
                $this->delete($key); // Delete corrupted cache
                return null;
            }
        } catch (Throwable $e) {
            // ROUND 50: Catch all Throwable types (Exception + Error) for PHP 8 compatibility
            $logFile = __DIR__ . '/../../logs/cache_unserialize_fail.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [CACHE_UNSERIALIZE_FAIL] CacheManager Exception for key={$key}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n", FILE_APPEND | LOCK_EX);
            if (class_exists('Logger')) {
                Logger::warning("Failed to unserialize cache data: " . $e->getMessage(), ['key' => $key]);
            }
            $this->delete($key); // Delete corrupted cache
            return null;
        }
        
        if (!is_array($data) || !isset($data['expires']) || $data['expires'] < time()) {
            $this->delete($key);
            return null;
        }

        return $data['value'] ?? null;
    }

    /**
     * Set cached data
     */
    public function set($key, $value, $ttl = null)
    {
        if (!$this->isWritable()) {
            return false;
        }
        
        // ===== ERR-019 FIX: Check memory usage before serializing =====
        if (!$this->checkMemoryAvailable()) {
            if (class_exists('Logger')) {
                Logger::warning("CacheManager set failed: Memory limit reached for key: " . $key);
            }
            return false;
        }
        // ===== ERR-019 FIX: End =====
        
        $ttl = $ttl ?? $this->defaultTTL;
        $file = $this->getCacheFile($key);
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];

        try {
            $serialized = serialize($data);
            
            // ===== ERR-019 FIX: Check serialized size before writing =====
            $serializedSize = strlen($serialized);
            if ($serializedSize > $this->maxFileSize) {
                if (class_exists('Logger')) {
                    Logger::warning("CacheManager set failed: Serialized data too large ({$serializedSize} bytes) for key: " . $key);
                }
                return false;
            }
            // ===== ERR-019 FIX: End =====
            
            // ===== ERR-020 FIX: Use proper file locking for write operations =====
            $fp = fopen($file, 'cb');
            if ($fp === false) {
                return false;
            }
            
            // Acquire exclusive lock for writing
            if (!flock($fp, LOCK_EX)) {
                fclose($fp);
                return false;
            }
            
            // Truncate file and write new content
            ftruncate($fp, 0);
            rewind($fp);
            $bytesWritten = fwrite($fp, $serialized);
            
            flock($fp, LOCK_UN);
            fclose($fp);
            
            return $bytesWritten !== false && $bytesWritten === strlen($serialized);
            // ===== ERR-020 FIX: End =====
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning("Failed to write cache file: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Delete cached data
     */
    public function delete($key)
    {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            try {
                unlink($file);
            } catch (Exception $e) {
                if (class_exists('Logger')) {
                    Logger::warning("Failed to delete cache file: " . $e->getMessage());
                }
            }
        }
        return true;
    }

    /**
     * Clear all cache
     */
    public function clear()
    {
        if (!$this->isWritable()) {
            return false;
        }
        
        // ===== ERR-022 FIX: Replace error suppression with proper error handling =====
        try {
            $files = glob($this->cacheDir . '*.cache');
            if ($files === false) {
                if (class_exists('Logger')) {
                    Logger::warning("Failed to glob cache files in clear: " . $this->cacheDir);
                }
                return false;
            }
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::error("Error globbing cache files: " . $e->getMessage());
            }
            return false;
        }
        // ===== ERR-022 FIX: End =====
        
        foreach ($files as $file) {
            try {
                unlink($file);
            } catch (Exception $e) {
                if (class_exists('Logger')) {
                    Logger::warning("Failed to delete cache file during clear: " . $e->getMessage());
                }
            }
        }
        return true;
    }

    /**
     * Get cache statistics
     */
    public function getStats()
    {
        if (!is_dir($this->cacheDir)) {
            return [
                'total_files' => 0,
                'valid_files' => 0,
                'expired_files' => 0,
                'total_size' => 0,
                'total_size_mb' => 0
            ];
        }
        
        try {
            $files = glob($this->cacheDir . '*.cache');
            if ($files === false) {
                return [
                    'total_files' => 0,
                    'valid_files' => 0,
                    'expired_files' => 0,
                    'total_size' => 0,
                    'total_size_mb' => 0
                ];
            }
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning("Failed to glob cache files in getStats: " . $e->getMessage());
            }
            return [
                'total_files' => 0,
                'valid_files' => 0,
                'expired_files' => 0,
                'total_size' => 0,
                'total_size_mb' => 0
            ];
        }
        
        $totalSize = 0;
        $expiredCount = 0;
        $validCount = 0;
        $processed = 0;
        $batchSize = 50; // Process 50 files at a time

        // ===== ERR-019 FIX: Process files in batches to prevent memory issues =====
        foreach ($files as $file) {
            try {
                $fileSize = filesize($file);
                if ($fileSize === false) {
                    continue;
                }
                
                // Skip oversized files
                if ($fileSize > $this->maxFileSize) {
                    $expiredCount++; // Count as expired/problematic
                    continue;
                }
                
                $totalSize += $fileSize;
                
                // Only check expiration for smaller files to avoid memory issues
                if ($fileSize < 1024 * 1024) { // Only check files < 1MB
                    try {
                        $raw = file_get_contents($file);
                        if ($raw !== false) {
                            try {
                                $data = unserialize($raw);
                                // ROUND 50: Check for false return (corrupted data)
                                if ($data === false && $raw !== 'b:0;' && $raw !== serialize(false)) {
                                    // Corrupted file - log
                                    $logFile = __DIR__ . '/../../logs/cache_unserialize_fail.log';
                                    @file_put_contents($logFile, date('Y-m-d H:i:s') . " [CACHE_UNSERIALIZE_FAIL] Corrupted file in CacheManager cleanup: " . basename($file) . "\n", FILE_APPEND | LOCK_EX);
                                }
                            } catch (Throwable $e) {
                                // ROUND 50: Log unserialize errors during cleanup
                                $logFile = __DIR__ . '/../../logs/cache_unserialize_fail.log';
                                @file_put_contents($logFile, date('Y-m-d H:i:s') . " [CACHE_UNSERIALIZE_FAIL] CacheManager cleanup exception: " . $e->getMessage() . " for file=" . basename($file) . "\n", FILE_APPEND | LOCK_EX);
                                $data = false;
                            }
                        } else {
                            $data = false;
                        }
                    } catch (Exception $e) {
                        $data = false;
                    }
                    
                    if (is_array($data) && isset($data['expires'])) {
                        if ($data['expires'] < time()) {
                            $expiredCount++;
                        } else {
                            $validCount++;
                        }
                    } else {
                        // Corrupt or legacy cache file: treat as expired
                        $expiredCount++;
                    }
                } else {
                    // Large files: assume valid (can't check without loading into memory)
                    $validCount++;
                }
                
                $processed++;
                // Check memory every batch
                if ($processed % $batchSize === 0 && !$this->checkMemoryAvailable()) {
                    if (class_exists('Logger')) {
                        Logger::warning("CacheManager getStats: Memory limit reached, stopping early");
                    }
                    break;
                }
            } catch (Exception $e) {
                // Ignore errors
            }
        }
        // ===== ERR-019 FIX: End =====

        return [
            'total_files' => count($files),
            'valid_files' => $validCount,
            'expired_files' => $expiredCount,
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2)
        ];
    }

    /**
     * Clean expired cache
     */
    public function cleanExpired()
    {
        if (!is_dir($this->cacheDir)) {
            return 0;
        }
        
        // ===== ERR-022 FIX: Replace error suppression with proper error handling =====
        try {
            $files = glob($this->cacheDir . '*.cache');
            if ($files === false) {
                if (class_exists('Logger')) {
                    Logger::warning("Failed to glob cache files in cleanExpired: " . $this->cacheDir);
                }
                return 0;
            }
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::error("Error globbing cache files: " . $e->getMessage());
            }
            return 0;
        }
        // ===== ERR-022 FIX: End =====
        
        $cleaned = 0;
        $processed = 0;
        $batchSize = 50; // Process 50 files at a time

        // ===== ERR-019 FIX: Process files in batches to prevent memory issues =====
        foreach ($files as $file) {
            try {
                // Check file size first
                $fileSize = filesize($file);
                if ($fileSize === false) {
                    continue;
                }
                
                // Delete oversized files immediately
                if ($fileSize > $this->maxFileSize) {
                    try {
                        unlink($file);
                        $cleaned++;
                    } catch (Exception $e) {
                        if (class_exists('Logger')) {
                            Logger::warning("Failed to delete oversized cache file: " . $e->getMessage());
                        }
                    }
                    continue;
                }
                
                // Only check expiration for smaller files
                if ($fileSize < 1024 * 1024) { // Only check files < 1MB
                    try {
                        $content = file_get_contents($file);
                        if ($content !== false) {
                            try {
                                $data = unserialize($content);
                                
                                // ROUND 50: Check for false return (corrupted data)
                                $isCorrupted = ($data === false && $content !== 'b:0;' && $content !== serialize(false));
                                if ($isCorrupted || (is_array($data) && isset($data['expires']) && $data['expires'] < time())) {
                                    // Delete corrupted or expired files
                                    try {
                                        unlink($file);
                                        $cleaned++;
                                        if ($isCorrupted) {
                                            $logFile = __DIR__ . '/../../logs/cache_unserialize_fail.log';
                                            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [CACHE_UNSERIALIZE_FAIL] Deleted corrupted file in CacheManager cleanup: " . basename($file) . "\n", FILE_APPEND | LOCK_EX);
                                        }
                                    } catch (Throwable $e) {
                                        if (class_exists('Logger')) {
                                            Logger::warning("Failed to delete expired cache file in cleanup: " . $e->getMessage());
                                        }
                                    }
                                }
                            } catch (Throwable $e) {
                                // ROUND 50: Log unserialize errors and delete corrupt files
                                $logFile = __DIR__ . '/../../logs/cache_unserialize_fail.log';
                                @file_put_contents($logFile, date('Y-m-d H:i:s') . " [CACHE_UNSERIALIZE_FAIL] CacheManager cleanup exception: " . $e->getMessage() . " for file=" . basename($file) . ", deleting\n", FILE_APPEND | LOCK_EX);
                                try {
                                    unlink($file);
                                    $cleaned++;
                                } catch (Throwable $delErr) {
                                    // Ignore delete errors
                                }
                                try {
                                    unlink($file);
                                    $cleaned++;
                                } catch (Exception $e2) {
                                    // Ignore delete errors
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // Ignore file read errors
                    }
                }
                
                $processed++;
                // Check memory every batch
                if ($processed % $batchSize === 0 && !$this->checkMemoryAvailable()) {
                    if (class_exists('Logger')) {
                        Logger::warning("CacheManager cleanExpired: Memory limit reached, stopping early");
                    }
                    break;
                }
            } catch (Exception $e) {
                // Ignore errors
            }
        }
        // ===== ERR-019 FIX: End =====

        return $cleaned;
    }

    /**
     * Get cache file path
     */
    private function getCacheFile($key)
    {
        return $this->cacheDir . md5($key) . '.cache';
    }

    /**
     * Cache with callback
     */
    public function remember($key, $callback, $ttl = null)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * Increment cache value
     */
    public function increment($key, $value = 1)
    {
        $current = $this->get($key) ?? 0;
        $newValue = $current + $value;
        $this->set($key, $newValue);
        return $newValue;
    }

    /**
     * Decrement cache value
     */
    public function decrement($key, $value = 1)
    {
        $current = $this->get($key) ?? 0;
        $newValue = max(0, $current - $value);
        $this->set($key, $newValue);
        return $newValue;
    }

    /**
     * Get cache analytics (empty for now)
     */
    public function getAnalytics()
    {
        return [
            'hit_rate' => 0,
            'miss_rate' => 0,
            'avg_response_time' => 0
        ];
    }

    /**
     * Get cache drivers (returns default driver info)
     */
    public function getDrivers()
    {
        return [
            'default' => [
                'name' => 'File System',
                'enabled' => true
            ]
        ];
    }
    
    // ===== ERR-019 FIX: Memory check helper =====
    /**
     * Check if memory is available for cache operations
     */
    private function checkMemoryAvailable(): bool
    {
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit === '-1') {
            // Unlimited memory
            return true;
        }
        
        // Convert memory limit to bytes
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        $currentMemoryUsage = memory_get_usage(true);
        $maxAllowedUsage = $memoryLimitBytes * $this->maxMemoryUsage;
        
        return $currentMemoryUsage < $maxAllowedUsage;
    }
    
    /**
     * Convert memory limit string to bytes
     */
    private function convertToBytes(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $value = (int)$memoryLimit;
        
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
    // ===== ERR-019 FIX: End =====
}