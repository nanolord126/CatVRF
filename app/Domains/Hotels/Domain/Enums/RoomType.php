<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary
 *
 * @see https://catvrf.ru/docs/roomtype
 */

namespace App\Domains\Hotels\Domain\Enums;

enum RoomType: string
{
    case SINGLE = 'single';
    case DOUBLE = 'double';
    case SUITE = 'suite';
    case FAMILY = 'family';
    case APARTMENT = 'apartment';
    case STUDIO = 'studio';
    case PENTHOUSE = 'penthouse';
    case DELUXE = 'deluxe';

    /**
     * Получить локализованное название типа номера.
     *
     * @return string Человекочитаемое название
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SINGLE => 'Одноместный',
            self::DOUBLE => 'Двухместный',
            self::SUITE => 'Люкс',
            self::FAMILY => 'Семейный',
            self::APARTMENT => 'Апартаменты',
            self::STUDIO => 'Студия',
            self::PENTHOUSE => 'Пентхаус',
            self::DELUXE => 'Делюкс',
        };
    }

    /**
     * Получить максимальную вместимость по умолчанию.
     *
     * @return int Количество гостей
     */
    public function getDefaultMaxGuests(): int
    {
        return match ($this) {
            self::SINGLE => 1,
            self::DOUBLE => 2,
            self::STUDIO => 2,
            self::SUITE => 3,
            self::DELUXE => 3,
            self::FAMILY => 4,
            self::APARTMENT => 6,
            self::PENTHOUSE => 8,
        };
    }

    /**
     * Является ли тип номера премиальным.
     *
     * @return bool True для Suite, Penthouse, Deluxe
     */
    public function isPremium(): bool
    {
        return in_array($this, [self::SUITE, self::PENTHOUSE, self::DELUXE], true);
    }
}
