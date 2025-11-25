<?php
/**
 * Base Job Class
 * Abstract base class for all background jobs
 */

abstract class Job
{
    protected $payload;
    protected $attempts;
    protected $maxTries;
    protected $timeout;
    protected $jobId;
    
    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
        $this->attempts = 0;
        $this->maxTries = 3;
        $this->timeout = 60;
        $this->jobId = uniqid('job_', true);
    }
    
    /**
     * Execute the job
     */
    abstract public function handle(): void;
    
    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Logger::error('Job failed', [
            'job_id' => $this->jobId,
            'job_class' => get_class($this),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
    
    /**
     * Get job timeout
     */
    public function timeout(): int
    {
        return $this->timeout;
    }
    
    /**
     * Get max tries
     */
    public function maxTries(): int
    {
        return $this->maxTries;
    }
    
    /**
     * Get retry delay
     */
    public function retryAfter(): int
    {
        return 60; // 1 minute
    }
    
    /**
     * Get job payload
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
    
    /**
     * Set job payload
     */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }
    
    /**
     * Get job ID
     */
    public function getJobId(): string
    {
        return $this->jobId;
    }
    
    /**
     * Set job ID
     */
    public function setJobId(string $jobId): void
    {
        $this->jobId = $jobId;
    }
    
    /**
     * Get attempts
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }
    
    /**
     * Set attempts
     */
    public function setAttempts(int $attempts): void
    {
        $this->attempts = $attempts;
    }
    
    /**
     * Increment attempts
     */
    public function incrementAttempts(): void
    {
        $this->attempts++;
    }
    
    /**
     * Check if job should be retried
     */
    public function shouldRetry(): bool
    {
        return $this->attempts < $this->maxTries;
    }
    
    /**
     * Get job name
     */
    public function getName(): string
    {
        return get_class($this);
    }
    
    /**
     * Get job display name
     */
    public function getDisplayName(): string
    {
        return class_basename($this);
    }
    
    /**
     * Get job tags
     */
    public function getTags(): array
    {
        return [];
    }
    
    /**
     * Get job metadata
     */
    public function getMetadata(): array
    {
        return [
            'job_id' => $this->jobId,
            'job_class' => $this->getName(),
            'attempts' => $this->attempts,
            'max_tries' => $this->maxTries,
            'timeout' => $this->timeout,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Dispatch job to queue
     */
    public function dispatch(string $queue = null): string
    {
        $queueManager = QueueManager::getInstance();
        return $queueManager->push($this->getName(), $this->payload, $queue);
    }
    
    /**
     * Dispatch job with delay
     */
    public function dispatchAfter(int $delay, string $queue = null): string
    {
        $queueManager = QueueManager::getInstance();
        return $queueManager->pushAfter($this->getName(), $this->payload, $delay, $queue);
    }
    
    /**
     * Dispatch job at specific time
     */
    public function dispatchAt(\DateTime $dateTime, string $queue = null): string
    {
        $queueManager = QueueManager::getInstance();
        return $queueManager->pushAt($this->getName(), $this->payload, $dateTime, $queue);
    }
    
    /**
     * Execute job with error handling
     */
    public function execute(): void
    {
        $startTime = microtime(true);
        
        try {
            Logger::info('Job started', [
                'job_id' => $this->jobId,
                'job_class' => $this->getName(),
                'attempts' => $this->attempts
            ]);
            
            $this->handle();
            
            $duration = microtime(true) - $startTime;
            
            Logger::info('Job completed', [
                'job_id' => $this->jobId,
                'job_class' => $this->getName(),
                'duration' => round($duration, 2)
            ]);
            
        } catch (\Throwable $exception) {
            $duration = microtime(true) - $startTime;
            
            Logger::error('Job failed', [
                'job_id' => $this->jobId,
                'job_class' => $this->getName(),
                'error' => $exception->getMessage(),
                'duration' => round($duration, 2)
            ]);
            
            $this->failed($exception);
            throw $exception;
        }
    }
    
    /**
     * Get job status
     */
    public function getStatus(): string
    {
        if ($this->attempts === 0) {
            return 'pending';
        } elseif ($this->attempts < $this->maxTries) {
            return 'processing';
        } else {
            return 'failed';
        }
    }
    
    /**
     * Check if job is completed
     */
    public function isCompleted(): bool
    {
        return $this->getStatus() === 'completed';
    }
    
    /**
     * Check if job is failed
     */
    public function isFailed(): bool
    {
        return $this->getStatus() === 'failed';
    }
    
    /**
     * Check if job is pending
     */
    public function isPending(): bool
    {
        return $this->getStatus() === 'pending';
    }
    
    /**
     * Check if job is processing
     */
    public function isProcessing(): bool
    {
        return $this->getStatus() === 'processing';
    }
    
    /**
     * Get job progress (0-100)
     */
    public function getProgress(): int
    {
        if ($this->isCompleted()) {
            return 100;
        } elseif ($this->isFailed()) {
            return 0;
        } else {
            return min(($this->attempts / $this->maxTries) * 100, 90);
        }
    }
    
    /**
     * Get job description
     */
    public function getDescription(): string
    {
        return "Execute {$this->getDisplayName()}";
    }
    
    /**
     * Get job priority
     */
    public function getPriority(): int
    {
        return 0; // Default priority
    }
    
    /**
     * Check if job should be unique
     */
    public function isUnique(): bool
    {
        return false;
    }
    
    /**
     * Get unique key for job
     */
    public function getUniqueKey(): string
    {
        return $this->getName() . ':' . md5(serialize($this->payload));
    }
    
    /**
     * Get job dependencies
     */
    public function getDependencies(): array
    {
        return [];
    }
    
    /**
     * Check if job can run
     */
    public function canRun(): bool
    {
        return true;
    }
    
    /**
     * Get job requirements
     */
    public function getRequirements(): array
    {
        return [];
    }
    
    /**
     * Validate job payload
     */
    public function validate(): bool
    {
        return true;
    }
    
    /**
     * Get job statistics
     */
    public function getStatistics(): array
    {
        return [
            'job_id' => $this->jobId,
            'job_class' => $this->getName(),
            'attempts' => $this->attempts,
            'max_tries' => $this->maxTries,
            'timeout' => $this->timeout,
            'status' => $this->getStatus(),
            'progress' => $this->getProgress(),
            'is_unique' => $this->isUnique(),
            'priority' => $this->getPriority()
        ];
    }
}
