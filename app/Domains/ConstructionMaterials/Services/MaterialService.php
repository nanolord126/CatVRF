<?php declare(strict_types=1);

namespace App\Domains\ConstructionMaterials\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\ConstructionMaterials\Models\ConstructionMaterial;
use App\Domains\ConstructionMaterials\Models\MaterialOrder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class MaterialService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function orderMaterial(int $materialId, int $quantity, array $data, int $userId, int $tenantId): MaterialOrder
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($materialId, $quantity, $data, $userId, $tenantId) {
            $material = ConstructionMaterial::lockForUpdate()->find($materialId);
            
            if (!$material || $material->current_stock < $quantity) {
                throw new \Exception('Insufficient stock');
            }

            $order = MaterialOrder::create([
                'tenant_id' => $tenantId,
                'uuid' => Str::uuid(),
                'correlation_id' => $this->correlationId,
                'material_id' => $materialId,
                'user_id' => $userId,
                'quantity' => $quantity,
                'total_price' => $material->price * $quantity,
                'status' => 'pending',
                'delivery_address' => $data['address'] ?? '',
            ]);

            Log::channel('audit')->info('Material order created', [
                'correlation_id' => $this->correlationId,
                'order_id' => $order->id,
                'material_id' => $materialId,
                'quantity' => $quantity,
            ]);

            return $order;
        });
    }

    public function deliverOrder(MaterialOrder $order): void
    {




        $order->update(['status' => 'delivered']);
        
        Log::channel('audit')->info('Material order delivered', [
            'correlation_id' => $this->correlationId,
            'order_id' => $order->id,
        ]);
    }
}
