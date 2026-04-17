<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\DTOs;

final readonly class AIDiagnosticResultDto
{
    public function __construct(
        public string $primaryDiagnosis,
        public array $differentialDiagnoses,
        public array $recommendedSpecialties,
        public string $urgencyLevel,
        public array $recommendedTests,
        public string $triageCategory,
        public int $healthScore,
        public array $riskFactors,
        public array $preventiveMeasures,
        public float $confidence,
        public array $embedding,
        public bool $requiresEmergency,
        public string $correlationId,
    ) {}

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        
        return new self(
            primaryDiagnosis: strval($data['primary_diagnosis'] ?? ''),
            differentialDiagnoses: array_map('strval', $data['differential_diagnoses'] ?? []),
            recommendedSpecialties: array_map('strval', $data['recommended_specialties'] ?? []),
            urgencyLevel: strval($data['urgency_level'] ?? 'routine'),
            recommendedTests: array_map('strval', $data['recommended_tests'] ?? []),
            triageCategory: strval($data['triage_category'] ?? 'green'),
            healthScore: intval($data['health_score'] ?? 70),
            riskFactors: array_map('strval', $data['risk_factors'] ?? []),
            preventiveMeasures: array_map('strval', $data['preventive_measures'] ?? []),
            confidence: floatval($data['confidence'] ?? 0.5),
            embedding: array_map('floatval', $data['embedding'] ?? []),
            requiresEmergency: boolval($data['requires_emergency'] ?? false),
            correlationId: strval($data['correlation_id'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'primary_diagnosis' => $this->primaryDiagnosis,
            'differential_diagnoses' => $this->differentialDiagnoses,
            'recommended_specialties' => $this->recommendedSpecialties,
            'urgency_level' => $this->urgencyLevel,
            'recommended_tests' => $this->recommendedTests,
            'triage_category' => $this->triageCategory,
            'health_score' => $this->healthScore,
            'risk_factors' => $this->riskFactors,
            'preventive_measures' => $this->preventiveMeasures,
            'confidence' => $this->confidence,
            'embedding' => $this->embedding,
            'requires_emergency' => $this->requiresEmergency,
            'correlation_id' => $this->correlationId,
        ];
    }
}
