<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Shared\Notifications\Models\NotificationExperiment;
use Illuminate\Database\Seeder;

final class NotificationExperimentSeeder extends Seeder
{
    public function run(): void
    {
        $experiments = [
            [
                'name' => 'Pre-Delivery Reminder - Short vs Long Text',
                'event_type' => 'pre_delivery_reminder',
                'variant_a' => [
                    'text' => '📦 Напоминание: доставка через 24 часа!',
                    'buttons' => ['track', 'pause'],
                ],
                'variant_b' => [
                    'text' => '📦 Здравствуйте! Ваша доставка по подписке будет через 24 часа. Вы можете отследить курьера или приостановить подписку.',
                    'buttons' => ['track', 'pause', 'cancel'],
                ],
                'traffic_percent' => 50,
                'is_active' => true,
                'started_at' => now(),
                'ended_at' => now()->addDays(30),
            ],
            [
                'name' => 'Subscription Created - Emoji vs No Emoji',
                'event_type' => 'subscription_created',
                'variant_a' => [
                    'text' => '✅ Подписка оформлена! Следующая доставка: ' . now()->addWeek()->format('d.m.Y'),
                    'buttons' => ['track', 'manage'],
                ],
                'variant_b' => [
                    'text' => 'Ваша подписка успешно оформлена. Следующая доставка состоится ' . now()->addWeek()->format('d.m.Y'),
                    'buttons' => ['track', 'manage'],
                ],
                'traffic_percent' => 50,
                'is_active' => false,
            ],
            [
                'name' => 'Payment Failed - Urgent vs Friendly Tone',
                'event_type' => 'payment_failed',
                'variant_a' => [
                    'text' => '❌ Оплата не прошла! Обновите способ оплаты срочно.',
                    'buttons' => ['update_payment'],
                ],
                'variant_b' => [
                    'text' => 'У нас возникли сложности с оплатой вашей подписки. Пожалуйста, обновите способ оплаты в настройках.',
                    'buttons' => ['update_payment', 'support'],
                ],
                'traffic_percent' => 50,
                'is_active' => true,
                'started_at' => now(),
                'ended_at' => now()->addDays(14),
            ],
        ];

        foreach ($experiments as $experiment) {
            NotificationExperiment::create($experiment);
        }

        $this->command->info('Notification experiments seeded successfully');
    }
}
