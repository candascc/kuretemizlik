<?php
namespace Tests\Support\Factories;

use Tests\Support\TestFactory;
use Database;

class CompanyFactory extends TestFactory
{
    public function create(array $attributes = []): int
    {
        $faker = $this->faker();
        $companyName = $faker->company();
        $subdomain = strtolower(preg_replace('/[^a-z0-9]/', '', $companyName)) . '_' . uniqid();
        
        $data = array_merge([
            'name' => $companyName,
            'subdomain' => $subdomain,
            'owner_name' => $faker->name(),
            'owner_email' => $faker->safeEmail(),
            'owner_phone' => $faker->phoneNumber(),
            'address' => $faker->address(),
            'tax_number' => $faker->numerify('###########'),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $attributes);

        return $this->db->insert('companies', $data);
    }
}
