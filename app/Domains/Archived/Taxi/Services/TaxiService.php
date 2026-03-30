<?php declare(strict_types=1);

namespace App\Domains\Archived\Taxi\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TaxiService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**


         * Конструктор с инъекцией зависимостей (по канону).


         */


        public function __construct(


            private readonly SurgeService $surgeService,


            private readonly \App\Services\WalletService $walletService,


            private readonly \App\Services\NotificationService $notificationService,


        ) {}


        /**


         * Сценарий создания поездки (Passenger Request -> Pending Ride).


         * B2B/B2C поддержка встроена в логику tenant + fleet_id.


         */


        public function createRide(


            User $passenger,


            array $params,


            ?int $fleetId = null


        ): TaxiRide {


            // 1. Fraud Check (по канону перед любой мутацией)


            FraudControlService::check($passenger->id, 'taxi_ride_create');


            // 2. Расчет стомости с учетом Surge и Расстояния


            $surgeMultiplier = $this->surgeService->getMultiplierAtPoint(


                (float)($params['pickup_lat'] ?? 0),


                (float)($params['pickup_lon'] ?? 0),


                (int)tenant()->id


            );


            // Цены по канону 2026: всё в копейках (int)


            $basePrice = 15000; // 150 руб посадка


            $perKmPrice = 2500; // 25 руб/км


            $estimatedDistance = (float)($params['estimated_distance'] ?? 1.0);


            $totalPrice = (int)(($basePrice + ($perKmPrice * $estimatedDistance)) * $surgeMultiplier);


            return DB::transaction(function () use ($passenger, $params, $fleetId, $totalPrice, $surgeMultiplier) {


                $correlationId = (string)Str::uuid();


                // 3. Создание записи в БД (Layer 1-2 integration)


                $ride = TaxiRide::create([


                    'uuid' => (string)Str::uuid(),


                    'tenant_id' => tenant()->id,


                    'passenger_id' => $passenger->id,


                    'fleet_id' => $fleetId,


                    'status' => 'pending',


                    'pickup_address' => $params['pickup_address'],


                    'pickup_lat' => (string)$params['pickup_lat'],


                    'pickup_lon' => (string)$params['pickup_lon'],


                    'dropoff_address' => $params['dropoff_address'],


                    'dropoff_lat' => (string)$params['dropoff_lat'],


                    'dropoff_lon' => (string)$params['dropoff_lon'],


                    'price' => $totalPrice,


                    'commission' => (int)($totalPrice * 0.15), // Платформа берет 15% по умолчанию


                    'surge_multiplier' => $surgeMultiplier,


                    'correlation_id' => $correlationId,


                    'metadata' => [


                        'source' => $params['source'] ?? 'api_ios',


                        'estimated_minutes' => $params['estimated_minutes'] ?? 10,


                        'passenger_phone' => $passenger->phone ?? 'n/a'


                    ],


                    'tags' => ['b2c', 'taxi', 'on_demand']


                ]);


                // 4. Audit Log (Log::channel('audit') по канону)


                Log::channel('audit')->info('Taxi ride created', [


                    'ride_uuid' => $ride->uuid,


                    'passenger_id' => $passenger->id,


                    'total_price' => $totalPrice,


                    'correlation_id' => $correlationId


                ]);


                // 5. Поиск и уведомление ближайших водителей


                $this->notificationService->notifyNearestDrivers($ride);


                return $ride;


            });


        }


        /**


         * Сценарий принятия поездки (Driver Acceptance).


         * Обязательный Optimistic Locking (lockForUpdate).


         */


        public function acceptRide(int $driverId, int $rideId): bool


        {


            return DB::transaction(function () use ($driverId, $rideId) {


                $ride = TaxiRide::where('id', $rideId)


                    ->where('status', 'pending')


                    ->lockForUpdate() // Предотвращаем Race Condition (Double Acceptance)


                    ->first();


                if (!$ride) {


                    return false; // Поездка уже взята другим водителем


                }


                $driver = Driver::findOrFail($driverId);


                $vehicle = $driver->vehicles()->where('is_active', true)->first();


                if (!$vehicle) {


                    throw new \Exception('Driver has no active vehicle and cannot accept ride');


                }


                $ride->update([


                    'driver_id' => $driverId,


                    'vehicle_id' => $vehicle->id,


                    'status' => 'accepted',


                    'accepted_at' => now()


                ]);


                // Переключаем статус водителя на "Busy"


                $driver->update(['status' => 'busy']);


                Log::channel('audit')->info('Taxi ride accepted', [


                    'ride_id' => $rideId,


                    'driver_id' => $driverId,


                    'vehicle_id' => $vehicle->id,


                    'correlation_id' => $ride->correlation_id


                ]);


                return true;


            });


        }


        /**


         * Завершение поездки (Ride Completion & Settlement).


         * Комплексные финансовые расчеты в транзакции.


         */


        public function completeRide(int $rideId): void


        {


            DB::transaction(function () use ($rideId) {


                $ride = TaxiRide::where('id', $rideId)


                    ->lockForUpdate()


                    ->firstOrFail();


                if ($ride->status !== 'in_progress') {


                    throw new \Exception('Cannot complete ride that is not in progress');


                }


                $ride->update([


                    'status' => 'completed',


                    'completed_at' => now()


                ]);


                // Начисления (по канону 2026: только через WalletService)


                $driverReward = $ride->price - $ride->commission;


                // 1. Прямая выплата водителю на Wallet


                $this->walletService->credit($ride->driver->wallet(), $driverReward, 'taxi_ride_income', [


                    'ride_uuid' => $ride->uuid,


                    'correlation_id' => $ride->correlation_id


                ]);


                // 2. Начисление комиссии Платформе (Tenant Wallet)


                $this->walletService->credit(tenant()->wallet(), $ride->commission, 'taxi_ride_platform_commission', [


                    'ride_uuid' => $ride->uuid,


                    'correlation_id' => $ride->correlation_id


                ]);


                // 3. Выплата Автопарку (если B2B контракт)


                if ($ride->fleet_id && $ride->fleet) {


                    // Дополнительная доля автопарка (например 5% от Грязной суммы)


                    $fleetShare = (int)($ride->price * 0.05);


                    $this->walletService->credit($ride->fleet->wallet(), $fleetShare, 'fleet_ride_share', [


                        'ride_uuid' => $ride->uuid,


                        'correlation_id' => $ride->correlation_id


                    ]);


                }


                // 4. Возвращаем водителя в статус "Active"


                $ride->driver->update(['status' => 'active']);


                Log::channel('audit')->info('Taxi ride completed successfully', [


                    'ride_uuid' => $ride->uuid,


                    'total_price' => $ride->price,


                    'driver_earned' => $driverReward,


                    'correlation_id' => $ride->correlation_id


                ]);


            });


        }


        /**


         * Поиск ближайших активных водителей (Radius Logic).


         */


        public function findAvailableDrivers(float $lat, float $lon, float $radiusKm = 10.0): Collection


        {


            return Driver::where('tenant_id', tenant()->id)


                ->where('status', 'active')


                ->where('is_online', true)


                ->get()


                ->filter(fn($driver) => $driver->calculateDistanceTo($lat, $lon) <= $radiusKm);


        }
}
