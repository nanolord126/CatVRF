<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\DTOs;

final readonly class LoyaltyTransactionDTO
{
    public function __construct(
        public int $loyaltyProfileId,
        public ?int $appointmentId,
        public string $type,
        public int $points,
        public string $description,
        public ?array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            loyaltyProfileId: $data['loyalty_profile_id'],
            appointmentId: $data['appointment_id'] ?? null,
            type: $data['type'],
            points: (int) $data['points'],
            description: $data['description'],
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'loyalty_profile_id' => $this->loyaltyProfileId,
            'appointment_id' => $this->appointmentId,
            'type' => $this->type,
            'points' => $this->points,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }
}
