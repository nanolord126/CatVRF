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

        // 2. Гео-поиск водителя (Учет класса авто и статуса)
        // В 2026: ST_DistanceSphere(last_location, ST_GeomFromText('POINT(lat lng)')) < 5000
        $nearestDriver = TaxiDriver::where('status', 'available')
             ->whereHas('vehicle', fn($q) => $q->where('class', $ride->vehicle_class))
             ->orderBy(DB::raw('RAND()')) // Заглушка для реального гео-расстояния
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
