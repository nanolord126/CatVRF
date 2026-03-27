<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use App\Domains\Beauty\Models\B2BBeautyStorefront;
use App\Domains\Beauty\Models\B2BBeautyOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class B2BBeautyController
{
	public function storefronts(): JsonResponse
	{
		try {
			$storefronts = B2BBeautyStorefront::where('is_active', true)
				->where('is_verified', true)
				->paginate(20);

			return response()->json([
				'success' => true,
				'data' => $storefronts,
				'correlation_id' => Str::uuid(),
			]);
		} catch (\Exception $e) {
			Log::channel('audit')->error('Beauty B2B: Storefronts list failed', ['error' => $e->getMessage()]);
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function createStorefront(Request $request): JsonResponse
	{
		try {
			$this->authorize('createStorefront', B2BBeautyStorefront::class);

			$validated = $request->validate([
				'company_name' => 'required|string',
				'inn' => 'required|string|unique:b2b_beauty_storefronts,inn',
				'description' => 'nullable|string',
				'wholesale_discount' => 'nullable|numeric|between:0,100',
				'min_order_amount' => 'integer|min:1000',
			]);

			$correlationId = Str::uuid()->toString();

			DB::transaction(function () use ($validated, $correlationId) {
				$storefront = B2BBeautyStorefront::create([
					'uuid' => Str::uuid(),
					'tenant_id' => auth()->user()->tenant_id,
					'company_name' => $validated['company_name'],
					'inn' => $validated['inn'],
					'description' => $validated['description'],
					'wholesale_discount' => $validated['wholesale_discount'] ?? 0,
					'min_order_amount' => $validated['min_order_amount'] ?? 50000,
					'correlation_id' => $correlationId,
				]);

				Log::channel('audit')->info('Beauty B2B: Storefront created', [
					'storefront_id' => $storefront->id,
					'inn' => $validated['inn'],
					'correlation_id' => $correlationId,
				]);
			});

			return response()->json(['success' => true, 'message' => 'Витрина создана', 'correlation_id' => $correlationId], 201);
		} catch (\Exception $e) {
			Log::channel('audit')->error('Beauty B2B: Storefront creation failed', ['error' => $e->getMessage()]);
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function createOrder(Request $request): JsonResponse
	{
		try {
			$validated = $request->validate([
				'b2b_beauty_storefront_id' => 'required|exists:b2b_beauty_storefronts,id',
				'company_contact_person' => 'required|string',
				'company_phone' => 'required|string',
				'items_json' => 'required|json',
				'total_amount' => 'required|numeric|min:1',
			]);

			$correlationId = Str::uuid()->toString();

			DB::transaction(function () use ($validated, $correlationId) {
				$order = B2BBeautyOrder::create([
					'uuid' => Str::uuid(),
					'tenant_id' => auth()->user()->tenant_id,
					'b2b_beauty_storefront_id' => $validated['b2b_beauty_storefront_id'],
					'order_number' => 'B2B-' . Str::random(8),
					'company_contact_person' => $validated['company_contact_person'],
					'company_phone' => $validated['company_phone'],
					'items_json' => $validated['items_json'],
					'total_amount' => $validated['total_amount'],
					'commission_amount' => (int) ($validated['total_amount'] * 0.14),
					'status' => 'pending',
					'correlation_id' => $correlationId,
				]);

				Log::channel('audit')->info('Beauty B2B: Order created', [
					'order_id' => $order->id,
					'amount' => $validated['total_amount'],
					'correlation_id' => $correlationId,
				]);
			});

			return response()->json(['success' => true, 'message' => 'Заказ создан', 'correlation_id' => $correlationId], 201);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function myB2BOrders(): JsonResponse
	{
		try {
			$orders = B2BBeautyOrder::where('tenant_id', auth()->user()->tenant_id)
				->latest()
				->paginate(20);

			return response()->json(['success' => true, 'data' => $orders, 'correlation_id' => Str::uuid()]);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function approveOrder(int $id): JsonResponse
	{
		try {
			$order = B2BBeautyOrder::findOrFail($id);
			$this->authorize('approveOrder', $order);

			DB::transaction(function () use ($order) {
				$order->update(['status' => 'approved']);
				Log::channel('audit')->info('Beauty B2B: Order approved', ['order_id' => $order->id, 'correlation_id' => $order->correlation_id]);
			});

			return response()->json(['success' => true, 'message' => 'Заказ одобрен', 'correlation_id' => Str::uuid()]);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function rejectOrder(int $id, Request $request): JsonResponse
	{
		try {
			$order = B2BBeautyOrder::findOrFail($id);
			$this->authorize('rejectOrder', $order);

			$reason = $request->get('reason', 'Причина не указана');

			DB::transaction(function () use ($order, $reason) {
				$order->update(['status' => 'rejected', 'notes' => $reason]);
				Log::channel('audit')->info('Beauty B2B: Order rejected', ['order_id' => $order->id, 'correlation_id' => $order->correlation_id]);
			});

			return response()->json(['success' => true, 'message' => 'Заказ отклонен', 'correlation_id' => Str::uuid()]);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function verifyInn(int $id): JsonResponse
	{
		try {
			$this->authorize('verifyInn', B2BBeautyStorefront::class);

			DB::transaction(function () use ($id) {
				$storefront = B2BBeautyStorefront::findOrFail($id);
				$storefront->update(['is_verified' => true]);
				Log::channel('audit')->info('Beauty B2B: INN verified', ['storefront_id' => $id, 'inn' => $storefront->inn]);
			});

			return response()->json(['success' => true, 'message' => 'ИНН верифицирован', 'correlation_id' => Str::uuid()]);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}
}
