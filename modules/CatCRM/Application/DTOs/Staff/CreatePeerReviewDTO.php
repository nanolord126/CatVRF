<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\DTOs\Staff;

/**
 * CreatePeerReviewDTO — DTO для создания peer review
 * 
 * Immutable DTO following CatVRF rules
 */
final readonly class CreatePeerReviewDTO
{
    public function __construct(
        public int $tenantId,
        public int $reviewerId,
        public int $revieweeId,
        public int $rating,
        public string $feedback,
        public ?string $strengths = null,
        public ?string $areasForImprovement = null,
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            reviewerId: $data['reviewer_id'],
            revieweeId: $data['reviewee_id'],
            rating: $data['rating'],
            feedback: $data['feedback'],
            strengths: $data['strengths'] ?? null,
            areasForImprovement: $data['areas_for_improvement'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'reviewer_id' => $this->reviewerId,
            'reviewee_id' => $this->revieweeId,
            'rating' => $this->rating,
            'feedback' => $this->feedback,
            'strengths' => $this->strengths,
            'areas_for_improvement' => $this->areasForImprovement,
            'metadata' => $this->metadata,
        ];
    }
}
