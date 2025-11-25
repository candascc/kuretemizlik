<?php
/**
 * Cache Helper
 * Helper functions for cache invalidation patterns
 */
class CacheHelper
{
    /**
     * Clear dashboard cache for specific date
     */
    public static function clearDashboardCache(?string $date = null): void
    {
        $date = $date ?? date('Y-m-d');
        Cache::delete("dashboard:today:{$date}");
        
        // Also clear yesterday and tomorrow for border cases
        $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($date)));
        $tomorrow = date('Y-m-d', strtotime('+1 day', strtotime($date)));
        Cache::delete("dashboard:today:{$yesterday}");
        Cache::delete("dashboard:today:{$tomorrow}");
    }
    
    /**
     * Clear all dashboard-related caches
     */
    public static function clearAllDashboardCaches(): void
    {
        // Clear today, yesterday, tomorrow
        self::clearDashboardCache();
        self::clearDashboardCache(date('Y-m-d', strtotime('-1 day')));
        self::clearDashboardCache(date('Y-m-d', strtotime('+1 day')));
    }
    
    /**
     * Clear job-related caches
     */
    public static function clearJobCaches(?int $jobId = null): void
    {
        self::clearDashboardCache();
        
        if ($jobId) {
            Cache::delete("job:{$jobId}");
            Cache::delete("job:{$jobId}:details");
        }
    }
    
    /**
     * Clear customer-related caches
     */
    public static function clearCustomerCaches(?int $customerId = null): void
    {
        self::clearDashboardCache();
        
        if ($customerId) {
            Cache::delete("customer:{$customerId}");
            Cache::delete("customer:{$customerId}:jobs");
            Cache::delete("customer:{$customerId}:addresses");
        }
    }
    
    /**
     * Clear finance-related caches
     */
    public static function clearFinanceCaches(?string $date = null): void
    {
        self::clearDashboardCache($date);
        
        if ($date) {
            Cache::delete("finance:stats:{$date}");
            Cache::delete("finance:week:{$date}");
            Cache::delete("finance:month:{$date}");
        } else {
            // Clear all finance caches for current month
            $today = date('Y-m-d');
            $monthStart = date('Y-m-01');
            $current = strtotime($monthStart);
            $end = strtotime($today);
            
            while ($current <= $end) {
                $dateStr = date('Y-m-d', $current);
                Cache::delete("finance:stats:{$dateStr}");
                $current = strtotime('+1 day', $current);
            }
        }
    }
    
    /**
     * Clear recurring job caches
     */
    public static function clearRecurringJobCaches(?int $recurringJobId = null): void
    {
        self::clearDashboardCache();
        
        if ($recurringJobId) {
            Cache::delete("recurring_job:{$recurringJobId}");
            Cache::delete("recurring_job:{$recurringJobId}:occurrences");
            Cache::delete("recurring_job:{$recurringJobId}:jobs");
        }
    }
    
    /**
     * Clear notification caches
     */
    public static function clearNotificationCaches(): void
    {
        // Clear all notification cache keys for today
        $today = date('Y-m-d');
        $hour = date('H');
        
        // Clear current and previous hour's caches
        for ($i = 0; $i <= 2; $i++) {
            $cacheHour = date('H', strtotime("-{$i} hours"));
            Cache::delete("notifications:all:{$today}-{$cacheHour}:" . floor(time() / 120));
        }
    }
}

