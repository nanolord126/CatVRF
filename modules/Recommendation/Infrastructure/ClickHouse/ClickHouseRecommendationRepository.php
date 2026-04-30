<?php

declare(strict_types=1);

namespace Modules\Recommendation\Infrastructure\ClickHouse;

use Modules\BigData\Infrastructure\ClickHouse\ClickHouseClient;
use Modules\Recommendation\Domain\Entities\ModelDriftReport;
use Modules\Recommendation\Domain\Enums\RecommendationScenario;
use Modules\Recommendation\Domain\Enums\DriftStatus;
use Modules\Recommendation\Domain\Repositories\RecommendationRepositoryInterface;
use Modules\Recommendation\Domain\ValueObjects\FeatureVector;

final class ClickHouseRecommendationRepository implements RecommendationRepositoryInterface
{
    public function __construct(
        private readonly ClickHouseClient $clickHouse,
    ) {}

    public function saveLog(\Modules\Recommendation\Domain\Entities\RecommendationItem $item): void
    {
        $sql = sprintf(
            "INSERT INTO recommendation_logs (tenant_id, user_id, item_id, seller_id, vertical, score, confidence, source, scenario, position, model_version, correlation_id, context, created_at) VALUES (%d, %d, %d, %d, '%s', %f, %f, '%s', '%s', %d, '%s', '%s', '%s', now())",
            $item->getTenantId(),
            $item->getUserId(),
            $item->getItemId(),
            $item->getSellerId(),
            $item->getVertical(),
            $item->getScore()->getValue(),
            $item->getScore()->getConfidence(),
            $item->getSource()->value,
            $item->getScenario()->value,
            $item->getPosition(),
            $item->getModelVersion() ?? 'unknown',
            $item->getCorrelationId(),
            json_encode($item->getContext()),
        );

        $this->clickHouse->select($sql);
    }

    public function saveImpression(int $tenantId, int $userId, int $itemId, int $position, string $scenario, string $source, string $correlationId): void
    {
        $sql = sprintf(
            "INSERT INTO recommendation_impressions (tenant_id, user_id, item_id, position, scenario, source, correlation_id, created_at) VALUES (%d, %d, %d, %d, '%s', '%s', '%s', now())",
            $tenantId,
            $userId,
            $itemId,
            $position,
            $scenario,
            $source,
            $correlationId,
        );

        $this->clickHouse->select($sql);
    }

    public function saveClick(int $tenantId, int $userId, int $itemId, string $scenario, string $correlationId): void
    {
        $sql = sprintf(
            "INSERT INTO recommendation_clicks (tenant_id, user_id, item_id, scenario, correlation_id, created_at) VALUES (%d, %d, %d, '%s', '%s', now())",
            $tenantId,
            $userId,
            $itemId,
            $scenario,
            $correlationId,
        );

        $this->clickHouse->select($sql);
    }

    public function saveConversion(int $tenantId, int $userId, int $itemId, float $revenue, string $scenario, string $correlationId): void
    {
        $sql = sprintf(
            "INSERT INTO recommendation_conversions (tenant_id, user_id, item_id, revenue, scenario, correlation_id, created_at) VALUES (%d, %d, %d, %f, '%s', '%s', now())",
            $tenantId,
            $userId,
            $itemId,
            $revenue,
            $scenario,
            $correlationId,
        );

        $this->clickHouse->select($sql);
    }

    public function getUserFeatures(int $tenantId, int $userId): FeatureVector
    {
        $sql = sprintf(
            "SELECT features FROM feature_store_user WHERE tenant_id = %d AND user_id = %d ORDER BY updated_at DESC LIMIT 1",
            $tenantId,
            $userId,
        );

        $result = $this->clickHouse->select($sql);

        if (empty($result)) {
            return FeatureVector::empty();
        }

        $features = json_decode($result[0]['features'] ?? '[]', true);

        return FeatureVector::fromMixed(
            $features['dense'] ?? [],
            $features['sparse'] ?? [],
            $features['version'] ?? 1,
        );
    }

