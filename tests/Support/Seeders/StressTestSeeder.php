<?php
namespace Tests\Support\Seeders;

use Tests\Support\DatabaseSeeder;
use Tests\Support\FactoryRegistry;

class StressTestSeeder extends DatabaseSeeder
{
    public function seed(): void
    {
        // Create a base company
        $companyId = FactoryRegistry::company()->create(['name' => 'Stress Test Co.']);
        $this->createdRecords['companies'][] = $companyId;

        // Create a large number of users for concurrent login tests
        for ($i = 0; $i < 500; $i++) {
            $userId = FactoryRegistry::user()->create(['company_id' => $companyId, 'username' => 'stress_user_' . $i]);
            $this->createdRecords['users'][] = $userId;
        }

        // Create a large number of customers and jobs for API stress
        for ($i = 0; $i < 2000; $i++) {
            $customerId = FactoryRegistry::customer()->create(['company_id' => $companyId]);
            $this->createdRecords['customers'][] = $customerId;
            $jobId = FactoryRegistry::job()->create(['customer_id' => $customerId, 'company_id' => $companyId]);
            $this->createdRecords['jobs'][] = $jobId;
        }

        // Create many buildings/units/residents for management portal stress
        for ($i = 0; $i < 100; $i++) {
            $customerId = $this->createdRecords['customers'][array_rand($this->createdRecords['customers'])];
            $buildingId = FactoryRegistry::building()->create(['customer_id' => $customerId]);
            $this->createdRecords['buildings'][] = $buildingId;
            
            for ($j = 0; $j < 10; $j++) { // 10 units per building
                $unitId = FactoryRegistry::unit()->create(['building_id' => $buildingId]);
                $this->createdRecords['units'][] = $unitId;
                $residentId = FactoryRegistry::residentUser()->create(['unit_id' => $unitId]);
                $this->createdRecords['residents'][] = $residentId;
            }
        }
    }
}
