<?php

declare(strict_types=1);

namespace App\Domains\Finances\Domain\Interfaces;

use Carbon\CarbonImmutable;

/**
 * Контракт калькулятора заработка для конкретной вертикали.
 *
 * Каждая вертикаль (Beauty, Food, Furniture и т.д.) реализует
 * свой калькулятор, который умеет считать выручку и комиссии
 * за указанный период по tenant.
 *
 * Калькуляторы регистрируются через FinancesServiceProvider
 * и используются в ProcessMonthlyPayoutsUseCase.
 *
 * Суммы возвращаются в копейках (1 рубль = 100).
 *
 * @package App\Domains\Finances\Domain\Interfaces
 */
interface EarningCalculatorInterface
{
    /**
     * Посчитать общий заработок тенанта за период.
     *
     * @param int              $tenantId  ID тенанта
     * @param CarbonImmutable  $from      Начало периода (включительно)
     * @param CarbonImmutable  $to        Конец периода (включительно)
     * @return int Сумма в копейках
     */
    public function calculateForTenant(int $tenantId, CarbonImmutable $from, CarbonImmutable $to): int;

    /**
     * Посчитать заработок тенанта по бизнес-группе (B2B-филиал).
     *
     * @param int              $tenantId        ID тенанта
     * @param int              $businessGroupId ID бизнес-группы
     * @param CarbonImmutable  $from            Начало периода
     * @param CarbonImmutable  $to              Конец периода
     * @return int Сумма в копейках
     */
    public function calculateForBusinessGroup(
        int $tenantId,
        int $businessGroupId,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): int;

    /**
     * Рассчитать комиссию платформы для тенанта за период.
     *
     * Комиссия зависит от вертикали, B2B-тира и объёма.
     *
     * @param int              $tenantId ID тенанта
     * @param CarbonImmutable  $from     Начало периода
     * @param CarbonImmutable  $to       Конец периода
     * @return int Комиссия в копейках
     */
    public function calculatePlatformCommission(
        int $tenantId,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): int;

    /**
     * Вернуть slug вертикали, которую обслуживает калькулятор.
     *
     * @return string Например: 'beauty', 'food', 'furniture'
     */
    public function getVertical(): string;
}
