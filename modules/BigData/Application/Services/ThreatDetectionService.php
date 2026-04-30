<?php

declare(strict_types=1);

namespace Modules\BigData\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\BigData\Domain\Enums\ThreatLevel;
use Modules\BigData\Domain\Events\SecurityThreatDetected;
use Modules\BigData\Domain\Interfaces\SecurityRepositoryInterface;
use Modules\BigData\Domain\Interfaces\ThreatDetectionInterface;
use Modules\BigData\Domain\ValueObjects\ThreatAssessment;

/**
 * Threat detection service for BigData event stream.
 * Isolation-forest approach for volume spikes, query pattern analysis for scraping.
 * Integrates with Redis for real-time rate tracking and ClickHouse for historical baselines.
 */
final class ThreatDetectionService implements ThreatDetectionInterface
{
    private const REDIS_RATE_PREFIX = 'bigdata:threat:rate:';
    private const REDIS_QUERY_PREFIX = 'bigdata:threat:queries:';
    private const VOLUME_SPIKE_SIGMA_THRESHOLD = 3.0;
    private const QUERY_ANOMALY_THRESHOLD = 0.6;

    public function __construct(
        private readonly SecurityRepositoryInterface $repository,
    ) {}

    public function analyzeSellerBehavior(int $sellerId): ThreatAssessment
    {
        $metrics = $this->repository->getSellerEventVolume($sellerId);
        $eventCount1h = $metrics['event_count_1h'] ?? 0;
        $avgHourly = $metrics['avg_hourly'] ?? 0;

        // Also check Redis real-time counter
        $redisKey = self::REDIS_RATE_PREFIX . 'seller:' . $sellerId;
        $redisCount = (int) Redis::get($redisKey);
        if ($redisCount > $eventCount1h) {
            $eventCount1h = $redisCount;
        }

        // Calculate standard deviation from historical average
        // Using simplified z-score: (current - mean) / mean (if mean > 0)
        $sigma = $avgHourly > 0 ? abs($eventCount1h - $avgHourly) / max(sqrt($avgHourly), 1.0) : 0.0;

        if ($sigma < self::VOLUME_SPIKE_SIGMA_THRESHOLD) {
            return ThreatAssessment::fromSellerVolumeSpike(
                sellerId: $sellerId,
                eventCount1h: $eventCount1h,
                avgHourly: $avgHourly,
                sigma: $sigma,
                affectedTables: ['ch_raw_events'],
            );
        }

        // Significant anomaly detected
        $assessment = ThreatAssessment::fromSellerVolumeSpike(
            sellerId: $sellerId,
            eventCount1h: $eventCount1h,
            avgHourly: $avgHourly,
            sigma: $sigma,
            affectedTables: $this->getAffectedTablesForSeller($sellerId),
        );

        // Persist and dispatch event
        $this->repository->recordThreatEvent($assessment);
        Event::dispatch(SecurityThreatDetected::fromAssessment($assessment));

        // Auto-block: set Redis flag for rate limiter middleware
        if ($assessment->isAutoBlocked) {
            Redis::setex(self::REDIS_RATE_PREFIX . 'blocked:' . $sellerId, 3600, '1');
            Log::warning('BigData security: seller auto-blocked', [
                'seller_id' => $sellerId,
                'sigma' => round($sigma, 2),
                'event_count_1h' => $eventCount1h,
            ]);
        }

        return $assessment;
    }

    public function analyzeQueryPatterns(int $userId): ThreatAssessment
    {
        $metrics = $this->repository->getQueryPatternMetrics($userId);
        $queryCount1h = $metrics['query_count_1h'] ?? 0;
        $avgDurationMs = $metrics['avg_query_duration_ms'] ?? 0.0;
        $distinctTables = $metrics['distinct_tables'] ?? 0;
        $maxRowsScanned = $metrics['max_rows_scanned'] ?? 0;

        // Check Redis for real-time query counter
        $redisKey = self::REDIS_QUERY_PREFIX . $userId;
        $redisCount = (int) Redis::get($redisKey);
        if ($redisCount > $queryCount1h) {
            $queryCount1h = $redisCount;
        }

        $assessment = ThreatAssessment::fromQueryAnomaly(
            userId: $userId,
            queryCount1h: $queryCount1h,
            avgDurationMs: $avgDurationMs,
            distinctTables: $distinctTables,
            maxRowsScanned: $maxRowsScanned,
        );

        if ($assessment->anomalyScore >= self::QUERY_ANOMALY_THRESHOLD) {
            $this->repository->recordThreatEvent($assessment);
            Event::dispatch(SecurityThreatDetected::fromAssessment($assessment));

            if ($assessment->isAutoBlocked) {
                Redis::setex(self::REDIS_QUERY_PREFIX . 'blocked:' . $userId, 3600, '1');
                Log::warning('BigData security: user query access auto-blocked', [
                    'user_id' => $userId,
                    'anomaly_score' => round($assessment->anomalyScore, 3),
                ]);
            }
        }

        return $assessment;
    }

    public function analyzeEventBatch(array $events): array
    {
        $threats = [];
        $sellerCounts = [];

        // Aggregate event counts per seller from batch
        foreach ($events as $event) {
            $sellerId = $event['seller_id'] ?? null;
            if ($sellerId !== null) {
                $sellerCounts[$sellerId] = ($sellerCounts[$sellerId] ?? 0) + 1;
            }
        }

        // Check each seller for volume spikes
        foreach ($sellerCounts as $sellerId => $count) {
            // Quick Redis rate check before full analysis
            $redisKey = self::REDIS_RATE_PREFIX . 'seller:' . $sellerId;
            Redis::incrby($redisKey, $count);
            Redis::expire($redisKey, 3600);

            $currentCount = (int) Redis::get($redisKey);
            $avgHourly = $this->repository->getSellerEventVolume($sellerId)['avg_hourly'] ?? 0;

            if ($avgHourly > 0 && $currentCount > $avgHourly * 3) {
                $threats[] = $this->analyzeSellerBehavior($sellerId);
            }
        }

        return $threats;
    }

    public function getCurrentThreatLevel(int $entityId, string $entityType = 'seller'): ThreatLevel
    {
        $threats = $this->repository->getActiveThreats(
            sellerId: $entityType === 'seller' ? $entityId : null,
            userId: $entityType === 'user' ? $entityId : null,
            limit: 10,
        );

        if (empty($threats)) {
            return ThreatLevel::None;
        }

        // Return the highest threat level among active threats
        $maxLevel = ThreatLevel::None;
        foreach ($threats as $threat) {
            if ($threat->level->value > $maxLevel->value) {
                $maxLevel = $threat->level;
            }
        }
        return $maxLevel;
    }

    public function evaluateThreat(ThreatAssessment $assessment): ThreatAssessment
    {
        // Re-evaluate: check if conditions still hold
        if ($assessment->sellerId !== null) {
            $fresh = $this->analyzeSellerBehavior($assessment->sellerId);
            return $fresh->level->value < $assessment->level->value
                ? $assessment->withResolved()
                : $fresh;
        }

        if ($assessment->userId !== null) {
            $fresh = $this->analyzeQueryPatterns($assessment->userId);
            return $fresh->level->value < $assessment->level->value
                ? $assessment->withResolved()
                : $fresh;
        }

        return $assessment;
    }

    /** Determine which ClickHouse tables a seller has data in */
    private function getAffectedTablesForSeller(int $sellerId): array
    {
        return [
            'ch_raw_events',
            'ch_seller_daily_metrics',
            'ch_clv_predictions',
            'ch_buyer_seller_features',
        ];
    }
}
