<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Listeners;

use Modules\BeautyMasters\Domain\Events\AppointmentCancelled;
use Illuminate\Support\Facades\Log;

final readonly class HandleCancellation
{
    public function handle(AppointmentCancelled $event): void
    {
        try {
            // TODO: Implement cancellation logic:
            // - Check cancellation policy
            // - Apply penalties if needed
            // - Notify master
            // - Release slot

            Log::info('Appointment cancelled', [
                'appointment_id' => $event->appointmentId,
                'user_id' => $event->userId,
                'reason' => $event->reason,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to handle cancellation', [
                'appointment_id' => $event->appointmentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
