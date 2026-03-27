<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Events;

use App\Domains\EventPlanning\Entertainment\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * КАНОН 2026 — BOOKING CREATED EVENT
 */
final class BookingCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Booking $booking,
        public readonly string $correlationId
    ) {
    }
}
