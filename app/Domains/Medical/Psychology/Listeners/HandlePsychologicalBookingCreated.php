<?php declare(strict_types=1);

/**
 * HandlePsychologicalBookingCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/handlepsychologicalbookingcreated
 */


namespace App\Domains\Medical\Psychology\Listeners;


use Psr\Log\LoggerInterface;
final class HandlePsychologicalBookingCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    public function handle(PsychologicalBookingCreated $event): void
        {
            $this->logger->info('Listener: PsychologicalBookingCreated triggered', [
                'booking_id' => $event->booking->id,
                'correlation_id' => $event->correlationId,
            ]);

            // Ставим джобу на напоминание за 2 часа до начала
            PsychologicalReminderJob::dispatch(
                $event->booking->id,
                $event->correlationId
            )->delay($event->booking->scheduled_at->subHours(2));
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
