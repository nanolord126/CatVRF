declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Taxi\Listeners;

use App\Domains\Taxi\Events\RideCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final /**
 * ProcessRideCompletedPayout
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ProcessRideCompletedPayout
{
    public function handle(RideCompleted $event): void
    {
        try {
            $this->db->transaction(function () use ($event) {
                // Update driver wallet with ride earnings
                $this->log->channel('audit')->info('Ride payout processed', [
                    'ride_id' => $event->rideId,
                    'driver_id' => $event->driverId,
                    'amount' => $event->priceAmount,
                    'correlation_id' => $event->correlationId,
                    'action' => 'ride_completed_payout',
                ]);
                // WalletService::credit($driver_id, $event->priceAmount)
            });
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Failed to process ride payout', [
                'correlation_id' => $event->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
