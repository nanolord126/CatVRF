<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tourism Booking Resource
 * 
 * API resource for tourism booking responses.
 * Includes all killer features data for frontend consumption.
 */
final class TourismBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'business_group_id' => $this->business_group_id,
            'tour_id' => $this->tour_id,
            'tour' => [
                'id' => $this->tour?->id,
                'uuid' => $this->tour?->uuid,
                'title' => $this->tour?->title,
                'destination' => $this->tour?->destination?->name ?? null,
            ],
            'user_id' => $this->user_id,
            'person_count' => $this->person_count,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'total_amount' => (float) $this->total_amount,
            'base_price' => (float) $this->base_price,
            'dynamic_price' => (float) $this->dynamic_price,
            'discount_amount' => (float) $this->discount_amount,
            'commission_rate' => (float) $this->commission_rate,
            'commission_amount' => (float) $this->commission_amount,
            'status' => $this->status,
            'biometric_token' => $this->biometric_token,
            'biometric_verified' => $this->biometric_verified,
            'hold_expires_at' => $this->hold_expires_at?->toIso8601String(),
            'virtual_tour_viewed' => $this->virtual_tour_viewed,
            'virtual_tour_viewed_at' => $this->virtual_tour_viewed_at?->toIso8601String(),
            'video_call_scheduled' => $this->video_call_scheduled,
            'video_call_time' => $this->video_call_time?->toIso8601String(),
            'video_call_link' => $this->video_call_link,
            'video_call_meeting_id' => $this->video_call_meeting_id,
            'video_call_join_url' => $this->video_call_join_url,
            'payment_method' => $this->payment_method,
            'split_payment_enabled' => $this->split_payment_enabled,
            'cashback_amount' => (float) $this->cashback_amount,
            'cancellation_reason' => $this->cancellation_reason,
            'refund_amount' => (float) $this->refund_amount,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'fraud_score' => $this->fraud_score ? (float) $this->fraud_score : null,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'tags' => $this->tags,
            'metadata' => $this->metadata,
            'is_b2b' => $this->business_group_id !== null,
            'is_held' => $this->status === 'held' && $this->hold_expires_at && $this->hold_expires_at->isFuture(),
            'is_confirmed' => $this->status === 'confirmed',
            'is_cancelled' => $this->status === 'cancelled',
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
