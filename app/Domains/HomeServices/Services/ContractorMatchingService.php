declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Services;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

final /**
 * ContractorMatchingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ContractorMatchingService
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function __construct()
    {
    }

    public function findContractors(string $serviceType, string $address, string $correlationId): \Illuminate\Database\Eloquent\Collection
    {


        try {
            $contractors = $this->db->table('contractors')
                ->where('service_type', $serviceType)
                ->where('is_available', true)
                ->orderBy('rating', 'desc')
                ->limit(10)
                ->get();

            $this->log->channel('audit')->info('Contractors found', [
                'service_type' => $serviceType,
                'count' => $contractors->count(),
                'correlation_id' => $correlationId,
            ]);

            return $contractors;
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Contractor matching failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
