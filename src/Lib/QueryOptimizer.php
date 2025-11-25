<?php

/**
 * Query Optimizer
 */
class QueryOptimizer
{
    private static $queryCache = [];
    private static $slowQueries = [];
    private static $slowQueryThreshold = 0.1; // 100ms

    /**
     * Execute optimized query with caching
     */
    public static function execute($sql, $params = [], $cacheKey = null, $cacheTTL = 3600)
    {
        $startTime = microtime(true);
        
        // Check cache first
        if ($cacheKey) {
            $cache = CacheManager::getInstance();
            $cached = $cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        // Execute query
        $db = Database::getInstance();
        $result = $db->fetchAll($sql, $params);
        
        $executionTime = microtime(true) - $startTime;
        
        // Log slow queries
        if ($executionTime > self::$slowQueryThreshold) {
            self::$slowQueries[] = [
                'sql' => $sql,
                'params' => $params,
                'execution_time' => $executionTime,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }

        // Cache result
        if ($cacheKey) {
            $cache = CacheManager::getInstance();
            $cache->set($cacheKey, $result, $cacheTTL);
        }

        return $result;
    }

    /**
     * Get optimized jobs with filters
     */
    public static function getJobs($filters = [], $limit = 20, $offset = 0)
    {
        $where = [];
        $params = [];
        $cacheKey = 'jobs_' . md5(serialize($filters) . $limit . $offset);

        // Build WHERE clause
        if (!empty($filters['status'])) {
            $where[] = "j.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['customer_id'])) {
            $where[] = "j.customer_id = ?";
            $params[] = $filters['customer_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(j.start_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "DATE(j.start_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT 
                j.*,
                c.name as customer_name,
                c.phone as customer_phone,
                s.name as service_name,
                a.line as address_line
            FROM jobs j
            LEFT JOIN customers c ON j.customer_id = c.id
            LEFT JOIN services s ON j.service_id = s.id
            LEFT JOIN addresses a ON j.address_id = a.id
            $whereClause
            ORDER BY j.start_at DESC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;

        return self::execute($sql, $params, $cacheKey);
    }

    /**
     * Get dashboard statistics with caching
     */
    public static function getDashboardStats()
    {
        $cache = CacheManager::getInstance();
        $cacheKey = 'dashboard_stats_' . date('Y-m-d-H');
        
        return $cache->remember($cacheKey, function() {
            $db = Database::getInstance();
            
            // Job statistics
            $jobStats = $db->fetch("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'SCHEDULED' THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN status = 'DONE' THEN 1 ELSE 0 END) as done,
                    SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled
                FROM jobs
            ");

            // Customer statistics
            $customerStats = $db->fetch("
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN created_at >= date('now', '-30 days') THEN 1 END) as new_this_month
                FROM customers
            ");

            // Finance statistics
            $financeStats = $db->fetch("
                SELECT 
                    COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as total_income,
                    COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as total_expense
                FROM money_entries
            ");

            $financeStats['net_profit'] = $financeStats['total_income'] - $financeStats['total_expense'];

            return [
                'jobs' => $jobStats,
                'customers' => $customerStats,
                'finance' => $financeStats
            ];
        }, 1800); // 30 minutes cache
    }

    /**
     * Get monthly job statistics
     */
    public static function getMonthlyJobStats($year = null, $month = null)
    {
        $year = $year ?? date('Y');
        $month = $month ?? date('m');
        
        $cacheKey = "monthly_job_stats_{$year}_{$month}";
        
        return self::execute("
            SELECT
                DATE(start_at) as date,
                COUNT(*) as total_jobs,
                SUM(CASE WHEN status = 'DONE' THEN 1 ELSE 0 END) as completed_jobs,
                SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled_jobs,
                SUM(total_amount) as total_revenue
            FROM jobs
            WHERE strftime('%Y', start_at) = ? AND strftime('%m', start_at) = ?
            GROUP BY DATE(start_at)
            ORDER BY date
        ", [$year, $month], $cacheKey, 3600);
    }

    /**
     * Get customer performance metrics
     */
    public static function getCustomerMetrics($customerId)
    {
        $cacheKey = "customer_metrics_{$customerId}";
        
        return self::execute("
            SELECT 
                COUNT(*) as total_jobs,
                SUM(CASE WHEN status = 'DONE' THEN 1 ELSE 0 END) as completed_jobs,
                SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled_jobs,
                SUM(total_amount) as total_spent,
                AVG(total_amount) as avg_job_value,
                MAX(start_at) as last_job_date
            FROM jobs
            WHERE customer_id = ?
        ", [$customerId], $cacheKey, 1800);
    }

    /**
     * Get service performance metrics
     */
    public static function getServiceMetrics($serviceId = null)
    {
        $where = $serviceId ? "WHERE service_id = ?" : "";
        $params = $serviceId ? [$serviceId] : [];
        $cacheKey = "service_metrics_" . ($serviceId ?? 'all');
        
        return self::execute("
            SELECT 
                s.id as service_id,
                s.name as service_name,
                COUNT(j.id) as total_jobs,
                SUM(CASE WHEN j.status = 'DONE' THEN 1 ELSE 0 END) as completed_jobs,
                SUM(j.total_amount) as total_revenue,
                AVG(j.total_amount) as avg_job_value
            FROM services s
            LEFT JOIN jobs j ON s.id = j.service_id
            $where
            GROUP BY s.id, s.name
            ORDER BY total_revenue DESC
        ", $params, $cacheKey, 3600);
    }

    /**
     * Get slow queries report
     */
    public static function getSlowQueries()
    {
        return self::$slowQueries;
    }

    /**
     * Clear query cache
     */
    public static function clearCache()
    {
        $cache = CacheManager::getInstance();
        return $cache->clear();
    }

    /**
     * Get cache hit ratio
     */
    public static function getCacheStats()
    {
        $cache = CacheManager::getInstance();
        return $cache->getStats();
    }

    /**
     * Optimize database indexes
     */
    public static function optimizeIndexes()
    {
        $db = Database::getInstance();
        
        // Create indexes for common queries
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_jobs_status ON jobs(status)",
            "CREATE INDEX IF NOT EXISTS idx_jobs_customer_id ON jobs(customer_id)",
            "CREATE INDEX IF NOT EXISTS idx_jobs_start_at ON jobs(start_at)",
            "CREATE INDEX IF NOT EXISTS idx_jobs_payment_status ON jobs(payment_status)",
            "CREATE INDEX IF NOT EXISTS idx_customers_name ON customers(name)",
            "CREATE INDEX IF NOT EXISTS idx_customers_phone ON customers(phone)",
            "CREATE INDEX IF NOT EXISTS idx_money_entries_type ON money_entries(type)",
            "CREATE INDEX IF NOT EXISTS idx_money_entries_date ON money_entries(created_at)",
            "CREATE INDEX IF NOT EXISTS idx_addresses_customer_id ON addresses(customer_id)",
            "CREATE INDEX IF NOT EXISTS idx_activity_logs_entity ON activity_logs(entity_type, entity_id)"
        ];

        foreach ($indexes as $index) {
            try {
                $db->execute($index);
            } catch (Exception $e) {
                error_log("Index creation failed: " . $e->getMessage());
            }
        }

        return true;
    }
}