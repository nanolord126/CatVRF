<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\Dental;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $uuid
 * @property string $name
 * @property array $metadata
 * @property float $rating
 */
final class DentalClinicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'address' => $this->metadata['address'] ?? null,
            'phones' => $this->metadata['phones'] ?? [],
            'rating' => (float) ($this->rating ?? 0.0),
            'coordinates' => [
                'lat' => $this->metadata['lat'] ?? null,
                'lon' => $this->metadata['lon'] ?? null,
            ],
            'tags' => $this->tags,
            'is_emergency_friendly' => (bool) ($this->metadata['emergency'] ?? false),
        ];
    }
}
