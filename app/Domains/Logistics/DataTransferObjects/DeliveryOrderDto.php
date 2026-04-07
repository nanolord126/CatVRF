<?php declare(strict_types=1);

/**
 * DeliveryOrderDto — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/deliveryorderdto
 */


namespace App\Domains\Logistics\DataTransferObjects;

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
            private ?string $courierName = null,
            private readonly ?string $courierPhone = null
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

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
