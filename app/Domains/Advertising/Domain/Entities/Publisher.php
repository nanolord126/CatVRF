<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Entities;

use Carbon\Carbon;

/**
 * Publisher Domain Entity
 *
 * Represents a publisher in the ad exchange.
 * Publishers provide ad inventory that advertisers bid on.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class Publisher
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenant_id,
        public string $name,
        public string $website_url,
        public string $status, // active, suspended, pending
        public float $commission_rate, // e.g., 0.15 for 15%
        public int $payout_threshold, // in kopeks
        public string $api_key,
        public ?string $webhook_url,
        public string $integration_type, // direct, ssp, dsp
        public Carbon $verified_at,
        public Carbon $last_payout_at,
        public string $correlation_id,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        string $websiteUrl,
        float $commissionRate,
        int $payoutThreshold,
        string $integrationType,
        ?string $webhookUrl = null,
        ?string $correlationId = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            tenant_id: $tenantId,
            name: $name,
            website_url: $websiteUrl,
            status: 'pending',
            commission_rate: $commissionRate,
            payout_threshold: $payoutThreshold,
            api_key: self::generateApiKey(),
            webhook_url: $webhookUrl,
            integration_type: $integrationType,
            verified_at: Carbon::now(),
            last_payout_at: Carbon::now(),
            correlation_id: $correlationId ?? (string) \Illuminate\Support\Str::uuid(),
        );
    }

    public static function generateApiKey(): string
    {
        return 'pub_' . bin2hex(random_bytes(24));
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $validTransitions = [
            'pending' => ['active', 'suspended'],
            'active' => ['suspended'],
            'suspended' => ['active'],
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? [], true);
    }

    public function calculateRevenue(int $grossRevenue): int
    {
        return (int) ($grossRevenue * (1 - $this->commission_rate));
    }

    public function isPayoutEligible(int $currentBalance): bool
    {
        return $currentBalance >= $this->payout_threshold;
    }

    public function regenerateApiKey(): string
    {
        return self::generateApiKey();
    }
}
