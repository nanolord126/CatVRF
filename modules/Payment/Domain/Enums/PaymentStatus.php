<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case PARTIALLY_PAID = 'partially_paid';
    case REFUNDED = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает',
            self::SUCCEEDED => 'Успешно',
            self::FAILED => 'Неудачно',
            self::PARTIALLY_PAID => 'Частично оплачено',
            self::REFUNDED => 'Возврат',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => '#f97316',
            self::SUCCEEDED => '#22c55e',
            self::FAILED => '#ef4444',
            self::PARTIALLY_PAID => '#fbbf24',
            self::REFUNDED => '#8b5cf6',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::SUCCEEDED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }
}
