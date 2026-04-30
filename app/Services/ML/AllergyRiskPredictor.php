<?php

declare(strict_types=1);

namespace App\Services\ML;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;

final readonly class AllergyRiskPredictor
{
    private const MODEL_ENDPOINT = 'http://localhost:8000/predict/allergy-risk';
    private const FALLBACK_RULE_BASED = true;

    public function __construct(
        private readonly HttpFactory $http,
        private readonly LogManager $log,
    ) {}

    public function predict(array $features): array
    {
        // Try ML model first
        try {
            $response = Http::timeout(2)->post(self::MODEL_ENDPOINT, [
                'features' => $features,
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            $this->log->warning('ML model unavailable, using fallback', ['error' => $e->getMessage()]);
        }

        // Fallback to rule-based prediction
        return $this->ruleBasedPrediction($features);
    }

    private function ruleBasedPrediction(array $features): array
    {
        $probability = 0.0;

        // Existing allergies increase risk
        if (!empty($features['existing_allergies'])) {
            $probability += 0.3 * count($features['existing_allergies']);
        }

        // Similar ingredients increase risk
        if (!empty($features['ingredient_similarity'])) {
            $probability += $features['ingredient_similarity'] * 0.4;
        }

        // Age factor (younger = higher risk for first-time reactions)
        if (isset($features['age'])) {
            if ($features['age'] < 2) {
                $probability += 0.2;
            } elseif ($features['age'] < 5) {
                $probability += 0.1;
            }
        }

        // Breed-specific risks (for pets)
        if (!empty($features['breed_risk_factor'])) {
            $probability += $features['breed_risk_factor'] * 0.3;
        }

        // History of severe reactions
        if (!empty($features['severe_reaction_history'])) {
            $probability += 0.4;
        }

        // Cap at 1.0
        $probability = min($probability, 1.0);

        return [
            'probability' => $probability,
            'confidence' => 0.7, // Lower confidence for rule-based
            'method' => 'rule_based',
        ];
    }

    public function trainModel(array $trainingData): void
    {
        // This would trigger model retraining
        // Implemented as a background job in production
        dispatch(new \App\Jobs\TrainAllergyModel($trainingData));
    }
}
