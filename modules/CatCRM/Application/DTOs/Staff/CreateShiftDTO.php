<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\DTOs\Staff;

use Carbon\CarbonImmutable;

/**
 * CreateShiftDTO — DTO для создания смены
 * 
 * Immutable DTO following CatVRF rules
 */
final readonly class CreateShiftDTO
{
    public function __construct(
        public int $tenantId,
        public int $employeeId,
        public CarbonImmutable $startTime,
        public CarbonImmutable $endTime,
        public ?string $location = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            employeeId: $data['employee_id'],
            startTime: CarbonImmutable::parse($data['start_time']),
            endTime: CarbonImmutable::parse($data['end_time']),
            location: $data['location'] ?? null,
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'start_time' => $this->startTime->toDateTimeString(),
            'end_time' => $this->endTime->toDateTimeString(),
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'metadata' => $this->metadata,
        ];
    }
}
