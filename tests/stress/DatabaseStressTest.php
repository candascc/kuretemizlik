<?php
/**
 * Database Stress Test
 * Tests database operations with bulk inserts and complex queries
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Support/DatabaseSeeder.php';
require_once __DIR__ . '/../Support/FactoryRegistry.php';

use PHPUnit\Framework\TestCase;
use Tests\Support\FactoryRegistry;

class DatabaseStressTest extends TestCase
{
    private Database $db;
    private \Tests\Support\Seeders\LargeDatasetSeeder $seeder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        require_once __DIR__ . '/../Support/DatabaseSeeder.php';
        require_once __DIR__ . '/../Support/Seeders/LargeDatasetSeeder.php';
        $this->seeder = new \Tests\Support\Seeders\LargeDatasetSeeder(false);
        $this->seeder->seed();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test bulk insert performance (10000 records)
     */
    public function testBulkInsert10000Records(): void
    {
        FactoryRegistry::setDatabase($this->db);
        $this->db->beginTransaction();
        
        try {
            $startTime = microtime(true);
            $ids = FactoryRegistry::customer()->createMany(10000, ['company_id' => 1]);
            $endTime = microtime(true);
            
            $duration = round($endTime - $startTime, 2);
            $recordsPerSecond = round(10000 / $duration, 0);

            $this->assertCount(10000, $ids, 'Should create 10000 records');
            $this->assertLessThan(30, $duration, 'Should complete in less than 30 seconds');
            $this->assertGreaterThan(300, $recordsPerSecond, 'Should insert at least 300 records per second');
        } finally {
            $this->db->rollBack();
        }
    }

    /**
     * Test complex query with joins
     */
    public function testComplexQueryWithJoins(): void
    {
        // Seed data
        $this->seeder->seedBasic();
        $records = $this->seeder->getCreatedRecords();
        
        if (empty($records['jobs'] ?? [])) {
            // Create some jobs
            require_once __DIR__ . '/../Support/FactoryRegistry.php';
            $customerId = FactoryRegistry::customer()->create(['company_id' => 1]);
            for ($i = 0; $i < 100; $i++) {
                FactoryRegistry::job()->create(['customer_id' => $customerId, 'company_id' => 1]);
            }
        }

        $startTime = microtime(true);
        
        $results = $this->db->fetchAll(
            "SELECT j.*, c.name as customer_name, c.email as customer_email 
             FROM jobs j 
             INNER JOIN customers c ON j.customer_id = c.id 
             WHERE j.status = ? 
             ORDER BY j.created_at DESC 
             LIMIT 100",
            ['SCHEDULED']
        );
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertLessThanOrEqual(100, count($results), 'Should return <= 100 results');
        $this->assertLessThan(500, $duration, 'Complex query should complete in < 500ms');
    }

    /**
     * Test aggregation query performance
     */
    public function testAggregationQueryPerformance(): void
    {
        // Seed data
        $this->seeder->seedBasic();
        
        FactoryRegistry::setDatabase($this->db);
        
        // Create more jobs with various statuses
        $customerId = FactoryRegistry::customer()->create(['company_id' => 1]);
        $statuses = ['SCHEDULED', 'DONE', 'CANCELLED'];
        for ($i = 0; $i < 500; $i++) {
            FactoryRegistry::job()->create([
                'customer_id' => $customerId,
                'company_id' => 1,
                'status' => $statuses[array_rand($statuses)],
            ]);
        }

        $startTime = microtime(true);
        
        $stats = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count, SUM(total_amount) as total 
             FROM jobs 
             GROUP BY status"
        );
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertGreaterThan(0, count($stats), 'Should return aggregation results');
        $this->assertLessThan(200, $duration, 'Aggregation query should complete in < 200ms');
    }

    /**
     * Test transaction performance
     */
    public function testTransactionPerformance(): void
    {
        FactoryRegistry::setDatabase($this->db);

        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->db->beginTransaction();
            try {
                $customerId = FactoryRegistry::customer()->create(['company_id' => 1]);
                $jobId = FactoryRegistry::job()->create(['customer_id' => $customerId, 'company_id' => 1]);
                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        $transactionsPerSecond = round($iterations / $duration, 0);

        $this->assertLessThan(10, $duration, '100 transactions should complete in < 10 seconds');
        $this->assertGreaterThan(10, $transactionsPerSecond, 'Should handle at least 10 transactions per second');
    }
}

