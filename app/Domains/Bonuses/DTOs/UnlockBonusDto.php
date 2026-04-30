<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для разблокировки бонусов.
 * 
 * Используется для мгновенной разблокировки или ежедневного анлока.
 */
final readonly class UnlockBonusDto
{
    public function __construct(
        public int $userId,
        public int $tenantId,
        public int $batchId,
        public bool $instant = false,
        public ?float $instantUnlockPrice = null,
        public ?string $correlationId = null,
        public array $metadata = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (int) $request->input('user_id'),
            tenantId: (int) $request->input('tenant_id'),
            batchId: (int) $request->input('batch_id'),
            instant: (bool) $request->input('instant', false),
            instantUnlockPrice: $request->filled('instant_unlock_price') 
                ? (float) $request->input('instant_unlock_price') 
                : null,
            correlationId: $request->header('X-Correlation-ID') ?? Str::uuid()->toString(),
            metadata: $request->input('metadata', []),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            tenantId: (int) $data['tenant_id'],
            batchId: (int) $data['batch_id'],
            instant: $data['instant'] ?? false,
            instantUnlockPrice: isset($data['instant_unlock_price']) 
                ? (float) $data['instant_unlock_price'] 
                : null,
            correlationId: $data['correlation_id'] ?? Str::uuid()->toString(),
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'batch_id' => $this->batchId,
            'instant' => $this->instant,
            'instant_unlock_price' => $this->instantUnlockPrice,
            'correlation_id' => $this->correlationId,
            'metadata' => $this->metadata,
        ];
    }

    public function toAuditContext(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'batch_id' => $this->batchId,
            'instant' => $this->instant,
            'instant_unlock_price' => $this->instantUnlockPrice,
            'correlation_id' => $this->correlationId,
        ];
    }

    public function validate(): bool
    {
        return $this->userId > 0
            && $this->tenantId > 0
            && $this->batchId > 0;
    }
}
