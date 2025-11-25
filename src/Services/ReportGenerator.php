<?php
/**
 * Report Generator
 * Advanced reporting system for business analytics
 */
class ReportGenerator
{
    /**
     * Generate financial report
     */
    public static function financialReport(array $params = []): array
    {
        $db = Database::getInstance();
        
        $dateFrom = $params['date_from'] ?? date('Y-m-01');
        $dateTo = $params['date_to'] ?? date('Y-m-t');
        $groupBy = $params['group_by'] ?? 'day'; // day, week, month
        
        $sql = "
            SELECT 
                DATE(date) as period,
                kind,
                SUM(amount) as total
            FROM money_entries
            WHERE date BETWEEN ? AND ?
            GROUP BY DATE(date), kind
            ORDER BY DATE(date)
        ";
        
        $data = $db->fetchAll($sql, [$dateFrom, $dateTo]);
        
        // Group by period
        $grouped = self::groupByPeriod($data, $groupBy);
        
        return [
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ],
            'summary' => self::calculateSummary($data),
            'data' => $grouped,
            'trends' => self::calculateTrends($grouped)
        ];
    }
    
    /**
     * Generate job performance report
     */
    public static function jobPerformanceReport(array $params = []): array
    {
        $db = Database::getInstance();
        
        $dateFrom = $params['date_from'] ?? date('Y-m-01');
        $dateTo = $params['date_to'] ?? date('Y-m-t');
        
        $sql = "
            SELECT 
                j.status,
                COUNT(*) as count,
                SUM(j.total_amount) as total_revenue,
                AVG(j.total_amount) as avg_revenue,
                SUM(j.amount_paid) as total_paid
            FROM jobs j
            WHERE DATE(j.start_at) BETWEEN ? AND ?
            GROUP BY j.status
        ";
        
        $stats = $db->fetchAll($sql, [$dateFrom, $dateTo]);
        
        // Top customers
        $topCustomers = $db->fetchAll("
            SELECT 
                c.name,
                c.phone,
                COUNT(j.id) as job_count,
                SUM(j.total_amount) as total_revenue
            FROM jobs j
            LEFT JOIN customers c ON j.customer_id = c.id
            WHERE DATE(j.start_at) BETWEEN ? AND ?
            GROUP BY j.customer_id
            ORDER BY total_revenue DESC
            LIMIT 10
        ", [$dateFrom, $dateTo]);
        
        // Top services
        $topServices = $db->fetchAll("
            SELECT 
                s.name,
                COUNT(j.id) as job_count,
                SUM(j.total_amount) as total_revenue
            FROM jobs j
            LEFT JOIN services s ON j.service_id = s.id
            WHERE DATE(j.start_at) BETWEEN ? AND ? AND s.id IS NOT NULL
            GROUP BY j.service_id
            ORDER BY total_revenue DESC
            LIMIT 10
        ", [$dateFrom, $dateTo]);
        
        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'status_breakdown' => $stats,
            'top_customers' => $topCustomers,
            'top_services' => $topServices,
            'efficiency' => self::calculateEfficiency($stats)
        ];
    }
    
    /**
     * Generate customer analysis report
     */
    public static function customerAnalysisReport(): array
    {
        $db = Database::getInstance();
        
        // Customer lifetime value
        $lifetimeValue = $db->fetchAll("
            SELECT 
                c.id,
                c.name,
                c.phone,
                c.email,
                COUNT(j.id) as total_jobs,
                SUM(j.total_amount) as lifetime_value,
                MAX(j.start_at) as last_job_date,
                MIN(j.start_at) as first_job_date
            FROM customers c
            LEFT JOIN jobs j ON c.id = j.customer_id
            GROUP BY c.id
            HAVING total_jobs > 0
            ORDER BY lifetime_value DESC
        ");
        
        // Customer segments
        $segments = self::segmentCustomers($lifetimeValue);
        
        // Acquisition trends
        $acquisition = $db->fetchAll("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as new_customers
            FROM customers
            WHERE created_at >= date('now', '-12 months')
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        
        return [
            'lifetime_value' => $lifetimeValue,
            'segments' => $segments,
            'acquisition_trends' => $acquisition,
            'retention_rate' => self::calculateRetentionRate()
        ];
    }
    
    /**
     * Group data by period
     */
    private static function groupByPeriod(array $data, string $groupBy): array
    {
        $grouped = [];
        
        foreach ($data as $row) {
            $date = new DateTime($row['period']);
            $key = match($groupBy) {
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
     * Calculate summary
     */
    private static function calculateSummary(array $data): array
    {
        $income = 0;
        $expense = 0;
        
        foreach ($data as $row) {
            if ($row['kind'] === 'INCOME') {
                $income += $row['total'];
            } else {
                $expense += $row['total'];
            }
        }
        
        return [
            'total_income' => $income,
            'total_expense' => $expense,
            'net_profit' => $income - $expense,
            'profit_margin' => $income > 0 ? (($income - $expense) / $income) * 100 : 0
        ];
    }
    
    /**
     * Calculate trends
     */
    private static function calculateTrends(array $data): array
    {
        if (count($data) < 2) {
            return ['trend' => 'stable', 'change_percentage' => 0];
        }
        
        $first = $data[0];
        $last = end($data);
        
        $firstValue = $first['income'] - $first['expense'];
        $lastValue = $last['income'] - $last['expense'];
        
        if ($firstValue == 0) {
            return ['trend' => 'stable', 'change_percentage' => 0];
        }
        
        $change = (($lastValue - $firstValue) / abs($firstValue)) * 100;
        
        return [
            'trend' => $change > 5 ? 'increasing' : ($change < -5 ? 'decreasing' : 'stable'),
            'change_percentage' => round($change, 2)
        ];
    }
    
    /**
     * Calculate efficiency metrics
     */
    private static function calculateEfficiency(array $stats): array
    {
        $total = 0;
        $completed = 0;
        
        foreach ($stats as $stat) {
            $total += $stat['count'];
            if ($stat['status'] === 'completed') {
                $completed = $stat['count'];
            }
        }
        
        return [
            'completion_rate' => $total > 0 ? ($completed / $total) * 100 : 0,
            'average_job_value' => self::calculateAverage($stats, 'avg_revenue')
        ];
    }
    
    /**
     * Segment customers
     */
    private static function segmentCustomers(array $customers): array
    {
        $segments = [
            'vip' => [],
            'regular' => [],
            'new' => []
        ];
        
        foreach ($customers as $customer) {
            $value = $customer['lifetime_value'] ?? 0;
            $jobs = $customer['total_jobs'] ?? 0;
            
            if ($value > 10000 || $jobs > 50) {
                $segments['vip'][] = $customer;
            } elseif ($jobs > 5) {
                $segments['regular'][] = $customer;
            } else {
                $segments['new'][] = $customer;
            }
        }
        
        return [
            'vip_count' => count($segments['vip']),
            'regular_count' => count($segments['regular']),
            'new_count' => count($segments['new'])
        ];
    }
    
    /**
     * Calculate retention rate
     */
    private static function calculateRetentionRate(): float
    {
        $db = Database::getInstance();
        
        // Customers with jobs in last 30 days
        $active = $db->fetch("
            SELECT COUNT(DISTINCT customer_id) as count 
            FROM jobs 
            WHERE start_at >= date('now', '-30 days')
        ")['count'] ?? 0;
        
        // Total customers with jobs
        $total = $db->fetch("
            SELECT COUNT(DISTINCT customer_id) as count 
            FROM jobs 
            WHERE start_at >= date('now', '-90 days')
        ")['count'] ?? 0;
        
        return $total > 0 ? ($active / $total) * 100 : 0;
    }
    
    /**
     * Helper: Calculate average
     */
    private static function calculateAverage(array $data, string $field): float
    {
        $sum = 0;
        $count = 0;
        
        foreach ($data as $item) {
            if (isset($item[$field])) {
                $sum += $item[$field];
                $count++;
            }
        }
        
        return $count > 0 ? $sum / $count : 0;
    }
}

