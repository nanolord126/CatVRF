<?php declare(strict_types=1);

namespace Modules\Medical\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Medical\Models\Clinic;

final class ClinicService
{
    public function createClinic(array $data, int $tenantId, string $correlationId): Clinic
    {
        return DB::transaction(function () use ($data, $tenantId, $correlationId) {
            Log::channel('audit')->info('Creating clinic', ['correlation_id' => $correlationId]);

            return Clinic::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'address' => $data['address'],
                'geo_point' => $data['geo_point'] ?? null,
                'license_number' => $data['license_number'],
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
