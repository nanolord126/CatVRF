<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Enums;

enum GroomingStatus: string
{
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Запланирован',
            self::IN_PROGRESS => 'В процессе',
            self::COMPLETED => 'Завершён',
            self::CANCELLED => 'Отменён',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SCHEDULED => 'blue',
            self::IN_PROGRESS => 'yellow',
            self::COMPLETED => 'green',
            self::CANCELLED => 'gray',
        };
    }

    public function canBeStarted(): bool
    {
        return $this === self::SCHEDULED;
    }

    public function canBeCompleted(): bool
    {
        return $this === self::IN_PROGRESS;
    }
}
