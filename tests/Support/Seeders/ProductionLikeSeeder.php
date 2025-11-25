<?php
namespace Tests\Support\Seeders;

use Tests\Support\DatabaseSeeder;
use Tests\Support\FactoryRegistry;

class ProductionLikeSeeder extends DatabaseSeeder
{
    public function seed(): void
    {
        // Create main company
        $companyId = FactoryRegistry::company()->create([
            'name' => 'Production Test Company',
            'is_active' => 1,
        ]);
        $this->createdRecords['companies'][] = $companyId;

        // Create admin users
        for ($i = 0; $i < 5; $i++) {
            $userId = FactoryRegistry::user()->create([
                'company_id' => $companyId,
                'role' => 'ADMIN',
            ]);
            $this->createdRecords['users'][] = $userId;
        }

        // Create operators
        for ($i = 0; $i < 20; $i++) {
            $userId = FactoryRegistry::user()->create([
                'company_id' => $companyId,
                'role' => 'OPERATOR',
            ]);
            $this->createdRecords['users'][] = $userId;
        }

        // Create customers with realistic distribution
        for ($i = 0; $i < 200; $i++) {
            $customerId = FactoryRegistry::customer()->create(['company_id' => $companyId]);
            $this->createdRecords['customers'][] = $customerId;

            // Some customers have addresses
            if ($i % 3 === 0) {
                $addressId = FactoryRegistry::address()->create([
                    'customer_id' => $customerId,
                    'company_id' => $companyId,
                ]);
                $this->createdRecords['addresses'][] = $addressId;
            }
        }

        // Create services
        $services = ['Temizlik', 'Bakım', 'Onarım', 'Boyama'];
        foreach ($services as $serviceName) {
            $serviceId = FactoryRegistry::service()->create([
                'name' => $serviceName,
                'company_id' => $companyId,
            ]);
            $this->createdRecords['services'][] = $serviceId;
        }

        // Create jobs with various statuses
        $statuses = ['SCHEDULED', 'IN_PROGRESS', 'DONE', 'CANCELLED'];
        $paymentStatuses = ['UNPAID', 'PARTIAL', 'PAID'];
        
        for ($i = 0; $i < 500; $i++) {
            $customerId = $this->createdRecords['customers'][array_rand($this->createdRecords['customers'])];
            $serviceId = $this->createdRecords['services'][array_rand($this->createdRecords['services'])] ?? null;
            
            $jobId = FactoryRegistry::job()->create([
                'customer_id' => $customerId,
                'company_id' => $companyId,
                'service_id' => $serviceId,
                'status' => $statuses[array_rand($statuses)],
                'payment_status' => $paymentStatuses[array_rand($paymentStatuses)],
            ]);
            $this->createdRecords['jobs'][] = $jobId;

            // Some jobs have payments
            if ($i % 5 === 0) {
                $paymentId = FactoryRegistry::payment()->create(['job_id' => $jobId]);
                $this->createdRecords['payments'][] = $paymentId;
            }
        }

        // Create buildings and units
        for ($i = 0; $i < 30; $i++) {
            $customerId = $this->createdRecords['customers'][array_rand($this->createdRecords['customers'])];
            $buildingId = FactoryRegistry::building()->create(['customer_id' => $customerId]);
            $this->createdRecords['buildings'][] = $buildingId;

            // Each building has multiple units
            $unitsPerBuilding = rand(5, 20);
            for ($j = 0; $j < $unitsPerBuilding; $j++) {
                $unitId = FactoryRegistry::unit()->create(['building_id' => $buildingId]);
                $this->createdRecords['units'][] = $unitId;

                // Some units have resident users
                if ($j % 2 === 0) {
                    $residentId = FactoryRegistry::residentUser()->create(['unit_id' => $unitId]);
                    $this->createdRecords['residents'][] = $residentId;
                }
            }
        }
    }
}

