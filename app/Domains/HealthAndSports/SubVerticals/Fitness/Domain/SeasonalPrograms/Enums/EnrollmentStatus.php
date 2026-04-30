<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\SeasonalPrograms\Enums;

enum EnrollmentStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case DROPPED = 'dropped';
    case PAUSED = 'paused';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Активна',
            self::COMPLETED => 'Завершена',
            self::DROPPED => 'Прекращена',
            self::PAUSED => 'На паузе',
        };
    }

    public function canTrackProgress(): bool
    {
        return in_array($this, [self::ACTIVE, self::PAUSED], true);
    }
}
