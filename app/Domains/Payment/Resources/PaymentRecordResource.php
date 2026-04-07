<?php

declare(strict_types=1);

namespace App\Domains\Payment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API-ресурс для платёжных записей.
 *
 * Возвращает все обязательные поля + correlation_id в meta.
 */
final class PaymentRecordResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'uuid' => $this->resource->uuid,
            'tenant_id' => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'idempotency_key' => $this->resource->idempotency_key,
            'provider_code' => $this->resource->provider_code?->value ?? $this->resource->provider_code,
            'status' => $this->resource->status?->value ?? $this->resource->status,
            'amount_kopecks' => $this->resource->amount_kopecks,
            'amount_rubles' => is_numeric($this->resource->amount_kopecks)
                ? $this->resource->amount_kopecks / 100
                : 0,
            'is_hold' => $this->resource->is_hold ?? false,
            'provider_payment_id' => $this->resource->provider_payment_id ?? null,
            'tags' => $this->resource->tags ?? [],
            'metadata' => $this->resource->metadata ?? [],
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }

    /**
     * Дополнительные данные, включая correlation_id.
     *
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $this->resource->correlation_id ?? '',
            ],
        ];
    }
}
