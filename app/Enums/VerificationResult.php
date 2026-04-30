<?php

declare(strict_types=1);

namespace App\Enums;

enum VerificationResult: string
{
    public function label(): string
    {
        return match ($this) {
            self::Success => 'Успешно',
            self::Failed => 'Неудачно',
            self::Pending => 'В процессе',
            self::RequiresReview => 'Требует проверки',
        };
    }

    public function isPassed(): bool
    {
        return $this === self::Success;
    }

    public function isFailed(): bool
    {
        return $this === self::Failed;
    }

    public function isPending(): bool
    {
        return match ($this) {
            self::Pending, self::RequiresReview => true,
            default => false,
        };
    }
    case Success = 'success';
    case Failed = 'failed';
    case Pending = 'pending';
    case RequiresReview = 'requires_review';
}
