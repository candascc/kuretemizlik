<?php
/**
 * Analytics Controller
 * Advanced analytics and business intelligence
 */
class AnalyticsController
{
    /**
     * Show analytics dashboard
     */
    public function index()
    {
        Auth::require();
        
        $period = $_GET['period'] ?? 'month'; // day, week, month, year
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-t');
        
        $db = Database::getInstance();
        
        // Revenue trends
        $revenueTrends = $this->getRevenueTrends($dateFrom, $dateTo, $period);
        
        // Job statistics
        $jobStats = $this->getJobStatistics($dateFrom, $dateTo);
        
        // Customer metrics
        $customerMetrics = $this->getCustomerMetrics($dateFrom, $dateTo);
        
        // Performance indicators
        $kpis = $this->getKPIs($dateFrom, $dateTo);
        
        echo View::renderWithLayout('analytics/index', [
            'period' => $period,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'revenue_trends' => $revenueTrends,
            'job_stats' => $jobStats,
            'customer_metrics' => $customerMetrics,
            'kpis' => $kpis,
            'flash' => Utils::getFlash()
        ]);
    }
    
    /**
     * Get revenue trends
     */
    private function getRevenueTrends(string $from, string $to, string $period): array
    {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                DATE(date) as period,
                kind,
                SUM(amount) as total
            FROM money_entries
            WHERE date BETWEEN ? AND ?
            GROUP BY DATE(date), kind
            ORDER BY date
        ";
        
        $data = $db->fetchAll($sql, [$from, $to]);
        
        // Group by period
        $grouped = [];
        foreach ($data as $row) {
            $date = new DateTime($row['period']);
            $key = match($period) {
                'week' => $date->format('Y-W'),
                'month' => $date->format('Y-m'),
                default => $date->format('Y-m-d')
            };
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['period' => $key, 'income' => 0, 'expense' => 0];
            }
            
            if ($row['kind'] === 'INCOME') {
                $grouped[$key]['income'] += $row['total'];
            } else {
                $grouped[$key]['expense'] += $row['total'];
            }
        }
        
        return array_values($grouped);
    }
    
    /**
     * Get job statistics
     */
    private function getJobStatistics(string $from, string $to): array
    {
        $db = Database::getInstance();
        
        return $db->fetchAll("
            SELECT 
                status,
                COUNT(*) as count,
                SUM(total_amount) as revenue,
                AVG(total_amount) as avg_amount
            FROM jobs
            WHERE DATE(start_at) BETWEEN ? AND ?
            GROUP BY status
        ", [$from, $to]);
    }
    
    /**
     * Get customer metrics
     */
    private function getCustomerMetrics(string $from, string $to): array
    {
        $db = Database::getInstance();
        
        $new = $db->fetch("
            SELECT COUNT(*) as count 
            FROM customers 
            WHERE DATE(created_at) BETWEEN ? AND ?
        ", [$from, $to])['count'] ?? 0;
        
        $active = $db->fetch("
            SELECT COUNT(DISTINCT customer_id) as count 
            FROM jobs 
            WHERE DATE(start_at) BETWEEN ? AND ?
        ", [$from, $to])['count'] ?? 0;
        
        return [
            'new_customers' => $new,
            'active_customers' => $active
        ];
    }
    
    /**
     * Get Key Performance Indicators
     */
    private function getKPIs(string $from, string $to): array
    {
        $db = Database::getInstance();
        
        // Total revenue
        $revenue = $db->fetch("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM money_entries 
            WHERE kind = 'INCOME' AND date BETWEEN ? AND ?
        ", [$from, $to])['total'] ?? 0;
        
        // Total jobs
        $jobs = $db->fetch("
            SELECT COUNT(*) as count 
            FROM jobs 
            WHERE DATE(start_at) BETWEEN ? AND ?
        ", [$from, $to])['count'] ?? 0;
        
        // Average job value
        $avgJob = $db->fetch("
            SELECT COALESCE(AVG(total_amount), 0) as avg 
            FROM jobs 
            WHERE DATE(start_at) BETWEEN ? AND ?
        ", [$from, $to])['avg'] ?? 0;
        
        // Completion rate
        $completed = $db->fetch("
            SELECT COUNT(*) as count 
            FROM jobs 
            WHERE status = 'completed' AND DATE(start_at) BETWEEN ? AND ?
        ", [$from, $to])['count'] ?? 0;
        
        return [
            'total_revenue' => $revenue,
            'total_jobs' => $jobs,
            'average_job_value' => $avgJob,
            'completion_rate' => $jobs > 0 ? ($completed / $jobs) * 100 : 0
        ];
    }
}

