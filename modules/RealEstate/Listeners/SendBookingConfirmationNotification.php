<?php declare(strict_types=1);

namespace Modules\RealEstate\Listeners;

use Modules\RealEstate\Events\BookingConfirmed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmedMail;
use App\Notifications\BookingConfirmedNotification;

final class SendBookingConfirmationNotification
{
    public function handle(BookingConfirmed $event): void
    {
        $booking = $event->booking;
        $user = $booking->user;

        try {
            Log::channel('audit')->info('real_estate.booking.confirmation_notification.start', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'correlation_id' => $event->correlationId,
            ]);

            $user->notify(new BookingConfirmedNotification($booking));

            if ($user->email) {
                Mail::to($user->email)->send(new BookingConfirmedMail($booking));
            }

            $propertyOwner = $booking->property->owner;
            $propertyOwner->notify(new \App\Notifications\NewBookingNotification($booking));

            Log::channel('audit')->info('real_estate.booking.confirmation_notification.success', [
                'booking_id' => $booking->id,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->critical('real_estate.booking.confirmation_notification.error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
