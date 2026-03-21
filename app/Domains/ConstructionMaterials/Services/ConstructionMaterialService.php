<?php declare(strict_types=1);

namespace App\Domains\ConstructionMaterials\Services;

use App\Domains\ConstructionMaterials\Models\ConstructionMaterial;
use App\Domains\ConstructionMaterials\Models\MaterialOrder;
use App\Services\WalletService;
use App\Services\FraudControlService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

final class ConstructionMaterialService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly FraudControlService $fraudControlService,
        private readonly string $correlationId = '',
    ) {}

    public function orderMaterial(
        int $materialId,
        int $quantity,
        string $deliveryAddress,
        ?string $correlationIdOverride = null
    ): MaterialOrder {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'orderMaterial'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL orderMaterial', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'orderMaterial'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL orderMaterial', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'orderMaterial'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL orderMaterial', ['domain' => __CLASS__]);

        $correlationId = $correlationIdOverride ?: Str::uuid()->toString();

        try {
            // Fraud check
            $this->fraudControlService->check([
                'type' => 'material_order',
                'user_id' => auth()->id(),
                'amount' => 0, // Will be calculated
                'correlation_id' => $correlationId,
            ]);

            return DB::transaction(function () use ($materialId, $quantity, $deliveryAddress, $correlationId) {
                // Lock material for update
                $material = ConstructionMaterial::lockForUpdate()->find($materialId);

                if (!$material) {
                    throw new Exception('Material not found', 404);
                }

                if ($material->current_stock < $quantity) {
                    throw new Exception('Insufficient stock. Available: ' . $material->current_stock, 422);
                }

                // Calculate prices
                $unitPrice = $material->price;
                $totalPrice = $unitPrice * $quantity;

                // Create order
                $order = MaterialOrder::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'uuid' => Str::uuid(),
                    'correlation_id' => $correlationId,
                    'material_id' => $materialId,
                    'user_id' => auth()->id(),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                    'delivery_address' => $deliveryAddress,
                ]);

                // Deduct from stock
                $material->update([
                    'current_stock' => $material->current_stock - $quantity,
                ]);

                // Log audit
                Log::channel('audit')->info('Construction material order created', [
                    'correlation_id' => $correlationId,
                    'order_id' => $order->id,
                    'material_id' => $materialId,
                    'quantity' => $quantity,
                    'total_price' => $totalPrice,
                    'user_id' => auth()->id(),
                ]);

                // Invalidate cache
                Cache::forget('material:' . $materialId);

                return $order;
            });
        } catch (Exception $e) {
            Log::channel('error')->error('Material order failed', [
                'correlation_id' => $correlationId,
                'material_id' => $materialId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function deliverOrder(MaterialOrder $order, string $trackingNumber = null): void
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

        $correlationId = $order->correlation_id ?? Str::uuid()->toString();

        try {
            $order->update([
                'status' => 'delivered',
                'tracking_number' => $trackingNumber,
                'delivery_date' => now(),
            ]);

            Log::channel('audit')->info('Material order delivered', [
                'correlation_id' => $correlationId,
                'order_id' => $order->id,
            ]);
        } catch (Exception $e) {
            Log::channel('error')->error('Delivery failed', [
                'correlation_id' => $correlationId,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function cancelOrder(MaterialOrder $order): void
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'cancelOrder'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL cancelOrder', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'cancelOrder'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL cancelOrder', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'cancelOrder'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL cancelOrder', ['domain' => __CLASS__]);

        $correlationId = $order->correlation_id ?? Str::uuid()->toString();

        try {
            DB::transaction(function () use ($order) {
                $material = $order->material;

                if ($material) {
                    $material->update([
                        'current_stock' => $material->current_stock + $order->quantity,
                    ]);
                }

                $order->update(['status' => 'cancelled']);

                Cache::forget('material:' . $order->material_id);
            });

            Log::channel('audit')->info('Material order cancelled', [
                'correlation_id' => $correlationId,
                'order_id' => $order->id,
            ]);
        } catch (Exception $e) {
            Log::channel('error')->error('Cancellation failed', [
                'correlation_id' => $correlationId,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function getMaterialsLowStock(): iterable
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getMaterialsLowStock'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getMaterialsLowStock', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getMaterialsLowStock'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getMaterialsLowStock', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getMaterialsLowStock'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getMaterialsLowStock', ['domain' => __CLASS__]);

        return ConstructionMaterial::where('current_stock', '<=', DB::raw('min_stock_threshold'))
            ->get();
    }

    public function checkMaterialAvailability(int $materialId, int $quantity): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'checkMaterialAvailability'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL checkMaterialAvailability', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'checkMaterialAvailability'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL checkMaterialAvailability', ['domain' => __CLASS__]);

        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'checkMaterialAvailability'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL checkMaterialAvailability', ['domain' => __CLASS__]);

        $material = ConstructionMaterial::find($materialId);

        return $material && $material->current_stock >= $quantity;
    }
}
