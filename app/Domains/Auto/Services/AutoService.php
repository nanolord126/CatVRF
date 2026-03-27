<?php

declare(strict_types=1);

namespace App\Domains\Auto\Services;

use App\Domains\Auto\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: AutoService.
 * Управление жизненным циклом транспортных средств.
 */
final readonly class AutoService
{
    /**
     * Регистрация нового ТС в системе.
     */
    public function registerVehicle(array $data, string $correlationId): Vehicle
    {
        return DB::transaction(function () use ($data, $correlationId) {
            $data['correlation_id'] = $correlationId;
            $data['uuid'] = (string) Str::uuid();
            $data['status'] = 'active';

            $vehicle = Vehicle::create($data);

            Log::channel('audit')->info('Vehicle registered', [
                'uuid' => $vehicle->uuid,
                'correlation_id' => $correlationId,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'license_plate' => $vehicle->license_plate,
            ]);

            return $vehicle;
        });
    }

    /**
     * Смена статуса (Ремонт, Свободен, В пути).
     */
    public function updateStatus(Vehicle $vehicle, string $newStatus, string $correlationId): void
    {
        DB::transaction(function () use ($vehicle, $newStatus, $correlationId) {
            $oldStatus = $vehicle->status;
            $vehicle->update([
                'status' => $newStatus,
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Vehicle status changed', [
                'uuid' => $vehicle->uuid,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Передача ТС в автопарк (B2B).
     */
    public function assignToFleet(Vehicle $vehicle, int $fleetId, string $correlationId): void
    {
        DB::transaction(function () use ($vehicle, $fleetId, $correlationId) {
            $vehicle->update([
                'business_group_id' => $fleetId,
                'type' => 'fleet',
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Vehicle assigned to fleet', [
                'uuid' => $vehicle->uuid,
                'fleet_id' => $fleetId,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
