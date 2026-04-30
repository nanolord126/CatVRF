<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Repositories;

use Modules\Recommendation\Domain\Entities\RecommendationItem;
use Modules\Recommendation\Domain\Entities\ModelDriftReport;
use Modules\Recommendation\Domain\Enums\RecommendationScenario;
use Modules\Recommendation\Domain\ValueObjects\FeatureVector;

interface RecommendationRepositoryInterface
{
    public function saveLog(RecommendationItem $item): void;

    public function saveImpression(int $tenantId, int $userId, int $itemId, int $position, string $scenario, string $source, string $correlationId): void;

    public function saveClick(int $tenantId, int $userId, int $itemId, string $scenario, string $correlationId): void;

    public function saveConversion(int $tenantId, int $userId, int $itemId, float $revenue, string $scenario, string $correlationId): void;

    public function getUserFeatures(int $tenantId, int $userId): FeatureVector;

    public function getItemFeatures(int $tenantId, int $itemId): FeatureVector;

    public function getSellerFeatures(int $tenantId, int $sellerId): FeatureVector;

    public function getContextFeatures(int $tenantId, string $scenario, array $context): FeatureVector;

    public function getCandidateItems(int $tenantId, FeatureVector $userEmbedding, int $limit = 200, ?string $vertical = null): array;

    public function getTrendingItems(int $tenantId, int $limit = 20, ?string $vertical = null): array;

    public function getFrequentlyBoughtTogether(int $tenantId, int $itemId, int $limit = 10): array;

    public function getSimilarItems(int $tenantId, int $itemId, int $limit = 12): array;

    public function saveModelDriftReport(ModelDriftReport $report): void;

    public function getLatestModelDriftReport(string $modelType): ?ModelDriftReport;

    public function getOfflineMetrics(string $modelVersion, string $scenario): array;

    public function getOnlineMetrics(int $tenantId, string $scenario, int $hours = 24): array;

    public function getUserEmbeddings(int $tenantId, int $userId): array;

    public function getProductEmbeddings(int $tenantId, int $itemId): array;

    public function saveUserFeatures(int $tenantId, int $userId, FeatureVector $features): void;

    public function saveItemFeatures(int $tenantId, int $itemId, FeatureVector $features): void;

    public function saveSellerFeatures(int $tenantId, int $sellerId, FeatureVector $features): void;
}
