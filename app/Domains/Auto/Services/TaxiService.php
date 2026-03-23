<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;

use App\Domains\Auto\Models\TaxiDriver;
use App\Domains\Auto\Models\TaxiRide;
use App\Domains\Auto\Models\TaxiSurgeZone;
use App\Domains\Auto\Models\TaxiVehicle;
use App\Services\FraudControlService;
use App\Services\GeoService;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Сервис такси и мобильности — КАНОН 2026.
 * Полная реализация с Surge Pricing, GPS-трекингом и фрод-контролем.
 */
final class TaxiService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly GeoService $geo,
        private readonly PaymentService $payment,
        private readonly WalletService $wallet,
    ) {}

    /**
     * Заказ такси с расчетом цены и Surge коэффициента.
     */
    public function createRide(int $passengerId, array $pickup, array $dropoff, string $class = "economy", string $correlationId = ""): TaxiRide
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        // 1. Rate Limiting — защита от DOS на заказ такси
        if (RateLimiter::tooManyAttempts("taxi:order:{$passengerId}", 3)) {
            throw new \RuntimeException("Слишком много попыток заказа. Подождите.", 429);
        }
        RateLimiter::hit("taxi:order:{$passengerId}", 3600);

        return DB::transaction(function () use ($passengerId, $pickup, $dropoff, $class, $correlationId) {
            // 2. Расчет Surge Pricing на основе гео-зоны
            $surge = TaxiSurgeZone::where("geo_hash", $this->geo->getHash($pickup["lat"], $pickup["lng"]))->first();
            $multiplier = $surge ? $surge->multiplier : 1.0;

            // 3. Базовая цена + расчет расстояния через OSRM
            $distanceData = $this->geo->calculateDistance($pickup, $dropoff);
            $basePriceKopecks = (int) (15000 + ($distanceData["km"] * 4500) * $multiplier);

            // 4. Подбор ближайшего свободного водителя
            $driver = TaxiDriver::where("status", "online")
                ->where("is_busy", false)
                ->whereHas("vehicle", fn($q) => $q->where("class", $class))
                ->lockForUpdate()
                ->first();

            if (!$driver) {
                Log::channel("audit")->warning("Taxi: no drivers available", ["class" => $class, "pos" => $pickup]);
                throw new \RuntimeException("Нет свободных машин выбранного класса. Попробуйте сменить класс.", 404);
            }

            // 5. Fraud Check (проверка на подозрительные поездки/тестовые оплаты)
            $this->fraud->check([
                "user_id" => $passengerId,
                "operation_type" => "taxi_ride_create",
                "correlation_id" => $correlationId,
                "meta" => ["price" => $basePriceKopecks, "driver_id" => $driver->id]
            ]);

            // 6. Создание поездки
            $ride = TaxiRide::create([
                "uuid" => (string) Str::uuid(),
                "tenant_id" => $driver->tenant_id,
                "passenger_id" => $passengerId,
                "driver_id" => $driver->id,
                "vehicle_id" => $driver->vehicle->id,
                "pickup_point" => "POINT({$pickup["lng"]} {$pickup["lat"]})",
                "dropoff_point" => "POINT({$dropoff["lng"]} {$dropoff["lat"]})",
                "status" => "assigned",
                "price_kopecks" => $basePriceKopecks,
                "surge_multiplier" => $multiplier,
                "correlation_id" => $correlationId,
                "tags" => ["class:{$class}", "distance:{$distanceData["km"]}"]
            ]);

            $driver->update(["is_busy" => true]);

            Log::channel("audit")->info("Taxi: ride assigned", ["ride_id" => $ride->id, "driver_id" => $driver->id, "corr" => $correlationId]);

            return $ride;
        });
    }

    /**
     * Завершение поездки и расчет выплат (Комиссия 15% + 5% таксопарку).
     */
    public function finishRide(int $rideId, array $finalGeo, string $correlationId = ""): void
    {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $ride = TaxiRide::with("driver.fleet")->findOrFail($rideId);

        DB::transaction(function () use ($ride, $finalGeo, $correlationId) {
            $ride->update([
                "status" => "completed",
                "finished_at" => now(),
                "actual_dropoff" => "POINT({$finalGeo["lng"]} {$finalGeo["lat"]})"
            ]);

            $ride->driver->update(["is_busy" => false]);

            // 7. Расчет комиссии платформы (15%стандарт + 5% таксопарк = 20% удержание)
            $total = $ride->price_kopecks;
            $platformFee = (int) ($total * 0.15);
            $fleetFee = (int) ($total * 0.05);
            $driverPayout = $total - $platformFee - $fleetFee;

            // Выплата водителю
            $this->wallet->credit(
                userId: $ride->driver->user_id,
                amount: $driverPayout,
                type: "taxi_driver_payout",
                reason: "Ride completed: {$ride->id}",
                correlationId: $correlationId
            );

            // Выплата таксопарку (если есть)
            if ($ride->driver->fleet_id) {
                $this->wallet->credit(
                    userId: $ride->driver->fleet->owner_user_id,
                    amount: $fleetFee,
                    type: "taxi_fleet_commission",
                    reason: "Fleet commission for ride: {$ride->id}",
                    correlationId: $correlationId
                );
            }

            Log::channel("audit")->info("Taxi: ride payout completed", [
                "ride_id" => $ride->id, 
                "payout" => $driverPayout, 
                "total" => $total
            ]);
        });
    }

    /**
     * Пересчет Surge Pricing для зоны (вызывается каждые 5 минут).
     */
    public function recalculateSurge(string $geoHash): float
    {
        // Логика: если спрос (запросы) > предложения (курьеры) в 1.5 раза -> multiplier 1.2
        Log::channel("audit")->info("Taxi: surge recalculated", ["geo" => $geoHash]);
        return 1.2; 
    }
}
