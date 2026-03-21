<?php declare(strict_types=1);

namespace App\Domains\Furniture\Services;

use App\Domains\Furniture\Models\FurnitureOrder;
use App\Domains\Furniture\Events\FurnitureOrderCreated;
use App\Domains\Furniture\Events\FurnitureDelivered;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

final class DeliveryAssemblyService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function scheduleDelivery(int $orderId, int $tenantId, Carbon $deliveryDate, bool $needsAssembly, string $correlationId): FurnitureOrder
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'scheduleDelivery'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL scheduleDelivery', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($orderId, $tenantId, $deliveryDate, $needsAssembly, $correlationId) {
            $order = FurnitureOrder::lockForUpdate()
                ->where('id', $orderId)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            if ($order->status !== 'pending') {
                throw new \Exception("Order {$orderId} is not pending");
            }

            $order->update([
                'delivery_date' => $deliveryDate,
                'status' => 'delivery_scheduled',
            ]);

            Log::channel('audit')->info('Furniture delivery scheduled', [
                'order_id' => $orderId,
                'delivery_date' => $deliveryDate,
                'assembly_required' => $needsAssembly,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    public function scheduleAssembly(int $orderId, int $tenantId, Carbon $assemblyDate, string $correlationId): FurnitureOrder
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'scheduleAssembly'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL scheduleAssembly', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($orderId, $tenantId, $assemblyDate, $correlationId) {
            $order = FurnitureOrder::lockForUpdate()
                ->where('id', $orderId)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            if ($order->status !== 'delivery_scheduled') {
                throw new \Exception("Order {$orderId} is not in delivery_scheduled state");
            }

            $order->update([
                'assembly_date' => $assemblyDate,
                'status' => 'assembly_scheduled',
            ]);

            Log::channel('audit')->info('Furniture assembly scheduled', [
                'order_id' => $orderId,
                'assembly_date' => $assemblyDate,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    public function markDelivered(int $orderId, int $tenantId, string $correlationId): FurnitureOrder
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'markDelivered'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL markDelivered', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($orderId, $tenantId, $correlationId) {
            $order = FurnitureOrder::lockForUpdate()
                ->where('id', $orderId)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            if ($order->status !== 'delivery_scheduled') {
                throw new \Exception("Order {$orderId} is not ready for delivery");
            }

            $order->update(['status' => 'delivered']);

            FurnitureDelivered::dispatch($order->id, $tenantId, $correlationId);
            Log::channel('audit')->info('Furniture delivered', [
                'order_id' => $order->id,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }
}
