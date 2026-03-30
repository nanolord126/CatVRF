<?php declare(strict_types=1);

namespace App\Domains\Archived\Taxi\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RideReminderJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;


        use InteractsWithQueue;


        use Queueable;


        use SerializesModels;


        public function __construct(


            private TaxiRide $ride,


            private string $correlationId = '',


        ) {


            $this->onQueue('notifications');


        }


        public function handle(): void


        {


            try {


                Log::channel('audit')->info('Ride reminder job started', [


                    'ride_id' => $this->ride->id,


                    'correlation_id' => $this->correlationId,


                ]);


                // Проверить, что поездка ещё в статусе waiting


                $ride = TaxiRide::query()->find($this->ride->id);


                if (!$ride || $ride->status !== 'waiting') {


                    Log::channel('audit')->notice('Ride not in waiting status, skipping reminder', [


                        'ride_id' => $this->ride->id,


                        'status' => $ride?->status,


                    ]);


                    return;


                }


                // Notification::send($ride->passenger, new RideReminderNotification($ride));


                Log::channel('audit')->info('Ride reminder sent', [


                    'ride_id' => $ride->id,


                    'passenger_id' => $ride->passenger_id,


                    'correlation_id' => $this->correlationId,


                ]);


            } catch (\Throwable $e) {


                Log::channel('audit')->error('Ride reminder job failed', [


                    'ride_id' => $this->ride->id,


                    'error' => $e->getMessage(),


                    'trace' => $e->getTraceAsString(),


                    'correlation_id' => $this->correlationId,


                ]);


                throw $e;


            }


        }


        public function retryUntil(): Carbon


        {


            return now()->addHours(1);


        }
}