    public function getItemFeatures(int $tenantId, int $itemId): FeatureVector
    {
        $sql = sprintf(
            "SELECT features FROM feature_store_item WHERE tenant_id = %d AND item_id = %d ORDER BY updated_at DESC LIMIT 1",
            $tenantId,
            $itemId,
        );

        $result = $this->clickHouse->select($sql);

        if (empty($result)) {
            return FeatureVector::empty();
        }

        $features = json_decode($result[0]['features'] ?? '[]', true);

        return FeatureVector::fromMixed(
            $features['dense'] ?? [],
            $features['sparse'] ?? [],
            $features['version'] ?? 1,
        );
    }

    public function getSellerFeatures(int $tenantId, int $sellerId): FeatureVector
    {
        $sql = sprintf(
            "SELECT features FROM feature_store_seller WHERE tenant_id = %d AND seller_id = %d ORDER BY updated_at DESC LIMIT 1",
            $tenantId,
            $sellerId,
        );

        $result = $this->clickHouse->select($sql);

        if (empty($result)) {
            return FeatureVector::fromDense([0.5, 0.5, 0.5, 0.5, 0.5], 1);
        }

        $features = json_decode($result[0]['features'] ?? '[]', true);

        return FeatureVector::fromMixed(
            $features['dense'] ?? [0.5, 0.5, 0.5, 0.5, 0.5],
            $features['sparse'] ?? [],
            $features['version'] ?? 1,
        );
    }

