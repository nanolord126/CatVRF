<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Resources;

use App\Domains\Beauty\Models\Salon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * SalonResource — API-представление салона красоты.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Всегда включает tenant_id и correlation_id.
 * Мастера и услуги подгружаются через whenLoaded().
 *
 * @mixin Salon
 */
final class SalonResource extends JsonResource
{
    /**
     * Трансформация модели в массив для API-ответа.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'tenant_id'        => $this->tenant_id,
            'name'             => $this->name,
            'address'          => $this->address,
            'lat'              => (float) $this->lat,
            'lon'              => (float) $this->lon,
            'phone'            => $this->phone ?? null,
            'email'            => $this->email ?? null,
            'description'      => $this->description ?? null,
            'rating'           => $this->rating ? (float) $this->rating : null,
            'status'           => $this->status,
            'tags'             => $this->tags ?? [],
            'working_hours'    => $this->working_hours ?? [],
            'is_active'        => (bool) ($this->is_active ?? true),
            'masters_count'    => $this->whenCounted('masters'),
            'masters'          => MasterResource::collection($this->whenLoaded('masters')),
            'services'         => ServiceResource::collection($this->whenLoaded('services')),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Дополнительные метаданные в ответе.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function with(\Illuminate\Http\Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->attributes->get('correlation_id'),
                'generated_at'   => now()->toIso8601String(),
            ],
        ];
    }
}
