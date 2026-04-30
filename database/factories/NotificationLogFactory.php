<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

final class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'tenant_id' => 1,
            'channel' => $this->faker->randomElement(['telegram', 'whatsapp', 'viber', 'email', 'push']),
            'event_type' => $this->faker->randomElement(['created', 'confirmed', 'ready_for_delivery', 'in_delivery', 'delivered', 'cancelled']),
            'entity_type' => 'order',
            'entity_id' => $this->faker->randomNumber(),
            'recipient' => $this->faker->email(),
            'status' => $this->faker->randomElement(['pending', 'sent', 'delivered', 'failed', 'bounced']),
            'message_content' => $this->faker->sentence(),
            'error_message' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
            'metadata' => [
                'interactive' => $this->faker->boolean(),
                'buttons' => $this->faker->boolean(50) ? ['track', 'my_orders'] : null,
            ],
            'sent_at' => $this->faker->boolean(80) ? $this->faker->dateTimeThisMonth() : null,
            'delivered_at' => $this->faker->boolean(60) ? $this->faker->dateTimeThisMonth() : null,
        ];
    }
}
