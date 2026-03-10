<?php

namespace App\Domains\Taxi\Services;

use App\Domains\Taxi\Models\TaxiRide;
use App\Domains\Taxi\Models\TaxiDriver;
use App\Domains\Taxi\Models\TaxiVehicle;
use Illuminate\Support\Facades\DB;
use App\Services\Taxi\TaxiAIPricingService;
use App\Services\Common\Security\AIAnomalyDetector;
use Carbon\Carbon;

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
     * Полноценный запуск заказа Такси (End-to-End)
     */
    public function createRide(int $customerId, array $pickup, array $destination, string $class): TaxiRide
    {
        return DB::transaction(function () use ($customerId, $pickup, $destination, $class) {
            // 1. Расчет стоимости через AI Pricing
            $priceData = $this->pricing->calculate(
                $this->calculateDistance($pickup, $destination),
                $class,
                $pickup['lat'],
                $pickup['lng']
            );

            // 2. Fraud Check (Аномальное поведение заказчика)
            $risk = $this->detector->analyze(tenant(), $customerId, 'taxi_ride_request', [
                'pickup' => $pickup,
                'amount' => $priceData['amount']
            ]);

            if ($risk >= 80) {
                throw new \Exception("Action blocked by Security System (Risk: $risk).");
            }

            // 3. Создание записи заказа
            $ride = TaxiRide::create([
                'customer_id' => $customerId,
                'pickup_address' => $pickup['address'],
                'pickup_coords' => json_encode($pickup),
                'destination_address' => $destination['address'],
                'destination_coords' => json_encode($destination),
                'estimated_price' => $priceData['amount'],
                'surge_multiplier' => $priceData['surge'],
                'status' => 'searching',
                'vehicle_class' => $class,
            ]);

            // 4. Логирование статуса
            $ride->statusLogs()->create(['status' => 'searching', 'meta' => $priceData]);

            return $ride;
        });
    }

    private function calculateDistance(array $point1, array $point2): float
    {
        // В 2026: ST_DistanceSphere или интеграция с Google/OSM
        return 5.4; // Заглушка для демонстрации
    }
}
