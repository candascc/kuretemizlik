<?php
namespace Tests\Support\Factories;

use Tests\Support\TestFactory;
use Database;

class ContractFactory extends TestFactory
{
    public function create(array $attributes = []): int
    {
        $faker = $this->faker();
        
        // If customer_id not provided, create a customer
        if (!isset($attributes['customer_id'])) {
            $customerFactory = CustomerFactory::getInstance($this->db);
            $attributes['customer_id'] = $customerFactory->create();
        }
        
        $startDate = $faker->dateTimeBetween('-1 year', 'now');
        $endDate = $faker->dateTimeBetween('now', '+1 year');
        
        $data = array_merge([
            'customer_id' => $attributes['customer_id'],
            'contract_number' => $faker->bothify('CON-######'),
            'title' => $faker->sentence(3),
            'description' => $faker->paragraph(),
            'contract_type' => $faker->randomElement(['CLEANING', 'MAINTENANCE', 'RECURRING', 'ONE_TIME']),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_amount' => $faker->randomFloat(2, 1000, 10000),
            'payment_terms' => $faker->sentence(),
            'status' => 'ACTIVE',
            'created_by' => UserFactory::getInstance($this->db)->create(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $attributes);

        return $this->db->insert('contracts', $data);
    }
}
