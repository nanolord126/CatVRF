<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Resources;

use App\Domains\Beauty\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * AppointmentResource — API-представление записи к мастеру.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Включает: дата, время, мастер, услуга, статус, стоимость.
 *
 * @mixin Appointment
 */
final class AppointmentResource extends JsonResource
{
    /**
     * Трансформация модели в массив для API-ответа.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid ?? null,
            'salon_id'         => $this->salon_id,
            'master_id'        => $this->master_id,
            'service_id'       => $this->service_id,
            'user_id'          => $this->user_id,
            'appointment_date' => $this->appointment_date,
            'time_slot'        => $this->time_slot,
            'status'           => $this->status,
            'price'            => $this->price ? number_format((float) $this->price, 2, '.', '') : null,
            'notes'            => $this->notes ?? null,
            'correlation_id'   => $this->correlation_id,
            'master'           => new MasterResource($this->whenLoaded('master')),
            'service'          => new ServiceResource($this->whenLoaded('service')),
            'salon'            => new SalonResource($this->whenLoaded('salon')),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Дополнительные метаданные в ответе.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function with(\Illuminate\Http\Request $request): array
    {
        return [
            'meta' => [
                'correlation_id' => $request->attributes->get('correlation_id'),
                'generated_at'   => now()->toIso8601String(),
            ],
        ];
    }
}
