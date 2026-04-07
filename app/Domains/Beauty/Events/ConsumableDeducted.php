<?php

declare(strict_types=1);

/**
 * ConsumableDeducted — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/consumablededucted
 */


namespace App\Domains\Beauty\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: расходники списаны после визита.
 */
final class ConsumableDeducted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param array<string, int> $consumables Карта [consumable_id => quantity]
     */
    public function __construct(
        public readonly int    $appointmentId,
        public readonly int    $tenantId,
        public readonly array  $consumables,
        public readonly string $correlationId,
    ) {}

    /** @return array<int, \Illuminate\Broadcasting\Channel> */
    public function broadcastOn(): array
    {
        return [];
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . $this->correlationId;
    }
}

