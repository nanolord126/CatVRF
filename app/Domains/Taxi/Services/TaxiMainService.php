<?php

namespace App\Domains\Taxi\Services;

use App\Domains\Taxi\Models\TaxiRide;
use App\Domains\Taxi\Models\TaxiDriver;
use App\Domains\Taxi\Models\TaxiVehicle;
use App\Domains\Taxi\Models\TaxiShift;
use App\Domains\Taxi\Models\TaxiSurgeZone;
use App\Domains\Taxi\Models\TaxiRideStatusLog;
use App\Models\AuditLog;
use App\Services\Taxi\TaxiAIPricingService;
use App\Services\Common\Security\AIAnomalyDetector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Основной сервис управления поездками Taxi домена
 * Обрабатывает весь цикл жизни поездки: создание, поиск водителя, завершение
 */
class TaxiMainService
{
    protected AIAnomalyDetector $detector;
    protected TaxiAIPricingService $pricing;

    public function __construct(AIAnomalyDetector $detector, TaxiAIPricingService $pricing)
    {
        $this->detector = $detector;
        $this->pricing = $pricing;
    }

    /**
     * Создание новой поездки: расчёт стоимости, проверка безопасности, поиск водителя
     * 
     * @param int $customerId
     * @param array $pickup ['lat' => float, 'lng' => float, 'address' => string]
     * @param array $destination ['lat' => float, 'lng' => float, 'address' => string]
     * @param string $vehicleClass economy|comfort|premium|xl
     * @return array ['success' => bool, 'ride_id' => int|null, 'estimated_price' => float, 'error' => string|null]
     */
    public function createRide(int $customerId, array $pickup, array $destination, string $vehicleClass = 'comfort'): array
    {
        return DB::transaction(function () use ($customerId, $pickup, $destination, $vehicleClass) {
            try {
                // 1. Расчёт расстояния между точками (формула Хаверсина)
                $distance = $this->calculateDistance($pickup, $destination);
                if ($distance <= 0) {
                    throw new \Exception("Invalid pickup/destination coordinates");
                }

                // 2. Расчёт базовой стоимости через AI Pricing сервис
                $priceData = $this->pricing->calculate(
                    $distance,
                    $vehicleClass,
                    $pickup['lat'],
                    $pickup['lng']
                );

                // 3. Поиск surge зон, применение динамического коэффициента
                $surgeZone = TaxiSurgeZone::nearPoint($pickup['lat'], $pickup['lng'])->active()->first();
                $surgeMultiplier = $surgeZone?->multiplier ?? 1.0;

                // 4. Проверка безопасности: анализ аномалий поведения клиента
                $riskScore = $this->detector->analyze(tenant(), $customerId, 'taxi_ride_request', [
                    'pickup' => $pickup,
                    'destination' => $destination,
                    'distance_km' => $distance,
                    'estimated_amount' => $priceData['amount'],
                    'surge_multiplier' => $surgeMultiplier
                ]);

                if ($riskScore >= 80) {
                    throw new \Exception("Request blocked by security system (Risk Score: {$riskScore})");
                }

                // 5. Создание записи поездки
                $estimatedPrice = round($priceData['amount'] * $surgeMultiplier, 2);

                $ride = TaxiRide::create([
                    'customer_id' => $customerId,
                    'tenant_id' => tenant()->id,
                    'pickup_address' => $pickup['address'] ?? '',
                    'pickup_coords' => $pickup,
                    'destination_address' => $destination['address'] ?? '',
                    'destination_coords' => $destination,
                    'distance_km' => $distance,
                    'estimated_price' => $estimatedPrice,
                    'surge_multiplier' => $surgeMultiplier,
                    'status' => 'searching',
                    'vehicle_class' => $vehicleClass,
                ]);

                // 6. Логирование статуса
                TaxiRideStatusLog::recordStatus($ride, 'searching', [
                    'distance' => $distance,
                    'estimated_price' => $estimatedPrice,
                    'surge_zone_id' => $surgeZone?->id,
                    'risk_score' => $riskScore
                ]);

                // 7. Audit Log
                AuditLog::create([
                    'user_id' => $customerId,
                    'tenant_id' => tenant()->id,
                    'action' => 'ride_created',
                    'model' => TaxiRide::class,
                    'model_id' => $ride->id,
                    'changes' => [
                        'distance_km' => [null, $distance],
                        'estimated_price' => [null, $estimatedPrice],
                        'surge_multiplier' => [null, $surgeMultiplier]
                    ],
                    'correlation_id' => request()?->header('X-Correlation-ID'),
                    'ip_address' => request()?->ip()
                ]);

                Log::channel('taxi')->info('Ride created successfully', [
                    'ride_id' => $ride->id,
                    'customer_id' => $customerId,
                    'distance_km' => $distance,
                    'estimated_price' => $estimatedPrice,
                    'vehicle_class' => $vehicleClass
                ]);

                return [
                    'success' => true,
                    'ride_id' => $ride->id,
                    'estimated_price' => $estimatedPrice,
                    'distance_km' => $distance,
                    'surge_multiplier' => $surgeMultiplier
                ];
            } catch (\Exception $e) {
                Log::channel('taxi')->error('Failed to create ride', [
                    'customer_id' => $customerId,
                    'error' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'ride_id' => null,
                    'estimated_price' => 0,
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Водитель принимает поездку и начинает её
     * 
     * @param int $driverId
     * @param int $rideId
     * @param int $vehicleId
     * @return array ['success' => bool, 'started_at' => string|null, 'error' => string|null]
     */
    public function acceptAndStartRide(int $driverId, int $rideId, int $vehicleId): array
    {
        return DB::transaction(function () use ($driverId, $rideId, $vehicleId) {
            try {
                $ride = TaxiRide::findOrFail($rideId);
                $driver = TaxiDriver::findOrFail($driverId);
                $vehicle = TaxiVehicle::findOrFail($vehicleId);

                // Проверка статуса поездки
                if ($ride->status !== 'searching') {
                    throw new \Exception("Ride status is {$ride->status}, expected 'searching'");
                }

                // Проверка активности водителя и лицензии
                if (!$driver->is_active || $driver->license_expires_at?->isPast()) {
                    throw new \Exception("Driver is not active or license expired");
                }

                // Проверка статуса ТС
                if (!$vehicle->is_active || $vehicle->next_inspection_due?->isPast()) {
                    throw new \Exception("Vehicle is not active or inspection expired");
                }

                // Получение или создание текущей смены водителя
                $shift = $driver->shifts()->where('is_active', true)->first();
                if (!$shift) {
                    $shift = TaxiShift::create([
                        'driver_id' => $driverId,
                        'tenant_id' => tenant()->id,
                        'started_at' => now(),
                        'total_earnings' => 0,
                        'rides_count' => 0,
                        'is_active' => true
                    ]);
                }

                // Обновляем поездку: назначаем водителя и ТС, начинаем
                $ride->update([
                    'driver_id' => $driverId,
                    'vehicle_id' => $vehicleId,
                    'shift_id' => $shift->id,
                    'status' => 'accepted',
                    'started_at' => now()
                ]);

                // Обновляем статус водителя
                $driver->update([
                    'current_vehicle_id' => $vehicleId,
                    'last_online_at' => now()
                ]);

                // Логирование
                TaxiRideStatusLog::recordStatus($ride, 'accepted', [
                    'driver_id' => $driverId,
                    'vehicle_id' => $vehicleId,
                    'shift_id' => $shift->id
                ]);

                Log::channel('taxi')->info('Ride accepted and started', [
                    'ride_id' => $rideId,
                    'driver_id' => $driverId,
                    'vehicle_id' => $vehicleId
                ]);

                AuditLog::create([
                    'user_id' => $driverId,
                    'tenant_id' => tenant()->id,
                    'action' => 'ride_accepted',
                    'model' => TaxiRide::class,
                    'model_id' => $ride->id,
                    'changes' => [
                        'driver_id' => [null, $driverId],
                        'vehicle_id' => [null, $vehicleId],
                        'status' => ['searching', 'accepted']
                    ],
                    'correlation_id' => request()?->header('X-Correlation-ID'),
                    'ip_address' => request()?->ip()
                ]);

                return [
                    'success' => true,
                    'ride_id' => $rideId,
                    'started_at' => $ride->started_at->toIso8601String(),
                    'shift_id' => $shift->id
                ];
            } catch (\Exception $e) {
                Log::channel('taxi')->error('Failed to accept and start ride', [
                    'ride_id' => $rideId,
                    'driver_id' => $driverId,
                    'error' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Завершить поездку: расчёт итоговой стоимости, комиссии, доходов водителя
     * 
     * @param int $rideId
     * @param float $actualDistance в км
     * @param int $durationMinutes продолжительность в минутах
     * @return array ['success' => bool, 'final_price' => float, 'driver_earnings' => float, 'error' => string|null]
     */
    public function completeRide(int $rideId, float $actualDistance, int $durationMinutes): array
    {
        try {
            $ride = TaxiRide::findOrFail($rideId);

            if (!$ride->complete($actualDistance, $durationMinutes)) {
                throw new \Exception("Failed to complete ride");
            }

            // Обновляем пробег ТС
            if ($ride->vehicle) {
                $ride->vehicle->updateMileage(
                    $ride->vehicle->mileage_km + $actualDistance,
                    'ride_completed'
                );
            }

            Log::channel('taxi')->info('Ride completed', [
                'ride_id' => $rideId,
                'final_price' => $ride->final_price,
                'driver_earnings' => $ride->driver_earnings
            ]);

            return [
                'success' => true,
                'ride_id' => $rideId,
                'final_price' => (float)$ride->final_price,
                'driver_earnings' => (float)$ride->driver_earnings,
                'platform_commission' => (float)$ride->platform_commission
            ];
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to complete ride', [
                'ride_id' => $rideId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Рассчитать динамическую цену для маршрута с учётом множества факторов
     * 
     * @param array $pickup
     * @param array $destination
     * @param string $vehicleClass
     * @param int $dayOfWeek день недели (1-7)
     * @return array ['base_price' => float, 'surge_multiplier' => float, 'final_price' => float]
     */
    public function calculateDynamicPrice(array $pickup, array $destination, string $vehicleClass = 'comfort', int $dayOfWeek = null): array
    {
        try {
            $dayOfWeek ??= now()->dayOfWeek;

            // 1. Расчёт базовой цены по дистанции
            $distance = $this->calculateDistance($pickup, $destination);
            $priceData = $this->pricing->calculate($distance, $vehicleClass, $pickup['lat'], $pickup['lng']);
            $basePrice = $priceData['amount'];

            // 2. Поиск surge зоны
            $surgeZone = TaxiSurgeZone::nearPoint($pickup['lat'], $pickup['lng'])->active()->first();
            $surgeMultiplier = $surgeZone?->multiplier ?? 1.0;

            // 3. Дополнительный коэффициент за время суток
            $hourOfDay = now()->hour;
            $timePeakMultiplier = match (true) {
                $hourOfDay >= 7 && $hourOfDay <= 9 => 1.20,     // Утренний пик
                $hourOfDay >= 17 && $hourOfDay <= 19 => 1.20,   // Вечерний пик
                $hourOfDay >= 22 || $hourOfDay <= 2 => 1.30,    // Ночь
                default => 1.0
            };

            // 4. Коэффициент за день недели (выходные дороже)
            $dayMultiplier = in_array($dayOfWeek, [0, 6]) ? 1.15 : 1.0;

            $finalPrice = round($basePrice * $surgeMultiplier * $timePeakMultiplier * $dayMultiplier, 2);

            return [
                'base_price' => $basePrice,
                'distance_km' => $distance,
                'surge_multiplier' => $surgeMultiplier,
                'time_peak_multiplier' => $timePeakMultiplier,
                'day_multiplier' => $dayMultiplier,
                'final_price' => $finalPrice
            ];
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to calculate dynamic price', [
                'error' => $e->getMessage()
            ]);

            return [
                'base_price' => 0,
                'final_price' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Получить статистику водителя за период
     * 
     * @param int $driverId
     * @param Carbon|null $from дата начала (по умолчанию -30 дней)
     * @param Carbon|null $to дата окончания (по умолчанию сейчас)
     * @return array статистика: rides, earnings, rating, etc.
     */
    public function getDriverStats(int $driverId, Carbon $from = null, Carbon $to = null): array
    {
        try {
            $from ??= now()->subDays(30);
            $to ??= now();

            $driver = TaxiDriver::findOrFail($driverId);

            // Поездки за период
            $ridesData = $driver->rides()
                ->completed()
                ->forPeriod($from, $to)
                ->selectRaw('COUNT(*) as count, SUM(final_price) as total, SUM(driver_earnings) as earnings, AVG(distance_km) as avg_distance')
                ->first();

            // Смены за период
            $shiftsData = $driver->shifts()
                ->forPeriod($from, $to)
                ->selectRaw('COUNT(*) as count, SUM(total_earnings) as total_earnings, SUM(driver_profit) as total_profit')
                ->first();

            // Статистика по дням недели
            $dayOfWeekStats = $driver->rides()
                ->completed()
                ->forPeriod($from, $to)
                ->selectRaw('DAYOFWEEK(completed_at) as day_of_week, COUNT(*) as rides, SUM(driver_earnings) as earnings')
                ->groupBy('day_of_week')
                ->get()
                ->pluck('rides', 'day_of_week');

            return [
                'driver_id' => $driverId,
                'driver_name' => $driver->user?->name,
                'period_from' => $from->toDateString(),
                'period_to' => $to->toDateString(),
                'total_rides' => (int)($ridesData->count ?? 0),
                'total_earnings' => (float)($ridesData->total ?? 0),
                'total_profit' => (float)($ridesData->earnings ?? 0),
                'average_ride_distance' => (float)($ridesData->avg_distance ?? 0),
                'shifts_count' => (int)($shiftsData->count ?? 0),
                'shift_total_profit' => (float)($shiftsData->total_profit ?? 0),
                'current_rating' => $driver->rating,
                'trust_score' => $driver->getTrustScore(),
                'day_of_week_performance' => $dayOfWeekStats->toArray(),
                'status' => $driver->status,
                'experience_level' => $driver->experience_level,
                'average_price_per_km' => (float)($ridesData->total && $ridesData->avg_distance 
                    ? $ridesData->total / ($ridesData->count * $ridesData->avg_distance)
                    : 0)
            ];
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to get driver stats', [
                'driver_id' => $driverId,
                'error' => $e->getMessage()
            ]);

            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Получить финансовую статистику по поездкам и доходам платформы
     * 
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return array финансовая отчётность
     */
    public function getRevenueStats(Carbon $from = null, Carbon $to = null): array
    {
        try {
            $from ??= now()->subDays(30);
            $to ??= now();

            // Общая статистика поездок
            $completedRides = TaxiRide::completed()
                ->forPeriod($from, $to)
                ->selectRaw('COUNT(*) as rides, SUM(final_price) as total_revenue, SUM(platform_commission) as platform_commission, SUM(driver_earnings) as driver_earnings, AVG(distance_km) as avg_distance')
                ->first();

            // Статистика по классам ТС
            $byClass = TaxiRide::completed()
                ->forPeriod($from, $to)
                ->selectRaw('vehicle_class, COUNT(*) as rides, SUM(final_price) as revenue, SUM(platform_commission) as commission')
                ->groupBy('vehicle_class')
                ->get();

            // Статистика по водителям (топ)
            $topDrivers = TaxiDriver::with('rides')
                ->selectRaw('taxi_drivers.id, taxi_drivers.name, COUNT(taxi_rides.id) as rides, SUM(taxi_rides.driver_earnings) as earnings')
                ->leftJoin('taxi_rides', 'taxi_drivers.id', '=', 'taxi_rides.driver_id')
                ->where('taxi_rides.completed_at', '>=', $from)
                ->where('taxi_rides.completed_at', '<=', $to)
                ->groupBy('taxi_drivers.id')
                ->orderByDesc('earnings')
                ->limit(10)
                ->get();

            // Статистика surge зон
            $activeSurgeZones = TaxiSurgeZone::active()->count();
            $surge_impact = TaxiRide::completed()
                ->forPeriod($from, $to)
                ->where('surge_multiplier', '>', 1.0)
                ->selectRaw('COUNT(*) as surge_rides, SUM(final_price - estimated_price) as surge_revenue')
                ->first();

            return [
                'period_from' => $from->toDateString(),
                'period_to' => $to->toDateString(),
                'total_rides' => (int)($completedRides->rides ?? 0),
                'total_revenue' => (float)($completedRides->total_revenue ?? 0),
                'platform_commission' => (float)($completedRides->platform_commission ?? 0),
                'driver_earnings_total' => (float)($completedRides->driver_earnings ?? 0),
                'average_ride_distance' => (float)($completedRides->avg_distance ?? 0),
                'commission_percentage' => 15, // 15% платформе
                'revenue_by_class' => $byClass->map(fn($r) => [
                    'class' => $r->vehicle_class,
                    'rides' => (int)$r->rides,
                    'revenue' => (float)$r->revenue,
                    'commission' => (float)$r->commission
                ])->toArray(),
                'top_drivers' => $topDrivers->map(fn($d) => [
                    'driver_id' => $d->id,
                    'rides' => (int)($d->rides ?? 0),
                    'earnings' => (float)($d->earnings ?? 0)
                ])->toArray(),
                'active_surge_zones' => $activeSurgeZones,
                'surge_impact' => [
                    'surge_rides' => (int)($surge_impact->surge_rides ?? 0),
                    'additional_revenue' => (float)($surge_impact->surge_revenue ?? 0)
                ]
            ];
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to get revenue stats', [
                'error' => $e->getMessage()
            ]);

            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Расчёт расстояния между двумя точками (формула Хаверсина)
     */
    private function calculateDistance(array $point1, array $point2): float
    {
        $earthRadius = 6371; // км

        $lat1 = deg2rad($point1['lat'] ?? $point1['latitude'] ?? 0);
        $lng1 = deg2rad($point1['lng'] ?? $point1['longitude'] ?? 0);
        $lat2 = deg2rad($point2['lat'] ?? $point2['latitude'] ?? 0);
        $lng2 = deg2rad($point2['lng'] ?? $point2['longitude'] ?? 0);

        $dLat = $lat2 - $lat1;
        $dLng = $lng2 - $lng1;

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos($lat1) * cos($lat2) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }
}
