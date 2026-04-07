<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API-ресурс для сериализации Wallet в JSON.
 *
 * Возвращает только реальные поля модели + computed available_balance.
 */
final class WalletResource extends JsonResource
{
    /** @param Request $request */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'uuid' => $this->resource->uuid,
            'tenant_id' => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'current_balance' => $this->resource->current_balance,
            'hold_amount' => $this->resource->hold_amount,
            'available_balance' => $this->resource->current_balance - $this->resource->hold_amount,
            'is_active' => $this->resource->is_active,
            'correlation_id' => $this->resource->correlation_id,
            'tags' => $this->resource->tags,
            'metadata' => $this->resource->metadata,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }

    /** Дополнительные мета-данные в обёртке ответа. */
    public function with($request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->header('X-Correlation-ID', ''),
            ],
        ];
    }

    /**
     * Коллекция ресурсов.
     *
     * @param mixed $resource
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public static function collection($resource): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return parent::collection($resource);
    }
}
