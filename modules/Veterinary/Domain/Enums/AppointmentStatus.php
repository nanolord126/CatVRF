<?php

declare(strict_types=1);

namespace Modules\Veterinary\Domain\Enums;

enum AppointmentStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает подтверждения',
            self::CONFIRMED => 'Подтверждено',
            self::IN_PROGRESS => 'В процессе',
            self::COMPLETED => 'Завершено',
            self::CANCELLED => 'Отменено',
            self::NO_SHOW => 'Не явился',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'info',
            self::IN_PROGRESS => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::NO_SHOW => 'gray',
        };
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED], true);
    }

    public function canComplete(): bool
    {
        return in_array($this, [self::CONFIRMED, self::IN_PROGRESS], true);
    }
}
