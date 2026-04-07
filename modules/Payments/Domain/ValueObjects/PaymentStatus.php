<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\ValueObjects;

/**
 * Value Object: Статус платежа.
 */
enum PaymentStatus: string
{
    case PENDING    = 'pending';
    case AUTHORIZED = 'authorized';
    case CAPTURED   = 'captured';
    case FAILED     = 'failed';
    case REFUNDED   = 'refunded';
    case CANCELLED  = 'cancelled';
    case EXPIRED    = 'expired';

    public function canBeRefunded(): bool
    {
        return $this === self::CAPTURED;
    }

    public function canBeCaptured(): bool
    {
        return in_array($this, [self::PENDING, self::AUTHORIZED], true);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::AUTHORIZED], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::CAPTURED, self::FAILED, self::REFUNDED, self::CANCELLED, self::EXPIRED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING    => 'Ожидает',
            self::AUTHORIZED => 'Авторизован',
            self::CAPTURED   => 'Оплачен',
            self::FAILED     => 'Ошибка',
            self::REFUNDED   => 'Возврат',
            self::CANCELLED  => 'Отменён',
            self::EXPIRED    => 'Истёк',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING    => 'warning',
            self::AUTHORIZED => 'info',
            self::CAPTURED   => 'success',
            self::FAILED     => 'danger',
            self::REFUNDED   => 'gray',
            self::CANCELLED  => 'gray',
            self::EXPIRED    => 'danger',
        };
    }
}
