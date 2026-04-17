<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;

final class ConfectioneryOrderController extends Controller
{

    public function __construct(
            private readonly ConfectioneryService $service,
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly Guard $guard,
    ) {}
        public function index(): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $orders = BakeryOrder::where('tenant_id', $tenantId)
                    ->with('cake')
                    ->paginate(20);
                return $this->successResponse($orders);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Confectionery orders list error', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to fetch orders', 500);
            }
        }
        public function store(StoreOrderRequest $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check($this->guard->id() ?? 0, 'confectionery_order_store', 0, $request->ip(), null, $correlationId);
            try {
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $order = $this->service->createOrder(
                    cakeId: $request->integer('cake_id'),
                    clientId: $request->integer('client_id'),
                    deliveryDate: Carbon::parse($request->input('delivery_date')),
                    tenantId: $tenantId,
                    correlationId: $correlationId,
                );
                $this->logger->channel('audit')->info('Confectionery order created', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);
                return $this->successResponse($order, 'Order created successfully', 201);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Confectionery order creation failed', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 400);
            }
        }
        public function markReady(int $id): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $order = BakeryOrder::where('tenant_id', $tenantId)->findOrFail($id);
                $this->service->markReady(
                    orderId: $id,
                    tenantId: $tenantId,
                    correlationId: $correlationId,
                );
                $this->logger->channel('audit')->info('Confectionery order marked ready', ['order_id' => $id]);
                return $this->successResponse($order->refresh(), 'Order marked as ready');
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Confectionery mark ready failed', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to mark order as ready', 400);
            }
        }
}
