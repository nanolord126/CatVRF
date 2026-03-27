<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Events;

use App\Domains\EventPlanning\Entertainment\Models\Ticket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * КАНОН 2026 — TICKET CHECKED IN EVENT
 */
final class TicketCheckedInEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $correlationId
    ) {
    }
}
