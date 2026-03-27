<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Listeners;

use App\Domains\EventPlanning\Entertainment\Events\BookingCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * КАНОН 2026 — AUDIT LOG LISTENER
 */
final class LogEntertainmentAction implements ShouldQueue
{
    public function handle(object $event): void
    {
        $payload = [];
        
        if ($event instanceof BookingCreatedEvent) {
            $payload = [
                'type' => 'booking_created',
                'booking_id' => $event->booking->id,
                'uuid' => $event->booking->uuid,
                'amount' => $event->booking->total_amount_kopecks,
            ];
        }

        Log::channel('audit')->info('Entertainment action audit', array_merge($payload, [
            'correlation_id' => $event->correlationId ?? 'unknown',
            'timestamp' => now()->toIso8601String(),
        ]));
    }
}
