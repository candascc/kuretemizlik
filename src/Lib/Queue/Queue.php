<?php
/**
 * Queue System
 * Enterprise-level job queue with Redis and database support
 */

class Queue
{
    private $redis;
    private $db;
    private $config;
    private $isConnected = false;
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'driver' => $_ENV['QUEUE_DRIVER'] ?? 'database',
            'redis_host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'redis_port' => $_ENV['REDIS_PORT'] ?? 6379,
            'redis_password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'redis_database' => $_ENV['REDIS_DATABASE'] ?? 1,
            'prefix' => $_ENV['QUEUE_PREFIX'] ?? 'queue:',
            'default_queue' => 'default',
            'retry_after' => 90,
            'max_tries' => 3,
            'timeout' => 60
        ], $config);
        
        $this->db = Database::getInstance();
        $this->initializeDriver();
    }
    
    /**
     * Initialize queue driver
     */
    private function initializeDriver(): void
    {
        if ($this->config['driver'] === 'redis') {
            $this->initializeRedis();
        }
    }
    
    /**
     * Initialize Redis connection
     */
    private function initializeRedis(): void
    {
        try {
            $this->redis = new Redis();
            $this->redis->connect(
                $this->config['redis_host'],
                $this->config['redis_port'],
                5 // timeout
            );
            
            if ($this->config['redis_password']) {
                $this->redis->auth($this->config['redis_password']);
            }
            
            $this->redis->select($this->config['redis_database']);
            $this->redis->setOption(Redis::OPT_PREFIX, $this->config['prefix']);
            
            $this->isConnected = true;
            
            Logger::info('Queue Redis connection established');
            
        } catch (Exception $e) {
            $this->isConnected = false;
            Logger::error('Queue Redis connection failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Push job to queue
     */
    public function push(string $job, array $payload = [], string $queue = null): string
    {
        $queue = $queue ?? $this->config['default_queue'];
        $jobId = uniqid('job_', true);
        
        $jobData = [
            'id' => $jobId,
            'job' => $job,
            'payload' => $payload,
            'queue' => $queue,
            'attempts' => 0,
            'max_tries' => $this->config['max_tries'],
            'retry_after' => $this->config['retry_after'],
            'timeout' => $this->config['timeout'],
            'created_at' => time(),
            'available_at' => time(),
            'reserved_at' => null,
            'failed_at' => null
        ];
        
        if ($this->config['driver'] === 'redis' && $this->isConnected) {
            return $this->pushToRedis($jobData, $queue);
        } else {
            return $this->pushToDatabase($jobData);
        }
    }
    
    /**
     * Push job to Redis
     */
    private function pushToRedis(array $jobData, string $queue): string
    {
        $this->redis->lpush("queues:{$queue}", json_encode($jobData));
        $this->redis->hset("jobs:{$jobData['id']}", $jobData);
        
        Logger::info('Job pushed to Redis queue', [
            'job_id' => $jobData['id'],
            'queue' => $queue,
            'job' => $jobData['job']
        ]);
        
        return $jobData['id'];
    }
    
    /**
     * Push job to database
     */
    private function pushToDatabase(array $jobData): string
    {
        $this->db->query(
            "INSERT INTO queue_jobs (id, queue, payload, attempts, max_tries, retry_after, timeout, created_at, available_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $jobData['id'],
                $jobData['queue'],
                json_encode($jobData),
                $jobData['attempts'],
                $jobData['max_tries'],
                $jobData['retry_after'],
                $jobData['timeout'],
                $jobData['created_at'],
                $jobData['available_at']
            ]
        );
        
        Logger::info('Job pushed to database queue', [
            'job_id' => $jobData['id'],
            'queue' => $jobData['queue'],
            'job' => $jobData['job']
        ]);
        
        return $jobData['id'];
    }
    
    /**
     * Pop job from queue
     */
    public function pop(string $queue = null): ?array
    {
        $queue = $queue ?? $this->config['default_queue'];
        
        if ($this->config['driver'] === 'redis' && $this->isConnected) {
            return $this->popFromRedis($queue);
        } else {
            return $this->popFromDatabase($queue);
        }
    }
    
    /**
     * Pop job from Redis
     */
    private function popFromRedis(string $queue): ?array
    {
        $jobData = $this->redis->brpop("queues:{$queue}", 5);
        
        if (!$jobData) {
            return null;
        }
        
        $job = json_decode($jobData[1], true);
        $job['reserved_at'] = time();
        
        // Update job status
        $this->redis->hset("jobs:{$job['id']}", 'reserved_at', $job['reserved_at']);
        
        return $job;
    }
    
    /**
     * Pop job from database
     */
    private function popFromDatabase(string $queue): ?array
    {
        $this->db->beginTransaction();
        
        try {
            // SQLite doesn't support FOR UPDATE, use a different approach
            $job = $this->db->fetch(
                "SELECT * FROM queue_jobs 
                 WHERE queue = ? AND reserved_at IS NULL AND available_at <= ? 
                 ORDER BY created_at ASC 
                 LIMIT 1",
                [$queue, time()]
            );
            
            if (!$job) {
                $this->db->rollback();
                return null;
            }
            
            // Reserve job
            $this->db->query(
                "UPDATE queue_jobs SET reserved_at = ?, attempts = attempts + 1 WHERE id = ?",
                [time(), $job['id']]
            );
            
            $this->db->commit();
            
            $jobData = json_decode($job['payload'], true);
            if (!$jobData) {
                return null;
            }
            
            $jobData['reserved_at'] = time();
            $jobData['attempts'] = $job['attempts'] + 1;
            
            return $jobData;
            
        } catch (Exception $e) {
            $this->db->rollback();
            Logger::error('Failed to pop job from database', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Mark job as completed
     */
    public function markCompleted(string $jobId): bool
    {
        if ($this->config['driver'] === 'redis' && $this->isConnected) {
            return $this->markCompletedRedis($jobId);
        } else {
            return $this->markCompletedDatabase($jobId);
        }
    }
    
    /**
     * Mark job as completed in Redis
     */
    private function markCompletedRedis(string $jobId): bool
    {
        $this->redis->hdel("jobs:{$jobId}");
        return true;
    }
    
    /**
     * Mark job as completed in database
     */
    private function markCompletedDatabase(string $jobId): bool
    {
        $stmt = $this->db->query("DELETE FROM queue_jobs WHERE id = ?", [$jobId]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Mark job as failed
     */
    public function markFailed(string $jobId, string $error = null): bool
    {
        if ($this->config['driver'] === 'redis' && $this->isConnected) {
            return $this->markFailedRedis($jobId, $error);
        } else {
            return $this->markFailedDatabase($jobId, $error);
        }
    }
    
    /**
     * Mark job as failed in Redis
     */
    private function markFailedRedis(string $jobId, string $error = null): bool
    {
        $jobData = $this->redis->hgetall("jobs:{$jobId}");
        if ($jobData) {
            $jobData['failed_at'] = time();
            $jobData['error'] = $error;
            $this->redis->hset("failed_jobs:{$jobId}", $jobData);
            $this->redis->hdel("jobs:{$jobId}");
        }
        return true;
    }
    
    /**
     * Mark job as failed in database
     */
    private function markFailedDatabase(string $jobId, string $error = null): bool
    {
        return $this->db->query(
            "UPDATE queue_jobs SET failed_at = ?, error = ? WHERE id = ?",
            [time(), $error, $jobId]
        );
    }
    
    /**
     * Retry failed job
     */
    public function retry(string $jobId): bool
    {
        if ($this->config['driver'] === 'redis' && $this->isConnected) {
            return $this->retryRedis($jobId);
        } else {
            return $this->retryDatabase($jobId);
        }
    }
    
    /**
     * Retry failed job in Redis
     */
    private function retryRedis(string $jobId): bool
    {
        $jobData = $this->redis->hgetall("failed_jobs:{$jobId}");
        if ($jobData) {
            $jobData['attempts'] = 0;
            $jobData['available_at'] = time();
            $jobData['reserved_at'] = null;
            $jobData['failed_at'] = null;
            unset($jobData['error']);
            
            $this->redis->lpush("queues:{$jobData['queue']}", json_encode($jobData));
            $this->redis->hset("jobs:{$jobId}", $jobData);
            $this->redis->hdel("failed_jobs:{$jobId}");
        }
        return true;
    }
    
    /**
     * Retry failed job in database
     */
    private function retryDatabase(string $jobId): bool
    {
        return $this->db->query(
            "UPDATE queue_jobs SET attempts = 0, available_at = ?, reserved_at = NULL, failed_at = NULL, error = NULL WHERE id = ?",
            [time(), $jobId]
        );
    }
    
    /**
     * Get queue statistics
     */
    public function getStats(): array
    {
        if ($this->config['driver'] === 'redis' && $this->isConnected) {
            return $this->getStatsRedis();
        } else {
            return $this->getStatsDatabase();
        }
    }
    
    /**
     * Get Redis queue statistics
     */
    private function getStatsRedis(): array
    {
        $queues = ['default', 'high', 'low'];
        $stats = [];
        
        foreach ($queues as $queue) {
            $stats[$queue] = [
                'pending' => $this->redis->llen("queues:{$queue}"),
                'failed' => $this->redis->hlen("failed_jobs")
            ];
        }
        
        return $stats;
    }
    
    /**
     * Get database queue statistics
     */
    private function getStatsDatabase(): array
    {
        $stats = $this->db->fetchAll(
            "SELECT queue, 
                    COUNT(*) as total,
                    SUM(CASE WHEN reserved_at IS NULL THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN reserved_at IS NOT NULL AND failed_at IS NULL THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN failed_at IS NOT NULL THEN 1 ELSE 0 END) as failed
             FROM queue_jobs 
             GROUP BY queue"
        );
        
        $result = [];
        foreach ($stats as $stat) {
            $result[$stat['queue']] = [
                'pending' => $stat['pending'],
                'processing' => $stat['processing'],
                'failed' => $stat['failed'],
                'total' => $stat['total']
            ];
        }
        
        return $result;
    }
    
    /**
     * Get failed jobs
     */
    public function getFailedJobs(int $limit = 50): array
    {
        if ($this->config['driver'] === 'redis' && $this->isConnected) {
            return $this->getFailedJobsRedis($limit);
        } else {
            return $this->getFailedJobsDatabase($limit);
        }
    }
    
    /**
     * Get failed jobs from Redis
     */
    private function getFailedJobsRedis(int $limit): array
    {
        $failedJobs = $this->redis->hgetall("failed_jobs");
        $jobs = [];
        
        $count = 0;
        foreach ($failedJobs as $jobId => $jobData) {
            if ($count >= $limit) break;
            
            $jobs[] = json_decode($jobData, true);
            $count++;
        }
        
        return $jobs;
    }
    
    /**
     * Get failed jobs from database
     */
    private function getFailedJobsDatabase(int $limit): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM queue_jobs WHERE failed_at IS NOT NULL ORDER BY failed_at DESC LIMIT ?",
            [$limit]
        );
    }
    
    /**
     * Clear failed jobs
     */
    public function clearFailedJobs(): bool
    {
        if ($this->config['driver'] === 'redis' && $this->isConnected) {
            $this->redis->del("failed_jobs");
            return true;
        } else {
            return $this->db->query("DELETE FROM queue_jobs WHERE failed_at IS NOT NULL");
        }
    }
    
    /**
     * Get queue size
     */
    public function size(string $queue = null): int
    {
        $queue = $queue ?? $this->config['default_queue'];
        
        if ($this->config['driver'] === 'redis' && $this->isConnected) {
            return $this->redis->llen("queues:{$queue}");
        } else {
            $result = $this->db->fetch(
                "SELECT COUNT(*) as count FROM queue_jobs WHERE queue = ? AND reserved_at IS NULL",
                [$queue]
            );
            return $result['count'] ?? 0;
        }
    }
    
    /**
     * Check if queue is empty
     */
    public function isEmpty(string $queue = null): bool
    {
        return $this->size($queue) === 0;
    }
    
    /**
     * Get all queues
     */
    public function getQueues(): array
    {
        if ($this->config['driver'] === 'redis' && $this->isConnected) {
            $keys = $this->redis->keys("queues:*");
            return array_map(function($key) {
                return str_replace('queues:', '', $key);
            }, $keys);
        } else {
            $queues = $this->db->fetchAll("SELECT DISTINCT queue FROM queue_jobs");
            return array_column($queues, 'queue');
        }
    }
    
    /**
     * Flush queue
     */
    public function flush(string $queue = null): bool
    {
        if ($queue) {
            if ($this->config['driver'] === 'redis' && $this->isConnected) {
                $this->redis->del("queues:{$queue}");
            } else {
                $this->db->query("DELETE FROM queue_jobs WHERE queue = ?", [$queue]);
            }
        } else {
            if ($this->config['driver'] === 'redis' && $this->isConnected) {
                $this->redis->flushDB();
            } else {
                $this->db->query("DELETE FROM queue_jobs");
            }
        }
        
        return true;
    }
    
    /**
     * Check connection status
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }
}
