<?php
namespace Tests\Support\Factories;

use Tests\Support\TestFactory;
use Database;

class JobFactory extends TestFactory
{
    public function create(array $attributes = []): int
    {
        $faker = $this->faker();
        $data = array_merge([
            'customer_id' => CustomerFactory::getInstance($this->db)->create(),
            'company_id' => 1,
            'service_id' => null,
            'address_id' => null,
            'start_at' => $faker->dateTimeBetween('+1 day', '+1 week')->format('Y-m-d H:i:s'),
            'end_at' => $faker->dateTimeBetween('+1 day', '+1 week')->format('Y-m-d H:i:s'),
            'status' => 'SCHEDULED',
            'total_amount' => $faker->randomFloat(2, 50, 500),
            'amount_paid' => 0,
            'payment_status' => 'UNPAID',
            'assigned_to' => null,
            'note' => $faker->sentence(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $attributes);

        return $this->db->insert('jobs', $data);
    }
}
