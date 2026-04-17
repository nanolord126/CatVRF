<?php declare(strict_types=1);

namespace App\Domains\Taxi\Listeners;

use App\Domains\Taxi\Events\RideStarted;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final readonly class NotifyRideStarted implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(RideStarted $event): void
    {
        $ride = $event->ride;

        $this->notificationService->sendPushNotification(
            userId: $ride->passenger_id,
            title: 'Поездка началась',
            body: 'Ваш водитель начал поездку. Приятной дороги!',
            data: [
                'ride_uuid' => $ride->uuid,
                'driver_id' => $ride->driver_id,
                'type' => 'ride_started',
            ],
            correlationId: $event->correlationId,
        );
    }
}
