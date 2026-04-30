<?php

declare(strict_types=1);

namespace App\Services\Behavioral;

use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;

/**
 * Multi-Modal Fusion Service
 *
 * Combines multiple biometric signals (typing, mouse, touch, session)
 * for robust continuous authentication
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class MultiModalFusionService
{
    use WithAuditLogging;

    private const DEFAULT_WEIGHTS = [
        'typing' => 0.3,
        'mouse' => 0.3,
        'touch' => 0.2,
        'session' => 0.2,
    ];

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * Fuse multiple biometric scores
     *
     * @param  array  $scores  Individual component scores
     * @param  array  $weights  Weights for each component
     * @return array Fused result
     */
    public function fuse(array $scores, array $weights = []): array
    {
        $weights = $this->normalizeWeights($weights);
        $availableScores = array_filter($scores, fn ($s) => isset($s['score']) && $s['score'] !== null);

        if (empty($availableScores)) {
            return [
                'fused_score' => 0.5,
                'confidence' => 0.0,
                'available_modalities' => [],
            ];
        }

        $weightedSum = 0.0;
        $weightSum = 0.0;
        $confidenceSum = 0.0;
        $availableModalities = [];

        foreach ($availableScores as $modality => $data) {
            $weight = $weights[$modality] ?? 0.25;
            $weightedSum += $data['score'] * $weight;
            $weightSum += $weight;
            $confidenceSum += ($data['confidence'] ?? 0.5) * $weight;
            $availableModalities[] = $modality;
        }

        $fusedScore = $weightSum > 0 ? $weightedSum / $weightSum : 0.5;
        $confidence = $weightSum > 0 ? $confidenceSum / $weightSum : 0.0;

        return [
            'fused_score' => $fusedScore,
            'confidence' => $confidence,
            'available_modalities' => $availableModalities,
        ];
    }

    /**
     * Calculate optimal weights based on availability and confidence
     *
     * @param  array  $scores  Individual component scores
     * @return array Calculated weights
     */
    public function calculateWeights(array $scores): array
    {
        $availableScores = array_filter($scores, fn ($s) => isset($s['score']) && $s['score'] !== null);

        if (empty($availableScores)) {
            return self::DEFAULT_WEIGHTS;
        }

        // Start with default weights
        $weights = self::DEFAULT_WEIGHTS;

        // Adjust weights based on confidence
        foreach ($availableScores as $modality => $data) {
            $confidence = $data['confidence'] ?? 0.5;

            // Increase weight for high confidence modalities
            if ($confidence > 0.8) {
                $weights[$modality] = min(0.5, $weights[$modality] * 1.5);
            }
            // Decrease weight for low confidence modalities
            elseif ($confidence < 0.5) {
                $weights[$modality] = max(0.1, $weights[$modality] * 0.5);
            }
        }

        // Normalize weights to sum to 1
        return $this->normalizeWeights($weights);
    }

    /**
     * Handle missing modalities (graceful degradation)
     *
     * @param  array  $scores  Individual component scores
     * @param  array  $availableModalities  Available modalities
     * @return array Adjusted scores
     */
    public function handleMissingModalities(
        array $scores,
        array $availableModalities
    ): array {
        $adjusted = [];

        foreach ($scores as $modality => $data) {
            if (in_array($modality, $availableModalities, true)) {
                $adjusted[$modality] = $data;
            } else {
                // Mark as unavailable
                $adjusted[$modality] = [
                    'score' => null,
                    'confidence' => 0.0,
                    'baseline_available' => false,
                    'message' => 'Modality not available',
                ];
            }
        }

        return $adjusted;
    }

    /**
     * Normalize weights to sum to 1
     *
     * @param  array  $weights  Raw weights
     * @return array Normalized weights
     */
    private function normalizeWeights(array $weights): array
    {
        $sum = array_sum($weights);

        if ($sum === 0) {
            return self::DEFAULT_WEIGHTS;
        }

        $normalized = [];
        foreach ($weights as $key => $value) {
            $normalized[$key] = $value / $sum;
        }

        return $normalized;
    }
}
