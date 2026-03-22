<?php
declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Domains\Pharmacy\Models\PharmacyOrder;
use App\Domains\Pharmacy\Models\Prescription;
use App\Domains\Pharmacy\Services\PharmacyService;
use App\Http\Requests\Pharmacy\StoreOrderRequest;
use App\Http\Requests\Pharmacy\VerifyPrescriptionRequest;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PharmacyOrderController extends BaseApiController
{
    public function __construct(
        private readonly PharmacyService $service,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;

            $orders = PharmacyOrder::where('tenant_id', $tenantId)
                ->with('prescription')
                ->paginate(20);

            return $this->successResponse($orders);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Pharmacy orders list error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch orders', 500);
        }
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'pharmacy_order_store', 0, $request->ip(), null, $correlationId);

        try {
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;

            $order = $this->service->createOrder(
                prescriptionId: $request->integer('prescription_id'),
                clientId: $request->integer('client_id'),
                medicines: json_decode((string) $request->string('medicines'), true),
                deliveryDate: Carbon::parse($request->input('delivery_date')),
                tenantId: $tenantId,
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('Pharmacy order created', ['order_id' => $order->id]);

            return $this->successResponse($order, 'Order created successfully', 201);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Pharmacy order creation failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 400);
        }
    }

    public function verifyPrescription(VerifyPrescriptionRequest $request): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;

            $prescription = $this->service->verifyPrescription(
                prescriptionId: $request->integer('prescription_id'),
                verifiedBy: $request->integer('verified_by'),
                tenantId: $tenantId,
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('Prescription verified', ['prescription_id' => $request->integer('prescription_id')]);

            return $this->successResponse($prescription, 'Prescription verified');
        } catch (\Exception $e) {
            Log::channel('audit')->error('Prescription verification failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to verify prescription: ' . $e->getMessage(), 400);
        }
    }
}
