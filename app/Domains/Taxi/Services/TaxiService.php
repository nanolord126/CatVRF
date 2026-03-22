<?php declare(strict_types=1);

namespace App\Domains\Taxi\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Domains\Taxi\Models\TaxiDriver;
use App\Domains\Taxi\Models\TaxiVehicle;
use App\Domains\Taxi\Models\TaxiRide;
use Illuminate\Support\Str;
use App\Services\FraudControlService;

/**
 * Taxi & Auto Service
 * CANON 2026 - Production Ready
 */
final class TaxiService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createDriver(array $data, int $userId, int $tenantId, string $correlationId): TaxiDriver
    {


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($data, $userId, $tenantId, $correlationId) {
            Log::channel('audit')->info('Creating taxi driver', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
            ]);

            return TaxiDriver::create([
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'license_number' => $data['license_number'],
                'license_expiry' => $data['license_expiry'],
                'rating' => 5.0,
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function createVehicle(array $data, int $driverId, string $correlationId): TaxiVehicle
    {


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($data, $driverId, $correlationId) {
            return TaxiVehicle::create([
                'driver_id' => $driverId,
                'brand' => $data['brand'],
                'model' => $data['model'],
                'year' => $data['year'],
                'license_plate' => $data['license_plate'],
                'vin' => $data['vin'],
                'class' => $data['class'] ?? 'economy',
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function createRide(array $data, int $driverId, int $userId, string $correlationId): TaxiRide
    {


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($data, $driverId, $userId, $correlationId) {
            Log::channel('audit')->info('Creating taxi ride', [
                'correlation_id' => $correlationId,
                'driver_id' => $driverId,
                'user_id' => $userId,
            ]);

            return TaxiRide::create([
                'driver_id' => $driverId,
                'user_id' => $userId,
                'pickup_point' => $data['pickup_point'],
                'dropoff_point' => $data['dropoff_point'],
                'status' => 'pending',
                'estimated_price' => $data['estimated_price'],
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function completeRide(TaxiRide $ride, int $actualPrice, string $correlationId): TaxiRide
    {


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($ride, $actualPrice, $correlationId) {
            Log::channel('audit')->info('Completing taxi ride', [
                'correlation_id' => $correlationId,
                'ride_id' => $ride->id,
            ]);

            $ride->update([
                'status' => 'completed',
                'actual_price' => $actualPrice,
                'completed_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            return $ride;
        });
    }
}
