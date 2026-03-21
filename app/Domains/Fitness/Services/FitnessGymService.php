<?php declare(strict_types=1);

namespace Modules\Fitness\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Fitness\Models\FitnessGym;

final class FitnessGymService
{
    public function createGym(array $data, int $tenantId, string $correlationId): FitnessGym
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'createGym'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createGym', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($data, $tenantId, $correlationId) {
            Log::channel('audit')->info('Creating fitness gym', ['correlation_id' => $correlationId]);

            return FitnessGym::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'address' => $data['address'],
                'geo_point' => $data['geo_point'] ?? null,
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
