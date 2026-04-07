<?php declare(strict_types=1);

namespace App\Domains\Logistics\DataTransferObjects;

final readonly class CourierProfileDto
{

    public function __construct(
            public string $uuid,
            public string $fullName,
            public string $status, // online, offline, busy
            public float $rating,
            public ?string $vehicleType,
            public ?string $licensePlate,
            public int $totalOrders,
            public bool $isActive,
            private array $currentLocation = [],
            private array $metadata = []
        ) {}

        public static function fromModel(\App\Domains\Logistics\Models\Courier $courier): self
        {
            return new self(
                uuid: $courier->uuid,
                fullName: $courier->user->name ?? 'Courier',
                status: $courier->status,
                rating: (float) $courier->rating,
                vehicleType: $courier->vehicle->type ?? null,
                licensePlate: $courier->vehicle->license_plate ?? null,
                totalOrders: $courier->metadata['total_orders'] ?? 0,
                isActive: $courier->is_active,
                currentLocation: $courier->current_location ?? [],
                metadata: $courier->metadata ?? []
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
