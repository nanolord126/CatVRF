<?php declare(strict_types=1);

namespace App\Domains\Taxi\Listeners;

use App\Domains\Taxi\Events\DriverAssigned;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final readonly class NotifyPassengerDriverAssigned implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(DriverAssigned $event): void
    {
        $ride = $event->ride;
        $driver = $event->driver;

        $this->notificationService->sendPushNotification(
            userId: $ride->passenger_id,
            title: 'Водитель найден',
            body: "{$driver->name} едет к вам. Ожидаемое время прибытия: {$ride->predicted_eta} мин",
            data: [
                'ride_uuid' => $ride->uuid,
                'driver_id' => $driver->id,
                'driver_name' => $driver->name,
                'driver_photo' => $driver->photo_url,
                'predicted_eta' => $ride->predicted_eta,
                'type' => 'driver_assigned',
            ],
            correlationId: $event->correlationId,
        );
    }
}
