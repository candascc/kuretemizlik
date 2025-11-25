<?php
/**
 * Backup Service
 * Automated database backup with rotation and compression
 */
class BackupService
{
    private static $backupDir = null;
    private static $maxBackups = 30; // Keep last 30 backups
    private static $autoBackupEnabled = true;
    
    /**
     * Get backup directory
     */
    private static function getBackupDir(): string
    {
        if (self::$backupDir === null) {
            // Use DB_PATH to determine backup location
            $dbPath = defined('DB_PATH') ? DB_PATH : __DIR__ . '/../../db/app.sqlite';
            $dbDir = dirname($dbPath);
            self::$backupDir = $dbDir . '/backups';
        }
        return self::$backupDir;
    }
    
    /**
     * Create database backup
     */
    public static function createBackup(string $label = null): array
    {
        $dbPath = DB_PATH;
        
        if (!file_exists($dbPath)) {
            throw new Exception('Database file not found');
        }
        
        $backupDir = self::getBackupDir();
        
        // Ensure backup directory exists
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Generate backup filename
        $timestamp = date('Y-m-d_His');
        $label = $label ? '_' . preg_replace('/[^a-zA-Z0-9_]/', '', $label) : '';
        $filename = "backup_{$timestamp}{$label}.sqlite";
        $backupPath = $backupDir . '/' . $filename;
        
        // Copy database file
        if (!copy($dbPath, $backupPath)) {
            throw new Exception('Failed to create backup file');
        }
        
        // Compress backup (optional, reduces size by ~70%)
        $compressedPath = self::compressBackup($backupPath);
        if ($compressedPath) {
            unlink($backupPath); // Remove uncompressed file
            $backupPath = $compressedPath;
            $filename = basename($compressedPath);
        }
        
        // Record backup in metadata
        self::recordBackup($filename, filesize($backupPath));
        
        // Cleanup old backups
        self::cleanupOldBackups();
        
        // Log backup creation
        if (class_exists('Logger')) {
            Logger::info('Database backup created', [
                'filename' => $filename,
                'size' => self::formatBytes(filesize($backupPath))
            ]);
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $backupPath,
            'size' => filesize($backupPath),
            'size_formatted' => self::formatBytes(filesize($backupPath)),
            'timestamp' => $timestamp
        ];
    }
    
    /**
     * Compress backup file
     */
    private static function compressBackup(string $filePath): ?string
    {
        if (!function_exists('gzencode')) {
            return null;
        }
        
        $compressedPath = $filePath . '.gz';
        $data = file_get_contents($filePath);
        $compressed = gzencode($data, 9); // Max compression
        
        if (file_put_contents($compressedPath, $compressed) === false) {
            return null;
        }
        
        return $compressedPath;
    }
    
    /**
     * Restore backup
     */
    public static function restoreBackup(string $filename): bool
    {
        $backupDir = self::getBackupDir();
        $backupPath = $backupDir . '/' . $filename;
        
        if (!file_exists($backupPath)) {
            throw new Exception('Backup file not found');
        }
        
        // Decompress if needed
        $sourcePath = $backupPath;
        if (pathinfo($filename, PATHINFO_EXTENSION) === 'gz') {
            $sourcePath = self::decompressBackup($backupPath);
            if (!$sourcePath) {
                throw new Exception('Failed to decompress backup');
            }
        }
        
        // Backup current database before restore
        try {
            self::createBackup('pre_restore');
        } catch (Exception $e) {
            // Log but don't fail
            if (class_exists('Logger')) {
                Logger::warning('Failed to create pre-restore backup', ['error' => $e->getMessage()]);
            }
        }
        
        // Restore database
        $dbPath = DB_PATH;
        if (!copy($sourcePath, $dbPath)) {
            if (isset($sourcePath) && $sourcePath !== $backupPath) {
                unlink($sourcePath); // Cleanup temp file
            }
            throw new Exception('Failed to restore backup');
        }
        
        // Cleanup temp file if created
        if (isset($sourcePath) && $sourcePath !== $backupPath) {
            unlink($sourcePath);
        }
        
        // Clear all caches after restore
        Cache::flush();
        
        if (class_exists('Logger')) {
            Logger::info('Database restored from backup', ['filename' => $filename]);
        }
        
        return true;
    }
    
