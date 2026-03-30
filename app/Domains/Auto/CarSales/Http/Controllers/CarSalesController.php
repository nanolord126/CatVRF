<?php declare(strict_types=1);

namespace App\Domains\Auto\CarSales\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BAutoController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function storefronts(): JsonResponse
    	{
    		try {
    			$storefronts = B2BAutoStorefront::where('is_active', true)
    				->where('is_verified', true)
    				->paginate(20);

    			return response()->json(['success' => true, 'data' => $storefronts, 'correlation_id' => Str::uuid()]);
    		} catch (\Exception $e) {
    			Log::channel('audit')->error('Auto B2B: Storefronts failed', ['error' => $e->getMessage()]);
    			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
    		}
    	}

    	public function createStorefront(Request $request): JsonResponse
    	{
    		try {
    			$this->authorize('createStorefront', B2BAutoStorefront::class);

    			$validated = $request->validate([
    				'company_name' => 'required|string',
    				'inn' => 'required|string|unique:b2b_auto_storefronts,inn',
    				'description' => 'nullable|string',
    				'auto_brands' => 'nullable|json',
    				'wholesale_discount' => 'nullable|numeric|between:0,100',
    				'min_order_amount' => 'integer|min:1000',
    			]);

    			$correlationId = Str::uuid()->toString();

    			DB::transaction(function () use ($validated, $correlationId) {
    				B2BAutoStorefront::create([
    					'uuid' => Str::uuid(),
    					'tenant_id' => auth()->user()->tenant_id,
    					'company_name' => $validated['company_name'],
    					'inn' => $validated['inn'],
    					'description' => $validated['description'],
    					'auto_brands' => $validated['auto_brands'],
    					'wholesale_discount' => $validated['wholesale_discount'] ?? 0,
    					'min_order_amount' => $validated['min_order_amount'] ?? 50000,
    					'correlation_id' => $correlationId,
    				]);

    				Log::channel('audit')->info('Auto B2B: Storefront created', [
    					'inn' => $validated['inn'],
    					'correlation_id' => $correlationId,
    				]);
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
    				'b2b_auto_storefront_id' => 'required|exists:b2b_auto_storefronts,id',
    				'company_contact_person' => 'required|string',
    				'company_phone' => 'required|string',
    				'items_json' => 'required|json',
    				'total_amount' => 'required|numeric|min:1',
    			]);

    			$correlationId = Str::uuid()->toString();

    			DB::transaction(function () use ($validated, $correlationId) {
    				B2BAutoOrder::create([
    					'uuid' => Str::uuid(),
    					'tenant_id' => auth()->user()->tenant_id,
    					'b2b_auto_storefront_id' => $validated['b2b_auto_storefront_id'],
    					'order_number' => 'B2B-' . Str::random(8),
    					'company_contact_person' => $validated['company_contact_person'],
    					'company_phone' => $validated['company_phone'],
    					'items_json' => $validated['items_json'],
    					'total_amount' => $validated['total_amount'],
    					'commission_amount' => (int) ($validated['total_amount'] * 0.14),
    					'status' => 'pending',
    					'correlation_id' => $correlationId,
    				]);

    				Log::channel('audit')->info('Auto B2B: Order created', ['correlation_id' => $correlationId]);
    			});

    			return response()->json(['success' => true, 'message' => 'Заказ создан', 'correlation_id' => $correlationId], 201);
    		} catch (\Exception $e) {
    			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
    		}
    	}

    	public function myB2BOrders(): JsonResponse
    	{
    		try {
    			$orders = B2BAutoOrder::where('tenant_id', auth()->user()->tenant_id)->latest()->paginate(20);
    			return response()->json(['success' => true, 'data' => $orders, 'correlation_id' => Str::uuid()]);
    		} catch (\Exception $e) {
    			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
    		}
    	}

    	public function approveOrder(int $id): JsonResponse
    	{
    		try {
    			$order = B2BAutoOrder::findOrFail($id);
    			$this->authorize('approveOrder', $order);

    			DB::transaction(function () use ($order) {
    				$order->update(['status' => 'approved']);
    				Log::channel('audit')->info('Auto B2B: Order approved', ['order_id' => $order->id]);
    			});

    			return response()->json(['success' => true, 'message' => 'Одобрено', 'correlation_id' => Str::uuid()]);
    		} catch (\Exception $e) {
    			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
    		}
    	}

    	public function rejectOrder(int $id, Request $request): JsonResponse
    	{
    		try {
    			$order = B2BAutoOrder::findOrFail($id);
    			$this->authorize('rejectOrder', $order);

       $validated = $request->all();
    			DB::transaction(function () use ($order, $validated) {
    				$order->update(['status' => 'rejected', 'notes' => ($validated['reason'] ?? '')]);
    				Log::channel('audit')->info('Auto B2B: Order rejected', ['order_id' => $order->id]);
    			});

    			return response()->json(['success' => true, 'message' => 'Отклонено', 'correlation_id' => Str::uuid()]);
    		} catch (\Exception $e) {
    			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
    		}
    	}

    	public function verifyInn(int $id): JsonResponse
    	{
    		try {
    			$this->authorize('verifyInn', B2BAutoStorefront::class);

    			DB::transaction(function () use ($id) {
    				B2BAutoStorefront::findOrFail($id)->update(['is_verified' => true]);
    				Log::channel('audit')->info('Auto B2B: INN verified', ['storefront_id' => $id]);
    			});

    			return response()->json(['success' => true, 'message' => 'Верифицировано', 'correlation_id' => Str::uuid()]);
    		} catch (\Exception $e) {
    			return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
    		}
    	}
}
