<?php
namespace Tests\Support\Seeders;

use Tests\Support\DatabaseSeeder;
use Tests\Support\FactoryRegistry;

class LargeDatasetSeeder extends DatabaseSeeder
{
    public function seed(): void
    {
        // Create a base company
        $companyId = FactoryRegistry::company()->create(['name' => 'Large Data Co.']);
        $this->createdRecords['companies'][] = $companyId;

        // Create 100 users
        for ($i = 0; $i < 100; $i++) {
            $userId = FactoryRegistry::user()->create(['company_id' => $companyId]);
            $this->createdRecords['users'][] = $userId;
        }

        // Create 500 customers
        for ($i = 0; $i < 500; $i++) {
            $customerId = FactoryRegistry::customer()->create(['company_id' => $companyId]);
            $this->createdRecords['customers'][] = $customerId;
        }

        // Create 1000 jobs
        for ($i = 0; $i < 1000; $i++) {
            $customerId = $this->createdRecords['customers'][array_rand($this->createdRecords['customers'])];
            $jobId = FactoryRegistry::job()->create(['customer_id' => $customerId, 'company_id' => $companyId]);
            $this->createdRecords['jobs'][] = $jobId;
        }

        // Create 50 buildings
        for ($i = 0; $i < 50; $i++) {
            $customerId = $this->createdRecords['customers'][array_rand($this->createdRecords['customers'])];
            $buildingId = FactoryRegistry::building()->create(['customer_id' => $customerId]);
            $this->createdRecords['buildings'][] = $buildingId;
        }

        // Create 200 units
        for ($i = 0; $i < 200; $i++) {
            $buildingId = $this->createdRecords['buildings'][array_rand($this->createdRecords['buildings'])];
            $unitId = FactoryRegistry::unit()->create(['building_id' => $buildingId]);
            $this->createdRecords['units'][] = $unitId;
        }

        // Create 300 resident users
        for ($i = 0; $i < 300; $i++) {
            $unitId = $this->createdRecords['units'][array_rand($this->createdRecords['units'])];
            $residentId = FactoryRegistry::residentUser()->create(['unit_id' => $unitId]);
            $this->createdRecords['residents'][] = $residentId;
        }
    }
}
