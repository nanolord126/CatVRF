<?php declare(strict_types=1);

namespace App\Services\Insurance;

use App\Models\Insurance\InsuranceType;
use Exception;
use Illuminate\Support\Collection;



use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

final readonly class AIRiskAssessmentService
{
    public function __construct(
        private readonly LogManager $logger,
    ) {}


    /**
         * Build risk profile for a user and recommend insurance types.
         */
        public function generateRecommendations(
            int $userId,
            array $userData,
            string $correlationId = null
        ): Collection {
            $correlationId = $correlationId ?? (string) Str::uuid();

            // 1. Log Audit (Start process)
            $this->logger->channel('audit')->info('[AIRiskAssessmentService] Generating user risk recommendations', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'user_age' => $userData['age'] ?? 'unknown',
            ]);

            try {
                // 2. Mock AI Logic (Logic based on user inputs)
                $availableTypes = InsuranceType::where('is_active', true)->get();
                $recommendations = collect();

                foreach ($availableTypes as $type) {
                    $score = 0.0;
                    $reason = 'Generic recommendation';

                    // Scenario 1: Vehicle Insurance (Age & Location focus)
                    if (in_array($type->slug, ['osago', 'kasko'])) {
                        if (isset($userData['owns_car']) && $userData['owns_car'] === true) {
                            $score += 0.8;
                            $reason = 'Critical for vehicle owners in ' . ($userData['region'] ?? 'your region');
                        }
                        if (isset($userData['age']) && ($userData['age'] < 25 || $userData['age'] > 65)) {
                            $score += 0.15; // High-risk categories need coverage
                            $reason .= ' (High-risk driver group recommendation)';
                        }
                    }

                    // Scenario 2: Health Insurance (Critical if age > 40)
                    if ($type->slug === 'health') {
                        if (isset($userData['age']) && $userData['age'] > 40) {
                            $score += 0.9;
                            $reason = 'Priority health monitoring recommended for your age group.';
                        }
                        if (isset($userData['has_children']) && $userData['has_children'] === true) {
                            $score += 0.2;
                            $reason .= ' (Includes family protection options)';
                        }
                    }

                    // Scenario 3: Travel (If frequent traveler)
                    if ($type->slug === 'travel' && !empty($userData['travel_frequency'])) {
                        $score += 0.7;
                        $reason = 'Optimal for frequent travelers with multi-trip coverage.';
                    }

                    // 3. Score Thresholding (Limit recommendations to score > 0.5)
                    if ($score >= 0.5) {
                        $recommendations->push([
                            'uuid' => (string) Str::uuid(),
                            'type_slug' => $type->slug,
                            'type_name' => $type->name,
                            'confidence_score' => min(1.0, (float) $score),
                            'personal_reason' => $reason,
                            'estimated_premium_start' => $type->base_premium,
                        ]);
                    }
                }

                // 4. Record recommendation log (Audit)
                $this->logger->channel('audit')->info('[AIRiskAssessmentService] Recommendations generated', [
                    'correlation_id' => $correlationId,
                    'count' => $recommendations->count(),
                ]);

                return $recommendations->sortByDesc('confidence_score')->values();

            } catch (Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('[AIRiskAssessmentService] Recommendation failure', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        /**
         * Advanced Risk Assessment: Projecting loss probability.
         */
        public function assessLossProbability(int $policyId, string $correlationId = null): float
        {
            $correlationId = $correlationId ?? (string) Str::uuid();

            $this->logger->channel('audit')->info('[AIRiskAssessmentService] Assessing loss probability for policy', [
                'correlation_id' => $correlationId,
                'policy_id' => $policyId,
            ]);

            // Logic based on recent claims in the tenant/region
            // (Mock: probabilistic analysis)
            return 0.12; // 12% probability
        }
}
