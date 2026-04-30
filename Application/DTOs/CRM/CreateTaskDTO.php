<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

use Carbon\CarbonImmutable;

/**
 * CreateTaskDTO — DTO для создания задачи в CRM
 */
final readonly class CreateTaskDTO
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public ?int $verticalId,
        public ?int $dealId,
        public ?int $customerId,
        public ?int $supplierId,
        public ?int $tenderId,
        public ?int $b2bOrderId,
        public int $assignedToId,
        public ?int $createdById,
        public string $title,
        public ?string $description,
        public ?string $category,
        public string $type,
        public string $priority,
        public string $status,
        public ?CarbonImmutable $dueDate,
        public ?string $location,
        public ?string $businessType,
        public float $kpiWeight,
        public bool $kpiTracked,
        public ?CarbonImmutable $kpiPeriodStart,
        public ?CarbonImmutable $kpiPeriodEnd,
        public ?int $parentTaskId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            businessGroupId: $data['business_group_id'] ?? null,
            verticalId: $data['vertical_id'] ?? null,
            dealId: $data['deal_id'] ?? null,
            customerId: $data['customer_id'] ?? null,
            supplierId: $data['supplier_id'] ?? null,
            tenderId: $data['tender_id'] ?? null,
            b2bOrderId: $data['b2b_order_id'] ?? null,
            assignedToId: $data['assigned_to_id'],
            createdById: $data['created_by_id'] ?? null,
            title: $data['title'],
            description: $data['description'] ?? null,
            category: $data['category'] ?? null,
            type: $data['type'] ?? 'custom',
            priority: $data['priority'] ?? 'medium',
            status: $data['status'] ?? 'pending',
            dueDate: isset($data['due_date']) ? CarbonImmutable::parse($data['due_date']) : null,
            location: $data['location'] ?? null,
            businessType: $data['business_type'] ?? null,
            kpiWeight: $data['kpi_weight'] ?? 1.0,
            kpiTracked: $data['kpi_tracked'] ?? false,
            kpiPeriodStart: isset($data['kpi_period_start']) ? CarbonImmutable::parse($data['kpi_period_start']) : null,
            kpiPeriodEnd: isset($data['kpi_period_end']) ? CarbonImmutable::parse($data['kpi_period_end']) : null,
            parentTaskId: $data['parent_task_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'vertical_id' => $this->verticalId,
            'deal_id' => $this->dealId,
            'customer_id' => $this->customerId,
            'supplier_id' => $this->supplierId,
            'tender_id' => $this->tenderId,
            'b2b_order_id' => $this->b2bOrderId,
            'assigned_to_id' => $this->assignedToId,
            'created_by_id' => $this->createdById,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'type' => $this->type,
            'priority' => $this->priority,
            'status' => $this->status,
            'due_date' => $this->dueDate?->toDateTimeString(),
            'location' => $this->location,
            'business_type' => $this->businessType,
            'kpi_weight' => $this->kpiWeight,
            'kpi_tracked' => $this->kpiTracked,
            'kpi_period_start' => $this->kpiPeriodStart?->toDateTimeString(),
            'kpi_period_end' => $this->kpiPeriodEnd?->toDateTimeString(),
            'parent_task_id' => $this->parentTaskId,
        ];
    }
}
