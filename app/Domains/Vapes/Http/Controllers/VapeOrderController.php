<?php

declare(strict_types=1);

namespace App\Domains\Vapes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Vapes\Http\Requests\VapeOrderRequest;
use App\Domains\Vapes\Services\VapeOrderService;
use App\Domains\Vapes\Services\VapeAgeVerificationService;
use App\Domains\Vapes\Models\VapeOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * VapeOrderController — Production Ready 2026
 * 
 * Контроллер заказов вейп-вертикали.
 * 
 * Обязательные компоненты:
 * - try/catch + JsonResponse с correlation_id
 * - Audit log на каждое действие
 * - Rate limiting через middleware
 * - DB::transaction через Service Layer
 */
final class VapeOrderController extends Controller
{
    /**
     * Конструктор с DP.
     */
    public function __construct(
        private VapeOrderService $orderService,
        private VapeAgeVerificationService $ageVerifier,
    ) {}

    /**
     * Создать новый заказ на устройства или жидкости.
     * Эндпоинт защищен 18+ гейтом внутри сервиса.
     */
    public function store(VapeOrderRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

        try {
            // 1. Создаем заказ через сервис
            $order = $this->orderService->createOrder(
                userId: auth()->id(),
                params: $request->validated(),
                correlationId: $correlationId,
            );

            // 2. Audit log
            Log::channel('audit')->info('Vape order controller: created', [
                'order_uuid' => $order->uuid,
                'user_id' => auth()->id(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'order' => $order,
                'correlation_id' => $correlationId,
            ], 201);

        } catch (Throwable $e) {
            
            // 3. Error Log + Trace
            Log::channel('audit')->error('Vape order controller error store', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal error creating vape order: ' . $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Получить информацию о заказе.
     */
    public function show(string $uuid): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID') ?? (string) Str::uuid();

        try {
            // 4. Глобальный теннант-скопинг уже применен в модели VapeOrder::booted()
            $order = VapeOrder::where('uuid', $uuid)->firstOrFail();

            return response()->json([
                'success' => true,
                'order' => $order,
                'correlation_id' => $correlationId,
            ]);

        } catch (Throwable $e) {
            
            Log::channel('audit')->warning('Vape order not found show', [
                'order_uuid' => $uuid,
                'user_id' => auth()->id(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'correlation_id' => $correlationId,
            ], 404);
        }
    }

    /**
     * Отмена заказа. Возвращает средства в Wallet, если оплачен.
     */
    public function cancel(string $uuid): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID') ?? (string) Str::uuid();

        try {
            $this->orderService->cancelOrder($uuid, 'Cancelled by user', $correlationId);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'correlation_id' => $correlationId,
            ]);

        } catch (Throwable $e) {
            
            Log::channel('audit')->error('Vape order cancel failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 400);
        }
    }
}
