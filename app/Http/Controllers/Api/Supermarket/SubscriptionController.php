<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Supermarket;

use App\Domains\Supermarket\Data\CreateSubscriptionData;
use App\Domains\Supermarket\Models\SupermarketOrder;
use App\Domains\Supermarket\Services\AgeVerificationService;
use App\Domains\Supermarket\Services\SubscriptionService;
use App\Domains\Supermarket\Services\SubscriptionNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final readonly class SubscriptionController
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private AgeVerificationService $ageVerificationService,
        private SubscriptionNotificationService $notificationService
    ) {
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:supermarket_orders,id',
            'frequency' => 'required|in:weekly,biweekly,monthly',
            'delivery_day' => 'required|integer|min:1|max:31',
            'delivery_time_slot' => 'nullable|string|in:morning,day,evening',
            'address_id' => 'required|exists:user_addresses,id',
            'payment_method' => 'required|string',
            'promo_code' => 'nullable|string',
            'pay_now' => 'nullable|boolean',
        ]);

        $order = SupermarketOrder::with(['items.product', 'buyer'])->findOrFail($validated['order_id']);

        // Age verification check for 18+ products
        $hasAgeRestricted = $order->items->contains(fn($item) => $item->product->is_age_restricted ?? false);
        if ($hasAgeRestricted) {
            $verificationResult = $this->ageVerificationService->checkOrder(
                data: [],
                user: $order->buyer
            );

            if (!$verificationResult->verified) {
                throw ValidationException::withMessages([
                    'age_verification' => 'Age verification required for 18+ products',
                ]);
            }
        }

        // Validate minimum requirements
        $totalAmount = $order->total_amount;
        $totalQuantity = $order->items->sum('quantity');

        if ($totalAmount < 1500) {
            throw ValidationException::withMessages([
                'amount' => 'Minimum subscription amount is 1500 ₽',
            ]);
        }

        if ($totalQuantity < 8) {
            throw ValidationException::withMessages([
                'quantity' => 'Minimum 8 items required for subscription',
            ]);
        }

        try {
            DB::beginTransaction();

            $subscription = $this->subscriptionService->createFromOrder($order, [
                'frequency' => $validated['frequency'],
                'delivery_day' => $validated['delivery_day'],
            ]);

            $this->notificationService->sendSubscriptionCreated($subscription);

            if ($validated['pay_now'] ?? false) {
                $nextOrder = $this->subscriptionService->createNextOrder($subscription);
                dispatch(new \App\Domains\Supermarket\Jobs\ChargeRecurringPaymentJob($subscription, $nextOrder))
                    ->onQueue('supermarket-high');
            }

            DB::commit();

            Log::info('Subscription created via API', [
                'subscription_id' => $subscription->id,
                'order_id' => $order->id,
                'buyer_id' => $order->buyer_id,
            ]);

            return response()->json([
                'message' => 'Subscription created successfully',
                'subscription' => [
                    'id' => $subscription->id,
                    'frequency' => $subscription->frequency,
                    'next_delivery_at' => $subscription->next_delivery_at->toIso8601String(),
                    'total_amount' => $subscription->total_amount,
                    'status' => $subscription->status,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create subscription', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
            ]);

            throw $e;
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscriptions()
            ->with(['items.product', 'orders'])
            ->findOrFail($id);

        $stats = $this->subscriptionService->getSubscriptionStats($subscription);

        return response()->json([
            'id' => $subscription->id,
            'frequency' => $subscription->frequency,
            'delivery_day' => $subscription->delivery_day,
            'next_delivery_at' => $subscription->next_delivery_at->toIso8601String(),
            'total_amount' => $subscription->total_amount,
            'status' => $subscription->status,
            'pause_until' => $subscription->pause_until?->toIso8601String(),
            'is_b2b' => $subscription->is_b2b,
            'items' => $subscription->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price_per_unit_at_creation,
                ];
            }),
            'stats' => $stats,
            'delivery_history' => $subscription->orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'created_at' => $order->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscriptions()->findOrFail($id);

        $validated = $request->validate([
            'frequency' => 'sometimes|in:weekly,biweekly,monthly',
            'delivery_day' => 'sometimes|integer|min:1|max:31',
            'address_id' => 'sometimes|exists:user_addresses,id',
        ]);

        $subscription->update($validated);

        return response()->json(['message' => 'Subscription updated']);
    }
}
