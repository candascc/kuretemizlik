<?php
namespace Tests\Support\Factories;

use Tests\Support\TestFactory;
use Database;

class UnitFactory extends TestFactory
{
    public function create(array $attributes = []): int
    {
        $faker = $this->faker();
        
        // If building_id not provided, create a building
        if (!isset($attributes['building_id'])) {
            $buildingFactory = BuildingFactory::getInstance($this->db);
            $attributes['building_id'] = $buildingFactory->create();
        }
        
        $data = array_merge([
            'building_id' => $attributes['building_id'],
            'unit_type' => $faker->randomElement(['daire', 'dubleks', 'ofis', 'dukkán', 'depo']),
            'unit_number' => $faker->randomElement(['A', 'B', 'C', 'D']) . $faker->numberBetween(1, 99),
            'owner_type' => $faker->randomElement(['owner', 'tenant', 'empty', 'company']),
            'owner_name' => $faker->name(),
            'monthly_fee' => $faker->randomFloat(2, 500, 5000),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $attributes);

        return $this->db->insert('units', $data);
    }
}
