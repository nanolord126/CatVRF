<?php

namespace Database\Factories;

use App\Models\Domains\Insurance\InsurancePolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsurancePolicyFactory extends Factory
{
    protected $model = InsurancePolicy::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'policyholder_id' => User::factory(),
            'policy_number' => $this->faker->unique()->bothify('POL-####'),
            'type' => $this->faker->randomElement(['health', 'auto', 'home', 'life']),
            'status' => $this->faker->randomElement(['active', 'expired', 'cancelled']),
            'premium_amount' => $this->faker->numberBetween(100, 5000),
            'coverage_amount' => $this->faker->numberBetween(10000, 1000000),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->dateTimeBetween('+1 days', '+365 days'),
        ];
    }
}
