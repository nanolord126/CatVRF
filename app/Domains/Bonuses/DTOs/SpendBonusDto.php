<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для траты бонусов.
 *
 * CANON 2026: final readonly, public properties, from(Request), toArray(), toAuditContext().
 */
final readonly class SpendBonusDto
{
    public function __construct(
        public int $userId,
        public int $tenantId,
        public int $amount,
        public ?string $reason = null,
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public string $correlationId,
        public ?string $idempotencyKey = null,
        public array $metadata = [],
    ) {}

    /** Создание из HTTP-запроса. */
    public static function from(Request $request): self
    {
        return new self(
            userId: (int) $request->input('user_id'),
            tenantId: (int) $request->input('tenant_id'),
            amount: (int) $request->input('amount'),
            reason: $request->input('reason'),
            sourceType: $request->input('source_type'),
            sourceId: $request->filled('source_id') ? (int) $request->input('source_id') : null,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
            idempotencyKey: $request->input('idempotency_key'),
            metadata: $request->input('metadata', []),
        );
    }

    /** Создание из массива (для Jobs и внутренних вызовов). */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            tenantId: (int) $data['tenant_id'],
            amount: (int) $data['amount'],
            reason: $data['reason'] ?? null,
            sourceType: $data['source_type'] ?? null,
            sourceId: isset($data['source_id']) ? (int) $data['source_id'] : null,
            correlationId: $data['correlation_id'] ?? Str::uuid()->toString(),
            idempotencyKey: $data['idempotency_key'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    /** Преобразование в массив для хранения. */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->idempotencyKey,
            'metadata' => $this->metadata,
        ];
    }

    /** Контекст для аудит-лога. */
    public function toAuditContext(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'correlation_id' => $this->correlationId,
        ];
    }

    /** Проверить валидность DTO. */
    public function validate(): bool
    {
        return $this->amount > 0
            && $this->userId > 0
            && $this->tenantId > 0;
    }
}
