<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;

final class OfficeCateringOrderController extends Controller
{

    public function __construct(
            private readonly OfficeCateringService $service,
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly Guard $guard,
    ) {}
        public function index(): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $orders = CorporateOrder::where('tenant_id', $tenantId)
                    ->with('menu', 'client')
                    ->paginate(20);
                return $this->successResponse($orders);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('OfficeCatering orders list error', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to fetch orders', 500);
            }
        }
        public function store(StoreOrderRequest $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check($this->guard->id() ?? 0, 'catering_order_store', 0, $request->ip(), null, $correlationId);
            try {
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $order = $this->service->placeOrder(
                    clientId: $request->integer('client_id'),
                    menuId: $request->integer('menu_id'),
                    portions: $request->integer('portions'),
                    deliveryDate: Carbon::parse($request->input('delivery_date')),
                    tenantId: $tenantId,
                    correlationId: $correlationId,
                );
                $this->logger->channel('audit')->info('OfficeCatering order created', ['order_id' => $order->id]);
                return $this->successResponse($order, 'Order placed successfully', 201);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('OfficeCatering order creation failed', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to place order: ' . $e->getMessage(), 400);
            }
        }
        public function setupRecurring(int $id): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $order = CorporateOrder::where('tenant_id', $tenantId)->findOrFail($id);
                $recurring = $this->service->setupRecurring(
                    orderId: $id,
                    frequency: 'weekly',
                    tenantId: $tenantId,
                    correlationId: $correlationId,
                );
                $this->logger->channel('audit')->info('OfficeCatering recurring setup', ['order_id' => $id]);
                return $this->successResponse($recurring, 'Recurring order setup');
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('OfficeCatering recurring setup failed', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to setup recurring', 400);
            }
        }
}
