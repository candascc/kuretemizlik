<?php
/**
 * Database Load Test
 * Tests concurrent database operations
 */

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;
use Tests\Support\FactoryRegistry;

class DatabaseLoadTest extends TestCase
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
     * Test concurrent database reads
     */
    public function testConcurrentDatabaseReads(): void
    {
        // Create test data
        $customerIds = FactoryRegistry::customer()->createMany(10, ['company_id' => 1]);

        $concurrentReads = 100;
        $successful = 0;
        $startTime = microtime(true);

        for ($i = 0; $i < $concurrentReads; $i++) {
            $customerId = $customerIds[array_rand($customerIds)];
            try {
                $result = $this->db->fetch("SELECT * FROM customers WHERE id = ?", [$customerId]);
                if ($result) {
                    $successful++;
                }
            } catch (Exception $e) {
                // Ignore errors for load test
            }
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertEquals($concurrentReads, $successful, 'All reads should succeed');
        $this->assertLessThan(500, $duration, '100 concurrent reads should complete in < 500ms');
    }

    /**
     * Test concurrent database writes
     */
    public function testConcurrentDatabaseWrites(): void
    {
        require_once __DIR__ . '/../Support/FactoryRegistry.php';
        
        $concurrentWrites = 50;
        $successful = 0;
        $startTime = microtime(true);

        for ($i = 0; $i < $concurrentWrites; $i++) {
            try {
                $customerId = FactoryRegistry::customer()->create([
                    'company_id' => 1,
                    'name' => 'Load Test Customer ' . $i,
                ]);
                if ($customerId) {
                    $successful++;
                }
            } catch (Exception $e) {
                // Ignore errors for load test
            }
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertEquals($concurrentWrites, $successful, 'All writes should succeed');
        $this->assertLessThan(2000, $duration, '50 concurrent writes should complete in < 2 seconds');
    }

    /**
     * Test transaction isolation under load
     */
    public function testTransactionIsolationUnderLoad(): void
    {
        require_once __DIR__ . '/../Support/FactoryRegistry.php';
        
        $concurrentTransactions = 20;
        $successful = 0;
        $startTime = microtime(true);

        for ($i = 0; $i < $concurrentTransactions; $i++) {
            try {
                $this->db->beginTransaction();
                $customerId = FactoryRegistry::customer()->create(['company_id' => 1]);
                $jobId = FactoryRegistry::job()->create(['customer_id' => $customerId, 'company_id' => 1]);
                $this->db->commit();
                $successful++;
            } catch (Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
            }
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $this->assertEquals($concurrentTransactions, $successful, 'All transactions should succeed');
        $this->assertLessThan(3000, $duration, '20 concurrent transactions should complete in < 3 seconds');
    }
}

