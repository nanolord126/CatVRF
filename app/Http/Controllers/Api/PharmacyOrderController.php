<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;

final class PharmacyOrderController extends Controller
{

    public function __construct(
            private readonly PharmacyService $service,
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly Guard $guard,
    ) {}
        public function index(): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $orders = PharmacyOrder::where('tenant_id', $tenantId)
                    ->with('prescription')
                    ->paginate(20);
                return $this->successResponse($orders);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Pharmacy orders list error', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to fetch orders', 500);
            }
        }
        public function store(StoreOrderRequest $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check($this->guard->id() ?? 0, 'pharmacy_order_store', 0, $request->ip(), null, $correlationId);
            try {
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $order = $this->service->createOrder(
                    prescriptionId: $request->integer('prescription_id'),
                    clientId: $request->integer('client_id'),
                    medicines: json_decode((string) $request->string('medicines'), true),
                    deliveryDate: Carbon::parse($request->input('delivery_date')),
                    tenantId: $tenantId,
                    correlationId: $correlationId,
                );
                $this->logger->channel('audit')->info('Pharmacy order created', ['order_id' => $order->id]);
                return $this->successResponse($order, 'Order created successfully', 201);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Pharmacy order creation failed', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 400);
            }
        }
        public function verifyPrescription(VerifyPrescriptionRequest $request): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $tenantId = $this->guard->user()?->tenant_id ?? tenant()->id;
                $prescription = $this->service->verifyPrescription(
                    prescriptionId: $request->integer('prescription_id'),
                    verifiedBy: $request->integer('verified_by'),
                    tenantId: $tenantId,
                    correlationId: $correlationId,
                );
                $this->logger->channel('audit')->info('Prescription verified', ['prescription_id' => $request->integer('prescription_id')]);
                return $this->successResponse($prescription, 'Prescription verified');
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Prescription verification failed', ['error' => $e->getMessage()]);
                return $this->errorResponse('Failed to verify prescription: ' . $e->getMessage(), 400);
            }
        }
}
