<?php
/**
 * Auto Scheduler
 * Automatically assigns jobs to staff based on availability and workload
 */

class AutoScheduler
{
    private $db;
    private $conflictDetector;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->conflictDetector = new ConflictDetector();
    }
    
    /**
     * Auto-assign job to best available staff
     */
    public function assignJob(int $jobId, ?string $preferredDate = null, ?string $preferredTime = null): ?int
    {
        $job = $this->db->fetch("SELECT * FROM jobs WHERE id = ?", [$jobId]);
        if (!$job) {
            return null;
        }
        
        $date = $preferredDate ?? date('Y-m-d', strtotime($job['job_date']));
        $time = $preferredTime ?? date('H:i:s', strtotime($job['job_date']));
        
        // Find available staff
        $availableStaff = $this->conflictDetector->findAvailableStaff($date, $time);
        
        if (empty($availableStaff)) {
            return null;
        }
        
        // Select staff with lowest workload
        $bestStaff = $this->selectBestStaff($availableStaff, $date);
        
        if ($bestStaff) {
            // Update job
            $this->db->query(
                "UPDATE jobs SET staff_id = ?, updated_at = ? WHERE id = ?",
                [$bestStaff['id'], date('Y-m-d H:i:s'), $jobId]
            );
            
            Logger::info("Job auto-assigned", [
                'job_id' => $jobId,
                'staff_id' => $bestStaff['id'],
                'date' => $date,
                'time' => $time
            ]);
            
            return $bestStaff['id'];
        }
        
        return null;
    }
    
    /**
     * Select best staff based on workload
     */
    private function selectBestStaff(array $staffList, string $date): ?array
    {
        if (empty($staffList)) {
            return null;
        }
        
        $startDate = date('Y-m-d', strtotime($date . ' -7 days'));
        $endDate = date('Y-m-d', strtotime($date . ' +7 days'));
        
        $bestStaff = null;
        $lowestWorkload = PHP_INT_MAX;
        
        foreach ($staffList as $staff) {
            $workload = $this->conflictDetector->getStaffWorkload($staff['id'], $startDate, $endDate);
            $totalHours = array_sum(array_column($workload, 'total_hours'));
            
            if ($totalHours < $lowestWorkload) {
                $lowestWorkload = $totalHours;
                $bestStaff = $staff;
            }
        }
        
        return $bestStaff;
    }
    
    /**
     * Distribute jobs evenly across staff
     */
    public function distributeJobs(array $jobIds, string $startDate, string $endDate): array
    {
        $results = [];
        
        foreach ($jobIds as $jobId) {
            $assignedStaffId = $this->assignJob($jobId);
            $results[$jobId] = [
                'success' => $assignedStaffId !== null,
                'staff_id' => $assignedStaffId
            ];
        }
        
        return $results;
    }
    
    /**
     * Optimize schedule for date range
     */
    public function optimizeSchedule(string $startDate, string $endDate): array
    {
        // Get all unassigned jobs
        $unassignedJobs = $this->db->fetchAll(
            "SELECT * FROM jobs 
             WHERE staff_id IS NULL 
             AND DATE(job_date) BETWEEN ? AND ?
             AND status != 'cancelled'
             ORDER BY job_date",
            [$startDate, $endDate]
        );
        
        $assigned = 0;
        $failed = 0;
        
        foreach ($unassignedJobs as $job) {
            $staffId = $this->assignJob($job['id']);
            if ($staffId) {
                $assigned++;
            } else {
                $failed++;
            }
        }
        
        return [
            'total' => count($unassignedJobs),
            'assigned' => $assigned,
            'failed' => $failed
        ];
    }
    
    /**
     * Rebalance workload
     */
    public function rebalanceWorkload(string $date): array
    {
        // Get all active staff
        $allStaff = $this->db->fetchAll("SELECT * FROM staff WHERE status = 'active'");
        
        // Calculate current workload
        $workloads = [];
        foreach ($allStaff as $staff) {
            $workload = $this->conflictDetector->getStaffWorkload($staff['id'], $date, $date);
            $workloads[$staff['id']] = [
                'staff' => $staff,
                'jobs' => $workload[0]['job_count'] ?? 0,
                'hours' => $workload[0]['total_hours'] ?? 0
            ];
        }
        
        // Sort by workload
        uasort($workloads, function($a, $b) {
            return $b['hours'] - $a['hours'];
        });
        
        $rebalanced = 0;
        
        // Try to move jobs from overloaded to underloaded staff
        $staffIds = array_keys($workloads);
        $overloaded = array_slice($staffIds, 0, ceil(count($staffIds) / 2));
        $underloaded = array_slice($staffIds, ceil(count($staffIds) / 2));
        
        foreach ($overloaded as $overloadedId) {
            $jobs = $this->db->fetchAll(
                "SELECT * FROM jobs WHERE staff_id = ? AND DATE(job_date) = ? AND status != 'cancelled'",
                [$overloadedId, $date]
            );
            
            foreach ($jobs as $job) {
                foreach ($underloaded as $underloadedId) {
                    $startTime = $job['job_date'];
                    $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' +2 hours'));
                    
                    if (!$this->conflictDetector->hasJobConflict($underloadedId, $startTime, $endTime)) {
                        // Move job
                        $this->db->query(
                            "UPDATE jobs SET staff_id = ?, updated_at = ? WHERE id = ?",
                            [$underloadedId, date('Y-m-d H:i:s'), $job['id']]
                        );
                        $rebalanced++;
                        break;
                    }
                }
            }
        }
        
        return [
            'rebalanced' => $rebalanced,
            'workloads' => $workloads
        ];
    }
}

