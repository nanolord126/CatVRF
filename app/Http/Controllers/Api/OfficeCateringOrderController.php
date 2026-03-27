<?php
declare(strict_types=1);
namespace App\Http\Controllers\API;
use App\Domains\OfficeCatering\Models\CorporateOrder;
use App\Domains\OfficeCatering\Services\OfficeCateringService;
use App\Http\Requests\OfficeCatering\StoreOrderRequest;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
final class OfficeCateringOrderController extends BaseApiController
{
    public function __construct(
        private readonly OfficeCateringService $service,
        private readonly FraudControlService $fraudControlService,
    ) {}
    public function index(): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;
            $orders = CorporateOrder::where('tenant_id', $tenantId)
                ->with('menu', 'client')
                ->paginate(20);
            return $this->successResponse($orders);
        } catch (\Exception $e) {
            Log::channel('audit')->error('OfficeCatering orders list error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch orders', 500);
        }
    }
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'catering_order_store', 0, $request->ip(), null, $correlationId);
        try {
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;
            $order = $this->service->placeOrder(
                clientId: $request->integer('client_id'),
                menuId: $request->integer('menu_id'),
                portions: $request->integer('portions'),
                deliveryDate: Carbon::parse($request->input('delivery_date')),
                tenantId: $tenantId,
                correlationId: $correlationId,
            );
            Log::channel('audit')->info('OfficeCatering order created', ['order_id' => $order->id]);
            return $this->successResponse($order, 'Order placed successfully', 201);
        } catch (\Exception $e) {
            Log::channel('audit')->error('OfficeCatering order creation failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to place order: ' . $e->getMessage(), 400);
        }
    }
    public function setupRecurring(int $id): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;
            $order = CorporateOrder::where('tenant_id', $tenantId)->findOrFail($id);
            $recurring = $this->service->setupRecurring(
                orderId: $id,
                frequency: 'weekly',
                tenantId: $tenantId,
                correlationId: $correlationId,
            );
            Log::channel('audit')->info('OfficeCatering recurring setup', ['order_id' => $id]);
            return $this->successResponse($recurring, 'Recurring order setup');
        } catch (\Exception $e) {
            Log::channel('audit')->error('OfficeCatering recurring setup failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to setup recurring', 400);
        }
    }
}
