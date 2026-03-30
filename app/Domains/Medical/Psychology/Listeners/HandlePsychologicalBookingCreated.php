<?php declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HandlePsychologicalBookingCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
