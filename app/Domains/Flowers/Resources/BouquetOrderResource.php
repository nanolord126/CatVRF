<?php declare(strict_types=1);

namespace App\Domains\Flowers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BouquetOrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->resource->id,
            'uuid'             => $this->resource->uuid,
            'tenant_id'        => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'shop_id'          => $this->resource->shop_id,
            'status'           => $this->resource->status,
            'items'            => $this->resource->items,
            'total_amount'     => $this->resource->total_amount,
            'delivery_at'      => $this->resource->delivery_at,
            'recipient_name'   => $this->resource->recipient_name,
            'recipient_phone'  => $this->resource->recipient_phone,
            'postcard_message' => $this->resource->postcard_message,
            'is_anonymous'     => $this->resource->is_anonymous,
            'tags'             => $this->resource->tags,
            'correlation_id'   => $this->resource->correlation_id,
            'created_at'       => $this->resource->created_at,
            'updated_at'       => $this->resource->updated_at,
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
