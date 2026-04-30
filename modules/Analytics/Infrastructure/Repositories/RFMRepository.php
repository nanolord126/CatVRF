<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Repositories;

use Illuminate\Database\DatabaseManager;
use Modules\Analytics\Domain\Entities\RFMScore;
use Modules\Analytics\Domain\Repositories\RFMRepositoryInterface;
use Modules\Analytics\Domain\ValueObjects\UserId;
use Carbon\CarbonImmutable;

/**
 * RFM Repository Implementation
 *
 * Implements RFM score data access using DatabaseManager.
 * Bridges domain entities with database tables.
 */
final class RFMRepository implements RFMRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    public function findLatestScore(UserId $userId): ?RFMScore
    {
        $result = $this->db->table('analytics_rfm_scores')
            ->where('user_id', $userId->value)
            ->orderBy('calculated_at', 'desc')
            ->first();

        if ($result === null) {
            return null;
        }

        return $this->toRFMScoreEntity($result);
    }

    public function findScoresByUser(UserId $userId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $results = $this->db->table('analytics_rfm_scores')
            ->where('user_id', $userId->value)
            ->whereBetween('calculated_at', [$from, $to])
            ->orderBy('calculated_at', 'desc')
            ->get();

        return $results->map(fn ($result) => $this->toRFMScoreEntity($result))->toArray();
    }

    public function save(RFMScore $score): void
    {
        $this->db->table('analytics_rfm_scores')->insert([
            'user_id' => $score->userId,
            'tenant_id' => $score->tenantId,
            'recency_score' => $score->recencyScore,
            'frequency_score' => $score->frequencyScore,
            'monetary_score' => $score->monetaryScore,
            'overall_score' => $score->overallScore,
            'segment' => $score->segment,
            'days_since_last_purchase' => $score->daysSinceLastPurchase,
            'purchase_count_90d' => $score->purchaseCount90d,
            'total_spend_90d' => $score->totalSpend90d,
            'calculated_at' => $score->calculatedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function findUsersBySegment(string $segment, int $tenantId): array
    {
        $results = $this->db->table('analytics_rfm_scores')
            ->where('tenant_id', $tenantId)
            ->where('segment', $segment)
            ->where('calculated_at', '>=', CarbonImmutable::now()->subDays(30))
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        return array_map(fn ($id) => new UserId($id), $results);
    }

    public function getSegmentDistribution(int $tenantId): array
    {
        $results = $this->db->table('analytics_rfm_scores')
            ->select('segment')
            ->selectRaw('COUNT(*) as count')
            ->where('tenant_id', $tenantId)
            ->where('calculated_at', '>=', CarbonImmutable::now()->subDays(30))
            ->groupBy('segment')
            ->get();

        return $results->pluck('count', 'segment')->toArray();
    }

    private function toRFMScoreEntity(object $result): RFMScore
    {
        return new RFMScore(
            userId: $result->user_id,
            tenantId: $result->tenant_id,
            recencyScore: $result->recency_score,
            frequencyScore: $result->frequency_score,
            monetaryScore: $result->monetary_score,
            overallScore: $result->overall_score,
            segment: $result->segment,
            daysSinceLastPurchase: $result->days_since_last_purchase,
            purchaseCount90d: $result->purchase_count_90d,
            totalSpend90d: (float) $result->total_spend_90d,
            calculatedAt: CarbonImmutable::parse($result->calculated_at),
        );
    }
}
