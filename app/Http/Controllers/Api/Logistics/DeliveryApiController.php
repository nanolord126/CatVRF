<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\Logistics;
use App\Http\Controllers\Controller;
use App\Domains\Logistics\Services\DeliveryOrderService;
use App\Domains\Logistics\Services\SurgePricingService;
use App\Domains\Logistics\Models\DeliveryOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * КАНОН 2026 — API ДЛЯ ЗАКАЗОВ ДОСТАВКИ
 * 1. Rate Limiting (через middleware)
 * 2. Correlation ID в каждом ответе
 * 3. Валидация через FormRequest
 */
final class DeliveryApiController extends Controller
{
    public function __construct(
        private readonly DeliveryOrderService $orderService,
        private readonly SurgePricingService $surgeService
    ) {}
    /**
     * Создать новый заказ на доставку
     */
    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $validated = $request->validate([
            'pickup_address' => 'required|string',
            'pickup_lat' => 'required|numeric',
            'pickup_lon' => 'required|numeric',
            'dropoff_address' => 'required|string',
            'dropoff_lat' => 'required|numeric',
            'dropoff_lon' => 'required|numeric',
            'items' => 'required|array',
        ]);
        try {
            $order = $this->orderService->createOrder($validated, $correlationId);
            return response()->json([
                'success' => true,
                'data' => [
                    'order_uuid' => $order->uuid,
                    'status' => $order->status,
                    'total_price' => $order->total_price_kopecks,
                    'surge' => $order->surge_multiplier,
                ],
                'correlation_id' => $correlationId
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('API Order Creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create delivery order',
                'correlation_id' => $correlationId
            ], 500);
        }
    }
    /**
     * Получить статус заказа
     */
    public function show(string $uuid): JsonResponse
    {
        $order = DeliveryOrder::where('uuid', $uuid)
            ->where('tenant_id', tenant('id'))
            ->firstOrFail();
        return response()->json([
            'success' => true,
            'data' => [
                'status' => $order->status,
                'courier' => $order->courier ? [
                    'name' => $order->courier->full_name,
                    'lat' => $order->courier->last_lat,
                    'lon' => $order->courier->last_lon,
                ] : null,
            ]
        ]);
    }
    /**
     * Оценка стоимости (Surge Preview)
     */
    public function estimate(Request $request): JsonResponse
    {
        $lat = (float) $request->input('lat');
        $lon = (float) $request->input('lon');
        $surge = $this->surgeService->calculateSurge($lat, $lon, 'logistics');
        return response()->json([
            'success' => true,
            'surge_multiplier' => $surge,
            'estimated_base_price' => 50000, // 500 руб база
            'estimated_total' => (int) (50000 * $surge)
        ]);
    }
}
