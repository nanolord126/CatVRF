<?php

declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Events\FlowerOrderPlaced;
use App\Domains\Flowers\Models\FlowerOrder;
use App\Domains\Flowers\Models\FlowerOrderItem;
use App\Domains\Flowers\Models\FlowerProduct;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class FlowerOrderService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Создать публичный заказ цветов (B2C).
     *
     * @param int    $tenantId      ID тенанта
     * @param int    $userId        ID пользователя
     * @param int    $shopId        ID магазина
     * @param array  $items         Позиции заказа
     * @param array  $deliveryData  Данные доставки
     * @param string $correlationId Трейсинг-идентификатор
     */
    public function createPublicOrder(
        int $tenantId,
        int $userId,
        int $shopId,
        array $items,
        array $deliveryData,
        string $correlationId = '',
    ): FlowerOrder {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        try {
            $this->fraud->check(
                userId: $userId,
                operationType: 'flower_order_create',
                amount: 0,
                correlationId: $correlationId,
            );

            return $this->db->transaction(function () use ($tenantId, $userId, $shopId, $items, $deliveryData, $correlationId): FlowerOrder {
                    $subtotal = 0;
                    $orderItems = [];

                    foreach ($items as $item) {
                        $product = FlowerProduct::query()
                            ->where('id', $item['product_id'])
                            ->where('shop_id', $shopId)
                            ->where('tenant_id', $tenantId)
                            ->lockForUpdate()
                            ->firstOrFail();

                        $quantity = $item['quantity'];
                        $unitPrice = $product->price;
                        $totalPrice = $unitPrice * $quantity;

                        $subtotal += $totalPrice;
                        $orderItems[] = [
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'total_price' => $totalPrice,
                            'customizations' => $item['customizations'] ?? null,
                        ];
                    }

                    $commissionAmount = $subtotal * 0.14;
                    $deliveryFee = $deliveryData['delivery_fee'] ?? 0;
                    $totalAmount = $subtotal + $deliveryFee;

                    $order = FlowerOrder::query()->create([
                        'tenant_id' => $tenantId,
                        'shop_id' => $shopId,
                        'user_id' => $userId,
                        'order_number' => $this->generateOrderNumber(),
                        'subtotal' => $subtotal,
                        'delivery_fee' => $deliveryFee,
                        'commission_amount' => $commissionAmount,
                        'total_amount' => $totalAmount,
                        'recipient_name' => $deliveryData['recipient_name'],
                        'recipient_phone' => $deliveryData['recipient_phone'],
                        'delivery_address' => $deliveryData['delivery_address'],
                        'delivery_date' => $deliveryData['delivery_date'],
                        'delivery_time_slot' => $deliveryData['delivery_time_slot'] ?? null,
                        'message' => $deliveryData['message'] ?? null,
                        'status' => 'pending',
                        'payment_status' => 'pending',
                        'correlation_id' => $correlationId,
                    ]);

                    foreach ($orderItems as $itemData) {
                        FlowerOrderItem::query()->create([
                            'order_id' => $order->id,
                            ...$itemData,
                        ]);
                    }

                    $this->logger->info('Flower order created', [
                        'order_id' => $order->id,
                        'user_id' => $userId,
                        'total_amount' => $totalAmount,
                        'commission_amount' => $commissionAmount,
                        'correlation_id' => $correlationId,
                    ]);

                    FlowerOrderPlaced::dispatch($order, $correlationId);

                    return $order;
                });
            } catch (\Throwable $exception) {
                $this->logger->error('Flower order creation failed', [
                    'user_id' => $userId,
                    'error' => $exception->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $exception;
            }
        }

    /**
     * Получить публичные заказы пользователя.
     *
     * @param int $tenantId ID тенанта
     * @param int $userId   ID пользователя
     */
    public function getPublicOrders(int $tenantId, int $userId): Collection
    {
        return FlowerOrder::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->with(['shop', 'items.product'])
                ->get();
        }

    /**
     * Обновить статус заказа.
     *
     * @param int    $orderId       ID заказа
     * @param string $status        Новый статус
     * @param string $correlationId Трейсинг-идентификатор
     */
    public function updateOrderStatus(int $orderId, string $status, string $correlationId = ''): FlowerOrder
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        return $this->db->transaction(function () use ($orderId, $status, $correlationId): FlowerOrder {
                $order = FlowerOrder::query()
                    ->where('id', $orderId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $order->update(['status' => $status]);

                $this->logger->info('Flower order status updated', [
                    'order_id' => $order->id,
                    'status' => $status,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });
        }

    /**
     * Сгенерировать номер заказа.
     */
    private function generateOrderNumber(): string
    {
        return 'FLO-' . date('Ymd') . '-' . Str::upper(Str::random(8));
    }
}
