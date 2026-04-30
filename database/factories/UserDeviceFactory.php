<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

final class UserDeviceFactory extends Factory
{
    protected $model = UserDevice::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'user_id' => User::factory(),
            'tenant_id' => null,
            'device_name' => fake()->randomElement(['iPhone 15', 'MacBook Pro', 'Windows PC', 'Android Phone']),
            'device_type' => fake()->randomElement([UserDevice::DEVICE_TYPE_MOBILE, UserDevice::DEVICE_TYPE_TABLET, UserDevice::DEVICE_TYPE_DESKTOP]),
            'fingerprint' => fake()->md5(),
            'user_agent' => fake()->userAgent,
            'ip_address' => fake()->ipv4,
            'platform' => fake()->randomElement(['Windows', 'macOS', 'Linux', 'Android', 'iOS']),
            'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'is_revoked' => false,
            'is_trusted' => fake()->boolean(30),
            'is_current' => fake()->boolean(20),
            'first_seen_at' => now()->subDays(fake()->numberBetween(1, 365)),
            'last_seen_at' => now()->subDays(fake()->numberBetween(0, 30)),
            'last_authenticated_at' => now()->subDays(fake()->numberBetween(0, 30)),
            'auth_count' => fake()->numberBetween(1, 100),
            'location_country' => fake()->countryCode(),
            'location_city' => fake()->city(),
            'meta' => [],
        ];
    }

    public function trusted(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_trusted' => true,
        ]);
    }

    public function revoked(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_revoked' => true,
        ]);
    }

    public function current(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
        ]);
    }
}
