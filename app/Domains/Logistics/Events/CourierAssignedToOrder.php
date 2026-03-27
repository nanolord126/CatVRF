<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Events;

use App\Domains\Logistics\Models\DeliveryOrder;
use App\Domains\Logistics\Models\Courier;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * КАНОН 2026 — СОБЫТИЕ ПРИВЯЗКИ КУРЬЕРА
 * Содержит correlation_id для сквозного логирования
 */
final class CourierAssignedToOrder
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly DeliveryOrder $order,
        public readonly Courier $courier,
        public readonly string $correlationId
    ) {}
}
