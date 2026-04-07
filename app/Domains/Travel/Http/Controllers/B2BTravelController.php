<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class B2BTravelController extends Controller
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function storefronts(): JsonResponse
        {
            $storefronts = B2BTravelStorefront::where('tenant_id', tenant()->id)
                ->paginate(15);

            return new \Illuminate\Http\JsonResponse([
                'data' => $storefronts->items(),
                'pagination' => [
                    'total' => $storefronts->total(),
                    'per_page' => $storefronts->perPage(),
                    'current_page' => $storefronts->currentPage(),
                ],
            ]);
        }

        public function createStorefront(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $validated = $request->validate([
                    'company_name' => 'required|string|max:255',
                    'inn' => 'required|string|unique:b2b_travel_storefronts',
                    'description' => 'nullable|string',
                    'wholesale_discount' => 'nullable|numeric|min:0|max:100',
                    'min_order_amount' => 'numeric|min:1000',
                ]);

                return $this->db->transaction(function () use ($validated, $correlationId) {
                    $storefront = B2BTravelStorefront::create([
                        'tenant_id' => tenant()->id,
                        'correlation_id' => $correlationId,
                        ...$validated,
                    ]);

                    $this->logger->info('B2B Travel storefront created', [
                        'storefront_id' => $storefront->id,
                        'correlation_id' => $correlationId,
                        'user_id' => $request->user()?->id,
                    ]);

                    return new \Illuminate\Http\JsonResponse([
                        'data' => $storefront,
                        'message' => 'Витрина создана',
                        'correlation_id' => $correlationId,
                    ], 201);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Travel storefront creation failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $request->user()?->id,
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'message' => 'Ошибка создания витрины',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function createOrder(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $validated = $request->validate([
                    'storefront_id' => 'required|exists:b2b_travel_storefronts,id',
                    'items' => 'required|array|min:1',
                    'items.*.product_id' => 'required|integer',
                    'items.*.quantity' => 'required|integer|min:1',
                ]);

                return $this->db->transaction(function () use ($validated, $correlationId) {
                    $storefront = B2BTravelStorefront::findOrFail($validated['storefront_id']);
                    $commission = ($validated['items'][0]['quantity'] ?? 1) * 0.14;

                    $this->logger->info('B2B Travel order created', [
                        'storefront_id' => $storefront->id,
                        'correlation_id' => $correlationId,
                        'commission' => $commission,
                    ]);

                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Заказ создан',
                        'correlation_id' => $correlationId,
                        'commission' => $commission,
                    ], 201);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Travel order creation failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'message' => 'Ошибка создания заказа',
                ], 500);
            }
        }

        public function myB2BOrders(): JsonResponse
        {
            $orders = B2BTravelStorefront::where('tenant_id', tenant()->id)
                ->latest()
                ->paginate(10);

            return new \Illuminate\Http\JsonResponse([
                'data' => $orders->items(),
                'pagination' => [
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                ],
            ]);
        }

        public function approveOrder(int $id): JsonResponse
        {
            try {
                return $this->db->transaction(function () use ($id) {
                    $order = B2BTravelStorefront::findOrFail($id);
                    $order->update(['status' => 'approved']);

                    $this->logger->info('Travel order approved', [
                        'order_id' => $id,
                        'user_id' => $request->user()?->id,
                        'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                    ]);

                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Заказ одобрен',
                        'data' => $order,
                    ]);
                });
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'message' => 'Ошибка одобрения',
                ], 500);
            }
        }

        public function rejectOrder(int $id, Request $request): JsonResponse
        {
            try {
                $validated = $request->validate([
                    'reason' => 'required|string|max:500',
                ]);

                return $this->db->transaction(function () use ($id, $validated) {
                    $order = B2BTravelStorefront::findOrFail($id);
                    $order->update([
                        'status' => 'rejected',
                        'rejection_reason' => $validated['reason'],
                    ]);

                    $this->logger->info('Travel order rejected', [
                        'order_id' => $id,
                        'reason' => $validated['reason'],
                        'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                    ]);

                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Заказ отклонён',
                    ]);
                });
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'message' => 'Ошибка отклонения',
                ], 500);
            }
        }

        public function verifyInn(int $id): JsonResponse
        {
            try {
                return $this->db->transaction(function () use ($id) {
                    $storefront = B2BTravelStorefront::findOrFail($id);
                    $storefront->update(['is_verified' => true]);

                    $this->logger->info('Travel storefront verified', [
                        'storefront_id' => $id,
                        'admin_id' => $request->user()?->id,
                        'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                    ]);

                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Витрина верифицирована',
                        'data' => $storefront,
                    ]);
                });
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'message' => 'Ошибка верификации',
                ], 500);
            }
        }
}
