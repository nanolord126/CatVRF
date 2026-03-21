<?php declare(strict_types=1);

namespace Modules\Fashion\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Fashion\Models\FashionBrand;

final class FashionBrandService
{
    public function createBrand(array $data, int $tenantId, string $correlationId): FashionBrand
    {
        return DB::transaction(function () use ($data, $tenantId, $correlationId) {
            Log::channel('audit')->info('Creating fashion brand', ['correlation_id' => $correlationId]);

            return FashionBrand::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
