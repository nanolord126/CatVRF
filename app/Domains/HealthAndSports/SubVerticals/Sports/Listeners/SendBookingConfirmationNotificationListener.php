<?php

declare(strict_types=1);

namespace App\Domains\Sports\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Sports\Events\BookingConfirmedEvent;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Log\LogManager;
use Illuminate\Notifications\ChannelManager;
use App\Domains\Sports\Notifications\BookingConfirmedNotification;
use App\Models\User;

final class SendBookingConfirmationNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly LogManager $log,
        private readonly ChannelManager $notification,) {}

    public function handle(BookingConfirmedEvent $event): void
    {
        $this->log->channel('notifications')->$this->logger->info('Sending booking confirmation notification', [
            'booking_id' => $event->bookingId,
            'user_id' => $event->userId,
            'correlation_id' => $event->correlationId,
        ]);

        $user = User::find($event->userId);

        if ($user !== null) {
            $this->notification->send($user, new BookingConfirmedNotification(
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
        $this->log->channel('notifications')->error('Failed to send booking confirmation notification', [
            'booking_id' => $event->bookingId,
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}
