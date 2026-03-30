<?php declare(strict_types=1);

namespace App\Domains\Medical\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BMedicalController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function storefronts(): JsonResponse
        {
            $storefronts = B2BMedicalStorefront::where('tenant_id', tenant()->id)
                ->paginate(15);

            return response()->json([
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
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {
                $validated = $request->validate([
                    'company_name' => 'required|string|max:255',
                    'inn' => 'required|string|unique:b2b_medical_storefronts',
                    'description' => 'nullable|string',
                    'wholesale_discount' => 'nullable|numeric|min:0|max:100',
                    'min_order_amount' => 'numeric|min:1000',
                ]);

                return DB::transaction(function () use ($validated, $correlationId) {
                    $storefront = B2BMedicalStorefront::create([
                        'tenant_id' => tenant()->id,
                        'correlation_id' => $correlationId,
                        ...$validated,
                    ]);

                    Log::channel('audit')->info('B2B Medical storefront created', [
                        'storefront_id' => $storefront->id,
                        'correlation_id' => $correlationId,
                        'user_id' => auth()->id(),
                    ]);

                    return response()->json([
                        'data' => $storefront,
                        'message' => 'Витрина создана',
                        'correlation_id' => $correlationId,
                    ], 201);
                });
            } catch (\Exception $e) {
                Log::channel('audit')->error('Medical storefront creation failed', [
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'message' => 'Ошибка создания витрины',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function createOrder(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {
                $validated = $request->validate([
                    'storefront_id' => 'required|exists:b2b_medical_storefronts,id',
                    'items' => 'required|array|min:1',
                    'items.*.product_id' => 'required|integer',
                    'items.*.quantity' => 'required|integer|min:1',
                ]);

                return DB::transaction(function () use ($validated, $correlationId) {
                    $storefront = B2BMedicalStorefront::findOrFail($validated['storefront_id']);
                    $commission = ($validated['items'][0]['quantity'] ?? 1) * 0.14;

                    Log::channel('audit')->info('B2B Medical order created', [
                        'storefront_id' => $storefront->id,
                        'correlation_id' => $correlationId,
                        'commission' => $commission,
                    ]);

                    return response()->json([
                        'message' => 'Заказ создан',
                        'correlation_id' => $correlationId,
                        'commission' => $commission,
                    ], 201);
                });
            } catch (\Exception $e) {
                Log::channel('audit')->error('Medical order creation failed', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Ошибка создания заказа',
                ], 500);
            }
        }

        public function myB2BOrders(): JsonResponse
        {
            $orders = B2BMedicalStorefront::where('tenant_id', tenant()->id)
                ->latest()
                ->paginate(10);

            return response()->json([
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
                return DB::transaction(function () use ($id) {
                    $order = B2BMedicalStorefront::findOrFail($id);
                    $order->update(['status' => 'approved']);

                    Log::channel('audit')->info('Medical order approved', [
                        'order_id' => $id,
                        'user_id' => auth()->id(),
                    ]);

                    return response()->json([
                        'message' => 'Заказ одобрен',
                        'data' => $order,
                    ]);
                });
            } catch (\Exception $e) {
                return response()->json([
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

                return DB::transaction(function () use ($id, $validated) {
                    $order = B2BMedicalStorefront::findOrFail($id);
                    $order->update([
                        'status' => 'rejected',
                        'rejection_reason' => $validated['reason'],
                    ]);

                    Log::channel('audit')->info('Medical order rejected', [
                        'order_id' => $id,
                        'reason' => $validated['reason'],
                    ]);

                    return response()->json([
                        'message' => 'Заказ отклонён',
                    ]);
                });
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Ошибка отклонения',
                ], 500);
            }
        }

        public function verifyInn(int $id): JsonResponse
        {
            try {
                return DB::transaction(function () use ($id) {
                    $storefront = B2BMedicalStorefront::findOrFail($id);
                    $storefront->update(['is_verified' => true]);

                    Log::channel('audit')->info('Medical storefront verified', [
                        'storefront_id' => $id,
                        'admin_id' => auth()->id(),
                    ]);

                    return response()->json([
                        'message' => 'Витрина верифицирована',
                        'data' => $storefront,
                    ]);
                });
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Ошибка верификации',
                ], 500);
            }
        }
}
