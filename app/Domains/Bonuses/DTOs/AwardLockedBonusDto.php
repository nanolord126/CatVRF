<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\DTOs;

use App\Domains\Bonuses\ValueObjects\BonusSource;
use App\Domains\Bonuses\ValueObjects\VestingCurve;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для начисления заблокированных бонусов с 15-дневным Smart Hold.
 * 
 * Используется для CatFloat Rewards - бонусы с ежедневным анлоком.
 */
final readonly class AwardLockedBonusDto
{
    public function __construct(
        public int $userId,
        public int $tenantId,
        public float $amount,
        public BonusSource $source,
        public VestingCurve $vestingCurve,
        public ?string $correlationId = null,
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public array $metadata = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (int) $request->input('user_id'),
            tenantId: (int) $request->input('tenant_id'),
            amount: (float) $request->input('amount'),
            source: BonusSource::fromString($request->input('source', 'purchase')),
            vestingCurve: VestingCurve::fromString($request->input('vesting_curve', 'linear_15_days')),
            correlationId: $request->header('X-Correlation-ID') ?? Str::uuid()->toString(),
            sourceType: $request->input('source_type'),
            sourceId: $request->filled('source_id') ? (int) $request->input('source_id') : null,
            metadata: $request->input('metadata', []),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            tenantId: (int) $data['tenant_id'],
            amount: (float) $data['amount'],
            source: $data['source'] instanceof BonusSource 
                ? $data['source'] 
                : BonusSource::fromString($data['source']),
            vestingCurve: $data['vesting_curve'] instanceof VestingCurve
                ? $data['vesting_curve']
                : VestingCurve::fromString($data['vesting_curve']),
            correlationId: $data['correlation_id'] ?? Str::uuid()->toString(),
            sourceType: $data['source_type'] ?? null,
            sourceId: isset($data['source_id']) ? (int) $data['source_id'] : null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'amount' => $this->amount,
            'source' => $this->source->getValue(),
            'vesting_curve' => $this->vestingCurve->getType(),
            'correlation_id' => $this->correlationId,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'metadata' => $this->metadata,
        ];
    }

    public function toAuditContext(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'amount' => $this->amount,
            'source' => $this->source->getValue(),
            'vesting_curve' => $this->vestingCurve->getType(),
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'correlation_id' => $this->correlationId,
        ];
    }

    public function validate(): bool
    {
        return $this->amount > 0
            && $this->userId > 0
            && $this->tenantId > 0;
    }
}
