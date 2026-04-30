<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Http\Resources;

use App\Domains\Beauty\Models\Master;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\CarbonImmutable;

/**
 * MasterResource — API-представление мастера салона.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Включает: специализация, рейтинг, расписание, салон.
 *
 * @mixin Master
 */
final class MasterResource extends JsonResource
{
    /**
     * Трансформация модели в массив для API-ответа.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'salon_id'        => $this->salon_id,
            'full_name'       => $this->full_name,
            'specialization'  => $this->specialization,
            'rating'          => $this->rating ? (float) $this->rating : 5.0,
            'experience_years' => $this->experience_years ?? null,
            'bio'             => $this->bio ?? null,
            'avatar_url'      => $this->avatar_url ?? null,
            'is_active'       => (bool) ($this->is_active ?? true),
            'schedule'        => $this->schedule ?? [],
            'services'        => ServiceResource::collection($this->whenLoaded('services')),
            'salon'           => new SalonResource($this->whenLoaded('salon')),
            'appointments_count' => $this->whenCounted('appointments'),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Дополнительные метаданные в ответе.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->attributes->get('correlation_id'),
                'generated_at'   => CarbonImmutable::now()->toIso8601String(),
            ],
        ];
    }
}
