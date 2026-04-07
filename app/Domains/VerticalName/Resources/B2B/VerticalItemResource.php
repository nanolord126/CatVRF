<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Resources\B2B;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource: VerticalItem для B2B-ответов.
 *
 * CANON 2026 — Layer 8: Resources.
 * Форматирование ответа для B2B API и Tenant Panel.
 * Включает оптовые цены, B2B-доступность, SKU.
 * Tenant isolation обеспечивается на уровне сервиса.
 *
 * @package App\Domains\VerticalName\Resources\B2B
 */
final class VerticalItemResource extends JsonResource
{
    /**
     * Преобразование модели в массив для API-ответа.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'uuid' => $this->resource->uuid,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'price_kopecks' => $this->resource->price_kopecks,
            'price_rubles' => $this->resource->price_rubles,
            'sku' => $this->resource->sku,
            'category' => $this->resource->category,
            'rating' => $this->resource->rating,
            'review_count' => $this->resource->review_count,
            'is_active' => $this->resource->is_active,
            'is_b2b_available' => $this->resource->is_b2b_available,
            'stock_quantity' => $this->resource->stock_quantity,
            'is_available' => $this->resource->isAvailable(),
            'image_url' => $this->resource->image_url,
            'tags' => $this->resource->tags ?? [],
            'metadata' => $this->when(
                $request->user()?->hasRole('tenant_owner'),
                $this->resource->metadata,
            ),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Дополнительные данные для обёртки ответа.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->header('X-Correlation-ID', ''),
                'tenant_id' => (int) (function_exists('tenant') && tenant() !== null ? tenant()->id : 0),
                'is_b2b' => $request->has('inn') && $request->has('business_card_id'),
            ],
        ];
    }
}
