<?php
/**
 * Large Dataset Filter Test
 * Tests complex filter combinations with large datasets
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Support/DatabaseSeeder.php';

use PHPUnit\Framework\TestCase;

class LargeDatasetFilterTest extends TestCase
{
    private Database $db;
    private \Tests\Support\Seeders\LargeDatasetSeeder $seeder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        require_once __DIR__ . '/../Support/Seeders/LargeDatasetSeeder.php';
        
        $this->seeder = new \Tests\Support\Seeders\LargeDatasetSeeder();
        $this->seeder->seed();
    }

    protected function tearDown(): void
    {
        $this->seeder->cleanup();
        parent::tearDown();
    }

    /**
     * Test complex filter combinations
     */
    public function testComplexFilterCombinations(): void
    {
        $filters = [
            'status' => 'SCHEDULED',
            'payment_status' => 'UNPAID',
            'min_amount' => 100,
        ];

        $startTime = microtime(true);
        
        $results = $this->db->fetchAll(
            "SELECT * FROM jobs 
             WHERE status = ? 
             AND payment_status = ? 
             AND total_amount >= ? 
             ORDER BY created_at DESC 
             LIMIT 100",
            [$filters['status'], $filters['payment_status'], $filters['min_amount']]
        );
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertLessThanOrEqual(100, count($results), 'Complex filter should return <= 100 results');
        $this->assertLessThan(500, $duration, 'Complex filter should complete in < 500ms');
    }

    /**
     * Test filter with date range
     */
    public function testFilterWithDateRange(): void
    {
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');

        $startTime = microtime(true);
        
        $results = $this->db->fetchAll(
            "SELECT * FROM jobs 
             WHERE DATE(start_at) BETWEEN ? AND ? 
             AND status = ? 
             ORDER BY start_at DESC 
             LIMIT 100",
            [$startDate, $endDate, 'SCHEDULED']
        );
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertLessThanOrEqual(100, count($results), 'Date range filter should return <= 100 results');
        $this->assertLessThan(400, $duration, 'Date range filter should complete in < 400ms');
    }
}

