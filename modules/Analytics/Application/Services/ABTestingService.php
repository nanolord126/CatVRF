<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Modules\Analytics\Application\DTOs\CLVPredictionDTO;
use Modules\Analytics\Application\DTOs\VariantDTO;
use Modules\Analytics\Models\Experiment;
use Modules\Analytics\Models\ExperimentAssignment;
use Modules\Analytics\Models\ExperimentVariant;

/**
 * A/B Testing Service for CLV-based Promotions
 *
 * Production-ready service for hash-based deterministic assignment
 * with CLV stratification. Prevents leakage through unique constraints.
 * 
 * Key features:
 * - Hash-based assignment (deterministic, no race conditions)
 * - CLV segment stratification
 * - Traffic percentage control
 * - Prevention of leakage (one assignment per buyer-seller-experiment)
 * - Audit logging for all assignments
 * - Caching for performance
 * 
 * @see https://github.com/nanolord126/CatVRF
 */
final readonly class ABTestingService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly AuditService $auditService,
        private readonly SellerCLVService $clvService,
    ) {}

    /**
     * Assign a buyer to a variant in an experiment.
     * 
     * Uses hash-based deterministic assignment with CLV stratification.
     * Returns control variant if user doesn't match CLV filters.
     * 
     * @param string $experimentKey Experiment unique key
     * @param int $buyerId Buyer ID
     * @param int $sellerId Seller ID
     * @param int $tenantId Tenant ID
     * @return VariantDTO Assigned variant
     */
    public function assign(
        string $experimentKey,
        int $buyerId,
        int $sellerId,
        int $tenantId,
    ): VariantDTO {
        // Fraud check - first action in any public method
        $this->logAction('abtest_assign', [
            'experiment_key' => $experimentKey,
            'buyer_id' => $buyerId,
            'seller_id' => $sellerId,
            'tenant_id' => $tenantId,
        ]);

        $cacheKey = "abtest:assignment:{$tenantId}:{$sellerId}:{$buyerId}:{$experimentKey}";

        return $this->cache->tags(["abtest:{$tenantId}", "seller:{$sellerId}"])
            ->remember($cacheKey, 3600, function () use ($experimentKey, $buyerId, $sellerId, $tenantId) {
                // Load experiment
                $experiment = Experiment::findByKey($experimentKey);
                
                if (!$experiment || !$experiment->isRunning()) {
                    return $this->getDefaultControlVariant();
                }

                // Check for existing assignment (prevents leakage)
                $existing = ExperimentAssignment::findExisting($tenantId, $experiment->id, $sellerId, $buyerId);
                if ($existing) {
                    return $existing->variant->toDTO();
                }

                // Get CLV prediction for stratification
                $clv = $this->clvService->predictForBuyer($sellerId, $buyerId, $tenantId);

                // Check if buyer matches CLV filters
                if (!$this->matchesCLVFilters($clv, $experiment)) {
                    return $experiment->getControlVariant()?->toDTO() ?? $this->getDefaultControlVariant();
                }

                // Calculate hash bucket for deterministic assignment
                $hash = md5($experiment->id . $buyerId . $sellerId);
                $hashBucket = hexdec(substr($hash, 0, 8)) % 100;

                // Check traffic percentage
                if ($hashBucket >= $experiment->traffic_percent) {
                    return $experiment->getControlVariant()?->toDTO() ?? $this->getDefaultControlVariant();
                }

                // Get variant for bucket
                $variant = $experiment->getVariantForBucket($hashBucket);
                
                if (!$variant) {
                    return $experiment->getControlVariant()?->toDTO() ?? $this->getDefaultControlVariant();
                }

                // Record assignment
                $this->recordAssignment($experiment, $variant, $buyerId, $sellerId, $tenantId, $clv, $hashBucket);

                return $variant->toDTO();
            });
    }

    /**
     * Create a new CLV-based A/B experiment.
     * 
     * @param array $experimentData Experiment configuration
     * @return Experiment Created experiment
     */
    public function createExperiment(array $experimentData): Experiment
    {
        $this->logAction('abtest_create', $experimentData);

        return $this->db->transaction(function () use ($experimentData) {
            $experiment = Experiment::create([
                'tenant_id' => $experimentData['tenant_id'] ?? null,
                'seller_id' => $experimentData['seller_id'] ?? null,
                'key' => $experimentData['key'],
                'name' => $experimentData['name'],
                'description' => $experimentData['description'] ?? null,
                'target_segment' => $experimentData['target_segment'],
                'clv_filters' => $experimentData['clv_filters'] ?? null,
                'traffic_percent' => $experimentData['traffic_percent'] ?? 100,
                'scheduled_start_at' => $experimentData['scheduled_start_at'] ?? null,
                'scheduled_end_at' => $experimentData['scheduled_end_at'] ?? null,
                'status' => 'draft',
                'primary_metric' => $experimentData['primary_metric'] ?? 'revenue_14d',
                'secondary_metrics' => $experimentData['secondary_metrics'] ?? null,
                'created_by' => $experimentData['created_by'] ?? null,
            ]);

            // Create variants
            foreach ($experimentData['variants'] as $variantData) {
                ExperimentVariant::create([
                    'experiment_id' => $experiment->id,
                    'key' => $variantData['key'],
                    'name' => $variantData['name'],
                    'configuration' => $variantData['configuration'] ?? null,
                    'traffic_allocation' => $variantData['traffic_allocation'] ?? 0,
                    'is_control' => $variantData['is_control'] ?? false,
                ]);
            }

            return $experiment;
        });
    }

    /**
     * Start an experiment.
     */
    public function startExperiment(int $experimentId): void
    {
        $experiment = Experiment::findOrFail($experimentId);
        
        $this->logAction('abtest_start', [
            'experiment_id' => $experimentId,
            'experiment_key' => $experiment->key,
        ]);

        $experiment->start();
        
        // Invalidate cache
        $this->invalidateExperimentCache($experiment);
    }

    /**
     * Pause an experiment.
     */
    public function pauseExperiment(int $experimentId): void
    {
        $experiment = Experiment::findOrFail($experimentId);
        
        $this->logAction('abtest_pause', [
            'experiment_id' => $experimentId,
            'experiment_key' => $experiment->key,
        ]);

        $experiment->pause();
        
        $this->invalidateExperimentCache($experiment);
    }

    /**
     * Finish an experiment.
     */
    public function finishExperiment(int $experimentId): void
    {
        $experiment = Experiment::findOrFail($experimentId);
        
        $this->logAction('abtest_finish', [
            'experiment_id' => $experimentId,
            'experiment_key' => $experiment->key,
        ]);

        $experiment->finish();
        
        $this->invalidateExperimentCache($experiment);
    }

    /**
     * Record exposure to variant (when user actually sees the promotion).
     */
    public function recordExposure(
        string $experimentKey,
        int $buyerId,
        int $sellerId,
        int $tenantId,
    ): void {
        $assignment = ExperimentAssignment::findExisting(
            $tenantId,
            Experiment::findByKey($experimentKey)?->id ?? 0,
            $sellerId,
            $buyerId
        );

        if ($assignment) {
            $assignment->recordExposure();
        }
    }

    /**
     * Check if CLV prediction matches experiment filters.
     */
    private function matchesCLVFilters(CLVPredictionDTO $clv, Experiment $experiment): bool
    {
        $filters = $experiment->clv_filters ?? [];

        if (empty($filters)) {
            return true;
        }

        // Check CLV segment
        if (isset($filters['segment']) && $clv->segment !== $filters['segment']) {
            return false;
        }

        // Check CLV minimum
        if (isset($filters['clv_180d_min']) && $clv->predictedClv180d < $filters['clv_180d_min']) {
            return false;
        }

        // Check churn probability maximum
        if (isset($filters['churn_prob_max']) && $clv->churnProbability > $filters['churn_prob_max']) {
            return false;
        }

        return true;
    }

    /**
     * Record assignment in database.
     */
    private function recordAssignment(
        Experiment $experiment,
        ExperimentVariant $variant,
        int $buyerId,
        int $sellerId,
        int $tenantId,
        CLVPredictionDTO $clv,
        int $hashBucket,
    ): void {
        ExperimentAssignment::create([
            'tenant_id' => $tenantId,
            'experiment_id' => $experiment->id,
            'variant_id' => $variant->id,
            'seller_id' => $sellerId,
            'buyer_id' => $buyerId,
            'clv_180d_at_assignment' => $clv->predictedClv180d,
            'clv_365d_at_assignment' => $clv->predictedClv365d,
            'churn_prob_at_assignment' => $clv->churnProbability,
            'clv_segment_at_assignment' => $clv->segment,
            'assignment_method' => 'hash',
            'hash_bucket' => $hashBucket,
            'assigned_at' => now(),
        ]);

        // Increment variant sample size
        $variant->incrementSampleSize();

        // Log assignment for audit
        $this->auditService->log(
            'abtest.assignment',
            [
                'experiment_id' => $experiment->id,
                'experiment_key' => $experiment->key,
                'variant_id' => $variant->id,
                'variant_key' => $variant->key,
                'buyer_id' => $buyerId,
                'seller_id' => $sellerId,
                'tenant_id' => $tenantId,
                'clv_segment' => $clv->segment,
                'hash_bucket' => $hashBucket,
            ],
            $tenantId,
        );
    }

    /**
     * Invalidate cache for experiment.
     */
    private function invalidateExperimentCache(Experiment $experiment): void
    {
        if ($experiment->tenant_id) {
            $this->cache->tags(["abtest:{$experiment->tenant_id}"])->flush();
        }
        if ($experiment->seller_id) {
            $this->cache->tags(["seller:{$experiment->seller_id}"])->flush();
        }
    }

    /**
     * Get default control variant (fallback when experiment not found).
     */
    private function getDefaultControlVariant(): VariantDTO
    {
        return new VariantDTO(
            id: null,
            experimentId: 0,
            key: 'control',
            name: 'Default Control',
            configuration: ['discount' => 0, 'message' => 'No promotion'],
            trafficAllocation: 100,
            isControl: true,
            sampleSize: 0,
            metrics: null,
        );
    }
}
