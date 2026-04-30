<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

use readonly;

final readonly class Venue
{
    public function __construct(
        public int $id,
        public ?int $businessGroupId,
        public ?int $userId,
        public string $name,
        public string $slug,
        public ?string $description,
        public ?string $address,
        public ?string $city,
        public ?string $phone,
        public ?string $email,
        public ?string $website,
        public ?array $workingHours,
        public ?float $latitude,
        public ?float $longitude,
        public bool $isActive,
        public bool $isChain,
        public ?int $parentVenueId,
        public ?array $amenities,
        public ?array $socialMedia,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
        public ?\DateTimeImmutable $deletedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            businessGroupId: $data['business_group_id'] ?? null,
            userId: $data['user_id'] ?? null,
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            website: $data['website'] ?? null,
            workingHours: $data['working_hours'] ?? null,
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
            isActive: (bool) $data['is_active'],
            isChain: (bool) $data['is_chain'],
            parentVenueId: $data['parent_venue_id'] ?? null,
            amenities: $data['amenities'] ?? null,
            socialMedia: $data['social_media'] ?? null,
            createdAt: new \DateTimeImmutable($data['created_at']),
            updatedAt: $data['updated_at'] ? new \DateTimeImmutable($data['updated_at']) : null,
            deletedAt: $data['deleted_at'] ? new \DateTimeImmutable($data['deleted_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'business_group_id' => $this->businessGroupId,
            'user_id' => $this->userId,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'address' => $this->address,
            'city' => $this->city,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'working_hours' => $this->workingHours,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_active' => $this->isActive,
            'is_chain' => $this->isChain,
            'parent_venue_id' => $this->parentVenueId,
            'amenities' => $this->amenities,
            'social_media' => $this->socialMedia,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
