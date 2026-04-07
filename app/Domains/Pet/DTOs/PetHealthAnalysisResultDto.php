<?php declare(strict_types=1);

namespace App\Domains\Pet\DTO;

/**
 * Class PetHealthAnalysisResultDto
 *
 * Part of the Pet vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Data Transfer Object (immutable).
 * Used for type-safe data passing between layers.
 *
 * All DTOs in CatVRF are final readonly classes.
 * Properties are set via constructor and cannot be modified.
 *
 * @see https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly
 * @package App\Domains\Pet\DTO
 */
final readonly class PetHealthAnalysisResultDto
{

    public function __construct(
            public string $pet_uuid,
            public string $analysis_type,
            public float $confidence_score,
            public array $detected_symptoms,
            public array $recommendations,
            public array $suggested_products,
            public array $suggested_services,
            public string $correlation_id,
            public string $analyzed_at) {}

        public static function fromAiResponse(array $aiData, string $petUuid, string $correlationId): self
        {
            return new self(
                pet_uuid: $petUuid,
                analysis_type: (string)($aiData['type'] ?? 'general_health'),
                confidence_score: (float)($aiData['score'] ?? 0.0),
                detected_symptoms: (array)($aiData['symptoms'] ?? []),
                recommendations: (array)($aiData['recommendations'] ?? []),
                suggested_products: (array)($aiData['products'] ?? []),
                suggested_services: (array)($aiData['services'] ?? []),
                correlation_id: $correlationId,
                analyzed_at: now()->toIso8601String(),
            );
        }

        public function toArray(): array
        {
            return [
                'pet_uuid' => $this->pet_uuid,
                'analysis_type' => $this->analysis_type,
                'confidence_score' => $this->confidence_score,
                'detected_symptoms' => $this->detected_symptoms,
                'recommendations' => $this->recommendations,
                'suggested_products' => $this->suggested_products,
                'suggested_services' => $this->suggested_services,
                'correlation_id' => $this->correlation_id,
                'analyzed_at' => $this->analyzed_at,
            ];
        }
}
