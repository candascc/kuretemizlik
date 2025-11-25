<?php
/**
 * Search/Filter Stress Test
 * Tests search and filter operations with 10000+ records
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Support/DatabaseSeeder.php';
require_once __DIR__ . '/../Support/FactoryRegistry.php';

use PHPUnit\Framework\TestCase;
use Tests\Support\FactoryRegistry;

class SearchFilterStressTest extends TestCase
{
    private Database $db;
    private \Tests\Support\Seeders\LargeDatasetSeeder $seeder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        require_once __DIR__ . '/../Support/DatabaseSeeder.php';
        require_once __DIR__ . '/../Support/Seeders/LargeDatasetSeeder.php';
        require_once __DIR__ . '/../Support/FactoryRegistry.php';
        
        FactoryRegistry::setDatabase($this->db);
        $this->seeder = new \Tests\Support\Seeders\LargeDatasetSeeder();
        $this->seeder->seed();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test search with 10000+ records
     */
    public function testSearchWith10000Records(): void
    {
        $searchTerm = 'Test';
        
        $startTime = microtime(true);
        
        $results = $this->db->fetchAll(
            "SELECT * FROM customers WHERE name LIKE ? OR email LIKE ? LIMIT 100",
            ["%{$searchTerm}%", "%{$searchTerm}%"]
        );
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertLessThanOrEqual(100, count($results), 'Search should return <= 100 results');
        $this->assertLessThan(500, $duration, 'Search should complete in < 500ms');
    }

    /**
     * Test filter with multiple conditions
     */
    public function testFilterWithMultipleConditions(): void
    {
        $startTime = microtime(true);
        
        $results = $this->db->fetchAll(
            "SELECT * FROM jobs 
             WHERE status = ? 
             AND payment_status = ? 
             AND total_amount > ? 
             ORDER BY created_at DESC 
             LIMIT 100",
            ['SCHEDULED', 'UNPAID', 100]
        );
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertLessThanOrEqual(100, count($results), 'Filter should return <= 100 results');
        $this->assertLessThan(400, $duration, 'Multi-condition filter should complete in < 400ms');
    }

    /**
     * Test complex WHERE clause with IN
     */
    public function testComplexWhereClauseWithIn(): void
    {
        FactoryRegistry::setDatabase($this->db);
        
        // Create jobs with various statuses
        $customerId = FactoryRegistry::customer()->create(['company_id' => 1]);
        $statuses = ['SCHEDULED', 'DONE'];
        $jobIds = [];
        for ($i = 0; $i < 100; $i++) {
            $jobIds[] = FactoryRegistry::job()->create([
                'customer_id' => $customerId,
                'company_id' => 1,
                'status' => $statuses[array_rand($statuses)],
            ]);
        }

        $startTime = microtime(true);
        
        $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
        $results = $this->db->fetchAll(
            "SELECT * FROM jobs WHERE id IN ({$placeholders}) AND status = ?",
            array_merge($jobIds, ['SCHEDULED'])
        );
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertLessThanOrEqual(count($jobIds), count($results), 'IN clause should return <= job count');
        $this->assertLessThan(300, $duration, 'IN clause query should complete in < 300ms');
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
             ORDER BY start_at DESC 
             LIMIT 100",
            [$startDate, $endDate]
        );
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertLessThanOrEqual(100, count($results), 'Date range filter should return <= 100 results');
        $this->assertLessThan(400, $duration, 'Date range filter should complete in < 400ms');
    }
}

