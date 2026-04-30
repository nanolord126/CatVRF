<?php

declare(strict_types=1);

namespace Modules\RealEstate\Application\DTOs;

final readonly class PropertyDto
{
    public function __construct(
        public ?int $id,
        public int $tenantId,
        public string $type,
        public string $title,
        public string $description,
        public string $address,
        public string $city,
        public string $country,
        public float $price,
        public string $currency,
        public float $area,
        public int $bedrooms,
        public int $bathrooms,
        public string $status,
        public array $amenities,
        public array $metadata,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            tenantId: (int) ($data['tenant_id'] ?? 1),
            type: (string) ($data['type'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            address: (string) ($data['address'] ?? ''),
            city: (string) ($data['city'] ?? ''),
            country: (string) ($data['country'] ?? ''),
            price: (float) ($data['price'] ?? 0.0),
            currency: (string) ($data['currency'] ?? 'USD'),
            area: (float) ($data['area'] ?? 0.0),
            bedrooms: (int) ($data['bedrooms'] ?? 0),
            bathrooms: (int) ($data['bathrooms'] ?? 0),
            status: (string) ($data['status'] ?? 'available'),
            amenities: (array) ($data['amenities'] ?? []),
            metadata: (array) ($data['metadata'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'price' => $this->price,
            'currency' => $this->currency,
            'area' => $this->area,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'status' => $this->status,
            'amenities' => $this->amenities,
            'metadata' => $this->metadata,
        ];
    }
}
