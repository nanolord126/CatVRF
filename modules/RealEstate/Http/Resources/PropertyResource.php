<?php declare(strict_types=1);

namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\PropertyType;

final class PropertyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'owner_id' => $this->owner_id,
            'owner' => $this->whenLoaded('owner', fn() => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
            ]),
            'title' => $this->title,
            'description' => $this->description,
            'address' => $this->address,
            'city' => $this->city,
            'region' => $this->region,
            'location' => [
                'lat' => (float) $this->lat,
                'lon' => (float) $this->lon,
            ],
            'property_type' => $this->property_type->value,
            'property_type_label' => $this->property_type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_available' => $this->status->isAvailable(),
            'price' => (float) $this->price,
            'price_per_sqm' => $this->getPricePerSquareMeter(),
            'area' => (float) $this->area,
            'rooms' => $this->rooms,
            'floor' => $this->floor,
            'total_floors' => $this->total_floors,
            'year_built' => $this->year_built,
            'features' => $this->features,
            'images' => $this->images,
            'has_virtual_tour' => $this->hasVirtualTour(),
            'virtual_tour_url' => $this->virtual_tour_url,
            'has_ar_model' => $this->hasARModel(),
            'ar_model_url' => $this->ar_model_url,
            'document_hashes' => $this->document_hashes,
            'tags' => $this->tags,
            'metadata' => $this->metadata,
            'bookings_count' => $this->whenLoaded('bookings', fn() => $this->bookings->count()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'correlation_id' => $this->correlation_id,
        ];
    }
}
