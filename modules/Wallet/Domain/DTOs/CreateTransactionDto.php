<?php

declare(strict_types=1);

namespace Modules\Wallet\Domain\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Wallet\Domain\Enums\BalanceTransactionType;

final readonly class CreateTransactionDto
{
    public function __construct(
        public int $walletId,
        public int $tenantId,
        public ?int $businessGroupId,
        public int $amount,
        public BalanceTransactionType $type,
        public string $correlationId,
        public ?string $idempotencyKey = null,
        public ?string $description = null,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            walletId: (int) $request->input('wallet_id'),
            tenantId: (int) $request->input('tenant_id'),
            businessGroupId: $request->filled('business_group_id') ? (int) $request->input('business_group_id') : null,
            amount: (int) $request->input('amount'),
            type: BalanceTransactionType::from($request->input('type')),
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
            idempotencyKey: $request->input('idempotency_key'),
            description: $request->input('description'),
        );
    }

    public function toArray(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'amount' => $this->amount,
            'type' => $this->type->value,
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->idempotencyKey,
            'description' => $this->description,
        ];
    }

    public function toAuditContext(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'tenant_id' => $this->tenantId,
            'amount' => $this->amount,
            'type' => $this->type->value,
            'correlation_id' => $this->correlationId,
        ];
    }
}
