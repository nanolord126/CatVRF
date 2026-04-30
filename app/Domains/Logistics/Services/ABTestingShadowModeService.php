<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use Carbon\CarbonImmutable;

use App\Domains\Logistics\DTOs\ABTestResult;
use App\Domains\Logistics\DTOs\BatchOptimizationResult;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

/**
 * A/B Testing Service for Shadow Mode
 *
 * Compares baseline (current) and shadow (new) optimization strategies.
 * Tracks metrics and determines statistical significance.
 *
 * Production Strategy:
 * - Store shadow results in MySQL for analysis
 * - Calculate statistical significance (p-value, confidence interval)
 * - Track deadhead ratio, batch size, delivery time
 * - Auto-promote if shadow outperforms baseline significantly
 * - Rollback if shadow underperforms
 */
final readonly class ABTestingShadowModeService
{
    private const MIN_SAMPLE_SIZE = 100;

    private const SIGNIFICANCE_LEVEL = 0.05; // 95% confidence

    private const IMPROVEMENT_THRESHOLD = 0.05; // 5% improvement required

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Run A/B test comparing baseline and shadow results.
     *
     * @param  BatchOptimizationResult  $baselineResult  Baseline (current) result
     * @param  BatchOptimizationResult  $shadowResult  Shadow (new) result
     * @param  string  $testName  Test identifier
     * @param  int  $tenantId  Tenant ID
     */
    public function runABTest(
        BatchOptimizationResult $baselineResult,
        BatchOptimizationResult $shadowResult,
        string $testName,
        int $tenantId,
    ): ABTestResult {
        $this->logger->$this->logger->info('Running A/B test for shadow mode', [
            'test_name' => $testName,
            'tenant_id' => $tenantId,
            'baseline_orders' => $baselineResult->totalOrders,
            'shadow_orders' => $shadowResult->totalOrders,
        ]);

        // Store test results
        $testId = $this->storeTestResult(
            $baselineResult,
            $shadowResult,
            $testName,
            $tenantId,
        );

        // Calculate metrics
        $baselineDeadhead = $baselineResult->totalDeadheadRatio;
        $shadowDeadhead = $shadowResult->totalDeadheadRatio;

        $baselineOptimizationScore = $baselineResult->optimizationScore;
        $shadowOptimizationScore = $shadowResult->optimizationScore;

        $baselineBatchCount = count($baselineResult->batches);
        $shadowBatchCount = count($shadowResult->batches);

        // Calculate improvements
        $deadheadImprovement = $this->calculateImprovement($baselineDeadhead, $shadowDeadhead, true); // Lower is better
        $scoreImprovement = $this->calculateImprovement($baselineOptimizationScore, $shadowOptimizationScore, false); // Higher is better
        $batchImprovement = $this->calculateImprovement($baselineBatchCount, $shadowBatchCount, false);

        // Calculate statistical significance
        $isSignificant = $this->calculateStatisticalSignificance(
            $baselineDeadhead,
            $shadowDeadhead,
            $baselineResult->totalOrders,
            $shadowResult->totalOrders,
        );

        // Determine winner
        $winner = $this->determineWinner(
            $deadheadImprovement,
            $scoreImprovement,
            $isSignificant,
        );

        $result = new ABTestResult(
            testId: $testId,
            testName: $testName,
            tenantId: $tenantId,
            baselineDeadheadRatio: $baselineDeadhead,
            shadowDeadheadRatio: $shadowDeadhead,
            deadheadImprovement: $deadheadImprovement,
            baselineOptimizationScore: $baselineOptimizationScore,
            shadowOptimizationScore: $shadowOptimizationScore,
            scoreImprovement: $scoreImprovement,
            baselineBatchCount: $baselineBatchCount,
            shadowBatchCount: $shadowBatchCount,
            batchImprovement: $batchImprovement,
            isSignificant: $isSignificant,
            winner: $winner,
            shouldPromote: $winner === 'shadow' && $isSignificant,
        );

        $this->logger->$this->logger->info('A/B test completed', [
            'test_id' => $testId,
            'winner' => $winner,
            'is_significant' => $isSignificant,
            'deadhead_improvement' => $deadheadImprovement,
            'should_promote' => $result->shouldPromote,
        ]);

        return $result;
    }

    /**
     * Get historical test results.
     */
    public function getHistoricalResults(string $testName, int $tenantId, int $days = 30): array
    {
        return $this->db->table('ab_test_results')
            ->where('test_name', $testName)
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', \Carbon\CarbonImmutable::now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get aggregate statistics for a test.
     */
    public function getTestStatistics(string $testName, int $tenantId): array
    {
        $results = $this->db->table('ab_test_results')
            ->where('test_name', $testName)
            ->where('tenant_id', $tenantId)
            ->get();

        if ($results->isEmpty()) {
            return [];
        }

        return [
            'total_tests' => $results->count(),
            'avg_deadhead_improvement' => $results->avg(
                fn ($r) => (($r->baseline_deadhead_ratio - $r->shadow_deadhead_ratio) / $r->baseline_deadhead_ratio) * 100
            ),
            'avg_score_improvement' => $results->avg(
                fn ($r) => (($r->shadow_optimization_score - $r->baseline_optimization_score) / $r->baseline_optimization_score) * 100
            ),
            'shadow_wins' => $results->where(
                fn ($r) => $r->shadow_deadhead_ratio < $r->baseline_deadhead_ratio
            )->count(),
            'baseline_wins' => $results->where(
                fn ($r) => $r->shadow_deadhead_ratio >= $r->baseline_deadhead_ratio
            )->count(),
        ];
    }

    /**
     * Store test result in database.
     */
    private function storeTestResult(
        BatchOptimizationResult $baselineResult,
        BatchOptimizationResult $shadowResult,
        string $testName,
        int $tenantId,
    ): string {
        $testId = (string) Str::uuid();

        $this->db->table('ab_test_results')->insert([
            'id' => $testId,
            'tenant_id' => $tenantId,
            'test_name' => $testName,
            'baseline_deadhead_ratio' => $baselineResult->totalDeadheadRatio,
            'shadow_deadhead_ratio' => $shadowResult->totalDeadheadRatio,
            'baseline_optimization_score' => $baselineResult->optimizationScore,
            'shadow_optimization_score' => $shadowResult->optimizationScore,
            'baseline_batch_count' => count($baselineResult->batches),
            'shadow_batch_count' => count($shadowResult->batches),
            'baseline_total_orders' => $baselineResult->totalOrders,
            'shadow_total_orders' => $shadowResult->totalOrders,
            'baseline_is_resort_spit' => $baselineResult->isResortSpit,
            'shadow_is_resort_spit' => $shadowResult->isResortSpit,
            'created_at' => \Carbon\CarbonImmutable::now(),
        ]);

        return $testId;
    }

    /**
     * Calculate improvement percentage.
     *
     * @param  float  $baseline  Baseline value
     * @param  float  $shadow  Shadow value
     * @param  bool  $lowerIsBetter  If true, lower values are better (e.g., deadhead)
     * @return float Improvement percentage (positive = shadow is better)
     */
    private function calculateImprovement(float $baseline, float $shadow, bool $lowerIsBetter): float
    {
        if ($baseline === 0) {
            return 0.0;
        }

        if ($lowerIsBetter) {
            // Lower is better (e.g., deadhead ratio)
            return (($baseline - $shadow) / $baseline) * 100;
        } else {
            // Higher is better (e.g., optimization score)
            return (($shadow - $baseline) / $baseline) * 100;
        }
    }

    /**
     * Calculate statistical significance using two-sample t-test.
     *
     * @param  float  $baselineMean  Baseline mean
     * @param  float  $shadowMean  Shadow mean
     * @param  int  $baselineN  Baseline sample size
     * @param  int  $shadowN  Shadow sample size
     * @return bool True if difference is statistically significant
     */
    private function calculateStatisticalSignificance(
        float $baselineMean,
        float $shadowMean,
        int $baselineN,
        int $shadowN,
    ): bool {
        if ($baselineN < self::MIN_SAMPLE_SIZE || $shadowN < self::MIN_SAMPLE_SIZE) {
            $this->logger->warning('Sample size too small for statistical significance', [
                'baseline_n' => $baselineN,
                'shadow_n' => $shadowN,
                'min_sample_size' => self::MIN_SAMPLE_SIZE,
            ]);

            return false;
        }

        // Simplified two-sample t-test (production should use proper statistical library)
        $pooledStdDev = 0.1; // Assumed standard deviation
        $standardError = $pooledStdDev * sqrt(1 / $baselineN + 1 / $shadowN);

        if ($standardError === 0) {
            return false;
        }

        $tStatistic = abs($shadowMean - $baselineMean) / $standardError;

        // Critical value for 95% confidence (two-tailed) is approximately 1.96
        $isSignificant = $tStatistic > 1.96;

        $this->logger->$this->logger->info('Statistical significance calculated', [
            't_statistic' => $tStatistic,
            'is_significant' => $isSignificant,
        ]);

        return $isSignificant;
    }

    /**
     * Determine winner based on improvements.
     */
    private function determineWinner(
        float $deadheadImprovement,
        float $scoreImprovement,
        bool $isSignificant,
    ): string {
        if (! $isSignificant) {
            return 'inconclusive';
        }

        // Weight deadhead improvement more heavily (70%) than score improvement (30%)
        $weightedScore = (0.7 * $deadheadImprovement) + (0.3 * $scoreImprovement);

        if ($weightedScore > self::IMPROVEMENT_THRESHOLD * 100) {
            return 'shadow';
        } elseif ($weightedScore < -(self::IMPROVEMENT_THRESHOLD * 100)) {
            return 'baseline';
        }

        return 'tie';
    }
}
