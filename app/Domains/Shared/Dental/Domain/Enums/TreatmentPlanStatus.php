<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\Enums;

enum TreatmentPlanStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Черновик',
            self::ACTIVE => 'Активен',
            self::IN_PROGRESS => 'В процессе',
            self::COMPLETED => 'Завершён',
            self::CANCELLED => 'Отменён',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => '#6b7280',
            self::ACTIVE => '#22c55e',
            self::IN_PROGRESS => '#3b82f6',
            self::COMPLETED => '#10b981',
            self::CANCELLED => '#ef4444',
        };
    }
}
