<?php

declare(strict_types=1);

namespace App\Services\ML;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Config\Repository as ConfigRepository;
use Psr\Log\LoggerInterface;

/**
 * Feature Drift Detection Service
 * 
 * Detects feature drift using PSI (Population Stability Index) and KS-test (Kolmogorov-Smirnov).
 * Supports per-vertical thresholds and reference distribution storage in Redis.
 * 
 * Production-ready for CatVRF ML pipeline with medical compliance.
 */
final readonly class FeatureDriftDetectorService
{
    private const REDIS_PREFIX = 'fraud:ml:drift:reference:';
    private const CACHE_TTL = 86400; // 24 hours

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConfigRepository $config
    ) {}

    /**
     * Calculate PSI (Population Stability Index) for a single feature
     * 
     * Production-ready implementation based on percentile-based binning (CatVRF standard).
     * PSI = sum((actual% - expected%) * ln(actual% / expected%))
     * 
     * Thresholds:
     * - PSI < 0.10: No significant drift (green)
     * - 0.10 <= PSI < 0.25: Minor drift (yellow, monitor)
     * - PSI >= 0.25: Significant drift (red, shadow mode + alert)
     * 
     * Medical vertical: PSI >= 0.15 already triggers action (152-ФЗ compliance)
     * 
     * @param array $expectedValues Reference values (training data - raw array)
     * @param array $actualValues Current values (inference data - raw array)
     * @param string $featureName Feature name for logging
     * @param int $bins Number of bins (default 10)
     * @param float $epsilon Small value to prevent division by zero
     * @return array PSI result with interpretation
     */
    public function calculatePSI(array $expectedValues, array $actualValues, string $featureName, int $bins = 10, float $epsilon = 1e-6): array
    {
        if (empty($expectedValues) || empty($actualValues)) {
            return [
                'psi' => 0.0,
                'interpretation' => 'Insufficient data',
                'num_bins' => 0,
                'max_psi_contribution' => 0.0,
                'drift_severity' => 'NONE',
            ];
        }

        // Create unified breakpoints based on combined data (percentile-based binning)
        $allData = array_merge($expectedValues, $actualValues);
        sort($allData);
        
        $breakpoints = $this->calculatePercentileBreakpoints($allData, $bins);
        $breakpoints = array_unique(array_map(function($v) {
            return round($v, 6);
        }, $breakpoints));
        sort($breakpoints);

        // Bin the data
        $expectedBinned = $this->binValues($expectedValues, $breakpoints);
        $actualBinned = $this->binValues($actualValues, $breakpoints);

        // Count frequencies
        $maxBin = count($breakpoints);
        $expectedCounts = $this->countBins($expectedBinned, $maxBin);
        $actualCounts = $this->countBins($actualBinned, $maxBin);

        // Calculate proportions with epsilon protection
        $expectedSum = array_sum($expectedCounts);
        $actualSum = array_sum($actualCounts);
        
        $expectedProp = [];
        $actualProp = [];
        $psiValues = [];

        for ($i = 0; $i <= $maxBin; $i++) {
            $expectedCount = $expectedCounts[$i] ?? 0;
            $actualCount = $actualCounts[$i] ?? 0;

            $expectedProp[$i] = ($expectedCount + $epsilon) / ($expectedSum + $epsilon * ($maxBin + 1));
            $actualProp[$i] = ($actualCount + $epsilon) / ($actualSum + $epsilon * ($maxBin + 1));

            // PSI for this bin
            if ($expectedProp[$i] > 0 && $actualProp[$i] > 0) {
                $psiValues[$i] = ($actualProp[$i] - $expectedProp[$i]) * log($actualProp[$i] / $expectedProp[$i]);
            } else {
                $psiValues[$i] = 0.0;
            }
        }

        $psi = abs(array_sum($psiValues));
        $maxPsiContribution = !empty($psiValues) ? max($psiValues) : 0.0;

        // Interpretation based on thresholds
        $interpretation = $psi < 0.10 ? 'No significant drift' : 
                         ($psi < 0.25 ? 'Minor drift - monitor' : 'Significant drift - ACTION REQUIRED');
        
        $driftSeverity = $psi < 0.10 ? 'LOW' : 
                        ($psi < 0.25 ? 'MEDIUM' : 'HIGH');

        $this->logger->debug('PSI calculated', [
            'feature' => $featureName,
            'psi' => round($psi, 6),
            'interpretation' => $interpretation,
            'num_bins' => count($breakpoints),
            'max_psi_contribution' => round($maxPsiContribution, 6),
            'drift_severity' => $driftSeverity,
        ]);

        return [
            'psi' => round($psi, 6),
            'interpretation' => $interpretation,
            'num_bins' => count($breakpoints),
            'max_psi_contribution' => round($maxPsiContribution, 6),
            'drift_severity' => $driftSeverity,
        ];
    }

    /**
     * Calculate percentile breakpoints for binning
     * 
     * @param array $sortedValues Sorted array of values
     * @param int $bins Number of bins
     * @return array Breakpoints
     */
    private function calculatePercentileBreakpoints(array $sortedValues, int $bins): array
    {
        $breakpoints = [];
        $count = count($sortedValues);
        
        for ($i = 0; $i <= $bins; $i++) {
            $percentile = ($i / $bins) * 100;
            $index = (int) (($percentile / 100) * ($count - 1));
            $breakpoints[] = $sortedValues[$index] ?? $sortedValues[$count - 1];
        }

        return $breakpoints;
    }

    /**
     * Bin values into breakpoints
     * 
     * @param array $values Values to bin
     * @param array $breakpoints Breakpoints
     * @return array Binned values (bin indices)
     */
    private function binValues(array $values, array $breakpoints): array
    {
        $binned = [];
        
        foreach ($values as $value) {
            $binIndex = 0;
            for ($i = 0; $i < count($breakpoints) - 1; $i++) {
                if ($value <= $breakpoints[$i + 1]) {
                    $binIndex = $i;
                    break;
                }
                $binIndex = count($breakpoints) - 1;
            }
            $binned[] = $binIndex;
        }

        return $binned;
    }

    /**
     * Count frequencies of bins
     * 
     * @param array $binnedValues Binned values
     * @param int $maxBin Maximum bin index
     * @return array Bin counts
     */
    private function countBins(array $binnedValues, int $maxBin): array
    {
        $counts = array_fill(0, $maxBin + 1, 0);
        
        foreach ($binnedValues as $binIndex) {
            if (isset($counts[$binIndex])) {
                $counts[$binIndex]++;
            }
        }

        return $counts;
    }

    /**
     * Calculate KS-test statistic for a single feature
     * 
     * KS = max(|CDF_actual - CDF_expected|)
     * 
     * Thresholds:
     * - KS < 0.05: No significant drift
     * - 0.05 <= KS < 0.1: Moderate drift
     * - KS >= 0.1: Significant drift
     * 
     * @param array $expectedValues Reference values (training data)
     * @param array $actualValues Current values (inference data)
     * @param string $featureName Feature name for logging
     * @return float KS statistic
     */
    public function calculateKSTest(array $expectedValues, array $actualValues, string $featureName): float
    {
        if (empty($expectedValues) || empty($actualValues)) {
            $this->logger->warning('KS-test skipped: empty data', [
                'feature' => $featureName,
                'expected_count' => count($expectedValues),
                'actual_count' => count($actualValues),
            ]);
            return 0.0;
        }

        sort($expectedValues);
        sort($actualValues);

        $expectedCdf = $this->calculateCDF($expectedValues);
        $actualCdf = $this->calculateCDF($actualValues);

        // Merge unique values to compare CDFs at same points
        $allValues = array_unique(array_merge($expectedValues, $actualValues));
        $maxDiff = 0.0;

        foreach ($allValues as $value) {
            $expectedValue = $expectedCdf[$value] ?? 0.0;
            $actualValue = $actualCdf[$value] ?? 0.0;
            $diff = abs($actualValue - $expectedValue);
            $maxDiff = max($maxDiff, $diff);
        }

        $this->logger->debug('KS-test calculated', [
            'feature' => $featureName,
            'ks_statistic' => $maxDiff,
            'expected_count' => count($expectedValues),
            'actual_count' => count($actualValues),
        ]);

        return $maxDiff;
    }

    /**
     * Calculate cumulative distribution function
     * 
     * @param array $sortedValues Sorted array of values
     * @return array CDF as [value => cumulative_probability]
     */
    private function calculateCDF(array $sortedValues): array
    {
        $cdf = [];
        $count = count($sortedValues);
        
        if ($count === 0) {
            return $cdf;
        }

        for ($i = 0; $i < $count; $i++) {
            $value = $sortedValues[$i];
            $cdf[$value] = ($i + 1) / $count;
        }

        return $cdf;
    }

    /**
     * Detect drift for multiple features
     * 
     * @param array $featuresData ['feature_name' => ['expected' => [], 'actual' => []]]
     * @param string|null $verticalCode Vertical code for per-vertical thresholds
     * @return array Drift report
     */
    public function detectDrift(array $featuresData, ?string $verticalCode = null): array
    {
        $thresholds = $this->getDriftThresholds($verticalCode);
        $driftReport = [
            'vertical_code' => $verticalCode,
            'features_checked' => count($featuresData),
            'drifted_features' => [],
            'moderate_drift_features' => [],
            'max_psi' => 0.0,
            'max_ks' => 0.0,
            'overall_drift_detected' => false,
            'timestamp' => now()->toIso8601String(),
        ];

        foreach ($featuresData as $featureName => $data) {
            $expected = $data['expected'] ?? [];
            $actual = $data['actual'] ?? [];

            if (empty($expected) || empty($actual)) {
                $this->logger->warning('Drift detection skipped: empty data', [
                    'feature' => $featureName,
                    'expected_count' => count($expected),
                    'actual_count' => count($actual),
                ]);
                continue;
            }

            // Determine if data is categorical (for PSI) or continuous (for KS)
            $isCategorical = $this->isCategoricalData($expected, $actual);

            if ($isCategorical) {
                // Use PSI for categorical data
                $expectedDist = $this->calculateDistribution($expected);
                $actualDist = $this->calculateDistribution($actual);
                
                // Convert distributions to value arrays for new PSI calculation
                $expectedValues = [];
                $actualValues = [];
                foreach ($expectedDist as $value => $count) {
                    for ($i = 0; $i < $count; $i++) {
                        $expectedValues[] = $value;
                    }
                }
                foreach ($actualDist as $value => $count) {
                    for ($i = 0; $i < $count; $i++) {
                        $actualValues[] = $value;
                    }
                }
                
                $psiResult = $this->calculatePSI($expectedValues, $actualValues, $featureName);
                $driftScore = $psiResult['psi'];
                $metricType = 'psi';
                
                // Store additional PSI metadata
                $psiMetadata = [
                    'interpretation' => $psiResult['interpretation'],
                    'drift_severity' => $psiResult['drift_severity'],
                    'num_bins' => $psiResult['num_bins'],
                    'max_psi_contribution' => $psiResult['max_psi_contribution'],
                ];
            } else {
                // Use KS-test for continuous data
                $driftScore = $this->calculateKSTest($expected, $actual, $featureName);
                $metricType = 'ks';
                $psiMetadata = [];
            }

            $driftReport['max_psi'] = max($driftReport['max_psi'], $metricType === 'psi' ? $driftScore : 0);
            $driftReport['max_ks'] = max($driftReport['max_ks'], $metricType === 'ks' ? $driftScore : 0);

            // Check against thresholds
            $threshold = $metricType === 'psi' ? $thresholds['psi_critical'] : $thresholds['ks_critical'];
            $moderateThreshold = $metricType === 'psi' ? $thresholds['psi_moderate'] : $thresholds['ks_moderate'];

            if ($driftScore >= $threshold) {
                $driftReport['drifted_features'][] = array_merge([
                    'feature' => $featureName,
                    'metric' => $metricType,
                    'score' => $driftScore,
                    'threshold' => $threshold,
                ], $psiMetadata);
                $driftReport['overall_drift_detected'] = true;
            } elseif ($driftScore >= $moderateThreshold) {
                $driftReport['moderate_drift_features'][] = array_merge([
                    'feature' => $featureName,
                    'metric' => $metricType,
                    'score' => $driftScore,
                    'threshold' => $moderateThreshold,
                ], $psiMetadata);
            }
        }

        $this->logDriftReport($driftReport);

        return $driftReport;
    }

    /**
     * Store reference distribution in Redis
     * 
     * @param string $modelVersion Model version
     * @param string $featureName Feature name
     * @param array $distribution Reference distribution
     * @param string|null $verticalCode Vertical code
     * @return bool Success status
     */
    public function storeReferenceDistribution(
        string $modelVersion,
        string $featureName,
        array $distribution,
        ?string $verticalCode = null
    ): bool {
        try {
            $key = $this->getReferenceKey($modelVersion, $featureName, $verticalCode);
            $serialized = json_encode($distribution, JSON_THROW_ON_ERROR);
            
            Redis::setex($key, self::CACHE_TTL, $serialized);

            $this->logger->info('Reference distribution stored', [
                'model_version' => $modelVersion,
                'feature' => $featureName,
                'vertical_code' => $verticalCode,
                'redis_key' => $key,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to store reference distribution', [
                'model_version' => $modelVersion,
                'feature' => $featureName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Retrieve reference distribution from Redis
     * 
     * @param string $modelVersion Model version
     * @param string $featureName Feature name
     * @param string|null $verticalCode Vertical code
     * @return array|null Reference distribution or null if not found
     */
    public function getReferenceDistribution(
        string $modelVersion,
        string $featureName,
        ?string $verticalCode = null
    ): ?array {
        try {
            $key = $this->getReferenceKey($modelVersion, $featureName, $verticalCode);
            $serialized = Redis::get($key);

            if ($serialized === null) {
                return null;
            }

            return json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve reference distribution', [
                'model_version' => $modelVersion,
                'feature' => $featureName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get drift thresholds for a vertical (or default)
     * 
     * @param string|null $verticalCode Vertical code
     * @return array Thresholds
     */
    private function getDriftThresholds(?string $verticalCode = null): array
    {
        $defaultThresholds = [
            'psi_critical' => 0.25,
            'psi_moderate' => 0.1,
            'ks_critical' => 0.1,
            'ks_moderate' => 0.05,
        ];

        if ($verticalCode === null) {
            return $defaultThresholds;
        }

        $verticalThresholds = $this->config->get("fraud.drift_thresholds.{$verticalCode}");

        return $verticalThresholds ?? $defaultThresholds;
    }

    /**
     * Check if data is categorical (for PSI) or continuous (for KS)
     * 
     * @param array $expected Expected values
     * @param array $actual Actual values
     * @return bool True if categorical, false if continuous
     */
    private function isCategoricalData(array $expected, array $actual): bool
    {
        // Heuristic: if most values are strings or have low cardinality (< 20), treat as categorical
        $allValues = array_merge($expected, $actual);
        $uniqueCount = count(array_unique($allValues));
        $totalCount = count($allValues);

        if ($totalCount === 0) {
            return false;
        }

        // If cardinality is low (< 20) or values are strings, use PSI
        $hasStrings = false;
        foreach ($allValues as $value) {
            if (is_string($value)) {
                $hasStrings = true;
                break;
            }
        }

        return $uniqueCount < 20 || $hasStrings;
    }

    /**
     * Calculate distribution (histogram) from values
     * 
     * @param array $values Values
     * @return array Distribution as [bin => count]
     */
    private function calculateDistribution(array $values): array
    {
        $distribution = [];

        foreach ($values as $value) {
            $key = is_string($value) ? $value : (string) $value;
            $distribution[$key] = ($distribution[$key] ?? 0) + 1;
        }

        return $distribution;
    }

    /**
     * Generate Redis key for reference distribution
     * 
     * @param string $modelVersion Model version
     * @param string $featureName Feature name
     * @param string|null $verticalCode Vertical code
     * @return string Redis key
     */
    private function getReferenceKey(string $modelVersion, string $featureName, ?string $verticalCode = null): string
    {
        $parts = [self::REDIS_PREFIX, $modelVersion, $featureName];
        
        if ($verticalCode !== null) {
            $parts[] = $verticalCode;
        }

        return implode(':', $parts);
    }

    /**
     * Log drift report
     * 
     * @param array $driftReport Drift report
     */
    private function logDriftReport(array $driftReport): void
    {
        $level = $driftReport['overall_drift_detected'] ? 'warning' : 'info';

        Log::channel('fraud_alert')->$level('Feature drift detection completed', [
            'vertical_code' => $driftReport['vertical_code'],
            'features_checked' => $driftReport['features_checked'],
            'drifted_features_count' => count($driftReport['drifted_features']),
            'moderate_drift_features_count' => count($driftReport['moderate_drift_features']),
            'max_psi' => $driftReport['max_psi'],
            'max_ks' => $driftReport['max_ks'],
            'overall_drift_detected' => $driftReport['overall_drift_detected'],
        ]);
    }
}
