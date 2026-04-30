<?php

declare(strict_types=1);

namespace App\Enums;

enum BusinessGroupVerificationStatus: string
{
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает верификации',
            self::AutoApproved => 'Авто-одобрено',
            self::ManualReview => 'На ручной проверке',
            self::Approved => 'Подтвержден',
            self::Rejected => 'Отклонен',
            self::Suspended => 'Приостановлен',
        };
    }

    public function canOperate(): bool
    {
        return match ($this) {
            self::Approved, self::AutoApproved => true,
            default => false,
        };
    }

    public function isPending(): bool
    {
        return match ($this) {
            self::Pending, self::ManualReview => true,
            default => false,
        };
    }
    case Pending = 'pending';
    case AutoApproved = 'auto_approved';
    case ManualReview = 'manual_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
}
