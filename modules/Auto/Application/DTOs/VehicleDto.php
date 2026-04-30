<?php

declare(strict_types=1);

namespace Modules\Auto\Application\DTOs;

final readonly class VehicleDto
{
    public function __construct(
        public ?int $id,
        public int $tenantId,
        public string $type,
        public string $licensePlate,
        public string $make,
        public string $model,
        public int $year,
        public string $color,
        public ?int $mileage,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            tenantId: (int) ($data['tenant_id'] ?? 1),
            type: (string) ($data['type'] ?? ''),
            licensePlate: (string) ($data['license_plate'] ?? ''),
            make: (string) ($data['make'] ?? ''),
            model: (string) ($data['model'] ?? ''),
            year: (int) ($data['year'] ?? date('Y')),
            color: (string) ($data['color'] ?? ''),
            mileage: isset($data['mileage']) ? (int) $data['mileage'] : null,
            metadata: (array) ($data['metadata'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'type' => $this->type,
            'license_plate' => $this->licensePlate,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'color' => $this->color,
            'mileage' => $this->mileage,
            'metadata' => $this->metadata,
        ];
    }
}
