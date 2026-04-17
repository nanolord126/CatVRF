<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class UpdateListingDto
{
    public function __construct(
        public int $tenantId,
        public string $propertyUuid,
        public int $sellerId,
        public string $correlationId,
        public ?string $title,
        public ?string $description,
        public ?float $price,
        public ?float $area,
        public ?int $rooms,
        public ?int $bathrooms,
        public ?string $contactPhone,
        public ?string $contactEmail,
        public ?bool $showContact,
        public ?string $availableFrom,
        public ?array $amenities,
        public ?array $features,
        public ?array $images,
        public $video,
        public $tour3DModel,
        public bool $regenerateAI,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) $request->header('X-Tenant-ID'),
            propertyUuid: $request->input('property_uuid'),
            sellerId: (int) $request->input('seller_id'),
            correlationId: $request->header('X-Correlation-ID') ?? (string) Str::uuid(),
            title: $request->input('title'),
            description: $request->input('description'),
            price: $request->has('price') ? (float) $request->input('price') : null,
            area: $request->has('area') ? (float) $request->input('area') : null,
            rooms: $request->has('rooms') ? (int) $request->input('rooms') : null,
            bathrooms: $request->has('bathrooms') ? (int) $request->input('bathrooms') : null,
            contactPhone: $request->input('contact_phone'),
            contactEmail: $request->input('contact_email'),
            showContact: $request->has('show_contact') ? (bool) $request->input('show_contact') : null,
            availableFrom: $request->input('available_from'),
            amenities: $request->input('amenities'),
            features: $request->input('features'),
            images: $request->file('images'),
            video: $request->file('video'),
            tour3DModel: $request->file('tour_3d_model'),
            regenerateAI: (bool) $request->input('regenerate_ai', false),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'property_uuid' => $this->propertyUuid,
            'seller_id' => $this->sellerId,
            'correlation_id' => $this->correlationId,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'area' => $this->area,
            'rooms' => $this->rooms,
            'bathrooms' => $this->bathrooms,
            'contact_phone' => $this->contactPhone,
            'contact_email' => $this->contactEmail,
            'show_contact' => $this->showContact,
            'available_from' => $this->availableFrom,
            'amenities' => $this->amenities,
            'features' => $this->features,
            'regenerate_ai' => $this->regenerateAI,
        ];
    }
}
