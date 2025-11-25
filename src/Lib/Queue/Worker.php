<?php
/**
 * Queue Worker
 * Processes background jobs from the queue
 */

class Worker
{
    private $queue;
    private $isRunning = false;
    private $shouldStop = false;
    private $config;
    private $memoryLimit;
    private $startTime;
    private $processedJobs = 0;
    private $failedJobs = 0;
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'queue' => 'default',
            'timeout' => 60,
            'memory_limit' => 128,
            'sleep' => 3,
            'max_tries' => 3,
            'retry_after' => 90,
            'max_jobs' => 0, // 0 = unlimited
            'max_time' => 0, // 0 = unlimited
            'stop_on_empty' => false
        ], $config);
        
        $this->queue = new Queue();
        $this->memoryLimit = $this->config['memory_limit'] * 1024 * 1024; // Convert to bytes
        $this->startTime = time();
    }
    
    /**
     * Start the worker
     */
    public function start(): void
    {
        $this->isRunning = true;
        $this->shouldStop = false;
        
        Logger::info('Queue worker started', [
            'queue' => $this->config['queue'],
            'timeout' => $this->config['timeout'],
            'memory_limit' => $this->config['memory_limit']
        ]);
        
        // Register signal handlers
        $this->registerSignalHandlers();
        
        while ($this->isRunning && !$this->shouldStop) {
            try {
                $this->processNextJob();
                
                // Check limits
                if ($this->shouldStop()) {
                    break;
                }
                
                // Sleep between jobs
                if ($this->config['sleep'] > 0) {
                    sleep($this->config['sleep']);
                }
                
            } catch (Exception $e) {
                Logger::error('Worker error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Sleep on error to prevent tight loop
                sleep(5);
            }
        }
        
        $this->isRunning = false;
        
        Logger::info('Queue worker stopped', [
            'processed_jobs' => $this->processedJobs,
            'failed_jobs' => $this->failedJobs,
            'uptime' => time() - $this->startTime
        ]);
    }
    
    /**
     * Stop the worker
     */
    public function stop(): void
    {
        $this->shouldStop = true;
        Logger::info('Queue worker stop requested');
    }
    
    /**
     * Process next job from queue
     */
    private function processNextJob(): void
    {
        $job = $this->queue->pop($this->config['queue']);
        
        if (!$job) {
            if ($this->config['stop_on_empty']) {
                $this->shouldStop = true;
            }
            return;
        }
        
        $this->processJob($job);
    }
    
    /**
     * Process a single job
     */
    private function processJob(array $job): void
    {
        $jobId = $job['id'];
        $jobClass = $job['job'];
        $payload = $job['payload'];
        $attempts = $job['attempts'];
        
        try {
            Logger::info('Processing job', [
                'job_id' => $jobId,
                'job_class' => $jobClass,
                'attempts' => $attempts
            ]);
            
            // Create job instance
            if (!class_exists($jobClass)) {
                throw new Exception("Job class {$jobClass} not found");
            }
            
            $jobInstance = new $jobClass($payload);
            $jobInstance->setJobId($jobId);
            $jobInstance->setAttempts($attempts);
            
            // Execute job
            $jobInstance->execute();
            
            // Mark as completed
            $this->queue->markCompleted($jobId);
            $this->processedJobs++;
            
            Logger::info('Job completed successfully', [
                'job_id' => $jobId,
                'job_class' => $jobClass
            ]);
            
        } catch (Exception $e) {
            $this->handleJobFailure($job, $e);
        }
    }
    
    /**
     * Handle job failure
     */
    private function handleJobFailure(array $job, Exception $exception): void
    {
        $jobId = $job['id'];
        $jobClass = $job['job'];
        $attempts = $job['attempts'];
        $maxTries = $job['max_tries'];
        
        Logger::error('Job failed', [
            'job_id' => $jobId,
            'job_class' => $jobClass,
            'attempts' => $attempts,
            'max_tries' => $maxTries,
            'error' => $exception->getMessage()
        ]);
        
        if ($attempts < $maxTries) {
            // Retry job
            $this->retryJob($job, $exception);
        } else {
            // Mark as permanently failed
            $this->queue->markFailed($jobId, $exception->getMessage());
            $this->failedJobs++;
            
            Logger::error('Job permanently failed', [
                'job_id' => $jobId,
                'job_class' => $jobClass,
                'attempts' => $attempts
            ]);
        }
    }
    
    /**
     * Retry failed job
     */
    private function retryJob(array $job, Exception $exception): void
    {
        $jobId = $job['id'];
        $retryAfter = $job['retry_after'] ?? $this->config['retry_after'];
        
        // Update job with retry information
        $job['available_at'] = time() + $retryAfter;
        $job['attempts'] = $job['attempts'] + 1;
        $job['reserved_at'] = null;
        
        // Push back to queue
        if ($this->queue->isConnected()) {
            $this->queue->push($job['job'], $job['payload'], $job['queue']);
        }
        
        Logger::info('Job scheduled for retry', [
            'job_id' => $jobId,
            'retry_after' => $retryAfter,
            'attempts' => $job['attempts']
        ]);
    }
    
    /**
     * Check if worker should stop
     */
    private function shouldStop(): bool
    {
        // Check memory limit
        if (memory_get_usage() > $this->memoryLimit) {
            Logger::warning('Memory limit reached, stopping worker');
            return true;
        }
        
        // Check max jobs
        if ($this->config['max_jobs'] > 0 && $this->processedJobs >= $this->config['max_jobs']) {
            Logger::info('Max jobs limit reached, stopping worker');
            return true;
        }
        
        // Check max time
        if ($this->config['max_time'] > 0 && (time() - $this->startTime) >= $this->config['max_time']) {
            Logger::info('Max time limit reached, stopping worker');
            return true;
        }
        
        return false;
    }
    
    /**
     * Register signal handlers
     */
    private function registerSignalHandlers(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
        }
    }
    
    /**
     * Handle system signals
     */
    public function handleSignal(int $signal): void
    {
        switch ($signal) {
            case SIGTERM:
            case SIGINT:
                Logger::info('Received signal, stopping worker', ['signal' => $signal]);
                $this->stop();
                break;
        }
    }
    
    /**
     * Get worker status
     */
    public function getStatus(): array
    {
        return [
            'is_running' => $this->isRunning,
            'should_stop' => $this->shouldStop,
            'processed_jobs' => $this->processedJobs,
            'failed_jobs' => $this->failedJobs,
            'uptime' => time() - $this->startTime,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'queue' => $this->config['queue']
        ];
    }
    
    /**
     * Get worker statistics
     */
    public function getStatistics(): array
    {
        $uptime = time() - $this->startTime;
        
        return [
            'uptime' => $uptime,
            'processed_jobs' => $this->processedJobs,
            'failed_jobs' => $this->failedJobs,
            'jobs_per_minute' => $uptime > 0 ? round(($this->processedJobs / $uptime) * 60, 2) : 0,
            'success_rate' => $this->processedJobs > 0 ? round((($this->processedJobs - $this->failedJobs) / $this->processedJobs) * 100, 2) : 0,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'queue_size' => $this->queue->size($this->config['queue'])
        ];
    }
    
    /**
     * Check if worker is running
     */
    public function isRunning(): bool
    {
        return $this->isRunning;
    }
    
    /**
     * Get processed jobs count
     */
    public function getProcessedJobs(): int
    {
        return $this->processedJobs;
    }
    
    /**
     * Get failed jobs count
     */
    public function getFailedJobs(): int
    {
        return $this->failedJobs;
    }
    
    /**
     * Get worker uptime
     */
    public function getUptime(): int
    {
        return time() - $this->startTime;
    }
    
    /**
     * Restart worker
     */
    public function restart(): void
    {
        $this->stop();
        sleep(1);
        $this->start();
    }
    
    /**
     * Pause worker
     */
    public function pause(): void
    {
        $this->shouldStop = true;
        Logger::info('Queue worker paused');
    }
    
    /**
     * Resume worker
     */
    public function resume(): void
    {
        $this->shouldStop = false;
        Logger::info('Queue worker resumed');
    }
}
