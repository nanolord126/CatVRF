<?php

declare(strict_types=1);

namespace App\Domains\Finances\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource для преобразования FinanceRecord.
 *
 * Форматирует данные модели для API-ответов.
 * Всегда включает correlation_id в meta.
 * Tenant-aware: не раскрывает внутренние ID других тенантов.
 *
 * @package App\Domains\Finances\Resources
 */
final class FinanceRecordResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'uuid'              => $this->uuid ?? null,
            'tenant_id'         => $this->tenant_id,
            'business_group_id' => $this->business_group_id ?? null,
            'type'              => $this->type ?? null,
            'amount'            => $this->amount ?? null,
            'amount_in_rubles'  => $this->amount !== null
                ? round($this->amount / 100, 2)
                : null,
            'currency'          => $this->currency ?? 'RUB',
            'status'            => $this->status ?? null,
            'description'       => $this->description ?? null,
            'wallet_id'         => $this->wallet_id ?? null,
            'correlation_id'    => $this->correlation_id ?? null,
            'metadata'          => $this->when(
                $this->metadata !== null,
                fn () => $this->metadata,
            ),
            'tags'              => $this->when(
                $this->tags !== null,
                fn () => $this->tags,
            ),
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Дополнительные метаданные ответа.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->header(
                    'X-Correlation-ID',
                    $this->correlation_id ?? '',
                ),
            ],
        ];
    }
}