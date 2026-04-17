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
}
