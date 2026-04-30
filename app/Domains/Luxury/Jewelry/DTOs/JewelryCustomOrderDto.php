<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\DTOs;

final readonly class JewelryCustomOrderDto
{
    public function __construct(
        public int $storeId,
        public int $userId,
        public string $customerName,
        public string $customerPhone,
        public int $estimatedPrice,
        public array $aiSpecification,
        public ?string $userNotes = null,
        public ?string $referencePhotoPath = null,
        public ?string $correlationId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'store_id' => $this->storeId,
            'user_id' => $this->userId,
            'customer_name' => $this->customerName,
            'customer_phone' => $this->customerPhone,
            'estimated_price' => $this->estimatedPrice,
            'ai_specification' => $this->aiSpecification,
            'user_notes' => $this->userNotes,
            'reference_photo_path' => $this->referencePhotoPath,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            storeId: $data['store_id'],
            userId: $data['user_id'],
            customerName: $data['customer_name'],
            customerPhone: $data['customer_phone'],
            estimatedPrice: $data['estimated_price'],
            aiSpecification: $data['ai_specification'],
            userNotes: $data['user_notes'] ?? null,
            referencePhotoPath: $data['reference_photo_path'] ?? null,
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
