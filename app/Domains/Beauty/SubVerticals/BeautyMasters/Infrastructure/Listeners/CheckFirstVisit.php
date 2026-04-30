<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Listeners;

use Modules\BeautyMasters\Domain\Events\AppointmentCompleted;
use Modules\BeautyMasters\Infrastructure\Models\AppointmentModel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

final readonly class CheckFirstVisit
{
    public function handle(AppointmentCompleted $event): void
    {
        try {
            $totalAppointments = AppointmentModel::where('user_id', $event->userId)
                ->where('venue_id', $event->venueId)
                ->where('status', 'completed')
                ->count();

            if ($totalAppointments === 1) {
                Event::dispatch(new \Modules\BeautyMasters\Domain\Events\UserFirstVisit(
                    userId: $event->userId,
                    venueId: $event->venueId,
                    appointmentId: $event->appointmentId,
                ));

                Log::info('First visit detected', [
                    'user_id' => $event->userId,
                    'venue_id' => $event->venueId,
                    'appointment_id' => $event->appointmentId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to check first visit', [
                'appointment_id' => $event->appointmentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
