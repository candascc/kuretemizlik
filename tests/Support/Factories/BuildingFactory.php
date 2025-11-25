<?php
namespace Tests\Support\Factories;

use Tests\Support\TestFactory;
use Database;

class BuildingFactory extends TestFactory
{
    public function create(array $attributes = []): int
    {
        $faker = $this->faker();
        $data = array_merge([
            'name' => $faker->company() . ' ' . $faker->randomElement(['Apartmanı', 'Sitesi', 'Plaza', 'Rezidansı']),
            'building_type' => $faker->randomElement(['apartman', 'site', 'plaza', 'rezidans']),
            'customer_id' => null,
            'address_line' => $faker->address(),
            'district' => $faker->city(),
            'city' => $faker->randomElement(['İstanbul', 'Ankara', 'İzmir', 'Bursa', 'Antalya']),
            'postal_code' => $faker->postcode(),
            'total_floors' => $faker->numberBetween(3, 20),
            'total_units' => $faker->numberBetween(10, 100),
            'construction_year' => $faker->numberBetween(1980, 2024),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $attributes);

        return $this->db->insert('buildings', $data);
    }
}
