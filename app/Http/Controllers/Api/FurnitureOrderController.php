<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;

final class FurnitureOrderController extends Controller
{

    public function __construct(
            private readonly DeliveryAssemblyService $service,
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly Guard $guard,
    ) {}
        public function index(): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $orders = FurnitureOrder::where('tenant_id', $tenantId)
                    ->with('item')
                    ->paginate(20);
                return $this->successResponse($orders);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Furniture orders list error', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to fetch orders', 500);
            }
        }
        public function store(StoreOrderRequest $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check($this->guard->id() ?? 0, 'furniture_order_store', 0, $request->ip(), null, $correlationId);
            try {
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $order = new FurnitureOrder([
                    'tenant_id' => $tenantId,
                    'uuid' => Str::uuid(),
                    'correlation_id' => $correlationId,
                    'item_id' => $request->integer('item_id'),
                    'client_id' => $request->integer('client_id'),
                    'client_address' => $request->string('client_address'),
                    'delivery_date' => Carbon::parse($request->input('delivery_date')),
                    'status' => 'pending',
                ]);
                $order->save();
                $this->logger->channel('audit')->info('Furniture order created', ['order_id' => $order->id]);
                return $this->successResponse($order, 'Order created successfully', 201);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Furniture order creation failed', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 400);
            }
        }
        public function scheduleDelivery(int $id): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $order = FurnitureOrder::where('tenant_id', $tenantId)->findOrFail($id);
                $this->service->scheduleDelivery(
                    orderId: $id,
                    tenantId: $tenantId,
                    deliveryDate: $order->delivery_date,
                    needsAssembly: $order->assembly_date !== null,
                    correlationId: $correlationId,
                );
                $this->logger->channel('audit')->info('Furniture delivery scheduled', ['order_id' => $id]);
                return $this->successResponse($order->refresh(), 'Delivery scheduled');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Furniture delivery schedule failed', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to schedule delivery', 400);
            }
        }
}
