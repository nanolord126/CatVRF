<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Logistics\Models\CourierService;
use Illuminate\Support\Facades\DB;

final class CourierServiceService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

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


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use (
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


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($courier, $data, $correlationId) {
            $courier->update([...$data, 'correlation_id' => $correlationId]);

            Log::channel('audit')->info('Courier service updated', [
                'courier_id' => $courier->id,
                'correlation_id' => $correlationId,
            ]);

            return $courier;
        });
    }
}
