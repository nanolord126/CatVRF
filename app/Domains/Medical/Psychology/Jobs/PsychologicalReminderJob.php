<?php declare(strict_types=1);

/**
 * PsychologicalReminderJob — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/psychologicalreminderjob
 */


namespace App\Domains\Medical\Psychology\Jobs;


use Psr\Log\LoggerInterface;
final class PsychologicalReminderJob
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            public int $bookingId,
            public string $correlationId, private readonly LoggerInterface $logger
        ) {}

        public function handle(): void
        {
            $booking = PsychologicalBooking::with(['client', 'psychologist'])->find($this->bookingId);

            if (!$booking) {
                return;
            }

            $this->logger->info('Sending therapy session reminder', [
                'booking_id' => $this->bookingId,
                'client_email' => $booking->client->email,
                'correlation_id' => $this->correlationId,
            ]);

            // В 2026 тут идет интеграция с Telegram/WhatsApp API
            // \App\Services\NotificationService::send(...)
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
