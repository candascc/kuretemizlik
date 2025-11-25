<?php
/**
 * Crawl Status Manager
 * 
 * Manages crawl test locks and status tracking
 * Prevents multiple crawl tests from running simultaneously
 */

class CrawlStatusManager
{
    /**
     * Lock directory
     */
    private string $lockDir;
    
    /**
     * Progress directory (shared with CrawlProgressTracker)
     */
    private string $progressDir;
    
    /**
     * Lock timeout in seconds (30 minutes)
     */
    private const LOCK_TIMEOUT = 1800;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $tempDir = sys_get_temp_dir();
        $this->lockDir = $tempDir . '/crawl_locks';
        $this->progressDir = $tempDir . '/crawl_progress';
        
        // Ensure directories exist
        if (!is_dir($this->lockDir)) {
            @mkdir($this->lockDir, 0755, true);
        }
        if (!is_dir($this->progressDir)) {
            @mkdir($this->progressDir, 0755, true);
        }
    }
    
    /**
     * Generate unique test ID
     * 
     * @param int $userId User ID
     * @return string Test ID
     */
    public function generateTestId(int $userId): string
    {
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        return "crawl_{$userId}_{$timestamp}_{$random}";
    }
    
    /**
     * Create lock file for a crawl test
     * 
     * @param string $testId Test ID
     * @param string $role Role being tested
     * @param int $userId User ID starting the test
     * @return bool True if lock was created, false if already locked
     */
    public function createLock(string $testId, string $role, int $userId): bool
    {
        // Check if any lock exists
        if ($this->isLocked()) {
            return false;
        }
        
        // Clean stale locks first
        $this->cleanStaleLocks();
        
        // Create lock file
        $lockFile = $this->getLockFilePath($testId);
        $lockData = [
            'testId' => $testId,
            'role' => $role,
            'userId' => $userId,
            'startTime' => time(),
            'status' => 'running',
            'progressFile' => $this->progressDir . '/' . $testId . '.json',
        ];
        
        $result = @file_put_contents($lockFile, json_encode($lockData, JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $result !== false;
    }
    
    /**
     * Release lock file
     * 
     * @param string $testId Test ID
     * @return bool True if lock was released
     */
    public function releaseLock(string $testId): bool
    {
        $lockFile = $this->getLockFilePath($testId);
        if (file_exists($lockFile)) {
            return @unlink($lockFile);
        }
        return false;
    }
    
    /**
     * Check if a crawl test is currently running
     * 
     * @return bool True if locked
     */
    public function isLocked(): bool
    {
        $this->cleanStaleLocks();
        
        $lockFiles = glob($this->lockDir . '/*.lock');
        foreach ($lockFiles as $lockFile) {
            $lockData = $this->readLockFile($lockFile);
            if ($lockData && $lockData['status'] === 'running') {
                // Check if lock is stale
                $age = time() - ($lockData['startTime'] ?? 0);
                if ($age < self::LOCK_TIMEOUT) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Get current lock information
     * 
     * @return array|null Lock data or null if no lock
     */
    public function getCurrentLock(): ?array
    {
        if (!$this->isLocked()) {
            return null;
        }
        
        $lockFiles = glob($this->lockDir . '/*.lock');
        foreach ($lockFiles as $lockFile) {
            $lockData = $this->readLockFile($lockFile);
            if ($lockData && $lockData['status'] === 'running') {
                $age = time() - ($lockData['startTime'] ?? 0);
                if ($age < self::LOCK_TIMEOUT) {
                    return $lockData;
                }
            }
        }
        return null;
    }
    
    /**
     * Get status for a specific test ID
     * 
     * @param string $testId Test ID
     * @return array|null Status data or null if not found
     */
    public function getStatus(string $testId): ?array
    {
        $lockFile = $this->getLockFilePath($testId);
        $lockData = $this->readLockFile($lockFile);
        
        if (!$lockData) {
            return null;
        }
        
        // Get progress data
        $progressFile = $lockData['progressFile'] ?? ($this->progressDir . '/' . $testId . '.json');
        $progressData = null;
        if (file_exists($progressFile)) {
            $progressContent = @file_get_contents($progressFile);
            if ($progressContent !== false) {
                $progressData = json_decode($progressContent, true);
            }
        }
        
        // Determine status
        $lockStatus = $lockData['status'] ?? 'running'; // Default to running if not set
        $lockAge = time() - ($lockData['startTime'] ?? 0);
        $isLockValid = $lockAge < self::LOCK_TIMEOUT;
        
        // If lock exists and is recent, assume running (even if progress file not yet created)
        if ($isLockValid && ($lockStatus === 'running' || $lockStatus === 'unknown')) {
            $finalStatus = 'running';
        } elseif ($lockStatus === 'completed') {
            $finalStatus = 'completed';
        } elseif ($lockStatus === 'failed') {
            $finalStatus = 'failed';
        } else {
            // Lock is stale or invalid
            $finalStatus = 'unknown';
        }
        
        return [
            'testId' => $testId,
            'role' => $lockData['role'] ?? '',
            'status' => $finalStatus,
            'startTime' => $lockData['startTime'] ?? 0,
            'elapsed' => $lockAge,
            'progress' => $progressData,
        ];
    }
    
    /**
     * Update lock status
     * 
     * @param string $testId Test ID
     * @param string $status Status ('running', 'completed', 'failed')
     * @return bool True if updated
     */
    public function updateStatus(string $testId, string $status): bool
    {
        $lockFile = $this->getLockFilePath($testId);
        $lockData = $this->readLockFile($lockFile);
        
        if (!$lockData) {
            return false;
        }
        
        $lockData['status'] = $status;
        if ($status === 'completed' || $status === 'failed') {
            $lockData['endTime'] = time();
        }
        
        return @file_put_contents($lockFile, json_encode($lockData, JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
    }
    
    /**
     * Clean stale locks (older than LOCK_TIMEOUT)
     * 
     * @return int Number of locks cleaned
     */
    public function cleanStaleLocks(): int
    {
        $cleaned = 0;
        $lockFiles = glob($this->lockDir . '/*.lock');
        
        if (!$lockFiles) {
            return 0;
        }
        
        foreach ($lockFiles as $lockFile) {
            $lockData = $this->readLockFile($lockFile);
            if ($lockData) {
                $age = time() - ($lockData['startTime'] ?? 0);
                // Clean locks older than timeout OR locks that haven't been updated in a while
                // Also clean if status is 'running' but no progress file exists or hasn't been updated
                $shouldClean = false;
                
                if ($age >= self::LOCK_TIMEOUT) {
                    $shouldClean = true;
                } else {
                    // Check if progress file exists and was recently updated
                    $progressFile = $lockData['progressFile'] ?? ($this->progressDir . '/' . ($lockData['testId'] ?? '') . '.json');
                    if (file_exists($progressFile)) {
                        $progressMtime = filemtime($progressFile);
                        $progressAge = time() - $progressMtime;
                        // If progress file hasn't been updated in 2 minutes and lock is older than 2 minutes, it's stale
                        if ($progressAge > 120 && $age > 120) {
                            $shouldClean = true;
                        }
                    } else {
                        // No progress file but lock is older than 5 minutes - likely stale
                        if ($age > 300) {
                            $shouldClean = true;
                        }
                    }
                }
                
                if ($shouldClean) {
                    // Also clean associated progress file
                    $progressFile = $lockData['progressFile'] ?? ($this->progressDir . '/' . ($lockData['testId'] ?? '') . '.json');
                    if (file_exists($progressFile)) {
                        @unlink($progressFile);
                    }
                    @unlink($lockFile);
                    $cleaned++;
                }
            } else {
                // Invalid lock file - remove it
                @unlink($lockFile);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Get lock file path for a test ID
     * 
     * @param string $testId Test ID
     * @return string Lock file path
     */
    private function getLockFilePath(string $testId): string
    {
        return $this->lockDir . '/' . $testId . '.lock';
    }
    
    /**
     * Read and parse lock file
     * 
     * @param string $lockFile Lock file path
     * @return array|null Lock data or null if invalid
     */
    private function readLockFile(string $lockFile): ?array
    {
        if (!file_exists($lockFile)) {
            return null;
        }
        
        $content = @file_get_contents($lockFile);
        if ($content === false) {
            return null;
        }
        
        $data = json_decode($content, true);
        return is_array($data) ? $data : null;
    }
}

