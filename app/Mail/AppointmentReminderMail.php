<?php declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Class AppointmentReminderMail
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Mail
 */
final class AppointmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

        public function __construct(
            private readonly Appointment $appointment,
        ) {}

        /**
         * Handle envelope operation.
         *
         * @throws \DomainException
         */
        public function envelope(): Envelope
        {
            return new Envelope(
                subject: 'Напоминание о записи',
            );
        }

        /**
         * Handle content operation.
         *
         * @throws \DomainException
         */
        public function content(): Content
        {
            return new Content(
                view: 'emails.appointment-reminder',
                with: [
                    'masterName' => $this->appointment->master->full_name ?? '',
                    'serviceName' => $this->appointment->service->name ?? '',
                    'datetime' => $this->appointment->datetime_start?->format('d.m.Y H:i'),
                    'salonAddress' => $this->appointment->salon->address ?? '',
                ],
            );
        }
}
