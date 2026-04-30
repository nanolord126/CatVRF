<?php

declare(strict_types=1);

namespace Modules\RealEstate\Application\DTOs;

final readonly class PropertyBookingDto
{
    public function __construct(
        public ?int $id,
        public int $propertyId,
        public int $userId,
        public string $checkIn,
        public string $checkOut,
        public string $status,
        public float $totalPrice,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            propertyId: (int) ($data['property_id'] ?? 0),
            userId: (int) ($data['user_id'] ?? 0),
            checkIn: (string) ($data['check_in'] ?? now()->toIso8601String()),
            checkOut: (string) ($data['check_out'] ?? now()->addDays(1)->toIso8601String()),
            status: (string) ($data['status'] ?? 'pending'),
            totalPrice: (float) ($data['total_price'] ?? 0.0),
            metadata: (array) ($data['metadata'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->propertyId,
            'user_id' => $this->userId,
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
            'status' => $this->status,
            'total_price' => $this->totalPrice,
            'metadata' => $this->metadata,
        ];
    }
}
