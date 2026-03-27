<?php

declare(strict_types=1);

namespace App\Domains\Medical\Events;

use App\Domains\Medical\Models\MedicalAppointment;
use App\Domains\Medical\Models\MedicalRecord;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * КАНОН 2026 — MEDICAL APPOINTMENT COMPLETED EVENT
 */
final class MedicalAppointmentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public MedicalAppointment $appointment,
        public MedicalRecord $record,
        public string $correlation_id
    ) {}
}
