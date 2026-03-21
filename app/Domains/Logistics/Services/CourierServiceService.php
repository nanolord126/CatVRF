<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Logistics\Models\CourierService;
use Illuminate\Support\Facades\DB;

final class CourierServiceService
{
    public function createCourierService(
        int $tenantId,
        int $userId,
        string $companyName,
        string $licenseNumber,
        array $vehicleTypes,
        int $serviceRadius,
        float $baseRate,
        float $perKmRate,
        string $correlationId,
    ): CourierService {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'createCourierService'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createCourierService', ['domain' => __CLASS__]);

        return DB::transaction(function () use (
            $tenantId,
            $userId,
            $companyName,
            $licenseNumber,
            $vehicleTypes,
            $serviceRadius,
            $baseRate,
            $perKmRate,
            $correlationId,
        ) {
            $courier = CourierService::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'company_name' => $companyName,
                'license_number' => $licenseNumber,
                'vehicle_types' => collect($vehicleTypes),
                'service_radius' => $serviceRadius,
                'base_rate' => $baseRate,
                'per_km_rate' => $perKmRate,
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Courier service created', [
                'courier_id' => $courier->id,
                'tenant_id' => $tenantId,
                'company_name' => $companyName,
                'correlation_id' => $correlationId,
            ]);

            return $courier;
        });
    }

    public function updateCourierService(CourierService $courier, array $data, string $correlationId): CourierService
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'updateCourierService'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL updateCourierService', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($courier, $data, $correlationId) {
            $courier->update([...$data, 'correlation_id' => $correlationId]);

            Log::channel('audit')->info('Courier service updated', [
                'courier_id' => $courier->id,
                'correlation_id' => $correlationId,
            ]);

            return $courier;
        });
    }
}
