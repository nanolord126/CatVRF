<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\Enums;

enum TreatmentStepStatus: string
{
    case PENDING = 'pending';
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает',
            self::SCHEDULED => 'Запланирован',
            self::IN_PROGRESS => 'В процессе',
            self::COMPLETED => 'Выполнен',
            self::CANCELLED => 'Отменён',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => '#6b7280',
            self::SCHEDULED => '#f59e0b',
            self::IN_PROGRESS => '#3b82f6',
            self::COMPLETED => '#10b981',
            self::CANCELLED => '#ef4444',
        };
    }
}
