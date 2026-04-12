<?php

declare(strict_types=1);

namespace App\Domains\CRM\Http\Resources;

use App\Domains\CRM\Models\CrmClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CRM Client API Resource — форматирование ответа клиента.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmClientResource extends JsonResource
{
    /**
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        /** @var CrmClient $client */
        $client = $this->resource;

        return [
            'id' => $client->id,
            'uuid' => $client->uuid,
            'tenant_id' => $client->tenant_id,
            'full_name' => $client->full_name,
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'company_name' => $client->company_name,
            'email' => $client->email,
            'phone' => $client->phone,
            'client_type' => $client->client_type,
            'status' => $client->status,
            'source' => $client->source,
            'vertical' => $client->vertical,
            'loyalty_tier' => $client->loyalty_tier,
            'segment' => $client->segment,
            'total_spent' => (float) $client->total_spent,
            'total_orders' => (int) $client->total_orders,
            'average_order_value' => (float) $client->average_order_value,
            'bonus_points' => (int) $client->bonus_points,
            'tags' => $client->tags ?? [],
            'preferences' => $client->preferences ?? [],
            'addresses' => $client->addresses ?? [],
            'last_interaction_at' => $client->last_interaction_at?->toIso8601String(),
            'last_order_at' => $client->last_order_at?->toIso8601String(),
            'avatar_url' => $client->avatar_url,
            'preferred_language' => $client->preferred_language,
            'created_at' => $client->created_at?->toIso8601String(),
            'updated_at' => $client->updated_at?->toIso8601String(),

            // Relations (если загружены)
            'interactions_count' => $this->whenCounted('interactions'),
            'segments' => $this->whenLoaded('segments'),
            'vertical_profile' => $this->when(
                $client->relationLoaded('beautyProfile')
                    || $client->relationLoaded('autoProfile')
                    || $client->relationLoaded('foodProfile'),
                fn () => $client->verticalProfile(),
            ),
        ];
    }
}
