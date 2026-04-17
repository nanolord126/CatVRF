<?php

declare(strict_types=1);

namespace App\Domains\FraudML\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * PaymentFeatureDriftMonitor - Feature drift monitoring for payment fraud ML
 * 
 * CRITICAL: Monitors payment-specific features for drift detection
 * - Tracks distribution of key features over time
 * - Calculates PSI (Population Stability Index) for drift detection
 * - Alerts when drift exceeds threshold
 * - Separate monitoring per vertical
 * 
 * CANON 2026 - Production Ready
 */
final readonly class PaymentFeatureDriftMonitor
{
    private const DRIFT_THRESHOLD = 0.2; // PSI threshold
    private const WINDOW_SIZE_HOURS = 24;
    private const REFERENCE_WINDOW_HOURS = 168; // 7 days
    private const CACHE_TTL_HOURS = 1;

    // Payment-specific features to monitor
    private const MONITORED_FEATURES = [
        'wallet_balance_ratio',
        'urgency_score',
        'payment_count_24h',
        'previous_payment_success_rate_7d',
        'consultation_price_spike_ratio',
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Record feature value for drift monitoring
     */
    public function recordFeature(
        string $featureName,
        float $value,
        string $verticalCode = 'payment',
        ?string $correlationId = null,
    ): void {
        if (!in_array($featureName, self::MONITORED_FEATURES, true)) {
            return;
        }

        $hourKey = now()->format('Y-m-d-H');
        $cacheKey = "fraud_ml:drift:{$verticalCode}:{$featureName}:{$hourKey}";

        $current = Cache::get($cacheKey, []);
        $current[] = $value;

        Cache::put($cacheKey, $current, now()->addHours(self::CACHE_TTL_HOURS));

        $this->logger->debug('Payment feature recorded for drift monitoring', [
            'feature' => $featureName,
            'value' => $value,
            'vertical_code' => $verticalCode,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Calculate drift for a feature
     */
    public function calculateDrift(
        string $featureName,
        string $verticalCode = 'payment',
    ): array {
        $currentDistribution = $this->getFeatureDistribution(
            $featureName,
            $verticalCode,
            self::WINDOW_SIZE_HOURS,
        );

        $referenceDistribution = $this->getFeatureDistribution(
            $featureName,
            $verticalCode,
            self::REFERENCE_WINDOW_HOURS,
        );

        if (empty($currentDistribution) || empty($referenceDistribution)) {
            return [
                'feature' => $featureName,
                'drift_detected' => false,
                'psi' => 0.0,
                'reason' => 'insufficient_data',
            ];
        }

        $psi = $this->calculatePSI($referenceDistribution, $currentDistribution);
        $driftDetected = $psi > self::DRIFT_THRESHOLD;

        $result = [
            'feature' => $featureName,
            'vertical_code' => $verticalCode,
            'drift_detected' => $driftDetected,
            'psi' => $psi,
            'threshold' => self::DRIFT_THRESHOLD,
            'current_samples' => count($currentDistribution),
            'reference_samples' => count($referenceDistribution),
        ];

        if ($driftDetected) {
            $this->logger->warning('Payment feature drift detected', $result);
        }

        return $result;
    }

    /**
     * Calculate drift for all monitored features
     */
    public function calculateAllDrifts(string $verticalCode = 'payment'): array
    {
        $results = [];

        foreach (self::MONITORED_FEATURES as $feature) {
            $results[] = $this->calculateDrift($feature, $verticalCode);
        }

        return $results;
    }

    /**
     * Get feature distribution from cache
     */
    private function getFeatureDistribution(
        string $featureName,
        string $verticalCode,
        int $windowHours,
    ): array {
        $distribution = [];
        $now = now();

        for ($i = 0; $i < $windowHours; $i++) {
            $hourKey = $now->subHours($i)->format('Y-m-d-H');
            $cacheKey = "fraud_ml:drift:{$verticalCode}:{$featureName}:{$hourKey}";
            $values = Cache::get($cacheKey, []);

            foreach ($values as $value) {
                $distribution[] = $value;
            }
        }

        return $distribution;
    }

    /**
     * Calculate Population Stability Index (PSI)
     * PSI measures the shift in distribution between reference and current
     */
    private function calculatePSI(array $reference, array $current): float
    {
        if (empty($reference) || empty($current)) {
            return 0.0;
        }

        // Create bins (10 bins based on reference distribution)
        $bins = $this->createBins($reference, 10);

        // Calculate percentages for each bin
        $refPercentages = $this->calculateBinPercentages($reference, $bins);
        $currPercentages = $this->calculateBinPercentages($current, $bins);

        // Calculate PSI
        $psi = 0.0;
        foreach ($bins as $i => $bin) {
            $refP = $refPercentages[$i] ?? 0.0001; // Small epsilon to avoid division by zero
            $currP = $currPercentages[$i] ?? 0.0001;

            $psi += ($currP - $refP) * log($currP / $refP);
        }

        return $psi;
    }

    /**
     * Create bins for PSI calculation
     */
    private function createBins(array $values, int $numBins): array
    {
        if (empty($values)) {
            return [];
        }

        sort($values);
        $min = $values[0];
        $max = $values[count($values) - 1];
        $binSize = ($max - $min) / $numBins;

        $bins = [];
        for ($i = 0; $i < $numBins; $i++) {
            $bins[] = [
                'min' => $min + ($i * $binSize),
                'max' => $min + (($i + 1) * $binSize),
            ];
        }

        return $bins;
    }

    /**
     * Calculate percentage of values in each bin
     */
    private function calculateBinPercentages(array $values, array $bins): array
    {
        $counts = array_fill(0, count($bins), 0);
        $total = count($values);

        if ($total === 0) {
            return $counts;
        }

        foreach ($values as $value) {
            foreach ($bins as $i => $bin) {
                if ($value >= $bin['min'] && $value < $bin['max']) {
                    $counts[$i]++;
                    break;
                }
                // Handle edge case for max value
                if ($i === count($bins) - 1 && $value >= $bin['max']) {
                    $counts[$i]++;
                    break;
                }
            }
        }

        return array_map(fn($count) => $count / $total, $counts);
    }

    /**
     * Get summary of drift status
     */
    public function getDriftSummary(string $verticalCode = 'payment'): array
    {
        $drifts = $this->calculateAllDrifts($verticalCode);
        $driftedFeatures = array_filter($drifts, fn($d) => $d['drift_detected']);

        return [
            'vertical_code' => $verticalCode,
            'total_features' => count($drifts),
            'drifted_features' => count($driftedFeatures),
            'drift_detected' => count($driftedFeatures) > 0,
            'drifts' => $drifts,
        ];
    }
}
