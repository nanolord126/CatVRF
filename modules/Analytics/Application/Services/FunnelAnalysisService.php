<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Modules\Analytics\Application\DTOs\FunnelDto;
use Modules\Analytics\Application\DTOs\FunnelStepDto;
use Modules\Analytics\Domain\ValueObjects\Period;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;

/**
 * Funnel Analysis Service
 *
 * Handles funnel analysis for conversion tracking.
 * Follows Single Responsibility Principle - only handles funnel queries.
 */
final readonly class FunnelAnalysisService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Get funnel analysis.
     */
    public function getFunnel(
        string $funnelName,
        Period $period,
        int $tenantId,
        ?string $category = null,
        ?int $sellerId = null,
    ): FunnelDto {
        // Fraud check for funnel queries
        // TODO: Integrate with FraudDetectionService when available

        $cacheKey = $this->getFunnelCacheKey($funnelName, $period, $tenantId, $category, $sellerId);
        
        return $this->cache->tags(["analytics:{$tenantId}"])->remember($cacheKey, 600, function () use ($funnelName, $period, $tenantId, $category, $sellerId) {
            $query = $this->db->table('analytics_funnels')
                ->where('tenant_id', $tenantId)
                ->where('funnel_name', $funnelName)
                ->whereBetween('date', [$period->from()->toDateString(), $period->to()->toDateString()]);

            if ($category !== null) {
                $query->where('category', $category);
            }

            if ($sellerId !== null) {
                $query->where('seller_id', $sellerId);
            }

            $result = $query->first();

            if ($result === null) {
                // Calculate funnel on-the-fly if not pre-calculated
                return $this->calculateFunnel($funnelName, $period, $tenantId, $category, $sellerId);
            }

            $steps = json_decode($result->steps, true);
            $funnelSteps = array_map(fn ($step) => FunnelStepDto::create(
                $step['name'],
                $step['count'],
                $step['conversion_rate'] ?? null,
                $step['drop_off_rate'] ?? null,
            ), $steps);

            return FunnelDto::create($funnelName, $period, $funnelSteps, $tenantId);
        });
    }

    /**
     * Calculate funnel on-the-fly.
     */
    private function calculateFunnel(
        string $funnelName,
        Period $period,
        int $tenantId,
        ?string $category,
        ?int $sellerId,
    ): FunnelDto {
        $steps = match ($funnelName) {
            'checkout' => [
                ['name' => 'add_to_cart', 'count' => $this->getEventCount('product.added_to_cart', $period, $tenantId)],
                ['name' => 'checkout_started', 'count' => $this->getEventCount('checkout.started', $period, $tenantId)],
                ['name' => 'payment_initiated', 'count' => $this->getEventCount('payment.initiated', $period, $tenantId)],
                ['name' => 'order_completed', 'count' => $this->getEventCount('order.completed', $period, $tenantId)],
            ],
            default => [],
        };

        $funnelSteps = [];
        $previousCount = null;

        foreach ($steps as $step) {
            $conversionRate = $previousCount !== null && $previousCount > 0
                ? ($step['count'] / $previousCount) * 100
                : null;
            
            $dropOffRate = $previousCount !== null && $previousCount > 0
                ? (($previousCount - $step['count']) / $previousCount) * 100
                : null;

            $funnelSteps[] = FunnelStepDto::create(
                $step['name'],
                $step['count'],
                $conversionRate,
                $dropOffRate,
            );

            $previousCount = $step['count'];
        }

        return FunnelDto::create($funnelName, $period, $funnelSteps, $tenantId);
    }

    /**
     * Get event count for funnel calculation.
     */
    private function getEventCount(string $eventType, Period $period, int $tenantId): int
    {
        return $this->db->table('analytics_events')
            ->where('tenant_id', $tenantId)
            ->where('event_type', $eventType)
            ->whereBetween('occurred_at', [$period->from(), $period->to()])
            ->count();
    }

    /**
     * Get cache key for funnel.
     */
    private function getFunnelCacheKey(
        string $funnelName,
        Period $period,
        int $tenantId,
        ?string $category = null,
        ?int $sellerId = null,
    ): string {
        $key = "analytics:funnel:{$funnelName}:{$tenantId}:{$period}";
        
        if ($category !== null) {
            $key .= ":category:{$category}";
        }
        if ($sellerId !== null) {
            $key .= ":seller:{$sellerId}";
        }

        return $key;
    }
}
