<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domains\Channels\Models\ChannelSubscriptionPlan;

/**
 * Наполняет таблицу channel_subscription_plans базовыми тарифами.
 * НЕ запускать в production без явного подтверждения.
 * Используется только для dev / staging.
 */
final class ChannelSubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = config('channels.plans', []);

        foreach ($plans as $slug => $cfg) {
            ChannelSubscriptionPlan::updateOrCreate(
                ['slug' => $slug],
                [
                    'name'              => $cfg['name'],
                    'price_kopecks'     => (int) $cfg['price_kopecks'],
                    'posts_per_day'     => (int) ($cfg['posts_per_day'] ?? 1),
                    'photos_per_post'   => (int) ($cfg['photos_per_post'] ?? 5),
                    'shorts_enabled'    => (bool) ($cfg['shorts_enabled'] ?? false),
                    'polls_enabled'     => (bool) ($cfg['polls_enabled'] ?? false),
                    'promo_enabled'     => (bool) ($cfg['promo_enabled'] ?? false),
                    'advanced_stats'    => (bool) ($cfg['advanced_stats'] ?? false),
                    'scheduled_posts'   => (bool) ($cfg['scheduled_posts'] ?? false),
                    'is_active'         => true,
                ]
            );
        }

        $this->command->info('ChannelSubscriptionPlanSeeder: ' . count($plans) . ' тарифных плана добавлено/обновлено.');
    }
}
