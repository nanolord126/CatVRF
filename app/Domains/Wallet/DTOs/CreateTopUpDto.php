<?php

declare(strict_types=1);

namespace App\Domains\Wallet\DTOs;

use Illuminate\Http\Request;

/**
 * DTO для пополнения кошелька.
 *
 * CANON 2026: final readonly, public properties, from(Request), toArray(), toAuditContext().
 */
final readonly class CreateTopUpDto
{
    public function __construct(
        public int $walletId,
        public int $tenantId,
        public ?int $businessGroupId,
        public int $amount,
        public string $correlationId,
        public ?string $idempotencyKey = null,
        public ?string $description = null,
    ) {}

    /** Создание из HTTP-запроса. */
    public static function from(Request $request): self
    {
        return new self(
            walletId: (int) $request->input('wallet_id'),
            tenantId: (int) $request->input('tenant_id'),
            businessGroupId: $request->filled('business_group_id') ? (int) $request->input('business_group_id') : null,
            amount: (int) $request->input('amount'),
            correlationId: $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            idempotencyKey: $request->input('idempotency_key'),
            description: $request->input('description'),
        );
    }

    /** Преобразование в массив для хранения. */
    public function toArray(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'amount' => $this->amount,
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->idempotencyKey,
            'description' => $this->description,
        ];
    }

    /** Контекст для аудит-лога. */
    public function toAuditContext(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'tenant_id' => $this->tenantId,
            'amount' => $this->amount,
            'correlation_id' => $this->correlationId,
        ];
    }
}
