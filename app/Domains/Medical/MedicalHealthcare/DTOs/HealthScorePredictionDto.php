<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\DTOs;

final readonly class HealthScorePredictionDto
{
    public function __construct(
        public int $currentScore,
        public int $predictedScore30Days,
        public int $predictedScore90Days,
        public string $trend,
        public array $keyFactors,
        public array $recommendations,
        public array $riskAreas,
        public float $confidence,
        public string $correlationId,
    ) {}

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        
        return new self(
            currentScore: intval($data['current_score'] ?? 70),
            predictedScore30Days: intval($data['predicted_30_days'] ?? 70),
            predictedScore90Days: intval($data['predicted_90_days'] ?? 70),
            trend: strval($data['trend'] ?? 'stable'),
            keyFactors: array_map('strval', $data['key_factors'] ?? []),
            recommendations: array_map('strval', $data['recommendations'] ?? []),
            riskAreas: array_map('strval', $data['risk_areas'] ?? []),
            confidence: floatval($data['confidence'] ?? 0.7),
            correlationId: strval($data['correlation_id'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'current_score' => $this->currentScore,
            'predicted_30_days' => $this->predictedScore30Days,
            'predicted_90_days' => $this->predictedScore90Days,
            'trend' => $this->trend,
            'key_factors' => $this->keyFactors,
            'recommendations' => $this->recommendations,
            'risk_areas' => $this->riskAreas,
            'confidence' => $this->confidence,
            'correlation_id' => $this->correlationId,
        ];
    }
}
