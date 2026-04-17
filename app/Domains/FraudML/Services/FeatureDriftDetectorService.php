<?php declare(strict_types=1);

namespace App\Domains\FraudML\Services;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

/**
 * FeatureDriftDetectorService — Production-ready Feature Drift Detection для CatVRF
 * 
 * Поддерживаемые метрики:
 * - PSI (Population Stability Index) — ловит сдвиг пропорций в бинах
 * - KS-test (Kolmogorov-Smirnov) — ловит любые изменения в форме распределения
 * - JS Divergence (Jensen-Shannon) — симметричная мера различия распределений
 * - CombinedDriftScore — агрегированный вердикт
 * 
 * Пороги (production стандарты из Ozon/Amazon):
 * - PSI: < 0.1 (OK), 0.1-0.25 (WARNING), > 0.25 (CRITICAL)
 * - KS p-value: > 0.05 (OK), ≤ 0.05 (DRIFT DETECTED)
 * - JS Divergence: < 0.1 (OK), 0.1-0.3 (WARNING), > 0.3 (CRITICAL)
 * 
 * Критичные фичи для Medical вертикали:
 * - ai_diagnosis_frequency
 * - health_score
 * - emergency_event_rate
 * - quota_usage_ratio
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final readonly class FeatureDriftDetectorService
{
    // Thresholds
    private const PSI_THRESHOLD_WARNING = 0.1;
    private const PSI_THRESHOLD_CRITICAL = 0.25;
    private const KS_ALPHA = 0.05;
    private const KS_ALPHA_CRITICAL = 0.01;
    private const JS_THRESHOLD_WARNING = 0.1;
    private const JS_THRESHOLD_CRITICAL = 0.3;
    
    // Redis cache settings
    private const CACHE_TTL_HOURS = 24 * 7; // 7 days
    private const CACHE_PREFIX = 'ml:feature_drift:reference:';
    
    // Bin count for PSI calculation
    private const PSI_BINS = 10;

    public function __construct(
        private readonly RedisFactory $redis,
        private readonly LogManager $logger,
        private readonly PrometheusMetricsService $prometheus,
    ) {}

    /**
     * Calculate PSI (Population Stability Index)
     * 
     * PSI = sum((actual_pct - expected_pct) * ln(actual_pct / expected_pct))
     * 
     * @param array $expected Reference distribution (training data)
     * @param array $actual Current distribution (last 24h/7d)
     * @param int $bins Number of bins for histogram
     * @return array PSI result with interpretation
     */
    public function calculatePSI(array $expected, array $actual, int $bins = self::PSI_BINS): array
    {
        if (empty($expected) || empty($actual)) {
            throw new \InvalidArgumentException('Expected and actual arrays cannot be empty');
        }

        // Create histograms
        $expectedHist = $this->createHistogram($expected, $bins);
        $actualHist = $this->createHistogram($actual, $bins);

        // Calculate PSI
        $psi = 0.0;
        $binsCount = count($expectedHist);

        for ($i = 0; $i < $binsCount; $i++) {
            $expectedPct = $expectedHist[$i] / count($expected);
            $actualPct = $actualHist[$i] / count($actual);

            // Avoid division by zero
            if ($expectedPct === 0) {
                continue;
            }

            if ($actualPct === 0) {
                // If actual is zero but expected is not, add penalty
                $psi += $expectedPct; // ln(0) = -infinity, so we cap
                continue;
            }

            $psi += ($actualPct - $expectedPct) * log($actualPct / $expectedPct);
        }

        // Interpret result
        $severity = match (true) {
            $psi <= self::PSI_THRESHOLD_WARNING => 'LOW',
            $psi <= self::PSI_THRESHOLD_CRITICAL => 'MEDIUM',
            default => 'HIGH',
        };

        $driftDetected = $psi > self::PSI_THRESHOLD_CRITICAL;

        return [
            'psi' => round($psi, 6),
            'interpretation' => $driftDetected 
                ? 'Significant drift detected - ACTION REQUIRED' 
                : 'No significant drift',
            'drift_detected' => $driftDetected,
            'severity' => $severity,
            'threshold_warning' => self::PSI_THRESHOLD_WARNING,
            'threshold_critical' => self::PSI_THRESHOLD_CRITICAL,
            'sample_size_expected' => count($expected),
            'sample_size_actual' => count($actual),
            'bins' => $bins,
        ];
    }

    /**
     * Calculate KS-test (Kolmogorov-Smirnov test)
     * 
     * KS-test чувствителен к любым изменениям в распределении
     * (не только к сдвигу среднего, как PSI)
     * 
     * @param array $expected Reference distribution
     * @param array $actual Current distribution
     * @param float $alpha Significance level (default 0.05)
     * @return array KS-test result
     */
    public function calculateKS(array $expected, array $actual, float $alpha = self::KS_ALPHA): array
    {
        if (empty($expected) || empty($actual)) {
            throw new \InvalidArgumentException('Expected and actual arrays cannot be empty');
        }

        // Sort arrays
        sort($expected);
        sort($actual);

        // Calculate empirical CDFs
        $expectedCdf = $this->calculateEmpiricalCDF($expected);
        $actualCdf = $this->calculateEmpiricalCDF($actual);

        // Merge unique values for comparison
        $allValues = array_unique(array_merge($expected, $actual));
        sort($allValues);

        // Calculate KS statistic (maximum difference between CDFs)
        $ksStatistic = 0.0;
        foreach ($allValues as $value) {
            $expectedCdfValue = $this->getCDFValue($expectedCdf, $value);
            $actualCdfValue = $this->getCDFValue($actualCdf, $value);
            $diff = abs($expectedCdfValue - $actualCdfValue);
            $ksStatistic = max($ksStatistic, $diff);
        }

        // Calculate p-value using approximation
        // For large samples, we use the Kolmogorov-Smirnov distribution
        $n = count($expected);
        $m = count($actual);
        $effectiveN = (int) (($n * $m) / ($n + $m));
        
        // Approximate p-value (simplified for production)
        $pValue = $this->calculateKSPValue($ksStatistic, $effectiveN);

        // Interpret result
        $severity = match (true) {
            $pValue > $alpha => 'LOW',
            $pValue > self::KS_ALPHA_CRITICAL => 'MEDIUM',
            default => 'HIGH',
        };

        $driftDetected = $pValue <= $alpha;

        return [
            'ks_statistic' => round($ksStatistic, 6),
            'p_value' => round($pValue, 6),
            'interpretation' => $driftDetected 
                ? 'Significant drift detected - ACTION REQUIRED' 
                : 'No significant drift',
            'drift_detected' => $driftDetected,
            'severity' => $severity,
            'alpha' => $alpha,
            'sample_size_expected' => $n,
            'sample_size_actual' => $m,
        ];
    }

    /**
     * Calculate Jensen-Shannon Divergence
     * 
     * JS Divergence = 0.5 * KL(P || M) + 0.5 * KL(Q || M)
     * where M = 0.5 * (P + Q)
     * 
     * Симметричная мера, в отличие от KL Divergence
     * 
     * @param array $expected Reference distribution
     * @param array $actual Current distribution
     * @param int $bins Number of bins for histogram
     * @return array JS divergence result
     */
    public function calculateJSDivergence(array $expected, array $actual, int $bins = self::PSI_BINS): array
    {
        if (empty($expected) || empty($actual)) {
            throw new \InvalidArgumentException('Expected and actual arrays cannot be empty');
        }

        // Create histograms
        $expectedHist = $this->createHistogram($expected, $bins);
        $actualHist = $this->createHistogram($actual, $bins);

        // Convert to probabilities
        $p = array_map(fn($x) => $x / count($expected), $expectedHist);
        $q = array_map(fn($x) => $x / count($actual), $actualHist);

        // Calculate mixture distribution M = 0.5 * (P + Q)
        $m = [];
        for ($i = 0; $i < $bins; $i++) {
            $m[$i] = 0.5 * ($p[$i] + $q[$i]);
        }

        // Calculate KL(P || M)
        $klPM = $this->calculateKLDivergence($p, $m);
        
        // Calculate KL(Q || M)
        $klQM = $this->calculateKLDivergence($q, $m);

        // JS Divergence
        $jsDivergence = 0.5 * ($klPM + $klQM);

        // Convert to JS Distance (sqrt of JS Divergence)
        $jsDistance = sqrt($jsDivergence);

        // Interpret result
        $severity = match (true) {
            $jsDivergence <= self::JS_THRESHOLD_WARNING => 'LOW',
            $jsDivergence <= self::JS_THRESHOLD_CRITICAL => 'MEDIUM',
            default => 'HIGH',
        };

        $driftDetected = $jsDivergence > self::JS_THRESHOLD_CRITICAL;

        return [
            'js_divergence' => round($jsDivergence, 6),
            'js_distance' => round($jsDistance, 6),
            'interpretation' => $driftDetected 
                ? 'Significant drift detected - ACTION REQUIRED' 
                : 'No significant drift',
            'drift_detected' => $driftDetected,
            'severity' => $severity,
            'threshold_warning' => self::JS_THRESHOLD_WARNING,
            'threshold_critical' => self::JS_THRESHOLD_CRITICAL,
            'sample_size_expected' => count($expected),
            'sample_size_actual' => count($actual),
            'bins' => $bins,
        ];
    }

    /**
     * Detect drift for a single feature using all three metrics
     * 
     * @param string $featureName Feature name (e.g., 'ai_diagnosis_frequency')
     * @param string $vertical Vertical name (e.g., 'medical')
     * @param array $expected Reference distribution
     * @param array $actual Current distribution
     * @return array Combined drift detection result
     */
    public function detectDriftForFeature(
        string $featureName,
        string $vertical,
        array $expected,
        array $actual
    ): array {
        $correlationId = Uuid::uuid4()->toString();

        $this->logger->info('Feature drift detection started', [
            'feature' => $featureName,
            'vertical' => $vertical,
            'correlation_id' => $correlationId,
        ]);

        // Calculate all three metrics
        $psiResult = $this->calculatePSI($expected, $actual);
        $ksResult = $this->calculateKS($expected, $actual);
        $jsResult = $this->calculateJSDivergence($expected, $actual);

        // Calculate combined drift score
        $combinedScore = $this->calculateCombinedDriftScore($psiResult, $ksResult, $jsResult);

        // Record Prometheus metrics
        $this->prometheus->recordFeatureDriftPSI($psiResult['psi'], $featureName, $vertical, $correlationId);
        $this->prometheus->recordFeatureDriftKS($ksResult['ks_statistic'], $featureName, $vertical, $correlationId);
        $this->prometheus->recordFeatureDriftJS($jsResult['js_divergence'], $featureName, $vertical, $correlationId);
        $this->prometheus->recordFeatureDriftCombined($combinedScore['score'], $featureName, $vertical, $correlationId);

        if ($combinedScore['drift_detected']) {
            $this->prometheus->recordFeatureDriftDetected($featureName, $vertical, $combinedScore['severity'], $correlationId);
        }

        $result = [
            'feature' => $featureName,
            'vertical' => $vertical,
            'correlation_id' => $correlationId,
            'psi' => $psiResult,
            'ks' => $ksResult,
            'js_divergence' => $jsResult,
            'combined' => $combinedScore,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->logger->info('Feature drift detection completed', [
            'feature' => $featureName,
            'vertical' => $vertical,
            'combined_score' => $combinedScore['score'],
            'drift_detected' => $combinedScore['drift_detected'],
            'correlation_id' => $correlationId,
        ]);

        return $result;
    }

    /**
     * Detect drift for all features in a vertical
     * 
     * @param string $vertical Vertical name
     * @param array $featuresData Associative array: ['feature_name' => ['expected' => [], 'actual' => []]]
     * @return array Combined drift detection for all features
     */
    public function detectAllFeatures(string $vertical, array $featuresData): array
    {
        $correlationId = Uuid::uuid4()->toString();
        $results = [];
        $maxSeverity = 'LOW';
        $maxDriftScore = 0.0;

        foreach ($featuresData as $featureName => $data) {
            if (!isset($data['expected']) || !isset($data['actual'])) {
                $this->logger->warning('Missing expected or actual data for feature', [
                    'feature' => $featureName,
                    'correlation_id' => $correlationId,
                ]);
                continue;
            }

            $result = $this->detectDriftForFeature(
                $featureName,
                $vertical,
                $data['expected'],
                $data['actual']
            );

            $results[$featureName] = $result;

            // Track maximum severity
            $severityOrder = ['LOW' => 0, 'MEDIUM' => 1, 'HIGH' => 2];
            if ($severityOrder[$result['combined']['severity']] > $severityOrder[$maxSeverity]) {
                $maxSeverity = $result['combined']['severity'];
            }

            // Track maximum drift score
            if ($result['combined']['score'] > $maxDriftScore) {
                $maxDriftScore = $result['combined']['score'];
            }
        }

        $overallResult = [
            'vertical' => $vertical,
            'correlation_id' => $correlationId,
            'features' => $results,
            'summary' => [
                'total_features' => count($results),
                'drift_detected_features' => count(array_filter($results, fn($r) => $r['combined']['drift_detected'])),
                'max_severity' => $maxSeverity,
                'max_drift_score' => round($maxDriftScore, 6),
                'overall_drift_detected' => $maxSeverity === 'HIGH',
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        // Record overall metrics
        $this->prometheus->recordVerticalDriftScore($maxDriftScore, $vertical, $correlationId);

        if ($maxSeverity === 'HIGH') {
            $this->prometheus->recordVerticalDriftDetected($vertical, $correlationId);
        }

        return $overallResult;
    }

    /**
     * Store reference distribution in Redis cache
     * 
     * @param string $featureName Feature name
     * @param string $vertical Vertical name
     * @param array $referenceData Reference distribution data
     * @return bool Success status
     */
    public function storeReferenceDistribution(string $featureName, string $vertical, array $referenceData): bool
    {
        $cacheKey = $this->getReferenceCacheKey($featureName, $vertical);
        
        try {
            Cache::tags(['ml', 'feature_drift', "vertical:{$vertical}"])
                ->put($cacheKey, $referenceData, now()->addHours(self::CACHE_TTL_HOURS));

            $this->logger->info('Reference distribution stored', [
                'feature' => $featureName,
                'vertical' => $vertical,
                'sample_size' => count($referenceData),
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to store reference distribution', [
                'feature' => $featureName,
                'vertical' => $vertical,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get reference distribution from Redis cache
     * 
     * @param string $featureName Feature name
     * @param string $vertical Vertical name
     * @return array|null Reference distribution or null if not found
     */
    public function getReferenceDistribution(string $featureName, string $vertical): ?array
    {
        $cacheKey = $this->getReferenceCacheKey($featureName, $vertical);
        
        $data = Cache::tags(['ml', 'feature_drift', "vertical:{$vertical}"])
            ->get($cacheKey);

        if ($data === null) {
            $this->logger->warning('Reference distribution not found in cache', [
                'feature' => $featureName,
                'vertical' => $vertical,
            ]);
        }

        return $data;
    }

    /**
     * Invalidate reference distribution cache for a vertical
     * 
     * @param string $vertical Vertical name
     * @return void
     */
    public function invalidateReferenceCache(string $vertical): void
    {
        Cache::tags(['ml', 'feature_drift', "vertical:{$vertical}"])->flush();

        $this->logger->info('Reference distribution cache invalidated', [
            'vertical' => $vertical,
        ]);
    }

    /**
     * Calculate combined drift score from PSI, KS, and JS results
     * 
     * Combined score uses weighted average:
     * - PSI: 40% (good for bin-based shifts)
     * - KS: 35% (good for distribution shape changes)
     * - JS: 25% (symmetric measure)
     * 
     * @param array $psiResult PSI result
     * @param array $ksResult KS result
     * @param array $jsResult JS result
     * @return array Combined drift score
     */
    private function calculateCombinedDriftScore(array $psiResult, array $ksResult, array $jsResult): array
    {
        // Normalize metrics to 0-1 scale
        $psiNormalized = min($psiResult['psi'] / self::PSI_THRESHOLD_CRITICAL, 1.0);
        
        // KS: use 1 - p_value for normalization (lower p-value = higher drift)
        $ksNormalized = 1.0 - $ksResult['p_value'];
        
        $jsNormalized = min($jsResult['js_divergence'] / self::JS_THRESHOLD_CRITICAL, 1.0);

        // Weighted average
        $combinedScore = ($psiNormalized * 0.4) + ($ksNormalized * 0.35) + ($jsNormalized * 0.25);

        // Determine severity based on combined score
        $severity = match (true) {
            $combinedScore <= 0.3 => 'LOW',
            $combinedScore <= 0.7 => 'MEDIUM',
            default => 'HIGH',
        };

        $driftDetected = $psiResult['drift_detected'] || $ksResult['drift_detected'] || $jsResult['drift_detected'];

        return [
            'score' => round($combinedScore, 6),
            'drift_detected' => $driftDetected,
            'severity' => $severity,
            'components' => [
                'psi_normalized' => round($psiNormalized, 6),
                'ks_normalized' => round($ksNormalized, 6),
                'js_normalized' => round($jsNormalized, 6),
            ],
        ];
    }

    /**
     * Create histogram for PSI/JS calculation
     * 
     * @param array $data Input data
     * @param int $bins Number of bins
     * @return array Histogram counts
     */
    private function createHistogram(array $data, int $bins): array
    {
        if (count($data) === 0) {
            return array_fill(0, $bins, 0);
        }

        $min = min($data);
        $max = max($data);
        
        // Handle edge case where all values are the same
        if ($min === $max) {
            $histogram = array_fill(0, $bins, 0);
            $histogram[0] = count($data);
            return $histogram;
        }

        $binWidth = ($max - $min) / $bins;
        $histogram = array_fill(0, $bins, 0);

        foreach ($data as $value) {
            $binIndex = (int) floor(($value - $min) / $binWidth);
            $binIndex = min($binIndex, $bins - 1); // Handle max value
            $histogram[$binIndex]++;
        }

        return $histogram;
    }

    /**
     * Calculate empirical CDF
     * 
     * @param array $sortedData Sorted data
     * @return array CDF values
     */
    private function calculateEmpiricalCDF(array $sortedData): array
    {
        $n = count($sortedData);
        $cdf = [];
        
        foreach ($sortedData as $i => $value) {
            $cdf[$value] = ($i + 1) / $n;
        }

        return $cdf;
    }

    /**
     * Get CDF value for a specific point (interpolated)
     * 
     * @param array $cdf CDF array
     * @param float $value Value to lookup
     * @return float CDF value
     */
    private function getCDFValue(array $cdf, float $value): float
    {
        $values = array_keys($cdf);
        sort($values);

        // Find the largest value <= $value
        $cdfValue = 0.0;
        foreach ($values as $v) {
            if ($v <= $value) {
                $cdfValue = $cdf[$v];
            } else {
                break;
            }
        }

        return $cdfValue;
    }

    /**
     * Calculate p-value for KS statistic (simplified approximation)
     * 
     * @param float $ksStatistic KS statistic
     * @param float $n Effective sample size
     * @return float Approximate p-value
     */
    private function calculateKSPValue(float $ksStatistic, float $n): float
    {
        // Simplified approximation for production
        // For accurate results, use scipy.stats.ks_2samp in Python
        
        if ($ksStatistic === 0.0) {
            return 1.0;
        }

        $lambda = (sqrt($n) + 0.12 + 0.11 / sqrt($n)) * $ksStatistic;
        
        // Approximate using Kolmogorov distribution
        $sum = 0.0;
        for ($k = 1; $k <= 100; $k++) {
            $term = (-1) ** ($k - 1) * exp(-2 * $k * $k * $lambda * $lambda);
            $sum += $term;
        }

        $pValue = 2 * abs($sum);
        
        return min(max($pValue, 0.0), 1.0);
    }

    /**
     * Calculate KL Divergence
     * 
     * @param array $p Distribution P
     * @param array $q Distribution Q
     * @return float KL divergence
     */
    private function calculateKLDivergence(array $p, array $q): float
    {
        $kl = 0.0;
        $n = count($p);

        for ($i = 0; $i < $n; $i++) {
            if ($p[$i] === 0) {
                continue;
            }

            if ($q[$i] === 0) {
                // If Q is zero but P is not, add penalty
                $kl += $p[$i] * log($p[$i] / 1e-10); // Small epsilon
                continue;
            }

            $kl += $p[$i] * log($p[$i] / $q[$i]);
        }

        return $kl;
    }

    /**
     * Generate Redis cache key for reference distribution
     * 
     * @param string $featureName Feature name
     * @param string $vertical Vertical name
     * @return string Cache key
     */
    private function getReferenceCacheKey(string $featureName, string $vertical): string
    {
        return self::CACHE_PREFIX . $vertical . ':' . $featureName;
    }
}
