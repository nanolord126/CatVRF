<?php

declare(strict_types=1);

namespace App\Domains\CRM\DTOs;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * CreateTaskDto — DTO для создания задачи.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class CreateTaskDto implements Castable
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public ?int $dealId,
        public ?int $customerId,
        public ?int $assignedToId,
        public ?int $createdById,
        public string $title,
        public ?string $description = null,
        public string $type = 'custom',
        public string $status = 'pending',
        public string $priority = 'medium',
        public ?string $dueDate = null,
        public ?string $location = null,
        public ?array $metadata = null,
        public ?string $correlationId = null,
    ) {}

    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes
        {
            public function get($model, string $key, $value, array $attributes): ?CreateTaskDto
            {
                if ($value === null) {
                    return null;
                }

                $data = json_decode($value, true);

                return new CreateTaskDto(
                    tenantId: $data['tenantId'],
                    businessGroupId: $data['businessGroupId'] ?? null,
                    dealId: $data['dealId'] ?? null,
                    customerId: $data['customerId'] ?? null,
                    assignedToId: $data['assignedToId'] ?? null,
                    createdById: $data['createdById'] ?? null,
                    title: $data['title'],
                    description: $data['description'] ?? null,
                    type: $data['type'] ?? 'custom',
                    status: $data['status'] ?? 'pending',
                    priority: $data['priority'] ?? 'medium',
                    dueDate: $data['dueDate'] ?? null,
                    location: $data['location'] ?? null,
                    metadata: $data['metadata'] ?? null,
                    correlationId: $data['correlationId'] ?? null,
                );
            }

            public function set($model, string $key, $value, array $attributes): string
            {
                return json_encode([
                    'tenantId' => $value->tenantId,
                    'businessGroupId' => $value->businessGroupId,
                    'dealId' => $value->dealId,
                    'customerId' => $value->customerId,
                    'assignedToId' => $value->assignedToId,
                    'createdById' => $value->createdById,
                    'title' => $value->title,
                    'description' => $value->description,
                    'type' => $value->type,
                    'status' => $value->status,
                    'priority' => $value->priority,
                    'dueDate' => $value->dueDate,
                    'location' => $value->location,
                    'metadata' => $value->metadata,
                    'correlationId' => $value->correlationId,
                ]);
            }
        };
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'deal_id' => $this->dealId,
            'customer_id' => $this->customerId,
            'assigned_to_id' => $this->assignedToId,
            'created_by_id' => $this->createdById,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->dueDate,
            'location' => $this->location,
            'metadata' => $this->metadata,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            businessGroupId: $data['business_group_id'] ?? null,
            dealId: $data['deal_id'] ?? null,
            customerId: $data['customer_id'] ?? null,
            assignedToId: $data['assigned_to_id'] ?? null,
            createdById: $data['created_by_id'] ?? null,
            title: $data['title'],
            description: $data['description'] ?? null,
            type: $data['type'] ?? 'custom',
            status: $data['status'] ?? 'pending',
            priority: $data['priority'] ?? 'medium',
            dueDate: $data['due_date'] ?? null,
            location: $data['location'] ?? null,
            metadata: $data['metadata'] ?? null,
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
