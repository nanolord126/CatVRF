<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\HomeAppliance;
use App\Http\Controllers\Controller;
use App\Domains\HouseholdGoods\HomeAppliance\Models\ApplianceRepairOrder;
use App\Domains\HouseholdGoods\HomeAppliance\Services\ApplianceRepairService;
use App\Http\Requests\Api\HomeAppliance\ApplianceRepairRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * ApplianceRepairApiController — Канон 2026.
 * B2C/B2B создание заявок на ремонт техники.
 */
final class ApplianceRepairApiController extends Controller
{
    public function __construct(
        private readonly ApplianceRepairService $repairService
    ) {}
    /**
     * Создание заявки на ремонт.
     */
    public function store(ApplianceRepairRequest $request): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        try {
            // 1. Создание базового заказа (с валидацией)
            $order = ApplianceRepairOrder::create([
                'client_id' => auth()->id() ?? $request->get('client_id'),
                'appliance_type' => $request->get('appliance_type'),
                'brand_name' => $request->get('brand_name'),
                'model_number' => $request->get('model_number'),
                'issue_description' => $request->get('issue_description'),
                'is_b2b' => $request->boolean('is_b2b'),
                'address_json' => $request->get('address'),
                'status' => 'pending',
                'correlation_id' => $correlationId,
                'tags' => ['api_source', $request->boolean('is_b2b') ? 'b2b_client' : 'b2c_client']
            ]);
            Log::channel('audit')->info('HomeAppliance repair order created via API', [
                'order_uuid' => $order->uuid,
                'is_b2b' => $order->is_b2b,
                'correlation_id' => $correlationId
            ]);
            return response()->json([
                'success' => true,
                'order_uuid' => $order->uuid,
                'status' => $order->status,
                'correlation_id' => $correlationId
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to create repair order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать заявку. Повторите попытку позже.',
                'correlation_id' => $correlationId
            ], 500);
        }
    }
    /**
     * Статус заказа и гарантия.
     */
    public function show(string $uuid): JsonResponse
    {
        $order = ApplianceRepairOrder::where('uuid', $uuid)->firstOrFail();
        return response()->json([
            'order_uuid' => $order->uuid,
            'status' => $order->status,
            'total_cost' => $order->total_cost_kopecks,
            'warranty_until' => $order->warranty_expires_at?->toIso8601String(),
            'visit_at' => $order->visit_scheduled_at?->toIso8601String(),
            'correlation_id' => $order->correlation_id
        ]);
    }
}
