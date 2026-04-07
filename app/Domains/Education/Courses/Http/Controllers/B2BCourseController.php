<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class B2BCourseController extends Controller
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}


    public function storefronts(): JsonResponse
        {
            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => B2BCourseStorefront::where('is_active', true)
                    ->where('is_verified', true)
                    ->paginate(20),
                'correlation_id' => Str::uuid(),
            ]);
        }

        public function createStorefront(Request $request): JsonResponse
        {
            try {
                $this->authorize('createStorefront', B2BCourseStorefront::class);

                $validated = $request->validate([
                    'company_name' => 'required|string|max:255',
                    'inn' => 'required|string|unique:b2b_course_storefronts,inn',
                    'description' => 'nullable|string',
                    'service_categories' => 'nullable|json',
                    'wholesale_discount' => 'nullable|numeric|between:0,100',
                    'min_order_amount' => 'integer|min:1000',
                ]);

                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($validated, $correlationId) {
                    B2BCourseStorefront::create([
                        'uuid' => Str::uuid(),
                        'tenant_id' => $request->user()->tenant_id,
                        ...$validated,
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Course B2B: Storefront created', [
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
                $this->logger->error('Course B2B: Storefront creation failed', [
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
                    'b2b_course_storefront_id' => 'required|exists:b2b_course_storefronts,id',
                    'company_contact_person' => 'required|string|max:255',
                    'company_phone' => 'required|string|max:20',
                    'items_json' => 'required|json',
                    'total_amount' => 'required|numeric|min:1',
                ]);

                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($validated, $correlationId) {
                    B2BCourseOrder::create([
                        'uuid' => Str::uuid(),
                        'tenant_id' => $request->user()->tenant_id,
                        'order_number' => 'B2B-' . Str::random(8),
                        'commission_amount' => (int) ($validated['total_amount'] * 0.14),
                        'status' => 'pending',
                        ...$validated,
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Course B2B: Order created', [
                        'order_number' => 'B2B-' . Str::random(8),
                        'total_amount' => $validated['total_amount'],
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Заказ создан',
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Course B2B: Order creation failed', [
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
            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => B2BCourseOrder::where('tenant_id', $request->user()->tenant_id)
                    ->latest()
                    ->paginate(20),
                'correlation_id' => Str::uuid(),
            ]);
        }

        public function approveOrder(int $id): JsonResponse
        {
            try {
                $order = B2BCourseOrder::findOrFail($id);
                $this->authorize('approveOrder', $order);

                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($order, $correlationId) {
                    $order->update(['status' => 'approved']);

                    $this->logger->info('Course B2B: Order approved', [
                        'order_id' => $order->id,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Заказ одобрен',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Course B2B: Order approval failed', [
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
                $order = B2BCourseOrder::findOrFail($id);
                $this->authorize('rejectOrder', $order);

                $correlationId = Str::uuid()->toString();
                $reason = $request->get('reason', '');

                $this->db->transaction(function () use ($order, $reason, $correlationId) {
                    $order->update([
                        'status' => 'rejected',
                        'notes' => $reason,
                    ]);

                    $this->logger->info('Course B2B: Order rejected', [
                        'order_id' => $order->id,
                        'reason' => $reason,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Заказ отклонен',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Course B2B: Order rejection failed', [
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
                $this->authorize('verifyInn', B2BCourseStorefront::class);

                $storefront = B2BCourseStorefront::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($storefront, $correlationId) {
                    $storefront->update(['is_verified' => true]);

                    $this->logger->info('Course B2B: Storefront verified', [
                        'storefront_id' => $storefront->id,
                        'inn' => $storefront->inn,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Витрина верифицирована',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Course B2B: Storefront verification failed', [
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
