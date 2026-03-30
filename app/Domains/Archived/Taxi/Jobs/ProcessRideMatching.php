<?php declare(strict_types=1);

namespace App\Domains\Archived\Taxi\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProcessRideMatching extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


        /**


         * Конструктор с инъекцией (по канону 2026).


         */


        public function __construct(


            private readonly TaxiRide $ride,


            private readonly string $correlationId


        ) {}


        /**


         * Масштабируемая обработка поиска водителя.


         */


        public function handle(TaxiService $taxiService): void


        {


            Log::channel('audit')->info('Processing ride matching for ride', [


                'ride_id' => $this->ride->id,


                'correlation_id' => $this->correlationId


            ]);


            // 1. Поиск ближайших водителей (Radius Logic)


            $drivers = $taxiService->findAvailableDrivers(


                (float)$this->ride->pickup_lat,


                (float)$this->ride->pickup_lon,


                5.0 // 5 км начальный радиус


            );


            if ($drivers->isEmpty()) {


                // Расширяем радиус до 10 км (эмуляция рекурсивного поиска)


                $drivers = $taxiService->findAvailableDrivers(


                    (float)$this->ride->pickup_lat,


                    (float)$this->ride->pickup_lon,


                    10.0


                );


            }


            // 2. Отправка нотификаций (Layer 9 integration)


            foreach ($drivers as $driver) {


                $driver->notify(new \App\Domains\Archived\Taxi\Notifications\RideCreatedNotification($this->ride, $this->correlationId));


            }


            // 3. Отмена поездки, если водитель не найден за 5 минут


            if ($this->ride->created_at->addMinutes(5)->isPast()) {


                $this->ride->update(['status' => 'cancelled', 'reason' => 'No drivers found']);


                Log::channel('audit')->warning('Ride auto-cancelled: No drivers found', ['ride_uuid' => $this->ride->uuid]);


            }


        }
}
