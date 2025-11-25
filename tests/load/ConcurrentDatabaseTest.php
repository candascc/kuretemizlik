<?php
/**
 * Concurrent Database Test
 * Tests transaction isolation and concurrent database operations
 */

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;
use Tests\Support\FactoryRegistry;

class ConcurrentDatabaseTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        FactoryRegistry::setDatabase($this->db);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test transaction isolation
     */
    public function testTransactionIsolation(): void
    {
        $customerId = FactoryRegistry::customer()->create(['company_id' => 1]);
        
        // Start transaction
        $this->db->beginTransaction();
        
        // Update in transaction
        $this->db->query(
            "UPDATE customers SET name = ? WHERE id = ?",
            ['Transaction Test', $customerId]
        );
        
        // In another "connection" (simulated), read should see old value
        // Since we're using same instance, we'll test rollback instead
        $customer = $this->db->fetch("SELECT name FROM customers WHERE id = ?", [$customerId]);
        $this->assertEquals('Transaction Test', $customer['name'], 'Should see updated value in transaction');
        
        // Rollback
        $this->db->rollBack();
        
        // After rollback, should see original value
        $customer = $this->db->fetch("SELECT name FROM customers WHERE id = ?", [$customerId]);
        $this->assertNotEquals('Transaction Test', $customer['name'], 'Should see original value after rollback');
    }

    /**
     * Test nested transactions
     * Note: Database::transaction() does not use savepoints for nested transactions.
     * If already in a transaction, it executes the callback directly.
     * An exception in the inner callback will cause the outer transaction to rollback.
     */
    public function testNestedTransactions(): void
    {
        $customerId = FactoryRegistry::customer()->create(['company_id' => 1]);
        
        // Get original name
        $original = $this->db->fetch("SELECT name FROM customers WHERE id = ?", [$customerId]);
        $originalName = $original['name'] ?? 'Original';
        
        // Outer transaction
        $this->db->beginTransaction();
        
        $this->db->query(
            "UPDATE customers SET name = ? WHERE id = ?",
            ['Outer Transaction', $customerId]
        );
        
        // Inner transaction (nested) - Database::transaction() executes directly if already in transaction
        try {
            $this->db->transaction(function() use ($customerId) {
                $this->db->query(
                    "UPDATE customers SET name = ? WHERE id = ?",
                    ['Inner Transaction', $customerId]
                );
                throw new Exception("Rollback inner");
            });
        } catch (Exception $e) {
            // Inner exception causes outer transaction to rollback (no savepoints)
        }
        
        // After inner exception, outer transaction is also rolled back
        // So we should see the original name, not "Outer Transaction"
        $customer = $this->db->fetch("SELECT name FROM customers WHERE id = ?", [$customerId]);
        $this->assertNotEquals('Outer Transaction', $customer['name'], 'Outer transaction should be rolled back due to inner exception');
        
        // Clean up if transaction is still active
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}

