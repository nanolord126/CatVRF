<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Resources\B2B;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * B2B API Resource: B2BDeal.
 *
 * CANON 2026 — Layer 8: Resources (B2B namespace).
 */
final class B2BDealResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isB2B = $request->has('inn') && $request->has('business_card_id');

        return [
            'id'                => $this->id,
            'uuid'              => $this->uuid ?? null,
            'name'              => $this->name ?? $this->title ?? null,
            'description'       => $this->description ?? null,
            'status'            => $this->status ?? 'active',
            'tags'              => $this->tags ?? [],
            'correlation_id'    => $this->correlation_id ?? null,
            'tenant_id'         => $this->tenant_id,
            'business_group_id' => $this->when($isB2B, $this->business_group_id ?? null),
            'b2b_pricing'       => $this->when($isB2B, fn () => [
                'wholesale_discount_percent' => 15,
            ]),
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->header('X-Correlation-ID', (string) Str::uuid()),
                'api_version'    => 'v1',
                'resource_type'  => 'b2_b_deal',
                'tenant_id'      => $this->tenant_id,
            ],
        ];
    }
}
