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

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\Food\Domain\ValueObjects;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case READY_FOR_PICKUP = 'ready_for_pickup';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED], true);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED], true);
    }

    public static function labels(): array
    {
        return [
            self::PENDING->value => 'В ожидании',
            self::CONFIRMED->value => 'Подтверждён',
            self::IN_PROGRESS->value => 'Готовится',
            self::READY_FOR_PICKUP->value => 'Готов к выдаче',
            self::COMPLETED->value => 'Завершён',
            self::CANCELLED->value => 'Отменён',
        ];
    }

    public function getLabel(): string
    {
        return self::labels()[$this->value];
    }
}
