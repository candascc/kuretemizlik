<?php
namespace Tests\Support\Factories;

use Tests\Support\TestFactory;
use Database;

class AddressFactory extends TestFactory
{
    public function create(array $attributes = []): int
    {
        $faker = $this->faker();
        
        // If customer_id not provided, create a customer
        if (!isset($attributes['customer_id'])) {
            $customerFactory = CustomerFactory::getInstance($this->db);
            $attributes['customer_id'] = $customerFactory->create();
        }
        
        $data = array_merge([
            'customer_id' => $attributes['customer_id'],
            'company_id' => 1,
            'label' => $faker->word() . ' Address',
            'line' => $faker->streetAddress(),
            'city' => $faker->city(),
            'created_at' => date('Y-m-d H:i:s'),
        ], $attributes);

        return $this->db->insert('addresses', $data);
    }
}
