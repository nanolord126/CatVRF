<?php declare(strict_types=1);

namespace App\Domains\OfficeCatering\Services;

use App\Domains\OfficeCatering\Models\CorporateOrder;
use App\Domains\OfficeCatering\Models\CorporateClient;
use App\Domains\OfficeCatering\Models\OfficeMenu;
use App\Domains\OfficeCatering\Events\CorporateOrderCreated;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

final class OfficeCateringService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function placeOrder(int $clientId, int $menuId, int $portions, Carbon $deliveryDate, int $tenantId, string $correlationId): CorporateOrder
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'placeOrder'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL placeOrder', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($clientId, $menuId, $portions, $deliveryDate, $tenantId, $correlationId) {
            $this->fraudControlService->check(
                userId: $clientId,
                operationType: 'corporate_order',
                amount: 0,
                correlationId: $correlationId,
            );

            $menu = OfficeMenu::findOrFail($menuId);
            $totalPrice = $menu->price_per_serving * $portions;

            $order = CorporateOrder::create([
                'tenant_id' => $tenantId,
                'uuid' => Str::uuid(),
                'correlation_id' => $correlationId,
                'client_id' => $clientId,
                'menu_id' => $menuId,
                'portions' => $portions,
                'total_price' => $totalPrice,
                'delivery_date' => $deliveryDate,
                'status' => 'pending',
                'idempotency_key' => md5("{$clientId}:{$menuId}:{$portions}:{$deliveryDate}:{$tenantId}"),
            ]);

            CorporateOrderCreated::dispatch($order->id, $tenantId, $clientId, $totalPrice, $correlationId);
            Log::channel('audit')->info('Corporate order created', [
                'order_id' => $order->id,
                'client_id' => $clientId,
                'menu_id' => $menuId,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    public function setupRecurring(int $orderId, string $frequency, int $tenantId, string $correlationId): CorporateOrder
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'setupRecurring'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL setupRecurring', ['domain' => __CLASS__]);

        $order = CorporateOrder::lockForUpdate()
            ->where('id', $orderId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $order->update([
            'is_recurring' => true,
            'recurrence' => $frequency,
        ]);

        Log::channel('audit')->info('Corporate order set to recurring', [
            'order_id' => $order->id,
            'frequency' => $frequency,
            'correlation_id' => $correlationId,
        ]);

        return $order;
    }

    public function markDelivered(int $orderId, int $tenantId, string $correlationId): CorporateOrder
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'markDelivered'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL markDelivered', ['domain' => __CLASS__]);

        $order = CorporateOrder::lockForUpdate()
            ->where('id', $orderId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        if ($order->status !== 'pending') {
            throw new \Exception("Order {$orderId} is not pending");
        }

        $order->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        Log::channel('audit')->info('Corporate order delivered', [
            'order_id' => $order->id,
            'correlation_id' => $correlationId,
        ]);

        return $order;
    }
}
