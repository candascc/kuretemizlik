<?php
/**
 * Memory Stress Test
 * Tests for memory leaks and excessive memory usage
 */

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;
use Tests\Support\FactoryRegistry;

class MemoryStressTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        FactoryRegistry::setDatabase($this->db);
        
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        parent::tearDown();
    }

    /**
     * Test memory usage with large result sets
     */
    public function testMemoryUsageWithLargeResultSets(): void
    {
        // Create large dataset
        $customerIds = FactoryRegistry::customer()->createMany(1000, ['company_id' => 1]);
        
        $initialMemory = memory_get_usage(true);
        
        // Fetch all customers
        $results = $this->db->fetchAll("SELECT * FROM customers WHERE company_id = ?", [1]);
        
        $finalMemory = memory_get_usage(true);
        $memoryUsed = round(($finalMemory - $initialMemory) / 1024 / 1024, 2);

        // Should have at least 1000 customers (may have more from previous tests)
        $this->assertGreaterThanOrEqual(1000, count($results), 'Should fetch at least 1000 customers');
        $this->assertLessThan(60, $memoryUsed, 'Memory usage should be < 60MB for 1000 records');
    }

    /**
     * Test memory leak detection with repeated operations
     */
    public function testMemoryLeakDetection(): void
    {
        require_once __DIR__ . '/../Support/FactoryRegistry.php';
        
        $iterations = 100;
        $memorySnapshots = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            // Create and fetch data
            $customerId = FactoryRegistry::customer()->create(['company_id' => 1]);
            $result = $this->db->fetch("SELECT * FROM customers WHERE id = ?", [$customerId]);
            
            // Take memory snapshot every 10 iterations
            if ($i % 10 === 0) {
                $memorySnapshots[] = memory_get_usage(true);
            }
        }

        // Check if memory is growing linearly (potential leak)
        $firstSnapshot = $memorySnapshots[0];
        $lastSnapshot = $memorySnapshots[count($memorySnapshots) - 1];
        $memoryGrowth = round(($lastSnapshot - $firstSnapshot) / 1024 / 1024, 2);

        // Memory growth should be reasonable (not exponential)
        $this->assertLessThan(20, $memoryGrowth, 'Memory growth should be < 20MB over 100 iterations');
    }

    /**
     * Test memory cleanup after large operations
     */
    public function testMemoryCleanupAfterLargeOperations(): void
    {
        require_once __DIR__ . '/../Support/FactoryRegistry.php';
        
        $initialMemory = memory_get_usage(true);
        
        // Perform large operation
        $customerIds = FactoryRegistry::customer()->createMany(500, ['company_id' => 1]);
        $results = $this->db->fetchAll("SELECT * FROM customers WHERE company_id = ?", [1]);
        
        // Clear variables
        unset($customerIds, $results);
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryDifference = round(($finalMemory - $initialMemory) / 1024 / 1024, 2);

        // Memory should be cleaned up (difference should be small)
        $this->assertLessThan(30, abs($memoryDifference), 'Memory should be cleaned up after operation');
    }
}

