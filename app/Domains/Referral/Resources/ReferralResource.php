<?php declare(strict_types=1);

namespace App\Domains\Referral\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReferralResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->resource->id,
            'uuid'              => $this->resource->uuid,
            'tenant_id'         => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'referral_code'     => $this->resource->referral_code,
            'program_id'        => $this->resource->program_id,
            'referrer_id'       => $this->resource->referrer_id,
            'referred_id'       => $this->resource->referred_id,
            'reward_type'       => $this->resource->reward_type,
            'reward_value'      => $this->resource->reward_value,
            'uses_count'        => $this->resource->uses_count ?? 0,
            'max_uses'          => $this->resource->max_uses,
            'status'            => $this->resource->status,
            'expires_at'        => $this->resource->expires_at,
            'tags'              => $this->resource->tags,
            'correlation_id'    => $this->resource->correlation_id,
            'created_at'        => $this->resource->created_at,
            'updated_at'        => $this->resource->updated_at,
        ];
    }

    /** @return array<string, mixed> */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->attributes->get('correlation_id'),
                'generated_at'   => now()->toIso8601String(),
            ],
        ];
    }
}
