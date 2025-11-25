<?php
/**
 * Crawl Progress Tracker
 * 
 * Tracks crawl progress for real-time updates
 * Uses file-based storage for simplicity (can be extended to use Redis/database)
 */

class CrawlProgressTracker
{
    /**
     * Progress file path
     */
    private string $progressFile;
    
    /**
     * Constructor
     * 
     * @param string $requestId Unique request ID
     */
    public function __construct(string $requestId)
    {
        $progressDir = sys_get_temp_dir() . '/crawl_progress';
        if (!is_dir($progressDir)) {
            @mkdir($progressDir, 0755, true);
        }
        $this->progressFile = $progressDir . '/' . $requestId . '.json';
    }
    
    /**
     * Update progress
     * 
     * @param int $current Current item count
     * @param int $total Total items
     * @param string $currentUrl Current URL being crawled
     * @param int $successCount Success count
     * @param int $errorCount Error count
     * @param array $items Optional items list to include in progress (for recent items display)
     */
    public function update(int $current, int $total, string $currentUrl = '', int $successCount = 0, int $errorCount = 0, array $items = []): void
    {
        // Read existing progress to preserve items if not provided
        $existingProgress = $this->get();
        $existingItems = $existingProgress['items'] ?? [];
        
        $progress = [
            'current' => $current,
            'total' => $total,
            'percentage' => $total > 0 ? round(($current / $total) * 100, 2) : 0,
            'current_url' => $currentUrl,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'timestamp' => time(),
            'items' => !empty($items) ? array_slice($items, -50) : array_slice($existingItems, -50), // Keep last 50 items
        ];
        
        @file_put_contents($this->progressFile, json_encode($progress, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
    
    /**
     * Get progress
     * 
     * @return array|null Progress data or null if not found
     */
    public function get(): ?array
    {
        if (!file_exists($this->progressFile)) {
            return null;
        }
        
        $content = @file_get_contents($this->progressFile);
        if ($content === false) {
            return null;
        }
        
        $progress = json_decode($content, true);
        return $progress ?: null;
    }
    
    /**
     * Clear progress
     */
    public function clear(): void
    {
        if (file_exists($this->progressFile)) {
            @unlink($this->progressFile);
        }
    }
    
    /**
     * Calculate ETA (Estimated Time to Arrival)
     * 
     * @param int $startTime Start timestamp
     * @param int $current Current item count
     * @param int $total Total items
     * @return int ETA in seconds
     */
    public static function calculateEta(int $startTime, int $current, int $total): int
    {
        if ($current <= 0 || $total <= 0) {
            return 0;
        }
        
        $elapsed = time() - $startTime;
        $rate = $current / $elapsed; // items per second
        $remaining = $total - $current;
        
        return $rate > 0 ? (int)($remaining / $rate) : 0;
    }
}

