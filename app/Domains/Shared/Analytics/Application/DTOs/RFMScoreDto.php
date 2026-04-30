<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

/**
 * RFM Score Data Transfer Object
 *
 * Represents the result of RFM (Recency, Frequency, Monetary) analysis
 * for a customer segmentation.
 */
final readonly class RFMScoreDto
{
    public function __construct(
        public readonly int $userId,
        public readonly int $recency,    // Days since last order
        public readonly int $frequency,  // Number of orders
        public readonly float $monetary, // Total monetary value
        public readonly string $segment, // Customer segment (e.g., 'Champions', 'Loyal', 'At Risk')
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            recency: $data['recency'],
            frequency: $data['frequency'],
            monetary: $data['monetary'],
            segment: $data['segment'],
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'recency' => $this->recency,
            'frequency' => $this->frequency,
            'monetary' => $this->monetary,
            'segment' => $this->segment,
        ];
    }
}
