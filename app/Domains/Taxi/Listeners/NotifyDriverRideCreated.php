declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Taxi\Listeners;

use App\Domains\Taxi\Events\RideCreated;
use Illuminate\Support\Facades\Log;

final /**
 * NotifyDriverRideCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class NotifyDriverRideCreated
{
    public function handle(RideCreated $event): void
    {
        try {
            $this->log->channel('audit')->info('Driver notified of new ride', [
                'ride_id' => $event->rideId,
                'driver_id' => $event->driverId,
                'correlation_id' => $event->correlationId,
                'action' => 'ride_created_driver_notification',
            ]);
            // Notification::send($driver, new RideAssignedNotification($event));
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Failed to notify driver', [
                'correlation_id' => $event->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
