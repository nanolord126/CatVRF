<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

use Carbon\CarbonImmutable;

/**
 * CreateManagerKPIDTO — DTO для создания KPI периода менеджера
 */
final readonly class CreateManagerKPIDTO
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $managerId,
        public ?int $verticalId,
        public string $periodType,
        public CarbonImmutable $periodStart,
        public CarbonImmutable $periodEnd,
        public array $targets,
        public string $businessType,
        public ?array $metadata,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            businessGroupId: $data['business_group_id'] ?? null,
            managerId: $data['manager_id'],
            verticalId: $data['vertical_id'] ?? null,
            periodType: $data['period_type'] ?? 'monthly',
            periodStart: CarbonImmutable::parse($data['period_start']),
            periodEnd: CarbonImmutable::parse($data['period_end']),
            targets: $data['targets'] ?? [],
            businessType: $data['business_type'] ?? 'both',
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'manager_id' => $this->managerId,
            'vertical_id' => $this->verticalId,
            'period_type' => $this->periodType,
            'period_start' => $this->periodStart->toDateString(),
            'period_end' => $this->periodEnd->toDateString(),
            'targets' => $this->targets,
            'business_type' => $this->businessType,
            'metadata' => $this->metadata,
        ];
    }
}
