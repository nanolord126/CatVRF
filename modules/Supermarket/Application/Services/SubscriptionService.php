<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Modules\Supermarket\Application\DTOs\CreateSubscriptionData;
use Modules\Supermarket\Application\DTOs\SubscriptionItemData;
use Modules\Supermarket\Domain\Enums\SubscriptionStatus;
use Modules\Supermarket\Infrastructure\Models\Subscription;
use Modules\Supermarket\Infrastructure\Models\SubscriptionItem;
use Modules\Supermarket\Infrastructure\Models\SupermarketOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final class SubscriptionService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }

    public function createFromOrder(SupermarketOrder $order, array $data): Subscription
    {
        return $this->withSpan(
            'subscription.create_from_order',
            function () use ($order, $data) {
                // Fraud check before creating subscription
                $this->fraudControl->check([
                    'operation_type' => 'subscription_create_from_order',
                    'vertical' => 'supermarket',
                    'user_id' => $order->buyer_id,
                    'amount' => $order->total_amount,
                    'order_id' => $order->id,
                    'correlation_id' => $data['correlation_id'] ?? $this->generateCorrelationId(),
                ]);

                return DB::transaction(function () use ($order, $data) {
                    $subscription = Subscription::create([
                        'buyer_id' => $order->buyer_id,
                        'seller_id' => $order->seller_id,
                        'status' => SubscriptionStatus::ACTIVE->value,
                        'frequency' => $data['frequency'] ?? 'weekly',
                        'delivery_day' => $data['delivery_day'] ?? 1,
                        'next_delivery_at' => $this->calculateNextDeliveryDate($data),
                        'total_amount' => $order->total_amount,
                        'is_b2b' => $order->is_b2b ?? false,
                    ]);

                    foreach ($order->items as $item) {
                        $subscription->items()->create([
                            'product_id' => $item->product_id,
                            'variant_id' => $item->variant_id ?? null,
                            'quantity' => $item->quantity,
                            'price_per_unit_at_creation' => $item->price_per_unit,
                        ]);
                    }

                    Log::info('Subscription created from order', [
                        'subscription_id' => $subscription->id,
                        'order_id' => $order->id,
                        'buyer_id' => $order->buyer_id,
                    ]);

                    // Invalidate cache with tags
                    \Illuminate\Support\Facades\Cache::tags(['supermarket', 'subscriptions', "user:{$order->buyer_id}"])->flush();

                    $this->logCreated(
                        entityType: 'subscription',
                        entityId: $subscription->id,
                        userId: $subscription->buyer_id,
                        context: [
                            'order_id' => $order->id,
                            'buyer_id' => $subscription->buyer_id,
                        ],
                    );

                    return $subscription;
                });
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'subscription_create_from_order',
                userId: (string) $order->buyer_id,
                correlationId: $data['correlation_id'] ?? null,
            ),
        );
    }

    public function create(CreateSubscriptionData $data): Subscription
    {
        return $this->withSpan(
            'subscription.create',
            function () use ($data) {
                // Calculate total amount for fraud check
                $totalAmount = $data->items->sum(function ($item) {
                    if (!($item instanceof SubscriptionItemData)) {
                        $item = SubscriptionItemData::fromArray($item);
                    }
                    return $item->quantity * $item->pricePerUnit;
                });

                // Fraud check before creating subscription
                $this->fraudControl->check([
                    'operation_type' => 'subscription_create',
                    'vertical' => 'supermarket',
                    'user_id' => $data->buyerId,
                    'amount' => $totalAmount,
                    'correlation_id' => $data->correlationId ?? $this->generateCorrelationId(),
                ]);

                return DB::transaction(function () use ($data) {
                    $subscription = Subscription::create([
                        'buyer_id' => $data->buyerId,
                        'seller_id' => $data->sellerId,
                        'status' => SubscriptionStatus::ACTIVE->value,
                        'frequency' => $data->frequency->value,
                        'delivery_day' => $data->deliveryDay,
                        'next_delivery_at' => $this->calculateNextDeliveryDateFromData($data),
                        'total_amount' => $totalAmount,
                        'is_b2b' => false,
                    ]);

                    foreach ($data->items as $itemData) {
                        if (!($itemData instanceof SubscriptionItemData)) {
                            $itemData = SubscriptionItemData::fromArray($itemData);
                        }

                        $subscription->items()->create([
                            'product_id' => $itemData->productId,
                            'variant_id' => $itemData->variantId ?? null,
                            'quantity' => $itemData->quantity,
                            'price_per_unit_at_creation' => $itemData->pricePerUnit,
                        ]);
                    }

                    Log::info('Subscription created', [
                        'subscription_id' => $subscription->id,
                        'buyer_id' => $data->buyerId,
                        'frequency' => $subscription->frequency,
                    ]);

                    // Invalidate cache with tags
                    \Illuminate\Support\Facades\Cache::tags(['supermarket', 'subscriptions', "user:{$data->buyerId}"])->flush();

                    $this->logCreated(
                        entityType: 'subscription',
                        entityId: $subscription->id,
                        userId: $subscription->buyer_id,
                        context: [
                            'frequency' => $subscription->frequency,
                            'total_amount' => $subscription->total_amount,
                        ],
                    );

                    return $subscription;
                });
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'subscription_create',
                userId: (string) $data->buyerId,
                correlationId: $data->correlationId ?? null,
            ),
        );
    }

    public function updateItems(Subscription $subscription, array $items): Subscription
    {
        return $this->withSpan(
            'subscription.update_items',
            function () use ($subscription, $items) {
                // Fraud check before updating items
                $totalAmount = collect($items)->sum(function ($item) {
                    if (!($item instanceof SubscriptionItemData)) {
                        $item = SubscriptionItemData::fromArray($item);
                    }
                    return $item->quantity * $item->pricePerUnit;
                });

                $this->fraudControl->check([
                    'operation_type' => 'subscription_update_items',
                    'vertical' => 'supermarket',
                    'user_id' => $subscription->buyer_id,
                    'amount' => $totalAmount,
                    'subscription_id' => $subscription->id,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                return DB::transaction(function () use ($subscription, $items, $totalAmount) {
                    $subscription->items()->delete();

                    foreach ($items as $itemData) {
                        if (!($itemData instanceof SubscriptionItemData)) {
                            $itemData = SubscriptionItemData::fromArray($itemData);
                        }

                        $subscription->items()->create([
                            'product_id' => $itemData->productId,
                            'variant_id' => $itemData->variantId ?? null,
                            'quantity' => $itemData->quantity,
                            'price_per_unit_at_creation' => $itemData->pricePerUnit,
                        ]);
                    }

                    $subscription->update([
                        'total_amount' => $totalAmount,
                    ]);

                    Log::info('Subscription items updated', [
                        'subscription_id' => $subscription->id,
                        'buyer_id' => $subscription->buyer_id,
                        'total_amount' => $totalAmount,
                    ]);

                    // Invalidate cache with tags
                    \Illuminate\Support\Facades\Cache::tags(['supermarket', 'subscriptions', "user:{$subscription->buyer_id}"])->flush();

                    $this->logAction(
                        action: 'subscription_items_updated',
                        entityType: 'subscription',
                        entityId: $subscription->id,
                        userId: $subscription->buyer_id,
                        context: [
                            'total_amount' => $totalAmount,
                            'items_count' => count($items),
                        ],
                    );

                    return $subscription->fresh();
                });
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'subscription_update_items',
                userId: (string) $subscription->buyer_id,
            ),
        );
    }

    public function cancel(Subscription $subscription, string $reason = null): void
    {
        $this->withSpan(
            'subscription.cancel',
            function () use ($subscription, $reason) {
                // Fraud check before cancellation
                $this->fraudControl->check([
                    'operation_type' => 'subscription_cancel',
                    'vertical' => 'supermarket',
                    'user_id' => $subscription->buyer_id,
                    'subscription_id' => $subscription->id,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                $subscription->update([
                    'status' => SubscriptionStatus::CANCELLED->value,
                    'cancelled_at' => now(),
                    'cancel_reason' => $reason,
                ]);

                Log::info('Subscription cancelled', [
                    'subscription_id' => $subscription->id,
                    'buyer_id' => $subscription->buyer_id,
                    'reason' => $reason,
                ]);

                // Invalidate cache with tags
                \Illuminate\Support\Facades\Cache::tags(['supermarket', 'subscriptions', "user:{$subscription->buyer_id}"])->flush();

                $this->logAction(
                    action: 'subscription_cancelled',
                    entityType: 'subscription',
                    entityId: $subscription->id,
                    userId: $subscription->buyer_id,
                    context: [
                        'reason' => $reason,
                    ],
                );
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'subscription_cancel',
                userId: (string) $subscription->buyer_id,
            ),
        );
    }

    public function pause(Subscription $subscription, int $days = 30): void
    {
        $this->withSpan(
            'subscription.pause',
            function () use ($subscription, $days) {
                $subscription->update([
                    'status' => SubscriptionStatus::PAUSED->value,
                    'paused_at' => now(),
                    'resumes_at' => now()->addDays($days),
                ]);

                Log::info('Subscription paused', [
                    'subscription_id' => $subscription->id,
                    'buyer_id' => $subscription->buyer_id,
                    'days' => $days,
                ]);

                // Invalidate cache with tags
                \Illuminate\Support\Facades\Cache::tags(['supermarket', 'subscriptions', "user:{$subscription->buyer_id}"])->flush();

                $this->logAction(
                    action: 'subscription_paused',
                    entityType: 'subscription',
                    entityId: $subscription->id,
                    userId: $subscription->buyer_id,
                    context: [
                        'days' => $days,
                    ],
                );
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'subscription_pause',
                userId: (string) $subscription->buyer_id,
            ),
        );
    }

    public function resume(Subscription $subscription): void
    {
        $this->withSpan(
            'subscription.resume',
            function () use ($subscription) {
                $subscription->update([
                    'status' => SubscriptionStatus::ACTIVE->value,
                    'resumed_at' => now(),
                    'next_delivery_at' => now()->addDays(7),
                ]);

                Log::info('Subscription resumed', [
                    'subscription_id' => $subscription->id,
                    'buyer_id' => $subscription->buyer_id,
                ]);

                // Invalidate cache with tags
                \Illuminate\Support\Facades\Cache::tags(['supermarket', 'subscriptions', "user:{$subscription->buyer_id}"])->flush();

                $this->logAction(
                    action: 'subscription_resumed',
                    entityType: 'subscription',
                    entityId: $subscription->id,
                    userId: $subscription->buyer_id,
                );
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'subscription_resume',
                userId: (string) $subscription->buyer_id,
            ),
        );
    }

    private function calculateNextDeliveryDate(array $data): Carbon
    {
        $frequency = $data['frequency'] ?? 'weekly';
        $deliveryDay = $data['delivery_day'] ?? 1;

        return match ($frequency) {
            'weekly' => now()->addDays($deliveryDay - now()->dayOfWeek),
            'biweekly' => now()->addWeeks(2)->addDays($deliveryDay - now()->dayOfWeek),
            'monthly' => now()->addMonth()->day($deliveryDay),
            default => now()->addWeek(),
        };
    }

    private function calculateNextDeliveryDateFromData(CreateSubscriptionData $data): Carbon
    {
        $frequency = $data->frequency->value;
        $deliveryDay = $data->deliveryDay;

        return match ($frequency) {
            'weekly' => now()->addDays($deliveryDay - now()->dayOfWeek),
            'biweekly' => now()->addWeeks(2)->addDays($deliveryDay - now()->dayOfWeek),
            'monthly' => now()->addMonth()->day($deliveryDay),
            default => now()->addWeek(),
        };
    }
}
