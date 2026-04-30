<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Services;

use App\Domains\Payment\Services\PaymentServiceAdapter;
use App\Domains\Shared\Inventory\DTOs\CreateStockMovementDto;
use App\Domains\Shared\Inventory\Services\InventoryService;
use App\Domains\Supermarket\DTOs\CreateReturnData;
use App\Domains\Supermarket\Enums\ReturnStatus;
use App\Domains\Supermarket\Events\ReturnApproved;
use App\Domains\Supermarket\Events\ReturnCreated;
use App\Domains\Supermarket\Events\ReturnRejected;
use App\Domains\Supermarket\Models\Return as ReturnModel;
use App\Domains\Supermarket\Models\SupermarketOrder;
use App\Domains\Supermarket\Services\InventoryReservationService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * ReturnService - Главный сервис возвратов для Supermarket.
 *
 * Координирует процесс возврата товаров:
 * - Создание возврата с валидацией политики
 * - Одобрение/отклонение возврата
 * - Автоматический возврат денег через PaymentAdapter
 * - Возврат товаров на склад
 * - Уведомления
 *
 * Флоу:
 * 1. createReturn() → Проверка политики → Создание записи → Уведомление
 * 2. approve() → Возврат денег → Возврат на склад → Уведомление
 * 3. reject() → Отклонение → Уведомление
 */
