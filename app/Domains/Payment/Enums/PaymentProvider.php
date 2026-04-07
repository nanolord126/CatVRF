<?php

declare(strict_types=1);

namespace App\Domains\Payment\Enums;

/**
 * Провайдеры платёжных шлюзов.
 */
enum PaymentProvider: string
{
    case TINKOFF = 'tinkoff';
    case SBER = 'sber';
    case TOCHKA = 'tochka';
    case SBP = 'sbp';
    case YOOKASSA = 'yookassa';
    case MANUAL = 'manual';

    /**
     * Человекочитаемая метка.
     */
    public function label(): string
    {
        return match ($this) {
            self::TINKOFF => 'Тинькофф',
            self::SBER => 'Сбербанк',
            self::TOCHKA => 'Точка',
            self::SBP => 'Система быстрых платежей',
            self::YOOKASSA => 'ЮKassa',
            self::MANUAL => 'Ручной',
        };
    }

    /**
     * Поддерживает ли провайдер двухфазные платежи (authorize → capture).
     */
    public function supportsTwoPhase(): bool
    {
        return in_array($this, [
            self::TINKOFF,
            self::SBER,
            self::YOOKASSA,
        ], true);
    }

    /**
     * Поддерживает ли провайдер возвраты.
     */
    public function supportsRefund(): bool
    {
        return $this !== self::MANUAL;
    }

    /**
     * Максимальный таймаут capture (минуты).
     */
    public function captureTimeoutMinutes(): int
    {
        return match ($this) {
            self::TINKOFF => 1440,
            self::SBER => 4320,
            self::YOOKASSA => 10080,
            default => 60,
        };
    }
}
