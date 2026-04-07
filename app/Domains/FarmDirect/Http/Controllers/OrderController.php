<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class OrderController extends Controller
{

    public function __construct(
            private readonly FarmService $service, private readonly LoggerInterface $logger) {}

        public function store(FarmOrderStoreRequest $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                $data = $request->validated();

                $order = $this->service->createOrder(
                    farmId: $data['farm_id'],
                    items: $data['items'],
                    data: [
                        'delivery_address' => $data['delivery_address'],
                        'delivery_datetime' => $data['delivery_datetime'],
                    ],
                    correlationId: $correlationId
                );

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'order_id' => $order->id,
                    'uuid' => $order->uuid,
                    'total_kopecks' => $order->total_kopecks,
                    'items_count' => count($data['items']),
                    'status' => $order->status,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\RuntimeException $e) {
                $this->logger->error('Farm order store error', [
                    'error' => $e->getMessage(),
                    'user_id' => $request->user()?->id,
                    'correlation_id' => $correlationId,
                ]);

                $code = (int) $e->getCode();
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], $code ?: 400);
            }
        }

        public function show(int $orderId): JsonResponse
        {
            try {
                $order = FarmOrder::findOrFail($orderId);

                if ($order->client_id !== $request->user()?->id && !$request->user()->isFarmOwner($order->farm_id)) {
                    return new \Illuminate\Http\JsonResponse([
                        'success' => false,
                        'message' => 'Unauthorized',
                    ], 403);
                }

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'order' => [
                        'id' => $order->id,
                        'uuid' => $order->uuid,
                        'status' => $order->status,
                        'total_kopecks' => $order->total_kopecks,
                        'payout_kopecks' => $order->payout_kopecks,
                        'commission_kopecks' => $order->commission_kopecks,
                        'payment_status' => $order->payment_status,
                        'delivery_address' => $order->delivery_address,
                        'delivery_datetime' => $order->delivery_datetime?->toIso8601String(),
                        'items' => $order->items_json,
                        'created_at' => $order->created_at->toIso8601String(),
                    ],
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Farm order show error', [
                    'order_id' => $orderId,
                    'user_id' => $request->user()?->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }
        }

        public function getProducts(int $farmId): JsonResponse
        {
            try {
                $products = $this->service->getFarmProducts($farmId);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'products' => $products->map(fn($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'price_kopecks' => $p->price_kopecks,
                        'unit' => $p->unit,
                        'available_quantity' => $p->available_quantity,
                        'is_certified_organic' => $p->is_certified_organic,
                        'description' => $p->description,
                    ]),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Products not found',
                ], 404);
            }
        }

        public function cancel(int $orderId): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                $order = FarmOrder::findOrFail($orderId);

                if ($order->client_id !== $request->user()?->id) {
                    return new \Illuminate\Http\JsonResponse([
                        'success' => false,
                        'message' => 'Unauthorized',
                    ], 403);
                }

                $updated = $this->service->cancelOrder($orderId, $correlationId);

                $this->logger->info('Farm order cancelled via API', [
                    'order_id' => $orderId,
                    'user_id' => $request->user()?->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Order cancelled',
                    'order' => $updated,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Farm order cancellation error', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 400);
            }
        }

        public function getUserOrders(): JsonResponse
        {
            try {
                $orders = $this->service->getUserOrders($request->user()?->id);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'orders' => $orders->map(fn($o) => [
                        'id' => $o->id,
                        'status' => $o->status,
                        'total_kopecks' => $o->total_kopecks,
                        'items_count' => count($o->items_json ?? []),
                        'delivery_datetime' => $o->delivery_datetime?->toIso8601String(),
                        'created_at' => $o->created_at->toIso8601String(),
                    ]),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Orders not found',
                ], 404);
            }
        }
}
