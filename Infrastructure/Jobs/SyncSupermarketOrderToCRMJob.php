<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Jobs;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Modules\CatCRM\Application\Services\SupermarketCRMService;
use Modules\CatCRM\Domain\Entities\Customer;
use Modules\Supermarket\Infrastructure\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to sync Supermarket Order to CRM asynchronously
 */
final class SyncSupermarketOrderToCRMJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use WithAuditLogging, WithTelemetry;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly int $orderId
    ) {}

    public function handle(SupermarketCRMService $crmService): void
    {
        $this->withSpan(
            'supermarket_crm.sync_order_to_crm_job',
            function () use ($crmService) {
                $order = Order::with(['user', 'items'])->find($this->orderId);

                if (!$order) {
                    return;
                }

                // Find or create customer
                $customer = $crmService->getOrCreateCustomer(
                    [
                        'user_id' => $order->user_id,
                        'type' => 'individual',
                        'first_name' => $order->user?->first_name,
                        'last_name' => $order->user?->last_name,
                        'email' => $order->user?->email,
                        'phone' => $order->user?->phone,
                        'address' => $order->delivery_address,
                    ],
                    $order->tenant_id,
                    $order->business_group_id
                );

                // Check if order has age-restricted items
                $hasAgeRestricted = $order->items->contains(fn($item) => $item->product?->is_age_restricted ?? false);

                // Check if order has honesty marks
                $hasHonestyMarks = $order->items->contains(fn($item) => $item->product?->requires_honesty_mark ?? false);

                // Create CRM deal
                $crmService->createOrderDeal(
                    $customer,
                    [
                        'order_number' => $order->order_number,
                        'order_type' => 'one_time',
                        'order_status' => $order->status,
                        'is_age_restricted' => $hasAgeRestricted,
                        'age_verification_required' => $hasAgeRestricted,
                        'age_verification_status' => $hasAgeRestricted ? 'pending' : 'not_required',
                        'contains_honesty_marks' => $hasHonestyMarks,
                        'honesty_marks_count' => $order->items->filter(fn($item) => $item->product?->requires_honesty_mark ?? false)->count(),
                        'subtotal' => $order->subtotal,
                        'discount_amount' => $order->discount_amount,
                        'delivery_fee' => $order->delivery_fee,
                        'service_fee' => 0,
                        'tax_amount' => $order->tax_amount,
                        'total_amount' => $order->total_amount,
                        'payment_status' => $order->payment_status,
                        'payment_method' => $order->payment_method,
                        'delivery_address' => $order->delivery_address,
                        'delivery_phone' => $order->delivery_phone,
                        'delivery_name' => $order->delivery_name,
                        'delivery_scheduled_at' => $order->delivery_scheduled_at,
                        'special_requests' => $order->special_requests,
                        'allergies' => $order->allergies ?? [],
                        'dietary_restrictions' => $order->dietary_restrictions ?? [],
                        'metadata' => [
                            'supermarket_order_id' => $order->id,
                        ],
                    ],
                    config('crm.supermarket.pipeline_id'),
                    null
                );

                $this->logAction('crm_order_synced', $order->id, [
                    'tenant_id' => $order->tenant_id,
                    'vertical' => 'supermarket',
                    'crm_customer_id' => $customer->id,
                ]);
            },
            $this->getStandardAttributes('supermarket', 'sync_order_to_crm_job')
        );
    }

    public function failed(\Throwable $exception): void
    {
        $this->recordSpanException($exception);

        \Log::error('Failed to sync order to CRM', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);
    }
}
