<?php

declare(strict_types=1);


namespace Modules\Sports\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Sports\Models\SportVenue;
use Illuminate\Support\Str;
use App\Services\FraudControlService;

final /**
 * SportVenueService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SportVenueService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createVenue(array $data, int $tenantId, string $correlationId): SportVenue
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Sports', ['correlation_id' => $correlationId]);

        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($data, $tenantId, $correlationId) {
            Log::channel('audit')->info('Creating sport venue', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
            ]);

            return SportVenue::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'address' => $data['address'],
                'geo_point' => $data['geo_point'] ?? null,
                'sports_types' => json_encode($data['sports_types'] ?? []),
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
