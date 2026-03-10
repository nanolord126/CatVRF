<?php

namespace Database\Factories;

use App\Models\Domains\Sports\SportsMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SportsMembershipFactory extends Factory
{
    protected $model = SportsMembership::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'athlete_id' => User::factory(),
            'tier' => $this->faker->randomElement(['bronze', 'silver', 'gold', 'platinum']),
            'status' => $this->faker->randomElement(['active', 'suspended', 'expired']),
            'expires_at' => $this->faker->dateTimeBetween('now', '+365 days'),
            'monthly_fee' => $this->faker->numberBetween(100, 5000),
        ];
    }
}
