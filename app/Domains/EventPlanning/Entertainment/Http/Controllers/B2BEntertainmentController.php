<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class B2BEntertainmentController extends Controller
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}


    public function storefronts(): JsonResponse
        {
            try {
                $storefronts = B2BEntertainmentStorefront::where('is_active', true)
                    ->where('is_verified', true)
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $storefronts,
                    'correlation_id' => Str::uuid(),
                ], 200);
            } catch (\Throwable $e) {
                $this->logger->error('Entertainment B2B: Failed to fetch storefronts', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при загрузке витрин',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function createStorefront(Request $request): JsonResponse
        {
            try {
                $this->authorize('createStorefront', B2BEntertainmentStorefront::class);

                $validated = $request->validate([
                    'company_name' => 'required|string|max:255',
                    'inn' => 'required|string|unique:b2b_entertainment_storefronts,inn',
                    'description' => 'nullable|string',
                    'service_categories' => 'nullable|json',
                    'wholesale_discount' => 'nullable|numeric|between:0,100',
                    'min_order_amount' => 'integer|min:1000',
                ]);

                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($validated, $correlationId) {
                    B2BEntertainmentStorefront::create([
                        'uuid' => Str::uuid(),
                        'tenant_id' => $request->user()->tenant_id,
                        ...$validated,
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Entertainment B2B: Storefront created', [
                        'inn' => $validated['inn'],
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Витрина создана',
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Entertainment B2B: Storefront creation failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при создании витрины',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function createOrder(Request $request): JsonResponse
        {
            try {
                $validated = $request->validate([
                    'b2b_entertainment_storefront_id' => 'required|exists:b2b_entertainment_storefronts,id',
                    'company_contact_person' => 'required|string',
                    'company_phone' => 'required|string',
                    'items_json' => 'required|json',
                    'total_amount' => 'required|numeric|min:1',
                ]);

                $correlationId = Str::uuid()->toString();
                $commission = (int) ($validated['total_amount'] * 0.14);

                $this->db->transaction(function () use ($validated, $correlationId, $commission) {
                    B2BEntertainmentOrder::create([
                        'uuid' => Str::uuid(),
                        'tenant_id' => $request->user()->tenant_id,
                        'order_number' => 'B2B-' . Str::random(8),
                        'commission_amount' => $commission,
                        'status' => 'pending',
                        ...$validated,
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Entertainment B2B: Order created', [
                        'amount' => $validated['total_amount'],
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Заказ создан',
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Entertainment B2B: Order creation failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при создании заказа',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function myB2BOrders(): JsonResponse
        {
            try {
                $orders = B2BEntertainmentOrder::where('tenant_id', $request->user()->tenant_id)
                    ->latest()
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $orders,
                    'correlation_id' => Str::uuid(),
                ], 200);
            } catch (\Throwable $e) {
                $this->logger->error('Entertainment B2B: Failed to fetch orders', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при загрузке заказов',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function approveOrder(int $id): JsonResponse
        {
            try {
                $order = B2BEntertainmentOrder::findOrFail($id);
                $this->authorize('approveOrder', $order);

                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($order, $correlationId) {
                    $order->update(['status' => 'approved']);

                    $this->logger->info('Entertainment B2B: Order approved', [
                        'order_id' => $order->id,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Заказ одобрен',
                    'correlation_id' => $correlationId,
                ], 200);
            } catch (\Throwable $e) {
                $this->logger->error('Entertainment B2B: Order approval failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при одобрении заказа',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function rejectOrder(int $id, Request $request): JsonResponse
        {
            try {
                $order = B2BEntertainmentOrder::findOrFail($id);
                $this->authorize('rejectOrder', $order);

                $correlationId = Str::uuid()->toString();
                $reason = $request->get('reason', '');

                $this->db->transaction(function () use ($order, $correlationId, $reason) {
                    $order->update([
                        'status' => 'rejected',
                        'notes' => $reason,
                    ]);

                    $this->logger->info('Entertainment B2B: Order rejected', [
                        'order_id' => $order->id,
                        'reason' => $reason,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Заказ отклонен',
                    'correlation_id' => $correlationId,
                ], 200);
            } catch (\Throwable $e) {
                $this->logger->error('Entertainment B2B: Order rejection failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при отклонении заказа',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function verifyInn(int $id): JsonResponse
        {
            try {
                $this->authorize('verifyInn', B2BEntertainmentStorefront::class);

                $storefront = B2BEntertainmentStorefront::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($storefront, $correlationId) {
                    $storefront->update(['is_verified' => true]);

                    $this->logger->info('Entertainment B2B: Storefront verified', [
                        'storefront_id' => $storefront->id,
                        'inn' => $storefront->inn,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Витрина верифицирована',
                    'correlation_id' => $correlationId,
                ], 200);
            } catch (\Throwable $e) {
                $this->logger->error('Entertainment B2B: Verification failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при верификации',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }
}
