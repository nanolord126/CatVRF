<?php declare(strict_types=1);

namespace App\Domains\Pet\Events;

use App\Domains\Pet\Models\PetAppointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AppointmentBooked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly PetAppointment $appointment,
        public readonly string $correlationId,
    ) {}
}
