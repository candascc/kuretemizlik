<?php
/**
 * Revenue Forecaster
 * Predicts future revenue using linear regression
 */

namespace App\Lib\Analytics;

class Forecaster
{
    private $db;
    
    public function __construct()
    {
        $this->db = \Database::getInstance();
    }
    
    /**
     * Forecast revenue for next N months
     */
    public function forecastRevenue(int $months = 3): array
    {
        // Get historical data (last 12 months)
        $historicalData = $this->getHistoricalRevenue(12);
        
        if (count($historicalData) < 3) {
            return ['error' => 'Insufficient historical data'];
        }
        
        // Simple linear regression
        $forecast = [];
        $trend = $this->calculateTrend($historicalData);
        
        $lastMonth = end($historicalData);
        $lastRevenue = $lastMonth['revenue'];
        
        for ($i = 1; $i <= $months; $i++) {
            $forecastDate = date('Y-m', strtotime("+{$i} months"));
            $forecastRevenue = $lastRevenue + ($trend * $i);
            
            $forecast[] = [
                'month' => $forecastDate,
                'forecast' => max(0, round($forecastRevenue, 2)),
                'confidence' => max(0, 100 - ($i * 10)) // Decreasing confidence
            ];
        }
        
        return [
            'historical' => $historicalData,
            'forecast' => $forecast,
            'trend' => $trend > 0 ? 'increasing' : ($trend < 0 ? 'decreasing' : 'stable')
        ];
    }
    
    /**
     * Get historical revenue data
     */
    private function getHistoricalRevenue(int $months): array
    {
        $startDate = date('Y-m-01', strtotime("-{$months} months"));
        
        $data = $this->db->fetchAll(
            "SELECT 
                strftime('%Y-%m', date) as month,
                SUM(amount) as revenue
             FROM money_entries
             WHERE kind = 'income' AND date >= ?
             GROUP BY month
             ORDER BY month",
            [$startDate]
        );
        
        return $data;
    }
    
    /**
     * Calculate revenue trend (linear regression slope)
     */
    private function calculateTrend(array $data): float
    {
        $n = count($data);
        
        if ($n < 2) {
            return 0;
        }
        
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        
        foreach ($data as $i => $point) {
            $x = $i;
            $y = $point['revenue'];
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        
        return round($slope, 2);
    }
}

