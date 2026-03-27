<?php

declare(strict_types=1);

namespace App\Domains\Content\Bloggers\Http\Controllers;

use App\Domains\Content\Bloggers\Models\Stream;
use App\Domains\Content\Bloggers\Services\LiveCommerceService;
use App\Domains\Content\Bloggers\Http\Requests\CreateOrderRequest;
use App\Domains\Content\Bloggers\Http\Requests\ConfirmPaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class OrderController
{
    public function __construct(
        private readonly LiveCommerceService $commerceService,
    ) {}

    /**
     * Create live-commerce order
     */
    public function store(CreateOrderRequest $request, string $roomId): JsonResponse
    {
        try {
            $correlationId = (string) Str::uuid();
            $userId = auth()->id();

            $stream = Stream::where('room_id', $roomId)
                ->where('status', 'live')
                ->where('tenant_id', tenant()->id)
                ->firstOrFail();

            $order = $this->commerceService->createAndPayOrder(
                streamId: (int) $stream->id,
                userId: $userId,
                productId: $request->integer('product_id'),
                quantity: $request->integer('quantity', 1),
                paymentMethod: $request->string('payment_method')->value(),
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('Live order created', [
                'correlation_id' => $correlationId,
                'order_id' => $order->id,
                'user_id' => $userId,
                'stream_id' => $stream->id,
                'amount' => $order->total,
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'data' => $order,
                'payment_id' => $order->payment_id,
                'requires_confirmation' => $order->status === 'pending',
            ], 201);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Create order failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Confirm payment and mark order as paid
     */
    public function confirmPayment(ConfirmPaymentRequest $request, int $orderId): JsonResponse
    {
        try {
            $correlationId = (string) Str::uuid();
            $userId = auth()->id();

            $order = \App\Domains\Content\Bloggers\Models\StreamOrder::findOrFail($orderId);

            // Verify order belongs to current user
            if ($order->user_id !== $userId) {
                Log::channel('audit')->warning('Unauthorized order confirmation attempt', [
                    'user_id' => $userId,
                    'order_id' => $orderId,
                ]);

                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            $order = $this->commerceService->confirmPayment(
                orderId: $orderId,
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('Order payment confirmed', [
                'correlation_id' => $correlationId,
                'order_id' => $orderId,
                'user_id' => $userId,
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'data' => $order,
                'status' => 'paid',
            ]);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Confirm payment failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to confirm payment',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get order status
     */
    public function show(int $orderId): JsonResponse
    {
        try {
            $order = \App\Domains\Content\Bloggers\Models\StreamOrder::with('product', 'stream')
                ->findOrFail($orderId);

            // Check authorization
            if ($order->user_id !== auth()->id() && $order->stream->blogger_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Order not found',
            ], 404);
        }
    }

    /**
     * Get user's recent orders
     */
    public function getUserOrders(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();

            $orders = \App\Domains\Content\Bloggers\Models\StreamOrder::where('user_id', $userId)
                ->where('tenant_id', tenant()->id)
                ->with('product', 'stream')
                ->orderByDesc('created_at')
                ->paginate(20);

            return response()->json([
                'data' => $orders->items(),
                'pagination' => [
                    'total' => $orders->total(),
                    'current_page' => $orders->currentPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch orders',
            ], 500);
        }
    }

    /**
     * Cancel order (before payment)
     */
    public function cancel(int $orderId): JsonResponse
    {
        try {
            $correlationId = (string) Str::uuid();
            $userId = auth()->id();

            $order = \App\Domains\Content\Bloggers\Models\StreamOrder::findOrFail($orderId);

            if ($order->user_id !== $userId) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            if ($order->isPaid()) {
                return response()->json([
                    'message' => 'Cannot cancel paid order',
                ], 400);
            }

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            Log::channel('audit')->info('Order cancelled', [
                'correlation_id' => $correlationId,
                'order_id' => $orderId,
                'user_id' => $userId,
            ]);

            return response()->json([
                'correlation_id' => $correlationId,
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel order',
            ], 400);
        }
    }
}
