<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Ramsey\Uuid\Uuid;

/**
 * BeautyBaseService — утилитный класс для общих констант и хелперов вертикали Beauty.
 *
 * Содержит статические вычислительные методы. Не наследуется — используется через DI.
 *
 * @package App\Domains\Beauty\Services
 */
final readonly class BeautyBaseService
{
    /** Комиссия платформы: 14% стандарт. */
    private const PLATFORM_COMMISSION = 0.14;

    /** Имя вертикали. */
    public function getVerticalName(): string
    {
        return 'beauty';
    }

    /** Базовая комиссия (14%). */
    public function getBaseCommissionRate(): float
    {
        return self::PLATFORM_COMMISSION;
    }

    /**
     * Рассчитать выплату после вычета комиссии платформы.
     *
     * @param int $totalKopecks Сумма в копейках
     * @return int Выплата после вычета комиссии
     */
    public function calculatePayout(int $totalKopecks): int
    {
        return (int) ($totalKopecks * (1 - self::PLATFORM_COMMISSION));
    }

    /**
     * Рассчитать сумму комиссии в копейках.
     *
     * @param int $totalKopecks Сумма в копейках
     * @return int Комиссия платформы в копейках
     */
    public function calculateCommission(int $totalKopecks): int
    {
        return (int) ($totalKopecks * self::PLATFORM_COMMISSION);
    }

    /**
     * Сгенерировать correlation_id если не передан.
     *
     * @param string $correlationId Существующий ID или пустая строка
     * @return string UUID correlation_id
     */
    public function resolveCorrelationId(string $correlationId): string
    {
        return $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();
    }
}

