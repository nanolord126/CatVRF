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
 * Touch Gesture Service
 *
 * Analyzes touch gestures on mobile devices (swipe, pinch, tap patterns)
 * for continuous authentication
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class TouchGestureService
{
    use WithAuditLogging;

    private const MIN_FEATURES_FOR_BASELINE = 15;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly AuditService $audit,
    ) {}

    /**
     * Analyze touch gestures
     *
     * @param  User  $user  User to analyze
     * @param  array  $touchData  Touch data from frontend
     * @return array Analysis result
     */
    public function analyze(User $user, array $touchData): array
    {
        $features = $this->extractFeatures($touchData);
        $baseline = $this->getBaseline($user);

        if (! $baseline || ! $baseline->hasEnoughSamples(self::MIN_FEATURES_FOR_BASELINE)) {
            $this->storeSample($user, $touchData);

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
     * Extract features from touch data
     *
     * @param  array  $touchData  Raw touch data
     * @return array Extracted features
     */
    public function extractFeatures(array $touchData): array
    {
        $events = $touchData['events'] ?? [];
        $gestures = $touchData['gestures'] ?? [];

        if (empty($events) && empty($gestures)) {
            return $this->getDefaultFeatures();
        }

        // Analyze touch events
        $touchVelocities = [];
        $touchAccelerations = [];
        $pressDurations = [];

        for ($i = 1; $i < count($events); $i++) {
            $prev = $events[$i - 1];
            $curr = $events[$i];

            $dx = $curr['x'] - $prev['x'];
            $dy = $curr['y'] - $prev['y'];
            $dt = ($curr['timestamp'] - $prev['timestamp']) / 1000;

            if ($dt > 0) {
                $velocity = sqrt($dx * $dx + $dy * $dy) / $dt;
                $touchVelocities[] = $velocity;

                if ($i > 1) {
                    $prevVelocity = $touchVelocities[count($touchVelocities) - 2];
                    $acceleration = abs($velocity - $prevVelocity) / $dt;
                    $touchAccelerations[] = $acceleration;
                }
            }

            // Calculate press duration
            if ($prev['action'] === 'touchstart' && $curr['action'] === 'touchend' && $prev['identifier'] === $curr['identifier']) {
                $pressDuration = $curr['timestamp'] - $prev['timestamp'];
                $pressDurations[] = $pressDuration;
            }
        }

        // Analyze gestures
        $swipeVelocities = [];
        $pinchScales = [];

        foreach ($gestures as $gesture) {
            $type = $gesture['type'] ?? '';

            if ($type === 'swipe') {
                $swipeVelocities[] = $gesture['velocity'] ?? 0;
            } elseif ($type === 'pinch') {
                $pinchScales[] = $gesture['scale'] ?? 1.0;
            }
        }

        return [
            'avg_touch_velocity' => ! empty($touchVelocities) ? array_sum($touchVelocities) / count($touchVelocities) : 0,
            'std_touch_velocity' => ! empty($touchVelocities) ? $this->calculateStdDev($touchVelocities) : 0,
            'avg_touch_acceleration' => ! empty($touchAccelerations) ? array_sum($touchAccelerations) / count($touchAccelerations) : 0,
            'avg_press_duration' => ! empty($pressDurations) ? array_sum($pressDurations) / count($pressDurations) : 0,
            'std_press_duration' => ! empty($pressDurations) ? $this->calculateStdDev($pressDurations) : 0,
            'avg_swipe_velocity' => ! empty($swipeVelocities) ? array_sum($swipeVelocities) / count($swipeVelocities) : 0,
            'std_swipe_velocity' => ! empty($swipeVelocities) ? $this->calculateStdDev($swipeVelocities) : 0,
            'avg_pinch_scale' => ! empty($pinchScales) ? array_sum($pinchScales) / count($pinchScales) : 1.0,
            'gesture_count' => count($gestures),
            'touch_event_count' => count($events),
        ];
    }

    /**
     * Compare touch pattern to baseline
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
            'avg_touch_velocity' => 0.2,
            'std_touch_velocity' => 0.1,
            'avg_touch_acceleration' => 0.15,
            'avg_press_duration' => 0.2,
            'std_press_duration' => 0.1,
            'avg_swipe_velocity' => 0.15,
            'avg_pinch_scale' => 0.1,
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
            ->where('baseline_type', 'touch')
            ->first();
    }

    /**
     * Store sample for baseline building
     *
     * @param  User  $user  User to store sample for
     * @param  array  $touchData  Raw touch data
     */
    public function storeSample(User $user, array $touchData): void
    {
        $features = $this->extractFeatures($touchData);

        BehavioralSample::create([
            'user_id' => $user->id,
            'session_id' => session()->getId(),
            'sample_type' => 'touch',
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
            ->where('sample_type', 'touch')
            ->whereNull('analyzed_at')
            ->count();

        if ($sampleCount < self::MIN_FEATURES_FOR_BASELINE) {
            return;
        }

        $samples = BehavioralSample::where('user_id', $user->id)
            ->where('sample_type', 'touch')
            ->whereNull('analyzed_at')
            ->limit(self::MIN_FEATURES_FOR_BASELINE)
            ->get();

        $baselineData = $this->calculateBaseline($samples);

        BehavioralBaseline::updateOrCreate(
            [
                'user_id' => $user->id,
                'baseline_type' => 'touch',
            ],
            [
                'baseline_data' => $baselineData,
                'sample_count' => $sampleCount,
            ]
        );

        foreach ($samples as $sample) {
            $sample->update(['analyzed_at' => CarbonImmutable::now()]);
        }

        $this->logger->info('Touch baseline updated', [
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
     * Calculate confidence based on sample count
     *
     * @param  int  $sampleCount  Sample count
     * @return float Confidence (0.0 to 1.0)
     */
    private function calculateConfidence(int $sampleCount): float
    {
        return min(1.0, $sampleCount / 40.0);
    }

    /**
     * Get default features
     *
     * @return array Default features
     */
    private function getDefaultFeatures(): array
    {
        return [
            'avg_touch_velocity' => 300.0,
            'std_touch_velocity' => 100.0,
            'avg_touch_acceleration' => 100.0,
            'avg_press_duration' => 150.0,
            'std_press_duration' => 30.0,
            'avg_swipe_velocity' => 2.5,
            'std_swipe_velocity' => 1.0,
            'avg_pinch_scale' => 0.8,
            'gesture_count' => 0,
            'touch_event_count' => 0,
        ];
    }
}
