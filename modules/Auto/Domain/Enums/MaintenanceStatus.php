<?php

declare(strict_types=1);

namespace Modules\Auto\Domain\Enums;

enum MaintenanceStatus: string
{
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case OVERDUE = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::OVERDUE => 'Overdue',
        };
    }

    public function canBeCompleted(): bool
    {
        return in_array($this, [self::SCHEDULED, self::IN_PROGRESS, self::OVERDUE], true);
    }
}
