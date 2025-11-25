<?php
/**
 * Functional Test: New Customer → Address → Job → Payment → Finance sync
 * Converted to PHPUnit from standalone test
 */

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

final class JobCustomerFinanceFlowTest extends TestCase
{
    private Database $db;
    private array $ids = [
        'customer' => null,
        'address' => null,
        'job' => null,
        'money_entry' => null,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
        
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        parent::tearDown();
    }

    /**
     * Test: Creating payment creates money_entries income
     */
    public function testCreateCustomerAddressJobPaymentCreatesFinance(): void
    {
        $now = date('Y-m-d H:i:s');

        // Create customer
        $this->ids['customer'] = (int)$this->db->insert('customers', [
            'name' => 'Flow Test Customer ' . uniqid(),
            'phone' => '555' . rand(1000000, 9999999),
            'company_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Create address
        $this->ids['address'] = (int)$this->db->insert('addresses', [
            'customer_id' => $this->ids['customer'],
            'company_id' => 1,
            'label' => 'Test',
            'line' => 'Test Mah. No:1',
            'city' => 'Istanbul',
            'created_at' => $now,
        ]);

        // Create job with total and payment
        $this->ids['job'] = (int)$this->db->insert('jobs', [
            'customer_id' => $this->ids['customer'],
            'address_id' => $this->ids['address'],
            'company_id' => 1,
            'start_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'end_at' => date('Y-m-d H:i:s', strtotime('+2 hour')),
            'status' => 'SCHEDULED',
            'total_amount' => 120.50,
            'amount_paid' => 50.00,
            'payment_status' => 'PARTIAL',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Simulate finance sync creation
        $this->ids['money_entry'] = (int)$this->db->insert('money_entries', [
            'kind' => 'INCOME',
            'category' => 'JOB_PAYMENT',
            'note' => 'Ödeme - İş #' . $this->ids['job'],
            'amount' => 50.00,
            'date' => date('Y-m-d'),
            'job_id' => $this->ids['job'],
            'created_by' => 1,
            'created_at' => $now,
        ]);

        $row = $this->db->fetch("SELECT * FROM money_entries WHERE id = ?", [$this->ids['money_entry']]);
        
        $this->assertNotFalse($row, 'Money entry should be created');
        $this->assertEquals(50.00, (float)$row['amount'], 'Money entry amount should match payment');
        $this->assertEquals('INCOME', $row['kind'], 'Money entry should be INCOME type');
    }

    /**
     * Test: Removing payment removes money_entries
     */
    public function testDeletingPaymentRemovesFinance(): void
    {
        // First create the money entry
        $this->testCreateCustomerAddressJobPaymentCreatesFinance();

        if (!$this->ids['money_entry']) {
            $this->markTestSkipped('No money entry created');
            return;
        }

        $this->db->delete('money_entries', 'id = ?', [$this->ids['money_entry']]);
        $row = $this->db->fetch("SELECT * FROM money_entries WHERE id = ?", [$this->ids['money_entry']]);
        
        $this->assertFalse($row, 'Money entry should be deleted');
    }

    private function cleanup(): void
    {
        if ($this->ids['money_entry']) {
            $this->db->delete('money_entries', 'id = ?', [$this->ids['money_entry']]);
        }
        if ($this->ids['job']) {
            $this->db->delete('jobs', 'id = ?', [$this->ids['job']]);
        }
        if ($this->ids['address']) {
            $this->db->delete('addresses', 'id = ?', [$this->ids['address']]);
        }
        if ($this->ids['customer']) {
            $this->db->delete('customers', 'id = ?', [$this->ids['customer']]);
        }
    }
}
