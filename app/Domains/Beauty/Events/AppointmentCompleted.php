<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Events;

use App\Domains\Beauty\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ramsey\Uuid\Uuid;

/**
 * Событие: запись завершена.
 * Production 2026.
 */
final class AppointmentCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        readonly public Appointment $appointment,
        readonly public string $correlationId = '',
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('beauty.appointments'),
        ];
    }
}
