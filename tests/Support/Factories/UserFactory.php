<?php
namespace Tests\Support\Factories;

use Tests\Support\TestFactory;
use Database;

class UserFactory extends TestFactory
{
    public function create(array $attributes = []): int
    {
        $faker = $this->faker();
        $data = array_merge([
            'username' => $faker->userName() . '_' . uniqid(),
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'role' => 'ADMIN',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'company_id' => 1, // Default company
        ], $attributes);

        return $this->db->insert('users', $data);
    }
}
