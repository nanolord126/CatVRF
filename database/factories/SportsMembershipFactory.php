<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Domains\Sports\SportsMembership;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

final class SportsMembershipFactory extends Factory
{
    protected $model = SportsMembership::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'athlete_id' => User::factory(),
            'tier' => fake()->randomElement(['bronze', 'silver', 'gold', 'platinum']),
            'status' => 'active',
            'expires_at' => Carbon::now()->addYear(),
            'monthly_fee' => fake()->numberBetween(100000, 500000),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
