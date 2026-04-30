<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Enums;

enum HousekeepingStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case INSPECTED = 'inspected';
    case SKIPPED = 'skipped';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает',
            self::IN_PROGRESS => 'В работе',
            self::COMPLETED => 'Выполнена',
            self::INSPECTED => 'Проверена',
            self::SKIPPED => 'Пропущена',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => '#f97316',
            self::IN_PROGRESS => '#eab308',
            self::COMPLETED => '#22c55e',
            self::INSPECTED => '#3b82f6',
            self::SKIPPED => '#6b7280',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::INSPECTED, self::SKIPPED]);
    }
}
