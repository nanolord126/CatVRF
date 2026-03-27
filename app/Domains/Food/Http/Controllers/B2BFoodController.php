<?php

declare(strict_types=1);

namespace App\Domains\Food\Http\Controllers;

use App\Domains\Food\Models\B2BFoodStorefront;
use App\Domains\Food\Models\B2BFoodOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class B2BFoodController
{
	public function storefronts(): JsonResponse
	{
		try {
			$storefronts = B2BFoodStorefront::where('is_active', true)
				->where('is_verified', true)->paginate(20);
			return response()->json(['success' => true, 'data' => $storefronts, 'correlation_id' => Str::uuid()]);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function createStorefront(Request $request): JsonResponse
	{
		try {
			$this->authorize('createStorefront', B2BFoodStorefront::class);
			$validated = $request->validate([
				'company_name' => 'required|string',
				'inn' => 'required|string|unique:b2b_food_storefronts,inn',
				'description' => 'nullable|string',
				'cuisine_types' => 'nullable|json',
				'wholesale_discount' => 'nullable|numeric|between:0,100',
				'min_order_amount' => 'integer|min:1000',
			]);

			$correlationId = Str::uuid()->toString();

			DB::transaction(function () use ($validated, $correlationId) {
				B2BFoodStorefront::create([
					'uuid' => Str::uuid(),
					'tenant_id' => auth()->user()->tenant_id,
					'company_name' => $validated['company_name'],
					'inn' => $validated['inn'],
					'description' => $validated['description'],
					'cuisine_types' => $validated['cuisine_types'],
					'wholesale_discount' => $validated['wholesale_discount'] ?? 0,
					'min_order_amount' => $validated['min_order_amount'] ?? 50000,
					'correlation_id' => $correlationId,
				]);

				Log::channel('audit')->info('Food B2B: Storefront created', ['inn' => $validated['inn'], 'correlation_id' => $correlationId]);
			});

			return response()->json(['success' => true, 'message' => 'Витрина создана', 'correlation_id' => $correlationId], 201);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function createOrder(Request $request): JsonResponse
	{
		try {
			$validated = $request->validate([
				'b2b_food_storefront_id' => 'required|exists:b2b_food_storefronts,id',
				'company_contact_person' => 'required|string',
				'company_phone' => 'required|string',
				'items_json' => 'required|json',
				'total_amount' => 'required|numeric|min:1',
			]);

			$correlationId = Str::uuid()->toString();

			DB::transaction(function () use ($validated, $correlationId) {
				B2BFoodOrder::create([
					'uuid' => Str::uuid(),
					'tenant_id' => auth()->user()->tenant_id,
					'b2b_food_storefront_id' => $validated['b2b_food_storefront_id'],
					'order_number' => 'B2B-' . Str::random(8),
					'company_contact_person' => $validated['company_contact_person'],
					'company_phone' => $validated['company_phone'],
					'items_json' => $validated['items_json'],
					'total_amount' => $validated['total_amount'],
					'commission_amount' => (int) ($validated['total_amount'] * 0.14),
					'status' => 'pending',
					'correlation_id' => $correlationId,
				]);

				Log::channel('audit')->info('Food B2B: Order created', ['correlation_id' => $correlationId]);
			});

			return response()->json(['success' => true, 'message' => 'Заказ создан', 'correlation_id' => $correlationId], 201);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function myB2BOrders(): JsonResponse
	{
		try {
			$orders = B2BFoodOrder::where('tenant_id', auth()->user()->tenant_id)->latest()->paginate(20);
			return response()->json(['success' => true, 'data' => $orders, 'correlation_id' => Str::uuid()]);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function approveOrder(int $id): JsonResponse
	{
		try {
			$order = B2BFoodOrder::findOrFail($id);
			$this->authorize('approveOrder', $order);
			DB::transaction(fn() => $order->update(['status' => 'approved']));
			return response()->json(['success' => true, 'message' => 'Одобрено', 'correlation_id' => Str::uuid()]);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function rejectOrder(int $id, Request $request): JsonResponse
	{
		try {
			$order = B2BFoodOrder::findOrFail($id);
			$this->authorize('rejectOrder', $order);
			DB::transaction(fn() => $order->update(['status' => 'rejected', 'notes' => $request->get('reason', '')]));
			return response()->json(['success' => true, 'message' => 'Отклонено', 'correlation_id' => Str::uuid()]);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function verifyInn(int $id): JsonResponse
	{
		try {
			$this->authorize('verifyInn', B2BFoodStorefront::class);
			DB::transaction(fn() => B2BFoodStorefront::findOrFail($id)->update(['is_verified' => true]));
			return response()->json(['success' => true, 'message' => 'Верифицировано', 'correlation_id' => Str::uuid()]);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}
}
