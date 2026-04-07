<?php

declare(strict_types=1);

namespace App\Domains\Art\Resources\B2B;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * B2B API Resource: произведение искусства (Artwork).
 *
 * CANON 2026 — Layer 8: Resources (B2B namespace).
 * Трансформирует модель Artwork в JSON для B2B API.
 * Всегда включает correlation_id, tenant_id в meta.
 * B2B-поля (оптовые цены, business_group) видны только для B2B-запросов.
 *
 * @package App\Domains\Art\Resources\B2B
 */
final class ArtworkResource extends JsonResource
{
    /**
     * Трансформация модели Artwork в массив для JSON-ответа.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isB2B = $request->has('inn') && $request->has('business_card_id');

        return [
            'id'                => $this->id,
            'uuid'              => $this->uuid,
            'title'             => $this->title,
            'description'       => $this->description,
            'artist_id'         => $this->artist_id,
            'project_id'        => $this->project_id,
            'technique'         => $this->technique ?? null,
            'dimensions'        => $this->dimensions ?? null,
            'price_cents'       => $this->price_cents,
            'price_rubles'      => number_format($this->price_cents / 100, 2, '.', ''),
            'is_visible'        => (bool) $this->is_visible,
            'status'            => $this->status ?? 'published',
            'tags'              => $this->tags ?? [],
            'correlation_id'    => $this->correlation_id,
            'tenant_id'         => $this->tenant_id,
            'business_group_id' => $this->when($isB2B, $this->business_group_id),
            'b2b_pricing'       => $this->when($isB2B, fn () => [
                'wholesale_price_cents' => (int) round($this->price_cents * 0.85),
                'moq'                   => 1,
                'discount_percent'      => 15,
            ]),
            'artist'            => $this->whenLoaded('artist', fn () => [
                'id'     => $this->artist->id,
                'name'   => $this->artist->name,
                'rating' => $this->artist->rating,
            ]),
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Мета-данные, добавляемые к каждому ответу.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->header('X-Correlation-ID', (string) Str::uuid()),
                'api_version'    => 'v1',
                'resource_type'  => 'artwork',
                'tenant_id'      => $this->tenant_id,
            ],
        ];
    }
}
