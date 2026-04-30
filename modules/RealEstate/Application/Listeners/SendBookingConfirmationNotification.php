<?php

declare(strict_types=1);

namespace Modules\RealEstate\Listeners;

use Modules\RealEstate\Events\BookingConfirmed;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Mail\Mailer;
use App\Mail\BookingConfirmedMail;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\NewBookingNotification;

final class SendBookingConfirmationNotification
{
    public function __construct(
        private readonly LogManager $log,
        private readonly Mailer $mailer,
    ) {}

    public function handle(BookingConfirmed $event): void
    {
        $booking = $event->booking;
        $user = $booking->user;

        try {
            $this->log->channel('audit')->info('real_estate.booking.confirmation_notification.start', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'correlation_id' => $event->correlationId,
            ]);

            $user->notify(new BookingConfirmedNotification($booking));

            if ($user->email) {
                $this->mailer->to($user->email)->send(new BookingConfirmedMail($booking));
            }

            $propertyOwner = $booking->property->owner;
            $propertyOwner->notify(new NewBookingNotification($booking));

            $this->log->channel('audit')->info('real_estate.booking.confirmation_notification.success', [
                'booking_id' => $booking->id,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->critical('real_estate.booking.confirmation_notification.error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
