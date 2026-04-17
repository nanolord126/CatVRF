<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

final class PropertyViewingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'property_id' => $this->property_id,
            'user_id' => $this->user_id,
            'agent_id' => $this->agent_id,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'held_at' => $this->held_at?->toIso8601String(),
            'hold_expires_at' => $this->hold_expires_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'status' => $this->status,
            'is_b2b' => $this->is_b2b,
            'webrtc_room_id' => $this->webrtc_room_id,
            'faceid_verified' => $this->faceid_verified,
            'cancellation_reason' => $this->cancellation_reason,
            'is_expired' => $this->isExpired(),
            'time_until_expiry' => $this->hold_expires_at 
                ? now()->diffForHumans($this->hold_expires_at, true) 
                : null,
            'correlation_id' => $this->correlation_id,
            'metadata' => $this->metadata,
            'tags' => $this->tags,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            'property' => $this->whenLoaded('property', fn() => [
                'id' => $this->property->id,
                'uuid' => $this->property->uuid,
                'title' => $this->property->title,
                'address' => $this->property->address,
                'price' => $this->property->price,
                'type' => $this->property->type,
                'area_sqm' => $this->property->area_sqm,
                'photos' => collect($this->property->photos ?? [])->map(fn($photo) => Storage::url($photo)),
                'features' => $this->property->features ?? [],
            ]),
            
            'agent' => $this->whenLoaded('agent', fn() => [
                'id' => $this->agent?->id,
                'uuid' => $this->agent?->uuid,
                'full_name' => $this->agent?->full_name,
                'phone' => $this->agent?->phone,
                'rating' => $this->agent?->rating,
                'deals_count' => $this->agent?->deals_count,
            ]),
            
            'actions' => [
                'can_confirm' => $this->status === 'held' && !$this->isExpired(),
                'can_cancel' => in_array($this->status, ['pending', 'held', 'confirmed']),
                'can_complete' => $this->status === 'confirmed',
                'can_reschedule' => in_array($this->status, ['pending', 'held']),
            ],
        ];
    }
}
