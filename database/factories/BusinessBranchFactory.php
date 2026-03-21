<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BusinessBranch;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BusinessBranch>
 */
final class BusinessBranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'correlation_id' => (string) Str::uuid(),
            'name' => fake()->company(),
            'inn' => fake()->numerify('##########'),
            'kpp' => fake()->numerify('######'),
            'ogrn' => fake()->numerify('##############'),
            'legal_address' => fake()->address(),
            'actual_address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->email(),
            'status' => 'active',
            'is_verified' => false,
            'meta' => [
                'timezone' => 'Europe/Moscow',
                'locale' => 'ru',
            ],
            'tags' => ['branch:active', 'source:factory'],
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
