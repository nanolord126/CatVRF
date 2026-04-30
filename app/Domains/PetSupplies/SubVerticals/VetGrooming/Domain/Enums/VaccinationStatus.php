<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Enums;

enum VaccinationStatus: string
{
    case PLANNED = 'planned';
    case COMPLETED = 'completed';
    case OVERDUE = 'overdue';
    case SKIPPED = 'skipped';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PLANNED => 'Запланирована',
            self::COMPLETED => 'Выполнена',
            self::OVERDUE => 'Просрочена',
            self::SKIPPED => 'Пропущена',
            self::CANCELLED => 'Отменена',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PLANNED => 'blue',
            self::COMPLETED => 'green',
            self::OVERDUE => 'red',
            self::SKIPPED => 'gray',
            self::CANCELLED => 'yellow',
        };
    }

    public function canBeCompleted(): bool
    {
        return in_array($this, [self::PLANNED, self::OVERDUE]);
    }
}
