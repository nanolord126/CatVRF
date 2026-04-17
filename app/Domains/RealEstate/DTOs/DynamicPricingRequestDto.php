<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\DTOs;

use Illuminate\Http\Request;

final readonly class DynamicPricingRequestDto
{
    public function __construct(
        public readonly int $tenantId,
        public readonly ?int $businessGroupId,
        public readonly int $userId,
        public readonly string $correlationId,
        public readonly int $propertyId,
        public readonly bool $isB2B,
        public readonly ?string $currency = 'RUB',
        public readonly ?array $marketFactors = null
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) tenant()?->id ?? $request->input('tenant_id'),
            businessGroupId: $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            userId: (int) $request->user()?->id ?? $request->input('user_id'),
            correlationId: $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            propertyId: (int) $request->route('propertyId'),
            isB2B: $request->has('inn') && $request->has('business_card_id'),
            currency: $request->input('currency', 'RUB'),
            marketFactors: $request->input('market_factors')
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
            'is_b2b' => $this->isB2B,
            'currency' => $this->currency,
            'market_factors' => $this->marketFactors,
        ];
    }
}
