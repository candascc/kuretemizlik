<?php
namespace Tests\Support\Factories;

use Tests\Support\TestFactory;
use Database;

class PaymentFactory extends TestFactory
{
    public function create(array $attributes = []): int
    {
        $faker = $this->faker();
        
        // If job_id not provided, create a job (which will create a customer if needed)
        if (!isset($attributes['job_id'])) {
            $jobFactory = JobFactory::getInstance($this->db);
            $attributes['job_id'] = $jobFactory->create();
        }
        
        $data = array_merge([
            'job_id' => $attributes['job_id'],
            'amount' => $faker->randomFloat(2, 100, 5000),
            'paid_at' => date('Y-m-d H:i:s'),
            'note' => $faker->sentence(),
            'finance_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $attributes);

        return $this->db->insert('job_payments', $data);
    }
}
