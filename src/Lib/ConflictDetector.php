<?php
/**
 * Scheduling Conflict Detector
 * Detects and resolves scheduling conflicts for jobs and staff
 */

class ConflictDetector
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Check for job time conflicts
     * SECURITY: Added company_id parameter for multi-tenant isolation
     */
    public function hasJobConflict(int $staffId, string $startTime, string $endTime, ?int $excludeJobId = null, ?int $companyId = null): bool
    {
        // SECURITY: Use SQLite datetime syntax instead of MySQL DATE_ADD
        $query = "SELECT COUNT(*) as count FROM jobs 
                  WHERE staff_id = ? 
                  AND status != 'cancelled'
                  AND ((datetime(job_date, '+2 hours') > ? AND job_date <= ?)
                       OR (job_date >= ? AND job_date < ?))";
        
        $params = [$staffId, $startTime, $endTime, $startTime, $endTime];
        
        // SECURITY: Add company_id filter for multi-tenant isolation
        if ($companyId !== null) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        if ($excludeJobId) {
            $query .= " AND id != ?";
            $params[] = $excludeJobId;
        }
        
        $result = $this->db->fetch($query, $params);
        return ($result['count'] ?? 0) > 0;
    }
    
    /**
     * Get conflicting jobs
     * SECURITY: Added company_id parameter for multi-tenant isolation
     */
    public function getConflictingJobs(int $staffId, string $startTime, string $endTime, ?int $excludeJobId = null, ?int $companyId = null): array
    {
        // SECURITY: Use SQLite datetime syntax instead of MySQL DATE_ADD
        $query = "SELECT * FROM jobs 
                  WHERE staff_id = ? 
                  AND status != 'cancelled'
                  AND ((datetime(job_date, '+2 hours') > ? AND job_date <= ?)
                       OR (job_date >= ? AND job_date < ?))";
        
        $params = [$staffId, $startTime, $endTime, $startTime, $endTime];
        
        // SECURITY: Add company_id filter for multi-tenant isolation
        if ($companyId !== null) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        if ($excludeJobId) {
            $query .= " AND id != ?";
            $params[] = $excludeJobId;
        }
        
        return $this->db->fetchAll($query, $params);
    }
    
    /**
     * Check staff availability
     * SECURITY: Added company_id parameter for multi-tenant isolation
     */
    public function isStaffAvailable(int $staffId, string $date, string $time, ?int $companyId = null): bool
    {
        // SECURITY: Check if staff exists, is active, and belongs to company
        $query = "SELECT * FROM staff WHERE id = ? AND status = 'active'";
        $params = [$staffId];
        
        if ($companyId !== null) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $staff = $this->db->fetch($query, $params);
        if (!$staff) {
            return false;
        }
        
        // Use staff's company_id if not provided
        $effectiveCompanyId = $companyId ?? ($staff['company_id'] ?? null);
        
        // Check for conflicts
        $startTime = $date . ' ' . $time;
        $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' +2 hours'));
        
        return !$this->hasJobConflict($staffId, $startTime, $endTime, null, $effectiveCompanyId);
    }
    
    /**
     * Find available staff for time slot
     * SECURITY: Added company_id parameter for multi-tenant isolation
     */
    public function findAvailableStaff(string $date, string $time, int $durationHours = 2, ?int $companyId = null): array
    {
        // SECURITY: Filter staff by company_id
        $query = "SELECT * FROM staff WHERE status = 'active'";
        $params = [];
        
        if ($companyId !== null) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $allStaff = $this->db->fetchAll($query, $params);
        $available = [];
        
        $startTime = $date . ' ' . $time;
        $endTime = date('Y-m-d H:i:s', strtotime($startTime . " +{$durationHours} hours"));
        
        foreach ($allStaff as $staff) {
            $staffCompanyId = $companyId ?? ($staff['company_id'] ?? null);
            if (!$this->hasJobConflict($staff['id'], $startTime, $endTime, null, $staffCompanyId)) {
                $available[] = $staff;
            }
        }
        
        return $available;
    }
    
    /**
     * Get staff workload for date range
     * SECURITY: Added company_id parameter for multi-tenant isolation
     */
    public function getStaffWorkload(int $staffId, string $startDate, string $endDate, ?int $companyId = null): array
    {
        $query = "SELECT DATE(job_date) as date, COUNT(*) as job_count, SUM(COALESCE(duration, 2)) as total_hours
             FROM jobs 
             WHERE staff_id = ? 
             AND DATE(job_date) BETWEEN ? AND ?
             AND status != 'cancelled'";
        
        $params = [$staffId, $startDate, $endDate];
        
        // SECURITY: Add company_id filter
        if ($companyId !== null) {
            $query .= " AND company_id = ?";
            $params[] = $companyId;
        }
        
        $query .= " GROUP BY DATE(job_date) ORDER BY date";
        
        $jobs = $this->db->fetchAll($query, $params);
        
        return $jobs;
    }
    
    /**
     * Suggest alternative time slots
     * SECURITY: Added company_id parameter for multi-tenant isolation
     */
    public function suggestAlternatives(int $staffId, string $preferredDate, int $maxSuggestions = 5, ?int $companyId = null): array
    {
        $suggestions = [];
        $date = new DateTime($preferredDate);
        $timeSlots = ['09:00:00', '11:00:00', '13:00:00', '15:00:00', '17:00:00'];
        
        // Try same day first
        foreach ($timeSlots as $time) {
            $startTime = $date->format('Y-m-d') . ' ' . $time;
            $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' +2 hours'));
            
            if (!$this->hasJobConflict($staffId, $startTime, $endTime, null, $companyId)) {
                $suggestions[] = [
                    'date' => $date->format('Y-m-d'),
                    'time' => $time,
                    'staff_id' => $staffId
                ];
                
                if (count($suggestions) >= $maxSuggestions) {
                    return $suggestions;
                }
            }
        }
        
        // Try next 7 days
        for ($i = 1; $i <= 7 && count($suggestions) < $maxSuggestions; $i++) {
            $date->modify('+1 day');
            
            foreach ($timeSlots as $time) {
                $startTime = $date->format('Y-m-d') . ' ' . $time;
                $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' +2 hours'));
                
                if (!$this->hasJobConflict($staffId, $startTime, $endTime, null, $companyId)) {
                    $suggestions[] = [
                        'date' => $date->format('Y-m-d'),
                        'time' => $time,
                        'staff_id' => $staffId
                    ];
                    
                    if (count($suggestions) >= $maxSuggestions) {
                        return $suggestions;
                    }
                }
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Detect overlapping jobs
     * SECURITY: Added company_id parameter for multi-tenant isolation
     */
    public function detectOverlaps(string $startDate, string $endDate, ?int $companyId = null): array
    {
        // SECURITY: Use SQLite datetime syntax instead of MySQL DATE_ADD
        $query = "SELECT j1.id as job1_id, j2.id as job2_id, 
                         j1.staff_id, j1.job_date as job1_date, j2.job_date as job2_date
                  FROM jobs j1
                  JOIN jobs j2 ON j1.staff_id = j2.staff_id AND j1.id < j2.id
                  WHERE j1.status != 'cancelled' AND j2.status != 'cancelled'
                  AND DATE(j1.job_date) BETWEEN ? AND ?
                  AND ((j1.job_date <= j2.job_date AND datetime(j1.job_date, '+2 hours') > j2.job_date)
                       OR (j2.job_date <= j1.job_date AND datetime(j2.job_date, '+2 hours') > j1.job_date))";
        
        $params = [$startDate, $endDate];
        
        // SECURITY: Add company_id filter for multi-tenant isolation
        if ($companyId !== null) {
            $query .= " AND j1.company_id = ? AND j2.company_id = ?";
            $params[] = $companyId;
            $params[] = $companyId;
        }
        
        return $this->db->fetchAll($query, $params);
    }
}

