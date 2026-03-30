<?php declare(strict_types=1);

namespace App\Domains\Archived\Taxi\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProcessRideCompletedPayout extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
