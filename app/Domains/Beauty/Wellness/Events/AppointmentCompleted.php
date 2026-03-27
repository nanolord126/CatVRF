<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Wellness\Events;

use App\Domains\Beauty\Wellness\Models\WellnessAppointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * AppointmentCompleted - Triggered when a wellness appointment is successfully finished.
 */
final class AppointmentCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly WellnessAppointment $appointment,
        public readonly string $correlation_id,
    ) {}
}
