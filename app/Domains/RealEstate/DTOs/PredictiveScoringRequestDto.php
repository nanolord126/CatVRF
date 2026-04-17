<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\DTOs;

use Illuminate\Http\Request;

final readonly class PredictiveScoringRequestDto
{
    public function __construct(
        public readonly int $tenantId,
        public readonly ?int $businessGroupId,
        public readonly int $userId,
        public readonly string $correlationId,
        public readonly int $propertyId,
        public readonly ?int $agentId = null,
        public readonly ?array $additionalData = null
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) tenant()?->id ?? $request->input('tenant_id'),
            businessGroupId: $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            userId: (int) $request->user()?->id ?? $request->input('user_id'),
            correlationId: $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            propertyId: (int) $request->route('propertyId'),
            agentId: $request->input('agent_id') ? (int) $request->input('agent_id') : null,
            additionalData: $request->input('additional_data')
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id' => $this->userId,
            'correlation_id' => $this->correlationId,
            'property_id' => $this->propertyId,
            'agent_id' => $this->agentId,
            'additional_data' => $this->additionalData,
        ];
    }
}
