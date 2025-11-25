<?php
/**
 * Large Dataset Search Test
 * Tests search functionality with 10000+ records
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Support/DatabaseSeeder.php';

use PHPUnit\Framework\TestCase;

class LargeDatasetSearchTest extends TestCase
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
     * Test search performance with large dataset
     */
    public function testSearchPerformance(): void
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
     * Test search with multiple conditions
     */
    public function testSearchWithMultipleConditions(): void
    {
        $startTime = microtime(true);
        
        $results = $this->db->fetchAll(
            "SELECT * FROM customers WHERE company_id = ? AND (name LIKE ? OR email LIKE ?) LIMIT 50",
            [1, '%Test%', '%example%']
        );
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertLessThanOrEqual(50, count($results), 'Multi-condition search should return <= 50 results');
        $this->assertLessThan(400, $duration, 'Multi-condition search should complete in < 400ms');
    }
}

