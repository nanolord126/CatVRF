<?php declare(strict_types=1);

namespace App\Domains\ConstructionMaterials\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\ConstructionMaterials\Models\ConstructionMaterial;
use App\Domains\ConstructionMaterials\Models\MaterialOrder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class MaterialService
{
    public function __construct(
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function orderMaterial(int $materialId, int $quantity, array $data): MaterialOrder
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'orderMaterial'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL orderMaterial', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'orderMaterial'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL orderMaterial', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'orderMaterial'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL orderMaterial', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($materialId, $quantity, $data) {
            $material = ConstructionMaterial::lockForUpdate()->find($materialId);
            
            if (!$material || $material->current_stock < $quantity) {
                throw new \Exception('Insufficient stock');
            }

            $order = MaterialOrder::create([
                'tenant_id' => auth()->user()->tenant_id,
                'uuid' => Str::uuid(),
                'correlation_id' => $this->correlationId,
                'material_id' => $materialId,
                'user_id' => auth()->id(),
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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'deliverOrder'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL deliverOrder', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'deliverOrder'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL deliverOrder', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'deliverOrder'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL deliverOrder', ['domain' => __CLASS__]);

        $order->update(['status' => 'delivered']);
        
        Log::channel('audit')->info('Material order delivered', [
            'correlation_id' => $this->correlationId,
            'order_id' => $order->id,
        ]);
    }
}
