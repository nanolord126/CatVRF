<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Enums;

/**
 * Статус инвентаризации.
 *
 * planned     — запланирована
 * in_progress — выполняется
 * completed   — завершена без расхождений
 * discrepancy — обнаружены расхождения
 */
enum InventoryCheckStatus: string
{
    case PLANNED     = 'planned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED   = 'completed';
    case DISCREPANCY = 'discrepancy';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::DISCREPANCY => true,
            default                             => false,
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PLANNED     => [self::IN_PROGRESS],
            self::IN_PROGRESS => [self::COMPLETED, self::DISCREPANCY],
            self::COMPLETED   => [],
            self::DISCREPANCY => [],
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
