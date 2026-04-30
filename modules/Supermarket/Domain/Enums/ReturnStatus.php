<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Enums;

enum ReturnStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case COMPLETED = 'completed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'На рассмотрении',
            self::APPROVED => 'Одобрено',
            self::REJECTED => 'Отклонено',
            self::COMPLETED => 'Завершено',
            self::REFUNDED => 'Возвращено',
        };
    }

    public function canBeApproved(): bool
    {
        return $this === self::PENDING;
    }

    public function canBeRejected(): bool
    {
        return $this === self::PENDING;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::REJECTED, self::COMPLETED, self::REFUNDED], true);
    }
}
