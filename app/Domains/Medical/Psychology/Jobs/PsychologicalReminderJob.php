<?php

declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Jobs;

use App\Domains\Medical\Psychology\Models\PsychologicalBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Джоба для напоминания о сессиях.
 */
final class PsychologicalReminderJob implements ShouldQueue
{
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
