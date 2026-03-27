<?php

declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Models\FlowerOrder;
use App\Domains\Flowers\Events\FlowerOrderCreated;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: FlowerService (Flowers).
 * Основной сервис для управления заказами цветов (B2B/B2C).
 */
final readonly class FlowerService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
    ) {}

    /**
     * Создать новый заказ цветов (B2C или B2B)
     */
    public function createOrder(array $data): FlowerOrder
    {
        $correlationId = $data['correlation_id'] ?? (string) Str::uuid();
        $isB2B = isset($data['inn']) || isset($data['business_card_id']);

        // 1. Fraud Check
        $this->fraud->check(
            userId: (int) ($data['user_id'] ?? 0),
            operationType: $isB2B ? 'flower_b2b_order' : 'flower_order',
            amount: (int) ($data['total_price'] ?? 0),
            correlationId: $correlationId
        );

        return DB::transaction(function () use ($data, $correlationId, $isB2B) {
            // 2. Создание заказа
            $order = FlowerOrder::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id ?? ($data['tenant_id'] ?? null),
                'user_id' => $data['user_id'] ?? null,
                'flower_shop_id' => $data['flower_shop_id'],
                'items_json' => $data['items'] ?? [],
                'total_price_kopecks' => $data['total_price'],
                'status' => 'pending',
                'inn' => $data['inn'] ?? null,
                'business_card_id' => $data['business_card_id'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_date' => $data['delivery_date'] ?? null,
                'correlation_id' => $correlationId,
                'tags' => ['mode' => $isB2B ? 'B2B' : 'B2C'],
            ]);

            // 3. Логирование
            Log::channel('audit')->info('Flower Order Created', [
                'order_id' => $order->id,
                'mode' => $isB2B ? 'B2B' : 'B2C',
                'total' => $order->total_price_kopecks,
                'correlation_id' => $correlationId,
            ]);

            // 4. Dispatch Event (Trigger Consumable Deduction & Notifications)
            event(new FlowerOrderCreated($order, $correlationId));

            return $order;
        });
    }

    /**
     * Обновить статус заказа
     */
    public function updateStatus(int $orderId, string $status): void
    {
        $order = FlowerOrder::findOrFail($orderId);
        $oldStatus = $order->status;
        
        $order->update(['status' => $status]);

        Log::channel('audit')->info('Flower Order Status Updated', [
           'order_id' => $orderId,
           'from' => $oldStatus,
           'to' => $status,
           'correlation_id' => $order->correlation_id,
        ]);
    }
}
