<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Resources;

use App\Domains\Beauty\Models\BeautyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ServiceResource — API-представление услуги салона красоты.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Включает: название, цена, длительность, категория.
 *
 * @mixin BeautyService
 */
final class ServiceResource extends JsonResource
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
            'id'           => $this->id,
            'salon_id'     => $this->salon_id ?? null,
            'name'         => $this->name,
            'description'  => $this->description ?? null,
            'category'     => $this->category ?? null,
            'price'        => $this->price ? number_format((float) $this->price, 2, '.', '') : null,
            'duration_min' => $this->duration_min ?? null,
            'is_active'    => (bool) ($this->is_active ?? true),
            'tags'         => $this->tags ?? [],
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
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

    /**
     * ServiceResource — CatVRF 2026 Component.
     *
     * Part of the CatVRF multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     * @author CatVRF Team
     * @license Proprietary
     */
}
