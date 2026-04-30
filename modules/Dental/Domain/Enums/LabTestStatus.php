<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\Enums;

enum LabTestStatus: string
{
    case ORDERED = 'ordered';
    case SAMPLE_COLLECTED = 'sample_collected';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::ORDERED => 'Заказан',
            self::SAMPLE_COLLECTED => 'Образец взят',
            self::IN_PROGRESS => 'В обработке',
            self::COMPLETED => 'Готов',
            self::CANCELLED => 'Отменён',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ORDERED => '#6b7280',
            self::SAMPLE_COLLECTED => '#f59e0b',
            self::IN_PROGRESS => '#3b82f6',
            self::COMPLETED => '#10b981',
            self::CANCELLED => '#ef4444',
        };
    }
}
