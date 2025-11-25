<?php
namespace Tests\Support\Factories;

use Tests\Support\TestFactory;
use Database;

class ResidentUserFactory extends TestFactory
{
    public function create(array $attributes = []): int
    {
        $faker = $this->faker();
        
        // If unit_id not provided, create a unit (which will create a building if needed)
        if (!isset($attributes['unit_id'])) {
            $unitFactory = UnitFactory::getInstance($this->db);
            $attributes['unit_id'] = $unitFactory->create();
        }
        
        $data = array_merge([
            'unit_id' => $attributes['unit_id'],
            'name' => $faker->name(),
            'email' => $faker->unique()->safeEmail(),
            'phone' => $faker->phoneNumber(),
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'is_owner' => $faker->boolean(),
            'is_active' => 1,
            'email_verified' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $attributes);

        return $this->db->insert('resident_users', $data);
    }
}