final readonly class ReturnService
{
    use WithAuditLogging;

    /**
     * @param FraudControlService $fraudControl Сервис контроля фрода
     * @param AuditService $audit Сервис аудита
     * @param DatabaseManager $db Менеджер базы данных
     * @param InventoryService $inventoryService Сервис инвентаря
     * @param PaymentServiceAdapter $paymentAdapter Сервис платежей
     * @param LoggerInterface $logger Логгер
     * @param ReturnPolicyService $policyService Сервис политики возврата
     * @param InventoryReservationService $inventoryReservationService Сервис резервирования инвентаря
     */
    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly InventoryService $inventoryService,
        private readonly PaymentServiceAdapter $paymentAdapter,
        private readonly LoggerInterface $logger,
        private readonly ReturnPolicyService $policyService,
        private readonly InventoryReservationService $inventoryReservationService,
    ) {}

    /**
     * Создать возврат.
     *
     * @param CreateReturnData $data Данные для создания возврата
     * @return ReturnModel
     * @throws \InvalidArgumentException Если политика не разрешает возврат
     * @throws \Exception Если произошла ошибка при создании
     */
    public function createReturn(CreateReturnData $data): ReturnModel
    {
        $correlationId = $data->correlationId ?? $this->generateCorrelationId();

        $this->logger->info('Creating return', [
            'correlation_id' => $correlationId,
            'order_id' => $data->orderId,
            'buyer_id' => $data->buyerId,
            'reason_type' => $data->reasonType->value,
        ]);

        // Получить заказ для проверки политики
        $order = SupermarketOrder::with('items')->findOrFail($data->orderId);

        // Fraud check
        $this->fraudControl->check([
            'operation_type' => 'return_create',
            'vertical' => 'supermarket',
            'user_id' => $data->buyerId,
            'amount' => $data->getTotalAmount(),
            'correlation_id' => $correlationId,
        ]);

        // Проверка политики возврата
        $policyResult = $this->policyService->canCreateReturn(
            $order,
            $data->reasonType,
            $data->isColdChain
        );

        if (!$policyResult['allowed']) {
            $this->logger->warning('Return not allowed by policy', [
                'correlation_id' => $correlationId,
                'order_id' => $data->orderId,
                'reason' => $policyResult['reason'],
            ]);

            throw new \InvalidArgumentException($policyResult['reason']);
        }

        // Проверка требований к фото
        $requiresPhoto = $this->policyService->requiresPhotoEvidence(
            $data->isColdChain,
            $data->reasonType
        );

        if ($requiresPhoto && !$data->hasImages()) {
            $this->logger->warning('Return requires photo evidence', [
                'correlation_id' => $correlationId,
                'order_id' => $data->orderId,
            ]);

            throw new \InvalidArgumentException('Для этого типа возврата требуются фото доказательства');
        }

        return $this->db->transaction(function () use ($data, $order, $correlationId) {
            $totalAmount = $data->getTotalAmount();
            $priority = $this->policyService->getReturnPriority(
                $data->isColdChain,
                $data->reasonType,
                $data->subVertical
            );

            // Создать возврат
            $return = ReturnModel::create([
                'order_id' => $data->orderId,
                'buyer_id' => $data->buyerId,
                'seller_id' => $data->sellerId,
                'status' => ReturnStatus::PENDING->value,
                'reason_type' => $data->reasonType->value,
                'reason_comment' => $data->comment,
                'total_amount' => $totalAmount,
                'refund_amount' => $totalAmount, // Изначально равно полной сумме
                'is_cold_chain' => $data->isColdChain,
                'return_method' => $data->returnMethod,
                'images' => $data->images,
                'priority' => $priority,
                'sub_vertical' => $data->subVertical ?? $order->sub_vertical,
                'correlation_id' => $correlationId,
            ]);

            // Применить лимиты возврата из политики
            $policy = $policyResult['policy'];
            if ($policy->max_refund_percent < 100) {
                $maxRefund = $policy->calculateMaxRefundAmount($totalAmount);
                $return->update(['refund_amount' => min($totalAmount, $maxRefund)]);
            }

            if ($policy->max_refund_amount && $return->refund_amount > $policy->max_refund_amount) {
                $return->update(['refund_amount' => $policy->max_refund_amount]);
            }

            // Создать товары возврата
            foreach ($data->items as $item) {
                $return->items()->create([
                    'order_item_id' => $item['order_item_id'] ?? null,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price_per_unit' => $item['price'],
                    'refund_amount' => $item['price'] * $item['quantity'],
                    'condition' => $item['condition'],
                    'comment' => $item['comment'] ?? null,
                ]);
            }

            // Очистить кэш политики
            $this->policyService->clearPolicyCache($data->orderId);

            // Логирование
            $this->logCreated(
                entityType: 'return',
                entityId: $return->id,
                context: [
                    'order_id' => $data->orderId,
                    'buyer_id' => $data->buyerId,
                    'total_amount' => $totalAmount,
                    'reason_type' => $data->reasonType->value,
                    'correlation_id' => $correlationId,
                ]
            );

            // Dispatch return created event for notifications
            event(new ReturnCreated($return, $correlationId));

            $this->logger->info('Return created successfully', [
                'correlation_id' => $correlationId,
                'return_id' => $return->id,
                'order_id' => $data->orderId,
            ]);

            return $return->load('items');
        });
    }

    /**
     * Одобрить возврат.
     *
     * @param ReturnModel $return Возврат
     * @return ReturnModel
     * @throws \Exception Если возврат уже обработан
     */
    public function approve(ReturnModel $return): ReturnModel
    {
        $correlationId = $return->correlation_id ?? $this->generateCorrelationId();

        $this->logger->info('Approving return', [
            'correlation_id' => $correlationId,
            'return_id' => $return->id,
            'order_id' => $return->order_id,
        ]);

        if (!$return->isPending()) {
            throw new \InvalidArgumentException('Можно одобрить только возврат в статусе pending');
        }

        return $this->db->transaction(function () use ($return, $correlationId) {
            // Обновить статус
            $return->updateStatus(ReturnStatus::APPROVED);

            // Возврат денег через PaymentAdapter на баланс внутри системы
            $this->processRefund($return, $correlationId);

            // Возврат товаров на склад
            $this->returnItemsToStock($return, $correlationId);

            // Обновить статус на completed
            $return->updateStatus(ReturnStatus::COMPLETED);

            // Логирование
            $this->logAction(
                action: 'return_approved',
                entityType: 'return',
                entityId: $return->id,
                context: [
                    'order_id' => $return->order_id,
                    'buyer_id' => $return->buyer_id,
                    'refund_amount' => $return->refund_amount,
                    'correlation_id' => $correlationId,
                ]
            );

            // Уведомление
            event(new ReturnApproved($return, $correlationId));

            $this->logger->info('Return approved successfully', [
                'correlation_id' => $correlationId,
                'return_id' => $return->id,
            ]);

            return $return->fresh();
        });
    }

    /**
     * Отклонить возврат.
     *
     * @param ReturnModel $return Возврат
     * @param string $reason Причина отклонения
     * @return ReturnModel
     * @throws \InvalidArgumentException Если возврат уже обработан
     */
    public function reject(ReturnModel $return, string $reason): ReturnModel
    {
        $correlationId = $return->correlation_id ?? $this->generateCorrelationId();

        $this->logger->info('Rejecting return', [
            'correlation_id' => $correlationId,
            'return_id' => $return->id,
            'order_id' => $return->order_id,
            'reject_reason' => $reason,
        ]);

        if (!$return->isPending()) {
            throw new \InvalidArgumentException('Можно отклонить только возврат в статусе pending');
        }

        $return->update([
            'status' => ReturnStatus::REJECTED->value,
            'reject_reason' => $reason,
        ]);

        // Логирование
        $this->logAction(
            action: 'return_rejected',
            entityType: 'return',
            entityId: $return->id,
            context: [
                'order_id' => $return->order_id,
                'buyer_id' => $return->buyer_id,
                'reject_reason' => $reason,
            ]
        );

        // Уведомление
        event(new ReturnRejected($return, $correlationId));

        $this->logger->info('Return rejected successfully', [
            'correlation_id' => $correlationId,
            'return_id' => $return->id,
        ]);

        return $return->fresh();
    }

    /**
     * Обработать возврат денег.
     *
     * @param ReturnModel $return Возврат
     * @param string $correlationId Correlation ID
     * @return void
     */
    private function processRefund(ReturnModel $return, string $correlationId): void
    {
        // Получить оригинальный платеж
        $order = $return->order;
        $payment = $order?->payment;

        if (!$payment) {
            $this->logger->error('Payment not found for return', [
           correlation_id' => $correlationId,
        $ rouerKoeu>kd (w )R(imeException('Платеж н * 100);
      $thi-paymntAdape->fPayment(paymnO$amИunаKиpecks,entAdapter для ример:
        // app(PaymentServiceAdapter::class)->refund([
        //$Оayment->id,
        //     'amount' => $return->refund_amount,
        //     'reason' => 'return',
        //     'return_id' => $return->id,
        //     'correlation_id' => $correlationId,
        // ]);

        // Временная заглушка - обновляем статус возврата денег
        $return->update([
            'status' => ReturnStatus::REFUNDED->value,
            'refunded_at' => now(),
        ]);

        $this->logPayment(
            paymentId: $payment->id,
            amount: $return->refund_amount,
            status: 'refunded',
            context: [
                'return_id' => $return->id,
                'correlation_id' => $correlationId,
            ]
        );

        $this->logger->info('Refund processed', [
            'correlation_id' => $correlationId,
            'return_id' => $return->id,
            'payment_id' => $payment->id,
            'amount' => $return->refund_amount,
        ]);
    }

    /**
     * Вернуть товары на склад.
     *
     * @param ReturnModel $return Возврат
     * @param strinrelationId Correlation
     * @return v$warehouseId = $return->order->warehouse_id ?? 1;

                oidНайт inventory_item по product_id и warehouse_id
     */$inventoryItem= db->table('_itms')
                    ->whee('product_d', $item->produt_id)
                    ->whre('warehouse_id', $warehouseId)
                    fist();

                if ($invnoyItem) {
                    $stockMovemetDt = new CreateMovementDto
    private funcon re   tenantII: $remurn->sellersTo,
tk                      inventoryId: (RnveneoryItturnid,
                        waoehduseIe: $wareholseIturn, string $correlationId): void
    {   type: in',
                        :
        foreach retur   sourceType: nittsrn',
                        corralat onI$:t co{rlaioId,
                        suceI: $rtun
                        metadata: [
                   if (tem->proturt_id_id &$ $item->id>quantity > 0) {
                        TODO:o dИерация с Invento->order_id,
                            'product_id' => $itemryproduct_ReservationService для возврата на склад
                        П
                    );

                    $this->inventoryService->addStock($stockMovementDtoример:
                // $this->inventoryService->returnToStock([
                    //     'product_id' => $item->product_id,
                    //     'quantity' => $item->quantity,
                    //     'warehouse_id' => $return->order->warehouse_id,
                    //     'reason' => 'return',
                    //     'return_id' => $returntity,
                        'inventory_id' => $inventoryItem->id,
                    ]);
                } else {
                    $-his->logger->warn>ng('Inveniord item not found for return', [
                        'correlation_id' => $correlationId,
                        'return_id' => $return->id,
                        'product_id' => $item->product_id,
                        'warehouse_id' => $warehouseId,
                    // 
                }]);

                $this->logger->info('Item returned to stock', [
                    'correlation_id' => $correlationId,
                    'return_id' => $return->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ]);
            }
        }
    }

    /**
     * Получить возврат по ID.
     *
     * @param int $returnId ID возврата
     * @return ReturnModel
     */
    public function getReturn(int $returnId): ReturnModel
    {
        $cacheKey = "return:{$returnId}";

        return Cache::tags(['returns', "return:{$returnId}"])->remember(
            $cacheKey,
            3600,
            fn () => ReturnModel::with(['order', 'buyer', 'seller', 'items.product'])
                ->findOrFail($returnId)
        );
    }

    /**
     * Получить возвраты покупателя.
     *
     * @param int $buyerId ID покупателя
     * @param int|null $limit Лимит
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBuyerReturns(int $buyerId, ?int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = ReturnModel::with(['order', 'items'])
            ->forBuyer($buyerId)
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Получить возвраты в ожидании (для модераторов).
     *
     * @param int|null $limit Лимит
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingReturns(?int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = ReturnModel::with(['order', 'buyer', 'items'])
            ->pending()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Очистить кэш возврата.
     *
     * @param int $returnId ID возврата
     * @return void
     */
    public function clearReturnCache(int $returnId): void
    {
        Cache::tags(['returns', "return:{$returnId}"])->flush();
    }

    /**
     * Проверить, следует ли автоматически одобрить возврат.
     *
     * @param ReturnModel $return Возврат
     * @return bool
     */
    private function shouldAutoApprove(ReturnModel $return): bool
    {
        // Авто-одобрение только для брака по холодной цепи в течение 12 часов
        if ($return->is_cold_chain && $return->reason_type === ReturnReason::SPOILED->value) {
            $hours = now()->diffInHours($return->order->created_at);
            return $hours <= 12;
        }

        // Авто-одобрение для B2B клиентов с документами
        if ($return->order->is_b2b && $return->hasImages()) {
            $hours = now()->diffInHours($return->order->created_at);
            return $hours <= 24;
        }

        return false;
    }

    /**
     * Автоматическое одобрение возврата.
     *
     * @param ReturnModel $return Возврат
     * @param string $correlationId Correlation ID
     * @return void
     */
    private function autoApprove(ReturnModel $return, string $correlationId): void
    {
        $this->logger->info('Auto-approving return', [
            'correlation_id' => $correlationId,
            'return_id' => $return->id,
            'reason' => 'cold_chain_defect_or_b2b',
        ]);

        $return->update([
            'status' => ReturnStatus::APPROVED->value,
            'approved_at' => now(),
        ]);

        // Сразу запускаем возврат денег
        $this->processRefund($return, $correlationId);
        
        // Возврат товаров на склад
        $this->returnItemsToStock($return, $correlationId);

        // Обновляем статус на completed
        $return->updateStatus(ReturnStatus::COMPLETED);

        // Логирование авто-одобрения
        $this->logAction(
            action: 'return_auto_approved',
            entityType: 'return',
            entityId: $return->id,
            context: [
                'order_id' => $return->order_id,
                'reason_type' => $return->reason_type,
                'is_cold_chain' => $return->is_cold_chain,
                'is_b2b' => $return->order->is_b2b,
                'correlation_id' => $correlationId,
            ]
        );

        $this->logger->info('Return auto-approved successfully', [
            'correlation_id' => $correlationId,
            'return_id' => $return->id,
        ]);
    }
}
