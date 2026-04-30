<?php

declare(strict_types=1);

namespace App\Services\Behavioral;

use Psr\Log\LoggerInterface;

use App\Models\BehavioralBaseline;
use App\Models\BehavioralSample;
use App\Models\User;
use Illuminate\Log\LogManager;
use Illuminate\Support\Collection;
use Carbon\CarbonImmutable;
use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;

/**
 * Mouse Dynamics Service
 *
 * Analyzes mouse movement patterns (velocity, acceleration, curvature)
 * for continuous authentication
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class MouseDynamicsService
{
    use WithAuditLogging;

    private const MIN_FEATURES_FOR_BASELINE = 20;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly AuditService $audit,
    ) {}

    /**
     * Analyze mouse dynamics
     *
     * @param  User  $user  User to analyze
     * @param  array  $mouseData  Mouse data from frontend
     * @return array Analysis result
     */
    public function analyze(User $user, array $mouseData): array
    {
        $features = $this->extractFeatures($mouseData);
        $baseline = $this->getBaseline($user);

        if (! $baseline || ! $baseline->hasEnoughSamples(self::MIN_FEATURES_FOR_BASELINE)) {
            $this->storeSample($user, $mouseData);

            return [
                'score' => null,
                'confidence' => 0.0,
                'baseline_available' => false,
                'message' => 'Baseline not yet established',
            ];
        }

        $baselineData = $baseline->baseline_data;
        $score = $this->compare($features, $baselineData);

        return [
            'score' => $score,
            'confidence' => $this->calculateConfidence($baseline->sample_count),
            'baseline_available' => true,
            'features' => $features,
        ];
    }

    /**
     * Extract features from mouse data
     *
     * @param  array  $mouseData  Raw mouse data
     * @return array Extracted features
     */
    public function extractFeatures(array $mouseData): array
    {
        $events = $mouseData['events'] ?? [];

        if (empty($events)) {
            return $this->getDefaultFeatures();
        }

        $velocities = [];
        $accelerations = [];
        $curvatures = [];
        $directionChanges = 0;

        for ($i = 1; $i < count($events); $i++) {
            $prev = $events[$i - 1];
            $curr = $events[$i];

            $dx = $curr['x'] - $prev['x'];
            $dy = $curr['y'] - $prev['y'];
            $dt = ($curr['timestamp'] - $prev['timestamp']) / 1000; // Convert to seconds

            if ($dt <= 0) {
                continue;
            }

            $velocity = sqrt($dx * $dx + $dy * $dy) / $dt;
            $velocities[] = $velocity;

            if ($i > 1) {
                $prevVelocity = $velocities[count($velocities) - 2];
                $acceleration = abs($velocity - $prevVelocity) / $dt;
                $accelerations[] = $acceleration;
            }

            // Calculate curvature (angle change)
            if ($i > 1) {
                $prevPrev = $events[$i - 2];
                $v1x = $prev['x'] - $prevPrev['x'];
                $v1y = $prev['y'] - $prevPrev['y'];
                $v2x = $curr['x'] - $prev['x'];
                $v2y = $curr['y'] - $prev['y'];

                $dotProduct = $v1x * $v2x + $v1y * $v2y;
                $magnitude1 = sqrt($v1x * $v1x + $v1y * $v1y);
                $magnitude2 = sqrt($v2x * $v2x + $v2y * $v2y);

                if ($magnitude1 > 0 && $magnitude2 > 0) {
                    $cosAngle = $dotProduct / ($magnitude1 * $magnitude2);
                    $cosAngle = max(-1, min(1, $cosAngle));
                    $angle = acos($cosAngle);
                    $curvatures[] = $angle;

                    // Count direction changes (significant angle > 45 degrees)
                    if ($angle > M_PI / 4) {
                        $directionChanges++;
                    }
                }
            }
        }

        $clicks = $mouseData['clicks'] ?? [];
        $scrolls = $mouseData['scrolls'] ?? [];

        $totalTime = 0;
        if (! empty($events)) {
            $totalTime = ($events[count($events) - 1]['timestamp'] - $events[0]['timestamp']) / 1000;
        }

        $totalDistance = 0;
        for ($i = 1; $i < count($events); $i++) {
            $prev = $events[$i - 1];
            $curr = $events[$i];
            $totalDistance += sqrt(
                pow($curr['x'] - $prev['x'], 2) +
                pow($curr['y'] - $prev['y'], 2)
            );
        }

        $straightLineDistance = 0;
        if (count($events) >= 2) {
            $first = $events[0];
            $last = $events[count($events) - 1];
            $straightLineDistance = sqrt(
                pow($last['x'] - $first['x'], 2) +
                pow($last['y'] - $first['y'], 2)
            );
        }

        return [
            'avg_velocity' => ! empty($velocities) ? array_sum($velocities) / count($velocities) : 0,
            'std_velocity' => ! empty($velocities) ? $this->calculateStdDev($velocities) : 0,
            'avg_acceleration' => ! empty($accelerations) ? array_sum($accelerations) / count($accelerations) : 0,
            'std_acceleration' => ! empty($accelerations) ? $this->calculateStdDev($accelerations) : 0,
            'movement_smoothness' => ! empty($curvatures) ? 1 - (array_sum($curvatures) / count($curvatures)) : 0,
            'curvature' => ! empty($curvatures) ? array_sum($curvatures) / count($curvatures) : 0,
            'click_frequency' => $totalTime > 0 ? count($clicks) / $totalTime : 0,
            'scroll_frequency' => $totalTime > 0 ? count($scrolls) / $totalTime : 0,
            'pause_ratio' => $this->calculatePauseRatio($events),
            'direction_changes' => $directionChanges,
            'path_efficiency' => $straightLineDistance > 0 ? $straightLineDistance / max($totalDistance, 1) : 0,
            'event_count' => count($events),
            'total_distance' => $totalDistance,
            'total_time' => $totalTime,
        ];
    }

    /**
     * Compare mouse pattern to baseline
     *
     * @param  array  $features  Current features
     * @param  array  $baseline  Baseline features
     * @return float Similarity score (0.0 to 1.0)
     */
    public function compare(array $features, array $baseline): float
    {
        if (empty($baseline)) {
            return 0.5;
        }

        $weights = [
            'avg_velocity' => 0.15,
            'std_velocity' => 0.1,
            'avg_acceleration' => 0.15,
            'movement_smoothness' => 0.15,
            'curvature' => 0.1,
            'click_frequency' => 0.1,
            'scroll_frequency' => 0.1,
            'path_efficiency' => 0.15,
            'direction_changes' => 0.05,
        ];

        $totalScore = 0.0;
        $totalWeight = 0.0;

        foreach ($weights as $feature => $weight) {
            if (! isset($features[$feature]) || ! isset($baseline[$feature])) {
                continue;
            }

            $featureValue = $features[$feature];
            $baselineValue = $baseline[$feature];

            if ($baselineValue == 0) {
                $similarity = 1.0;
            } else {
                $difference = abs($featureValue - $baselineValue);
                $normalizedDiff = $difference / max(abs($baselineValue), 1);
                $similarity = max(0, 1 - $normalizedDiff);
            }

            $totalScore += $similarity * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $totalScore / $totalWeight : 0.5;
    }

    /**
     * Get baseline for user
     *
     * @param  User  $user  User to get baseline for
     * @return BehavioralBaseline|null Baseline data
     */
    public function getBaseline(User $user): ?BehavioralBaseline
    {
        return BehavioralBaseline::where('user_id', $user->id)
            ->where('baseline_type', 'mouse')
            ->first();
    }

    /**
     * Store sample for baseline building
     *
     * @param  User  $user  User to store sample for
     * @param  array  $mouseData  Raw mouse data
     */
    public function storeSample(User $user, array $mouseData): void
    {
        $features = $this->extractFeatures($mouseData);

        BehavioralSample::create([
            'user_id' => $user->id,
            'session_id' => session()->getId(),
            'sample_type' => 'mouse',
            'sample_data' => $features,
        ]);

        $this->updateBaselineIfNeeded($user);
    }

    /**
     * Update baseline if enough samples collected
     *
     * @param  User  $user  User to update baseline for
     */
    private function updateBaselineIfNeeded(User $user): void
    {
        $sampleCount = BehavioralSample::where('user_id', $user->id)
            ->where('sample_type', 'mouse')
            ->whereNull('analyzed_at')
            ->count();

        if ($sampleCount < self::MIN_FEATURES_FOR_BASELINE) {
            return;
        }

        $samples = BehavioralSample::where('user_id', $user->id)
            ->where('sample_type', 'mouse')
            ->whereNull('analyzed_at')
            ->limit(self::MIN_FEATURES_FOR_BASELINE)
            ->get();

        $baselineData = $this->calculateBaseline($samples);

        BehavioralBaseline::updateOrCreate(
            [
                'user_id' => $user->id,
                'baseline_type' => 'mouse',
            ],
            [
                'baseline_data' => $baselineData,
                'sample_count' => $sampleCount,
            ]
        );

        foreach ($samples as $sample) {
            $sample->update(['analyzed_at' => CarbonImmutable::now()]);
        }

        $this->logger->info('Mouse baseline updated', [
            'user_id' => $user->id,
            'sample_count' => $sampleCount,
        ]);
    }

    /**
     * Calculate baseline from samples
     *
     * @param  Collection  $samples  Samples
     * @return array Baseline data
     */
    private function calculateBaseline($samples): array
    {
        $featuresList = $samples->pluck('sample_data')->toArray();

        if (empty($featuresList)) {
            return $this->getDefaultFeatures();
        }

        $baseline = [];
        $featureKeys = array_keys($featuresList[0]);

        foreach ($featureKeys as $key) {
            $values = array_column($featuresList, $key);
            $baseline[$key] = array_sum($values) / count($values);
        }

        return $baseline;
    }

    /**
     * Calculate standard deviation
     *
     * @param  array  $values  Values
     * @return float Standard deviation
     */
    private function calculateStdDev(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $mean = array_sum($values) / count($values);
        $variance = 0.0;

        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }

        return sqrt($variance / count($values));
    }

    /**
     * Calculate pause ratio (time spent not moving)
     *
     * @param  array  $events  Mouse events
     * @return float Pause ratio (0.0 to 1.0)
     */
    private function calculatePauseRatio(array $events): array
    {
        if (empty($events)) {
            return 0.0;
        }

        $pauseCount = 0;

        for ($i = 1; $i < count($events); $i++) {
            $prev = $events[$i - 1];
            $curr = $events[$i];

            $dt = $curr['timestamp'] - $prev['timestamp'];

            // If time between events > 500ms, consider it a pause
            if ($dt > 500) {
                $pauseCount++;
            }
        }

        return $pauseCount / max(count($events), 1);
    }

    /**
     * Calculate confidence based on sample count
     *
     * @param  int  $sampleCount  Sample count
     * @return float Confidence (0.0 to 1.0)
     */
    private function calculateConfidence(int $sampleCount): float
    {
        return min(1.0, $sampleCount / 50.0);
    }

    /**
     * Get default features
     *
     * @return array Default features
     */
    private function getDefaultFeatures(): array
    {
        return [
            'avg_velocity' => 450.0,
            'std_velocity' => 150.0,
            'avg_acceleration' => 120.0,
            'std_acceleration' => 50.0,
            'movement_smoothness' => 0.85,
            'curvature' => 0.12,
            'click_frequency' => 2.5,
            'scroll_frequency' => 1.2,
            'pause_ratio' => 0.35,
            'direction_changes' => 15,
            'path_efficiency' => 0.78,
            'event_count' => 0,
            'total_distance' => 0,
            'total_time' => 0,
        ];
    }
}
