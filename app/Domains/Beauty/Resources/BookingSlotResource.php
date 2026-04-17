<?php declare(strict_types=1);

namespace App\Domains\Beauty\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BookingSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'correlation_id' => $this->correlation_id,
            
            'salon_id' => $this->salon_id,
            'master_id' => $this->master_id,
            'service_id' => $this->service_id,
            'customer_id' => $this->customer_id,
            'order_id' => $this->order_id,
            
            'slot_date' => $this->slot_date?->format('Y-m-d'),
            'slot_time' => $this->slot_time?->format('H:i'),
            'duration_minutes' => $this->duration_minutes,
            
            'status' => $this->status,
            'held_at' => $this->held_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'booked_at' => $this->booked_at?->toIso8601String(),
            'released_at' => $this->released_at?->toIso8601String(),
            
            'metadata' => $this->metadata,
            'tags' => $this->tags,
            'is_active' => $this->is_active,
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            'is_available' => $this->isAvailable(),
            'is_held' => $this->isHeld(),
            'is_booked' => $this->isBooked(),
            'is_expired' => $this->isExpired(),
            'hold_duration_minutes' => $this->getHoldDurationMinutes(),
            
            'salon' => $this->whenLoaded('salon'),
            'master' => $this->whenLoaded('master'),
            'service' => $this->whenLoaded('service'),
            'customer' => $this->whenLoaded('customer'),
            'order' => $this->whenLoaded('order'),
        ];
    }
}
