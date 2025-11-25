<?php
namespace Tests\Support;

use Database;
use Tests\Support\FactoryRegistry;

abstract class DatabaseSeeder
{
    protected Database $db;
    protected array $createdRecords = [];
    protected bool $useTransaction;

    public function __construct(bool $useTransaction = true)
    {
        $this->db = Database::getInstance();
        $this->useTransaction = $useTransaction;
        FactoryRegistry::setDatabase($this->db);
        
        if ($this->useTransaction) {
            $this->db->beginTransaction();
        }
    }

    abstract public function seed(): void;

    public function truncate(array $tables): void
    {
        foreach ($tables as $table) {
            $this->db->query("DELETE FROM {$table}");
            $this->db->query("DELETE FROM sqlite_sequence WHERE name='{$table}'"); // Reset auto-increment
        }
    }

    /**
     * Seed basic test data
     */
    public function seedBasic(): void
    {
        $companyId = FactoryRegistry::company()->create();
        $this->createdRecords['companies'][] = $companyId;

        for ($i = 0; $i < 10; $i++) {
            $userId = FactoryRegistry::user()->create(['company_id' => $companyId]);
            $this->createdRecords['users'][] = $userId;
        }

        for ($i = 0; $i < 50; $i++) {
            $customerId = FactoryRegistry::customer()->create(['company_id' => $companyId]);
            $this->createdRecords['customers'][] = $customerId;
        }

        for ($i = 0; $i < 100; $i++) {
            $customerId = $this->createdRecords['customers'][array_rand($this->createdRecords['customers'])];
            $jobId = FactoryRegistry::job()->create(['customer_id' => $customerId, 'company_id' => $companyId]);
            $this->createdRecords['jobs'][] = $jobId;
        }
    }

    /**
     * Seed large dataset
     */
    public function seedLarge(int $users = 100, int $customers = 500, int $buildings = 50, int $jobs = 1000): void
    {
        $companyId = FactoryRegistry::company()->create();
        $this->createdRecords['companies'][] = $companyId;

        // Create users
        for ($i = 0; $i < $users; $i++) {
            $userId = FactoryRegistry::user()->create(['company_id' => $companyId]);
            $this->createdRecords['users'][] = $userId;
        }

        // Create customers
        for ($i = 0; $i < $customers; $i++) {
            $customerId = FactoryRegistry::customer()->create(['company_id' => $companyId]);
            $this->createdRecords['customers'][] = $customerId;
        }

        // Create buildings
        for ($i = 0; $i < $buildings; $i++) {
            $customerId = $this->createdRecords['customers'][array_rand($this->createdRecords['customers'])];
            $buildingId = FactoryRegistry::building()->create(['customer_id' => $customerId]);
            $this->createdRecords['buildings'][] = $buildingId;
        }

        // Create jobs
        for ($i = 0; $i < $jobs; $i++) {
            $customerId = $this->createdRecords['customers'][array_rand($this->createdRecords['customers'])];
            $jobId = FactoryRegistry::job()->create(['customer_id' => $customerId, 'company_id' => $companyId]);
            $this->createdRecords['jobs'][] = $jobId;
        }
    }

    /**
     * Get created records
     */
    public function getCreatedRecords(): array
    {
        return $this->createdRecords;
    }

    /**
     * Cleanup (rollback transaction if used)
     */
    public function cleanup(): void
    {
        if ($this->useTransaction && $this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
