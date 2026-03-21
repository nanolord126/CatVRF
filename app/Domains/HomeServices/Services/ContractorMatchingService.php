<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ContractorMatchingService
{
    public function __construct()
    {
    }

    public function findContractors(string $serviceType, string $address, string $correlationId): \Illuminate\Database\Eloquent\Collection
    {
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
