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

enum RestaurantStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case IN_REVIEW = 'in_review';
    case SUSPENDED = 'suspended';

    public function isPubliclyVisible(): bool
    {
        return match ($this) {
            self::OPEN => true,
            default => false,
        };
    }

    public function canAcceptOrders(): bool
    {
        return $this === self::OPEN;
    }

    public static function labels(): array
    {
        return [
            self::OPEN->value => 'Открыто',
            self::CLOSED->value => 'Закрыто',
            self::IN_REVIEW->value => 'На модерации',
            self::SUSPENDED->value => 'Приостановлено',
        ];
    }

    public function getLabel(): string
    {
        return self::labels()[$this->value];
    }
}
