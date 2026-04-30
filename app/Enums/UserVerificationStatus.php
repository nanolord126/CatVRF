<?php

declare(strict_types=1);

namespace App\Enums;

enum UserVerificationStatus: string
{
    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Не верифицирован',
            self::Pending => 'В процессе',
            self::Verified => 'Верифицирован',
            self::Rejected => 'Отклонен',
            self::RequiresReview => 'Требует проверки',
        };
    }

    public function isVerified(): bool
    {
        return $this === self::Verified;
    }

    public function isPending(): bool
    {
        return match ($this) {
            self::Pending, self::RequiresReview => true,
            default => false,
        };
    }

    public function canOperate(): bool
    {
        return match ($this) {
            self::Verified => true,
            default => false,
        };
    }
    case Unverified = 'unverified';
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case RequiresReview = 'requires_review';
}
