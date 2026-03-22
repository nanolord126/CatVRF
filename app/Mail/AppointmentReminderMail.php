<?php

declare(strict_types=1);

namespace App\Mail;

use App\Domains\Beauty\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class AppointmentReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Напоминание о записи',
        );
    }

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
