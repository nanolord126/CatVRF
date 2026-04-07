<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class B2BHotelController extends Controller
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}



    public function storefronts(): JsonResponse
    {
        $data = B2BHotelStorefront::where('is_active', true)->where('is_verified', true)->paginate(20);

        return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $data, 'correlation_id' => (string) Str::uuid()]);
    }

    	public function createStorefront(Request $request): JsonResponse
    	{
    		try {
    			$this->authorize('createStorefront', B2BHotelStorefront::class);
    			$v = $request->validate(['company_name' => 'required', 'inn' => 'required|unique:b2b_hotel_storefronts,inn', 'description' => 'nullable', 'room_types' => 'nullable|json', 'wholesale_discount' => 'nullable|numeric|between:0,100', 'min_booking_nights' => 'integer|min:1']);
    			$cId = Str::uuid()->toString();
    			$this->db->transaction(fn() => B2BHotelStorefront::create(['uuid' => Str::uuid(), 'tenant_id' => $request->user()->tenant_id] + $v + ['correlation_id' => $cId]) && $this->logger->info('Hotel B2B: Storefront created', ['inn' => $v['inn'], 'correlation_id' => $cId]));
    			return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Витрина создана', 'correlation_id' => $cId], 201);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
    		}
    	}
    	public function createOrder(Request $request): JsonResponse
    	{
    		try {
    			$v = $request->validate(['b2b_hotel_storefront_id' => 'required|exists:b2b_hotel_storefronts,id', 'company_contact_person' => 'required', 'company_phone' => 'required', 'booking_details' => 'required|json', 'total_amount' => 'required|numeric|min:1']);
    			$cId = Str::uuid()->toString();
    			$this->db->transaction(fn() => B2BHotelOrder::create(['uuid' => Str::uuid(), 'tenant_id' => $request->user()->tenant_id, 'order_number' => 'B2B-' . Str::random(8), 'commission_amount' => (int)($v['total_amount'] * 0.14), 'status' => 'pending'] + $v + ['correlation_id' => $cId]));
    			return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Заказ создан', 'correlation_id' => $cId], 201);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
    		}
    	}
    	public function myB2BOrders(Request $request): JsonResponse
    	{
    		$data = B2BHotelOrder::where('tenant_id', $request->user()->tenant_id)->latest()->paginate(20);

    		return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $data, 'correlation_id' => (string) Str::uuid()]);
    	}
    	public function approveOrder(int $id): JsonResponse
    	{
    		try {
    			$o = B2BHotelOrder::findOrFail($id);
    			$this->authorize('approveOrder', $o);
    			$this->db->transaction(fn() => $o->update(['status' => 'approved']));
    			return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Одобрено', 'correlation_id' => Str::uuid()]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
    		}
    	}
    	public function rejectOrder(int $id, Request $request): JsonResponse
    	{
    		try {
    			$o = B2BHotelOrder::findOrFail($id);
    			$this->authorize('rejectOrder', $o);
    			$this->db->transaction(fn() => $o->update(['status' => 'rejected', 'notes' => $request->get('reason', '')]));
    			return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Отклонено', 'correlation_id' => Str::uuid()]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
    		}
    	}
    	public function verifyInn(int $id): JsonResponse
    	{
    		try {
    			$this->authorize('verifyInn', B2BHotelStorefront::class);
    			$this->db->transaction(fn() => B2BHotelStorefront::findOrFail($id)->update(['is_verified' => true]));
    			return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Верифицировано', 'correlation_id' => Str::uuid()]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка', 'correlation_id' => Str::uuid()], 500);
    		}
    	}
}
