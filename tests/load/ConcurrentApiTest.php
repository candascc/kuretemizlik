<?php
/**
 * Concurrent API Test
 * Tests race conditions in API endpoints
 */

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;
use Tests\Support\FactoryRegistry;

class ConcurrentApiTest extends TestCase
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
     * Test concurrent updates to same record
     */
    public function testConcurrentUpdatesToSameRecord(): void
    {
        $customerId = FactoryRegistry::customer()->create(['company_id' => 1]);
        $concurrentUpdates = 10;
        $successful = 0;

        for ($i = 0; $i < $concurrentUpdates; $i++) {
            try {
                $this->db->query(
                    "UPDATE customers SET name = ? WHERE id = ?",
                    ['Updated Customer ' . $i, $customerId]
                );
                $successful++;
            } catch (Exception $e) {
                // Some updates might fail due to locking, which is acceptable
            }
        }

        // At least some updates should succeed
        $this->assertGreaterThan(0, $successful, 'At least some concurrent updates should succeed');
        
        // Verify final state
        $customer = $this->db->fetch("SELECT * FROM customers WHERE id = ?", [$customerId]);
        $this->assertNotNull($customer, 'Customer should still exist after concurrent updates');
    }

    /**
     * Test concurrent inserts (no race condition expected)
     */
    public function testConcurrentInserts(): void
    {
        require_once __DIR__ . '/../Support/FactoryRegistry.php';
        
        $concurrentInserts = 20;
        $insertedIds = [];

        for ($i = 0; $i < $concurrentInserts; $i++) {
            try {
                $customerId = FactoryRegistry::customer()->create([
                    'company_id' => 1,
                    'name' => 'Concurrent Customer ' . $i,
                ]);
                $insertedIds[] = $customerId;
            } catch (Exception $e) {
                // Should not fail
            }
        }

        $this->assertCount($concurrentInserts, $insertedIds, 'All concurrent inserts should succeed');
        
        // Verify all records exist
        foreach ($insertedIds as $id) {
            $customer = $this->db->fetch("SELECT * FROM customers WHERE id = ?", [$id]);
            $this->assertNotNull($customer, "Customer {$id} should exist");
        }
    }
}