    public function getContextFeatures(int $tenantId, string $scenario, array $context): FeatureVector
    {
        $hour = (int) date('H');
        $dayOfWeek = (int) date('w');
        $isWeekend = $dayOfWeek === 0 || $dayOfWeek === 6 ? 1 : 0;
        $isHoliday = $this->isHoliday() ? 1 : 0;

        $denseFeatures = [
            $hour / 24.0,
            $dayOfWeek / 7.0,
            $isWeekend,
            $isHoliday,
        ];

        $sparseFeatures = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $sparseFeatures["ctx_{$key}"] = (float) $value;
            }
        }

        return FeatureVector::fromMixed($denseFeatures, $sparseFeatures, 1);
    }

    public function getCandidateItems(int $tenantId, FeatureVector $userEmbedding, int $limit = 200, ?string $vertical = null): array
    {
        $verticalClause = $vertical !== null ? sprintf("AND vertical = '%s'", $vertical) : '';

        $sql = sprintf(
            "SELECT item_id, seller_id, vertical, embedding, score FROM feature_store_item WHERE tenant_id = %d %s AND is_active = 1 ORDER BY score DESC LIMIT %d",
            $tenantId,
            $verticalClause,
            $limit * 2,
        );

        $results = $this->clickHouse->select($sql);

        $candidates = [];
        foreach ($results as $row) {
            $itemEmbedding = FeatureVector::fromMixed(
                json_decode($row['embedding'] ?? '[]', true),
                [],
            );

            $similarity = $userEmbedding->cosineSimilarity($itemEmbedding);

            $candidates[] = [
                'item_id' => (int) $row['item_id'],
                'seller_id' => (int) $row['seller_id'],
                'vertical' => $row['vertical'],
                'score' => $similarity,
                'confidence' => 0.8,
                'position' => 0,
            ];
        }

        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($candidates, 0, $limit);
    }

    public function getTrendingItems(int $tenantId, int $limit = 20, ?string $vertical = null): array
    {
        $verticalClause = $vertical !== null ? sprintf("AND vertical = '%s'", $vertical) : '';
        $timeWindow = 'INTERVAL 24 HOUR';

        $sql = sprintf(
            "SELECT item_id, seller_id, vertical, COUNT(*) as clicks, SUM(revenue) as revenue FROM recommendation_clicks WHERE tenant_id = %d %s AND created_at >= now() - %s GROUP BY item_id, seller_id, vertical ORDER BY clicks DESC LIMIT %d",
            $tenantId,
            $verticalClause,
            $timeWindow,
            $limit,
        );

        $results = $this->clickHouse->select($sql);

        $trending = [];
        $maxClicks = max(array_column($results, 'clicks')) ?? 1;

        foreach ($results as $idx => $row) {
            $score = $row['clicks'] / $maxClicks;

            $trending[] = [
                'item_id' => (int) $row['item_id'],
                'seller_id' => (int) $row['seller_id'],
                'vertical' => $row['vertical'],
                'score' => $score,
                'clicks' => (int) $row['clicks'],
                'revenue' => (float) $row['revenue'],
                'position' => $idx,
            ];
        }

        return $trending;
    }

    public function getFrequentlyBoughtTogether(int $tenantId, int $itemId, int $limit = 10): array
    {
        $sql = sprintf(
            "SELECT item_b as item_id, seller_b as seller_id, COUNT(*) as frequency FROM recommendation_conversions WHERE tenant_id = %d AND item_a = %d GROUP BY item_id, seller_id ORDER BY frequency DESC LIMIT %d",
            $tenantId,
            $itemId,
            $limit,
        );

        $results = $this->clickHouse->select($sql);

        $frequentlyBought = [];
        foreach ($results as $row) {
            $frequentlyBought[] = [
                'item_id' => (int) $row['item_id'],
                'seller_id' => (int) $row['seller_id'],
                'score' => min(1.0, (int) $row['frequency'] / 10.0),
                'position' => 0,
                'vertical' => 'unknown',
            ];
        }

        return $frequentlyBought;
    }

    public function getSimilarItems(int $tenantId, int $itemId, int $limit = 12): array
    {
        $itemFeatures = $this->getItemFeatures($tenantId, $itemId);

        $sql = sprintf(
            "SELECT item_id, seller_id, vertical, embedding FROM feature_store_item WHERE tenant_id = %d AND item_id != %d AND is_active = 1 LIMIT 1000",
            $tenantId,
            $itemId,
        );

        $results = $this->clickHouse->select($sql);

        $similarItems = [];
        foreach ($results as $row) {
            $rowEmbedding = FeatureVector::fromMixed(
                json_decode($row['embedding'] ?? '[]', true),
                [],
            );

            $similarity = $itemFeatures->cosineSimilarity($rowEmbedding);

            if ($similarity > 0.5) {
                $similarItems[] = [
                    'item_id' => (int) $row['item_id'],
                    'seller_id' => (int) $row['seller_id'],
                    'vertical' => $row['vertical'],
                    'score' => $similarity,
                    'position' => 0,
                ];
            }
        }

        usort($similarItems, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($similarItems, 0, $limit);
    }

    public function saveModelDriftReport(ModelDriftReport $report): void
    {
        $sql = sprintf(
            "INSERT INTO model_drift_reports (model_type, model_version, psi_value, accuracy_drop, ndcg_drop, status, checked_at, created_at) VALUES ('%s', '%s', %f, %f, %f, '%s', '%s', now())",
            $report->getModelType(),
            $report->getModelVersion(),
            $report->getPsiValue(),
            $report->getAccuracyDrop(),
            $report->getNdcgDrop(),
            $report->getStatus()->value,
            $report->getCheckedAt()->format('Y-m-d H:i:s'),
        );

        $this->clickHouse->select($sql);
    }

    public function getLatestModelDriftReport(string $modelType): ?ModelDriftReport
    {
        $sql = sprintf(
            "SELECT model_type, model_version, psi_value, accuracy_drop, ndcg_drop, status, checked_at FROM model_drift_reports WHERE model_type = '%s' ORDER BY checked_at DESC LIMIT 1",
            $modelType,
        );

        $results = $this->clickHouse->select($sql);

        if (empty($results)) {
            return null;
        }

        $row = $results[0];

        return ModelDriftReport::fromMetrics(
            modelType: $row['model_type'],
            modelVersion: $row['model_version'],
            psiValue: (float) $row['psi_value'],
            accuracyDrop: (float) $row['accuracy_drop'],
            ndcgDrop: (float) $row['ndcg_drop'],
        );
    }

    public function getOfflineMetrics(string $modelVersion, string $scenario): array
    {
        $sql = sprintf(
            "SELECT metric_name, metric_value FROM offline_metrics WHERE model_version = '%s' AND scenario = '%s' ORDER BY metric_name",
            $modelVersion,
            $scenario,
        );

        $results = $this->clickHouse->select($sql);

        $metrics = [];
        foreach ($results as $row) {
            $metrics[$row['metric_name']] = (float) $row['metric_value'];
        }

        return $metrics;
    }

    public function getOnlineMetrics(int $tenantId, string $scenario, int $hours = 24): array
    {
        $timeWindow = sprintf("INTERVAL %d HOUR", $hours);

        $scenarioClause = $scenario !== 'all' ? sprintf("AND scenario = '%s'", $scenario) : '';

        $sql = sprintf(
            "SELECT COUNT(DISTINCT id) as impressions, COUNT(DISTINCT click_id) as clicks, COUNT(DISTINCT conversion_id) as conversions, SUM(revenue) as total_revenue FROM recommendation_impressions LEFT JOIN recommendation_clicks ON recommendation_impressions.id = recommendation_clicks.impression_id LEFT JOIN recommendation_conversions ON recommendation_clicks.id = recommendation_conversions.click_id WHERE tenant_id = %d %s AND recommendation_impressions.created_at >= now() - %s",
            $tenantId,
            $scenarioClause,
            $timeWindow,
        );

        $results = $this->clickHouse->select($sql);

        return $results[0] ?? [
            'impressions' => 0,
            'clicks' => 0,
            'conversions' => 0,
            'total_revenue' => 0.0,
        ];
    }

    public function getUserEmbeddings(int $tenantId, int $userId): array
    {
        $features = $this->getUserFeatures($tenantId, $userId);
        return $features->toArray();
    }

    public function getProductEmbeddings(int $tenantId, int $itemId): array
    {
        $features = $this->getItemFeatures($tenantId, $itemId);
        return $features->toArray();
    }

    public function saveUserFeatures(int $tenantId, int $userId, FeatureVector $features): void
    {
        $sql = sprintf(
            "INSERT INTO feature_store_user (tenant_id, user_id, features, version, updated_at) VALUES (%d, %d, '%s', %d, now())",
            $tenantId,
            $userId,
            json_encode($features->toArray()),
            $features->getVersion(),
        );

        $this->clickHouse->select($sql);
    }

    public function saveItemFeatures(int $tenantId, int $itemId, FeatureVector $features): void
    {
        $sql = sprintf(
            "INSERT INTO feature_store_item (tenant_id, item_id, features, embedding, version, updated_at) VALUES (%d, %d, '%s', '%s', %d, now())",
            $tenantId,
            $itemId,
            json_encode($features->toArray()),
            json_encode($features->getValues()),
            $features->getVersion(),
        );

        $this->clickHouse->select($sql);
    }

    public function saveSellerFeatures(int $tenantId, int $sellerId, FeatureVector $features): void
    {
        $sql = sprintf(
            "INSERT INTO feature_store_seller (tenant_id, seller_id, features, version, updated_at) VALUES (%d, %d, '%s', %d, now())",
            $tenantId,
            $sellerId,
            json_encode($features->toArray()),
            $features->getVersion(),
        );

        $this->clickHouse->select($sql);
    }

    private function isHoliday(): bool
    {
        $holidays = [
            '01-01', '01-07', '02-23', '03-08', '05-01', '05-09',
            '06-12', '11-04', '12-31',
        ];

        $today = date('m-d');

        return in_array($today, $holidays);
    }
}