    /**
     * Decompress backup file
     */
    private static function decompressBackup(string $filePath): ?string
    {
        $data = gzdecode(file_get_contents($filePath));
        if ($data === false) {
            return null;
        }
        
        $tempPath = sys_get_temp_dir() . '/' . basename($filePath, '.gz');
        if (file_put_contents($tempPath, $data) === false) {
            return null;
        }
        
        return $tempPath;
    }
    
    /**
     * List all backups
     */
    public static function listBackups(): array
    {
        $backupDir = self::getBackupDir();
        
        if (!is_dir($backupDir)) {
            return [];
        }
        
        $backups = [];
        $files = glob($backupDir . '/backup_*.sqlite*');
        
        foreach ($files as $file) {
            $filename = basename($file);
            $filetime = filemtime($file);
            
            $backups[] = [
                'filename' => $filename,
                'size' => filesize($file),
                'size_formatted' => self::formatBytes(filesize($file)),
                'created_at' => date('Y-m-d H:i:s', $filetime),
                'timestamp' => $filetime,
                'is_compressed' => pathinfo($filename, PATHINFO_EXTENSION) === 'gz'
            ];
        }
        
        // Sort by timestamp (newest first)
        usort($backups, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });
        
        return $backups;
    }
    
    /**
     * Delete backup
     */
    public static function deleteBackup(string $filename): bool
    {
        $backupDir = self::getBackupDir();
        $backupPath = $backupDir . '/' . $filename;
        
        if (!file_exists($backupPath)) {
            return false;
        }
        
        $result = unlink($backupPath);
        
        if ($result && class_exists('Logger')) {
            Logger::info('Backup deleted', ['filename' => $filename]);
        }
        
        return $result;
    }
    
    /**
     * Cleanup old backups (keep only last N)
     */
    private static function cleanupOldBackups(): void
    {
        $backups = self::listBackups();
        
        if (count($backups) <= self::$maxBackups) {
            return;
        }
        
        // Delete oldest backups
        $toDelete = array_slice($backups, self::$maxBackups);
        
        foreach ($toDelete as $backup) {
            self::deleteBackup($backup['filename']);
        }
        
        if (count($toDelete) > 0 && class_exists('Logger')) {
            Logger::info('Old backups cleaned up', ['deleted' => count($toDelete)]);
        }
    }
    
    /**
     * Get backup statistics
     */
    public static function getStats(): array
    {
        $backups = self::listBackups();
        $totalSize = array_sum(array_column($backups, 'size'));
        
        return [
            'total_backups' => count($backups),
            'total_size' => $totalSize,
            'total_size_formatted' => self::formatBytes($totalSize),
            'oldest_backup' => !empty($backups) ? end($backups)['created_at'] : null,
            'newest_backup' => !empty($backups) ? $backups[0]['created_at'] : null,
            'max_backups' => self::$maxBackups,
            'auto_backup_enabled' => self::$autoBackupEnabled
        ];
    }
    
    /**
     * Schedule automatic backup (should be called via cron)
     */
    public static function scheduleAutoBackup(): void
    {
        if (!self::$autoBackupEnabled) {
            return;
        }
        
        try {
            // Check if backup was already created today
            $backups = self::listBackups();
            $today = date('Y-m-d');
            
            foreach ($backups as $backup) {
                if (strpos($backup['created_at'], $today) === 0) {
                    // Backup already exists for today
                    return;
                }
            }
            
            // Create daily backup
            self::createBackup('auto_daily');
            
        } catch (Exception $e) {
            if (class_exists('Logger')) {
                Logger::error('Auto backup failed', ['error' => $e->getMessage()]);
            }
        }
    }
    
    /**
     * Record backup in metadata file
     */
    private static function recordBackup(string $filename, int $size): void
    {
        $backupDir = self::getBackupDir();
        $metadataFile = $backupDir . '/.metadata.json';
        $metadata = [];
        
        if (file_exists($metadataFile)) {
            $metadata = json_decode(file_get_contents($metadataFile), true) ?: [];
        }
        
        $userId = null;
        if (class_exists('Auth') && method_exists('Auth', 'check')) {
            try {
                $userId = Auth::check() ? Auth::id() : null;
            } catch (Exception $e) {
                // Silently fail - auth is not critical for backup metadata
            }
        }
        
        $metadata[] = [
            'filename' => $filename,
            'size' => $size,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $userId
        ];
        
        // Keep only last 100 entries
        $metadata = array_slice($metadata, -100);
        
        file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT), LOCK_EX);
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
}

