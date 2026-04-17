<?php declare(strict_types=1);

namespace App\Domains\Beauty\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MasterMatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'master_id' => $this['master_id'] ?? null,
            'name' => $this['name'] ?? '',
            'salon' => [
                'id' => $this['salon_id'] ?? null,
                'name' => $this['salon_name'] ?? '',
            ],
            'specialization' => $this['specialization'] ?? '',
            'rating' => (float) ($this['rating'] ?? 0.0),
            'experience_years' => (int) ($this['experience_years'] ?? 0),
            'match_score' => (float) ($this['match_score'] ?? 0.0),
            'is_top_rated' => (bool) ($this['is_top_rated'] ?? false),
            'has_flash_discount' => (bool) ($this['has_flash_discount'] ?? false),
            'services' => array_map(function ($service) {
                return [
                    'service_id' => $service['service_id'] ?? null,
                    'name' => $service['name'] ?? '',
                    'price' => (float) ($service['price'] ?? 0),
                    'duration_minutes' => (int) ($service['duration_minutes'] ?? 0),
                ];
            }, $this['services'] ?? []),
        ];
    }
}
