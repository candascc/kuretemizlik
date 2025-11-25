<?php
/**
 * Queue Management Controller
 * Handles queue operations, monitoring, and job management
 */

class QueueController
{
    private $queueManager;
    
    public function __construct()
    {
        $this->queueManager = QueueManager::getInstance();
    }
    
    /**
     * Show queue dashboard
     */
    public function index()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $stats = $this->queueManager->getStats();
        $failedJobs = $this->queueManager->getFailedJobs(AppConstants::QUEUE_BATCH_SIZE);
        $healthStatus = $this->queueManager->getHealthStatus();
        $performanceMetrics = $this->queueManager->getPerformanceMetrics();
        $alerts = $this->queueManager->getAlerts();
        $recommendations = $this->queueManager->getRecommendations();
        
        $data = [
            'title' => 'Kuyruk Yönetimi',
            'stats' => $stats,
            'failedJobs' => $failedJobs,
            'healthStatus' => $healthStatus,
            'performanceMetrics' => $performanceMetrics,
            'alerts' => $alerts,
            'recommendations' => $recommendations
        ];
        
        echo View::renderWithLayout('admin/queue/index', $data);
    }
    
    /**
     * Show queue statistics
     */
    public function stats()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $stats = $this->queueManager->getStats();
        
        header('Content-Type: application/json');
        echo json_encode($stats);
    }
    
    /**
     * Show failed jobs
     */
    public function failed()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $limit = (int)($_GET['limit'] ?? 50);
        $failedJobs = $this->queueManager->getFailedJobs($limit);
        
        $data = [
            'title' => 'Başarısız İşler',
            'failedJobs' => $failedJobs,
            'limit' => $limit
        ];
        
        echo View::renderWithLayout('admin/queue/failed', $data);
    }
    
    /**
     * Retry failed job
     */
    public function retry()
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/queue');
        }
        
        $jobId = $_POST['job_id'] ?? '';
        
        if (empty($jobId)) {
            set_flash('error', 'Job ID is required.');
            redirect('/admin/queue/failed');
        }
        
        $result = $this->queueManager->retry($jobId);
        
        if ($result) {
            set_flash('success', 'Job queued for retry.');
        } else {
            set_flash('error', 'Failed to retry job.');
        }
        
        redirect('/admin/queue/failed');
    }
    
    /**
     * Delete failed job
     */
    public function delete()
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/queue');
        }
        
        // ===== ERR-010 FIX: Add try-catch for error handling =====
        try {
            $jobId = $_POST['job_id'] ?? '';
            
            if (empty($jobId)) {
                set_flash('error', 'Job ID is required.');
                redirect('/admin/queue/failed');
            }
            
            // Delete from database
            $db = Database::getInstance();
            $result = $db->query("DELETE FROM queue_jobs WHERE id = ?", [$jobId]);
            
            if ($result) {
                set_flash('success', 'Job deleted successfully.');
            } else {
                set_flash('error', 'Failed to delete job.');
            }
        } catch (Exception $e) {
            error_log("QueueController::delete() error: " . $e->getMessage());
            set_flash('error', 'Job silinirken bir hata oluştu: ' . (defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Lütfen tekrar deneyin.'));
        }
        // ===== ERR-010 FIX: End =====
        
        redirect('/admin/queue/failed');
    }
    
    /**
     * Clear all failed jobs
     */
    public function clearFailed()
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/queue');
        }
        
        $result = $this->queueManager->clearFailedJobs();
        
        if ($result) {
            set_flash('success', 'All failed jobs cleared.');
        } else {
            set_flash('error', 'Failed to clear failed jobs.');
        }
        
        redirect('/admin/queue/failed');
    }
    
    /**
     * Push test job
     */
    public function pushTest()
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/queue');
        }
        
        $jobType = $_POST['job_type'] ?? 'SendEmailJob';
        $queue = $_POST['queue'] ?? 'default';
        
        try {
            switch ($jobType) {
                case 'SendEmailJob':
                    $job = new SendEmailJob([
                        'to' => 'test@example.com',
                        'subject' => 'Test Email',
                        'body' => 'This is a test email from the queue system.',
                        'from' => $_ENV['MAIL_FROM'] ?? 'noreply@example.com'
                    ]);
                    break;
                    
                case 'GenerateReportJob':
                    $job = new GenerateReportJob([
                        'report_type' => 'financial',
                        'format' => 'pdf',
                        'user_id' => Auth::id(),
                        'parameters' => [
                            'start_date' => date('Y-m-01'),
                            'end_date' => date('Y-m-t')
                        ]
                    ]);
                    break;
                    
                default:
                    throw new Exception('Unknown job type');
            }
            
            $jobId = $job->dispatch($queue);
            
            set_flash('success', "Test job pushed successfully. Job ID: {$jobId}");
            
        } catch (Exception $e) {
            set_flash('error', 'Failed to push test job: ' . Utils::safeExceptionMessage($e));
        }
        
        redirect('/admin/queue');
    }
    
    /**
     * Get queue health status
     */
    public function health()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $healthStatus = $this->queueManager->getHealthStatus();
        
        header('Content-Type: application/json');
        echo json_encode($healthStatus);
    }
    
    /**
     * Get queue performance metrics
     */
    public function metrics()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $metrics = $this->queueManager->getPerformanceMetrics();
        
        header('Content-Type: application/json');
        echo json_encode($metrics);
    }
    
    /**
     * Get queue alerts
     */
    public function alerts()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $alerts = $this->queueManager->getAlerts();
        
        header('Content-Type: application/json');
        echo json_encode($alerts);
    }
    
    /**
     * Get queue recommendations
     */
    public function recommendations()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $recommendations = $this->queueManager->getRecommendations();
        
        header('Content-Type: application/json');
        echo json_encode($recommendations);
    }
    
    /**
     * Flush queue
     */
    public function flush()
    {
        Auth::require();
        Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/queue');
        }
        
        $queue = $_POST['queue'] ?? null;
        
        if ($this->queueManager->flush($queue)) {
            set_flash('success', 'Queue flushed successfully.');
        } else {
            set_flash('error', 'Failed to flush queue.');
        }
        
        redirect('/admin/queue');
    }
    
    /**
     * Export queue data
     */
    public function export()
    {
        Auth::require();
        Auth::requireAdmin();
        
        $format = $_GET['format'] ?? 'json';
        $stats = $this->queueManager->getStats();
        $failedJobs = $this->queueManager->getFailedJobs(AppConstants::EXPORT_BATCH_SIZE);
        
        $data = [
            'stats' => $stats,
            'failed_jobs' => $failedJobs,
            'exported_at' => date('Y-m-d H:i:s'),
            'exported_by' => Auth::user()['username']
        ];
        
        if ($format === 'json') {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="queue_export_' . date('Y-m-d') . '.json"');
            echo json_encode($data, JSON_PRETTY_PRINT);
        } else {
            // CSV export
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="queue_export_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Queue', 'Pending', 'Processing', 'Failed', 'Total']);
            
            foreach ($stats as $queue => $stat) {
                fputcsv($output, [
                    $queue,
                    $stat['pending'] ?? 0,
                    $stat['processing'] ?? 0,
                    $stat['failed'] ?? 0,
                    $stat['total'] ?? 0
                ]);
            }
            
            fclose($output);
        }
        
        exit;
    }
}
