<?php

declare(strict_types=1);

namespace App\Domains\CRM\DTOs;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * CreateDealDto — DTO для создания сделки.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class CreateDealDto implements Castable
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $pipelineId,
        public int $stageId,
        public ?int $customerId,
        public string $title,
        public ?string $description = null,
        public int $value = 0,
        public string $currency = 'RUB',
        public string $status = 'new',
        public ?string $source = null,
        public int $priority = 3,
        public ?string $expectedCloseDate = null,
        public ?int $assignedToId = null,
        public ?string $contactPerson = null,
        public ?string $contactPhone = null,
        public ?string $contactEmail = null,
        public ?int $marketplaceOrderId = null,
        public ?array $metadata = null,
        public ?string $correlationId = null,
    ) {}

    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes
        {
            public function get($model, string $key, $value, array $attributes): ?CreateDealDto
            {
                if ($value === null) {
                    return null;
                }

                $data = json_decode($value, true);

                return new CreateDealDto(
                    tenantId: $data['tenantId'],
                    businessGroupId: $data['businessGroupId'] ?? null,
                    pipelineId: $data['pipelineId'],
                    stageId: $data['stageId'],
                    customerId: $data['customerId'] ?? null,
                    title: $data['title'],
                    description: $data['description'] ?? null,
                    value: $data['value'] ?? 0,
                    currency: $data['currency'] ?? 'RUB',
                    status: $data['status'] ?? 'new',
                    source: $data['source'] ?? null,
                    priority: $data['priority'] ?? 3,
                    expectedCloseDate: $data['expectedCloseDate'] ?? null,
                    assignedToId: $data['assignedToId'] ?? null,
                    contactPerson: $data['contactPerson'] ?? null,
                    contactPhone: $data['contactPhone'] ?? null,
                    contactEmail: $data['contactEmail'] ?? null,
                    marketplaceOrderId: $data['marketplaceOrderId'] ?? null,
                    metadata: $data['metadata'] ?? null,
                    correlationId: $data['correlationId'] ?? null,
                );
            }

            public function set($model, string $key, $value, array $attributes): string
            {
                return json_encode([
                    'tenantId' => $value->tenantId,
                    'businessGroupId' => $value->businessGroupId,
                    'pipelineId' => $value->pipelineId,
                    'stageId' => $value->stageId,
                    'customerId' => $value->customerId,
                    'title' => $value->title,
                    'description' => $value->description,
                    'value' => $value->value,
                    'currency' => $value->currency,
                    'status' => $value->status,
                    'source' => $value->source,
                    'priority' => $value->priority,
                    'expectedCloseDate' => $value->expectedCloseDate,
                    'assignedToId' => $value->assignedToId,
                    'contactPerson' => $value->contactPerson,
                    'contactPhone' => $value->contactPhone,
                    'contactEmail' => $value->contactEmail,
                    'marketplaceOrderId' => $value->marketplaceOrderId,
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
            'pipeline_id' => $this->pipelineId,
            'stage_id' => $this->stageId,
            'customer_id' => $this->customerId,
            'title' => $this->title,
            'description' => $this->description,
            'value' => $this->value,
            'currency' => $this->currency,
            'status' => $this->status,
            'source' => $this->source,
            'priority' => $this->priority,
            'expected_close_date' => $this->expectedCloseDate,
            'assigned_to_id' => $this->assignedToId,
            'contact_person' => $this->contactPerson,
            'contact_phone' => $this->contactPhone,
            'contact_email' => $this->contactEmail,
            'marketplace_order_id' => $this->marketplaceOrderId,
            'metadata' => $this->metadata,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            businessGroupId: $data['business_group_id'] ?? null,
            pipelineId: $data['pipeline_id'],
            stageId: $data['stage_id'],
            customerId: $data['customer_id'] ?? null,
            title: $data['title'],
            description: $data['description'] ?? null,
            value: $data['value'] ?? 0,
            currency: $data['currency'] ?? 'RUB',
            status: $data['status'] ?? 'new',
            source: $data['source'] ?? null,
            priority: $data['priority'] ?? 3,
            expectedCloseDate: $data['expected_close_date'] ?? null,
            assignedToId: $data['assigned_to_id'] ?? null,
            contactPerson: $data['contact_person'] ?? null,
            contactPhone: $data['contact_phone'] ?? null,
            contactEmail: $data['contact_email'] ?? null,
            marketplaceOrderId: $data['marketplace_order_id'] ?? null,
            metadata: $data['metadata'] ?? null,
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
