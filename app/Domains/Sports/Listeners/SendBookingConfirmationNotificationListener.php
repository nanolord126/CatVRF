<?php

declare(strict_types=1);

namespace App\Domains\Sports\Listeners;

use App\Domains\Sports\Events\BookingConfirmedEvent;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Domains\Sports\Notifications\BookingConfirmedNotification;

final class SendBookingConfirmationNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private AuditService $audit,
    ) {}

    public function handle(BookingConfirmedEvent $event): void
    {
        Log::channel('notifications')->info('Sending booking confirmation notification', [
            'booking_id' => $event->bookingId,
            'user_id' => $event->userId,
            'correlation_id' => $event->correlationId,
        ]);

        $user = \App\Models\User::find($event->userId);
        
        if ($user !== null) {
            Notification::send($user, new BookingConfirmedNotification(
                bookingId: $event->bookingId,
                venueId: $event->venueId,
                slotStart: $event->slotStart,
                slotEnd: $event->slotEnd,
                bookingType: $event->bookingType,
            ));
        }

        $this->audit->record(
            'booking_confirmation_notification_sent',
            'sports_booking',
            $event->bookingId,
            [],
            [
                'user_id' => $event->userId,
                'venue_id' => $event->venueId,
                'correlation_id' => $event->correlationId,
            ],
            $event->correlationId
        );
    }

    public function failed(BookingConfirmedEvent $event, \Throwable $exception): void
    {
        Log::channel('notifications')->error('Failed to send booking confirmation notification', [
            'booking_id' => $event->bookingId,
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}
