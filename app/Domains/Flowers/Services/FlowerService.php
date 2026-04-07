<?php

declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Events\FlowerOrderCreated;
use App\Domains\Flowers\Models\FlowerOrder;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class FlowerService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Создать новый заказ цветов (B2C или B2B).
     *
     * @param array $data Данные заказа (tenant_id, user_id, flower_shop_id, items, total_price и т.д.)
     */
    public function createOrder(array $data): FlowerOrder
    {
        $correlationId = $data['correlation_id'] ?? Str::uuid()->toString();
        $isB2B = isset($data['inn']) || isset($data['business_card_id']);
        $userId = (int) ($data['user_id'] ?? 0);
        $tenantId = (int) ($data['tenant_id'] ?? 0);

        $this->fraud->check(
            userId: $userId,
            operationType: $isB2B ? 'flower_b2b_order' : 'flower_order',
            amount: (int) ($data['total_price'] ?? 0),
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($data, $correlationId, $isB2B, $tenantId): FlowerOrder {
            $order = FlowerOrder::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $tenantId,
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

            $this->logger->info('Flower Order Created', [
                'order_id' => $order->id,
                'mode' => $isB2B ? 'B2B' : 'B2C',
                'total' => $order->total_price_kopecks,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            event(new FlowerOrderCreated($order, $correlationId));

            return $order;
        });
    }

    /**
     * Обновить статус заказа.
     *
     * @param int    $orderId       ID заказа
     * @param string $status        Новый статус
     * @param string $correlationId Трейсинг-идентификатор
     */
    public function updateStatus(int $orderId, string $status, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $order = FlowerOrder::findOrFail($orderId);
            $oldStatus = $order->status;

            $order->update(['status' => $status]);

        $this->logger->info('Flower Order Status Updated', [
            'order_id' => $orderId,
            'from' => $oldStatus,
            'to' => $status,
            'correlation_id' => $correlationId,
        ]);
    }
}
