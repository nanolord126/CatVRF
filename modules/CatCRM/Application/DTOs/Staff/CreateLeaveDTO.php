<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\DTOs\Staff;

use Carbon\CarbonImmutable;

/**
 * CreateLeaveDTO — DTO для создания отпуска/больничного
 * 
 * Immutable DTO following CatVRF rules
 */
final readonly class CreateLeaveDTO
{
    public function __construct(
        public int $tenantId,
        public int $employeeId,
        public string $type,
        public CarbonImmutable $startDate,
        public CarbonImmutable $endDate,
        public string $reason,
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            employeeId: $data['employee_id'],
            type: $data['type'],
            startDate: CarbonImmutable::parse($data['start_date']),
            endDate: CarbonImmutable::parse($data['end_date']),
            reason: $data['reason'],
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'type' => $this->type,
            'start_date' => $this->startDate->toDateTimeString(),
            'end_date' => $this->endDate->toDateTimeString(),
            'reason' => $this->reason,
            'metadata' => $this->metadata,
        ];
    }
}
