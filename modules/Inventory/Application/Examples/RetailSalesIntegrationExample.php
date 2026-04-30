<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Examples;

use Modules\Inventory\Application\Services\FIFOShelfLifeService;
use Modules\Inventory\Domain\Exceptions\InsufficientStockWithExpiryException;
use Modules\Inventory\Domain\Exceptions\ShelfLifeException;
use Modules\Inventory\Infrastructure\Models\InventoryBatchModel;
use Modules\Inventory\Infrastructure\Models\InventoryItemModel;

/**
 * Пример интеграции FIFO системы контроля срока годности с модулем розничных продаж
 * 
 * Сценарии:
 * - Добавление товара в чек с проверкой срока годности
 * - Блокировка продажи просроченных товаров
 * - Автоматическое FIFO-списание при оплате
 * - Контроль кормов и лакомств
 */
final class RetailSalesIntegrationExample
{
    private FIFOShelfLifeService $fifoService;

    public function __construct(FIFOShelfLifeService $fifoService)
    {
        $this->fifoService = $fifoService;
    }

    /**
     * Пример 1: Добавление товара в чек с проверкой срока годности
     */
    public function addToCart(
        int $productId,
        int $quantity,
        int $cashierId,
        int $cartId
    ): array {
        $item = InventoryItemModel::find($productId);
        if (!$item) {
            throw new \InvalidArgumentException('Товар не найден');
        }

        $domainItem = $item->toDomain();

        // Жёсткая проверка срока годности перед добавлением в чек
        try {
            $this->fifoService->validateBeforeSale($domainItem, $quantity);
        } catch (ShelfLifeException $e) {
            throw new \RuntimeException(
                "Товар нельзя добавить в чек: {$e->getMessage()}"
            );
        } catch (InsufficientStockWithExpiryException $e) {
            throw new \RuntimeException(
                "Недостаточно товара с действующим сроком годности: {$e->getMessage()}"
            );
        }

        // Проверка доступного количества
        $availableStock = InventoryBatchModel::where('inventory_item_id', $item->id)
            ->usable()
            ->sum('current_quantity');

        if ($availableStock < $quantity) {
            throw new \RuntimeException(
                "Недостаточно товара. Доступно: {$availableStock}, запрошено: {$quantity}"
            );
        }

        // Информация о следующей истекающей партии для клиента
        $nextBatch = $this->fifoService->getNextExpiringBatch($item->id);
        $expiryWarning = null;
        if ($nextBatch && $nextBatch->isExpiringSoon(30)) {
            $expiryWarning = "Товар с сроком годности до {$nextBatch->expiry_date->format('d.m.Y')}";
        }

        // Добавление в чек (резервирование)
        $this->reserveInCart($cartId, $productId, $quantity, $cashierId);

        return [
            'product_id' => $productId,
            'product_name' => $item->name,
            'sku' => $item->sku,
            'quantity' => $quantity,
            'price' => $item->selling_price,
            'total' => $item->selling_price * $quantity,
            'available_stock' => $availableStock,
            'expiry_warning' => $expiryWarning,
            'is_controlled' => $item->is_controlled,
        ];
    }

