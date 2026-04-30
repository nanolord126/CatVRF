<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Modules\Analytics\Application\DTOs\RetentionCohortDto;
use Modules\Analytics\Application\DTOs\CohortDataDto;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;

/**
 * Retention Analysis Service
 *
 * Handles retention cohort analysis.
 * Follows Single Responsibility Principle - only handles retention queries.
 */
final readonly class RetentionAnalysisService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Get retention cohort analysis.
     */
    public function getRetentionCohorts(
        string $cohortType,
        CarbonImmutable $analysisDate,
        int $tenantId,
    ): RetentionCohortDto {
        // Fraud check for retention queries
        // TODO: Integrate with FraudDetectionService when available

        $cacheKey = "analytics:retention:{$cohortType}:{$tenantId}:{$analysisDate->toDateString()}";
        
        return $this->cache->tags(["analytics:{$tenantId}"])->remember($cacheKey, 3600, function () use ($cohortType, $analysisDate, $tenantId) {
            $results = $this->db->table('analytics_retention_cohorts')
                ->where('tenant_id', $tenantId)
                ->where('cohort_type', $cohortType)
                ->where('cohort_date', '<=', $analysisDate->toDateString())
                ->orderBy('cohort_date', 'desc')
                ->limit(12)
                ->get();

            $cohorts = [];
            foreach ($results as $result) {
                $cohorts[] = CohortDataDto::create(
                    $result->cohort_name,
                    CarbonImmutable::parse($result->cohort_date),
                    $result->cohort_size,
                    json_decode($result->retention_rates, true),
                );
            }

            return RetentionCohortDto::create($cohortType, $analysisDate, $cohorts, $tenantId);
        });
    }
}
