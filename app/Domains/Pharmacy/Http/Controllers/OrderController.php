<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Http\Controllers;

use App\Domains\Pharmacy\Services\PharmacyService;
use App\Domains\Pharmacy\Http\Requests\PharmacyOrderStoreRequest;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final class OrderController extends Controller
{
    public function __construct(
        private readonly PharmacyService $service,
        private readonly FraudControlService $fraudControl,
    ) {}

    /**
     * Создание заказа лекарств (B2C).
     * POST /pharmacy/orders
     */
    public function store(PharmacyOrderStoreRequest $request): JsonResponse
    {
        $correlationId = (string) Str::uuid();

        try {
            return DB::transaction(function () use ($request, $correlationId) {
                // 1. Fraud Check
                $fraudResult = $this->fraudControl->check([
                    'user_id' => auth()->id(),
                    'operation_type' => 'pharmacy_order',
                    'correlation_id' => $correlationId,
                    'amount' => collect($request->validated()['items'] ?? [])->sum('price'),
                ]);

                if ($fraudResult['decision'] === 'block') {
                    Log::channel('audit')->warning('Pharmacy order blocked by fraud check', [
                        'user_id' => auth()->id(),
                        'correlation_id' => $correlationId,
                        'score' => $fraudResult['score'],
                    ]);
                    return response()->json(['error' => 'Заказ заблокирован системой безопасности'], 403);
                }

                // 2. Создание заказа через сервис
                $order = $this->service->createOrder(
                    pharmacyId: $request->validated()['pharmacy_id'],
                    items: $request->validated()['items'],
                    correlationId: $correlationId
                );

                // 3. Audit Log
                Log::channel('audit')->info('Pharmacy order created (B2C)', [
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                    'total' => $order->total_price_kopecks,
                    'correlation_id' => $correlationId,
                    'timestamp' => Carbon::now(),
                ]);

                return response()->json([
                    'status' => 'success',
                    'order_id' => $order->id,
                    'order_uuid' => $order->uuid,
                    'requires_prescription' => $order->requires_prescription,
                    'total_kopecks' => $order->total_price_kopecks,
                    'correlation_id' => $correlationId,
                ], 201);
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Pharmacy order creation failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Получение статуса заказа.
     * GET /pharmacy/orders/{orderId}
     */
    public function show(int $orderId): JsonResponse
    {
        $correlationId = (string) Str::uuid();

        try {
            $order = $this->service->getOrder($orderId);

            if ($order->client_id !== auth()->id() && !auth()->user()->isPharmacyOwner($order->pharmacy_id)) {
                Log::channel('audit')->warning('Unauthorized pharmacy order access', [
                    'user_id' => auth()->id(),
                    'order_id' => $orderId,
                    'correlation_id' => $correlationId,
                ]);
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return response()->json([
                'order_id' => $order->id,
                'uuid' => $order->uuid,
                'status' => $order->status,
                'total_kopecks' => $order->total_price_kopecks,
                'delivered_at' => $order->delivered_at,
                'requires_prescription' => $order->requires_prescription,
                'correlation_id' => $correlationId,
            ], 200);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Pharmacy order fetch failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return response()->json(['error' => 'Order not found'], 404);
        }
    }

    /**
     * Отправка рецепта для заказа (для рецептурных препаратов).
     * POST /pharmacy/orders/{orderId}/prescription
     */
    public function uploadPrescription(int $orderId, PharmacyOrderStoreRequest $request): JsonResponse
    {
        $correlationId = (string) Str::uuid();

        try {
            return DB::transaction(function () use ($orderId, $request, $correlationId) {
                $this->service->validatePrescription(
                    orderId: $orderId,
                    rxData: $request->validated()['prescription_data'] ?? '',
                    correlationId: $correlationId
                );

                Log::channel('audit')->info('Pharmacy prescription uploaded', [
                    'order_id' => $orderId,
                    'user_id' => auth()->id(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Рецепт получен и обработан',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Pharmacy prescription validation failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
