<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Repositories;

use Modules\Analytics\Domain\Entities\RFMScore;
use Modules\Analytics\Domain\ValueObjects\UserId;
use Carbon\CarbonImmutable;

/**
 * RFM Repository Interface
 *
 * Defines contract for RFM score data access following Clean Architecture.
 */
interface RFMRepositoryInterface
{
    /**
     * Find latest RFM score for a user.
     */
    public function findLatestScore(UserId $userId): ?RFMScore;

    /**
     * Find RFM scores for a user within a date range.
     * 
     * @return RFMScore[]
     */
    public function findScoresByUser(UserId $userId, CarbonImmutable $from, CarbonImmutable $to): array;

    /**
     * Save RFM score.
     */
    public function save(RFMScore $score): void;

    /**
     * Find users by segment.
     * 
     * @param string $segment Segment name (champions, loyal, at_risk, etc.)
     * @param int $tenantId Tenant ID for scoping
     * @return UserId[]
     */
    public function findUsersBySegment(string $segment, int $tenantId): array;

    /**
     * Get segment distribution for a tenant.
     * 
     * @return array<string, int> Segment name => count
     */
    public function getSegmentDistribution(int $tenantId): array;
}
