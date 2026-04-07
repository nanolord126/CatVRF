<?php

declare(strict_types=1);

namespace Modules\Commissions\Infrastructure\Persistence;

use Modules\Commissions\Domain\Entities\CommissionRule;
use Modules\Commissions\Domain\Repositories\CommissionRuleRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;

final class EloquentCommissionRuleRepository implements CommissionRuleRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1 hour

    public function findByVerticalAndTenant(string $vertical, int $tenantId): ?CommissionRule
    {
        $cacheKey = $this->getCacheKey($vertical, $tenantId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($vertical, $tenantId) {
            return CommissionRule::where('tenant_id', $tenantId)
                ->where('vertical', $vertical)
                ->where('is_active', true)
                ->first();
        });
    }

    public function save(CommissionRule $rule): CommissionRule
    {
        $rule->save();
        
        $this->clearCache($rule->vertical, $rule->tenant_id);

        return $rule;
    }

    public function getAllActiveForTenant(int $tenantId): Collection
    {
        $cacheKey = "commission_rules:tenant:{$tenantId}:all_active";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId) {
            return CommissionRule::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get();
        });
    }

    public function clearCache(string $vertical, int $tenantId): void
    {
        Cache::forget($this->getCacheKey($vertical, $tenantId));
        Cache::forget("commission_rules:tenant:{$tenantId}:all_active");
    }

    private function getCacheKey(string $vertical, int $tenantId): string
    {
        return "commission_rule:tenant:{$tenantId}:vertical:{$vertical}";
    }
}
