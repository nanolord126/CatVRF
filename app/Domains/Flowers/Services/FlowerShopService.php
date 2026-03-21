<?php declare(strict_types=1);

namespace Modules\Flowers\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Flowers\Models\FlowerShop;

final class FlowerShopService
{
    public function createShop(array $data, int $tenantId, string $correlationId): FlowerShop
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Flowers', ['correlation_id' => $correlationId]);
        FraudControlService::check('service_operation', ['correlation_id' => $correlationId]);

        return DB::transaction(function () use ($data, $tenantId, $correlationId) {
            Log::channel('audit')->info('Creating flower shop', ['correlation_id' => $correlationId]);

            return FlowerShop::create([
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
