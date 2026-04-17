<?php declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use App\Domains\Beauty\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class AppointmentBookedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public string $correlationId,
    ) {}

    public function getAppointmentId(): int
    {
        return $this->appointment->id;
    }

    public function getUserId(): int
    {
        return $this->appointment->user_id;
    }

    public function getSalonId(): int
    {
        return $this->appointment->salon_id;
    }

    public function getMasterId(): int
    {
        return $this->appointment->master_id;
    }

    public function getTotalPrice(): float
    {
        return (float) $this->appointment->total_price;
    }

    public function isB2b(): bool
    {
        return (bool) $this->appointment->is_b2b;
    }
}
