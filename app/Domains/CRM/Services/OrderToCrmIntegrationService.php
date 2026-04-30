<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;

use App\Domains\CRM\Events\OrderCreatedForCrm;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

/**
 * OrderToCrmIntegrationService — сервис интеграции заказов с CRM.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class OrderToCrmIntegrationService
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Обработка нового заказа для интеграции с CRM.
     */
    public function handleNewOrder(
        int $orderId,
        int $tenantId,
        ?int $businessGroupId,
        int $customerId,
        string $orderTitle,
        int $orderValue,
        string $vertical,
        ?string $correlationId = null
    ): void {
        if (! config('crm.enabled', true)) {
            $this->logger->debug('CRM is disabled, skipping order integration', [
                'order_id' => $orderId,
            ]);

            return;
        }

        // Проверяем, существует ли уже сделка для этого заказа
        $existingDeal = DB::table('crm_deals')
            ->where('marketplace_order_id', $orderId)
            ->first();

        if ($existingDeal !== null) {
            $this->logger->info('CRM deal already exists for order', [
                'order_id' => $orderId,
                'deal_id' => $existingDeal->id,
            ]);

            return;
        }

        // Диспатчим событие для асинхронного создания сделки
        $this->events->dispatch(new OrderCreatedForCrm(
            orderId: $orderId,
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            customerId: $customerId,
            orderTitle: $orderTitle,
            orderValue: $orderValue,
            vertical: $vertical,
            correlationId: $correlationId,
        ));

        $this->logger->info('OrderCreatedForCrm event dispatched', [
            'order_id' => $orderId,
            'tenant_id' => $tenantId,
            'vertical' => $vertical,
        ]);
    }

    /**
     * Обновление сделки при изменении статуса заказа.
     */
    public function handleOrderStatusChange(
        int $orderId,
        string $newStatus,
        ?string $correlationId = null
    ): void {
        if (! config('crm.integration.orders.sync_on_order_status_change', true)) {
            return;
        }

        $deal = DB::table('crm_deals')
            ->where('marketplace_order_id', $orderId)
            ->first();

        if ($deal === null) {
            $this->logger->debug('No CRM deal found for order status change', [
                'order_id' => $orderId,
            ]);

            return;
        }

        // Маппинг статусов заказа на статусы сделок
        $dealStatus = match ($newStatus) {
            'completed', 'delivered' => 'won',
            'cancelled', 'refunded' => 'lost',
            'processing', 'confirmed' => 'in_progress',
            default => null,
        };

        if ($dealStatus === null) {
            $this->logger->debug('No CRM status mapping for order status', [
                'order_id' => $orderId,
                'order_status' => $newStatus,
            ]);

            return;
        }

        DB::table('crm_deals')
            ->where('id', $deal->id)
            ->update([
                'status' => $dealStatus,
                'actual_close_date' => $dealStatus === 'won' || $dealStatus === 'lost' ? now() : null,
                'updated_at' => now(),
            ]);

        $this->logger->info('CRM deal status updated from order status', [
            'deal_id' => $deal->id,
            'order_id' => $orderId,
            'new_status' => $dealStatus,
        ]);
    }

    /**
     * Обновление сделки при оплате заказа.
     */
    public function handleOrderPayment(
        int $orderId,
        int $amount,
        ?string $correlationId = null
    ): void {
        if (! config('crm.integration.orders.sync_on_order_payment', true)) {
            return;
        }

        $deal = DB::table('crm_deals')
            ->where('marketplace_order_id', $orderId)
            ->first();

        if ($deal === null) {
            return;
        }

        // Обновляем статус сделки на "in_progress" при оплате
        DB::table('crm_deals')
            ->where('id', $deal->id)
            ->update([
                'status' => 'in_progress',
                'updated_at' => now(),
            ]);

        $this->logger->info('CRM deal updated on order payment', [
            'deal_id' => $deal->id,
            'order_id' => $orderId,
            'amount' => $amount,
        ]);
    }
}
