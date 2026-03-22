<?php declare(strict_types=1);

namespace Modules\Flowers\Services;

use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Flowers\Models\FlowerShop;

final class FlowerShopService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createShop(array $data, int $tenantId, string $correlationId): FlowerShop
    {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Flowers', ['correlation_id' => $correlationId]);
        $this->fraudControlService->check(0, 'flower_shop_create', 0, null, null, $correlationId);

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
