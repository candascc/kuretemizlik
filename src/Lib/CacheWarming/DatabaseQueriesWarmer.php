<?php
/**
 * Database Queries Cache Warmer
 * Pre-loads frequently used database queries
 */

class DatabaseQueriesWarmer
{
    private $cacheManager;
    
    public function __construct()
    {
        $this->cacheManager = CacheManager::getInstance();
    }
    
    /**
     * Warm database queries cache
     */
    public function warm(): void
    {
        Logger::info('Starting database queries cache warming');
        
        try {
            // Warm customer data
            $this->warmCustomerData();
            
            // Warm service data
            $this->warmServiceData();
            
            // Warm job statistics
            $this->warmJobStatistics();
            
            // Warm financial data
            $this->warmFinancialData();
            
            Logger::info('Database queries cache warming completed');
            
        } catch (Exception $e) {
            Logger::error('Database queries cache warming failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Warm customer data
     */
    private function warmCustomerData(): void
    {
        $db = Database::getInstance();
        
        // Active customers count
        $activeCustomers = $db->fetch("SELECT COUNT(*) as count FROM customers WHERE is_active = 1");
        $this->cacheManager->set('stats:customers:active', $activeCustomers['count'], 1800);
        
        // Recent customers
        $recentCustomers = $db->fetchAll(
            "SELECT * FROM customers ORDER BY created_at DESC LIMIT 10"
        );
        $this->cacheManager->set('data:customers:recent', $recentCustomers, 1800);
        
        Logger::info('Customer data cached');
    }
    
    /**
     * Warm service data
     */
    private function warmServiceData(): void
    {
        $db = Database::getInstance();
        
        // Active services
        $activeServices = $db->fetchAll(
            "SELECT * FROM services WHERE is_active = 1 ORDER BY name"
        );
        $this->cacheManager->set('data:services:active', $activeServices, 3600);
        
        // Service statistics
        $serviceStats = $db->fetchAll(
            "SELECT s.name, COUNT(j.id) as job_count 
             FROM services s 
             LEFT JOIN jobs j ON s.id = j.service_id 
             WHERE s.is_active = 1 
             GROUP BY s.id, s.name 
             ORDER BY job_count DESC"
        );
        $this->cacheManager->set('stats:services:popularity', $serviceStats, 1800);
        
        Logger::info('Service data cached');
    }
    
    /**
     * Warm job statistics
     */
    private function warmJobStatistics(): void
    {
        $db = Database::getInstance();
        
        // Job counts by status
        $jobCounts = $db->fetchAll(
            "SELECT status, COUNT(*) as count 
             FROM jobs 
             GROUP BY status"
        );
        $this->cacheManager->set('stats:jobs:by_status', $jobCounts, 1800);
        
        // Recent jobs
        $recentJobs = $db->fetchAll(
            "SELECT j.*, c.name as customer_name, s.name as service_name 
             FROM jobs j 
             JOIN customers c ON j.customer_id = c.id 
             JOIN services s ON j.service_id = s.id 
             ORDER BY j.created_at DESC 
             LIMIT 20"
        );
        $this->cacheManager->set('data:jobs:recent', $recentJobs, 1800);
        
        Logger::info('Job statistics cached');
    }
    
    /**
     * Warm financial data
     */
    private function warmFinancialData(): void
    {
        $db = Database::getInstance();
        
        // Monthly revenue
        $monthlyRevenue = $db->fetch(
            "SELECT SUM(amount) as total 
             FROM payments 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)"
        );
        $this->cacheManager->set('stats:finance:monthly_revenue', $monthlyRevenue['total'] ?? 0, 1800);
        
        // Payment statistics
        $paymentStats = $db->fetchAll(
            "SELECT status, COUNT(*) as count, SUM(amount) as total 
             FROM payments 
             GROUP BY status"
        );
        $this->cacheManager->set('stats:finance:payments', $paymentStats, 1800);
        
        Logger::info('Financial data cached');
    }
}
