<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
final class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'uuid' => Str::uuid(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => fake()->phoneNumber(),
            'phone_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'correlation_id' => (string) Str::uuid(),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'meta' => [
                'locale' => 'ru',
                'timezone' => 'Europe/Moscow',
            ],
            'tags' => ['user:test', 'source:factory'],
            'last_login_at' => now(),
            'last_activity_at' => now(),
            'is_active' => true,
            'is_admin' => false,
            'remember_token' => Str::random(10),
            'category_preference' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Mark user as admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
            'tags' => ['user:admin', 'source:factory'],
        ]);
    }

    /**
     * Mark user as business owner.
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true, // Owners are admins of their tenant
            'tags' => ['user:owner', 'source:factory'],
        ]);
    }

    /**
     * Mark user as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'tags' => ['user:inactive', 'source:factory'],
        ]);
    }
}
