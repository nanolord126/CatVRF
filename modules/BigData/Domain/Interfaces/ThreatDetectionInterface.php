<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Interfaces;

use Modules\BigData\Domain\Enums\ThreatLevel;
use Modules\BigData\Domain\ValueObjects\ThreatAssessment;

/**
 * Threat Detection Interface
 *
 * Contract for anomaly detection and query pattern analysis
 * on the BigData event stream.
 */
interface ThreatDetectionInterface
{
    /**
     * Analyze event volume for a seller (isolation forest approach)
     * Detects: unusual volume spikes, scraping patterns, data exfiltration
     */
    public function analyzeSellerBehavior(int $sellerId): ThreatAssessment;

    /**
     * Analyze query patterns for a user
     * Detects: unusual query patterns, table scans, data mining
     */
    public function analyzeQueryPatterns(int $userId): ThreatAssessment;

    /**
     * Analyze real-time event stream for anomalies
     * Called by Kafka consumer for each batch
     */
    public function analyzeEventBatch(array $events): array;

    /**
     * Get current threat level for a seller or user
     */
    public function getCurrentThreatLevel(int $entityId, string $entityType = 'seller'): ThreatLevel;

    /**
     * Record and evaluate a potential threat
     * Returns updated assessment
     */
    public function evaluateThreat(ThreatAssessment $assessment): ThreatAssessment;
}
