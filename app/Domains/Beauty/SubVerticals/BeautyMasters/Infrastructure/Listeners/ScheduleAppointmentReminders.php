<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Listeners;

use Modules\BeautyMasters\Domain\Events\AppointmentConfirmed;
use Modules\BeautyMasters\Application\Jobs\SendReminder24h;
use Modules\BeautyMasters\Application\Jobs\SendReminder2h;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Bus\Bus;

final readonly class ScheduleAppointmentReminders
{
    public function handle(AppointmentConfirmed $event): void
    {
        try {
            $now = now();
            $appointmentTime = $event->startTime->setTimezone($now->timezone);

            // Schedule 24h reminder
            $reminder24hTime = $appointmentTime->subHours(24);
            if ($reminder24hTime > $now) {
                SendReminder24h::dispatch($event->appointmentId)
                    ->delay($reminder24hTime);
            }

            // Schedule 2h reminder
            $reminder2hTime = $appointmentTime->subHours(2);
            if ($reminder2hTime > $now) {
                SendReminder2h::dispatch($event->appointmentId)
                    ->delay($reminder2hTime);
            }

            Log::info('Appointment reminders scheduled', [
                'appointment_id' => $event->appointmentId,
                'start_time' => $event->startTime->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to schedule appointment reminders', [
                'appointment_id' => $event->appointmentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
