<?php

declare(strict_types=1);

namespace Modules\Restaurant\Enums;

use Illuminate\Support\Str;

/**
 * Table Status — Статус столика в ресторане
 */
enum TableStatus: string
{
    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case RESERVED = 'reserved';
    case CLEANING = 'cleaning';
    case MAINTENANCE = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Свободен',
            self::OCCUPIED => 'Занят',
            self::RESERVED => 'Забронирован',
            self::CLEANING => 'Уборка',
            self::MAINTENANCE => 'На обслуживании',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE => 'green',
            self::OCCUPIED => 'red',
            self::RESERVED => 'yellow',
            self::CLEANING => 'blue',
            self::MAINTENANCE => 'gray',
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::AVAILABLE;
    }

    public function isOccupied(): bool
    {
        return $this === self::OCCUPIED;
    }

    public function isReserved(): bool
    {
        return $this === self::RESERVED;
    }

    public function canBeReserved(): bool
    {
        return in_array($this, [self::AVAILABLE, self::RESERVED], true);
    }

    public function canBeOccupied(): bool
    {
        return $this === self::AVAILABLE;
    }

    public function canBeReleased(): bool
    {
        return in_array($this, [self::OCCUPIED, self::RESERVED], true);
    }

    public static function fromLabel(string $label): ?self
    {
        foreach (self::cases() as $case) {
            if (Str::lower($case->label()) === Str::lower($label)) {
                return $case;
            }
        }

        return null;
    }
}
