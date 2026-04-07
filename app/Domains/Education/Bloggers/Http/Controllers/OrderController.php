<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Controllers;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class OrderController extends Controller
{

    public function __construct(
            private readonly LiveCommerceService $commerceService, private readonly LoggerInterface $logger) {}

        /**
         * Create live-commerce order
         */
        public function store(CreateOrderRequest $request, string $roomId): JsonResponse
        {
            try {
                $correlationId = (string) Str::uuid();
                $userId = $request->user()?->id;

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

                $this->logger->info('Live order created', [
                    'correlation_id' => $correlationId,
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'stream_id' => $stream->id,
                    'amount' => $order->total,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $order,
                    'payment_id' => $order->payment_id,
                    'requires_confirmation' => $order->status === 'pending',
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Create order failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $request->user()?->id,
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
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
                $userId = $request->user()?->id;

                $order = \App\Domains\Content\Bloggers\Models\StreamOrder::findOrFail($orderId);

                // Verify order belongs to current user
                if ($order->user_id !== $userId) {
                    $this->logger->warning('Unauthorized order confirmation attempt', [
                        'user_id' => $userId,
                        'order_id' => $orderId,
                        'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                    ]);

                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Unauthorized',
                    ], 403);
                }

                $order = $this->commerceService->confirmPayment(
                    orderId: $orderId,
                    correlationId: $correlationId,
                );

                $this->logger->info('Order payment confirmed', [
                    'correlation_id' => $correlationId,
                    'order_id' => $orderId,
                    'user_id' => $userId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $order,
                    'status' => 'paid',
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Confirm payment failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
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
                if ($order->user_id !== $request->user()?->id && $order->stream->blogger_id !== $request->user()?->id) {
                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Unauthorized',
                    ], 403);
                }

                return new \Illuminate\Http\JsonResponse([
                    'data' => $order,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
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
                $userId = $request->user()?->id;

                $orders = \App\Domains\Content\Bloggers\Models\StreamOrder::where('user_id', $userId)
                    ->where('tenant_id', tenant()->id)
                    ->with('product', 'stream')
                    ->orderByDesc('created_at')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'data' => $orders->items(),
                    'pagination' => [
                        'total' => $orders->total(),
                        'current_page' => $orders->currentPage(),
                    ],
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
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
                $userId = $request->user()?->id;

                $order = \App\Domains\Content\Bloggers\Models\StreamOrder::findOrFail($orderId);

                if ($order->user_id !== $userId) {
                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Unauthorized',
                    ], 403);
                }

                if ($order->isPaid()) {
                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Cannot cancel paid order',
                    ], 400);
                }

                $order->update([
                    'status' => 'cancelled',
                    'cancelled_at' => Carbon::now(),
                ]);

                $this->logger->info('Order cancelled', [
                    'correlation_id' => $correlationId,
                    'order_id' => $orderId,
                    'user_id' => $userId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $order,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'message' => 'Failed to cancel order',
                ], 400);
            }
        }
}
