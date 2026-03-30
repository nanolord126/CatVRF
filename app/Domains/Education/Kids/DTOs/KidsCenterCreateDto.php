<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\DTOs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsCenterCreateDto extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @param array<string, bool> $facility_details
         * @param array<string, array<string, string>> $schedule_hours
         * @param array<string> $tags
         */
        public function __construct(
            public int $store_id,
            public string $name,
            public string $center_type, // playground, education, club, day_care
            public string $address,
            public ?string $geo_point,
            public int $capacity_limit,
            public int $hourly_rate, // in kopecks
            public bool $is_safety_verified,
            public array $facility_details,
            public array $schedule_hours,
            public ?string $correlation_id = null,
            public array $tags = [],
        ) {}

        /**
         * Create from request.
         */
        public static function fromRequest(array $data): self
        {
            return new self(
                store_id: (int) $data['store_id'],
                name: $data['name'],
                center_type: $data['center_type'],
                address: $data['address'],
                geo_point: $data['geo_point'] ?? null,
                capacity_limit: (int) ($data['capacity_limit'] ?? 10),
                hourly_rate: (int) ($data['hourly_rate'] ?? 0),
                is_safety_verified: (bool) ($data['is_safety_verified'] ?? false),
                facility_details: $data['facility_details'] ?? [],
                schedule_hours: $data['schedule_hours'] ?? [],
                correlation_id: $data['correlation_id'] ?? null,
                tags: $data['tags'] ?? [],
            );
        }

        /**
         * Convert to array.
         */
        public function toArray(): array
        {
            return [
                'store_id' => $this->store_id,
                'name' => $this->name,
                'center_type' => $this->center_type,
                'address' => $this->address,
                'geo_point' => $this->geo_point,
                'capacity_limit' => $this->capacity_limit,
                'hourly_rate' => $this->hourly_rate,
                'is_safety_verified' => $this->is_safety_verified,
                'facility_details' => $this->facility_details,
                'schedule_hours' => $this->schedule_hours,
                'correlation_id' => $this->correlation_id,
                'tags' => $this->tags,
            ];
        }
}
