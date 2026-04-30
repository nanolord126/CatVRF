<?php

declare(strict_types=1);

namespace App\Domains\CRM\Listeners;

use App\Domains\CRM\Events\OrderCreatedForCrm;
use App\Domains\CRM\Services\DealService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * CreateDealFromOrder — слушатель для автоматического создания сделки из заказа.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class CreateDealFromOrder implements ShouldQueue
{
    public string $queue = 'crm';

    public function __construct(
        private readonly DealService $dealService,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(OrderCreatedForCrm $event): void
    {
        if (! config('crm.integration.orders.auto_create_deals', true)) {
            $this->logger->info('CRM auto-create deals from orders is disabled', [
                'order_id' => $event->orderId,
                'tenant_id' => $event->tenantId,
            ]);

            return;
        }

        try {
            $deal = $this->dealService->createDealFromOrder(
                tenantId: $event->tenantId,
                businessGroupId: $event->businessGroupId,
                customerId: $event->customerId,
                orderId: $event->orderId,
                orderTitle: $event->orderTitle,
                orderValue: $event->orderValue,
                vertical: $event->vertical,
                correlationId: $event->correlationId,
            );

            $this->logger->info('CRM deal created from order', [
                'deal_id' => $deal->id,
                'order_id' => $event->orderId,
                'tenant_id' => $event->tenantId,
                'vertical' => $event->vertical,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to create CRM deal from order', [
                'order_id' => $event->orderId,
                'tenant_id' => $event->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
