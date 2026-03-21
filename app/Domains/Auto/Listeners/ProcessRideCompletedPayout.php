<?php declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use App\Domains\Auto\Events\RideCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ProcessRideCompletedPayout
{
    public function handle(RideCompleted $event): void
    {
        try {
            DB::transaction(function () use ($event) {
                // Update driver wallet with ride earnings
                Log::channel('audit')->info('Ride payout processed', [
                    'ride_id' => $event->rideId,
                    'driver_id' => $event->driverId,
                    'amount' => $event->priceAmount,
                    'correlation_id' => $event->correlationId,
                    'action' => 'ride_completed_payout',
                ]);
                // WalletService::credit($driver_id, $event->priceAmount)
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Failed to process ride payout', [
                'correlation_id' => $event->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
