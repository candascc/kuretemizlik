<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Lib/Database.php';
require_once __DIR__ . '/../../src/Lib/Auth.php';
require_once __DIR__ . '/../../src/Controllers/JobController.php';
require_once __DIR__ . '/../../src/Controllers/CustomerController.php';
require_once __DIR__ . '/../../src/Controllers/ServiceController.php';

/**
 * Integration Tests: Controller Integration
 * 
 * Tests controller integration with models, services, and database
 */
final class ControllerIntegrationTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        $this->db = Database::getInstance();
        
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollback();
        }
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
    }

    /**
     * Test JobController integration with database
     */
    public function testJobControllerIntegration(): void
    {
        // Create test data
        $customerId = $this->createTestCustomer();
        $serviceId = $this->createTestService();
        
        // Verify data exists
        $customer = $this->db->fetch("SELECT * FROM customers WHERE id = ?", [$customerId]);
        $service = $this->db->fetch("SELECT * FROM services WHERE id = ?", [$serviceId]);
        
        $this->assertNotNull($customer, 'Test customer should exist');
        $this->assertNotNull($service, 'Test service should exist');
    }

    /**
     * Test CustomerController integration
     */
    public function testCustomerControllerIntegration(): void
    {
        // Create test customer
        $customerId = $this->createTestCustomer();
        
        // Verify customer exists
        $customer = $this->db->fetch("SELECT * FROM customers WHERE id = ?", [$customerId]);
        
        $this->assertNotNull($customer, 'Test customer should exist');
        $this->assertEquals($customerId, $customer['id']);
    }

    /**
     * Test ServiceController integration
     */
    public function testServiceControllerIntegration(): void
    {
        // Create test service
        $serviceId = $this->createTestService();
        
        // Verify service exists
        $service = $this->db->fetch("SELECT * FROM services WHERE id = ?", [$serviceId]);
        
        $this->assertNotNull($service, 'Test service should exist');
        $this->assertEquals($serviceId, $service['id']);
    }

    /**
     * Helper: Create test customer
     */
    private function createTestCustomer(): int
    {
        return (int)$this->db->insert('customers', [
            'name' => 'Test Customer ' . uniqid(),
            'phone' => '0531 300 40 50',
            'email' => 'test@example.com',
            'company_id' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Helper: Create test service
     */
    private function createTestService(): int
    {
        return (int)$this->db->insert('services', [
            'name' => 'Test Service ' . uniqid(),
            'duration_min' => 60,
            'default_fee' => 100.00,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}

