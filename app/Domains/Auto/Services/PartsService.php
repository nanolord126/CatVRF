<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class PartsService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    /**
         * Сверхважный метод списания запчастей под заказ.
         */
        public function reservePartsForRepair(AutoRepairOrder $order, array $partsData, string $correlationId): void
        {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
            $this->db->transaction(function () use ($order, $partsData, $correlationId) {
                foreach ($partsData as $item) {
                    // $item['part_id'], $item['quantity']
                    $part = AutoPart::lockForUpdate()->findOrFail($item['part_id']);

                    if ($part->current_stock < $item['quantity']) {
                        throw new InsufficientStockException("Запчасти '{$part->name}' недостаточно на складе ({$part->current_stock} < {$item['quantity']})");
                    }

                    // Списание со склада
                    $part->current_stock -= $item['quantity'];

                    // Фиксация в заказе
                    $orderParts = $order->parts_list ?? [];
                    $orderParts[] = [
                        'part_id' => $part->id,
                        'sku' => $part->sku,
                        'name' => $part->name,
                        'quantity' => $item['quantity'],
                        'price' => $part->price_kopecks,
                        'total' => $part->price_kopecks * $item['quantity'],
                    ];

                    $order->parts_list = $orderParts;
                    $order->parts_cost_kopecks += ($part->price_kopecks * $item['quantity']);

                    $part->save();

                    $this->logger->info('Part reserved for repair', [
                        'order_uuid' => $order->uuid,
                        'part_sku' => $part->sku,
                        'quantity' => $item['quantity'],
                        'correlation_id' => $correlationId,
                    ]);
                }

                $order->recalculateTotal();
                $order->save();
            });
        }

        /**
         * Пополнение склада.
         */
        public function addStock(array $data, string $correlationId): AutoPart
        {
            return $this->db->transaction(function () use ($data, $correlationId) {
                $part = AutoPart::updateOrCreate(
                    ['sku' => $data['sku'], 'tenant_id' => tenant()->id],
                    [
                        'name' => $data['name'],
                        'brand' => $data['brand'] ?? 'Unknown',
                        'price_kopecks' => $data['price_kopecks'],
                        'correlation_id' => $correlationId,
                    ]
                );

                $part->current_stock += ($data['quantity'] ?? 0);
                $part->save();

                $this->logger->info('Stock replenished', [
                    'sku' => $part->sku,
                    'added' => $data['quantity'],
                    'new_total' => $part->current_stock,
                    'correlation_id' => $correlationId,
                ]);

                return $part;
            });
        }

        /**
         * Проверка на низкие остатки.
         */
        public function getLowStockAlerts(): \Illuminate\Support\Collection
        {
            return AutoPart::whereRaw('current_stock <= min_stock_threshold')
                ->orderBy('current_stock', 'asc')
                ->get();
        }
}
