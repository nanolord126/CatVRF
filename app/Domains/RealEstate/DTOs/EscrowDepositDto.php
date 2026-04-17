<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class EscrowDepositDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $propertyId,
        public int $buyerId,
        public int $sellerId,
        public ?int $agentId,
        public int $buyerWalletId,
        public float $amount,
        public string $currency,
        public string $correlationId,
        public string $paymentMethod,
        public bool $isB2b,
        public ?array $splitConfig,
        public ?array $tags,
        public ?string $idempotencyKey,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) $request->header('X-Tenant-ID'),
            businessGroupId: $request->has('business_group_id') ? (int) $request->input('business_group_id') : null,
            propertyId: (int) $request->input('property_id'),
            buyerId: (int) $request->input('buyer_id'),
            sellerId: (int) $request->input('seller_id'),
            agentId: $request->has('agent_id') ? (int) $request->input('agent_id') : null,
            buyerWalletId: (int) $request->input('buyer_wallet_id'),
            amount: (float) $request->input('amount'),
            currency: $request->input('currency', 'RUB'),
            correlationId: $request->header('X-Correlation-ID') ?? (string) Str::uuid(),
            paymentMethod: $request->input('payment_method'),
            isB2b: $request->has('inn') && $request->has('business_card_id'),
            splitConfig: $request->input('split_config'),
            tags: $request->input('tags'),
            idempotencyKey: $request->header('X-Idempotency-Key'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'property_id' => $this->propertyId,
            'buyer_id' => $this->buyerId,
            'seller_id' => $this->sellerId,
            'agent_id' => $this->agentId,
            'buyer_wallet_id' => $this->buyerWalletId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'correlation_id' => $this->correlationId,
            'payment_method' => $this->paymentMethod,
            'is_b2b' => $this->isB2b,
            'split_config' => $this->splitConfig,
            'tags' => $this->tags,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }
}
