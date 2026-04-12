<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Domains\CRM\Models\CrmInteraction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Фабрика CrmInteraction — взаимодействия с клиентами.
 * Канон CatVRF 2026.
 */
final class CrmInteractionFactory extends Factory
{
    protected $model = CrmInteraction::class;

    private const TYPES = [
        'call', 'email', 'sms', 'chat', 'visit', 'purchase',
        'complaint', 'feedback', 'meeting', 'callback', 'push', 'social',
    ];

    private const CHANNELS = ['phone', 'email', 'website', 'app', 'social', 'in_person'];
    private const DIRECTIONS = ['inbound', 'outbound'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'crm_client_id' => null,
            'user_id' => null,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'type' => $this->faker->randomElement(self::TYPES),
            'channel' => $this->faker->randomElement(self::CHANNELS),
            'direction' => $this->faker->randomElement(self::DIRECTIONS),
            'subject' => $this->faker->sentence(4),
            'content' => $this->faker->paragraph(),
            'metadata' => [],
            'interacted_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function forClient(int $clientId): static
    {
        return $this->state(fn () => ['crm_client_id' => $clientId]);
    }

    public function call(): static
    {
        return $this->state(fn () => ['type' => 'call', 'channel' => 'phone']);
    }

    public function purchase(): static
    {
        return $this->state(fn () => [
            'type' => 'purchase',
            'channel' => 'website',
            'direction' => 'inbound',
        ]);
    }
}
