<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Domain\Enums;

enum TransactionStatusEnum: string
{
    case ESCROW_PENDING = 'escrow_pending';
    case ESCROW_RELEASED = 'escrow_released';
    case ESCROW_REFUNDED = 'escrow_refunded';
    case PAYMENT_PENDING = 'payment_pending';
    case PAYMENT_COMPLETED = 'payment_completed';
    case PAYMENT_FAILED = 'payment_failed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ESCROW_PENDING => 'Escrow Pending',
            self::ESCROW_RELEASED => 'Escrow Released',
            self::ESCROW_REFUNDED => 'Escrow Refunded',
            self::PAYMENT_PENDING => 'Payment Pending',
            self::PAYMENT_COMPLETED => 'Payment Completed',
            self::PAYMENT_FAILED => 'Payment Failed',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED, self::ESCROW_REFUNDED], true);
    }

    public function isEscrow(): bool
    {
        return in_array($this, [self::ESCROW_PENDING, self::ESCROW_RELEASED, self::ESCROW_REFUNDED], true);
    }
}
