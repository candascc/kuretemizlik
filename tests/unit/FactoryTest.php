<?php
/**
 * Factory Test
 * Tests all factories to ensure they work correctly
 */

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;
use Tests\Support\FactoryRegistry;

class FactoryTest extends TestCase
{
    private Database $db;

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
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        parent::tearDown();
    }

    public function testUserFactory(): void
    {
        $userId = FactoryRegistry::user()->create(['role' => 'ADMIN']);
        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);
        
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        $this->assertNotNull($user);
        $this->assertEquals('ADMIN', $user['role']);
    }

    public function testCustomerFactory(): void
    {
        $customerId = FactoryRegistry::customer()->create(['company_id' => 1]);
        $this->assertIsInt($customerId);
        
        $customer = $this->db->fetch("SELECT * FROM customers WHERE id = ?", [$customerId]);
        $this->assertNotNull($customer);
        $this->assertEquals(1, $customer['company_id']);
    }

    public function testJobFactory(): void
    {
        $jobId = FactoryRegistry::job()->create(['company_id' => 1]);
        $this->assertIsInt($jobId);
        
        $job = $this->db->fetch("SELECT * FROM jobs WHERE id = ?", [$jobId]);
        $this->assertNotNull($job);
        $this->assertNotNull($job['customer_id']); // Factory should create customer automatically
    }

    public function testBuildingFactory(): void
    {
        $buildingId = FactoryRegistry::building()->create();
        $this->assertIsInt($buildingId);
        
        $building = $this->db->fetch("SELECT * FROM buildings WHERE id = ?", [$buildingId]);
        $this->assertNotNull($building);
    }

    public function testUnitFactory(): void
    {
        $unitId = FactoryRegistry::unit()->create();
        $this->assertIsInt($unitId);
        
        $unit = $this->db->fetch("SELECT * FROM units WHERE id = ?", [$unitId]);
        $this->assertNotNull($unit);
        $this->assertNotNull($unit['building_id']); // Factory should create building automatically
    }

    public function testResidentUserFactory(): void
    {
        $residentId = FactoryRegistry::residentUser()->create();
        $this->assertIsInt($residentId);
        
        $resident = $this->db->fetch("SELECT * FROM resident_users WHERE id = ?", [$residentId]);
        $this->assertNotNull($resident);
        $this->assertNotNull($resident['unit_id']); // Factory should create unit/building automatically
    }

    public function testCompanyFactory(): void
    {
        $companyId = FactoryRegistry::company()->create();
        $this->assertIsInt($companyId);
        
        $company = $this->db->fetch("SELECT * FROM companies WHERE id = ?", [$companyId]);
        $this->assertNotNull($company);
    }

    public function testPaymentFactory(): void
    {
        $paymentId = FactoryRegistry::payment()->create();
        $this->assertIsInt($paymentId);
        
        $payment = $this->db->fetch("SELECT * FROM job_payments WHERE id = ?", [$paymentId]);
        $this->assertNotNull($payment);
        $this->assertNotNull($payment['job_id']); // Factory should create job automatically
    }

    public function testContractFactory(): void
    {
        $contractId = FactoryRegistry::contract()->create();
        $this->assertIsInt($contractId);
        
        $contract = $this->db->fetch("SELECT * FROM contracts WHERE id = ?", [$contractId]);
        $this->assertNotNull($contract);
        $this->assertNotNull($contract['customer_id']); // Factory should create customer automatically
    }

    public function testServiceFactory(): void
    {
        $serviceId = FactoryRegistry::service()->create();
        $this->assertIsInt($serviceId);
        
        $service = $this->db->fetch("SELECT * FROM services WHERE id = ?", [$serviceId]);
        $this->assertNotNull($service);
    }

    public function testAddressFactory(): void
    {
        $addressId = FactoryRegistry::address()->create();
        $this->assertIsInt($addressId);
        
        $address = $this->db->fetch("SELECT * FROM addresses WHERE id = ?", [$addressId]);
        $this->assertNotNull($address);
        $this->assertNotNull($address['customer_id']); // Factory should create customer automatically
    }

    public function testFactoryCreateMany(): void
    {
        $customerIds = FactoryRegistry::customer()->createMany(10, ['company_id' => 1]);
        $this->assertCount(10, $customerIds);
        
        foreach ($customerIds as $id) {
            $this->assertIsInt($id);
            $customer = $this->db->fetch("SELECT * FROM customers WHERE id = ?", [$id]);
            $this->assertNotNull($customer);
        }
    }
}

