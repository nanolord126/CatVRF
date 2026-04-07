<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Services;

use App\Domains\GroceryAndDelivery\Models\GroceryOrder;
use App\Domains\GroceryAndDelivery\Models\GroceryProduct;
use App\Services\FraudControlService;
use App\Services\Inventory\InventoryManagementService;
use App\Services\Wallet\WalletService;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

/**
 * Главный сервис управления заказами вертикали GroceryAndDelivery.
 *
 * Функциональность:
 * - Создание заказа с fraud-проверкой и холдом товаров.
 * - Подтверждение, завершение и отмена заказов.
 * - Расчёт комиссии платформы и выплата магазину через WalletService.
 *
 * Все мутации выполняются в $this->db->transaction() с обязательным correlation_id.
 * Перед созданием заказа — FraudControlService::checkOrder().
 */
final readonly class GroceryOrderService
{
    public function __construct(
        private FraudControlService $fraudControl,
        private InventoryManagementService $inventory,
        private WalletService $wallet,        private DatabaseManager $db,        private LoggerInterface $logger,


    ) {}

    /**
     * Создать заказ с fraud-проверкой и холдом товаров.
     *
     * @param array<int, array{product_id: int, quantity: int, price: float}> $items
     */
    public function createOrder(
        int $userId,
        int $storeId,
        int $deliverySlotId,
        array $items,
        float $lat,
        float $lon,
        string $correlationId,
    ): GroceryOrder {
        $totalAmount = array_sum(array_map(
            static fn(array $i): float => $i['quantity'] * $i['price'],
            $items,
        ));

        $fraudScore = $this->fraudControl->checkOrder(
            userId: $userId,
            orderType: 'grocery_order',
            totalAmount: $totalAmount,
            correlationId: $correlationId,
        );

        if ($fraudScore > 0.85) {
            throw new \RuntimeException(
                "Order blocked due to fraud detection (score: {$fraudScore})"
            );
        }

        return $this->db->transaction(function () use ($userId, $storeId, $deliverySlotId, $items, $lat, $lon, $correlationId, $fraudScore): GroceryOrder {
            $totalPrice = 0;

            foreach ($items as $item) {
                $product = GroceryProduct::findOrFail($item['product_id']);
                $totalPrice += $item['quantity'] * $product->price;

                $this->inventory->reserveStock(
                    itemId: $item['product_id'],
                    quantity: $item['quantity'],
                    sourceType: 'grocery_order',
                    sourceId: $storeId,
                );
            }

            $storeCommission = $this->db->table('grocery_stores')
                ->where('id', $storeId)
                ->value('commission_percent') ?? 14;

            $commissionAmount = (int) ($totalPrice * $storeCommission / 100);

            $order = GroceryOrder::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => tenant()->id,
                'user_id' => $userId,
                'store_id' => $storeId,
                'delivery_slot_id' => $deliverySlotId,
                'status' => 'pending',
                'total_price' => $totalPrice,
                'delivery_price' => 200,
                'commission_amount' => $commissionAmount,
                'delivery_address' => 'TBD',
                'lat' => $lat,
                'lon' => $lon,
                'placed_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            foreach ($items as $item) {
                $product = GroceryProduct::findOrFail($item['product_id']);
                $order->orderItems()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price_per_unit' => $product->price,
                    'total_price' => $item['quantity'] * $product->price,
                    'correlation_id' => $correlationId,
                ]);
            }

            $this->logger->channel('audit')->info('Grocery order created', [
                'order_id' => $order->id,
                'user_id' => $userId,
                'total_price' => $totalPrice,
                'fraud_score' => $fraudScore,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Подтвердить заказ (pending → confirmed).
     */
    public function confirmOrder(GroceryOrder $order, string $correlationId): GroceryOrder
    {
        return $this->db->transaction(function () use ($order, $correlationId): GroceryOrder {
            $order->update([
                'status' => 'confirmed',
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->info('Grocery order confirmed', [
                'order_id' => $order->id,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Завершить заказ: списать товары со склада и перевести средства магазину.
     */
    public function completeOrder(GroceryOrder $order, string $correlationId): GroceryOrder
    {
        return $this->db->transaction(function () use ($order, $correlationId): GroceryOrder {
            foreach ($order->orderItems as $item) {
                $this->inventory->deductStock(
                    itemId: $item->product_id,
                    quantity: $item->quantity,
                    reason: 'delivery_completed',
                    sourceType: 'grocery_order',
                    sourceId: $order->id,
                );
            }

            $payoutAmount = $order->total_price - $order->commission_amount;

            $this->wallet->credit(
                tenantId: $order->store()->first()->tenant_id,
                amount: $payoutAmount,
                reason: 'order_payout',
                correlationId: $correlationId,
            );

            $order->update([
                'status' => 'delivered',
                'delivered_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->info('Grocery order delivered', [
                'order_id' => $order->id,
                'payout_amount' => $payoutAmount,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Отменить заказ и освободить зарезервированные товары.
     */
    public function cancelOrder(GroceryOrder $order, string $reason, string $correlationId): GroceryOrder
    {
        return $this->db->transaction(function () use ($order, $reason, $correlationId): GroceryOrder {
            foreach ($order->orderItems as $item) {
                $this->inventory->releaseStock(
                    itemId: $item->product_id,
                    quantity: $item->quantity,
                    sourceType: 'grocery_order',
                    sourceId: $order->id,
                );
            }

            $order->update([
                'status' => 'cancelled',
                'correlation_id' => $correlationId,
            ]);

            $this->logger->channel('audit')->info('Grocery order cancelled', [
                'order_id' => $order->id,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }
}
