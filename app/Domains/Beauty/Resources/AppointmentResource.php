<?php declare(strict_types=1);

namespace App\Domains\Beauty\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'salon' => [
                'id' => $this->salon_id,
                'name' => $this->salon->name ?? null,
                'address' => $this->salon->address ?? null,
                'lat' => $this->salon->lat ?? null,
                'lon' => $this->salon->lon ?? null,
            ],
            'master' => [
                'id' => $this->master_id,
                'name' => $this->master->name ?? null,
                'specialization' => $this->master->specialization ?? null,
                'rating' => $this->master->rating ?? 0.0,
                'experience_years' => $this->master->experience_years ?? 0,
            ],
            'service' => [
                'id' => $this->service_id,
                'name' => $this->service->name ?? null,
                'description' => $this->service->description ?? null,
                'duration_minutes' => $this->service->duration_minutes ?? 0,
                'base_price' => (float) ($this->service->price ?? 0),
            ],
            'status' => $this->status,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'total_price' => (float) $this->total_price,
            'is_b2b' => (bool) $this->is_b2b,
            'pricing_details' => $this->metadata['pricing_details'] ?? [],
            'payment_status' => $this->metadata['payment_status'] ?? 'pending',
            'webrtc_room_id' => $this->metadata['webrtc_room_id'] ?? null,
            'video_call_expires_at' => $this->metadata['video_call_expires_at'] ?? null,
            'cancellation_reason' => $this->cancellation_reason,
            'refund_amount' => $this->metadata['refund_amount'] ?? null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'correlation_id' => $this->correlation_id,
        ];
    }
}
