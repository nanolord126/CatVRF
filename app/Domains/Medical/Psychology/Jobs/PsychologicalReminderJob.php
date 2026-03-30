<?php declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PsychologicalReminderJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            public int $bookingId,
            public string $correlationId
        ) {}

        public function handle(): void
        {
            $booking = PsychologicalBooking::with(['client', 'psychologist'])->find($this->bookingId);

            if (!$booking) {
                return;
            }

            Log::channel('audit')->info('Sending therapy session reminder', [
                'booking_id' => $this->bookingId,
                'client_email' => $booking->client->email,
                'correlation_id' => $this->correlationId,
            ]);

            // В 2026 тут идет интеграция с Telegram/WhatsApp API
            // \App\Services\NotificationService::send(...)
        }
}
