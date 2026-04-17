<?php declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Log\LogManager;

/**
 * Tenant Quota Plan Service
 *
 * Production 2026 CANON - Quota Plan Management
 *
 * Manages differentiated quota plans per tenant subscription tier:
 * - Free: Limited quotas, suitable for testing
 * - Starter: Basic quotas for small businesses
 * - Pro: Enhanced quotas for growing businesses
 * - Enterprise: Unlimited or very high quotas for large enterprises
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final readonly class TenantQuotaPlanService
{
    private const QUOTA_PREFIX = 'tenant:quota:custom:';

    public function __construct(
        private readonly RedisFactory $redis,
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,
    ) {}

    /**
     * Apply quota plan to tenant
     */
    public function applyPlan(int $tenantId, string $plan): void
    {
        $quotas = $this->getPlanQuotas($plan);

        foreach ($quotas as $resource => $quota) {
            $this->setQuota($resource, $tenantId, $quota);
        }

        $this->logger->info('Quota plan applied to tenant', [
            'tenant_id' => $tenantId,
            'plan' => $plan,
            'quotas' => $quotas,
        ]);
    }

    /**
     * Get quota configuration for a plan
     */
    public function getPlanQuotas(string $plan): array
    {
        return match($plan) {
            'free' => [
                'ai_tokens' => 10000,           // 10K tokens/day
                'redis_ops' => 1000,           // 1K ops/hour
                'db_queries' => 500,           // 500 queries/hour
                'storage_bytes' => 100 * 1024 * 1024, // 100MB/day
                'vertical_medical_diagnosis' => 10,   // 10 diagnoses/day
                'vertical_delivery_routing' => 20,    // 20 routings/day
            ],
            'starter' => [
                'ai_tokens' => 100000,          // 100K tokens/day
                'redis_ops' => 10000,           // 10K ops/hour
                'db_queries' => 5000,           // 5K queries/hour
                'storage_bytes' => 1024 * 1024 * 1024, // 1GB/day
                'vertical_medical_diagnosis' => 100,  // 100 diagnoses/day
                'vertical_delivery_routing' => 200,   // 200 routings/day
            ],
            'pro' => [
                'ai_tokens' => 1000000,         // 1M tokens/day
                'redis_ops' => 100000,          // 100K ops/hour
                'db_queries' => 50000,          // 50K queries/hour
                'storage_bytes' => 10 * 1024 * 1024 * 1024, // 10GB/day
                'vertical_medical_diagnosis' => 1000, // 1K diagnoses/day
                'vertical_delivery_routing' => 2000,  // 2K routings/day
            ],
            'enterprise' => [
                'ai_tokens' => PHP_INT_MAX,     // Unlimited
                'redis_ops' => PHP_INT_MAX,     // Unlimited
                'db_queries' => PHP_INT_MAX,    // Unlimited
                'storage_bytes' => 1024 * 1024 * 1024 * 1024, // 1TB/day
                'vertical_medical_diagnosis' => PHP_INT_MAX, // Unlimited
                'vertical_delivery_routing' => PHP_INT_MAX, // Unlimited
            ],
            default => $this->getDefaultQuotas(),
        };
    }

    /**
     * Get default quotas (fallback)
     */
    private function getDefaultQuotas(): array
    {
        return [
            'ai_tokens' => 1000000,
            'redis_ops' => 100000,
            'db_queries' => 50000,
            'storage_bytes' => 10 * 1024 * 1024 * 1024,
        ];
    }

    /**
     * Set quota for a specific resource
     */
    private function setQuota(string $resourceType, int $tenantId, int $quota): void
    {
        $key = self::QUOTA_PREFIX . "{$resourceType}:{$tenantId}";
        $this->redis->connection()->set($key, $quota);
    }

    /**
     * Get tenant's current plan
     */
    public function getTenantPlan(int $tenantId): string
    {
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            return 'free';
        }

        $meta = is_string($tenant->meta) ? json_decode($tenant->meta, true) : $tenant->meta;
        
        return $meta['quota_plan'] ?? 'free';
    }

    /**
     * Upgrade tenant to a higher plan
     */
    public function upgradePlan(int $tenantId, string $newPlan): bool
    {
        $currentPlan = $this->getTenantPlan($tenantId);
        
        if ($currentPlan === $newPlan) {
            return false;
        }

        $this->applyPlan($tenantId, $newPlan);

        // Update tenant meta
        $tenant = Tenant::find($tenantId);
        if ($tenant) {
            $meta = is_string($tenant->meta) ? json_decode($tenant->meta, true) : $tenant->meta;
            $meta['quota_plan'] = $newPlan;
            $meta['quota_plan_upgraded_at'] = now()->toIso8601String();
            $tenant->update(['meta' => $meta]);
        }

        $this->logger->info('Tenant quota plan upgraded', [
            'tenant_id' => $tenantId,
            'from_plan' => $currentPlan,
            'to_plan' => $newPlan,
        ]);

        return true;
    }

    /**
     * Get available plans
     */
    public function getAvailablePlans(): array
    {
        return [
            'free' => [
                'name' => 'Free',
                'description' => 'Limited quotas for testing',
                'price' => 0,
            ],
            'starter' => [
                'name' => 'Starter',
                'description' => 'Basic quotas for small businesses',
                'price' => 4900, // RUB per month
            ],
            'pro' => [
                'name' => 'Pro',
                'description' => 'Enhanced quotas for growing businesses',
                'price' => 14900, // RUB per month
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'description' => 'Unlimited quotas for large enterprises',
                'price' => null, // Contact sales
            ],
        ];
    }
}
