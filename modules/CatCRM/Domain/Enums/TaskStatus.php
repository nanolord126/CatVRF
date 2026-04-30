<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Enums;

use App\Enums\BaseEnum;

/**
 * Task Status — Статус задачи в CRM
 */
enum TaskStatus: string implements BaseEnum
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает',
            self::InProgress => 'В работе',
            self::Completed => 'Завершена',
            self::Cancelled => 'Отменена',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::InProgress => 'blue',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::InProgress]);
    }
}
