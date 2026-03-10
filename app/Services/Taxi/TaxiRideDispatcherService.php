<?php

namespace App\Services\Taxi;

use App\Domains\Taxi\Models\TaxiRide;
use App\Domains\Taxi\Models\TaxiDriver;
use Illuminate\Support\{Carbon, Facades};
use Illuminate\Support\Facades\DB;
use App\Services\Common\Security\AIAnomalyDetector;
use Stancl\Tenancy\Facades\Tenancy;

class TaxiRideDispatcherService
{
    protected AIAnomalyDetector $fraudDetector;

    public function __construct(AIAnomalyDetector $detector)
    {
        $this->fraudDetector = $detector;
    }

    /**
     * Поиск водителя через ИИ-диспетчер (Гео-сегментация)
     */
    public function searchDriver(TaxiRide $ride): ?TaxiDriver
    {
        // 1. Предварительная проверка фрода (Фантомный заказ, Velocity Check)
        $tenant = Tenancy::tenant();
        $riskScore = $this->fraudDetector->analyze($tenant, $ride->customer_id, 'taxi_ride_request', [
            'ride_id' => $ride->id,
            'pickup' => $ride->pickup_address,
            'amount' => $ride->estimated_price
        ]);

        if ($riskScore >= 70) {
            $ride->update(['status' => 'cancelled']);
            throw new \Exception("Заказ заблокирован: обнаружена аномальная активность в такси-секторе.");
        }

        // 2. Гео-поиск водителя (ST_DistanceSphere для точного расчета расстояния)
        $pickupLat = $ride->pickup_latitude;
        $pickupLon = $ride->pickup_longitude;

        // Поиск доступных водителей в радиусе 5 км с нужным классом авто
        $nearestDriver = TaxiDriver::where('status', 'available')
             ->whereHas('vehicle', fn($q) => $q->where('class', $ride->vehicle_class))
             ->whereNotNull('current_latitude')
             ->whereNotNull('current_longitude')
             ->selectRaw(
                 'taxi_drivers.*,' .
                 'ST_Distance_Sphere(current_location, ST_GeomFromText(?, 4326)) as distance',
                 ["POINT($pickupLon $pickupLat)"]
             )
             ->having(DB::raw('distance'), '<', 5000) // 5 км
             ->orderBy('distance')
             ->orderBy('rating', 'desc') // Приоритет лучшим водителям
             ->first();

        if ($nearestDriver) {
            $this->assignDriver($ride, $nearestDriver);
            return $nearestDriver;
        }

        // Fallback: расширить радиус поиска до 10 км если нет водителя в 5 км
        $nearestDriver = TaxiDriver::where('status', 'available')
             ->whereHas('vehicle', fn($q) => $q->where('class', $ride->vehicle_class))
             ->whereNotNull('current_latitude')
             ->whereNotNull('current_longitude')
             ->selectRaw(
                 'taxi_drivers.*,' .
                 'ST_Distance_Sphere(current_location, ST_GeomFromText(?, 4326)) as distance',
                 ["POINT($pickupLon $pickupLat)"]
             )
             ->having(DB::raw('distance'), '<', 10000) // 10 км
             ->orderBy('distance')
             ->first();

        if ($nearestDriver) {
            $this->assignDriver($ride, $nearestDriver);
            return $nearestDriver;
        }

        return null;
    }

    private function assignDriver(TaxiRide $ride, TaxiDriver $driver): void
    {
        DB::transaction(function () use ($ride, $driver) {
            $ride->update([
                'driver_id' => $driver->id,
                'vehicle_id' => $driver->current_vehicle_id,
                'status' => 'accepted',
                'started_at' => Carbon::now(),
            ]);

            $driver->update(['status' => 'on_ride']);
        });
    }
}
