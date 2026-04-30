<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantVerificationStatus: string
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

    public function isRejected(): bool
    {
        return $this === self::Rejected;
    }
    case Pending = 'pending';           // Registration submitted, verification pending
    case AutoApproved = 'auto_approved'; // Auto-approved by ML/rules
    case ManualReview = 'manual_review'; // Pending manual review
    case Approved = 'approved';         // Verified and approved
    case Rejected = 'rejected';         // Rejected by moderator
    case Suspended = 'suspended';       // Suspended for review
}
