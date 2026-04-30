<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WebauthnCredential;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebauthnCredential>
 */
final class WebauthnCredentialFactory extends Factory
{
    protected $model = WebauthnCredential::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'user_id' => User::factory(),
            'credential_id' => $this->faker->uuid,
            'public_key' => json_encode([
                'kty' => 'EC',
                'crv' => 'P-256',
                'x' => $this->faker->sha256,
                'y' => $this->faker->sha256,
            ]),
            'user_handle' => (string) $this->faker->randomNumber(),
            'aaguid' => $this->faker->uuid,
            'transports' => $this->faker->randomElement([['internal'], ['hybrid'], ['internal', 'hybrid']]),
            'counter' => $this->faker->numberBetween(0, 1000),
            'backed_up' => $this->faker->boolean(30), // 30% chance of being backed up
            'device_type' => $this->faker->randomElement(['single_device', 'syncable']),
            'name' => $this->faker->randomElement(['iPhone Face ID', 'MacBook Touch ID', 'Windows Hello', 'Security Key']),
            'user_agent' => $this->faker->userAgent,
            'ip_address' => $this->faker->ipv4,
            'last_used_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'backup_codes' => [],
            'recovery_enabled' => false,
            'last_backup_code_used_at' => null,
            'backup_codes_remaining' => 0,
            'is_compromised' => false,
            'compromised_at' => null,
        ];
    }

    public function platform(): static
    {
        return $this->state(fn (array $attributes) => [
            'transports' => ['internal'],
            'device_type' => 'single_device',
            'name' => 'iPhone Face ID',
        ]);
    }

    public function syncable(): static
    {
        return $this->state(fn (array $attributes) => [
            'transports' => ['hybrid'],
            'device_type' => 'syncable',
            'backed_up' => true,
            'name' => 'Passkey (Synced)',
        ]);
    }

    public function securityKey(): static
    {
        return $this->state(fn (array $attributes) => [
            'transports' => ['usb', 'nfc'],
            'device_type' => 'single_device',
            'backed_up' => false,
            'name' => 'Security Key',
        ]);
    }
}