    /**
     * Пример 2: Оформление продажи с автоматическим FIFO-списанием
     */
    public function processSale(
        int $orderId,
        int $cashierId,
        array $cartItems // [['product_id' => 1, 'quantity' => 2], ...]
    ): array {
        $results = [];
        $failedItems = [];

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            foreach ($cartItems as $cartItem) {
                try {
                    $item = InventoryItemModel::find($cartItem['product_id']);
                    if (!$item) {
                        $failedItems[] = [
                            'product_id' => $cartItem['product_id'],
                            'reason' => 'Товар не найден',
                        ];
                        continue;
                    }

                    $domainItem = $item->toDomain();

                    // Финальная проверка перед списанием
                    if ($domainItem->isExpired()) {
                        $failedItems[] = [
                            'product_id' => $cartItem['product_id'],
                            'product_name' => $item->name,
                            'reason' => 'Товар просрочен',
                        ];
                        continue;
                    }

                    // FIFO-списание
                    $result = $this->fifoService->autoDeduct(
                        $domainItem,
                        $cartItem['quantity'],
                        'sale',
                        [
                            'order_id' => $orderId,
                            'cashier_id' => $cashierId,
                        ]
                    );

                    $results[] = [
                        'product_id' => $cartItem['product_id'],
                        'product_name' => $item->name,
                        'quantity' => $cartItem['quantity'],
                        'price' => $item->selling_price,
                        'total' => $item->selling_price * $cartItem['quantity'],
                        'batch_number' => $result->deductedBatches[0]['batch_number'],
                        'expiry_date' => $result->deductedBatches[0]['expiry_date'],
                        'status' => 'success',
                    ];

                } catch (InsufficientStockWithExpiryException $e) {
                    $failedItems[] = [
                        'product_id' => $cartItem['product_id'],
                        'reason' => 'Недостаточно товара с действующим сроком',
                        'message' => $e->getMessage(),
                    ];
                    throw $e; // Откат транзакции
                } catch (\Exception $e) {
                    $failedItems[] = [
                        'product_id' => $cartItem['product_id'],
                        'reason' => 'Ошибка',
                        'message' => $e->getMessage(),
                    ];
                    throw $e; // Откат транзакции
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            // Запись продажи в систему
            $this->recordSale($orderId, $results, $cashierId);

            return [
                'order_id' => $orderId,
                'status' => 'completed',
                'items' => $results,
                'failed_items' => $failedItems,
                'total_amount' => collect($results)->sum('total'),
            ];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            throw new \RuntimeException("Ошибка оформления продажи: {$e->getMessage()}");
        }
    }

    /**
     * Пример 3: Проверка товара перед продажей (для кассира)
     */
    public function checkProductForSale(int $productId, int $quantity): array
    {
        $item = InventoryItemModel::find($productId);
        if (!$item) {
            return [
                'can_sell' => false,
                'reason' => 'Товар не найден',
            ];
        }

        $domainItem = $item->toDomain();

        // Проверка срока годности
        if ($domainItem->isExpired()) {
            return [
                'can_sell' => false,
                'reason' => 'Товар просрочен',
                'expiry_date' => $domainItem->expiryDate?->format('Y-m-d'),
                'days_expired' => abs($domainItem->getDaysUntilExpiry()),
            ];
        }

        // Предупреждение если срок подходит к концу
        $warning = null;
        if ($domainItem->isExpiringSoon(30)) {
            $warning = "Срок годности истекает через {$domainItem->getDaysUntilExpiry()} дней";
        }

        // Проверка доступного количества
        $availableStock = InventoryBatchModel::where('inventory_item_id', $item->id)
            ->usable()
            ->sum('current_quantity');

        if ($availableStock < $quantity) {
            return [
                'can_sell' => false,
                'reason' => 'Недостаточно количества',
                'available' => $availableStock,
                'required' => $quantity,
                'warning' => $warning,
            ];
        }

        return [
            'can_sell' => true,
            'product_name' => $item->name,
            'sku' => $item->sku,
            'price' => $item->selling_price,
            'available_stock' => $availableStock,
            'warning' => $warning,
            'is_controlled' => $item->is_controlled,
            'next_expiry_batch' => $this->fifoService->getNextExpiringBatch($item->id)?->batch_number,
        ];
    }

    /**
     * Пример 4: Возврат товара (возврат в FIFO-пул)
     */
    public function processReturn(
        int $orderId,
        int $productId,
        int $quantity,
        string $reason,
        int $cashierId
    ): array {
        $item = InventoryItemModel::find($productId);
        if (!$item) {
            throw new \InvalidArgumentException('Товар не найден');
        }

        // В реальной реализации нужно хранить информацию о том,
        // из какой партии был продан товар для корректного возврата
        // Здесь упрощённая логика - возврат в последнюю активную партию

        $lastBatch = InventoryBatchModel::where('inventory_item_id', $item->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastBatch) {
            throw new \RuntimeException('Нет активной партии для возврата');
        }

        // Возврат в партию
        $lastBatch->increment('current_quantity', $quantity);
        
        // Если партия была в карантине, вернуть в активный статус
        if ($lastBatch->status === 'quarantine') {
            $lastBatch->update(['status' => 'active']);
        }

        // Обновление общего количества товара
        $item->increment('quantity', $quantity);

        // Логирование возврата
        \Illuminate\Support\Facades\Log::info('Возврат товара', [
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'batch_number' => $lastBatch->batch_number,
            'reason' => $reason,
            'cashier_id' => $cashierId,
        ]);

        return [
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity_returned' => $quantity,
            'batch_number' => $lastBatch->batch_number,
            'status' => 'success',
        ];
    }

    /**
     * Пример 5: Отчёт по продажам с информацией о партиях
     */
    public function generateSalesReport(
        string $startDate,
        string $endDate,
        ?int $productId = null
    ): array {
        // В реальной реализации нужна таблица для хранения FIFO-движений
        // Здесь пример структуры отчёта

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'product_id' => $productId,
            'total_sales' => 0,
            'total_revenue' => 0,
            'batches_used' => [],
            'items_sold' => [],
        ];
    }

