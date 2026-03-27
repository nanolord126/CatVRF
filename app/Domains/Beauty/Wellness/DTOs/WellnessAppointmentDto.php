<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Wellness\DTOs;

/**
 * WellnessAppointmentDto - Data Transfer Object for creating/updating appointments.
 */
final readonly class WellnessAppointmentDto
{
    public function __construct(
        public int $center_id,
        public int $specialist_id,
        public int $service_id,
        public int $client_id,
        public string $datetime_start,
        public string $datetime_end,
        public string $status = 'pending',
        public int $price = 0,
        public string $payment_status = 'unpaid',
        public array $medical_notes = [],
        public ?string $correlation_id = null,
    ) {}

    /**
     * Map DTO to an array for Eloquent manipulation.
     */
    public function toArray(): array
    {
        return [
            'center_id' => $this->center_id,
            'specialist_id' => $this->specialist_id,
            'service_id' => $this->service_id,
            'client_id' => $this->client_id,
            'datetime_start' => $this->datetime_start,
            'datetime_end' => $this->datetime_end,
            'status' => $this->status,
            'price' => $this->price,
            'payment_status' => $this->payment_status,
            'medical_notes' => $this->medical_notes,
            'correlation_id' => $this->correlation_id,
        ];
    }
}
