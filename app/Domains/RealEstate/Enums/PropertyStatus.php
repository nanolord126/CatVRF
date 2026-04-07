<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Enums;

/**
 * Enum PropertyStatus
 *
 * @package App\Domains\RealEstate\Enums
 *
 * @comment Статусы объекта недвижимости.
 * - PENDING: На модерации, ожидает проверки
 * - ACTIVE: Активно, опубликовано и доступно для поиска
 * - SOLD: Продано/Сдано, сделка завершена
 * - RENTED: Сдано в аренду
 * - INACTIVE: Неактивно, скрыто владельцем
 * - REJECTED: Отклонено модератором
 * - ARCHIVED: В архиве, скрыто из поиска, но сохранено в системе
 */
enum PropertyStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SOLD = 'sold';
    case RENTED = 'rented';
    case INACTIVE = 'inactive';
    case REJECTED = 'rejected';
    case ARCHIVED = 'archived';

    /**
     * Получить человекочитаемое название статуса.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'На модерации',
            self::ACTIVE => 'Активно',
            self::SOLD => 'Продано',
            self::RENTED => 'Сдано в аренду',
            self::INACTIVE => 'Неактивно',
            self::REJECTED => 'Отклонено',
            self::ARCHIVED => 'В архиве',
        };
    }

    /**
     * Получить все значения в виде массива для использования в Filament.
     *
     * @return array<string, string>
     */
    public static function forFilament(): array
    {
        return array_reduce(
            self::cases(),
            static fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            []
        );
    }

    /**
     * Статусы, которые считаются "завершенными" или "неактивными" для публичного поиска.
     *
     * @return array<string>
     */
    public static function getInactiveStatuses(): array
    {
        return [
            self::SOLD->value,
            self::RENTED->value,
            self::INACTIVE->value,
            self::REJECTED->value,
            self::ARCHIVED->value,
        ];
    }

    /**
     * Проверяет, является ли статус активным.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
