<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

final class ContractorMatchingService
{
    public function __construct()
    {
    }

    public function findContractors(string $serviceType, string $address, string $correlationId): \Illuminate\Database\Eloquent\Collection
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'findContractors'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL findContractors', ['domain' => __CLASS__]);

        try {
            $contractors = DB::table('contractors')
                ->where('service_type', $serviceType)
                ->where('is_available', true)
                ->orderBy('rating', 'desc')
                ->limit(10)
                ->get();

            Log::channel('audit')->info('Contractors found', [
                'service_type' => $serviceType,
                'count' => $contractors->count(),
                'correlation_id' => $correlationId,
            ]);

            return $contractors;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Contractor matching failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
