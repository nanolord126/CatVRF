<?php
declare(strict_types=1);

namespace App\Domains\Taxi\Services;

use App\Domains\Taxi\Models\Driver;
use App\Domains\Taxi\Models\Ride;
use App\Domains\Taxi\DTOs\OrderRideDto;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;

final readonly class TaxiBookingService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $log
    ) {}

    public function createRide(OrderRideDto $dto): Ride
    {
        $this->fraud->check([
            "action" => "taxi_order",
            "customer_id" => $dto->customerId,
            "pickup_lat" => $dto->pickupLat,
            "pickup_lon" => $dto->pickupLon,
            "correlation_id" => $dto->correlationId,
        ]);

        return $this->db->transaction(function () use ($dto): Ride {
            $driver = $this->findNearestAvailableDriver($dto->pickupLat, $dto->pickupLon);
            
            if (!$driver) {
                throw new \RuntimeException("No drivers available nearby", 404);
            }

            $distance = $this->calculateDistance($dto->pickupLat, $dto->pickupLon, $dto->dropoffLat, $dto->dropoffLon);
            $price = $this->calculatePrice($distance, $dto->vehicleClass);

            $ride = Ride::create([
                "driver_id" => $driver->id,
                "customer_id" => $dto->customerId,
                "pickup_lat" => $dto->pickupLat,
                "pickup_lon" => $dto->pickupLon,
                "pickup_address" => $dto->pickupAddress,
                "dropoff_lat" => $dto->dropoffLat,
                "dropoff_lon" => $dto->dropoffLon,
                "dropoff_address" => $dto->dropoffAddress,
                "status" => "pending",
                "price" => $price,
                "distance_km" => $distance,
                "correlation_id" => $dto->correlationId,
            ]);

            $driver->update(["is_available" => false]);

            $this->audit->log(
                action: "ride_created",
                subjectType: Ride::class,
                subjectId: $ride->id,
                old: [],
                new: $ride->toArray(),
                correlationId: $dto->correlationId
            );

            $this->log->channel("audit")->info("Taxi ride created successfully", [
                "ride_id" => $ride->id,
                "driver_id" => $driver->id,
                "correlation_id" => $dto->correlationId,
            ]);

            return $ride;
        });
    }

    private function findNearestAvailableDriver(float $lat, float $lon): ?Driver
    {
        return Driver::query()
            ->selectRaw("*, (6371 * acos(cos(radians(?)) * cos(radians(current_lat)) * cos(radians(current_lon) - radians(?)) + sin(radians(?)) * sin(radians(current_lat)))) AS distance", [$lat, $lon, $lat])
            ->where("is_active", true)
            ->where("is_available", true)
            ->orderBy("distance")
            ->first();
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return $miles * 1.609344;
    }

    private function calculatePrice(float $distanceKm, string $vehicleClass): float
    {
        $baseFare = 100.0;
        $perKm = match ($vehicleClass) {
            "comfort" => 30.0,
            "business" => 60.0,
            default => 20.0,
        };
        return $baseFare + ($distanceKm * $perKm);
    }
}
