<?php declare(strict_types=1);

namespace Modules\Payments\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'В ожидании',
            self::PROCESSING => 'В обработке',
            self::COMPLETED => 'Завершено',
            self::FAILED => 'Ошибка',
            self::CANCELLED => 'Отменено',
            self::REFUNDED => 'Возвращено',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'secondary',
            self::REFUNDED => 'dark',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED, self::REFUNDED]);
    }

    public function canBeRefunded(): bool
    {
        return $this === self::COMPLETED;
    }
}

