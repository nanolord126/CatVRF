<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Entities;

use Carbon\Carbon;

/**
 * Publisher Sub-Account Entity
 *
 * Represents a sub-account under a publisher for multi-tenant management.
 * Allows granular control of permissions, inventory, and revenue sharing.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class PublisherSubAccount
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $publisher_id,
        public string $name,
        public string $email,
        public string $status,
        public array $permissions,
        public float $revenue_share,
        public int $monthly_quota,
        public Carbon $created_at,
        public ?Carbon $verified_at,
        public ?string $correlation_id,
    ) {}

    public static function create(
        int $publisherId,
        string $name,
        string $email,
        array $permissions,
        float $revenueShare = 0.5,
        int $monthlyQuota = 1000000,
        ?string $correlationId = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) str()->uuid(),
            publisher_id: $publisherId,
            name: $name,
            email: $email,
            status: 'pending',
            permissions: $permissions,
            revenue_share: $revenueShare,
            monthly_quota: $monthlyQuota,
            created_at: now(),
            verified_at: null,
            correlation_id: $correlationId,
        );
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

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function canTransitionTo(string $status): bool
    {
        return match (true) {
            $this->status === 'pending' => in_array($status, ['active', 'suspended', 'cancelled']),
            $this->status === 'active' => in_array($status, ['suspended', 'cancelled']),
            $this->status === 'suspended' => in_array($status, ['active', 'cancelled']),
            default => false,
        };
    }

    public function calculateQuotaUsage(int $usedImpressions): array
    {
        $usagePercent = $this->monthly_quota > 0
            ? ($usedImpressions / $this->monthly_quota) * 100
            : 0;

        return [
            'quota' => $this->monthly_quota,
            'used' => $usedImpressions,
            'remaining' => max(0, $this->monthly_quota - $usedImpressions),
            'usage_percent' => $usagePercent,
            'over_quota' => $usedImpressions > $this->monthly_quota,
        ];
    }
}
