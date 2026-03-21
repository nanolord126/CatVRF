<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'uuid' => (string) Str::uuid(),
            'correlation_id' => (string) Str::uuid(),
            'name' => fake()->company(),
            'type' => fake()->randomElement(['hotel', 'beauty', 'food', 'auto', 'real_estate']),
            'slug' => fake()->unique()->slug(),
            'inn' => fake()->numerify('##########'),
            'kpp' => fake()->numerify('######'),
            'ogrn' => fake()->numerify('##############'),
            'legal_entity_type' => fake()->randomElement(['ip', 'ooo', 'ao', 'zao']),
            'legal_address' => fake()->address(),
            'actual_address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'website' => fake()->url(),
            'is_active' => true,
            'is_verified' => false,
            'meta' => [
                'timezone' => 'Europe/Moscow',
                'locale' => 'ru',
            ],
            'tags' => ['tenant:test', 'source:factory'],
        ];
    }

    /**
     * Неверифицированный тенант.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
        ]);
    }

    /**
     * Верифицированный тенант.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    /**
     * Неактивный тенант.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
