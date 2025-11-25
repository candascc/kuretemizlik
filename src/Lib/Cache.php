<?php
/**
 * Cache System - SaaS Level Caching
 * File-based caching with TTL support
 */

class Cache
{
    private static $cacheDir = __DIR__ . '/../../cache/';
    private static $defaultTTL = 3600; // 1 hour
    // ===== ERR-019 FIX: Memory leak prevention =====
    private static $maxFileSize = 10 * 1024 * 1024; // 10MB max file size
    private static $maxMemoryUsage = 0.8; // 80% of memory limit
    private static $cleanupProbability = 0.01; // 1% chance to run cleanup on each set
    private static $lastCleanupTime = null;
    private static $cleanupInterval = 3600; // Run cleanup at least once per hour
    // ===== ERR-019 FIX: End =====
    
    public static function init()
    {
        if (!file_exists(self::$cacheDir)) {
            try {
                if (!mkdir(self::$cacheDir, 0755, true)) {
                    if (class_exists('Logger')) {
                        Logger::warning("Failed to create cache directory: " . self::$cacheDir);
                    }
                    return false;
                }
            } catch (Exception $e) {
                if (class_exists('Logger')) {
                    Logger::error("Error creating cache directory: " . $e->getMessage());
                }
                return false;
            }
        }
        
        // Check if directory is writable
        if (!is_dir(self::$cacheDir) || !is_writable(self::$cacheDir)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Cache'e veri kaydet
     */
    public static function set($key, $value, $ttl = null)
    {
        if (!self::init()) {
            // Cache directory is not writable, silently fail
            return false;
        }
        
        // ===== ERR-019 FIX: Check memory usage before serializing =====
        if (!self::checkMemoryAvailable()) {
            if (class_exists('Logger')) {
                Logger::warning("Cache set failed: Memory limit reached for key: " . $key);
            }
            return false;
        }
        // ===== ERR-019 FIX: End =====
        
        $ttl = $ttl ?? self::$defaultTTL;
        $expires = time() + $ttl;
        
        $data = [
            'value' => $value,
            'expires' => $expires,
            'created' => time()
        ];
        
        $file = self::getFilePath($key);
        try {
            $serialized = serialize($data);
            
            // ===== ERR-019 FIX: Check serialized size before writing =====
            $serializedSize = strlen($serialized);
            if ($serializedSize > self::$maxFileSize) {
                if (class_exists('Logger')) {
                    Logger::warning("Cache set failed: Serialized data too large ({$serializedSize} bytes) for key: " . $key);
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
            
            // ===== ERR-019 FIX: Periodically run cleanup to prevent memory leaks =====
            self::maybeCleanup();
            // ===== ERR-019 FIX: End =====
            
            return $bytesWritten !== false && $bytesWritten === strlen($serialized);
            // ===== ERR-020 FIX: End =====
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning("Failed to write cache file: " . $e->getMessage());
            }
            return false;
        }
    }
    
    // ===== ERR-019 FIX: Periodic cleanup helper =====
    /**
     * Periodically run cleanup to prevent memory leaks
     */
    private static function maybeCleanup(): void
    {
        $now = time();
        
        // Run cleanup if:
        // 1. It's been more than cleanupInterval since last cleanup, OR
        // 2. Random chance (cleanupProbability)
        $shouldCleanup = false;
        
        if (self::$lastCleanupTime === null || ($now - self::$lastCleanupTime) > self::$cleanupInterval) {
            $shouldCleanup = true;
        } elseif (mt_rand(1, 100) <= (self::$cleanupProbability * 100)) {
            $shouldCleanup = true;
        }
        
        if ($shouldCleanup) {
            // Run cleanup in background (non-blocking)
            // Use a simple approach: just call cleanup, but limit execution time
            $startTime = microtime(true);
            $maxExecutionTime = 2.0; // Max 2 seconds for cleanup
            
            try {
                $cleaned = self::cleanup();
                self::$lastCleanupTime = $now;
                
                $executionTime = microtime(true) - $startTime;
                if ($executionTime > $maxExecutionTime && class_exists('Logger')) {
                    Logger::warning("Cache cleanup took {$executionTime}s (cleaned {$cleaned} files)");
                }
            } catch (Exception $e) {
                if (class_exists('Logger')) {
                    Logger::warning("Cache cleanup failed: " . $e->getMessage());
                }
            }
        }
    }
    // ===== ERR-019 FIX: End =====
    
    /**
     * Cache'den veri al
     */
    public static function get($key, $default = null)
    {
        if (!self::init()) {
            // Cache directory is not accessible, return default
            return $default;
        }
        
        $file = self::getFilePath($key);
        
        if (!file_exists($file)) {
            return $default;
        }
        
        // ===== ERR-019 FIX: Check file size before loading into memory =====
        try {
            $fileSize = filesize($file);
            if ($fileSize === false || $fileSize > self::$maxFileSize) {
                if (class_exists('Logger')) {
                    Logger::warning("Cache get failed: File too large ({$fileSize} bytes) for key: " . $key);
                }
                // Delete oversized cache file
                self::delete($key);
                return $default;
            }
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning("Failed to check cache file size: " . $e->getMessage());
            }
            return $default;
        }
        // ===== ERR-019 FIX: End =====
        
        // ===== ERR-020 FIX: Use file locking for read operations to prevent race conditions =====
        try {
            $fp = fopen($file, 'rb');
            if ($fp === false) {
                return $default;
            }
            
            // Acquire shared lock for reading
            if (!flock($fp, LOCK_SH)) {
                fclose($fp);
                return $default;
            }
            
            $content = '';
            while (!feof($fp)) {
                $content .= fread($fp, 8192); // Read in chunks
            }
            
            flock($fp, LOCK_UN);
            fclose($fp);
            
            if ($content === '') {
                return $default;
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
            return $default;
        }
        // ===== ERR-020 FIX: End =====
        
        // ===== PATHD_STAGE4: Hardened unserialize with cache-miss behavior (no fatal, no error.log spam) =====
        // ROUND 50: Hardened unserialize with Throwable catch and false check
        try {
            // Use @unserialize to suppress PHP warnings (we handle errors ourselves)
            $data = @unserialize($content);
            
            // ROUND 50: Check for false return (corrupted data, not just 'b:0;')
            if ($data === false && $content !== 'b:0;' && $content !== serialize(false)) {
                // Corrupted data detected → treat as cache miss, delete corrupt file
                // Only log to dedicated cache log, NOT to error.log (reduces spam)
                $logFile = __DIR__ . '/../../logs/cache_unserialize_fail.log';
                $logDir = dirname($logFile);
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                @file_put_contents($logFile, date('Y-m-d H:i:s') . " [CACHE_CORRUPT] unserialize() returned false for key={$key}, content_length=" . strlen($content) . "\n", FILE_APPEND | LOCK_EX);
                self::delete($key); // Delete corrupted cache
                return $default; // Return default (cache miss behavior)
            }
        } catch (Throwable $e) {
            // ROUND 50: Catch all Throwable types (Exception + Error) for PHP 8 compatibility
            // ===== PATHD_STAGE4: Log to cache log only, NOT to error.log =====
            $logFile = __DIR__ . '/../../logs/cache_unserialize_fail.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [CACHE_CORRUPT] Exception for key={$key}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n", FILE_APPEND | LOCK_EX);
            // DO NOT log to error.log or Logger::warning (reduces spam)
            // if (class_exists('Logger')) {
            //     Logger::warning("Failed to unserialize cache data: " . $e->getMessage(), ['key' => $key]);
            // }
            self::delete($key); // Delete corrupted cache
            return $default; // Return default (cache miss behavior)
        }
        // ===== PATHD_STAGE4 END =====
        
        if (!$data || (isset($data['expires']) && $data['expires'] < time())) {
            self::delete($key);
            return $default;
        }
        
        return $data['value'] ?? $default;
    }
    
    /**
     * Cache'den veri sil
     */
    public static function delete($key)
    {
        $file = self::getFilePath($key);
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
     * Tüm cache'i temizle
     */
    public static function flush()
    {
        if (!self::init()) {
            return false;
        }
        
        try {
            $files = glob(self::$cacheDir . '*');
            if ($files === false) {
                return false;
            }
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning("Failed to glob cache files: " . $e->getMessage());
            }
            return false;
        }
        
        foreach ($files as $file) {
            if (is_file($file)) {
                try {
                    unlink($file);
                } catch (Exception $e) {
                    if (class_exists('Logger')) {
                        Logger::warning("Failed to delete cache file during flush: " . $e->getMessage());
                    }
                }
            }
        }
        return true;
    }
    
    /**
     * Cache istatistikleri
     */
    public static function stats()
    {
        if (!self::init()) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'expired_files' => 0,
                'cache_dir' => self::$cacheDir
            ];
        }
        
        try {
            $files = glob(self::$cacheDir . '*');
            if ($files === false) {
                return [
                    'total_files' => 0,
                    'total_size' => 0,
                    'expired_files' => 0,
                    'cache_dir' => self::$cacheDir
                ];
            }
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning("Failed to glob cache files in stats: " . $e->getMessage());
            }
            return [
                'total_files' => 0,
                'total_size' => 0,
                'expired_files' => 0,
                'cache_dir' => self::$cacheDir
            ];
        }
        
        $totalSize = 0;
        $count = 0;
        $expired = 0;
        
        // ===== ERR-019 FIX: Process files in batches to prevent memory issues =====
        $batchSize = 50; // Process 50 files at a time
        $processed = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                try {
                    // Check file size first without loading into memory
                    $fileSize = filesize($file);
                    if ($fileSize === false) {
                        continue;
                    }
                    
                    // Skip oversized files
                    if ($fileSize > self::$maxFileSize) {
                        $expired++; // Count as expired/problematic
                        continue;
                    }
                    
                    $totalSize += $fileSize;
                    $count++;
                    
                    // Only check expiration for smaller files to avoid memory issues
                    if ($fileSize < 1024 * 1024) { // Only check files < 1MB
                        try {
                            $content = file_get_contents($file);
                            if ($content !== false) {
                                try {
                                    // ===== PATHF_STAGE4: Use @unserialize to suppress PHP warnings =====
                                    $data = @unserialize($content);
                                    // ROUND 50: Check for false return (corrupted data)
                                    if ($data === false && $content !== 'b:0;' && $content !== serialize(false)) {
                                        // Corrupted file - log and skip
                                        $logFile = __DIR__ . '/../../logs/cache_unserialize_fail.log';
                                        @file_put_contents($logFile, date('Y-m-d H:i:s') . " [CACHE_CORRUPT] Corrupted file in cleanup: " . basename($file) . "\n", FILE_APPEND | LOCK_EX);
                                    } elseif ($data && isset($data['expires']) && $data['expires'] < time()) {
                                        $expired++;
                                    }
                                } catch (Throwable $e) {
                                    // ROUND 50: Log unserialize errors during cleanup
                                    $logFile = __DIR__ . '/../../logs/cache_unserialize_fail.log';
                                    @file_put_contents($logFile, date('Y-m-d H:i:s') . " [CACHE_UNSERIALIZE_FAIL] Cleanup exception: " . $e->getMessage() . " for file=" . basename($file) . "\n", FILE_APPEND | LOCK_EX);
                                }
                            }
                        } catch (Throwable $e) {
                            // Ignore file read errors
                        }
                    }
                    
                    $processed++;
                    // Check memory every batch
                    if ($processed % $batchSize === 0 && !self::checkMemoryAvailable()) {
                        if (class_exists('Logger')) {
                            Logger::warning("Cache stats: Memory limit reached, stopping early");
                        }
                        break;
                    }
                } catch (Exception $e) {
                    // Ignore errors
                }
            }
        }
        // ===== ERR-019 FIX: End =====
        
        return [
            'total_files' => $count,
            'total_size' => $totalSize,
            'expired_files' => $expired,
            'cache_dir' => self::$cacheDir
        ];
    }
    
    /**
     * Expired cache'leri temizle
     */
    public static function cleanup()
    {
        if (!self::init()) {
            return 0;
        }
        
        try {
            $files = glob(self::$cacheDir . '*');
            if ($files === false) {
                return 0;
            }
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning("Failed to glob cache files in cleanup: " . $e->getMessage());
            }
            return 0;
        }
        
        $cleaned = 0;
        $processed = 0;
        $batchSize = 50; // Process 50 files at a time
        
        // ===== ERR-019 FIX: Process files in batches to prevent memory issues =====
        foreach ($files as $file) {
            if (is_file($file)) {
                try {
                    // Check file size first
                    $fileSize = filesize($file);
                    if ($fileSize === false) {
                        continue;
                    }
                    
                    // Delete oversized files immediately
                    if ($fileSize > self::$maxFileSize) {
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
                                    // ===== PATHE_STAGE3: Use @unserialize to suppress PHP warnings =====
                                    $data = @unserialize($content);
                                    if ($data && isset($data['expires']) && $data['expires'] < time()) {
                                        try {
                                            unlink($file);
                                            $cleaned++;
                                        } catch (Exception $e) {
                                            if (class_exists('Logger')) {
                                                Logger::warning("Failed to delete expired cache file: " . $e->getMessage());
                                            }
                                        }
                                    } elseif ($data === false && $content !== 'b:0;' && $content !== serialize(false)) {
                                        // Corrupted cache file detected → delete it silently
                                        try {
                                            unlink($file);
                                            $cleaned++;
                                        } catch (Exception $e2) {
                                            // Ignore delete errors
                                        }
                                    }
                                } catch (Throwable $e) {
                                    // ===== PATHE_STAGE3: Log to cache log only, NOT to error.log =====
                                    // Ignore unserialize errors, but delete corrupt files
                                    // Only log to dedicated cache log, NOT to error.log (reduces spam)
                                    $logFile = __DIR__ . '/../../logs/cache_unserialize_fail.log';
                                    $logDir = dirname($logFile);
                                    if (!is_dir($logDir)) {
                                        @mkdir($logDir, 0755, true);
                                    }
                                    @file_put_contents($logFile, date('Y-m-d H:i:s') . " [CACHE_CORRUPT_CLEANUP] Exception for file={$file}: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
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
                    if ($processed % $batchSize === 0 && !self::checkMemoryAvailable()) {
                        if (class_exists('Logger')) {
                            Logger::warning("Cache cleanup: Memory limit reached, stopping early");
                        }
                        break;
                    }
                } catch (Exception $e) {
                    // Ignore errors
                }
            }
        }
        // ===== ERR-019 FIX: End =====
        
        return $cleaned;
    }
    
    /**
     * Cache key'den dosya yolu oluştur
     */
    private static function getFilePath($key)
    {
        $hash = md5($key);
        return self::$cacheDir . $hash . '.cache';
    }
    
    // ===== ERR-019 FIX: Memory check helper =====
    /**
     * Check if memory is available for cache operations
     */
    private static function checkMemoryAvailable(): bool
    {
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit === '-1') {
            // Unlimited memory
            return true;
        }
        
        // Convert memory limit to bytes
        $memoryLimitBytes = self::convertToBytes($memoryLimit);
        $currentMemoryUsage = memory_get_usage(true);
        $maxAllowedUsage = $memoryLimitBytes * self::$maxMemoryUsage;
        
        return $currentMemoryUsage < $maxAllowedUsage;
    }
    
    /**
     * Convert memory limit string to bytes
     */
    private static function convertToBytes(string $memoryLimit): int
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
    
    /**
     * Cache remember pattern
     */
    public static function remember($key, $callback, $ttl = null)
    {
        $value = self::get($key);
        
        if ($value === null) {
            $value = $callback();
            self::set($key, $value, $ttl);
        }
        
        return $value;
    }
    
    /**
     * Cache tags sistemi
     */
    public static function tag($tag, $keys)
    {
        if (!self::init()) {
            // Cache directory is not writable, silently fail
            return false;
        }
        
        $tagFile = self::getFilePath("tag_$tag");
        $existing = [];
        if (file_exists($tagFile)) {
            try {
                $content = file_get_contents($tagFile);
                if ($content !== false) {
                    try {
                        // ===== PATHF_STAGE4: Use @unserialize to suppress PHP warnings =====
                        $data = @unserialize($content);
                        // ROUND 50: Check for false return (corrupted data)
                        if ($data === false && $content !== 'b:0;' && $content !== serialize(false)) {
                            // Corrupted tag file - log and use empty array
                            $logFile = __DIR__ . '/../../logs/cache_unserialize_fail.log';
                            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [CACHE_CORRUPT] Corrupted tag file: tag_{$tag}\n", FILE_APPEND | LOCK_EX);
                            $existing = [];
                        } else {
                            $existing = is_array($data) ? $data : [];
                        }
                    } catch (Throwable $e) {
                        // ROUND 50: Log unserialize errors for tag files
                        $logFile = __DIR__ . '/../../logs/cache_unserialize_fail.log';
                        @file_put_contents($logFile, date('Y-m-d H:i:s') . " [CACHE_UNSERIALIZE_FAIL] Tag file exception: " . $e->getMessage() . " for tag={$tag}\n", FILE_APPEND | LOCK_EX);
                        $existing = [];
                    }
                }
            } catch (Exception $e) {
                // Ignore file read errors
            }
        }
        $allKeys = array_unique(array_merge($existing, $keys));
        try {
            $result = file_put_contents($tagFile, serialize($allKeys), LOCK_EX);
            return $result !== false;
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::warning("Failed to write cache tag file: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Tag'e göre cache temizle
     */
    public static function forgetTag($tag)
    {
        if (!self::init()) {
            return false;
        }
        
        $tagFile = self::getFilePath("tag_$tag");
        
        if (file_exists($tagFile)) {
            try {
                $content = file_get_contents($tagFile);
                if ($content !== false) {
                    try {
                        // ===== PATHF_STAGE4: Use @unserialize to suppress PHP warnings =====
                        $data = @unserialize($content);
                        // ROUND 50: Check for false return (corrupted data)
                        if ($data === false && $content !== 'b:0;' && $content !== serialize(false)) {
                            // Corrupted tag file - log and skip
                            $logFile = __DIR__ . '/../../logs/cache_unserialize_fail.log';
                            @file_put_contents($logFile, date('Y-m-d H:i:s') . " [CACHE_CORRUPT] Corrupted tag file in forgetTag: tag_{$tag}\n", FILE_APPEND | LOCK_EX);
                        } elseif (is_array($data)) {
                            foreach ($data as $key) {
                                self::delete($key);
                            }
                        }
                    } catch (Throwable $e) {
                        // ROUND 50: Log unserialize errors for tag files
                        $logFile = __DIR__ . '/../../logs/cache_unserialize_fail.log';
                        @file_put_contents($logFile, date('Y-m-d H:i:s') . " [CACHE_UNSERIALIZE_FAIL] Tag file exception in forgetTag: " . $e->getMessage() . " for tag={$tag}\n", FILE_APPEND | LOCK_EX);
                    }
                }
            } catch (Exception $e) {
                // Ignore file read errors
            }
            try {
                unlink($tagFile);
            } catch (Exception $e) {
                if (class_exists('Logger')) {
                    Logger::warning("Failed to delete cache tag file: " . $e->getMessage());
                }
            }
            return true;
        }
        
        return false;
    }
}
