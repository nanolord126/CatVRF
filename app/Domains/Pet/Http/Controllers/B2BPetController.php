<?php declare(strict_types=1);

namespace App\Domains\Pet\Http\Controllers;

use App\Domains\Pet\Models\B2BPetStorefront;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class B2BPetController
{
    public function storefronts(): JsonResponse
    {
        $storefronts = B2BPetStorefront::where('tenant_id', tenant()->id)
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
        try {
            FraudControlService::check();

            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'inn' => 'required|string|unique:b2b_pet_storefronts',
                'description' => 'nullable|string',
                'wholesale_discount' => 'nullable|numeric|min:0|max:100',
                'min_order_amount' => 'numeric|min:1000',
            ]);

            $correlationId = Str::uuid();

            return DB::transaction(function () use ($validated, $correlationId) {
                $storefront = B2BPetStorefront::create([
                    'tenant_id' => tenant()->id,
                    'correlation_id' => $correlationId,
                    ...$validated,
                ]);

                Log::channel('audit')->info('B2B Pet storefront created', [
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
            Log::channel('audit')->error('Pet storefront creation failed', [
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
        try {
            FraudControlService::check();

            $validated = $request->validate([
                'storefront_id' => 'required|exists:b2b_pet_storefronts,id',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            $correlationId = Str::uuid();

            return DB::transaction(function () use ($validated, $correlationId) {
                $storefront = B2BPetStorefront::findOrFail($validated['storefront_id']);
                $commission = ($validated['items'][0]['quantity'] ?? 1) * 0.14;

                Log::channel('audit')->info('B2B Pet order created', [
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
            Log::channel('audit')->error('Pet order creation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка создания заказа',
            ], 500);
        }
    }

    public function myB2BOrders(): JsonResponse
    {
        $orders = B2BPetStorefront::where('tenant_id', tenant()->id)
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
                $order = B2BPetStorefront::findOrFail($id);
                $order->update(['status' => 'approved']);

                Log::channel('audit')->info('Pet order approved', [
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
                $order = B2BPetStorefront::findOrFail($id);
                $order->update([
                    'status' => 'rejected',
                    'rejection_reason' => $validated['reason'],
                ]);

                Log::channel('audit')->info('Pet order rejected', [
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
                $storefront = B2BPetStorefront::findOrFail($id);
                $storefront->update(['is_verified' => true]);

                Log::channel('audit')->info('Pet storefront verified', [
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
