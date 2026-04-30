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
use App\Services\Security\AuditService;

/**
 * Typing Pattern Service
 *
 * Analyzes typing dynamics (keystroke timing, pressure, rhythm)
 * for continuous authentication
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class TypingPatternService
{
    use WithAuditLogging;

    private const MIN_FEATURES_FOR_BASELINE = 20;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Analyze typing pattern
     *
     * @param  User  $user  User to analyze
     * @param  array  $typingData  Typing data from frontend
     * @return array Analysis result
     */
    public function analyze(User $user, array $typingData): array
    {
        $features = $this->extractFeatures($typingData);
        $baseline = $this->getBaseline($user);

        if (! $baseline || ! $baseline->hasEnoughSamples(self::MIN_FEATURES_FOR_BASELINE)) {
            // No baseline yet, store sample for enrollment
            $this->storeSample($user, $typingData);

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
     * Extract features from typing data
     *
     * @param  array  $typingData  Raw typing data
     * @return array Extracted features
     */
    public function extractFeatures(array $typingData): array
    {
        $events = $typingData['events'] ?? [];

        if (empty($events)) {
            return $this->getDefaultFeatures();
        }

        $keyDownTimes = [];
        $keyUpTimes = [];
        $flightTimes = [];
        $holdTimes = [];
        $digraphs = [];
        $errors = 0;

        $lastKeyDown = null;
        $lastKeyUp = null;
        $lastKey = null;

        foreach ($events as $event) {
            $key = $event['key'] ?? null;
            $timestamp = $event['timestamp'] ?? 0;
            $keyDown = $event['keyDown'] ?? false;
            $keyUp = $event['keyUp'] ?? false;

            if ($keyDown && $key) {
                $keyDownTimes[$key] = $timestamp;

                if ($lastKey && $lastKeyUp) {
                    $flightTime = $timestamp - $lastKeyUp;
                    $digraph = strtolower($lastKey.$key);
                    $flightTimes[] = $flightTime;

                    if (! isset($digraphs[$digraph])) {
                        $digraphs[$digraph] = [];
                    }
                    $digraphs[$digraph][] = $flightTime;
                }

                $lastKey = $key;
            }

            if ($keyUp && $key && isset($keyDownTimes[$key])) {
                $holdTime = $timestamp - $keyDownTimes[$key];
                $holdTimes[] = $holdTime;
                $keyUpTimes[$key] = $timestamp;
                $lastKeyUp = $timestamp;
                unset($keyDownTimes[$key]);
            }

            // Detect backspace as error
            if ($key === 'Backspace' && $keyDown) {
                $errors++;
            }
        }

        return [
            'avg_key_hold_time' => ! empty($holdTimes) ? array_sum($holdTimes) / count($holdTimes) : 0,
            'std_key_hold_time' => ! empty($holdTimes) ? $this->calculateStdDev($holdTimes) : 0,
            'avg_flight_time' => ! empty($flightTimes) ? array_sum($flightTimes) / count($flightTimes) : 0,
            'std_flight_time' => ! empty($flightTimes) ? $this->calculateStdDev($flightTimes) : 0,
            'typing_speed' => $this->calculateTypingSpeed($events),
            'error_rate' => ! empty($events) ? $errors / count($events) : 0,
            'rhythm_variance' => ! empty($flightTimes) ? $this->calculateStdDev($flightTimes) / (array_sum($flightTimes) / count($flightTimes)) : 0,
            'digraph_patterns' => $this->averageDigraphs($digraphs),
            'event_count' => count($events),
        ];
    }

    /**
     * Compare typing pattern to baseline
     *
     * @param  array  $features  Current features
     * @param  array  $baseline  Baseline features
     * @return float Similarity score (0.0 to 1.0)
     */
    public function compare(array $features, array $baseline): float
    {
        if (empty($baseline)) {
            return 0.5; // Neutral if no baseline
        }

        $weights = [
            'avg_key_hold_time' => 0.2,
            'std_key_hold_time' => 0.15,
            'avg_flight_time' => 0.2,
            'std_flight_time' => 0.15,
            'typing_speed' => 0.1,
            'error_rate' => 0.1,
            'rhythm_variance' => 0.1,
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
            ->where('baseline_type', 'typing')
            ->first();
    }

    /**
     * Store sample for baseline building
     *
     * @param  User  $user  User to store sample for
     * @param  array  $typingData  Raw typing data
     */
    public function storeSample(User $user, array $typingData): void
    {
        $features = $this->extractFeatures($typingData);

        BehavioralSample::create([
            'user_id' => $user->id,
            'session_id' => session()->getId(),
            'sample_type' => 'typing',
            'sample_data' => $features,
        ]);

        // Update baseline if we have enough samples
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
            ->where('sample_type', 'typing')
            ->whereNull('analyzed_at')
            ->count();

        if ($sampleCount < self::MIN_FEATURES_FOR_BASELINE) {
            return;
        }

        $samples = BehavioralSample::where('user_id', $user->id)
            ->where('sample_type', 'typing')
            ->whereNull('analyzed_at')
            ->limit(self::MIN_FEATURES_FOR_BASELINE)
            ->get();

        $baselineData = $this->calculateBaseline($samples);

        BehavioralBaseline::updateOrCreate(
            [
                'user_id' => $user->id,
                'baseline_type' => 'typing',
            ],
            [
                'baseline_data' => $baselineData,
                'sample_count' => $sampleCount,
            ]
        );

        // Mark samples as analyzed
        foreach ($samples as $sample) {
            $sample->update(['analyzed_at' => CarbonImmutable::now()]);
        }

        $this->logger->info('Typing baseline updated', [
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

            if (is_array($values[0])) {
                // Handle digraph patterns (nested arrays)
                $baseline[$key] = $this->averageNestedArrays($values);
            } else {
                $baseline[$key] = array_sum($values) / count($values);
            }
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
     * Calculate typing speed (words per minute)
     *
     * @param  array  $events  Typing events
     * @return float Typing speed
     */
    private function calculateTypingSpeed(array $events): float
    {
        if (empty($events)) {
            return 0.0;
        }

        $firstEvent = $events[0];
        $lastEvent = $events[count($events) - 1];

        $duration = ($lastEvent['timestamp'] ?? 0) - ($firstEvent['timestamp'] ?? 0);

        if ($duration <= 0) {
            return 0.0;
        }

        $charCount = count(array_filter($events, fn ($e) => isset($e['keyDown']) && $e['keyDown']));
        $wordCount = max(1, $charCount / 5); // Average 5 chars per word

        return ($wordCount / $duration) * 60; // WPM
    }

    /**
     * Average digraph patterns
     *
     * @param  array  $digraphs  Digraph data
     * @return array Averaged digraphs
     */
    private function averageDigraphs(array $digraphs): array
    {
        $averaged = [];

        foreach ($digraphs as $digraph => $times) {
            $averaged[$digraph] = array_sum($times) / count($times);
        }

        return $averaged;
    }

    /**
     * Average nested arrays
     *
     * @param  array  $arrays  Nested arrays
     * @return array Averaged arrays
     */
    private function averageNestedArrays(array $arrays): array
    {
        $result = [];

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (! isset($result[$key])) {
                    $result[$key] = [];
                }
                $result[$key][] = $value;
            }
        }

        foreach ($result as $key => $values) {
            $result[$key] = array_sum($values) / count($values);
        }

        return $result;
    }

    /**
     * Calculate confidence based on sample count
     *
     * @param  int  $sampleCount  Sample count
     * @return float Confidence (0.0 to 1.0)
     */
    private function calculateConfidence(int $sampleCount): float
    {
        return min(1.0, $sampleCount / 50.0); // Max confidence at 50 samples
    }

    /**
     * Get default features
     *
     * @return array Default features
     */
    private function getDefaultFeatures(): array
    {
        return [
            'avg_key_hold_time' => 85.5,
            'std_key_hold_time' => 25.0,
            'avg_flight_time' => 150.0,
            'std_flight_time' => 50.0,
            'typing_speed' => 45.0,
            'error_rate' => 0.02,
            'rhythm_variance' => 0.15,
            'digraph_patterns' => [],
            'event_count' => 0,
        ];
    }
}
