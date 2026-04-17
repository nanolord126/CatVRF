<?php declare(strict_types=1);

namespace Modules\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\RealEstate\Enums\BookingStatus;

final class PropertyBookingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'property_id' => $this->property_id,
            'user_id' => $this->user_id,
            'viewing_slot' => $this->viewing_slot?->toIso8601String(),
            'amount' => (float) $this->amount,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'deal_score' => $this->deal_score,
            'fraud_score' => (float) $this->fraud_score,
            'is_b2b' => $this->is_b2b,
            'hold_until' => $this->hold_until?->toIso8601String(),
            'is_hold_expired' => $this->isHoldExpired(),
            'can_be_confirmed' => $this->canBeConfirmed(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'face_id_verified' => $this->face_id_verified,
            'blockchain_verified' => $this->blockchain_verified,
            'webrtc_room_id' => $this->webrtc_room_id,
            'original_price' => (float) $this->original_price,
            'dynamic_discount' => (float) $this->dynamic_discount,
            'escrow_amount' => (float) $this->escrow_amount,
            'commission_split' => $this->commission_split,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'correlation_id' => $this->correlation_id,
        ];
    }
}
