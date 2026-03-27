<?php declare(strict_types=1);

namespace App\Domains\Logistics\DataTransferObjects;

/**
 * Delivery Order DTO (2026 Edition)
 * 
 * Данные заказа для клиента/API.
 */
final readonly class DeliveryOrderDto
{
    public function __construct(
        public string $uuid,
        public string $status,
        public int $totalPrice, // в копейках
        public float $surgeMultiplier,
        public string $estimatedDeliveryAt,
        public array $pickup,
        public array $dropoff,
        public ?string $courierName = null,
        public ?string $courierPhone = null
    ) {}

    public static function fromModel(\App\Domains\Logistics\Models\DeliveryOrder $order): self
    {
        return new self(
            uuid: $order->uuid,
            status: $order->status,
            totalPrice: $order->total_price,
            surgeMultiplier: (float) $order->surge_multiplier,
            estimatedDeliveryAt: $order->estimated_delivery_at?->toIso8601String() ?? 'Unknown',
            pickup: $order->pickup_point,
            dropoff: $order->dropoff_point,
            courierName: $order->courier->user->name ?? null,
            courierPhone: $order->courier->user->phone ?? null
        );
    }
}
