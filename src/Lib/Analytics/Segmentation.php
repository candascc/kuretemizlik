<?php
/**
 * Customer Segmentation
 * Segments customers by value, frequency, and recency
 */

namespace App\Lib\Analytics;

class Segmentation
{
    private $db;
    
    public function __construct()
    {
        $this->db = \Database::getInstance();
    }
    
    /**
     * RFM Analysis (Recency, Frequency, Monetary)
     */
    public function rfmAnalysis(): array
    {
        $customers = $this->db->fetchAll(
            "SELECT 
                c.id,
                c.name,
                COUNT(j.id) as frequency,
                MAX(j.start_at) as last_job_date,
                COALESCE(SUM(m.amount), 0) as monetary_value
             FROM customers c
             LEFT JOIN jobs j ON c.id = j.customer_id
             LEFT JOIN money_entries m ON j.id = m.job_id AND m.kind = 'income'
             GROUP BY c.id"
        );
        
        $segments = [];
        
        foreach ($customers as $customer) {
            $recency = $customer['last_job_date'] 
                ? (time() - strtotime($customer['last_job_date'])) / 86400 
                : 999;
            
            $segment = $this->determineSegment(
                $recency,
                $customer['frequency'],
                $customer['monetary_value']
            );
            
            $segments[$segment][] = $customer;
        }
        
        return $segments;
    }
    
    /**
     * Determine customer segment
     */
    private function determineSegment(float $recency, int $frequency, float $monetary): string
    {
        // VIP: High value, frequent, recent
        if ($monetary > 5000 && $frequency > 10 && $recency < 30) {
            return 'VIP';
        }
        
        // Champions: High value, frequent
        if ($monetary > 2000 && $frequency > 5) {
            return 'Champions';
        }
        
        // Loyal: Frequent but lower value
        if ($frequency > 5 && $monetary < 2000) {
            return 'Loyal';
        }
        
        // At Risk: Not recent
        if ($recency > 90 && $frequency > 3) {
            return 'At Risk';
        }
        
        // New: Recent but low frequency
        if ($recency < 30 && $frequency <= 2) {
            return 'New';
        }
        
        // Inactive: No recent activity
        if ($recency > 180) {
            return 'Inactive';
        }
        
        return 'Regular';
    }
    
    /**
     * Get segment statistics
     */
    public function getSegmentStats(): array
    {
        $segments = $this->rfmAnalysis();
        $stats = [];
        
        foreach ($segments as $segment => $customers) {
            $stats[$segment] = [
                'count' => count($customers),
                'total_value' => array_sum(array_column($customers, 'monetary_value')),
                'avg_value' => count($customers) > 0 
                    ? array_sum(array_column($customers, 'monetary_value')) / count($customers) 
                    : 0
            ];
        }
        
        return $stats;
    }
}

