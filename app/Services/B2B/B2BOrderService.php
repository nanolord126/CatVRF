<?php declare(strict_types=1);

namespace App\Services\B2B;


use Illuminate\Http\Request;
use App\Models\BusinessGroup;
use App\Services\BusinessGroupService;
use App\Services\FraudControlService;
use App\Services\InventoryService;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

/**
 * B2BOrderService — создание и управление B2B-заказами.
 *
 * Правила канона:
 *  - Только для B2B-клиентов (is_b2b = true в Request)
 *  - Проверка кредитного лимита перед созданием заказа с отсрочкой
 *  - MOQ (minimum order quantity) из конфига products
 *  - Оптовые цены (wholesale_price_kopecks из products)
 *  - Долгосрочный резерв товаров (до 7 дней)
 *  - Fraud-check + $this->db->transaction() + audit log
 *  - Массовые операции: bulk create, import Excel
 */
final readonly class B2BOrderService
{
    public function __construct(
        private readonly Request $request,
        private FraudControlService  $fraud,
        private BusinessGroupService $businessGroupService,
        private InventoryService     $inventory,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
    ) {}

    /**
     * Создать B2B-заказ с одним или несколькими товарами.
     *
     * @param array<int, array{product_id: int, quantity: int, warehouse_id: int}> $items
     * @param bool $useCredit — если true, списать из кредитного лимита (без немедленной оплаты)
     */
    public function create(
        BusinessGroup $group,
        array         $items,
        string        $deliveryAddress,
        bool          $useCredit,
        string        $correlationId,
    ): array {
        $totalKopecks = $this->calculateTotal($items);

        $this->fraud->check(
            (int) $this->guard->id(),
            'b2b_order_create',
            $totalKopecks,
            $this->request->ip(),
            null,
            $correlationId,
        );

        return $this->db->transaction(function () use ($group, $items, $deliveryAddress, $useCredit, $totalKopecks, $correlationId): array {
            // Проверяем кредитный лимит если оплата с отсрочкой
            if ($useCredit) {
                $this->businessGroupService->consumeCredit($group, $totalKopecks, $correlationId);
            }

            // Проверяем MOQ и резервируем товары (TTL = 7 дней для B2B)
            $reservationIds = [];
            foreach ($items as $item) {
                $this->validateMoq($item['product_id'], $item['quantity']);

                $reservationId = $this->inventory->reserveForB2B(
                    productId:    $item['product_id'],
                    warehouseId:  $item['warehouse_id'],
                    quantity:     $item['quantity'],
                    orderId:      0, // обновим после создания order
                    correlationId: $correlationId,
                );

                $reservationIds[] = $reservationId;
            }

            // Создаём запись заказа
            $orderId = (int) $this->db->table('orders')->insertGetId([
                'uuid'              => Str::uuid()->toString(),
                'tenant_id'         => $group->tenant_id,
                'business_group_id' => $group->id,
                'user_id'           => $this->guard->id(),
                'status'            => 'pending',
                'is_b2b'            => true,
                'total_kopecks'     => $totalKopecks,
                'payment_type'      => $useCredit ? 'credit' : 'prepaid',
                'delivery_address'  => $deliveryAddress,
                'correlation_id'    => $correlationId,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            // Создаём позиции заказа
            foreach ($items as $item) {
                $this->db->table('order_items')->insert([
                    'order_id'              => $orderId,
                    'product_id'            => $item['product_id'],
                    'quantity'              => $item['quantity'],
                    'price_kopecks'         => $this->getWholesalePrice($item['product_id']),
                    'warehouse_id'          => $item['warehouse_id'],
                    'correlation_id'        => $correlationId,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }

            $this->logger->channel('audit')->info('B2B order created', [
                'order_id'          => $orderId,
                'business_group_id' => $group->id,
                'total_kopecks'     => $totalKopecks,
                'use_credit'        => $useCredit,
                'items_count'       => count($items),
                'correlation_id'    => $correlationId,
            ]);

            return [
                'order_id'       => $orderId,
                'total_kopecks'  => $totalKopecks,
                'total_rubles'   => $totalKopecks / 100,
                'use_credit'     => $useCredit,
                'items_count'    => count($items),
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Массовое создание B2B-заказов (import из Excel/JSON).
     *
     * @param array<int, array{items: array, delivery_address: string, use_credit: bool}> $orders
     * @return array{created: int, failed: int, errors: array}
     */
    public function bulkCreate(BusinessGroup $group, array $orders, string $correlationId): array
    {
        $created = 0;
        $failed  = 0;
        $errors  = [];

        foreach ($orders as $index => $orderData) {
            try {
                $this->create(
                    $group,
                    $orderData['items'],
                    $orderData['delivery_address'],
                    $orderData['use_credit'] ?? false,
                    $correlationId . '-' . $index,
                );
                $created++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = ['index' => $index, 'error' => $e->getMessage()];

                $this->logger->channel('audit')->error('B2B bulk order failed', [
                    'index'          => $index,
                    'error'          => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        return ['created' => $created, 'failed' => $failed, 'errors' => $errors];
    }

    // ──────────────────────────────────────────────────────
    // PRIVATE
    // ──────────────────────────────────────────────────────

    /**
     * @param array<int, array{product_id: int, quantity: int}> $items
     */
    private function calculateTotal(array $items): int
    {
        $total = 0;
        foreach ($items as $item) {
            $price = $this->getWholesalePrice($item['product_id']);
            $total += $price * $item['quantity'];
        }
        return $total;
    }

    private function getWholesalePrice(int $productId): int
    {
        $price = $this->db->table('products')
            ->where('id', $productId)
            ->value('wholesale_price_kopecks');

        if ($price === null) {
            // Fallback: 80% от розничной цены
            $retail = (int) $this->db->table('products')->where('id', $productId)->value('price_kopecks');
            return (int) round($retail * 0.8);
        }

        return (int) $price;
    }

    private function validateMoq(int $productId, int $quantity): void
    {
        $moq = (int) $this->db->table('products')->where('id', $productId)->value('moq') ?: 1;

        if ($quantity < $moq) {
            throw new \DomainException(
                "Product #{$productId} requires minimum order quantity of {$moq}, got {$quantity}"
            );
        }
    }
}
