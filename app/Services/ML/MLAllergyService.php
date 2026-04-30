<?php

declare(strict_types=1);

namespace App\Services\ML;

use Illuminate\Support\Collection;
use Modules\Contraindications\Application\Services\ContraindicationService;
use Modules\Contraindications\Domain\DTOs\CompatibilityResult;
use Modules\Contraindications\Domain\ValueObjects\Scope;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class MLAllergyService
{
    use WithAuditLogging;

    public function __construct(
        private readonly AllergyRiskPredictor $riskPredictor,
        private readonly CrossAllergyDetector $crossAllergyDetector,
        private readonly SafeAlternativeRecommender $alternativeRecommender,
        private readonly ContraindicationService $contraindicationService,
        private readonly AllergyFeatureExtractor $featureExtractor,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Predict allergy risk for a service/product with user/pet
     */
    public function predictRisk(
        object $entity,
        object $subject, // User or Pet
        Scope $scope
    ): RiskPrediction {
        $features = $this->featureExtractor->extract($entity, $subject, $scope);

        // Get ML prediction
        $prediction = $this->riskPredictor->predict($features);

        // Detect cross-allergies
        $crossRisks = $this->crossAllergyDetector->detect($features);

        // Get safe alternatives if high risk
        $alternatives = [];
        if ($prediction['probability'] > 0.5) {
            $alternatives = $this->alternativeRecommender->recommend($entity, $subject, $scope);
        }

        return new RiskPrediction(
            probability: $prediction['probability'],
            riskLevel: $this->mapRiskLevel($prediction['probability']),
            confidence: $prediction['confidence'] ?? 0.85,
            crossAllergies: $crossRisks,
            safeAlternatives: $alternatives,
            features: $features,
        );
    }

    /**
     * Check if booking should be blocked due to critical risk
     */
    public function shouldBlock(
        object $entity,
        object $subject,
        Scope $scope
    ): bool {
        // Always check explicit contraindications first
        $compatibility = $this->contraindicationService->checkCompatibilityForUser(
            get_class($entity),
            $entity->id,
            $subject->id,
            $scope
        );

        if (!$compatibility->isCompatible && $compatibility->getSeverity() === 'critical') {
            return true;
        }

        // Check ML prediction for critical risk
        $risk = $this->predictRisk($entity, $subject, $scope);

        return $risk->riskLevel === RiskLevel::Critical;
    }

    /**
     * Get warnings for display in UI
     */
    public function getWarnings(
        object $entity,
        object $subject,
        Scope $scope
    ): Collection {
        $risk = $this->predictRisk($entity, $subject, $scope);
        $warnings = collect();

        if ($risk->probability > 0.3) {
            $warnings->push([
                'type' => 'risk',
                'level' => $risk->riskLevel->value,
                'message' => $this->getRiskMessage($risk),
                'probability' => $risk->probability,
            ]);
        }

        foreach ($risk->crossAllergies as $crossAllergy) {
            $warnings->push([
                'type' => 'cross_allergy',
                'level' => 'warning',
                'message' => "Possible cross-allergy detected: {$crossAllergy}",
            ]);
        }

        if (!empty($risk->safeAlternatives)) {
            $warnings->push([
                'type' => 'alternative',
                'level' => 'info',
                'message' => 'Safe alternatives available',
                'alternatives' => $risk->safeAlternatives,
            ]);
        }

        return $warnings;
    }

    /**
     * Get detailed risk explanation for audit/medical review
     */
    public function explainRisk(
        object $entity,
        object $subject,
        Scope $scope
    ): array {
        $risk = $this->predictRisk($entity, $subject, $scope);

        return [
            'prediction' => [
                'probability' => $risk->probability,
                'risk_level' => $risk->riskLevel->value,
                'confidence' => $risk->confidence,
            ],
            'features' => $risk->features,
            'cross_allergies' => $risk->crossAllergies,
            'alternatives' => $risk->safeAlternatives,
            'recommendation' => $risk->riskLevel === RiskLevel::Critical 
                ? 'BLOCK - Critical allergy risk detected'
                : ($risk->riskLevel === RiskLevel::High 
                    ? 'WARN - High risk, recommend alternatives'
                    : 'ALLOW - Low risk'),
            'timestamp' => now()->toISOString(),
        ];
    }

    private function mapRiskLevel(float $probability): RiskLevel
    {
        return match (true) {
            $probability >= 0.8 => RiskLevel::Critical,
            $probability >= 0.6 => RiskLevel::High,
            $probability >= 0.3 => RiskLevel::Medium,
            default => RiskLevel::Low,
        };
    }

    private function getRiskMessage(RiskPrediction $risk): string
    {
        return match ($risk->riskLevel) {
            RiskLevel::Critical => "Critical allergy risk detected ({$risk->probability * 100}% probability). This service is not recommended.",
            RiskLevel::High => "High allergy risk detected ({$risk->probability * 100}% probability). Please consult with a specialist.",
            RiskLevel::Medium => "Moderate allergy risk detected ({$risk->probability * 100}% probability). Monitor for reactions.",
            RiskLevel::Low => "Low allergy risk detected ({$risk->probability * 100}% probability).",
        };
    }

    /**
     * Batch predict for multiple entities
     */
    public function batchPredict(
        array $entities,
        object $subject,
        Scope $scope
    ): array {
        $results = [];

        foreach ($entities as $entity) {
            $results[get_class($entity) . ':' . $entity->id] = $this->predictRisk($entity, $subject, $scope);
        }

        return $results;
    }
}
