<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Carbon\Carbon;

/**
 * CommissionCalculator — расчёт комиссии платформы для вертикали Beauty.
 *
 * Правила:
 *  - Стандарт: 14%
 *  - Переход с Dikidi: 10% (первые 4 мес.) → 12% (след. 24 мес.) → 14% далее
 */
final readonly class CommissionCalculator
{
    private const COMMISSION_STANDARD     = 14;
    private const COMMISSION_DIKIDI_EARLY = 10;
    private const COMMISSION_DIKIDI_MID   = 12;

    /**
     * Рассчитать комиссию в копейках.
     *
     * @param array<string, mixed> $tags Теги салона (migration_source, migration_date)
     */
    public function calculatePlatformCommission(
        int   $amountKopecks,
        array $tags = [],
    ): int {
        $percentage = $this->resolvePercentage($tags);

        return (int) ($amountKopecks * ($percentage / 100));
    }

    /**
     * Рассчитать выплату мастеру/салону (сумма минус комиссия).
     *
     * @param array<string, mixed> $tags
     */
    public function calculatePayout(int $amountKopecks, array $tags = []): int
    {
        return $amountKopecks - $this->calculatePlatformCommission($amountKopecks, $tags);
    }

    /**
     * Вернуть текущий процент комиссии.
     *
     * @param array<string, mixed> $tags
     */
    public function resolvePercentage(array $tags): int
    {
        if (($tags['migration_source'] ?? null) !== 'dikidi') {
            return self::COMMISSION_STANDARD;
        }

        $migrationDate = Carbon::parse($tags['migration_date'] ?? Carbon::now()->toDateString());
        $monthsElapsed = $migrationDate->diffInMonths(Carbon::now());

        return match (true) {
            $monthsElapsed < 4  => self::COMMISSION_DIKIDI_EARLY,
            $monthsElapsed < 28 => self::COMMISSION_DIKIDI_MID,
            default             => self::COMMISSION_STANDARD,
        };
    }
}
