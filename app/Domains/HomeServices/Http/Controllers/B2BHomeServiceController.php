<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Http\Controllers;

use App\Domains\HomeServices\Models\B2BHomeServiceStorefront;
use App\Domains\HomeServices\Models\B2BHomeServiceOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class B2BHomeServiceController
{
    public function storefronts(): JsonResponse
    {
        try {
            $storefronts = B2BHomeServiceStorefront::where('is_active', true)
                ->where('is_verified', true)
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $storefronts,
                'correlation_id' => Str::uuid(),
            ], 200);
        } catch (\Exception $e) {
            Log::channel('audit')->error('HomeServices B2B: Failed to fetch storefronts', [
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке витрин',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function createStorefront(Request $request): JsonResponse
    {
        try {
            $this->authorize('createStorefront', B2BHomeServiceStorefront::class);

            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'inn' => 'required|string|unique:b2b_home_service_storefronts,inn',
                'description' => 'nullable|string',
                'service_categories' => 'nullable|json',
                'wholesale_discount' => 'nullable|numeric|between:0,100',
                'min_order_amount' => 'integer|min:1000',
            ]);

            $correlationId = Str::uuid()->toString();

            DB::transaction(function () use ($validated, $correlationId) {
                B2BHomeServiceStorefront::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => auth()->user()->tenant_id,
                    ...$validated,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('HomeServices B2B: Storefront created', [
                    'inn' => $validated['inn'],
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Витрина создана',
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Exception $e) {
            Log::channel('audit')->error('HomeServices B2B: Storefront creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

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
                'b2b_home_service_storefront_id' => 'required|exists:b2b_home_service_storefronts,id',
                'company_contact_person' => 'required|string',
                'company_phone' => 'required|string',
                'items_json' => 'required|json',
                'total_amount' => 'required|numeric|min:1',
            ]);

            $correlationId = Str::uuid()->toString();
            $commission = (int) ($validated['total_amount'] * 0.14);

            DB::transaction(function () use ($validated, $correlationId, $commission) {
                B2BHomeServiceOrder::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => auth()->user()->tenant_id,
                    'order_number' => 'B2B-' . Str::random(8),
                    'commission_amount' => $commission,
                    'status' => 'pending',
                    ...$validated,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('HomeServices B2B: Order created', [
                    'amount' => $validated['total_amount'],
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Заказ создан',
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Exception $e) {
            Log::channel('audit')->error('HomeServices B2B: Order creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании заказа',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function myB2BOrders(): JsonResponse
    {
        try {
            $orders = B2BHomeServiceOrder::where('tenant_id', auth()->user()->tenant_id)
                ->latest()
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $orders,
                'correlation_id' => Str::uuid(),
            ], 200);
        } catch (\Exception $e) {
            Log::channel('audit')->error('HomeServices B2B: Failed to fetch orders', [
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке заказов',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function approveOrder(int $id): JsonResponse
    {
        try {
            $order = B2BHomeServiceOrder::findOrFail($id);
            $this->authorize('approveOrder', $order);

            $correlationId = Str::uuid()->toString();

            DB::transaction(function () use ($order, $correlationId) {
                $order->update(['status' => 'approved']);

                Log::channel('audit')->info('HomeServices B2B: Order approved', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Заказ одобрен',
                'correlation_id' => $correlationId,
            ], 200);
        } catch (\Exception $e) {
            Log::channel('audit')->error('HomeServices B2B: Order approval failed', [
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

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
            $order = B2BHomeServiceOrder::findOrFail($id);
            $this->authorize('rejectOrder', $order);

            $correlationId = Str::uuid()->toString();
            $reason = $request->get('reason', '');

            DB::transaction(function () use ($order, $correlationId, $reason) {
                $order->update([
                    'status' => 'rejected',
                    'notes' => $reason,
                ]);

                Log::channel('audit')->info('HomeServices B2B: Order rejected', [
                    'order_id' => $order->id,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Заказ отклонен',
                'correlation_id' => $correlationId,
            ], 200);
        } catch (\Exception $e) {
            Log::channel('audit')->error('HomeServices B2B: Order rejection failed', [
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

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
            $this->authorize('verifyInn', B2BHomeServiceStorefront::class);

            $storefront = B2BHomeServiceStorefront::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            DB::transaction(function () use ($storefront, $correlationId) {
                $storefront->update(['is_verified' => true]);

                Log::channel('audit')->info('HomeServices B2B: Storefront verified', [
                    'storefront_id' => $storefront->id,
                    'inn' => $storefront->inn,
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Витрина верифицирована',
                'correlation_id' => $correlationId,
            ], 200);
        } catch (\Exception $e) {
            Log::channel('audit')->error('HomeServices B2B: Verification failed', [
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при верификации',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }
}

