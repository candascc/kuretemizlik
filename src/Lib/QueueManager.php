<?php
/**
 * Queue Manager
 * Centralized queue management system
 */

class QueueManager
{
    private static $instance = null;
    private $queue;
    private $config;
    
    private function __construct()
    {
        $this->config = [
            'driver' => $_ENV['QUEUE_DRIVER'] ?? 'database',
            'default_queue' => 'default',
            'queues' => ['default', 'high', 'low'],
            'retry_after' => 90,
            'max_tries' => 3,
            'timeout' => 60
        ];
        
        // Load Queue class
        if (!class_exists('Queue')) {
            require_once __DIR__ . '/Queue/Queue.php';
        }
        
        $this->queue = new Queue($this->config);
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Push job to queue
     */
    public function push(string $job, array $payload = [], string $queue = null): string
    {
        $queue = $queue ?? $this->config['default_queue'];
        return $this->queue->push($job, $payload, $queue);
    }
    
    /**
     * Push job with delay
     */
    public function pushAfter(string $job, array $payload, int $delay, string $queue = null): string
    {
        $queue = $queue ?? $this->config['default_queue'];
        $payload['_delay'] = $delay;
        $payload['_available_at'] = time() + $delay;
        
        return $this->queue->push($job, $payload, $queue);
    }
    
    /**
     * Push job at specific time
     */
    public function pushAt(string $job, array $payload, \DateTime $dateTime, string $queue = null): string
    {
        $queue = $queue ?? $this->config['default_queue'];
        $delay = $dateTime->getTimestamp() - time();
        
        if ($delay <= 0) {
            return $this->push($job, $payload, $queue);
        }
        
        return $this->pushAfter($job, $payload, $delay, $queue);
    }
    
    /**
     * Push job to high priority queue
     */
    public function pushHigh(string $job, array $payload = []): string
    {
        return $this->push($job, $payload, 'high');
    }
    
    /**
     * Push job to low priority queue
     */
    public function pushLow(string $job, array $payload = []): string
    {
        return $this->push($job, $payload, 'low');
    }
    
    /**
     * Pop job from queue
     */
    public function pop(string $queue = null): ?array
    {
        $queue = $queue ?? $this->config['default_queue'];
        return $this->queue->pop($queue);
    }
    
    /**
     * Mark job as completed
     */
    public function markCompleted(string $jobId): bool
    {
        return $this->queue->markCompleted($jobId);
    }
    
    /**
     * Mark job as failed
     */
    public function markFailed(string $jobId, string $error = null): bool
    {
        return $this->queue->markFailed($jobId, $error);
    }
    
    /**
     * Retry failed job
     */
    public function retry(string $jobId): bool
    {
        return $this->queue->retry($jobId);
    }
    
    /**
     * Get queue statistics
     */
    public function getStats(): array
    {
        return $this->queue->getStats();
    }
    
    /**
     * Get failed jobs
     */
    public function getFailedJobs(int $limit = 50): array
    {
        return $this->queue->getFailedJobs($limit);
    }
    
    /**
     * Clear failed jobs
     */
    public function clearFailedJobs(): bool
    {
        return $this->queue->clearFailedJobs();
    }
    
    /**
     * Get queue size
     */
    public function size(string $queue = null): int
    {
        $queue = $queue ?? $this->config['default_queue'];
        return $this->queue->size($queue);
    }
    
    /**
     * Check if queue is empty
     */
    public function isEmpty(string $queue = null): bool
    {
        return $this->queue->isEmpty($queue);
    }
    
    /**
     * Get all queues
     */
    public function getQueues(): array
    {
        return $this->queue->getQueues();
    }
    
    /**
     * Flush queue
     */
    public function flush(string $queue = null): bool
    {
        return $this->queue->flush($queue);
    }
    
    /**
     * Get queue configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
    
    /**
     * Set queue configuration
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }
    
    /**
     * Check if queue is connected
     */
    public function isConnected(): bool
    {
        return $this->queue->isConnected();
    }
    
    /**
     * Get queue health status
     */
    public function getHealthStatus(): array
    {
        $stats = $this->getStats();
        $totalJobs = 0;
        $totalFailed = 0;
        
        foreach ($stats as $queue => $stat) {
            $totalJobs += $stat['total'] ?? 0;
            $totalFailed += $stat['failed'] ?? 0;
        }
        
        return [
            'status' => $this->isConnected() ? 'healthy' : 'unhealthy',
            'connected' => $this->isConnected(),
            'total_jobs' => $totalJobs,
            'total_failed' => $totalFailed,
            'queues' => $stats,
            'timestamp' => time()
        ];
    }
    
    /**
     * Get queue performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $stats = $this->getStats();
        $metrics = [];
        
        foreach ($stats as $queue => $stat) {
            $metrics[$queue] = [
                'pending' => $stat['pending'] ?? 0,
                'processing' => $stat['processing'] ?? 0,
                'failed' => $stat['failed'] ?? 0,
                'total' => $stat['total'] ?? 0,
                'success_rate' => $stat['total'] > 0 ? round((($stat['total'] - ($stat['failed'] ?? 0)) / $stat['total']) * 100, 2) : 100
            ];
        }
        
        return $metrics;
    }
    
    /**
     * Get queue alerts
     */
    public function getAlerts(): array
    {
        $alerts = [];
        $stats = $this->getStats();
        
        foreach ($stats as $queue => $stat) {
            // High failure rate alert
            if (isset($stat['failed']) && isset($stat['total']) && $stat['total'] > 0) {
                $failureRate = ($stat['failed'] / $stat['total']) * 100;
                if ($failureRate > 20) {
                    $alerts[] = [
                        'type' => 'high_failure_rate',
                        'queue' => $queue,
                        'message' => "High failure rate in {$queue} queue: {$failureRate}%",
                        'severity' => 'warning'
                    ];
                }
            }
            
            // Large queue size alert
            if (isset($stat['pending']) && $stat['pending'] > 100) {
                $alerts[] = [
                    'type' => 'large_queue_size',
                    'queue' => $queue,
                    'message' => "Large queue size in {$queue} queue: {$stat['pending']} jobs",
                    'severity' => 'info'
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * Get queue recommendations
     */
    public function getRecommendations(): array
    {
        $recommendations = [];
        $stats = $this->getStats();
        
        foreach ($stats as $queue => $stat) {
            // Add more workers recommendation
            if (isset($stat['pending']) && $stat['pending'] > 50) {
                $recommendations[] = [
                    'type' => 'add_workers',
                    'queue' => $queue,
                    'message' => "Consider adding more workers for {$queue} queue",
                    'priority' => 'medium'
                ];
            }
            
            // Check failed jobs
            if (isset($stat['failed']) && $stat['failed'] > 10) {
                $recommendations[] = [
                    'type' => 'check_failed_jobs',
                    'queue' => $queue,
                    'message' => "Review failed jobs in {$queue} queue",
                    'priority' => 'high'
                ];
            }
        }
        
        return $recommendations;
    }
}
