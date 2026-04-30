<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\SeasonalPrograms\Enums;

enum SeasonalProgramStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case FULL = 'full';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case ARCHIVED = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Черновик',
            self::ACTIVE => 'Активна',
            self::FULL => 'Мест нет',
            self::COMPLETED => 'Завершена',
            self::CANCELLED => 'Отменена',
            self::ARCHIVED => 'Архивирована',
        };
    }

    public function canEnroll(): bool
    {
        return in_array($this, [self::ACTIVE], true);
    }
}
