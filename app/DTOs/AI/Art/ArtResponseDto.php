<?php

declare(strict_types=1);

namespace App\DTOs\AI\Art;

/**
 * Response DTO for Art AI Constructor
 */
final readonly class ArtResponseDto
{
    public function __construct(
        public bool $success,
        public array $recommendations,
        public float $confidence,
        public array $metadata,
        public string $correlationId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            success: $data['success'] ?? false,
            recommendations: $data['recommendations'] ?? [],
            confidence: $data['confidence'] ?? 0.0,
            metadata: $data['metadata'] ?? [],
            correlationId: $data['correlation_id'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'recommendations' => $this->recommendations,
            'confidence' => $this->confidence,
            'metadata' => $this->metadata,
            'correlation_id' => $this->correlationId,
        ];
    }
}