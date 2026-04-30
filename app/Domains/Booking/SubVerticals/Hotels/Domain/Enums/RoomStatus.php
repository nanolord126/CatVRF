<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Enums;

enum RoomStatus: string
{
    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case DIRTY = 'dirty';
    case CLEANING = 'cleaning';
    case MAINTENANCE = 'maintenance';
    case OUT_OF_ORDER = 'out_of_order';
    case RESERVED = 'reserved';

    public function getLabel(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Доступен',
            self::OCCUPIED => 'Занят',
            self::DIRTY => 'Грязный',
            self::CLEANING => 'Уборка',
            self::MAINTENANCE => 'Обслуживание',
            self::OUT_OF_ORDER => 'Вне эксплуатации',
            self::RESERVED => 'Зарезервирован',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::AVAILABLE => '#22c55e',
            self::OCCUPIED => '#ef4444',
            self::DIRTY => '#f97316',
            self::CLEANING => '#eab308',
            self::MAINTENANCE => '#8b5cf6',
            self::OUT_OF_ORDER => '#6b7280',
            self::RESERVED => '#3b82f6',
        };
    }

    public function canBeBooked(): bool
    {
        return in_array($this, [self::AVAILABLE, self::RESERVED]);
    }

    public function requiresCleaning(): bool
    {
        return in_array($this, [self::DIRTY, self::OCCUPIED]);
    }
}
