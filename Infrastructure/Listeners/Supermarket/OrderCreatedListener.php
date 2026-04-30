<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Listeners\Supermarket;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Modules\Supermarket\Events\OrderCreated;
use Modules\CatCRM\Application\Services\SupermarketCRMService;
use Modules\CatCRM\Domain\Entities\Customer;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener for Supermarket Order Created event
 * Creates corresponding CRM deal
 */
final class OrderCreatedListener implements ShouldQueue
{
    use WithAuditLogging;
    use WithTelemetry;

    public function __construct(
        private readonly SupermarketCRMService $crmService
    ) {}

    public function handle(OrderCreated $event): void
    {
        $this->withSpan(
            'supermarket_crm.order_created_listener',
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

                // Create CRM deal
                $this->crmService->createOrderDeal(
                    $customer,
                    [
                        'order_number' => $event->orderNumber,
                        'order_type' => 'one_time',
                        'order_status' => 'pending',
                        'is_age_restricted' => $event->hasAgeRestrictedItems ?? false,
                        'age_verification_required' => $event->hasAgeRestrictedItems ?? false,
                        'age_verification_status' => $event->hasAgeRestrictedItems ? 'pending' : 'not_required',
                        'contains_honesty_marks' => $event->hasHonestyMarkItems ?? false,
                        'honesty_marks_count' => $event->honestyMarksCount ?? 0,
                        'subtotal' => $event->subtotal,
                        'discount_amount' => $event->discountAmount ?? 0,
                        'delivery_fee' => $event->deliveryFee ?? 0,
                        'service_fee' => 0,
                        'tax_amount' => $event->taxAmount ?? 0,
                        'total_amount' => $event->totalAmount,
                        'payment_status' => 'pending',
                        'payment_method' => $event->paymentMethod ?? null,
                        'delivery_address' => $event->deliveryAddress,
                        'delivery_phone' => $event->deliveryPhone ?? null,
                        'delivery_name' => $event->deliveryName ?? null,
                        'delivery_scheduled_at' => $event->deliveryScheduledAt,
                        'special_requests' => $event->specialRequests ?? null,
                        'allergies' => $event->allergies ?? [],
                        'dietary_restrictions' => $event->dietaryRestrictions ?? [],
                    ],
                    config('crm.supermarket.pipeline_id'),
                    null
                );
            },
            $this->getStandardAttributes('supermarket', 'order_created_listener')
        );
    }
}
