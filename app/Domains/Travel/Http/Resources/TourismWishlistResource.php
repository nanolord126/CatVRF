<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tourism Wishlist Resource
 * 
 * API resource for tourism wishlist responses.
 */
final class TourismWishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'tour_id' => $this->tour_id,
            'tour' => [
                'id' => $this->tour?->id,
                'uuid' => $this->tour?->uuid,
                'title' => $this->tour?->title,
                'destination' => $this->tour?->destination?->name ?? null,
                'base_price' => (float) ($this->tour?->base_price ?? 0),
                'duration_days' => $this->tour?->duration_days ?? 0,
            ],
            'priority' => $this->priority,
            'notes' => $this->notes,
            'budget_range' => $this->budget_range,
            'preferred_dates' => $this->preferred_dates,
            'group_size' => $this->group_size,
            'special_requests' => $this->special_requests,
            'is_high_priority' => $this->isHighPriority(),
            'has_budget' => $this->hasBudget(),
            'has_preferred_dates' => $this->hasPreferredDates(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
