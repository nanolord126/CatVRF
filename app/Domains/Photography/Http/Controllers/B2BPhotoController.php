<?php declare(strict_types=1);

namespace App\Domains\Photography\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;

final class B2BPhotoController extends Controller
{

    public function __construct(private readonly B2BService $b2bService
    ,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

    	public function storefronts(): JsonResponse
    	{
    		try {
    			$storefronts = B2BPhotoStorefront::where('is_active', true)
    				->where('is_verified', true)
    				->paginate(20);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $storefronts,
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Ошибка',
    				'correlation_id' => Str::uuid(),
    			], 500);
    		}
    	}

    	public function createStorefront(Request $request): JsonResponse
    	{
    		try {
    			$this->authorize('create', B2BPhotoStorefront::class);

    			$validated = $request->validate([
    				'company_name' => 'required|string',
    				'inn' => 'required|string|unique:b2b_photo_storefronts,inn',
    				'description' => 'nullable|string',
    				'corporate_packages' => 'nullable|json',
    				'corporate_rate' => 'nullable|numeric',
    				'min_booking_hours' => 'integer|min:1',
    			]);

    			$correlationId = Str::uuid()->toString();

    			$storefront = $this->b2bService->createStorefront(
    				array_merge($validated, [
    					'tenant_id' => $request->user()->tenant_id,
    					'correlation_id' => $correlationId,
    				])
    			);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $storefront,
    				'correlation_id' => $correlationId,
    			], 201);
    		} catch (\Throwable $e) {
    			$this->logger->error('Photography B2B: Storefront creation failed', [
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

    	public function showStorefront(int $id): JsonResponse
    	{
    		try {
    			$storefront = B2BPhotoStorefront::with('b2bOrders')->findOrFail($id);
    			$this->authorize('view', $storefront);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $storefront,
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Витрина не найдена',
    				'correlation_id' => Str::uuid(),
    			], 404);
    		}
    	}

    	public function updateStorefront(int $id, Request $request): JsonResponse
    	{
    		try {
    			$storefront = B2BPhotoStorefront::findOrFail($id);
    			$this->authorize('update', $storefront);

    			$validated = $request->validate([
    				'company_name' => 'sometimes|string',
    				'description' => 'sometimes|nullable|string',
    				'corporate_rate' => 'sometimes|nullable|numeric',
    				'min_booking_hours' => 'sometimes|integer|min:1',
    			]);

    			$storefront->update($validated);

    			$this->logger->info('Photography B2B: Storefront updated', [
    				'storefront_id' => $id,
    				'correlation_id' => Str::uuid(),
    			]);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'message' => 'Витрина обновлена',
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Ошибка',
    				'correlation_id' => Str::uuid(),
    			], 500);
    		}
    	}

    	public function createOrder(Request $request): JsonResponse
    	{
    		try {
    			$this->authorize('create', B2BPhotoOrder::class);

    			$validated = $request->validate([
    				'b2b_photo_storefront_id' => 'required|exists:b2b_photo_storefronts,id',
    				'photographer_id' => 'required|exists:photographers,id',
    				'company_contact_person' => 'required|string',
    				'company_phone' => 'required|string',
    				'datetime_start' => 'required|date',
    				'duration_hours' => 'required|integer|min:1',
    				'total_amount' => 'required|numeric|min:1',
    			]);

    			$correlationId = Str::uuid()->toString();

    			$order = $this->b2bService->createB2BOrder(
    				array_merge($validated, [
    					'tenant_id' => $request->user()->tenant_id,
    					'correlation_id' => $correlationId,
    				])
    			);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $order,
    				'correlation_id' => $correlationId,
    			], 201);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Ошибка при создании заказа',
    				'correlation_id' => Str::uuid(),
    			], 500);
    		}
    	}

    	public function orders(): JsonResponse
    	{
    		try {
    			$orders = B2BPhotoOrder::paginate(20);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $orders,
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Ошибка',
    				'correlation_id' => Str::uuid(),
    			], 500);
    		}
    	}

    	public function myB2BOrders(): JsonResponse
    	{
    		try {
    			$orders = B2BPhotoOrder::where('tenant_id', $request->user()->tenant_id)
    				->latest()
    				->paginate(20);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $orders,
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Ошибка',
    				'correlation_id' => Str::uuid(),
    			], 500);
    		}
    	}

    	public function showOrder(int $id): JsonResponse
    	{
    		try {
    			$order = B2BPhotoOrder::findOrFail($id);
    			$this->authorize('view', $order);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $order,
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Заказ не найден',
    				'correlation_id' => Str::uuid(),
    			], 404);
    		}
    	}

    	public function updateOrderStatus(int $id, Request $request): JsonResponse
    	{
    		try {
    			$order = B2BPhotoOrder::findOrFail($id);
    			$this->authorize('update', $order);

    			$status = $request->validate(['status' => 'required|in:pending,approved,rejected,in_progress,completed,cancelled'])['status'];

    			$order->update(['status' => $status]);

    			$this->logger->info('Photography B2B: Order status updated', [
    				'order_id' => $id,
    				'status' => $status,
    				'correlation_id' => Str::uuid(),
    			]);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'message' => 'Статус обновлен',
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Ошибка',
    				'correlation_id' => Str::uuid(),
    			], 500);
    		}
    	}

    	public function approveOrder(int $id): JsonResponse
    	{
    		try {
    			$order = B2BPhotoOrder::findOrFail($id);
    			$this->authorize('approve', $order);

    			$this->b2bService->approveB2BOrder($order);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'message' => 'Заказ одобрен',
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Ошибка',
    				'correlation_id' => Str::uuid(),
    			], 500);
    		}
    	}

    	public function rejectOrder(int $id, Request $request): JsonResponse
    	{
    		try {
    			$order = B2BPhotoOrder::findOrFail($id);
    			$this->authorize('reject', $order);

    			$reason = $request->get('reason', 'Причина не указана');
    			$order->update(['status' => 'rejected', 'notes' => $reason]);

    			$this->logger->info('Photography B2B: Order rejected', [
    				'order_id' => $id,
    				'reason' => $reason,
    				'correlation_id' => Str::uuid(),
    			]);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'message' => 'Заказ отклонен',
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Ошибка',
    				'correlation_id' => Str::uuid(),
    			], 500);
    		}
    	}

    	public function pendingB2BOrders(): JsonResponse
    	{
    		try {
    			$orders = B2BPhotoOrder::where('status', 'pending')->paginate(20);

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'data' => $orders,
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Ошибка',
    				'correlation_id' => Str::uuid(),
    			], 500);
    		}
    	}

    	public function verifyInn(int $id): JsonResponse
    	{
    		try {
    			$this->authorize('verify', B2BPhotoStorefront::class);

    			$this->db->transaction(function () use ($id) {
    				$storefront = B2BPhotoStorefront::findOrFail($id);
    				$storefront->update(['is_verified' => true]);

    				$this->logger->info('Photography B2B: INN verified', [
    					'storefront_id' => $id,
    					'inn' => $storefront->inn,
    					'correlation_id' => Str::uuid(),
    				]);
    			});

    			return new \Illuminate\Http\JsonResponse([
    				'success' => true,
    				'message' => 'ИНН верифицирован',
    				'correlation_id' => Str::uuid(),
    			]);
    		} catch (\Throwable $e) {
    			return new \Illuminate\Http\JsonResponse([
    				'success' => false,
    				'message' => 'Ошибка',
    				'correlation_id' => Str::uuid(),
    			], 500);
    		}
    	}
}
