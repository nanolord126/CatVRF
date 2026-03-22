<?php declare(strict_types=1);

namespace Modules\Fashion\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Fashion\Models\FashionBrand;
use App\Services\FraudControlService;

final class FashionBrandService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createBrand(array $data, int $tenantId, string $correlationId): FashionBrand
    {


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($data, $tenantId, $correlationId) {
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
