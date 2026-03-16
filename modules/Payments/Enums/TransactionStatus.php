<?php

namespace App\Domains\Payments\Enums;

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
        return match($this) {
            self::PENDING => 'Ожидание',
            self::PROCESSING => 'Обработка',
            self::COMPLETED => 'Завершена',
            self::FAILED => 'Не выполнена',
            self::CANCELLED => 'Отменена',
            self::REFUNDED => 'Возвращена',
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
