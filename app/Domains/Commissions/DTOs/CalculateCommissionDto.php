<?php declare(strict_types=1);

namespace App\Domains\Commissions\DTOs;

final readonly class CalculateCommissionDto
{
    public function __construct(
        public int $tenantId,
        public string $vertical,
        public int $amount,
        public bool $isB2B = false,
        public ?int $monthlyVolume = null,
        public ?string $correlationId = null,
        public ?string $b2bTier = null,
        public ?string $migrationSource = null,
        public ?bool $hasFleet = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            vertical: $data['vertical'],
            amount: $data['amount'],
            isB2B: $data['is_b2b'] ?? false,
            monthlyVolume: $data['monthly_volume'] ?? null,
            correlationId: $data['correlation_id'] ?? null,
            b2bTier: $data['b2b_tier'] ?? null,
            migrationSource: $data['migration_source'] ?? null,
            hasFleet: $data['has_fleet'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'vertical' => $this->vertical,
            'amount' => $this->amount,
            'is_b2b' => $this->isB2B,
            'monthly_volume' => $this->monthlyVolume,
            'correlation_id' => $this->correlationId,
            'b2b_tier' => $this->b2bTier,
            'migration_source' => $this->migrationSource,
            'has_fleet' => $this->hasFleet,
        ];
    }
}
