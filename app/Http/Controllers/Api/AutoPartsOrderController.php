<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;

final class AutoPartsOrderController extends Controller
{

    public function __construct(
        private readonly Request $request,
            private VINCompatibilityService $service,
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly Guard $guard,
    ) {}
        public function index(): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $orders = AutoPartOrder::where('tenant_id', $tenantId)
                    ->with('part')
                    ->paginate(20);
                return $this->successResponse($orders);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('AutoParts orders list error', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to fetch orders', 500);
            }
        }
        public function store(StoreOrderRequest $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check($this->guard->id() ?? 0, 'operation', 0, $this->request->ip(), null, $correlationId);
            try {
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $order = $this->service->createOrder(
                    partId: $request->integer('part_id'),
                    clientId: $request->integer('client_id'),
                    vin: $request->string('vin'),
                    quantity: $request->integer('quantity'),
                    deliveryDate: Carbon::parse($request->input('delivery_date')),
                    tenantId: $tenantId,
                    correlationId: $correlationId,
                );
                $this->logger->channel('audit')->info('AutoParts order created', ['order_id' => $order->id]);
                return $this->successResponse($order, 'Order created successfully', 201);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('AutoParts order creation failed', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 400);
            }
        }
        public function findCompatible(string $vin): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $parts = $this->service->findCompatibleParts($vin, $tenantId);
                $this->logger->channel('audit')->info('AutoParts compatible search', ['vin' => $vin]);
                return $this->successResponse($parts, 'Compatible parts found');
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('AutoParts compatibility search failed', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to search parts: ' . $e->getMessage(), 400);
            }
        }
}
