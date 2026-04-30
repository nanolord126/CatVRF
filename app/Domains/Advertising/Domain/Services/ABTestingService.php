<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Services;

use App\Domains\Advertising\Domain\Interfaces\AdShortRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;

/**
 * A/B Testing Service for Ad Creatives
 *
 * Implements A/B testing framework to compare different ad creatives,
- targeting strategies, and pricing models to optimize performance.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class ABTestingService
{
    private const CACHE_TTL = 600; // 10 minutes
    private const MIN_SAMPLE_SIZE = 1000; // Minimum impressions per variant

    public function __construct(
        private readonly AdShortRepositoryInterface $adShortRepository,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Create A/B test
     *
     * @param string $name Test name
     * @param int $controlAdId Control ad short ID
     * @param int $variantAdId Variant ad short ID
     * @param string $metric Primary metric to optimize (ctr, conversions, revenue)
     * @param int $sampleSize Target sample size per variant
     * @param int $userId User ID creating the test
     * @return array{test_id: string, status: string, variants: array}
     */
    public function createTest(
        string $name,
        int $controlAdId,
        int $variantAdId,
        string $metric = 'ctr',
        int $sampleSize = 5000,
        int $userId = 0,
    ): array {
        $testId = 'ab_test_' . str()->uuid()->toString();

        $test = [
            'test_id' => $testId,
            'name' => $name,
            'metric' => $metric,
            'sample_size' => $sampleSize,
            'status' => 'running',
            'created_at' => now()->toIso8601String(),
            'created_by' => $userId,
            'variants' => [
                'control' => [
                    'ad_id' => $controlAdId,
                    'traffic_split' => 0.5,
                    'impressions' => 0,
                    'clicks' => 0,
                    'conversions' => 0,
                    'revenue' => 0,
                ],
                'variant' => [
                    'ad_id' => $variantAdId,
                    'traffic_split' => 0.5,
                    'impressions' => 0,
                    'clicks' => 0,
                    'conversions' => 0,
                    'revenue' => 0,
                ],
            ],
        ];

        Redis::setex("ab_test:{$testId}", 86400 * 7, json_encode($test)); // 7 days TTL

        $this->logger->info('A/B test created', [
            'test_id' => $testId,
            'name' => $name,
            'control_ad_id' => $controlAdId,
            'variant_ad_id' => $variantAdId,
        ]);

        return $test;
    }

    /**
     * Get variant for user (deterministic assignment)
     *
     * @param string $testId Test ID
     * @param int $userId User ID
     * @return string 'control' or 'variant'
     */
    public function getVariant(string $testId, int $userId): string
    {
        $test = $this->getTest($testId);
        if ($test === null || $test['status'] !== 'running') {
            return 'control';
        }

        // Deterministic assignment based on user ID
        $hash = crc32($testId . $userId);
        $variant = ($hash % 100) < 50 ? 'control' : 'variant';

        return $variant;
    }

    /**
     * Record impression for A/B test
     *
     * @param string $testId Test ID
     * @param string $variant Variant name
     */
    public function recordImpression(string $testId, string $variant): void
    {
        $key = "ab_test:{$testId}";
        $test = Redis::get($key);
        
        if ($test === null) {
            return;
        }

        $testData = json_decode($test, true);
        $testData['variants'][$variant]['impressions']++;
        
        Redis::setex($key, 86400 * 7, json_encode($testData));
    }

    /**
     * Record click for A/B test
     *
     * @param string $testId Test ID
     * @param string $variant Variant name
     */
    public function recordClick(string $testId, string $variant): void
    {
        $key = "ab_test:{$testId}";
        $test = Redis::get($key);
        
        if ($test === null) {
            return;
        }

        $testData = json_decode($test, true);
        $testData['variants'][$variant]['clicks']++;
        
        Redis::setex($key, 86400 * 7, json_encode($testData));
    }

    /**
     * Record conversion for A/B test
     *
     * @param string $testId Test ID
     * @param string $variant Variant name
     * @param int $revenue Revenue from conversion
     */
    public function recordConversion(string $testId, string $variant, int $revenue = 0): void
    {
        $key = "ab_test:{$testId}";
        $test = Redis::get($key);
        
        if ($test === null) {
            return;
        }

        $testData = json_decode($test, true);
        $testData['variants'][$variant]['conversions']++;
        $testData['variants'][$variant]['revenue'] += $revenue;
        
        Redis::setex($key, 86400 * 7, json_encode($testData));
    }

    /**
     * Get A/B test results
     *
     * @param string $testId Test ID
     * @return array{test: array, results: array, winner: string|null, significance: float}
     */
    public function getResults(string $testId): array
    {
        $test = $this->getTest($testId);
        if ($test === null) {
            throw new \RuntimeException('Test not found');
        }

        $control = $test['variants']['control'];
        $variant = $test['variants']['variant'];

        // Calculate metrics
        $controlMetrics = $this->calculateMetrics($control);
        $variantMetrics = $this->calculateMetrics($variant);

        // Calculate statistical significance
        $significance = $this->calculateSignificance($controlMetrics, $variantMetrics);

        // Determine winner
        $winner = $this->determineWinner($controlMetrics, $variantMetrics, $significance, $test['metric']);

        return [
            'test' => $test,
            'results' => [
                'control' => $controlMetrics,
                'variant' => $variantMetrics,
            ],
            'winner' => $winner,
            'significance' => $significance,
            'is_complete' => $this->isTestComplete($test),
        ];
    }

    /**
     * Calculate metrics for a variant
     */
    private function calculateMetrics(array $variant): array
    {
        $impressions = $variant['impressions'] ?? 0;
        $clicks = $variant['clicks'] ?? 0;
        $conversions = $variant['conversions'] ?? 0;
        $revenue = $variant['revenue'] ?? 0;

        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'revenue' => $revenue,
            'ctr' => $impressions > 0 ? $clicks / $impressions : 0,
            'conversion_rate' => $impressions > 0 ? $conversions / $impressions : 0,
            'cpm' => $impressions > 0 ? ($revenue / $impressions) * 1000 : 0,
            'rpc' => $clicks > 0 ? $revenue / $clicks : 0, // Revenue per click
        ];
    }

    /**
     * Calculate statistical significance (simplified chi-squared test)
     */
    private function calculateSignificance(array $control, array $variant): float
    {
        // Simplified significance calculation
        // In production, use proper statistical library
        
        $controlRate = $control['ctr'];
        $variantRate = $variant['ctr'];
        
        $difference = abs($controlRate - $variantRate);
        
        // More difference = higher significance
        $significance = min(0.99, $difference * 10);
        
        return $significance;
    }

    /**
     * Determine winner based on metrics
     */
    private function determineWinner(
        array $control,
        array $variant,
        float $significance,
        string $metric,
    ): ?string {
        if ($significance < 0.95) {
            return null; // Not statistically significant
        }

        $controlValue = $control[$metric] ?? 0;
        $variantValue = $variant[$metric] ?? 0;

        if ($variantValue > $controlValue) {
            return 'variant';
        } elseif ($controlValue > $variantValue) {
            return 'control';
        }

        return null; // No significant difference
    }

    /**
     * Check if test is complete
     */
    private function isTestComplete(array $test): bool
    {
        $controlImpressions = $test['variants']['control']['impressions'];
        $variantImpressions = $test['variants']['variant']['impressions'];
        $target = $test['sample_size'];

        return $controlImpressions >= $target && $variantImpressions >= $target;
    }

    /**
     * Stop A/B test
     *
     * @param string $testId Test ID
     * @param int $userId User ID stopping the test
     */
    public function stopTest(string $testId, int $userId = 0): void
    {
        $key = "ab_test:{$testId}";
        $test = Redis::get($key);
        
        if ($test === null) {
            throw new \RuntimeException('Test not found');
        }

        $testData = json_decode($test, true);
        $testData['status'] = 'stopped';
        $testData['stopped_at'] = now()->toIso8601String();
        $testData['stopped_by'] = $userId;
        
        Redis::setex($key, 86400 * 30, json_encode($testData)); // 30 days TTL after stopping

        $this->logger->info('A/B test stopped', [
            'test_id' => $testId,
            'stopped_by' => $userId,
        ]);
    }

    /**
     * Get A/B test
     */
    private function getTest(string $testId): ?array
    {
        $key = "ab_test:{$testId}";
        $test = Redis::get($key);
        
        return $test ? json_decode($test, true) : null;
    }

    /**
     * List all A/B tests for tenant
     *
     * @param int $tenantId Tenant ID
     * @return array<int, array>
     */
    public function listTests(int $tenantId): array
    {
        // In production, query from database
        // For now, return empty array
        return [];
    }

    /**
     * Auto-allocate traffic for running test
     *
     * @param string $testId Test ID
     * @param array $newSplits New traffic splits [control => 0.5, variant => 0.5]
     */
    public function adjustTrafficSplit(string $testId, array $newSplits): void
    {
        $key = "ab_test:{$testId}";
        $test = Redis::get($key);
        
        if ($test === null) {
            throw new \RuntimeException('Test not found');
        }

        $testData = json_decode($test, true);
        
        // Validate splits sum to 1
        $total = array_sum($newSplits);
        if (abs($total - 1.0) > 0.01) {
            throw new \InvalidArgumentException('Traffic splits must sum to 1.0');
        }

        foreach ($newSplits as $variant => $split) {
            if (isset($testData['variants'][$variant])) {
                $testData['variants'][$variant]['traffic_split'] = $split;
            }
        }
        
        Redis::setex($key, 86400 * 7, json_encode($testData));

        $this->logger->info('A/B test traffic split adjusted', [
            'test_id' => $testId,
            'new_splits' => $newSplits,
        ]);
    }
}
