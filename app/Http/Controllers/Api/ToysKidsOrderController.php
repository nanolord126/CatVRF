<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;

final class ToysKidsOrderController extends Controller
{

    public function __construct(
            private readonly ToyOrderService $service,
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly Guard $guard,
    ) {}
        public function index(): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $orders = ToyOrder::where('tenant_id', $tenantId)
                    ->with('product')
                    ->paginate(20);
                return $this->successResponse($orders);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('ToysKids orders list error', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to fetch orders', 500);
            }
        }
        public function store(StoreOrderRequest $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check($this->guard->id() ?? 0, 'toys_order_store', 0, $request->ip(), null, $correlationId);
            try {
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $order = $this->service->createOrder(
                    productId: $request->integer('product_id'),
                    clientId: $request->integer('client_id'),
                    quantity: $request->integer('quantity'),
                    giftWrapping: $request->boolean('gift_wrapping', false),
                    deliveryDate: Carbon::parse($request->input('delivery_date')),
                    tenantId: $tenantId,
                    correlationId: $correlationId,
                );
                $this->logger->channel('audit')->info('ToysKids order created', ['order_id' => $order->id]);
                return $this->successResponse($order, 'Order created successfully', 201);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('ToysKids order creation failed', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 400);
            }
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
