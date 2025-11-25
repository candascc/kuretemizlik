<?php
namespace Tests\Support\Factories;

use Tests\Support\TestFactory;
use Database;

class CustomerFactory extends TestFactory
{
    public function create(array $attributes = []): int
    {
        $faker = $this->faker();
        $data = array_merge([
            'name' => $faker->name(),
            'phone' => $faker->phoneNumber(),
            'email' => $faker->unique()->safeEmail(),
            'company_id' => 1,
            'notes' => $faker->sentence(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $attributes);

        return $this->db->insert('customers', $data);
    }
}
