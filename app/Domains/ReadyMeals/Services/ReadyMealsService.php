<?php declare(strict_types=1);

namespace App\Domains\ReadyMeals\Services;

use App\Domains\ReadyMeals\Models\MealProvider;
use App\Domains\ReadyMeals\Models\Meal;
use App\Domains\ReadyMeals\Models\MealOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class ReadyMealsService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
    ) {}

    public function createOrder(int $providerId, array $items, array $data, string $correlationId = ""): MealOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        if (RateLimiter::tooManyAttempts("meals:order:".auth()->id(), 15)) {
            throw new \RuntimeException("Too many orders", 429);
        }
        RateLimiter::hit("meals:order:".auth()->id(), 3600);

        return DB::transaction(function () use ($providerId, $items, $data, $correlationId) {
            $provider = MealProvider::findOrFail($providerId);
            $total = 0;

            foreach ($items as $item) {
                $meal = Meal::where('id', $item['meal_id'])
                    ->where('provider_id', $providerId)->firstOrFail();
                $total += $meal->price_kopecks * $item['quantity'];
            }

            $fraud = $this->fraud->check([
                'user_id' => auth()->id() ?? 0,
                'operation_type' => 'meal_order_create',
                'correlation_id' => $correlationId,
                'amount' => $total,
            ]);

            if ($fraud['decision'] === 'block') {
                Log::channel('audit')->error('Meal order blocked', [
                    'user_id' => auth()->id(),
                    'score' => $fraud['score'],
                    'correlation_id' => $correlationId,
                ]);
                throw new \RuntimeException("Security block", 403);
            }

            $order = MealOrder::create([
                'uuid' => Str::uuid(),
                'tenant_id' => tenant()->id,
                'provider_id' => $providerId,
                'client_id' => auth()->id() ?? 0,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'items_json' => $items,
                'delivery_datetime' => $data['delivery_datetime'],
                'tags' => ['ready_meals' => true, 'items_count' => count($items)],
            ]);

            Log::channel('audit')->info('Meal order created', [
                'order_id' => $order->id,
                'provider_id' => $providerId,
                'total_kopecks' => $total,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    public function completeOrder(int $orderId, string $correlationId = ""): MealOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return DB::transaction(function () use ($orderId, $correlationId) {
            $order = MealOrder::findOrFail($orderId);

            if ($order->payment_status !== 'completed') {
                throw new \RuntimeException("Order not paid", 400);
            }

            $order->update(['status' => 'completed', 'correlation_id' => $correlationId]);

            $this->wallet->credit(tenant()->id, $order->payout_kopecks, 'meals_payout', [
                'correlation_id' => $correlationId,
                'order_id' => $order->id,
            ]);

            Log::channel('audit')->info('Meal order completed', [
                'order_id' => $order->id,
                'payout_kopecks' => $order->payout_kopecks,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    public function cancelOrder(int $orderId, string $correlationId = ""): MealOrder
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return DB::transaction(function () use ($orderId, $correlationId) {
            $order = MealOrder::findOrFail($orderId);

            if ($order->status === 'completed') {
                throw new \RuntimeException("Cannot cancel completed", 400);
            }

            $order->update(['status' => 'cancelled', 'payment_status' => 'refunded', 'correlation_id' => $correlationId]);

            if ($order->payment_status === 'completed') {
                $this->wallet->credit(tenant()->id, $order->total_kopecks, 'meals_refund', [
                    'correlation_id' => $correlationId,
                    'order_id' => $order->id,
                ]);
            }

            Log::channel('audit')->info('Meal order cancelled', [
                'order_id' => $order->id,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    public function getOrder(int $orderId): MealOrder
    {
        return MealOrder::with(['provider'])->findOrFail($orderId);
    }

    public function getUserOrders(int $clientId)
    {
        return MealOrder::where('client_id', $clientId)->orderBy('created_at', 'desc')->take(10)->get();
    }

    public function getProviderMeals(int $providerId)
    {
        return Meal::where('provider_id', $providerId)->orderBy('name')->get();
    }
}
