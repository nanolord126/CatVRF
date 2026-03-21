<?php declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;

use App\Domains\Sports\Models\B2BSportStorefront;
use App\Domains\Sports\Models\B2BSportOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class B2BSportController
{
    public function storefronts(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => B2BSportStorefront::where('is_active', true)
                ->where('is_verified', true)
                ->paginate(20),
            'correlation_id' => Str::uuid(),
        ]);
    }

    public function createStorefront(Request $request): JsonResponse
    {
        try {
            $this->authorize('createStorefront', B2BSportStorefront::class);

            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'inn' => 'required|string|unique:b2b_sport_storefronts,inn',
                'description' => 'nullable|string',
                'service_categories' => 'nullable|json',
                'wholesale_discount' => 'nullable|numeric|between:0,100',
                'min_order_amount' => 'integer|min:1000',
            ]);

            $correlationId = Str::uuid()->toString();

            DB::transaction(function () use ($validated, $correlationId) {
                B2BSportStorefront::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => auth()->user()->tenant_id,
                    ...$validated,
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Витрина создана',
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
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
                'b2b_sport_storefront_id' => 'required|exists:b2b_sport_storefronts,id',
                'company_contact_person' => 'required|string|max:255',
                'company_phone' => 'required|string|max:20',
                'items_json' => 'required|json',
                'total_amount' => 'required|numeric|min:1',
            ]);

            $correlationId = Str::uuid()->toString();

            DB::transaction(function () use ($validated, $correlationId) {
                B2BSportOrder::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => auth()->user()->tenant_id,
                    'order_number' => 'B2B-' . Str::random(8),
                    'commission_amount' => (int) ($validated['total_amount'] * 0.14),
                    'status' => 'pending',
                    ...$validated,
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Заказ создан',
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании заказа',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function myB2BOrders(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => B2BSportOrder::where('tenant_id', auth()->user()->tenant_id)
                ->latest()
                ->paginate(20),
            'correlation_id' => Str::uuid(),
        ]);
    }

    public function approveOrder(int $id): JsonResponse
    {
        try {
            $order = B2BSportOrder::findOrFail($id);
            $this->authorize('approveOrder', $order);

            $correlationId = Str::uuid()->toString();

            DB::transaction(function () use ($order) {
                $order->update(['status' => 'approved']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Заказ одобрен',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при одобрении заказа',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function rejectOrder(int $id, Request $request): JsonResponse
    {
        try {
            $order = B2BSportOrder::findOrFail($id);
            $this->authorize('rejectOrder', $order);

            $correlationId = Str::uuid()->toString();
            $reason = $request->get('reason', '');

            DB::transaction(function () use ($order, $reason) {
                $order->update([
                    'status' => 'rejected',
                    'notes' => $reason,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Заказ отклонен',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отклонении заказа',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function verifyInn(int $id): JsonResponse
    {
        try {
            $this->authorize('verifyInn', B2BSportStorefront::class);

            $storefront = B2BSportStorefront::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            DB::transaction(function () use ($storefront) {
                $storefront->update(['is_verified' => true]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Витрина верифицирована',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при верификации',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }
}
