<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Services;

use App\Domains\Supermarket\Models\Subscription;
use App\Domains\Supermarket\Models\SubscriptionItem;
use App\Domains\Supermarket\Models\SupermarketOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final readonly class SubscriptionService
{
    public function createFromOrder(SupermarketOrder $order, array $data): Subscription
    {
        return DB::transaction(function () use ($order, $data) {
            $subscription = Subscription::create([
                'buyer_id' => $order->buyer_id,
                'seller_id' => $order->seller_id,
                'status' => 'active',
                'frequency' => $data['frequency'],
                'delivery_day' => $data['delivery_day'],
                'next_delivery_at' => $this->calculateNextDeliveryDate($data),
                'total_amount' => $order->total_amount,
                'is_b2b' => $order->is_b2b ?? false,
            ]);

            foreach ($order->items as $item) {
                $subscription->items()->create([
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                    'price_per_unit_at_creation' => $item->price_per_unit,
                ]);
            }

            Log::info('Subscription created from order', [
                'subscription_id' => $subscription->id,
                'order_id' => $order->id,
                'buyer_id' => $order->buyer_id,
            ]);

            return $subscription;
        });
    }

    public function processDueSubscriptions(): void
    {
        $subscriptions = Subscription::where('status', 'active')
            ->where('next_delivery_at', '<=', now())
            ->with(['items.product', 'buyer'])
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                $this->createNextOrder($subscription);
            } catch (\Exception $e) {
                Log::error('Failed to process subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function createNextOrder(Subscription $subscription): SupermarketOrder
    {
        return DB::transaction(function () use ($subscription) {
            $order = SupermarketOrder::create([
                'buyer_id' => $subscription->buyer_id,
                'seller_id' => $subscription->seller_id,
                'subscription_id' => $subscription->id,
                'is_subscription' => true,
                'status' => 'pending',
                'total_amount' => $subscription->items->sum(function ($item) {
                    return $item->product->price * $item->quantity;
                }),
                'delivery_cost' => 0,
                'sub_vertical' => 'supermarket',
                'created_at' => now(),
            ]);

            foreach ($subscription->items as $subItem) {
                $order->items()->create([
                    'product_id' => $subItem->product_id,
                    'quantity' => $subItem->quantity,
                    'price_per_unit' => $subItem->product->price,
                    'total_price' => $subItem->product->price * $subItem->quantity,
                ]);
            }

            dispatch(new \App\Domains\Supermarket\Jobs\ChargeRecurringPaymentJob($subscription, $order))
                ->onQueue('supermarket-high');

            $subscription->update([
                'next_delivery_at' => $this->calculateNextDeliveryDate($subscription),
            ]);

            Log::info('Created order from subscription', [
                'subscription_id' => $subscription->id,
                'order_id' => $order->id,
            ]);

            return $order;
        });
    }

    private function calculateNextDeliveryDate(Subscription|array $data): Carbon
    {
        $now = now();
        $frequency = is_array($data) ? $data['frequency'] : $data->frequency;
        $deliveryDay = is_array($data) ? $data['delivery_day'] : $data->delivery_day;

        return match ($frequency) {
            'weekly' => $now->next((int) $deliveryDay),
            'biweekly' => $now->next((int) $deliveryDay)->addWeek(),
            'monthly' => $now->copy()->day((int) $deliveryDay)->addMonth(),
            default => $now->addWeek(),
        };
    }

    public function pause(Subscription $subscription, ?int $days = null): void
    {
        $subscription->update([
            'status' => 'paused',
            'pause_until' => $days ? now()->addDays($days) : null,
        ]);

        Log::info('Subscription paused', [
            'subscription_id' => $subscription->id,
            'pause_days' => $days,
        ]);
    }

    public function resume(Subscription $subscription): void
    {
        $subscription->update([
            'status' => 'active',
            'pause_until' => null,
            'next_delivery_at' => $this->calculateNextDeliveryDate($subscription),
        ]);

        Log::info('Subscription resumed', [
            'subscription_id' => $subscription->id,
        ]);
    }

    public function cancel(Subscription $subscription): void
    {
        $subscription->update([
            'status' => 'cancelled',
        ]);

        Log::info('Subscription cancelled', [
            'subscription_id' => $subscription->id,
        ]);
    }

    public function updateNextDeliveryDate(Subscription $subscription): void
    {
        $subscription->update([
            'next_delivery_at' => $this->calculateNextDeliveryDate($subscription),
        ]);

        Log::info('Subscription next delivery date updated', [
            'subscription_id' => $subscription->id,
            'next_delivery_at' => $subscription->next_delivery_at,
        ]);
    }

    public function getSubscriptionStats(Subscription $subscription): array
    {
        $totalOrders = $subscription->orders()->count();
        $completedOrders = $subscription->orders()->where('status', 'delivered')->count();
        $totalSpent = $subscription->orders()->where('status', 'delivered')->sum('total_amount');

        return [
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'total_spent' => $totalSpent,
            'average_order_value' => $completedOrders > 0 ? $totalSpent / $completedOrders : 0,
            'status' => $subscription->status,
            'next_delivery_at' => $subscription->next_delivery_at?->toIso8601String(),
        ];
    }
}
