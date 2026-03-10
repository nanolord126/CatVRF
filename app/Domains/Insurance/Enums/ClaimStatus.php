<?php

namespace App\Domains\Insurance\Enums;

enum ClaimStatus: string
{
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::SUBMITTED => 'Подана',
            self::UNDER_REVIEW => 'На рассмотрении',
            self::APPROVED => 'Одобрена',
            self::REJECTED => 'Отклонена',
            self::PAID => 'Выплачена',
            self::CANCELLED => 'Отменена',
        };
    }

    public function canBePaid(): bool
    {
        return $this === self::APPROVED;
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::PAID, self::REJECTED, self::CANCELLED]);
    }
}
