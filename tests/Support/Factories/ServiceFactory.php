<?php
namespace Tests\Support\Factories;

use Tests\Support\TestFactory;
use Database;

class ServiceFactory extends TestFactory
{
    public function create(array $attributes = []): int
    {
        $faker = $this->faker();
        
        $serviceNames = [
            'Ev Temizliği',
            'Ofis Temizliği',
            'Cam Temizliği',
            'Halı Yıkama',
            'Balkon Temizliği',
            'Bina Dış Cephe Temizliği',
            'Buharlı Temizlik',
            'Derinlemesine Temizlik',
        ];
        
        $data = array_merge([
            'name' => $faker->randomElement($serviceNames),
            'company_id' => 1,
            'duration_min' => $faker->numberBetween(60, 240),
            'default_fee' => $faker->randomFloat(2, 50, 500),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ], $attributes);

        return $this->db->insert('services', $data);
    }
}
