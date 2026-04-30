<?php

declare(strict_types=1);

namespace Modules\Recommendation\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Modules\Recommendation\Domain\Entities\RecommendationItem;
use Modules\Recommendation\Domain\Enums\RecommendationScenario;
use Modules\Recommendation\Domain\Enums\RecommendationSource;
use Modules\Recommendation\Domain\Events\ImpressionTracked;
use Modules\Recommendation\Domain\Repositories\RecommendationRepositoryInterface;
use Modules\Recommendation\Domain\ValueObjects\RecommendationScore;

final readonly class ImpressionTrackingService
{
    use WithAuditLogging;

    public function __construct(
        private RecommendationRepositoryInterface $repository,
        private AuditService $audit,
    ) {}

    public function trackImpression(
        int $tenantId,
        int $userId,
        int $itemId,
        int $position,
        string $scenario,
        string $source,
        string $correlationId,
    ): void {
        try {
            $this->repository->saveImpression(
                $tenantId,
                $userId,
                $itemId,
                $position,
                $scenario,
                $source,
                $correlationId,
            );

            Event::dispatch(new ImpressionTracked(
                tenantId: $tenantId,
                userId: $userId,
                itemId: $itemId,
                position: $position,
                scenario: $scenario,
                source: $source,
                correlationId: $correlationId,
            ));

            $this->logAction('recommendation_impression', 'Recommendation', $itemId, [
                'user_id' => $userId,
                'position' => $position,
                'scenario' => $scenario,
                'source' => $source,
                'correlation_id' => $correlationId,
            ], $userId, $tenantId);
        } catch (\Throwable $e) {
            Log::error('Failed to track recommendation impression', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'item_id' => $itemId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
        }
    }

    public function trackClick(
        int $tenantId,
        int $userId,
        int $itemId,
        string $scenario,
        string $correlationId,
    ): void {
        try {
            $this->repository->saveClick(
                $tenantId,
                $userId,
                $itemId,
                $scenario,
                $correlationId,
            );

            $this->logAction('recommendation_click', 'Recommendation', $itemId, [
                'user_id' => $userId,
                'scenario' => $scenario,
                'correlation_id' => $correlationId,
            ], $userId, $tenantId);
        } catch (\Throwable $e) {
            Log::error('Failed to track recommendation click', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'item_id' => $itemId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
        }
    }

    public function trackConversion(
        int $tenantId,
        int $userId,
        int $itemId,
        float $revenue,
        string $scenario,
        string $correlationId,
    ): void {
        try {
            $this->repository->saveConversion(
                $tenantId,
                $userId,
                $itemId,
                $revenue,
                $scenario,
                $correlationId,
            );

            $this->logAction('recommendation_conversion', 'Recommendation', $itemId, [
                'user_id' => $userId,
                'revenue' => $revenue,
                'scenario' => $scenario,
                'correlation_id' => $correlationId,
            ], $userId, $tenantId);
        } catch (\Throwable $e) {
            Log::error('Failed to track recommendation conversion', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'item_id' => $itemId,
                'revenue' => $revenue,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
        }
    }

    public function trackBatchImpressions(int $tenantId, int $userId, array $impressions): void
    {
        foreach ($impressions as $impression) {
            $this->trackImpression(
                tenantId: $tenantId,
                userId: $userId,
                itemId: $impression['item_id'],
                position: $impression['position'],
                scenario: $impression['scenario'],
                source: $impression['source'] ?? RecommendationSource::TWO_TOWER->value,
                correlationId: $impression['correlation_id'] ?? $this->generateCorrelationId(),
            );
        }
    }

    public function getClickThroughRate(int $tenantId, string $scenario, int $hours = 24): float
    {
        $onlineMetrics = $this->repository->getOnlineMetrics($tenantId, $scenario, $hours);

        $impressions = $onlineMetrics['impressions'] ?? 0;
        $clicks = $onlineMetrics['clicks'] ?? 0;

        return $impressions > 0 ? $clicks / $impressions : 0.0;
    }

    public function getConversionRate(int $tenantId, string $scenario, int $hours = 24): float
    {
        $onlineMetrics = $this->repository->getOnlineMetrics($tenantId, $scenario, $hours);

        $clicks = $onlineMetrics['clicks'] ?? 0;
        $conversions = $onlineMetrics['conversions'] ?? 0;

        return $clicks > 0 ? $conversions / $clicks : 0.0;
    }

    public function getAverageRevenuePerConversion(int $tenantId, string $scenario, int $hours = 24): float
    {
        $onlineMetrics = $this->repository->getOnlineMetrics($tenantId, $scenario, $hours);

        $conversions = $onlineMetrics['conversions'] ?? 0;
        $totalRevenue = $onlineMetrics['total_revenue'] ?? 0.0;

        return $conversions > 0 ? $totalRevenue / $conversions : 0.0;
    }

    public function getScenarioPerformanceReport(int $tenantId, int $hours = 24): array
    {
        $scenarios = [
            RecommendationScenario::HOME_FEED->value,
            RecommendationScenario::PRODUCT_DETAIL->value,
            RecommendationScenario::SEARCH->value,
            RecommendationScenario::CART->value,
            RecommendationScenario::SELLER_PAGE->value,
        ];

        $report = [];

        foreach ($scenarios as $scenario) {
            $onlineMetrics = $this->repository->getOnlineMetrics($tenantId, $scenario, $hours);

            $impressions = $onlineMetrics['impressions'] ?? 0;
            $clicks = $onlineMetrics['clicks'] ?? 0;
            $conversions = $onlineMetrics['conversions'] ?? 0;
            $totalRevenue = $onlineMetrics['total_revenue'] ?? 0.0;

            $report[$scenario] = [
                'scenario' => $scenario,
                'impressions' => $impressions,
                'clicks' => $clicks,
                'conversions' => $conversions,
                'total_revenue' => round($totalRevenue, 2),
                'ctr' => $impressions > 0 ? round($clicks / $impressions, 4) : 0.0,
                'conversion_rate' => $clicks > 0 ? round($conversions / $clicks, 4) : 0.0,
                'revenue_per_impression' => $impressions > 0 ? round($totalRevenue / $impressions, 4) : 0.0,
                'revenue_per_click' => $clicks > 0 ? round($totalRevenue / $clicks, 2) : 0.0,
            ];
        }

        return $report;
    }

    public function getSourcePerformance(int $tenantId, int $hours = 24): array
    {
        $onlineMetrics = $this->repository->getOnlineMetrics($tenantId, 'all', $hours);
        $sourceBreakdown = $onlineMetrics['by_source'] ?? [];

        $performance = [];

        foreach ($sourceBreakdown as $source => $metrics) {
            $impressions = $metrics['impressions'] ?? 0;
            $clicks = $metrics['clicks'] ?? 0;
            $conversions = $metrics['conversions'] ?? 0;
            $totalRevenue = $metrics['total_revenue'] ?? 0.0;

            $performance[$source] = [
                'source' => $source,
                'impressions' => $impressions,
                'clicks' => $clicks,
                'conversions' => $conversions,
                'total_revenue' => round($totalRevenue, 2),
                'ctr' => $impressions > 0 ? round($clicks / $impressions, 4) : 0.0,
                'conversion_rate' => $clicks > 0 ? round($conversions / $clicks, 4) : 0.0,
            ];
        }

        return $performance;
    }

    private function generateCorrelationId(): string
    {
        return uniqid('track_', true);
    }
}
