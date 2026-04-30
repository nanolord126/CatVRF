<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для операций с Float Yield.
 * 
 * Используется для расчета и начисления yield от заблокированных бонусов.
 */
final readonly class FloatYieldDto
{
    public function __construct(
        public int $userId,
        public int $tenantId,
        public float $totalFloat,
        public float $platformYield,
        public float $userYield,
        public float $platformRate,
        public float $userRate,
        public string $yieldDate,
        public ?int $lockedBatchId,
        public ?string $correlationId = null,
        public array $metadata = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (int) $request->input('user_id'),
            tenantId: (int) $request->input('tenant_id'),
            totalFloat: (float) $request->input('total_float'),
            platformYield: (float) $request->input('platform_yield'),
            userYield: (float) $request->input('user_yield'),
            platformRate: (float) $request->input('platform_rate'),
            userRate: (float) $request->input('user_rate'),
            yieldDate: $request->input('yield_date', now()->toDateString()),
            lockedBatchId: $request->filled('locked_batch_id') ? (int) $request->input('locked_batch_id') : null,
            correlationId: $request->header('X-Correlation-ID') ?? Str::uuid()->toString(),
            metadata: $request->input('metadata', []),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            tenantId: (int) $data['tenant_id'],
            totalFloat: (float) $data['total_float'],
            platformYield: (float) $data['platform_yield'],
            userYield: (float) $data['user_yield'],
            platformRate: (float) $data['platform_rate'],
            userRate: (float) $data['user_rate'],
            yieldDate: $data['yield_date'] ?? now()->toDateString(),
            lockedBatchId: isset($data['locked_batch_id']) ? (int) $data['locked_batch_id'] : null,
            correlationId: $data['correlation_id'] ?? Str::uuid()->toString(),
            metadata: $data['metadata'] ?? [],
        );
    }

    public static function fromCalculation(
        int $userId,
        int $tenantId,
        float $totalFloat,
        array $rates,
        ?int $lockedBatchId = null,
        ?string $correlationId = null,
    ): self {
        $platformYield = $totalFloat * $rates['platform_rate'];
        $userYield = $totalFloat * $rates['user_rate'];

        return new self(
            userId: $userId,
            tenantId: $tenantId,
            totalFloat: $totalFloat,
            platformYield: $platformYield,
            userYield: $userYield,
            platformRate: $rates['platform_rate'],
            userRate: $rates['user_rate'],
            yieldDate: now()->toDateString(),
            lockedBatchId: $lockedBatchId,
            correlationId: $correlationId ?? Str::uuid()->toString(),
            metadata: [],
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'total_float' => $this->totalFloat,
            'platform_yield' => $this->platformYield,
            'user_yield' => $this->userYield,
            'platform_rate' => $this->platformRate,
            'user_rate' => $this->userRate,
            'yield_date' => $this->yieldDate,
            'locked_batch_id' => $this->lockedBatchId,
            'correlation_id' => $this->correlationId,
            'metadata' => $this->metadata,
        ];
    }

    public function toAuditContext(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'total_float' => $this->totalFloat,
            'platform_yield' => $this->platformYield,
            'user_yield' => $this->userYield,
            'platform_rate' => $this->platformRate,
            'user_rate' => $this->userRate,
            'yield_date' => $this->yieldDate,
            'correlation_id' => $this->correlationId,
        ];
    }

    public function getTotalYield(): float
    {
        return $this->platformYield + $this->userYield;
    }

    public function validate(): bool
    {
        return $this->userId > 0
            && $this->tenantId > 0
            && $this->totalFloat >= 0
            && $this->platformYield >= 0
            && $this->userYield >= 0;
    }
}