    /**
     * Пример 6: Скидка на товары с истекающим сроком
     */
    public function applyExpiryDiscount(
        int $productId,
        float $basePrice
    ): array {
        $item = InventoryItemModel::find($productId);
        if (!$item) {
            return ['discount_applied' => false, 'final_price' => $basePrice];
        }

        $domainItem = $item->toDomain();

        if (!$domainItem->expiryDate) {
            return ['discount_applied' => false, 'final_price' => $basePrice];
        }

        $daysLeft = $domainItem->getDaysUntilExpiry();
        $discountPercent = 0;

        // Скидка в зависимости от оставшегося срока
        if ($daysLeft <= 3) {
            $discountPercent = 50; // 50% скидка за 3 дня
        } elseif ($daysLeft <= 7) {
            $discountPercent = 30; // 30% скидка за 7 дней
        } elseif ($daysLeft <= 14) {
            $discountPercent = 20; // 20% скидка за 14 дней
        } elseif ($daysLeft <= 30) {
            $discountPercent = 10; // 10% скидка за 30 дней
        }

        if ($discountPercent > 0) {
            $discountAmount = $basePrice * ($discountPercent / 100);
            $finalPrice = $basePrice - $discountAmount;

            return [
                'discount_applied' => true,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice,
                'days_left' => $daysLeft,
                'expiry_date' => $domainItem->expiryDate->format('Y-m-d'),
            ];
        }

        return [
            'discount_applied' => false,
            'final_price' => $basePrice,
            'days_left' => $daysLeft,
        ];
    }

    /**
     * Пример 7: Проверка чека перед оплатой
     */
    public function validateCartBeforePayment(
        int $cartId,
        array $cartItems
    ): array {
        $validationResults = [];

        foreach ($cartItems as $item) {
            $check = $this->checkProductForSale($item['product_id'], $item['quantity']);

            $validationResults[] = [
                'product_id' => $item['product_id'],
                'product_name' => $check['product_name'] ?? 'Неизвестно',
                'can_sell' => $check['can_sell'],
                'reason' => $check['reason'] ?? null,
                'warning' => $check['warning'] ?? null,
            ];
        }

        $allCanSell = collect($validationResults)->every('can_sell');

        return [
            'cart_id' => $cartId,
            'all_items_available' => $allCanSell,
            'validations' => $validationResults,
            'has_warnings' => collect($validationResults)->filter(fn ($v) => $v['warning'] !== null)->count() > 0,
        ];
    }

    private function reserveInCart(int $cartId, int $productId, int $quantity, int $cashierId): void
    {
        // Резервирование товара в чеке
        // В реальной реализации нужна таблица cart_items с резервированием
        \Illuminate\Support\Facades\Log::info('Товар добавлен в чек', [
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'cashier_id' => $cashierId,
        ]);
    }

    private function recordSale(int $orderId, array $items, int $cashierId): void
    {
        // Запись продажи в систему
        \Illuminate\Support\Facades\Log::info('Продажа оформлена', [
            'order_id' => $orderId,
            'items_count' => count($items),
            'total_amount' => collect($items)->sum('total'),
            'cashier_id' => $cashierId,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
