<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Listeners\Supermarket;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Modules\Supermarket\Events\OrderStatusUpdated;
use Modules\CatCRM\Application\Services\SupermarketCRMService;
use Modules\CatCRM\Domain\Entities\Deal;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener for Supermarket Order Status Updated event
 * Updates CRM deal status
 */
final class OrderStatusUpdatedListener implements ShouldQueue
{
    use WithAuditLogging;
    use WithTelemetry;

    public function __construct(
        private readonly SupermarketCRMService $crmService
    ) {}

    public function handle(OrderStatusUpdated $event): void
    {
        $this->withSpan(
            'supermarket_crm.order_status_updated_listener',
            function () use ($event) {
                $deal = Deal::where('marketplace_order_id', $event->orderId)
                    ->where('tenant_id', $event->tenantId)
                    ->first();

                if ($deal) {
                    $this->crmService->updateOrderStatus(
                        $deal,
                        $event->newStatus,
                        $event->reason ?? null
                    );
                }
            },
            $this->getStandardAttributes('supermarket', 'order_status_updated_listener')
        );
    }
}
