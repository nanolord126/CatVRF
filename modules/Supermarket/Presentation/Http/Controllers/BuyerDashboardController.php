<?php

declare(strict_types=1);

namespace Modules\Supermarket\Presentation\Http\Controllers;

use App\Models\User;
use Modules\Supermarket\Application\Services\SellerAnalyticsService;
use Modules\Supermarket\Infrastructure\Models\Subscription;
use Modules\Supermarket\Infrastructure\Models\SupermarketOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class BuyerDashboardController
{
    public function index(Request $request): JsonResponse
    {
        $buyer = Auth::user();
        
        $activeSubscriptions = Subscription::byBuyer($buyer->id)
            ->active()
            ->with(['items.product'])
            ->orderBy('next_delivery_at')
            ->limit(5)
            ->get();

        $recentOrders = SupermarketOrder::where('buyer_id', $buyer->id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->with(['items.product'])
            ->get();

        return response()->json([
            'bonuses' => $buyer->bonus_balance ?? 0,
            'active_subscriptions' => $activeSubscriptions,
            'recent_orders' => $recentOrders,
            'subscription_count' => $activeSubscriptions->count(),
        ]);
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $buyer = Auth::user();
        
        $subscriptions = Subscription::byBuyer($buyer->id)
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($subscriptions);
    }

    public function orders(Request $request): JsonResponse
    {
        $buyer = Auth::user();
        
        $query = SupermarketOrder::where('buyer_id', $buyer->id)
            ->with(['items.product', 'seller']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($orders);
    }

    public function pauseSubscription(Request $request, int $id): JsonResponse
    {
        $subscription = Subscription::where('id', $id)
            ->where('buyer_id', Auth::id())
            ->firstOrFail();

        $days = $request->input('days', 7);
        
        $subscription->pause($days);

        return response()->json([
            'message' => 'Subscription paused',
            'pause_until' => $subscription->pause_until,
        ]);
    }

    public function resumeSubscription(Request $request, int $id): JsonResponse
    {
        $subscription = Subscription::where('id', $id)
            ->where('buyer_id', Auth::id())
            ->firstOrFail();

        $subscription->resume();

        return response()->json([
            'message' => 'Subscription resumed',
            'next_delivery_at' => $subscription->next_delivery_at,
        ]);
    }

    public function cancelSubscription(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string',
        ]);

        $subscription = Subscription::where('id', $id)
            ->where('buyer_id', Auth::id())
            ->firstOrFail();

        $subscription->cancel($request->input('reason'));

        return response()->json([
            'message' => 'Subscription cancelled',
        ]);
    }
}
