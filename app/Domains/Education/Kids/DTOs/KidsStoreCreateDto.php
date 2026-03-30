<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\DTOs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KidsStoreCreateDto extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @param array<string, mixed> $schedule_json
         * @param array<string, mixed> $metadata
         */
        public function __construct(
            public string $name,
            public string $type, // retail, digital, warehouse, kiosk
            public string $address,
            public ?string $geo_point,
            public array $schedule_json,
            public array $metadata,
            public ?string $correlation_id = null,
            public ?string $tenant_id = null,
        ) {}

        /**
         * Create from validated request data.
         */
        public static function fromRequest(array $data): self
        {
            return new self(
                name: $data['name'],
                type: $data['type'],
                address: $data['address'] ?? 'online',
                geo_point: $data['geo_point'] ?? null,
                schedule_json: $data['schedule_json'] ?? [],
                metadata: $data['metadata'] ?? [],
                correlation_id: $data['correlation_id'] ?? null,
                tenant_id: $data['tenant_id'] ?? null,
            );
        }

        /**
         * To array for model fill.
         */
        public function toArray(): array
        {
            return [
                'name' => $this->name,
                'type' => $this->type,
                'address' => $this->address,
                'geo_point' => $this->geo_point,
                'schedule_json' => $this->schedule_json,
                'metadata' => $this->metadata,
                'correlation_id' => $this->correlation_id,
                'tenant_id' => $this->tenant_id,
            ];
        }
}
