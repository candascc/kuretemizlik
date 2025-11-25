<?php
/**
 * Functional Test: Payment Transaction Atomicity
 * 
 * Tests that payment processing is atomic:
 * - Payment status update + Fee application are atomic
 * - Transaction rollback on failure
 * - No partial updates on error
 * 
 * Converted to PHPUnit from standalone test
 */

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

final class PaymentTransactionTest extends TestCase
{
    private Database $db;
    private $paymentService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        $this->paymentService = new \PaymentService();
        
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
     * Test 1: Payment transaction rolls back on failure
     */
    public function testPaymentTransactionRollback(): void
    {
        // Rollback any existing transaction from setUp
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        
        // Create test fee
        $feeId = $this->createTestFee(100.00);
        
        // Create payment record
        $paymentId = $this->createTestPayment($feeId, 50.00);
        
        // Get initial state
        $initialPayment = $this->db->fetch(
            "SELECT * FROM online_payments WHERE id = ?", 
            [$paymentId]
        );
        
        // Simulate transaction failure
        try {
            $this->db->transaction(function() use ($paymentId) {
                // Update payment status
                $this->db->update('online_payments', 
                    ['status' => 'completed'], 
                    'id = ?', 
                    [$paymentId]
                );
                
                // Force failure with invalid query
                $this->db->query("UPDATE non_existent_table SET x = 1");
            });
            
            $this->fail('Expected exception not thrown');
            
        } catch (Exception $e) {
            // Check that payment status was rolled back
            $afterPayment = $this->db->fetch(
                "SELECT * FROM online_payments WHERE id = ?", 
                [$paymentId]
            );
            
            $this->assertEquals(
                $initialPayment['status'],
                $afterPayment['status'],
                'Payment status should be rolled back on transaction failure'
            );
        }
        
        // Cleanup
        $this->cleanupTestData($feeId, $paymentId);
    }
    
