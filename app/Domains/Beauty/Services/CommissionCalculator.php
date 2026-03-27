<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\BeautySalon;
use App\Domains\Consulting\Finances\Models\CommissionRule;

/**
 * КАНОН 2026: Commission Calculator (Layer 3)
 */
final readonly class CommissionCalculator
{
    /**
     * Рассчитать комиссию платформы.
     * 
     * Правила:
     * - Стандарт: 14%
     * - Переход с Dikidi: 10% (первые 4 мес) -> 12% (след 24 мес)
     */
    public function calculatePlatformCommission(BeautySalon $salon, int $amount): int
    {
        $percentage = 14; 

        // Проверка миграции
        if ($salon->tags['migration_source'] ?? null === 'dikidi') {
            $migrationDate = \Carbon\Carbon::parse($salon->tags['migration_date'] ?? $salon->created_at);
            $now = \Carbon\Carbon::now();

            if ($now->diffInMonths($migrationDate) < 4) {
                $percentage = 10;
            } elseif ($now->diffInMonths($migrationDate) < 28) {
                $percentage = 12;
            }
        }

        return (int) ($amount * ($percentage / 100));
    }
}
