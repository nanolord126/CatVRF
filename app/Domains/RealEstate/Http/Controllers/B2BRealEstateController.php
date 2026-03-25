<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Http\Controllers;

use App\Domains\RealEstate\Models\B2BRealEstateStorefront;
use App\Domains\RealEstate\Models\B2BRealEstateOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class B2BRealEstateController
{
	public function storefronts(): JsonResponse
	{
		try {
			return response()->json(['success' => true, 'data' => B2BRealEstateStorefront::where('is_active', true)->where('is_verified', true)->paginate(20), 'correlation_id' => Str::uuid()]);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function createStorefront(Request $request): JsonResponse
	{
		try {
			$this->authorize('createStorefront', B2BRealEstateStorefront::class);
			$v = $request->validate(['company_name' => 'required|string', 'inn' => 'required|string|unique:b2b_real_estate_storefronts,inn', 'description' => 'nullable|string', 'property_types' => 'nullable|json', 'wholesale_discount' => 'nullable|numeric|between:0,100', 'min_order_amount' => 'integer|min:1000']);
			$cId = Str::uuid()->toString();
			$this->db->transaction(fn() => B2BRealEstateStorefront::create(['uuid' => Str::uuid(), 'tenant_id' => auth()->user()->tenant_id] + $v + ['correlation_id' => $cId]) && $this->log->channel('audit')->info('RealEstate B2B: Storefront created', ['inn' => $v['inn'], 'correlation_id' => $cId]));
			return response()->json(['success' => true, 'message' => 'Витрина создана', 'correlation_id' => $cId], 201);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function createOrder(Request $request): JsonResponse
	{
		try {
			$v = $request->validate(['b2b_real_estate_storefront_id' => 'required|exists:b2b_real_estate_storefronts,id', 'company_contact_person' => 'required|string', 'company_phone' => 'required|string', 'properties_json' => 'required|json', 'total_amount' => 'required|numeric|min:1']);
			$cId = Str::uuid()->toString();
			$this->db->transaction(fn() => B2BRealEstateOrder::create(['uuid' => Str::uuid(), 'tenant_id' => auth()->user()->tenant_id, 'order_number' => 'B2B-' . Str::random(8), 'commission_amount' => (int)($v['total_amount'] * 0.14), 'status' => 'pending'] + $v + ['correlation_id' => $cId]) && $this->log->channel('audit')->info('RealEstate B2B: Order created', ['correlation_id' => $cId]));
			return response()->json(['success' => true, 'message' => 'Заказ создан', 'correlation_id' => $cId], 201);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function myB2BOrders(): JsonResponse
	{
		return response()->json(['success' => true, 'data' => B2BRealEstateOrder::where('tenant_id', auth()->user()->tenant_id)->latest()->paginate(20), 'correlation_id' => Str::uuid()]);
	}

	public function approveOrder(int $id): JsonResponse
	{
		try {
			$o = B2BRealEstateOrder::findOrFail($id);
			$this->authorize('approveOrder', $o);
			$this->db->transaction(fn() => $o->update(['status' => 'approved']));
			return response()->json(['success' => true, 'message' => 'Одобрено', 'correlation_id' => Str::uuid()]);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function rejectOrder(int $id, Request $request): JsonResponse
	{
		try {
			$o = B2BRealEstateOrder::findOrFail($id);
			$this->authorize('rejectOrder', $o);
			$this->db->transaction(fn() => $o->update(['status' => 'rejected', 'notes' => $request->get('reason', '')]));
			return response()->json(['success' => true, 'message' => 'Отклонено', 'correlation_id' => Str::uuid()]);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}

	public function verifyInn(int $id): JsonResponse
	{
		try {
			$this->authorize('verifyInn', B2BRealEstateStorefront::class);
			$this->db->transaction(fn() => B2BRealEstateStorefront::findOrFail($id)->update(['is_verified' => true]));
			return response()->json(['success' => true, 'message' => 'Верифицировано', 'correlation_id' => Str::uuid()]);
		} catch (\Exception $e) {
			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
		}
	}
}
