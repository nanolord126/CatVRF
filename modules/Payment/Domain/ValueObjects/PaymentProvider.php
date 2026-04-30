<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\ValueObjects;

/**
 * Payment Provider Enum.
 */
enum PaymentProvider: string
{
    case TINKOFF = 'tinkoff';
    case TOCHKA = 'tochka';
    case SBER = 'sber';
    case YOO_MONEY = 'yoo_money';
    case SBP = 'sbp';
    case YOOKASSA = 'yookassa';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::TINKOFF => 'Тинькофф',
            self::SBER => 'Сбербанк',
            self::TOCHKA => 'Точка',
            self::SBP => 'Система быстрых платежей',
            self::YOOKASSA => 'ЮKassa',
            self::YOO_MONEY => 'ЮMoney',
            self::MANUAL => 'Ручной',
        };
    }

    public function supportsTwoPhase(): bool
    {
        return in_array($this, [
            self::TINKOFF,
            self::SBER,
            self::YOOKASSA,
        ], true);
    }

    public function supportsRefund(): bool
    {
        return $this !== self::MANUAL;
    }

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
