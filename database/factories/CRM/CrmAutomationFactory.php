<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Domains\CRM\Models\CrmAutomation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Фабрика CrmAutomation — автоматизации CRM.
 * Канон CatVRF 2026.
 */
final class CrmAutomationFactory extends Factory
{
    protected $model = CrmAutomation::class;

    private const TRIGGER_TYPES = ['new_client', 'sleeping_client', 'birthday', 'purchase', 'segment_enter'];
    private const ACTION_TYPES = ['send_email', 'send_sms', 'send_push', 'assign_segment', 'notify_manager'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'tags' => [],
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'vertical' => $this->faker->randomElement(['beauty', 'auto', 'food', 'hotel', 'taxi']),
            'is_active' => true,
            'trigger_type' => $this->faker->randomElement(self::TRIGGER_TYPES),
            'trigger_config' => ['vertical' => 'beauty'],
            'action_type' => $this->faker->randomElement(self::ACTION_TYPES),
            'action_config' => ['template' => 'default'],
            'delay_type' => null,
            'delay_minutes' => 0,
            'total_sent' => $this->faker->numberBetween(0, 1000),
            'total_opened' => $this->faker->numberBetween(0, 500),
            'total_clicked' => $this->faker->numberBetween(0, 200),
            'total_converted' => $this->faker->numberBetween(0, 50),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
