<?php declare(strict_types=1);

namespace App\Domains\Logistics\DataTransferObjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourierProfileDto extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            public string $uuid,
            public string $fullName,
            public string $status, // online, offline, busy
            public float $rating,
            public ?string $vehicleType,
            public ?string $licensePlate,
            public int $totalOrders,
            public bool $isActive,
            public array $currentLocation = [],
            public array $metadata = []
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
}
