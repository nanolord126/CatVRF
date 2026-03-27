<?php

declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Listeners;

use App\Domains\Medical\Psychology\Events\PsychologicalBookingCreated;
use App\Domains\Medical\Psychology\Jobs\PsychologicalReminderJob;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик создания бронирования.
 */
final class HandlePsychologicalBookingCreated
{
    public function handle(PsychologicalBookingCreated $event): void
    {
        Log::channel('audit')->info('Listener: PsychologicalBookingCreated triggered', [
            'booking_id' => $event->booking->id,
            'correlation_id' => $event->correlationId,
        ]);

        // Ставим джобу на напоминание за 2 часа до начала
        PsychologicalReminderJob::dispatch(
            $event->booking->id,
            $event->correlationId
        )->delay($event->booking->scheduled_at->subHours(2));
    }
}
