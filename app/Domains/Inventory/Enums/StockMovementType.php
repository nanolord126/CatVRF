<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Enums;

/**
 * Тип движения остатков.
 *
 * in       — приход на склад (поставка)
 * out      — расход со склада (отгрузка)
 * reserve  — резервирование для корзины/заказа
 * release  — снятие резерва (просрочка, отмена)
 * return   — возврат товара на склад
 * adjustment — ручная корректировка (инвентаризация)
 */
enum StockMovementType: string
{
    case IN         = 'in';
    case OUT        = 'out';
    case RESERVE    = 'reserve';
    case RELEASE    = 'release';
    case RETURN     = 'return';
    case ADJUSTMENT = 'adjustment';

    /** Типы, увеличивающие доступный остаток. */
    public function isIncrement(): bool
    {
        return match ($this) {
            self::IN, self::RELEASE, self::RETURN => true,
            default                                => false,
        };
    }

    /** Типы, уменьшающие доступный остаток. */
    public function isDecrement(): bool
    {
        return match ($this) {
            self::OUT, self::RESERVE => true,
            default                  => false,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            self::cases(),
        );
    }
}
