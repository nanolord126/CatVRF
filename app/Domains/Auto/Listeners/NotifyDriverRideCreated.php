<?php declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use App\Domains\Auto\Events\RideCreated;
use Illuminate\Support\Facades\Log;

final class NotifyDriverRideCreated
{
    public function handle(RideCreated $event): void
    {
        try {
            Log::channel('audit')->info('Driver notified of new ride', [
                'ride_id' => $event->rideId,
                'driver_id' => $event->driverId,
                'correlation_id' => $event->correlationId,
                'action' => 'ride_created_driver_notification',
            ]);
            // Notification::send($driver, new RideAssignedNotification($event));
        } catch (\Exception $e) {
            Log::channel('audit')->error('Failed to notify driver', [
                'correlation_id' => $event->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
