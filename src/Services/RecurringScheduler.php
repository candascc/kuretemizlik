<?php
/**
 * Recurring Scheduler Service
 * Automated job generation for recurring jobs
 */

class RecurringScheduler
{
    /**
     * Process all active recurring jobs and generate missing occurrences/jobs
     * Should be called daily via cron or scheduled task
     * 
     * @return array Summary of operations
     */
    public static function processAll(): array
    {
        $db = Database::getInstance();
        $recurringModel = new RecurringJob();
        
        $summary = [
            'recurring_jobs_processed' => 0,
            'occurrences_generated' => 0,
            'jobs_created' => 0,
            'errors' => []
        ];
        
        try {
            // Get all active recurring jobs
            $activeRecurringJobs = $db->fetchAll("
                SELECT id 
                FROM recurring_jobs 
                WHERE status = 'ACTIVE'
            ");
            
            foreach ($activeRecurringJobs as $rj) {
                $recurringJobId = (int)$rj['id'];
                
                try {
                    // Check if we need to generate more occurrences (look ahead 30 days)
                    $result = self::processRecurringJob($recurringJobId, 30);
                    
                    $summary['recurring_jobs_processed']++;
                    $summary['occurrences_generated'] += $result['occurrences'];
                    $summary['jobs_created'] += $result['jobs'];
                    
                } catch (Exception $e) {
                    $summary['errors'][] = [
                        'recurring_job_id' => $recurringJobId,
                        'error' => $e->getMessage()
                    ];
                    error_log("RecurringScheduler error for RJ #{$recurringJobId}: " . $e->getMessage());
                }
            }
            
        } catch (Exception $e) {
            error_log("RecurringScheduler processAll error: " . $e->getMessage());
            $summary['errors'][] = ['general' => $e->getMessage()];
        }
        
        return $summary;
    }
    
    /**
     * Process a single recurring job
     * Generates occurrences and materializes to jobs if needed
     * 
     * @param int $recurringJobId
     * @param int $daysAhead How many days ahead to generate
     * @return array ['occurrences' => int, 'jobs' => int, 'errors' => array]
     */
    public static function processRecurringJob(int $recurringJobId, int $daysAhead = 30): array
    {
        $errors = [];
        
        try {
            // Step 1: Generate occurrences
            $occurrencesGenerated = RecurringGenerator::generateForJob($recurringJobId, $daysAhead);
        } catch (Exception $e) {
            error_log("RecurringScheduler: Failed to generate occurrences for RJ #{$recurringJobId}: " . $e->getMessage());
            $occurrencesGenerated = 0;
            $errors[] = "Oluşum oluşturma hatası: " . $e->getMessage();
        }
        
        try {
            // Step 2: Materialize occurrences to jobs (only for today and future)
            $jobsCreated = RecurringGenerator::materializeToJobs($recurringJobId);
        } catch (Exception $e) {
            error_log("RecurringScheduler: Failed to materialize jobs for RJ #{$recurringJobId}: " . $e->getMessage());
            $jobsCreated = 0;
            $errors[] = "İş oluşturma hatası: " . $e->getMessage();
        }
        
        return [
            'occurrences' => $occurrencesGenerated,
            'jobs' => $jobsCreated,
            'errors' => $errors
        ];
    }
    
    /**
     * Check and generate missing jobs for today and tomorrow
     * Quick check for immediate needs
     * 
     * @return array Summary
     */
    public static function checkAndGenerateNow(): array
    {
        $db = Database::getInstance();
        
        $summary = [
            'checked' => 0,
            'generated' => 0,
            'errors' => []
        ];
        
        try {
            // Get occurrences that should have jobs but don't
            // For today and next 2 days
            $today = date('Y-m-d');
            $twoDaysLater = date('Y-m-d', strtotime('+2 days'));
            
            $missingOccurrences = $db->fetchAll("
                SELECT 
                    ro.*,
                    rj.status as recurring_status
                FROM recurring_job_occurrences ro
                INNER JOIN recurring_jobs rj ON ro.recurring_job_id = rj.id
                WHERE ro.status = 'PLANNED'
                AND ro.date >= ?
                AND ro.date <= ?
                AND rj.status = 'ACTIVE'
                AND ro.job_id IS NULL
                ORDER BY ro.date, ro.start_at
            ", [$today, $twoDaysLater]);
            
            if (!empty($missingOccurrences)) {
                // Group by recurring_job_id for batch processing
                $grouped = [];
                foreach ($missingOccurrences as $occ) {
                    $rjId = $occ['recurring_job_id'];
                    if (!isset($grouped[$rjId])) {
                        $grouped[$rjId] = [];
                    }
                    $grouped[$rjId][] = $occ;
                }
                
                // Process each recurring job
                foreach ($grouped as $recurringJobId => $occurrences) {
                    try {
                        $result = RecurringGenerator::materializeToJobs($recurringJobId);
                        $summary['checked']++;
                        $summary['generated'] += $result;
                    } catch (Exception $e) {
                        error_log("RecurringScheduler: Failed to process RJ #{$recurringJobId}: " . $e->getMessage());
                        $summary['errors'][] = [
                            'recurring_job_id' => $recurringJobId,
                            'error' => $e->getMessage()
                        ];
                        // Continue with other recurring jobs
                    }
                }
            }
            
        } catch (Exception $e) {
            error_log("RecurringScheduler checkAndGenerateNow error: " . $e->getMessage());
            $summary['errors'][] = ['general' => $e->getMessage()];
        }
        
        return $summary;
    }
}

