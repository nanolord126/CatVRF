<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\AppointmentCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final class HandleAppointmentCancelledListener implements ShouldQueue
{
    public function handle(AppointmentCancelled $event): void
    {
        $appointment = $event->appointment;
        
        // Release held consumables (inventory)
        if ($appointment->status === 'cancelled' && $appointment->held_consumables) {
            app(\App\Services\InventoryManagementService::class)->releaseStock(
                $appointment->id,
                'appointment',
                $event->correlationId
            );
        }

        // Notify client about cancellation
        if ($appointment->client) {
            Notification::send(
                $appointment->client,
                new \App\Notifications\AppointmentCancelledNotification(
                    $appointment,
                    $event->reason
                )
            );
        }

        Log::channel('audit')->info('AppointmentCancelled event handled', [
            'appointment_id' => $appointment->id,
            'reason' => $event->reason,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
