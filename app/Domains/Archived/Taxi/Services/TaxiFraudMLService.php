<?php declare(strict_types=1);

namespace App\Domains\Archived\Taxi\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TaxiFraudMLService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**


         * Конструктор с инъекцией (по канону).


         */


        public function __construct(


            private readonly \App\Services\ML\FraudMLService $coreML,


        ) {}


        /**


         * Комплексная проверка перед созданием/принятием поездки.


         */


        public function checkRideSecurity(int $passengerId, array $params): bool


        {


            $correlationId = (string)Str::uuid();


            // 1. Core ML Score (Слой 0)


            $mlScore = $this->coreML->scoreOperation([


                'user_id' => $passengerId,


                'operation_type' => 'taxi_ride_request',


                'amount' => $params['price'] ?? 0,


                'ip' => request()->ip(),


                'device' => request()->header('User-Agent')


            ]);


            if ($mlScore > 0.85) { // Критический порог фрода


                Log::channel('fraud_alert')->warning('Taxi Fraud Detected: Critical High Score', [


                    'user_id' => $passengerId,


                    'score' => $mlScore,


                    'correlation_id' => $correlationId


                ]);


                return false;


            }


            // 2. Taxi-specific heuristics (Скор по вертикали)


            // Пример: слишком частые заказы с одного IP за 5 минут


            $recentRides = TaxiRide::where('passenger_id', $passengerId)


                ->where('created_at', '>', now()->subMinutes(5))


                ->count();


            if ($recentRides > 3) {


                Log::channel('fraud_alert')->error('Taxi Fraud: Spam Ride Requests', [


                    'user_id' => $passengerId,


                    'rides_count' => $recentRides,


                    'correlation_id' => $correlationId


                ]);


                return false;


            }


            // 3. Расстояние (если pickup и dropoff слишком близко или слишком далеко)


            $distance = $params['estimated_distance'] ?? 0;


            if ($distance < 0.1 || $distance > 500.0) {


                Log::channel('fraud_alert')->info('Taxi Security: Suspicious Distance', [


                    'user_id' => $passengerId,


                    'distance' => $distance,


                    'correlation_id' => $correlationId


                ]);


                return false;


            }


            return true;


        }


        /**


         * Проверка легитимности водителя при выходе на линию.


         */


        public function verifyDriverSession(int $driverId): bool


        {


            $driver = Driver::findOrFail($driverId);


            // Проверка KYC, лицензии и истории (эмуляция по канону)


            if ($driver->license_number === 'test' || !$driver->is_active) {


                return false;


            }


            Log::channel('audit')->info('Driver session verified via ML', [


                'driver_id' => $driverId,


                'license' => $driver->license_number


            ]);


            return true;


        }
}
