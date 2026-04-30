<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Listeners\Supermarket;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Modules\Supermarket\Events\SubscriptionCreated;
use Modules\CatCRM\Application\Services\SupermarketCRMService;
use Modules\CatCRM\Domain\Entities\Customer;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener for Supermarket Subscription Created event
 * Creates corresponding CRM deal for subscription
 */
final class SubscriptionCreatedListener implements ShouldQueue
{
    use WithAuditLogging;
    use WithTelemetry;

    public function __construct(
        private readonly SupermarketCRMService $crmService
    ) {}

    public function handle(SubscriptionCreated $event): void
    {
        $this->withSpan(
            'supermarket_crm.subscription_created_listener',
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
                        'address' => $event->deliveryAddress ?? null,
                    ],
                    $event->tenantId,
                    $event->businessGroupId
                );

                // Create CRM deal for subscription
                $this->crmService->createSubscriptionDeal(
                    $customer,
                    [
                        'subscription_number' => $event->subscriptionNumber,
                        'subscription_type' => $event->subscriptionType,
                        'subscription_delivery_day' => $event->deliveryDay,
                        'subscription_next_delivery' => $event->nextDelivery,
                        'total_amount' => $event->amount,
                        'delivery_address' => $event->deliveryAddress,
                        'delivery_phone' => $event->deliveryPhone ?? null,
                        'delivery_name' => $event->deliveryName ?? null,
                        'delivery_scheduled_at' => $event->nextDelivery,
                    ]
                );
            },
            $this->getStandardAttributes('supermarket', 'subscription_created_listener')
        );
    }
}
