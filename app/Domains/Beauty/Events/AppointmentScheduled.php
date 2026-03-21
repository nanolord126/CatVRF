<?php declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AppointmentScheduled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $appointmentId,
        public readonly int $masterId,
        public readonly int $clientId,
        public readonly string $scheduledAt,
        public readonly string $correlationId,
    ) {}
}
