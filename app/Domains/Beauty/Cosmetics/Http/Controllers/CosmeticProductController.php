<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Cosmetics\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class CosmeticProductController extends Controller
{
    public function __construct(
        private CosmeticService $cosmeticService,
        private BeautyTryOnService $tryOnService,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $tenantId = $request->user()?->tenant_id ?? 0;

                $products = CosmeticProduct::where('tenant_id', $tenantId)
                    ->when($request->input('brand'),     fn ($q, $v) => $q->where('brand', $v))
                    ->when($request->input('category'),  fn ($q, $v) => $q->where('category', $v))
                    ->when($request->input('skin_type'), fn ($q, $v) => $q->whereJsonContains('skin_types', $v))
                    ->when($request->input('search'),    fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
                    ->orderByDesc('rating')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $products, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('Cosmetics: index error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка загрузки', 'correlation_id' => $correlationId], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $product = CosmeticProduct::findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $product, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Продукт не найден', 'correlation_id' => $correlationId], 404);
            }
        }

        public function tryOn(Request $request, int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $product = CosmeticProduct::findOrFail($id);

                $validated = $request->validate([
                    'photo_url'  => 'nullable|url',
                    'shade'      => 'nullable|string',
                ]);

                $result = $this->tryOnService->tryOn($product, $validated['photo_url'] ?? null, $validated['shade'] ?? null, $correlationId);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                $this->logger->error('Cosmetics: tryOn error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка AR-примерки', 'correlation_id' => $correlationId], 500);
            }
        }

        public function order(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $userId = $request->user()?->id;

                $fraudResult = $this->fraud->check(
                    userId: $userId,
                    operationType: 'cosmetic_order',
                    amount: (int) $request->input('total_kopecks', 0),
                    correlationId: $correlationId,
                );
                if ($fraudResult['decision'] === 'block') {
                    return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Операция заблокирована', 'correlation_id' => $correlationId], 403);
                }

                $validated = $request->validate([
                    'product_id'       => 'required|integer|exists:cosmetic_products,id',
                    'quantity'         => 'required|integer|min:1|max:50',
                    'delivery_address' => 'required|string',
                    'shade'            => 'nullable|string',
                ]);

                $order = $this->db->transaction(function () use ($validated, $userId, $correlationId, $request): CosmeticOrder {
                    $product = CosmeticProduct::findOrFail($validated['product_id']);
                    $order   = CosmeticOrder::create([
                        'uuid'             => Str::uuid(),
                        'tenant_id'        => $request->user()?->tenant_id ?? 0,
                        'client_id'        => $userId,
                        'product_id'       => $validated['product_id'],
                        'quantity'         => $validated['quantity'],
                        'shade'            => $validated['shade'] ?? null,
                        'delivery_address' => $validated['delivery_address'],
                        'total_kopecks'    => $product->price * $validated['quantity'],
                        'status'           => 'pending',
                        'correlation_id'   => $correlationId,
                    ]);

                    $this->logger->info('Cosmetics: Order created', [
                        'order_id'       => $order->id,
                        'product_id'     => $validated['product_id'],
                        'user_id'        => $userId,
                        'correlation_id' => $correlationId,
                    ]);

                    return $order;
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $order, 'correlation_id' => $correlationId], 201);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
            } catch (\Throwable $e) {
                $this->logger->error('Cosmetics: order error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка заказа', 'correlation_id' => $correlationId], 500);
            }
        }

        public function myOrders(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $orders = CosmeticOrder::where('client_id', $request->user()?->id)
                    ->with('product')
                    ->orderByDesc('created_at')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $orders, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка', 'correlation_id' => $correlationId], 500);
            }
        }
}
