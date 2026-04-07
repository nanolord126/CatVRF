<?php declare(strict_types=1);

namespace App\Domains\Confectionery\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class OrderController extends Controller
{

    public function __construct(
            private readonly ConfectioneryService $service, private readonly LoggerInterface $logger) {}

        public function store(CakeOrderStoreRequest $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                $data = $request->validated();

                $order = $this->service->createOrder(
                    companyId: $data['confectionery_shop_id'],
                    menuId: $data['cake_id'],
                    data: [
                        'office_name' => $data['recipient_name'] ?? 'Получатель',
                        'office_address' => $data['delivery_address'],
                        'delivery_datetime' => $data['delivery_datetime'],
                        'person_count' => 1,
                        'special_requests' => $data['special_requests'] ?? null,
                    ],
                    correlationId: $correlationId
                );

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'order_id' => $order->id,
                    'uuid' => $order->uuid,
                    'total_kopecks' => $order->total_kopecks,
                    'status' => $order->status,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\RuntimeException $e) {
                $this->logger->error('Cake order store error', [
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
                $order = CakeOrder::findOrFail($orderId);

                if ($order->client_id !== $request->user()?->id && !$request->user()->isShopOwner($order->confectionery_shop_id)) {
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
                        'delivery_datetime' => $order->delivery_datetime?->toIso8601String(),
                        'office_name' => $order->office_name,
                        'office_address' => $order->office_address,
                        'menu_items' => $order->menu_items_json,
                        'special_requests' => $order->special_requests,
                        'created_at' => $order->created_at->toIso8601String(),
                    ],
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Cake order show error', [
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

        public function uploadCustomDesign(int $orderId): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                if (!$request->hasFile('design_photo')) {
                    return new \Illuminate\Http\JsonResponse([
                        'success' => false,
                        'message' => 'Design photo required',
                    ], 400);
                }

                $order = CakeOrder::findOrFail($orderId);

                if ($order->client_id !== $request->user()?->id) {
                    return new \Illuminate\Http\JsonResponse([
                        'success' => false,
                        'message' => 'Unauthorized',
                    ], 403);
                }

                $file = $request->file('design_photo');
                $path = $file->store('cake-designs', 'public');

                $order->update([
                    'menu_items_json' => array_merge(
                        $order->menu_items_json ?? [],
                        ['custom_design_photo' => $path, 'design_description' => $request->input('description')]
                    ),
                    'tags' => array_merge($order->tags ?? [], ['custom_design' => true]),
                ]);

                $this->logger->info('Custom cake design uploaded', [
                    'order_id' => $order->id,
                    'user_id' => $request->user()?->id,
                    'photo_path' => $path,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Design uploaded',
                    'photo_path' => $path,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Custom design upload error', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Upload failed',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function cancel(int $orderId): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                $order = CakeOrder::findOrFail($orderId);

                if ($order->client_id !== $request->user()?->id) {
                    return new \Illuminate\Http\JsonResponse([
                        'success' => false,
                        'message' => 'Unauthorized',
                    ], 403);
                }

                $updated = $this->service->cancelOrder($orderId, $correlationId);

                $this->logger->info('Cake order cancelled via API', [
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
                $this->logger->error('Cake order cancellation error', [
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
}