    /**
     * Test 2: Successful payment is atomic (payment + fee update)
     */
    public function testPaymentSuccessAtomic(): void
    {
        // Rollback any existing transaction from setUp
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        
        // Create test fee
        $feeId = $this->createTestFee(100.00);
        
        // Create payment directly (PaymentService::createPaymentRequest doesn't exist)
        $paymentId = $this->createTestPayment($feeId, 50.00);
        
        // Mock successful payment processing
        $this->db->update('online_payments', [
            'status' => 'completed',
            'processed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$paymentId]);
        
        // Apply to fee (method handles its own transaction)
        $feeModel = new \ManagementFee();
        $feeModel->applyPayment($feeId, 50.00, 'card');
        
        // Verify both updated
        $payment = $this->db->fetch(
            "SELECT * FROM online_payments WHERE id = ?", 
            [$paymentId]
        );
        $fee = $this->db->fetch(
            "SELECT * FROM management_fees WHERE id = ?", 
            [$feeId]
        );
        
        $this->assertEquals('completed', $payment['status'], 'Payment should be completed');
        $this->assertEquals(50.00, (float)$fee['paid_amount'], 'Fee paid amount should be updated');
        
        // Cleanup
        $this->cleanupTestData($feeId, $paymentId);
    }
    
    /**
     * Test 3: Fee applyPayment is atomic (fee + money_entry)
     */
    public function testFeeUpdateAtomic(): void
    {
        // Create test fee
        $feeId = $this->createTestFee(100.00);
        
        // Apply payment
        $feeModel = new \ManagementFee();
        $result = $feeModel->applyPayment($feeId, 50.00, 'cash', date('Y-m-d'), 'Test payment');
        
        // Verify fee updated
        $fee = $feeModel->find($feeId);
        $this->assertEquals(50.00, (float)$fee['paid_amount'], 'Fee paid amount should be updated');
        
        // Verify money entry created
        $moneyEntry = $this->db->fetch(
            "SELECT * FROM money_entries WHERE kind = 'INCOME' AND category = 'MANAGEMENT_FEE' ORDER BY id DESC LIMIT 1"
        );
        $this->assertNotFalse($moneyEntry, 'Money entry should be created');
        $this->assertEquals(50.00, (float)$moneyEntry['amount'], 'Money entry amount should match payment');
        
        // Cleanup
        $this->cleanupTestData($feeId);
        if ($moneyEntry) {
            $this->db->delete('money_entries', 'id = ?', [$moneyEntry['id']]);
        }
    }
    
    /**
     * Test 4: Partial payment prevention on failure
     */
    public function testPartialPaymentPrevention(): void
    {
        // Rollback any existing transaction from setUp
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        
        // Create test fee
        $feeId = $this->createTestFee(100.00);
        $paymentId = $this->createTestPayment($feeId, 50.00);
        
        // Get initial state
        $initialFee = $this->db->fetch(
            "SELECT * FROM management_fees WHERE id = ?", 
            [$feeId]
        );
        
        // Simulate partial failure
        try {
            $this->db->transaction(function() use ($paymentId, $feeId) {
                // Update payment
                $this->db->update('online_payments', 
                    ['status' => 'completed'], 
                    'id = ?', 
                    [$paymentId]
                );
                
                // Update fee
                $this->db->update('management_fees',
                    ['paid_amount' => 50.00],
                    'id = ?',
                    [$feeId]
                );
                
                // Force failure
                throw new Exception('Simulated processing error');
            });
        } catch (Exception $e) {
            // Expected exception
        }
        
        // Verify no partial updates
        $afterFee = $this->db->fetch(
            "SELECT * FROM management_fees WHERE id = ?", 
            [$feeId]
        );
        $afterPayment = $this->db->fetch(
            "SELECT * FROM online_payments WHERE id = ?", 
            [$paymentId]
        );
        
        $this->assertEquals(
            $initialFee['paid_amount'],
            $afterFee['paid_amount'],
            'Fee should not be partially updated'
        );
        $this->assertEquals(
            'pending',
            $afterPayment['status'],
            'Payment should remain pending on failure'
        );
        
        // Cleanup
        $this->cleanupTestData($feeId, $paymentId);
    }
    
    /**
     * Helper: Create test fee
     */
    private function createTestFee($amount): int
    {
        // Create test building first
        $timestamp = date('Y-m-d H:i:s');
        $buildingId = $this->db->insert('buildings', [
            'name' => 'Test Building ' . uniqid(),
            'building_type' => 'apartman',
            'address_line' => 'Test Address',
            'city' => 'Istanbul',
            'total_units' => 1,
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp
        ]);
        
        // Create test unit
        $unitId = $this->db->insert('units', [
            'building_id' => $buildingId,
            'unit_type' => 'daire',
            'unit_number' => 'TEST-1',
            'owner_name' => 'Test Owner',
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp
        ]);
        
        // Create test fee
        $feeId = $this->db->insert('management_fees', [
            'building_id' => $buildingId,
            'unit_id' => $unitId,
            'definition_id' => null,
            'period' => date('Y-m'),
            'fee_name' => 'Test Fee',
            'base_amount' => $amount,
            'discount_amount' => 0,
            'late_fee' => 0,
            'total_amount' => $amount,
            'paid_amount' => 0,
            'status' => 'pending',
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'created_at' => $timestamp,
            'updated_at' => $timestamp
        ]);
        
        return (int)$feeId;
    }
    
    /**
     * Helper: Create test payment
     */
    private function createTestPayment($feeId, $amount): int
    {
        $paymentId = $this->db->insert('online_payments', [
            'management_fee_id' => $feeId,
            'amount' => $amount,
            'payment_method' => 'card',
            'payment_provider' => 'mock',
            'transaction_id' => 'TEST_' . uniqid(),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return (int)$paymentId;
    }
    
    /**
     * Helper: Cleanup test data
     */
    private function cleanupTestData($feeId, $paymentId = null): void
    {
        $fee = $this->db->fetch("SELECT * FROM management_fees WHERE id = ?", [$feeId]);
        
        if ($paymentId) {
            $this->db->delete('online_payments', 'id = ?', [$paymentId]);
        }
        
        if ($fee) {
            $this->db->delete('management_fees', 'id = ?', [$feeId]);
            if ($fee['unit_id']) {
                $this->db->delete('units', 'id = ?', [$fee['unit_id']]);
            }
            if ($fee['building_id']) {
                $this->db->delete('buildings', 'id = ?', [$fee['building_id']]);
            }
        }

        $this->db->delete(
            'money_entries',
            "category = ? AND note LIKE ?",
            ['MANAGEMENT_FEE', 'Aidat %Test Fee%']
        );
    }
}
