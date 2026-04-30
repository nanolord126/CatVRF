<?php

declare(strict_types=1);

namespace Modules\Recommendation\Application\Services;

use Illuminate\Support\Facades\Facade;
use Modules\Recommendation\Application\DTOs\RecommendationRequestDTO;
use Modules\Recommendation\Application\DTOs\RecommendationResponseDTO;
use Modules\Recommendation\Domain\Enums\RecommendationScenario;
use Modules\Recommendation\Domain\Enums\RecommendationSource;

class RecommendationFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RecommendationOrchestrator::class;
    }

    public static function forUser(int $userId): self
    {
        $facade = new static();
        $facade->userId = $userId;

        return $facade;
    }

    public static function forSeller(int $sellerId): SellerRecommendationService
    {
        return app(SellerRecommendationService::class);
    }

    public static function tracking(): ImpressionTrackingService
    {
        return app(ImpressionTrackingService::class);
    }

    public function getHomeFeed(int $tenantId, int $limit = 20, array $context = [], ?string $vertical = null, ?string $correlationId = null): RecommendationResponseDTO
    {
        $request = RecommendationRequestDTO::forHomeFeed($tenantId, $this->userId ?? 0, $context, $correlationId);

        if ($vertical) {
            $request = new RecommendationRequestDTO(
                tenantId: $tenantId,
                userId: $this->userId ?? 0,
                scenario: RecommendationScenario::HOME_FEED,
                vertical: $vertical,
                limit: $limit,
                context: $context,
                correlationId: $correlationId,
            );
        }

        return static::getFacadeRoot()->getRecommendations($request);
    }

    public function getProductRecommendations(int $tenantId, int $itemId, int $limit = 12, array $context = [], ?string $correlationId = null): RecommendationResponseDTO
    {
        $request = RecommendationRequestDTO::forProductDetail($tenantId, $this->userId ?? 0, $itemId, $context, $correlationId);

        return static::getFacadeRoot()->getRecommendations($request);
    }

    public function getPersonalizedSearch(int $tenantId, string $query, int $limit = 30, array $context = [], ?string $vertical = null, ?string $correlationId = null): RecommendationResponseDTO
    {
        $request = RecommendationRequestDTO::forSearch($tenantId, $this->userId ?? 0, $query, $context, $correlationId);

        if ($vertical) {
            $request = new RecommendationRequestDTO(
                tenantId: $tenantId,
                userId: $this->userId ?? 0,
                scenario: RecommendationScenario::SEARCH,
                query: $query,
                vertical: $vertical,
                limit: $limit,
                context: $context,
                correlationId: $correlationId,
            );
        }

        return static::getFacadeRoot()->getRecommendations($request);
    }

    public function getSellerPageRecommendations(int $tenantId, int $sellerId, int $limit = 16, array $context = [], ?string $correlationId = null): RecommendationResponseDTO
    {
        $request = RecommendationRequestDTO::forSellerPage($tenantId, $this->userId ?? 0, $sellerId, $context, $correlationId);

        return static::getFacadeRoot()->getRecommendations($request);
    }

    public function getCartRecommendations(int $tenantId, int $limit = 6, array $context = [], ?string $correlationId = null): RecommendationResponseDTO
    {
        $request = RecommendationRequestDTO::forCart($tenantId, $this->userId ?? 0, $context, $correlationId);

        return static::getFacadeRoot()->getRecommendations($request);
    }

    public function getCategoryRecommendations(int $tenantId, string $vertical, int $limit = 24, array $context = [], ?string $correlationId = null): RecommendationResponseDTO
    {
        $request = new RecommendationRequestDTO(
            tenantId: $tenantId,
            userId: $this->userId ?? 0,
            scenario: RecommendationScenario::CATEGORY_BROWSE,
            vertical: $vertical,
            limit: $limit,
            context: $context,
            correlationId: $correlationId,
        );

        return static::getFacadeRoot()->getRecommendations($request);
    }

    public function getCheckoutUpsell(int $tenantId, int $limit = 4, array $context = [], ?string $correlationId = null): RecommendationResponseDTO
    {
        $request = new RecommendationRequestDTO(
            tenantId: $tenantId,
            userId: $this->userId ?? 0,
            scenario: RecommendationScenario::CHECKOUT_UPSELL,
            limit: $limit,
            context: $context,
            correlationId: $correlationId,
        );

        return static::getFacadeRoot()->getRecommendations($request);
    }

    public function getEmailDigest(int $tenantId, int $limit = 8, array $context = [], ?string $correlationId = null): RecommendationResponseDTO
    {
        $request = new RecommendationRequestDTO(
            tenantId: $tenantId,
            userId: $this->userId ?? 0,
            scenario: RecommendationScenario::EMAIL_DIGEST,
            limit: $limit,
            context: $context,
            correlationId: $correlationId,
        );

        return static::getFacadeRoot()->getRecommendations($request);
    }

    public function trackImpression(int $tenantId, int $itemId, int $position, string $scenario, string $source, string $correlationId): void
    {
        static::tracking()->trackImpression(
            $tenantId,
            $this->userId ?? 0,
            $itemId,
            $position,
            $scenario,
            $source,
            $correlationId,
        );
    }

    public function trackClick(int $tenantId, int $itemId, string $scenario, string $correlationId): void
    {
        static::tracking()->trackClick(
            $tenantId,
            $this->userId ?? 0,
            $itemId,
            $scenario,
            $correlationId,
        );
    }

    public function trackConversion(int $tenantId, int $itemId, float $revenue, string $scenario, string $correlationId): void
    {
        static::tracking()->trackConversion(
            $tenantId,
            $this->userId ?? 0,
            $itemId,
            $revenue,
            $scenario,
            $correlationId,
        );
    }

    public function invalidateCache(int $userId = null): void
    {
        $targetUserId = $userId ?? $this->userId ?? 0;

        if ($targetUserId > 0) {
            static::getFacadeRoot()->invalidateUserCache($targetUserId);
        }
    }

    public function getPerformanceMetrics(int $tenantId, string $scenario = null, int $hours = 24): array
    {
        if ($scenario) {
            $onlineMetrics = static::getFacadeRoot()->repository->getOnlineMetrics($tenantId, $scenario, $hours);

            return [
                'scenario' => $scenario,
                'hours' => $hours,
                'impressions' => $onlineMetrics['impressions'] ?? 0,
                'clicks' => $onlineMetrics['clicks'] ?? 0,
                'conversions' => $onlineMetrics['conversions'] ?? 0,
                'total_revenue' => $onlineMetrics['total_revenue'] ?? 0.0,
                'ctr' => ($onlineMetrics['impressions'] ?? 0) > 0
                    ? ($onlineMetrics['clicks'] ?? 0) / ($onlineMetrics['impressions'] ?? 1)
                    : 0.0,
                'conversion_rate' => ($onlineMetrics['clicks'] ?? 0) > 0
                    ? ($onlineMetrics['conversions'] ?? 0) / ($onlineMetrics['clicks'] ?? 1)
                    : 0.0,
            ];
        }

        return static::tracking()->getScenarioPerformanceReport($tenantId, $hours);
    }

    public function getModelHealth(int $tenantId): array
    {
        $orchestrator = static::getFacadeRoot();
        $mlInference = $orchestrator->mlInference;

        return [
            'model_version' => $mlInference->getModelVersion(),
            'health_check' => $mlInference->healthCheck(),
            'drift_report' => $orchestrator->repository->getLatestModelDriftReport('two_tower')?->toArray(),
            'last_checked' => now()->toIso8601String(),
        ];
    }

    public function getFairnessMetrics(int $tenantId): array
    {
        $fairnessConfig = \Modules\Recommendation\Domain\ValueObjects\FairnessConfig::default();

        return [
            'config' => $fairnessConfig->toArray(),
            'exposure_distribution' => [],
            'diversity_score' => 0.0,
            'last_evaluated' => now()->toIso8601String(),
        ];
    }

    public function withSource(RecommendationSource $source): self
    {
        $facade = clone $this;
        $facade->preferredSource = $source;

        return $facade;
    }

    public function withVertical(string $vertical): self
    {
        $facade = clone $this;
        $facade->vertical = $vertical;

        return $facade;
    }

    public function withContext(array $context): self
    {
        $facade = clone $this;
        $facade->context = $context;

        return $facade;
    }

    public function withCorrelationId(string $correlationId): self
    {
        $facade = clone $this;
        $facade->correlationId = $correlationId;

        return $facade;
    }

    private ?int $userId = null;
    private ?RecommendationSource $preferredSource = null;
    private ?string $vertical = null;
    private array $context = [];
    private ?string $correlationId = null;
}
