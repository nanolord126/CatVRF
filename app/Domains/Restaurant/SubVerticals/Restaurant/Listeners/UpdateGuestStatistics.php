<?php

declare(strict_types=1);

namespace Modules\Restaurant\Listeners;

use Modules\Restaurant\Events\LoyaltyPointsEarned;

/**
 * Update Guest Statistics — Обновление статистики гостя
 */
final class UpdateGuestStatistics
{
    public function handle(LoyaltyPointsEarned $event): void
    {
        $guest = $event->guest;

        // Статистика уже обновляется в LoyaltyService, но здесь можно добавить
        // дополнительную логику, например:
        // - Отправка уведомления о достижении нового уровня
        // - Персональные предложения
        // - Обновление рекомендаций

        if ($event->order) {
            // Проверяем, достиг ли гость порога для нового уровня
            $this->checkTierUpgrade($guest);
        }
    }

    private function checkTierStatistics(\Modules\Restaurant\Models\Guest $guest): void
    {
        // TODO: Дополнительная логика проверки достижения уровней
    }
}
