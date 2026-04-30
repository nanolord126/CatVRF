<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Listeners\Supermarket;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Modules\Supermarket\Events\ReturnCreated;
use Modules\CatCRM\Application\Services\SupermarketCRMService;
use Modules\CatCRM\Domain\Entities\Customer;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener for Supermarket Return Created event
 * Creates corresponding CRM deal for return
 */
final class ReturnCreatedListener implements ShouldQueue
{
    use WithAuditLogging;
    use WithTelemetry;

    public function __construct(
        private readonly SupermarketCRMService $crmService
    ) {}

    public function handle(ReturnCreated $event): void
    {
        $this->withSpan(
            'supermarket_crm.return_created_listener',
            function () use ($event) {
                // Find or create customer
                $customer = $this->crmService->getOrCreateCustomer(
                    [
                        'user_id' => $event->userId,
                        'type' => 'individual',
                        'first_name' => $event->customerFirstName ?? null,
                        'last_name' => $event->customerLastName ?? null,
                        'email' => $event->customerEmail ?? null,
                        'phone' => $event->customerPhone ?? null,
                    ],
                    $event->tenantId,
                    $event->businessGroupId
                );

                // Create CRM deal for return
                $this->crmService->createReturnDeal(
                    $customer,
                    [
                        'return_number' => $event->returnNumber,
                        'return_reason' => $event->reason,
                        'refund_amount' => $event->refundAmount,
                        'total_amount' => -$event->refundAmount,
                    ]
                );
            },
            $this->getStandardAttributes('supermarket', 'return_created_listener')
        );
    }
}
