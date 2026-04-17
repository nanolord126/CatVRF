<?php declare(strict_types=1);

namespace App\Domains\Medical\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AppointmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->resource->id,
            'uuid'              => $this->resource->uuid,
            'tenant_id'         => $this->resource->tenant_id,
            'business_group_id' => $this->resource->business_group_id,
            'clinic_id'         => $this->resource->clinic_id,
            'doctor_id'         => $this->resource->doctor_id,
            'service_id'        => $this->resource->service_id,
            'patient_id'        => $this->resource->patient_id,
            'appointment_at'    => $this->resource->appointment_at,
            'status'            => $this->resource->status,
            'prepayment_amount' => $this->resource->prepayment_amount,
            'client_notes'      => $this->resource->client_notes,
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
